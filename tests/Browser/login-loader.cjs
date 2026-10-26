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
    const lockScreen = fs.readFileSync(path.join(root, `resources/themes/${theme}/views/starter/auth/lock-screen.blade.php`), 'utf8');
    assert.match(lockScreen, /wire:submit="unlock"/);
}

const lockComponent = fs.readFileSync(path.join(root, 'src/Livewire/Starter/Auth/LockScreen.php'), 'utf8');
assert.match(lockComponent, /#\[Layout\('layouts::auth'\)\]/);

function fixture(authShell, pathname = '/auth/login') {
    const attributes = new Map([['aria-hidden', 'true']]);
    const classes = new Set();
    const componentAttributes = new Map();
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
        location: { href: `https://example.test${pathname}`, assign: (url) => navigation.push(url) },
        Livewire: { interceptRequest: (callback) => { intercept = callback; } },
        addEventListener: (name, callback) => listeners.set(name, callback),
    };
    vm.runInNewContext(runtime, { window, document, URL, Set, clearTimeout, setTimeout });
    window.StarterTemplate.bindLivewire();

    function request(method = 'authenticate') {
        const hooks = {};
        const rootElement = { setAttribute: (name, value) => componentAttributes.set(name, value), removeAttribute: (name) => componentAttributes.delete(name) };
        const livewireRequest = {
            messages: [{ calls: [{ method }], component: { el: rootElement } }],
            options: { headers: {} },
            cancel() {},
        };
        intercept({
            request: livewireRequest,
            onSend: (callback) => { hooks.send = callback; },
            onSuccess: (callback) => { hooks.success = callback; },
            onError: (callback) => { hooks.error = callback; },
            onRedirect: (callback) => { hooks.redirect = callback; },
            onFinish: (callback) => { hooks.finish = callback; },
        });
        return hooks;
    }

    return { window, attributes, componentAttributes, classes, listeners, navigation, request };
}

for (const [action, pathname] of [
    ['authenticate', '/auth/login'],
    ['verifyOtp', '/auth/login'],
    ['verifyAuthenticator', '/auth/login'],
    ['unlock', '/lock-screen'],
]) {
    const successful = fixture(true, pathname);
    const request = successful.request(action);
    request.send();
    assert.equal(successful.attributes.get('aria-hidden'), 'false');
    request.success({ json: { components: [{ effects: { redirect: 'https://example.test/dashboard' } }] } });
    request.finish();
    assert.equal(successful.attributes.get('aria-hidden'), 'false', `${action}: request finish before Livewire redirect must not reveal the form`);
    assert(successful.classes.has('starter-livewire-is-loading'));
    request.redirect({ url: 'https://example.test/dashboard', preventDefault() {} });
    assert.deepEqual(successful.navigation, ['https://example.test/dashboard']);
    assert.equal(successful.attributes.get('aria-hidden'), 'false', `${action}: loader stays visible while the destination has not loaded`);
    assert(successful.classes.has('starter-livewire-is-loading'));
    assert.equal(successful.componentAttributes.get('aria-busy'), 'true');
    successful.window.StarterTemplate.hideLivewireLoader();
    assert.equal(successful.attributes.get('aria-hidden'), 'false', `${action}: late request cleanup cannot reveal the form`);

    successful.listeners.get('pageshow')({ persisted: true });
    assert.equal(successful.attributes.get('aria-hidden'), 'true', `${action}: Back navigation clears the stale loader`);
    assert.equal(successful.window.StarterTemplate.terminalNavigationStarted, false);
    assert.equal(successful.componentAttributes.has('aria-busy'), false);

    const failed = fixture(true, pathname);
    const retry = failed.request(action);
    retry.send();
    retry.success({ json: { components: [{ effects: {} }] } });
    retry.finish();
    assert.equal(failed.attributes.get('aria-hidden'), 'true', `${action}: invalid password reveals the form for correction`);
    assert.equal(failed.componentAttributes.has('aria-busy'), false);
    assert.equal(failed.navigation.length, 0);
    const corrected = failed.request(action);
    corrected.send();
    corrected.success({ json: { components: [{ effects: { redirect: 'https://example.test/dashboard' } }] } });
    corrected.finish();
    assert.equal(failed.attributes.get('aria-hidden'), 'false', `${action}: retry must keep loader between request finish and redirect`);
    corrected.redirect({ url: 'https://example.test/dashboard', preventDefault() {} });
    assert.equal(failed.attributes.get('aria-hidden'), 'false', `${action}: retry retains the loader through successful navigation`);
    assert.deepEqual(failed.navigation, ['https://example.test/dashboard']);
}

const app = fixture(false);
const appAction = app.request();
appAction.send();
appAction.redirect({ url: 'https://example.test/other', preventDefault() {} });
appAction.finish();
assert.equal(app.attributes.get('aria-hidden'), 'true', 'Other pages retain the navigation loader handoff');
assert(app.classes.has('starter-is-navigating'));

console.log('Login and lock-screen loader handoff passed for all auth layouts.');
