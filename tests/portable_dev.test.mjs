import assert from 'node:assert/strict';
import { mkdtemp, mkdir, cp, symlink, writeFile, chmod, rm, readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { spawn } from 'node:child_process';
import { once } from 'node:events';
import { createServer } from 'node:http';
import { setTimeout as delay } from 'node:timers/promises';

const frontend = fileURLToPath(new URL('../frontend/', import.meta.url));
const scratch = await mkdtemp('/tmp/opencode/pasos-portable-');
await chmod(scratch, 0o700);
const project = `${scratch}/project with spaces`;
await mkdir(project);
await cp(frontend, `${project}/frontend`, {
    recursive: true, filter: path => !/\/(node_modules|dist|\.vite)(\/|$)/.test(path),
});
await symlink(`${frontend}node_modules`, `${project}/frontend/node_modules`);
await cp(fileURLToPath(new URL('../backend/', import.meta.url)), `${project}/backend`, {
    recursive: true, filter: path => !path.endsWith('/google.local.php'),
});
await writeFile(`${project}/backend/api/probe.php`, `<?php
header('Content-Type: application/json');
echo json_encode(['uri' => $_SERVER['REQUEST_URI'], 'query' => $_GET]);
`);
const children = new Set();
const sentinels = new Set();
const php = '/opt/lampp/bin/php';
const launcher = `${project}/frontend/dev.mjs`;

function request(url, options = {}) {
    return fetch(url, {
        ...options, signal: AbortSignal.timeout(4000),
        headers: { Connection: 'close', ...options.headers },
    });
}

function start(env = {}, npmWrapper = false) {
    const child = spawn(npmWrapper ? 'npm' : process.execPath,
        npmWrapper ? ['--prefix', `${project}/frontend`, 'run', 'dev'] : [launcher], {
        cwd: scratch, env: { ...process.env, ...env }, stdio: ['ignore', 'pipe', 'pipe'],
        detached: npmWrapper,
    });
    children.add(child);
    child.output = '';
    child.stdout.on('data', chunk => { child.output += chunk; });
    child.stderr.on('data', chunk => { child.output += chunk; });
    child.done = once(child, 'exit').then(([code, signal]) => {
        children.delete(child);
        return { code, signal };
    });
    return child;
}

async function until(check, message, timeout = 12000) {
    const deadline = Date.now() + timeout;
    while (Date.now() < deadline) {
        if (await check()) return;
        await delay(40);
    }
    assert.fail(message);
}

async function exited(child) {
    return Promise.race([child.done, delay(12000, undefined, { ref: false }).then(() => assert.fail(`Launcher did not exit: ${child.output}`))]);
}

async function sentinel(port, host = '127.0.0.1') {
    const server = createServer((req, res) => res.end('sentinel'));
    server.listen(port, host);
    await once(server, 'listening');
    sentinels.add(server);
    return server;
}

async function release(server) {
    await new Promise(resolve => server.close(resolve));
    sentinels.delete(server);
}

async function portsFree() {
    const backend = await sentinel(8000);
    const vite = await sentinel(5174, 'localhost');
    await release(backend);
    await release(vite);
}

async function ready(child) {
    await until(() => child.output.includes('http://localhost:5174/'), 'Launcher did not become ready.');
}

async function fakePhp(mode) {
    const binary = `${scratch}/php-${mode}`;
    await writeFile(binary, `#!${process.execPath}
if (process.argv[2] === '-r') {
    ${mode === 'probe-timeout' ? 'setInterval(() => {}, 1000);' : mode === 'spawn-error' ? "require('node:fs').unlinkSync(__filename); process.exit(0);" : 'process.exit(0);'}
} else {
require('node:fs').writeFileSync(${JSON.stringify(`${scratch}/child.pid`)}, String(process.pid));
${mode === 'error' ? "process.exit(7);" : "setInterval(() => {}, 1000);"}
}
`);
    await chmod(binary, 0o700);
    return binary;
}

try {
    assert.equal(JSON.parse(await readFile(`${frontend}package.json`)).scripts.dev, 'node dev.mjs');
    await portsFree();
    const invalid = start({ PHP_BINARY: `${scratch}/missing php` });
    assert.equal((await exited(invalid)).code, 1);
    assert.match(invalid.output, /PHP_BINARY/);
    await portsFree();
    for (const mode of ['probe-timeout', 'spawn-error']) {
        const child = start({ PHP_BINARY: await fakePhp(mode) });
        assert.equal((await exited(child)).code, 1);
        await portsFree();
    }
    console.log('PASS PHP discovery errors.');

    for (const port of [8000, 5174]) {
        const host = port === 5174 ? 'localhost' : '127.0.0.1';
        const occupied = await sentinel(port, host);
        const child = start({ PHP_BINARY: php });
        assert.equal((await exited(child)).code, 1);
        assert.equal(await (await request(`http://${host}:${port}`)).text(), 'sentinel');
        await release(occupied);
        await portsFree();
    }
    console.log('PASS occupied ports preserve sentinels.');

    const child = start({ PHP_BINARY: '', PATH: scratch });
    await ready(child);
    assert.deepEqual(await (await request('http://localhost:5174/backend/api/probe.php/123?item=1')).json(), {
        uri: '/api/probe.php/123?item=1', query: { item: '1' },
    });
    const unrelated = await request('http://localhost:5174/backendextra/api/probe.php');
    assert.notEqual(unrelated.headers.get('content-type'), 'application/json');
    await unrelated.text();
    let response = await request('http://localhost:5174/backend/captcha.php?fresh=1');
    assert.equal(response.status, 200);
    assert.match(response.headers.get('content-type'), /application\/json/);
    assert.equal(typeof (await response.json()).pregunta, 'string');
    const cookie = response.headers.get('set-cookie');
    assert.match(cookie, /path=\//i);
    assert.ok(cookie.startsWith('PHPSESSID='));
    response = await request('http://localhost:5174/backend/captcha.php', {
        headers: { Cookie: cookie.split(';')[0] },
    });
    assert.equal(response.status, 200);
    assert.equal(response.headers.get('set-cookie'), null);
    await response.json();
    for (const route of ['sesion_actual.php', 'api/google_auth.php', 'api/consultations.php/123?item=1']) {
        response = await request(`http://localhost:5174/backend/${route}`);
        assert.equal(response.status, 401, route);
        assert.match(response.headers.get('content-type'), /application\/json/);
        await response.json();
    }
    for (const route of ['pasos.sql', 'PASOS-entrega-privada.zip', '.git/config']) {
        response = await request(`http://127.0.0.1:8000/${route}`);
        assert.equal(response.status, 404);
        await response.text();
    }
    child.kill('SIGINT');
    assert.equal((await exited(child)).code, 0);
    await portsFree();
    console.log('PASS fallback, spaces, proxy, cookies and normal shutdown.');

    await symlink(php, `${scratch}/php`);
    const pathChild = start({ PHP_BINARY: '', PATH: scratch });
    await ready(pathChild);
    pathChild.kill('SIGTERM');
    assert.equal((await exited(pathChild)).code, 0);
    await portsFree();
    console.log('PASS PATH PHP and SIGTERM shutdown.');

    const configPath = `${project}/frontend/vite.config.js`;
    const config = await readFile(configPath, 'utf8');
    await writeFile(configPath, config.replace('plugins: [vue()]', "plugins: [vue(), { name: 'slow-close', closeBundle() { return new Promise(() => {}); } }]"));
    const slowClose = start({ PHP_BINARY: php });
    await ready(slowClose);
    slowClose.kill('SIGINT');
    assert.equal((await exited(slowClose)).code, 0);
    assert.match(slowClose.output, /Vite shutdown timed out/);
    await portsFree();
    console.log('PASS bounded Vite cleanup after owned PHP exit.');
    await writeFile(configPath, `${config}\nawait import('node:fs/promises').then(fs => fs.writeFile(${JSON.stringify(`${scratch}/vite-starting`)}, 'ready'));\nawait new Promise(resolve => setTimeout(resolve, 1000));\n`);
    const interruptedVite = start({ PHP_BINARY: php });
    await until(async () => {
        try { return await readFile(`${scratch}/vite-starting`, 'utf8'); }
        catch { return false; }
    }, 'Vite initialization did not start.');
    interruptedVite.kill('SIGTERM');
    assert.equal((await exited(interruptedVite)).code, 0);
    await writeFile(configPath, config);
    await portsFree();
    console.log('PASS interruption during Vite initialization.');

    for (const mode of ['error', 'timeout', 'interrupt']) {
        const child = start({ PHP_BINARY: await fakePhp(mode) });
        if (mode === 'interrupt') {
            await until(async () => {
                try { return (await readFile(`${scratch}/child.pid`, 'utf8')).length > 0; }
                catch { return false; }
            }, 'Fake PHP did not start.');
            child.kill('SIGTERM');
        }
        assert.equal((await exited(child)).code, mode === 'interrupt' ? 0 : 1);
        if (mode === 'timeout') assert.match(child.output, /timed out/i);
        const pid = Number(await readFile(`${scratch}/child.pid`, 'utf8'));
        assert.throws(() => process.kill(pid, 0), { code: 'ESRCH' });
        await rm(`${scratch}/child.pid`, { force: true });
        await portsFree();
    }

    if (process.platform === 'linux') {
        const child = start({ PHP_BINARY: php });
        await ready(child);
        const ids = (await readFile(`/proc/${child.pid}/task/${child.pid}/children`, 'utf8')).trim().split(/\s+/);
        let phpPid;
        for (const id of ids) {
            if ((await readFile(`/proc/${id}/cmdline`, 'utf8')).startsWith(`${php}\0`)) phpPid = Number(id);
        }
        assert.ok(phpPid, 'Owned PHP child not found.');
        process.kill(phpPid, 'SIGTERM');
        assert.equal((await exited(child)).code, 1);
        await portsFree();
    }
    if (process.platform !== 'win32') {
        const wrapper = start({ PHP_BINARY: php }, true);
        const closed = once(wrapper, 'close');
        try {
            await ready(wrapper);
            process.kill(-wrapper.pid, 'SIGINT');
            await Promise.race([
                closed,
                delay(12000, undefined, { ref: false }).then(() => assert.fail('npm process group did not close.')),
            ]);
            await portsFree();
            console.log('PASS actual npm from unrelated cwd/spaced path: process-group Ctrl+C releases both ports.');
            console.log('SKIP native Windows terminal shutdown: POSIX process groups do not verify Windows.');
        } finally {
            for (const signal of ['SIGTERM', 'SIGKILL']) {
                try { process.kill(-wrapper.pid, signal); }
                catch (error) { if (error.code !== 'ESRCH') throw error; }
                await Promise.race([wrapper.done, delay(1000)]);
            }
        }
    } else {
        console.log('SKIP actual npm process-group signals: native Windows verification required.');
    }
    console.log('PASS portable startup: fallback, paths, proxy/session, occupied ports, errors, timeout, signals and PHP exit.');
} catch (error) {
    console.error(error);
    throw error;
} finally {
    for (const child of children) child.kill('SIGTERM');
    await Promise.all([...children].map(child => child.done));
    for (const server of sentinels) await release(server);
    await rm(scratch, { recursive: true, force: true });
}
