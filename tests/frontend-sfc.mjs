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

async function loadComponent(filename, extra = {}) {
    const handlers = [];
    const root = {};
    const rows = [{ id: '7', text: 'Latest consultation', visible: true }, { text: 'Empty', visible: true }];
    const $ = (element) => ({
        on(events, selector, callback) {
            for (const event of events.split(' ')) handlers.push({ element, event, selector, callback });
            return this;
        },
        off(namespace) {
            for (let i = handlers.length - 1; i >= 0; i--) if (handlers[i].element === element && handlers[i].event.includes(namespace)) handlers.splice(i, 1);
        },
        find() { return { each(callback) { rows.forEach((row) => callback.call(row)); } }; },
        text() { return element.text; },
        toggle(value) { element.visible = value; },
    });
    const context = vm.createContext({ window: { location: { hash: '' } }, console, setInterval: () => 1, clearInterval: () => {}, fetch: async () => ({ ok: true, json: async () => ({ pregunta: '1 + 1', correcto: false }) }), ...extra });
    const module = new vm.SourceTextModule(compiled.get(filename).script, { context });
    await module.link(async (specifier) => {
        let exports = { default: {} };
        if (specifier === 'jquery') exports = { default: $ };
        if (specifier.endsWith('config.js')) exports = { apiUrl: (path) => path };
        if (specifier === '@fullcalendar/core') exports = { Calendar: extra.Calendar || function () {} };
        if (specifier === 'firebase/app') exports = { initializeApp: () => ({}), getApps: () => [] };
        if (specifier === 'firebase/auth') exports = { getAuth: () => ({}), signInWithEmailAndPassword: extra.signIn || (async () => { throw new Error('Offline'); }) };
        return new vm.SyntheticModule(Object.keys(exports), function () {
            for (const [key, value] of Object.entries(exports)) this.setExport(key, value);
        }, { context });
    });
    await module.evaluate();
    const options = module.namespace.default;
    const emitted = [];
    const state = { ...(options.data?.() || {}), $el: root, $emit: (...args) => emitted.push(args), consultations: [], role: 'administrator' };
    for (const [name, method] of Object.entries(options.methods || {})) state[name] = method.bind(state);
    function fire(type, selector, target = {}) {
        let prevented = 0;
        const event = { type, target, currentTarget: target, preventDefault() { prevented++; }, ...target };
        const matches = handlers.filter((handler) => handler.element === root && handler.event.split('.')[0] === type && handler.selector === selector);
        for (const handler of matches) handler.callback(event);
        return { count: matches.length, prevented };
    }
    return { options, state, fire, handlers, rows, emitted };
}

const login = await loadComponent('components/LoginForm.vue');
let loginSubmits = 0;
login.state.submitLogin = () => { loginSubmits++; };
await login.options.mounted.call(login.state);
assert.equal(login.fire('submit', '#login-form').count, 1, 'Login needs one delegated submit');
assert.equal(loginSubmits, 1);
login.options.beforeUnmount.call(login.state);
assert.equal(login.handlers.length, 0);
assert.doesNotMatch(readFileSync(resolve(frontend, 'components/LoginForm.vue'), 'utf8'), /@submit/);

const consultations = await loadComponent('views/ConsultationsView.vue');
for (const name of ['loadConsultations', 'loadGoogleStatus', 'handleGoogleCallback']) consultations.state[name] = () => {};
let creates = 0, updates = 0;
consultations.state.submitConsultation = (event) => { event.preventDefault(); creates++; };
consultations.state.updateConsultation = (event) => { event.preventDefault(); updates++; };
consultations.options.mounted.call(consultations.state);
assert.equal(consultations.fire('submit', '#consultation-form').count, 1);
consultations.state.selected = { id: 7 };
assert.equal(consultations.fire('submit', '#consultation-update-form').count, 1);
assert.equal(creates, 1); assert.equal(updates, 1);
assert.doesNotMatch(readFileSync(resolve(frontend, 'views/ConsultationsView.vue'), 'utf8'), /@submit/);
consultations.options.beforeUnmount.call(consultations.state);
assert.equal(consultations.handlers.length, 0);

const table = await loadComponent('components/ConsultationTable.vue');
table.options.mounted.call(table.state);
const latest = { id: 7, description: 'Latest data' };
table.state.consultations = [latest];
const row = { dataset: { id: '7' } };
assert.equal(table.fire('click', 'tbody tr[data-id]', row).count, 1);
assert.equal(table.emitted[0][1], latest);
for (const key of ['Enter', ' ']) assert.equal(table.fire('keydown', 'tbody tr[data-id]', { ...row, key }).prevented, 1);
table.fire('keydown', 'tbody tr[data-id]', { ...row, key: 'Tab' });
table.fire('click', 'tbody tr[data-id]', { dataset: { id: '' } });
assert.equal(table.emitted.length, 3, 'Ignore unrelated keys and empty/stale rows');
table.fire('input', '#consultation-filter', { value: 'latest' });
assert.equal(table.rows[0].visible, true); assert.equal(table.rows[1].visible, false);
const tableSource = readFileSync(resolve(frontend, 'components/ConsultationTable.vue'), 'utf8');
assert.doesNotMatch(tableSource, /@click/);
assert.match(tableSource, /tabindex="0"/); assert.match(tableSource, /role="button"/);
table.options.beforeUnmount.call(table.state);
assert.equal(table.handlers.length, 0);

let captchaLoads = 0, signIns = 0, sessionRequests = 0;
let failAt = 'captcha';
const flow = await loadComponent('components/LoginForm.vue', {
    fetch: async (url, request) => {
        if (url === 'captcha.php') { captchaLoads++; return { ok: true, json: async () => ({ pregunta: '2 + 2' }) }; }
        if (url === 'crear_sesion.php') {
            sessionRequests++;
            assert.equal(JSON.parse(request.body).password, ' untrimmed ');
        }
        return { ok: failAt !== (url === 'validar_captcha.php' ? 'captcha' : 'session'), json: async () => ({ correcto: true, rol: 'user' }) };
    },
    signIn: async () => { signIns++; return { user: { email: 'user@example.test', uid: '7', getIdToken: async () => 'offline' } }; },
});
await flow.options.mounted.call(flow.state);
for (const failure of ['captcha', 'session', 'none']) {
    failAt = failure;
    Object.assign(flow.state, { email: 'user@example.test', password: ' untrimmed ', captcha: '4' });
    const first = flow.state.submitLogin();
    await flow.state.submitLogin();
    await first;
    assert.equal(flow.state.loading, false);
}
assert.equal(signIns, 2); assert.equal(sessionRequests, 2);
assert.equal(captchaLoads, 3, 'Refresh consumed CAPTCHA only after failure, never after success');
assert.equal(flow.emitted.length, 1, 'HTTP errors must not authenticate despite a success-shaped body');
assert.equal(flow.state.message, '');
console.log('PASS: six SFCs, root navigation, delegated jQuery events/cleanup, late forms, accessible selection, login failure/retry');

const profileRequests = [];
const contact = await loadComponent('views/ConsultationsView.vue', {
    fetch: async (url, options) => {
        profileRequests.push([url, options]);
        return { ok: true, json: async () => url === 'sesion_actual.php'
            ? { correcto: true, phone: '+5493421234567' } : { correcto: true, consultations: [] } };
    },
});
assert.equal(contact.state.profileLoading, true);
await contact.state.loadProfile();
assert.equal(contact.state.savedPhone, '+5493421234567');
assert.equal(contact.state.profileLoading, false);
contact.state.contactPreference = 'phone';
contact.state.phone = '+12345678901';
await contact.state.submitConsultation({ preventDefault() {} });
assert.equal(JSON.parse(profileRequests.find(([, options]) => options?.method === 'POST')[1].body).phone, undefined, 'Saved phone is reused, not overwritten');
const before = { id: 7, can_join: false, scheduled_at_iso: '2099-12-01T09:00:00-03:00', ends_at: '2099-12-01T09:30:00-03:00' };
assert.equal(contact.state.isMeetingAvailable(before), false);
assert.equal(contact.state.meetingLinkFor({ id: 7, can_join: true }), 'api/join_meeting.php?id=7');
const expired = { id: 7, can_join: true, ends_at: '2000-01-01T09:30:00-03:00' };
assert.equal(contact.state.isMeetingAvailable(expired), false);
const video = await loadComponent('views/VideoCallView.vue');
assert.equal(video.state.isMeetingAvailable(expired), false);
assert.match(readFileSync(resolve(frontend, 'views/VideoCallView.vue'), 'utf8'), /join_meeting\.php/);
console.log('PASS: profile loading/reuse and authenticated expiring meeting entry');

let persistedPhone = null;
const submitted = [];
const firstPhone = await loadComponent('views/ConsultationsView.vue', {
    fetch: async (url, options) => {
        if (options?.method === 'POST') {
            submitted.push(JSON.parse(options.body));
            persistedPhone = '+5493421234567';
        }
        return { ok: true, json: async () => url === 'sesion_actual.php'
            ? { correcto: true, phone: persistedPhone } : { correcto: true, consultations: [] } };
    },
});
await firstPhone.state.submitConsultation({ preventDefault() {} });
assert.equal(submitted.length, 0, 'Do not submit before profile restoration');
await firstPhone.state.loadProfile();
assert.equal(firstPhone.state.savedPhone, null);
Object.assign(firstPhone.state, { contactPreference: 'phone', phone: '+54 (9) 342-1234567' });
await firstPhone.state.submitConsultation({ preventDefault() {} });
assert.equal(submitted[0].phone, '+54 (9) 342-1234567');
assert.equal(firstPhone.state.savedPhone, persistedPhone);
await firstPhone.state.loadProfile();
await firstPhone.state.submitConsultation({ preventDefault() {} });
assert.equal(submitted[1].phone, undefined, 'Reloaded profile must not prompt or overwrite');
const failedProfile = await loadComponent('views/ConsultationsView.vue', { fetch: async () => { throw new Error('Offline'); } });
await failedProfile.state.loadProfile();
assert.equal(failedProfile.state.profileLoading, false);
assert.ok(failedProfile.state.profileError);
const contactSource = readFileSync(resolve(frontend, 'views/ConsultationsView.vue'), 'utf8');
assert.match(contactSource, /v-else-if="contactPreference === 'phone' && !savedPhone"/);
assert.match(contactSource, /:disabled="profileLoading \|\| !!profileError"/);
Object.assign(contact.state, { selectedDate: '2099-12-01', selected: { id: 7 }, consultations: [{ id: 8, status: 'scheduled', contact_preference: 'phone', scheduled_at_iso: '2099-12-01T09:00:00-03:00', ends_at: '2099-12-01T09:30:00-03:00' }] });
assert.equal(contact.state.isSlotBusy('09:00'), true);
assert.equal(contact.state.isSlotBusy('09:15'), true);
assert.equal(contact.state.isSlotBusy('09:30'), false);
let opened;
const click = await loadComponent('views/VideoCallView.vue', { window: { open: (url) => { opened = url; } } });
click.state.openMeeting(expired);
assert.equal(opened, undefined);
click.state.openMeeting({ id: 7, can_join: true, ends_at: '2099-12-01T09:30:00-03:00', meeting_link: 'https://evil.test/' });
assert.equal(opened, 'api/join_meeting.php?id=7', 'Stored/browser link must never select the redirect');
console.log('PASS: first-phone POST/reload/error guards, occupied slots and click-time entry URL');
let refreshedRows = [{ id: 7, can_join: true, ends_at: '2099-12-01T09:30:00-03:00' }];
const refresh = await loadComponent('views/ConsultationsView.vue', {
    fetch: async () => ({ ok: true, json: async () => ({ correcto: true, consultations: refreshedRows }) }),
});
Object.assign(refresh.state, { selected: before, professionalNote: 'Unsaved note', updateStatus: 'closed', scheduleDateTime: '2099-12-02T10:00' });
await refresh.state.loadConsultations();
assert.equal(refresh.state.selected, refreshedRows[0], 'Collection refresh must replace stale selected access state');
assert.equal(refresh.state.isMeetingAvailable(refresh.state.selected), true);
assert.equal(refresh.state.professionalNote, 'Unsaved note');
assert.equal(refresh.state.updateStatus, 'closed');
assert.equal(refresh.state.scheduleDateTime, '2099-12-02T10:00');
refreshedRows = [];
await refresh.state.loadConsultations();
assert.equal(refresh.state.selected, null, 'A removed selection must be cleared');

let calendarOptions;
const calendarElement = { dataset: {}, querySelectorAll: () => [] };
const calendar = await loadComponent('views/ConsultationsView.vue', {
    document: { getElementById: () => calendarElement },
    Calendar: function (element, options) { calendarOptions = options; this.render = () => {}; },
});
Object.assign(calendar.state, { role: 'professional', selected: { id: 7, contact_preference: 'video' }, $nextTick: (callback) => callback() });
calendar.options.updated.call(calendar.state);
for (const dateStr of ['2099-12-01', '2099-12-01T10:30:00-03:00']) {
    calendarOptions.dateClick({ dateStr, dayEl: { style: {} } });
    assert.equal(calendar.state.selectedDate, '2099-12-01');
    calendar.state.selectTime('09:30');
    assert.equal(calendar.state.scheduleDateTime, '2099-12-01T09:30');
}

const noteRequests = [];
const notes = await loadComponent('views/ConsultationsView.vue', {
    fetch: async (url, options) => {
        if (options?.method === 'PUT') noteRequests.push(JSON.parse(options.body));
        return { ok: true, json: async () => ({ correcto: true, consultations: [] }) };
    },
});
for (const role of ['professional', 'administrator']) {
    for (const preference of ['phone', 'video', 'email']) {
        Object.assign(notes.state, { role, selected: { id: 7, contact_preference: preference, status: 'closed' }, professionalNote: 'Review note', updateStatus: 'closed' });
        await notes.state.updateConsultation({ preventDefault() {} });
        assert.deepEqual(noteRequests.at(-1), role === 'professional' && preference !== 'email'
            ? { professional_note: 'Review note' } : { status: 'closed', professional_note: 'Review note' });
    }
}
const noteSource = readFileSync(resolve(frontend, 'views/ConsultationsView.vue'), 'utf8');
assert.doesNotMatch(noteSource, /v-html/);
assert.match(noteSource, /\{\{\s*selected\.professional_note\s*\}\}/);


const noteContext = vm.createContext({});
const noteTemplate = new vm.SourceTextModule(compiled.get('views/ConsultationsView.vue').template, { context: noteContext });
await noteTemplate.link(async () => new vm.SyntheticModule(Object.keys(vue), function () {
    for (const [name, value] of Object.entries(vue)) this.setExport(name, name === 'resolveComponent' ? () => ({ name: 'ConsultationTable' }) : name === 'withDirectives' ? (node) => node : value);
}, { context: noteContext }));
await noteTemplate.evaluate();
function nodes(node) { return !node || typeof node !== 'object' ? [] : [node, ...(Array.isArray(node.children) ? node.children.flatMap(nodes) : [])]; }
for (const role of ['user', 'professional']) {
    Object.assign(notes.state, { role, selected: { id: 7, contact_preference: 'video', status: 'closed', professional_note: '<img src=x onerror=alert(1)>' } });
    const rendered = nodes(noteTemplate.namespace.render(notes.state, [], { role }, {}, notes.state, notes.state));
    assert.ok(rendered.some((node) => typeof node.children === 'string' && node.children.includes(notes.state.selected.professional_note)), 'Existing note must render as text');
    assert.ok(rendered.every((node) => !node.props?.innerHTML), 'Notes must never render as HTML');
    assert.equal(rendered.some((node) => node.props?.id === 'consultation-update-form'), role === 'professional');
    assert.equal(rendered.some((node) => node.props?.id === 'consultation-status'), false, 'No broad status control for phone/video professionals');
}
console.log('PASS: actual Calendar callback day normalization, note-only update payloads and escaped owner detail');
