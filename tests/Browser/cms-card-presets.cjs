// Run after starter:sync on all three local comparison hosts. No user browser/profile is used.
// NODE_PATH=<Playwright module directory> node tests/Browser/cms-card-presets.cjs
const { chromium } = require('playwright');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const assert = require('assert/strict');

(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
    try {
        for (const theme of ['tabler', 'vuexy', 'dashcode']) {
            const host = path.resolve('..', 'starterkit-larawire-laravel-' + (theme === 'dashcode' ? 'dashbcode' : theme));
            for (const resource of ['cards', 'posts', 'pages']) {
                const html = execFileSync('php', [path.resolve('tests/Browser/render-cms-card-fixture.php'), resource], { cwd: host, encoding: 'utf8' });
                for (const width of [1280, 390]) {
                    const page = await browser.newPage({ viewport: { width, height: 844 } });
                    page.setDefaultTimeout(5000);
                    const errors = [], requests = [];
                    page.on('pageerror', error => errors.push(error.message));
                    await page.route('**/*', route => {
                        const url = route.request().url();
                        if (url.includes('/livewire/update')) requests.push(url);
                        if (url.endsWith('/test-image.png')) return route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120"><rect width="120" height="120" fill="#dbeafe"/><circle cx="60" cy="45" r="22" fill="#64748b"/><path d="M20 120v-20a40 40 0 0 1 80 0v20" fill="#64748b"/></svg>' });
                        const file = path.join(host, 'public', new URL(url).pathname);
                        return fs.existsSync(file) && fs.statSync(file).isFile() ? route.fulfill({ path: file }) : route.abort();
                    });
                    await page.setContent(`<html data-bs-theme="light"><head><base href="https://cms-fixture.test/"></head><body class="${theme === 'dashcode' ? 'dashcode-app' : ''}"><main style="padding:20px;max-width:1200px;margin:auto">${html}</main></body></html>`);
                    const css = theme === 'tabler' ? ['assets/tabler/dist/css/tabler.min.css', 'assets/tabler/css/tabler.css'] : theme === 'vuexy' ? ['assets/vuexy/vendor/css/core.css', 'assets/vuexy/vendor/fonts/iconify-icons.css', 'assets/vuexy/css/vuexy.css'] : ['assets/dashcode/css/app.css', 'assets/dashcode/css/dashcode.css'];
                    for (const file of ['assets/starter/css/starter.css', ...css, 'assets/starter/vendor/quill/quill.snow.css', 'assets/starter/vendor/flatpickr/flatpickr.min.css', 'assets/starter/css/cms-workspace.css']) await page.addStyleTag({ path: path.join(host, 'public', file) });
                    await page.addStyleTag({ content: '[x-cloak]{display:none!important}' });
                    await page.evaluate(() => { window.livewireScriptConfig = { csrf: 'fixture', uri: '/livewire/update', moduleUrl: '/livewire/module', progressBar: 'data-no-progress-bar' }; });
                    for (const file of ['assets/starter/vendor/quill/quill.js', 'assets/starter/vendor/flatpickr/flatpickr.min.js', 'assets/starter/js/cms-workspace.js']) await page.addScriptTag({ path: path.join(host, 'public', file) });
                    const livewire = fs.readFileSync('vendor/livewire/livewire/dist/livewire.esm.js', 'utf8').replace(/export \{[\s\S]*?\};/, 'window.Livewire=Livewire2; Livewire2.start();');
                    await page.addScriptTag({ content: '(()=>{' + livewire + '})()' });
                    const choose = async preset => {
                        await page.locator('#cms-card-preset').click();
                        await page.locator('#cms-preset-search').fill('');
                        await page.locator(`[data-cms-preset-option="${preset}"]`).click();
                        assert.equal(await page.locator('#cms-card-preset').getAttribute('aria-expanded'), 'false');
                    };
                    assert.equal(await page.locator('input[type="datetime-local"]').count(), 0);
                    if (resource === 'cards') {
                        assert.equal(await page.getByRole('button', { name: 'Beberapa item', exact: true }).getAttribute('aria-pressed'), 'true');
                        const firstItem = page.locator('[data-cms-card-item]');
                        assert.equal(await firstItem.count(), 1);
                        assert.equal(await firstItem.locator('input[id$="-title"]').isVisible(), true);
                        await firstItem.getByRole('button', { name: /^Hapus / }).click();
                        assert.equal(await firstItem.count(), 0);
                        await choose('profile');
                        assert.equal(await firstItem.locator('input[id$="-title"]').isVisible(), true);
                        await firstItem.locator('input[id$="-title"]').fill('Anggota awal');
                        await choose('banner');
                        assert.equal(await firstItem.count(), 1);
                        assert.equal(await firstItem.locator('input[id$="-title"]').inputValue(), 'Anggota awal');
                        await page.getByRole('button', { name: 'Satu item', exact: true }).click();
                        await page.locator('#cms-card-title').fill('Layanan Akademik');
                        assert.equal(await page.locator('#cms-name').inputValue(), 'Layanan Akademik');
                        await page.locator('#cms-name').fill('Profil Rektor');
                        await choose('profile');
                        await page.locator('#cms-card-title').fill('Dr. Ratna Pratiwi');
                        assert.equal(await page.locator('#cms-name').inputValue(), 'Profil Rektor');
                        assert.equal(await page.locator('#cms-card-card_data-value').isVisible(), false);
                        await choose('statistic');
                        await page.locator('#cms-card-card_data-value').fill('12.500');
                        await choose('faq');
                        assert.equal(await page.locator('#cms-image_id').isVisible(), false);
                        const presets = await page.locator('[data-cms-preset-option]').evaluateAll(options => options.map(option => option.dataset.cmsPresetOption));
                        assert.equal(presets.length, 19);
                        for (const preset of presets) {
                            await choose(preset);
                            assert.equal(await page.locator('#cms-name').inputValue(), 'Profil Rektor');
                            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, `${theme} ${preset} ${width} overflow`);
                        }
                        await page.locator('#cms-card-preset').click();
                        if (process.env.CMS_SCREENSHOT_DIR) {
                            await page.evaluate(() => window.scrollTo(0, 0));
                            await page.locator('.cms-preset-picker').screenshot({ path: path.join(process.env.CMS_SCREENSHOT_DIR, `${theme}-catalog-${width}.png`), animations: 'disabled' });
                        }
                        await page.waitForFunction(() => document.querySelector('.cms-preset-grid').scrollTop === 0);
                        const alignment = await page.locator('[data-cms-preset-option] strong').evaluateAll(nodes => nodes.map(n => Math.round(n.getBoundingClientRect().left)));
                        assert.equal(new Set(alignment).size, 1, 'Every preset title starts in the same column');
                        const heights = await page.locator('.cms-preset-search :is(input,button)').evaluateAll(nodes => nodes.map(n => n.offsetHeight));
                        assert.equal(heights[0], heights[1], 'Close matches the search field');
                        await page.locator('#cms-preset-search').fill('tidak-adanya-jenis');
                        assert.equal(await page.locator('[data-cms-preset-option]:visible').count(), 0);
                        assert.equal(await page.locator('.cms-preset-no-results').isVisible(), true);
                        await page.locator('#cms-preset-search').fill('agenda');
                        assert.equal(await page.locator('[data-cms-preset-option]:visible').count(), 1);
                        await page.keyboard.press('Escape');
                        await page.waitForFunction(() => document.activeElement?.id === 'cms-card-preset');
                        await choose('profile');
                        await page.locator('#cms-card-subtitle').fill('Rektor Universitas');
                        await page.locator('.ql-editor').fill('Memimpin pengembangan pendidikan dan riset untuk masyarakat.');
                        await choose('partner');
                        assert.equal(await page.locator('.ql-editor').isVisible(), false);
                        await choose('profile');
                        assert.match(await page.locator('.ql-editor').innerText(), /Memimpin pengembangan/);
                        assert.equal(await page.locator('#cms-card-card_data-value').inputValue(), '12.500');
                        await page.locator('[data-cms-card-form]').evaluate(element => { element._x_dataStack[0].mediaTarget = 'image_id'; });
                    } else {
                        await page.locator('#cms-title').fill('Profil Universitas');
                        assert.equal(await page.locator('#cms-slug').inputValue(), 'profil-universitas');
                        await page.locator('.cms-slug-disclosure > summary').click();
                        await page.locator('#cms-slug').fill('profil-kampus');
                        await page.locator('#cms-title').fill('Profil Kampus');
                        assert.equal(await page.locator('#cms-slug').inputValue(), 'profil-kampus');
                        await page.locator('.cms-slug-disclosure > summary').click();
                        await page.locator('[data-starter-region="post-details-form"] summary').click();
                        await page.locator('#cms-excerpt').fill('Pendidikan, riset, dan pengabdian untuk masa depan.');
                        await page.locator('.ql-editor').fill('Universitas berkomitmen mengembangkan pendidikan dan penelitian untuk masyarakat.');
                    }
                    await page.locator('[data-starter-region="publication-form"] input[value="published"]').check();
                    await page.locator('[data-cms-workspace]').evaluate(element => {
                        const componentId = element.getAttribute('wire:id');
                        window.dispatchEvent(new CustomEvent('cms-thumbnail-selected', { detail: { componentId, media: { id: 123, name: 'Foto Kampus', url: 'https://cms-fixture.test/test-image.png' } } }));
                    });
                    await page.locator('.cms-asset-image').first().waitFor({ state: 'visible' });
                    // All dates use the same local calendar; selection is deferred and keeps canonical WIB values.
                    await page.locator('#cms-published_at').click();
                    await page.locator('.cms-publication-calendar.open').waitFor({ state: 'visible' });
                    await page.locator('.cms-publication-calendar.open').getByRole('button', { name: 'Selesai' }).click();
                    assert.match(await page.locator('#cms-published_at').inputValue(), /^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/);
                    if (resource === 'cards') {
                        await page.getByRole('button', { name: 'Beberapa item', exact: true }).click();
                        assert.deepEqual(errors, []);
                        let rows = page.locator('[data-cms-card-item]');
                        await rows.nth(0).locator('input[id$="-title"]').fill('Dr. Ratna Pratiwi');
                        await rows.nth(0).locator('input[id$="-subtitle"]').fill('Rektor');
                        await page.locator('[data-cms-add-card-item]').click();
                        await rows.nth(1).locator('input[id$="-title"]').fill('Dr. Bima Santoso');
                        await rows.nth(1).locator('input[id$="-subtitle"]').fill('Wakil Rektor');
                        await page.getByRole('button', { name: 'Naikkan Dr. Bima Santoso', exact: true }).click();
                        assert.match(await rows.first().innerText(), /Bima/);
                        await page.getByRole('button', { name: 'Hapus Dr. Bima Santoso', exact: true }).click();
                        assert.equal(await rows.count(), 1);
                        await page.getByRole('button', { name: 'Urungkan', exact: true }).click();
                        assert.equal(await rows.count(), 2);
                        await page.evaluate(() => window.addEventListener('cms-open-media-picker', event => { window.cardPickerRequest = event.detail; event.stopImmediatePropagation(); }, true));
                        await rows.first().locator('[id$="-image_id"]').click();
                        await page.evaluate(() => window.dispatchEvent(new CustomEvent('cms-thumbnail-selected', { detail: { componentId: window.cardPickerRequest.componentId, media: { id: 124, name: 'Foto Wakil Rektor', url: 'https://cms-fixture.test/test-image.png' } } })));
                        assert.equal(await rows.first().locator('.cms-asset-image').count(), 1);
                        const items = () => page.locator('[data-cms-workspace]').evaluate(element => window.Livewire.find(element.getAttribute('wire:id')).$get('form.card_data.items'));
                        assert.equal((await items())[0].image_id, 124);
                        assert.equal((await items())[1].image_id, null);
                        await page.locator('[data-cms-add-card-item]').click();
                        await rows.last().locator('input[id$="-title"]').fill('Anggota ketiga');
                        await rows.last().locator('.cms-card-item-toggle').click();
                        const originalKeys = (await items()).map(item => item.key);
                        await page.locator('.cms-card-item-list').evaluate(list => list.scrollIntoView({ block: 'center', behavior: 'instant' }));
                        await rows.last().locator('.cms-card-grip').hover();
                        await page.mouse.down();
                        await page.locator('[data-cms-card-item].is-dragged').waitFor();
                        const target = await rows.first().boundingBox();
                        await page.mouse.move(target.x + target.width / 2, target.y + target.height / 4, { steps: 12 });
                        await page.waitForFunction(() => document.querySelector('[data-cms-card-item]')?.dataset.dropEdge === 'before');
                        await page.mouse.up();
                        await page.waitForFunction(key => document.querySelector('[data-cms-card-item]').dataset.cmsCardItem === key, originalKeys[2]);
                        assert.deepEqual((await items()).map(item => item.key), [originalKeys[2], ...originalKeys.slice(0, 2)]);
                        await page.getByRole('button', { name: 'Hapus Anggota ketiga' }).click();
                        await rows.first().locator('.cms-card-item-toggle').click();
                        // Different list presets reuse items without discarding captions, photos, or identity.
                        for (const preset of ['banner', 'partner', 'service', 'product', 'testimonial', 'statistic', 'faq', 'step', 'timeline', 'event', 'award', 'facility', 'contact', 'download', 'video']) {
                            await choose(preset);
                            assert.equal(await rows.count(), 2);
                            assert.equal((await items())[0].title, 'Dr. Bima Santoso');
                            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, `${preset} collection overflow`);
                        }
                        await choose('event');
                        await rows.first().locator('[data-cms-date-field$=".starts_at"] [data-cms-publish-now]').click();
                        const dateBeforeMove = (await items())[0].starts_at;
                        assert.match(dateBeforeMove, /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/);
                        await page.getByRole('button', { name: 'Turunkan Dr. Bima Santoso', exact: true }).click();
                        await rows.nth(1).locator('[data-cms-date-field$=".ends_at"] [data-cms-publish-now]').click();
                        assert.equal((await items())[1].starts_at, dateBeforeMove);
                        assert.match((await items())[1].ends_at, /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/);
                        assert.equal((await items())[0].ends_at ?? '', '');
                        await choose('greeting');
                        assert.equal(await page.locator('.cms-card-mode').isVisible(), false);
                        await choose('profile');
                        assert.equal(await rows.first().isVisible(), true);
                        assert.equal((await items()).length, 2);
                        await page.locator('#cms-card-title').fill('Tim Pimpinan Universitas');
                        const modeColors = await page.locator('.cms-mode-choice').evaluateAll(nodes => nodes.map(node => getComputedStyle(node).backgroundColor));
                        assert.notEqual(modeColors[0], modeColors[1]);
                        assert.equal(await page.getByRole('button', { name: 'Beberapa item', exact: true }).getAttribute('aria-pressed'), 'true');
                    }
                    assert.equal(await page.locator('.cms-authoring-main').evaluate(element => element.getBoundingClientRect().width > 0), true);
                    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, `${theme} ${resource} overflow`);
                    assert.deepEqual(requests, [], 'Deferred editing must not issue a Livewire request');
                    assert.deepEqual(errors, [], `${theme} ${resource} JS errors`);
                    if (process.env.CMS_SCREENSHOT_DIR) {
                        await page.evaluate(() => window.scrollTo(0, 0));
                        await page.screenshot({ path: path.join(process.env.CMS_SCREENSHOT_DIR, `${theme}-${resource}-${width}.png`), fullPage: true, animations: 'disabled' });
                    }
                    console.log(`PASS ${theme} ${resource} ${width}: retained input, media, publication, responsive layout, no typing requests or JS errors`);
                    await page.close();
                }
            }
        }
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
