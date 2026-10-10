// Run with: node tests/Browser/login-loader.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.resolve(__dirname, '../..');
const runtime = fs.readFileSync(path.join(root, 'public/assets/starter/js/starter-runtime.js'), 'utf8');

for (const theme of ['vuexy', 'dashcode', 'tabler']) {
    const layout = fs.readFileSync(path.join(root, `resources/themes/${theme}/views/starter/templates/layouts/auth.blade.php`), 'utf8');
    assert.match(layout, /data-starter-auth-shell/);
    assert.match(layout, /data-starter-livewire-loader/);
}

function fixture(authShell) {
    const attributes = new Map([['aria-hidden', 'true']]);
    const classes = new Set();
    const listeners = new Map();
    const navigation = [];
    let intercept;
    const loader = { setAttribute: (name, value) => attributes.set(name, value) };
    const body = {
        hasAttribute: (name) => name === 'data-starter-auth-shell' && authShell,
        classList: {
            add: (name) => classes.add(name),
            remove: (name) => classes.delete(name),
            contains: (name) => classes.has(name),
        },
    };
    const document = {
        body,
        documentElement: { clientWidth: 1280 },
        querySelector: (selector) => selector === '[data-starter-livewire-loader]' ? loader : null,
        querySelectorAll: () => [],
        getElementById: () => null,
        addEventListener: (name, callback) => listeners.set(name, callback),
    };
    const window = {
        location: { href: 'https://example.test/auth/login', assign: (url) => navigation.push(url) },
        Livewire: { interceptRequest: (callback) => { intercept = callback; } },
        addEventListener: (name, callback) => listeners.set(name, callback),
    };
    vm.runInNewContext(runtime, { window, document, URL, Set, clearTimeout, setTimeout });
    window.StarterTemplate.bindLivewire();

    function request() {
        const hooks = {};
        const rootElement = { setAttribute() {}, removeAttribute() {} };
        const livewireRequest = {
            messages: [{ calls: [{ method: 'authenticate' }], component: { el: rootElement } }],
            options: { headers: {} },
            cancel() {},
        };
        intercept({
            request: livewireRequest,
            onSend: (callback) => { hooks.send = callback; },
            onError: (callback) => { hooks.error = callback; },
            onRedirect: (callback) => { hooks.redirect = callback; },
            onFinish: (callback) => { hooks.finish = callback; },
        });
        return hooks;
    }

    return { window, attributes, classes, listeners, navigation, request };
}

const successful = fixture(true);
const login = successful.request();
login.send();
assert.equal(successful.attributes.get('aria-hidden'), 'false');
login.redirect({ url: 'https://example.test/dashboard', preventDefault() {} });
login.finish();
assert.deepEqual(successful.navigation, ['https://example.test/dashboard']);
assert.equal(successful.attributes.get('aria-hidden'), 'false', 'Successful login keeps its loader until navigation');
assert(successful.classes.has('starter-livewire-is-loading'));

successful.listeners.get('pageshow')({ persisted: true });
assert.equal(successful.attributes.get('aria-hidden'), 'true', 'Back navigation clears the stale loader');
assert.equal(successful.window.StarterTemplate.terminalNavigationStarted, false);

const failed = fixture(true);
const retry = failed.request();
retry.send();
retry.finish();
assert.equal(failed.attributes.get('aria-hidden'), 'true', 'Failed login reveals the form for correction');
assert.equal(failed.navigation.length, 0);

const app = fixture(false);
const appAction = app.request();
appAction.send();
appAction.redirect({ url: 'https://example.test/other', preventDefault() {} });
appAction.finish();
assert.equal(app.attributes.get('aria-hidden'), 'true', 'Other pages retain the navigation loader handoff');
assert(app.classes.has('starter-is-navigating'));

console.log('Login loader handoff passed for all auth layouts.');
