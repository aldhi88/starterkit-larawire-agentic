// Run: NODE_PATH=<directory containing playwright> node tests/Browser/livewire-dialog-loader.cjs
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const path = require('node:path');

(async () => {
    const browser = await chromium.launch({
        headless: true,
        executablePath: process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    });
    try {
        const page = await browser.newPage();
        await page.setContent(`<div data-starter-livewire-loader aria-hidden="true"></div>
            <main id="parent"><button id="outside">Outside</button>
                <div id="modal" role="dialog" aria-modal="true"><button id="inside">Inside</button></div>
            </main>
            <div id="picker" data-starter-dialog-component>
                <div id="picker-modal" role="dialog" aria-modal="true"><input id="search"></div>
            </div>`);
        await page.evaluate(() => {
            window.Livewire = { interceptRequest(callback) { window.intercept = callback; } };
        });
        await page.addScriptTag({ path: path.resolve(__dirname, '../../public/assets/starter/js/starter-runtime.js') });
        const states = await page.evaluate(() => {
            const runtime = window.StarterTemplate;
            runtime.bindLivewire();
            function request(rootId, originId = null) {
                const root = document.getElementById(rootId);
                const origin = originId ? document.getElementById(originId) : null;
                const hooks = {};
                window.intercept({
                    request: {
                        messages: [{ component: { el: root }, calls: [{ method: 'work' }], actions: origin ? new Set([{ origin: { targetEl: origin } }]) : new Set() }],
                        options: { headers: {} }, cancel() {},
                    },
                    onSend(callback) { hooks.send = callback; },
                    onError() {}, onRedirect() {},
                    onFinish(callback) { hooks.finish = callback; },
                });
                hooks.send();
                return hooks.finish;
            }
            const snapshot = () => ({
                pageBusy: document.body.classList.contains('starter-livewire-is-loading'),
                overlay: document.querySelector('[data-starter-livewire-loader]').getAttribute('aria-hidden'),
                dialogBusy: document.getElementById('modal').getAttribute('aria-busy'),
                pickerBusy: document.getElementById('picker-modal').getAttribute('aria-busy'),
            });
            const modalFinish = request('parent', 'inside');
            const modal = snapshot();
            const pickerFinish = request('picker');
            const both = snapshot();
            modalFinish();
            const pickerOnly = snapshot();
            pickerFinish();
            const idle = snapshot();
            const outsideFinish = request('parent', 'outside');
            const outside = snapshot();
            outsideFinish();
            return { modal, both, pickerOnly, idle, outside, after: snapshot() };
        });
        assert.deepEqual(states.modal, { pageBusy: false, overlay: 'true', dialogBusy: 'true', pickerBusy: null });
        assert.deepEqual(states.both, { pageBusy: false, overlay: 'true', dialogBusy: 'true', pickerBusy: 'true' });
        assert.deepEqual(states.pickerOnly, { pageBusy: false, overlay: 'true', dialogBusy: null, pickerBusy: 'true' });
        assert.deepEqual(states.idle, { pageBusy: false, overlay: 'true', dialogBusy: null, pickerBusy: null });
        assert.deepEqual(states.outside, { pageBusy: true, overlay: 'false', dialogBusy: null, pickerBusy: null });
        assert.deepEqual(states.after, states.idle);
        console.log('PASS Livewire modal requests keep the page stable, track local busy state, and retain normal background loading');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
