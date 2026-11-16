// Isolated browser fixture: validates the actual Blade click expressions with bundled Alpine.
const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../..');
(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
    try {
        const files = ['resources/views/cms/title-link.blade.php', 'resources/views/cms/permalink.blade.php', ...['tabler', 'vuexy', 'dashcode'].map(theme => `resources/themes/${theme}/views/starter/cms/powergrid/row-actions.blade.php`)];
        for (const file of files) {
            const page = await browser.newPage();
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            const template = fs.readFileSync(path.join(root, file), 'utf8');
            const button = template.match(/<button\b[\s\S]*?<\/button>/g).find(markup => markup.includes('navigator.clipboard.writeText')).replaceAll('@js($permalink)', "'https://campus.test/halaman/profil'");
            await page.route('https://cms-fixture.test/', route => route.fulfill({ contentType: 'text/html', body: `<div x-data="{ copied: false, failed: false }">${button}</div>` }));
            await page.goto('https://cms-fixture.test/');
            await page.evaluate(() => {
                window.livewireScriptConfig = {};
                Object.defineProperty(navigator, 'clipboard', { value: { writeText: async text => { window.copiedUrl = text; } } });
            });
            await page.addStyleTag({ path: path.join(root, 'public/assets/starter/css/cms-workspace.css') });
            const alpine = fs.readFileSync(path.join(root, 'vendor/livewire/livewire/dist/livewire.esm.js'), 'utf8').replace(/export \{[\s\S]*?\};/, 'window.Alpine = export_Alpine; window.Alpine.start();');
            await page.addScriptTag({ content: alpine });
            if (file.includes('title-link')) {
                await page.locator('button').hover();
                assert.equal(await page.locator('.cms-title-chain').evaluate(element => getComputedStyle(element).opacity), '1');
            }
            await page.locator('button').click();
            await page.waitForFunction(() => window.copiedUrl === 'https://campus.test/halaman/profil');
            if (file.includes('title-link')) await page.locator('.cms-copy-result').waitFor({ state: 'visible' });
            else await page.waitForFunction(() => /tersalin/i.test(document.querySelector('button').textContent));
            assert.deepEqual(errors, []);
            await page.close();
            console.log(`PASS ${file}: copy and feedback without Alpine errors`);
        }
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
