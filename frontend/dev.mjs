import { spawn, spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { createServer } from 'vite';

const frontend = fileURLToPath(new URL('.', import.meta.url));
const backend = fileURLToPath(new URL('../backend/', import.meta.url));
let php;
let vite;
let stopping = false;
let stop;
const stopped = new Promise(resolve => { stop = resolve; });
let phpExited;

function requestStop(code = 0) {
    if (stopping) return;
    stopping = true;
    process.exitCode = code;
    stop();
}

process.on('SIGINT', () => requestStop());
process.on('SIGTERM', () => requestStop());

function findPhp() {
    const explicit = process.env.PHP_BINARY;
    const candidates = explicit ? [explicit] : [
        'php', process.platform === 'win32' ? 'C:\\xampp\\php\\php.exe' : '/opt/lampp/bin/php',
    ];
    for (const binary of candidates) {
        const probe = spawnSync(binary, ['-r', 'exit(PHP_SAPI === "cli" ? 0 : 1);'], {
            timeout: 2000, killSignal: 'SIGKILL', stdio: 'ignore', windowsHide: true,
        });
        if (!probe.error && probe.status === 0) return binary;
    }
    throw new Error(explicit ? 'PHP_BINARY must point to a working PHP CLI executable.' :
        'PHP CLI not found. Install PHP/XAMPP or set PHP_BINARY to its executable.');
}

try {
    const binary = findPhp();
    const env = { ...process.env };
    delete env.PHP_CLI_SERVER_WORKERS;
    let ready;
    let fail;
    const phpReady = new Promise((resolve, reject) => { ready = resolve; fail = reject; });
    php = spawn(binary, ['-S', '127.0.0.1:8000', '-t', backend], {
        env, stdio: ['ignore', 'ignore', 'pipe'], windowsHide: true,
    });
    phpExited = new Promise(resolve => {
        php.once('error', () => {
            fail(new Error('Could not start PHP CLI.'));
            resolve();
        });
        php.once('exit', () => {
            fail(new Error('PHP exited before startup; check whether port 8000 is occupied.'));
            if (!stopping) {
                console.error('PHP stopped unexpectedly; stopping development servers.');
                requestStop(1);
            }
            resolve();
        });
    });
    let output = '';
    php.stderr.on('data', chunk => {
        output = (output + chunk).slice(-4096);
        if (/Development Server \(http:\/\/127\.0\.0\.1:8000\) started/.test(output)) ready();
    });
    const timeout = setTimeout(() => fail(new Error('PHP startup timed out.')), 5000);
    try {
        await Promise.race([phpReady, stopped]);
    } finally {
        clearTimeout(timeout);
    }
    if (!stopping) {
        const signalHandlers = new Set(process.listeners('SIGTERM'));
        const stdinHandlers = new Set(process.stdin.listeners('end'));
        vite = await createServer({
            root: frontend,
            configFile: `${frontend}vite.config.js`,
            plugins: [{
                name: 'owned-development-lifecycle',
                configureServer() {
                    for (const listener of process.listeners('SIGTERM')) {
                        if (!signalHandlers.has(listener)) process.off('SIGTERM', listener);
                    }
                    for (const listener of process.stdin.listeners('end')) {
                        if (!stdinHandlers.has(listener)) process.stdin.off('end', listener);
                    }
                },
            }],
        });
        if (!stopping) await vite.listen();
        if (!stopping) vite.printUrls();
    }
    await stopped;
} catch (error) {
    console.error(error.message);
    requestStop(1);
} finally {
    requestStop();
    const closingVite = vite?.close();
    try {
        if (php && php.exitCode === null && php.signalCode === null && php.pid) {
            php.kill('SIGTERM');
            let timer;
            await Promise.race([
                phpExited,
                new Promise(resolve => { timer = setTimeout(resolve, 2000); }),
            ]);
            clearTimeout(timer);
            if (php.exitCode === null && php.signalCode === null) php.kill('SIGKILL');
        }
        if (phpExited) {
            let timer;
            try {
                await Promise.race([
                    phpExited,
                    new Promise((resolve, reject) => {
                        timer = setTimeout(() => reject(new Error('PHP did not exit after termination.')), 2000);
                    }),
                ]);
            } finally {
                clearTimeout(timer);
            }
        }
    } catch (error) {
        process.exitCode = 1;
        console.error(error.message);
    } finally {
        const deadline = setTimeout(() => {
            console.error('Vite shutdown timed out; exiting.');
            process.exit(process.exitCode ?? 1);
        }, 3000);
        try {
            await closingVite;
        } finally {
            clearTimeout(deadline);
        }
    }
}
