import assert from 'node:assert/strict';
import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const frontend = resolve(root, 'frontend');
const require = createRequire(resolve(frontend, 'package.json'));
const { parse, compileScript, compileTemplate } = require('@vue/compiler-sfc');
const vue = require('vue');
const components = [
    ['App.vue', 'app.js'],
    ['components/LoginForm.vue', 'components/LoginForm.js'],
    ['components/ConsultationTable.vue', 'components/ConsultationTable.js'],
    ['views/LoginView.vue', 'views/LoginView.js'],
    ['views/ConsultationsView.vue', 'views/ConsultationsView.js'],
    ['views/VideoCallView.vue', 'views/VideoCallView.js'],
];
const compiled = new Map();
for (const [filename, previous] of components) {
    const path = resolve(frontend, filename);
    assert.ok(existsSync(path), `Missing SFC: ${filename}`);
    assert.ok(!existsSync(resolve(frontend, previous)), `Old component remains: ${previous}`);
    const { descriptor, errors } = parse(readFileSync(path, 'utf8'), { filename });
    assert.deepEqual(errors, [], filename);
    assert.ok(descriptor.template && descriptor.script, `Template and ordinary script required: ${filename}`);
    assert.equal(descriptor.scriptSetup, null, filename);
    const id = `data-v-${filename.replace(/\W/g, '-')}`;
    const script = compileScript(descriptor, { id });
    const template = compileTemplate({
        id,
        filename,
        source: descriptor.template.content,
        scoped: descriptor.styles.some((style) => style.scoped),
        compilerOptions: { bindingMetadata: script.bindings },
    });
    assert.deepEqual(template.errors, [], filename);
    assert.doesNotMatch(script.content, /\b(?:setup|ref|reactive)\s*\(/, filename);
    new vm.SourceTextModule(script.content);
    new vm.SourceTextModule(template.code);
    compiled.set(filename, { script: script.content, template: template.code });
}

function checkImports(directory) {
    for (const entry of readdirSync(directory, { withFileTypes: true })) {
        if (['node_modules', 'dist'].includes(entry.name)) continue;
        const path = resolve(directory, entry.name);
        if (entry.isDirectory()) checkImports(path);
        else if (/\.(?:js|vue|html)$/.test(entry.name)) {
            const source = readFileSync(path, 'utf8');
            for (const match of source.matchAll(/(?:from\s*|import\s*)['"]([^'"]+)['"]/g)) {
                if (match[1].startsWith('.')) {
                    assert.ok(existsSync(resolve(dirname(path), match[1])), `Unresolved import in ${path}: ${match[1]}`);
                    assert.ok(!components.some(([, old]) => match[1].endsWith(`/${old.split('/').pop()}`)), `Old component import: ${match[1]}`);
                }
            }
        }
    }
}
checkImports(frontend);
assert.match(readFileSync(resolve(frontend, 'main.js'), 'utf8'), /import App from '\.\/App\.vue'/);
assert.match(readFileSync(resolve(frontend, 'index.html'), 'utf8'), /src="(?:\.\/|\/)?main\.js"/);

const childComponents = Object.fromEntries(['LoginView', 'ConsultationsView', 'VideoCallView'].map((name) => [name, { name }]));

async function loadApp(hash = '', fetchResult = { ok: true, json: async () => ({ autenticado: false }) }) {
    const listeners = new Map();
    const requests = [];
    const window = {
        location: { hash },
        addEventListener(name, callback) { listeners.set(name, callback); },
        removeEventListener(name, callback) {
            assert.equal(listeners.get(name), callback, 'Cleanup must remove the same named handler');
            listeners.delete(name);
        },
    };
    const context = vm.createContext({
        window,
        fetch: async (...args) => {
            requests.push(args);
            if (fetchResult instanceof Error) throw fetchResult;
            return fetchResult;
        },
    });
    const module = new vm.SourceTextModule(compiled.get('App.vue').script, { context });
    await module.link(async (specifier) => {
        const name = specifier.split('/').pop().replace('.vue', '');
        const exports = specifier === './config.js' ? { apiUrl: (path) => `/backend/${path}` } : { default: childComponents[name] };
        assert.ok(Object.values(exports).every(Boolean), `Unexpected root import: ${specifier}`);
        return new vm.SyntheticModule(Object.keys(exports), function () {
            for (const [key, value] of Object.entries(exports)) this.setExport(key, value);
        }, { context });
    });
    await module.evaluate();
    const options = module.namespace.default;
    assert.equal(options.setup, undefined);
    const state = options.data();
    for (const [name, method] of Object.entries(options.methods)) state[name] = method.bind(state);
    return { options, state, window, listeners, requests };
}

async function renderApp(app) {
    const context = vm.createContext({});
    const module = new vm.SourceTextModule(compiled.get('App.vue').template, { context });
    await module.link(async (specifier) => {
        assert.equal(specifier, 'vue');
        return new vm.SyntheticModule(Object.keys(vue), function () {
            for (const [name, value] of Object.entries(vue)) {
                this.setExport(name, name === 'resolveComponent'
                    ? (tag) => Object.values(childComponents).find((child) => child.name.toLowerCase() === tag.replaceAll('-', '').toLowerCase())
                    : value);
            }
        }, { context });
    });
    await module.evaluate();
    return module.namespace.render(app.state, [], {}, {}, app.state, app.state);
}

for (const failure of [
    { ok: false, json: async () => { throw new Error('Must not read failed response'); } },
    { ok: true, json: async () => ({ autenticado: false }) },
    { ok: true, json: async () => { throw new Error('Invalid JSON'); } },
    new Error('Network unavailable'),
]) {
    const app = await loadApp('#videocall', failure);
    await app.state.restoreSession();
    assert.equal(app.state.authenticated, false);
    assert.equal(app.window.location.hash, '');
    app.state.showConsultations('professional');
    await app.state.restoreSession();
    assert.equal(app.state.authenticated, true, 'A failed restoration must not undo a completed login');
    assert.equal(app.window.location.hash, '#consultations');
}

for (const role of ['user', 'professional', 'administrator', undefined]) {
    for (const hash of ['', '#consultations', '#videocall', '#consultations?google=connected', '#consultations?google=error']) {
        const app = await loadApp(hash, { ok: true, json: async () => ({ autenticado: true, rol: role }) });
        await app.state.restoreSession();
        assert.equal(app.state.authenticated, true);
        assert.equal(app.state.role, role || 'user');
        assert.equal(app.state.activeView, hash === '#videocall' ? 'videocall' : 'consultations');
        assert.equal(app.window.location.hash, hash || '#consultations');
        assert.equal(app.requests[0][0], '/backend/sesion_actual.php');
        assert.equal(app.requests[0][1].credentials, 'same-origin');
        const rendered = await renderApp(app);
        const view = rendered.children.find((child) => child.type === childComponents.ConsultationsView || child.type === childComponents.VideoCallView);
        assert.ok(view, 'The active child view must render');
        if (hash !== '#videocall') assert.equal(view.props.role, role || 'user');
    }
}

const app = await loadApp();
app.options.beforeMount.call(app.state);
assert.equal(app.requests.length, 1, 'Session restoration must start before child login/captcha mounting');
app.options.mounted.call(app.state);
assert.equal(app.listeners.get('hashchange'), app.state.handleHashChange);
assert.equal(app.requests.length, 1, 'Startup must restore the PHP session once');
await Promise.resolve();
await Promise.resolve();
for (const hash of ['#consultations', '#videocall']) {
    app.window.location.hash = hash;
    app.listeners.get('hashchange')();
    assert.equal(app.state.authenticated, false);
    assert.equal(app.window.location.hash, '');
}
app.state.showConsultations('administrator');
assert.equal(app.state.role, 'administrator');
app.state.goToVideoCall();
assert.equal(app.window.location.hash, '#videocall');
assert.equal(app.state.activeView, 'videocall');
app.state.goToConsultations();
assert.equal(app.window.location.hash, '#consultations');
assert.equal(app.state.activeView, 'consultations');
for (const [hash, view] of [['#videocall', 'videocall'], ['#consultations', 'consultations']]) {
    app.window.location.hash = hash;
    app.listeners.get('hashchange')();
    assert.equal(app.state.activeView, view);
}
app.window.location.hash = '#consultations?google=connected';
app.listeners.get('hashchange')();
assert.equal(app.window.location.hash, '#consultations?google=connected');
app.options.beforeUnmount.call(app.state);
assert.equal(app.listeners.size, 0);
console.log('PASS: six SFCs compile; imports, session roles, child props, hash navigation and cleanup');
