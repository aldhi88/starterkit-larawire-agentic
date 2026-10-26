// Run: NODE_PATH=<directory containing playwright> node tests/Browser/cms-editor.cjs
// Uses pinned local Quill and the three editor partials. Host saving/theme appearance
// remain covered by package feature tests and direct per-theme browser review.
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const packageRoot = path.resolve(__dirname, '../..');
const asset = (file) => path.join(packageRoot, 'public/assets/starter', file);
const publicId = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

async function check(browser, theme, width) {
    const page = await browser.newPage({ viewport: { width, height: width === 390 ? 844 : 768 } });
    page.setDefaultTimeout(3000);
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    try {
        await page.route('https://root.example.test/cms/media/**', route => route.fulfill({
            contentType: 'image/svg+xml',
            body: '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400"><rect width="600" height="400" fill="blue"/></svg>',
        }));
        let editor = fs.readFileSync(path.join(packageRoot, `resources/themes/${theme}/views/starter/cms/editor.blade.php`), 'utf8').split('@error')[0];
        if (theme === 'tabler') editor = editor.replace(/@if \(\$resource === 'posts'\)([\s\S]*?)@else[\s\S]*?@endif/, '$1');
        editor = editor.replaceAll("@include('starter-shared::cms.media-plus-icon')", fs.readFileSync(path.join(packageRoot, 'resources/views/cms/media-plus-icon.blade.php'), 'utf8'));
        editor = editor.replace(/data-cms-media-base="[^"]+"/, 'data-cms-media-base="https://root.example.test/cms/media/00000000000000000000000000"')
            .replaceAll('{{ $this->getId() }}', 'fixture')
            .replace("{{ $form['body'] ?? '' }}", `<p>Example</p><p><img src="https://root.example.test/cms/media/${publicId}" alt="Example image"></p>`);
        await page.setContent(`<style>body{font:16px sans-serif}#fixture{width:700px;max-width:100%;padding:20px;box-sizing:border-box}.card{display:flex;flex-direction:column}</style><div id="fixture" wire:id="parent"><form ${theme === "tabler" ? "data-cms-tabler-post-form" : ""}><input data-width-reference style="width:100%;box-sizing:border-box">${editor}</form></div>`);
        await page.evaluate(() => {
            window.calls = [];
            window.wires = {
                parent: { $set: (...args) => window.calls.push(args) },
                child: { closeMediaPicker() {
                    document.querySelector('[data-cms-picker]').remove();
                    document.dispatchEvent(new CustomEvent('cms-editor-picker-closed', { detail: { componentId: 'parent', purpose: 'editor' } }));
                } },
            };
            window.Livewire = { find: id => window.wires[id] };
        });
        await page.addStyleTag({ path: asset('vendor/quill/quill.snow.css') });
        await page.addStyleTag({ path: asset('css/cms-workspace.css') });
        await page.addScriptTag({ path: asset('vendor/quill/quill.js') });
        await page.addScriptTag({ path: asset('js/cms-workspace.js') });
        await page.waitForFunction(() => document.querySelector('.ql-editor img')?.naturalWidth === 600);
        const image = page.locator('.ql-editor img');
        const dimensions = () => image.evaluate(element => [element.getAttribute('width'), element.getAttribute('height')]);
        assert.equal(await page.evaluate(() => StarterCms.slug('  Café, naïve & Łódź — 2026!  ')), 'cafe-naive-lodz-2026');
        assert.equal(await page.evaluate(() => StarterCms.slug('a'.repeat(179) + ' -- ending').length), 179);
        assert.match(await page.evaluate(() => StarterCms.localDateTime()), /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/);
        assert.equal(await page.evaluate(() => Math.abs(new Date(StarterCms.localDateTime('UTC') + 'Z').getTime() - Date.now()) < 60000), true);
        await image.click();
        assert.equal(await page.locator('[data-cms-image-controls]').isVisible(), true);
        await page.locator('[data-cms-image-width]').fill('300');
        await page.locator('[data-cms-image-apply]').click();
        assert.deepEqual(await dimensions(), ['300', '200']);
        const html = await page.locator('[data-cms-editor-surface]').evaluate(element => Quill.find(element).getSemanticHTML());
        assert.match(html, /width="300"/);
        assert.match(html, /height="200"/);
        assert.equal(await page.evaluate(() => window.calls.every(call => call[2] === false)), true);
        await page.locator('[data-cms-editor-surface]').evaluate(element => {
            const quill = Quill.find(element);
            quill.setContents(quill.clipboard.convert({ html: quill.getSemanticHTML() }), 'silent');
            quill.history.clear();
        });
        assert.deepEqual(await dimensions(), ['300', '200']);
        await image.click();
        const handle = page.locator('[data-cms-image-handle]');
        await handle.scrollIntoViewIfNeeded();
        const corner = await handle.boundingBox();
        await page.mouse.move(corner.x + corner.width / 2, corner.y + corner.height / 2);
        await page.mouse.down();
        await page.mouse.move(corner.x + corner.width / 2 + 120, corner.y + corner.height / 2 + 80, { steps: 5 });
        await page.mouse.up();
        assert.deepEqual(await dimensions(), ['420', '280']);
        await page.locator('[data-cms-editor-surface]').evaluate(element => Quill.find(element).history.undo());
        assert.deepEqual(await dimensions(), ['300', '200']);
        assert.equal(await page.locator('[data-cms-image-choose],.ql-toolbar .ql-image,.ql-toolbar .ql-media').count(), 0);
        await image.click();
        await page.locator('[data-cms-image-width]').fill('20000');
        await page.locator('[data-cms-image-apply]').click();
        assert.deepEqual(await dimensions(), ['10000', '6667']);
        assert.equal(await image.evaluate(element => element.getBoundingClientRect().width <= element.closest('.ql-editor').clientWidth), true);
        await handle.focus();
        await handle.press('ArrowLeft');
        assert.deepEqual(await dimensions(), ['9990', '6660']);
        await page.locator('[data-cms-image-done]').click();
        await page.waitForTimeout(30);
        assert.equal(await page.locator('[data-cms-image-controls]').isVisible(), false);
        await page.evaluate(() => {
            window.openEvents = [];
            document.addEventListener('cms-open-media-picker', event => window.openEvents.push(event.detail));
        });
        await page.locator('[data-cms-open-media]').click();
        assert.deepEqual(await page.evaluate(() => window.openEvents[0]), { componentId: 'parent', type: 'all', purpose: 'editor' });
        await page.evaluate(() => {
            const child = document.createElement('div');
            child.setAttribute('wire:id', 'child');
            child.innerHTML = '<div tabindex="-1" data-cms-picker data-cms-picker-owner="parent"><input type="search"><button>Close</button></div>';
            document.body.append(child);
            StarterCms.init();
        });
        assert.equal(await page.evaluate(() => document.body.style.overflow), 'hidden');
        await page.locator('[data-cms-picker] input').press('Escape');
        await page.waitForTimeout(30);
        assert.equal(await page.evaluate(() => document.body.style.overflow), '');
        assert.equal(await page.locator('.ql-editor').evaluate(element => element.contains(document.activeElement) || element === document.activeElement), true);
        await page.evaluate(() => document.dispatchEvent(new Event('livewire:navigating')));
        assert.equal(await page.locator('[data-cms-image-overlay]').isVisible(), false);
        await page.evaluate(() => StarterCms.init());
        assert.equal(await page.locator('.ql-toolbar').count(), 1);
        assert.deepEqual(await dimensions(), ['9990', '6660']);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
        if (theme === 'tabler') {
            const reference = await page.locator('[data-width-reference]').boundingBox();
            const aligned = async () => {
                for (const selector of ['.ql-container', '.ql-toolbar']) {
                    const bounds = await page.locator(selector).boundingBox();
                    assert.ok(Math.abs(bounds.x - reference.x) < 1);
                    assert.ok(Math.abs(bounds.width - reference.width) < 1);
                }
            };
            await page.locator('[data-cms-editor-surface]').evaluate(element => Quill.find(element).setText(''));
            await aligned();
            await page.locator('[data-cms-editor-surface]').evaluate(element => Quill.find(element).setText(('Konten panjang '.repeat(100) + '\n').repeat(60)));
            assert.equal(await page.locator('.ql-editor').evaluate(el => el.scrollWidth <= el.clientWidth + 1 && el.scrollHeight > el.clientHeight), true);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
            const vertical = page.locator('.cms-editor-scrollbar[data-axis="vertical"]');
            await page.waitForFunction(() => document.querySelector('.cms-editor-scrollbar[data-axis="vertical"]').getAttribute('aria-disabled') === 'false');
            const contentBounds = await page.locator('.ql-container').boundingBox();
            const railBounds = await vertical.boundingBox();
            await aligned();
            assert.ok(railBounds.x >= contentBounds.x + contentBounds.width + 5);
            await vertical.focus();
            await vertical.press('End');
            assert.equal(await page.locator('.ql-editor').evaluate(el => el.scrollTop > 0), true);
            await vertical.press('Home');
            assert.equal(await page.locator('.ql-editor').evaluate(el => el.scrollTop), 0);
            const thumb = await vertical.locator('span').boundingBox();
            await page.mouse.move(thumb.x + thumb.width / 2, thumb.y + thumb.height / 2);
            await page.mouse.down();
            await page.mouse.move(thumb.x + thumb.width / 2, thumb.y + thumb.height / 2 + 70);
            await page.mouse.up();
            assert.equal(await page.locator('.ql-editor').evaluate(el => el.scrollTop > 0), true);
            await page.locator('.ql-editor p').first().evaluate(el => { el.style.width = '1500px'; el.closest('.ql-editor').dispatchEvent(new Event('scroll')); });
            const horizontal = page.locator('.cms-editor-scrollbar[data-axis="horizontal"]');
            await page.waitForFunction(() => document.querySelector('.cms-editor-scrollbar[data-axis="horizontal"]').getAttribute('aria-disabled') === 'false');
            assert.ok((await horizontal.boundingBox()).y >= contentBounds.y + contentBounds.height + 5);
            await horizontal.focus();
            await horizontal.press('End');
            assert.equal(await page.locator('.ql-editor').evaluate(el => el.scrollLeft > 0), true);
            await page.locator('.ql-editor p').first().evaluate(el => { el.style.width = ''; el.closest('.ql-editor').dispatchEvent(new Event('scroll')); });

            await page.locator('[data-cms-editor-expand]').click();
            assert.equal(await page.locator('[data-cms-editor]').getAttribute('aria-modal'), 'true');
            assert.equal(await page.evaluate(() => document.body.style.overflow), 'hidden');
            await page.locator('[data-cms-editor-expand]').press('Escape');
            assert.equal(await page.evaluate(() => document.body.style.overflow), '');
            assert.equal(await page.locator('[data-cms-editor-expand]').getAttribute('aria-pressed'), 'false');
            await page.evaluate(() => { document.dispatchEvent(new Event('livewire:navigating')); StarterCms.init(); });
            assert.equal(await page.locator('.cms-editor-scrollbar').count(), 2);
        }
        assert.deepEqual(errors, []);
        console.log(`PASS ${theme} ${width}px: resize, persistence, undo, bounds, keyboard, slug/date, picker isolation, navigation cleanup`);
    } finally {
        await page.close();
    }
}

async function checkPublicationPicker(browser, timezoneId) {
    const page = await browser.newPage({ viewport: { width: 390, height: 844 }, timezoneId });
    try {
        await page.setContent('<div wire:id="publication"><div data-cms-publish-picker><input data-cms-publish-input readonly><button data-cms-publish-now>Sekarang</button></div></div>');
        await page.evaluate(() => {
            window.value = '25/10/2026 19:00';
            window.Livewire = { find: () => ({ $get: () => window.value, $set: (key, value) => { window.value = value; } }) };
        });
        await page.addStyleTag({ path: asset('vendor/flatpickr/flatpickr.min.css') });
        await page.addScriptTag({ path: asset('vendor/flatpickr/flatpickr.min.js') });
        await page.addScriptTag({ path: asset('js/cms-workspace.js') });
        const input = page.locator('[data-cms-publish-input]');
        assert.equal(await input.inputValue(), '25/10/2026 19:00');
        await input.click();
        await page.locator('.flatpickr-day[aria-label="28 Oktober 2026"]').click();
        await page.locator('.flatpickr-hour').fill('21');
        await page.locator('.flatpickr-hour').press('Tab');
        await page.locator('.flatpickr-minute').fill('45');
        await page.locator('.flatpickr-minute').press('Tab');
        await page.getByRole('button', { name: 'Selesai' }).click();
        assert.equal(await input.inputValue(), '28/10/2026 21:45');
        assert.equal(await page.evaluate(() => window.value), '28/10/2026 21:45');
        assert.equal(await input.getAttribute('aria-expanded'), 'false');
        await page.locator('[data-cms-publish-now]').click();
        assert.equal(await input.inputValue(), await page.evaluate(() => StarterCms.maskDateTime(StarterCms.localDateTime('Asia/Jakarta'))));
        await page.evaluate(() => { window.value = '30/10/2026 10:15'; StarterCms.init(); });
        assert.equal(await input.inputValue(), '30/10/2026 10:15');
        await page.evaluate(() => document.dispatchEvent(new Event('livewire:navigating')));
        assert.equal(await page.locator('.flatpickr-calendar').count(), 0);
        await page.evaluate(() => StarterCms.init());
        assert.equal(await page.locator('.flatpickr-calendar').count(), 1);
        console.log(`PASS publication picker ${timezoneId}: calendar, 24h time, WIB now, refresh, cleanup`);
    } finally { await page.close(); }
}

(async () => {
    const browser = await chromium.launch({ headless: true,
        executablePath: process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
    try {
        for (const timezoneId of ['Asia/Jakarta', 'America/Los_Angeles']) await checkPublicationPicker(browser, timezoneId);
        for (const theme of ['tabler', 'vuexy', 'dashcode']) {
            for (const width of [1280, 390]) await check(browser, theme, width);
        }
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
