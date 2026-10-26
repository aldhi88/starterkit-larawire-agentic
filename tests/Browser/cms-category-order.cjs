// NODE_PATH=<directory containing playwright> node tests/Browser/cms-category-order.cjs
// Exercises direct PowerGrid-style row dragging; server persistence/auth are covered by Pest.
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const path = require('node:path');
const asset = file => path.resolve(__dirname, '../../public/assets/starter', file);
async function check(browser, width) {
    const page = await browser.newPage({ viewport: { width, height: 844 } });
    page.setDefaultTimeout(4000);
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    try {
        await page.setContent(`<style>body{margin:12px;font:14px sans-serif}table{border-collapse:collapse;width:100%;table-layout:fixed}td{padding:10px;border:1px solid #ddd}td:first-child{width:24px}button{min-height:28px}.d-flex{display:flex}.gap-2{gap:8px}.cms-category-name{min-width:0}</style><div id="fixture" wire:id="category"><div data-cms-category-feedback role="status"></div><table><tbody></tbody></table></div>`);
        await page.evaluate(() => {
            window.calls = []; window.outcome = 'success'; window.revision = 1;
            window.records = [
                { id: 1, name: 'Artikel', parent: 0, depth: 0 },
                { id: 2, name: 'Kesehatan', parent: 1, depth: 1 },
                { id: 3, name: 'Teknologi', parent: 1, depth: 1 },
                { id: 4, name: 'Berita', parent: 0, depth: 0 },
            ];
            window.renderRows = () => {
                document.querySelector('tbody').innerHTML = '<tr id="filters"><td></td><td><input aria-label="Filter"></td></tr>' + window.records.map(row => `<tr><td><input type="checkbox" aria-label="Pilih ${row.name}"></td><td><div data-cms-category-row data-id="${row.id}" data-parent="${row.parent}" data-depth="${row.depth}" data-version="v${window.revision}" data-reorderable="true" class="d-flex gap-2"><button data-cms-category-handle class="cms-category-row-handle" aria-label="Geser ${row.name}">⠿</button><div class="cms-category-name">${row.name}</div></div></td></tr>`).join('');
            };
            window.renderRows();
            window.Livewire = { find: () => ({
                async moveCategory(id, targetId, placement, version) {
                    window.calls.push({ id, targetId, placement, version });
                    if (window.outcome === 'network') throw new Error('offline');
                    if (window.outcome === 'validation') { window.renderRows(); return false; }
                    const rows = window.records;
                    const start = rows.findIndex(row => row.id === id);
                    let end = start + 1;
                    while (end < rows.length && rows[end].depth > rows[start].depth) end++;
                    const subtree = rows.splice(start, end - start);
                    let target = rows.findIndex(row => row.id === targetId);
                    if (placement === 'after') {
                        const depth = rows[target].depth;
                        target++;
                        while (target < rows.length && rows[target].depth > depth) target++;
                    }
                    rows.splice(target, 0, ...subtree);
                    window.revision++; window.renderRows(); return true;
                },
                async $refresh() { window.renderRows(); },
            }) };
        });
        await page.addStyleTag({ path: asset('css/cms-workspace.css') });
        await page.addScriptTag({ path: asset('vendor/sortable/Sortable.min.js') });
        await page.addScriptTag({ path: asset('js/cms-workspace.js') });
        const ids = () => page.locator('[data-cms-category-row]').evaluateAll(items => items.map(item => Number(item.dataset.id)));
        const drag = async (source, target) => {
            const handle = await page.getByRole('button', { name: `Geser ${source}`, exact: true }).boundingBox();
            const destination = await page.getByRole('button', { name: `Geser ${target}`, exact: true }).boundingBox();
            await page.mouse.move(handle.x + handle.width / 2, handle.y + handle.height / 2);
            await page.mouse.down();
            await page.mouse.move(destination.x + destination.width / 2, destination.y + 2, { steps: 25 });
            await page.mouse.up();
        };
        await page.waitForSelector('[data-cms-draggable-row]');
        await drag('Berita', 'Artikel');
        await page.waitForFunction(() => window.calls.length === 1 && ! document.querySelector('[data-cms-category-saving]'));
        assert.deepEqual(await ids(), [4, 1, 2, 3]);
        assert.deepEqual(await page.evaluate(() => window.calls[0]), { id: 4, targetId: 1, placement: 'before', version: 'v1' });
        // A Livewire rerender must keep grips working and use the fresh version.
        await page.getByRole('button', { name: 'Geser Teknologi', exact: true }).focus();
        await page.keyboard.press('Alt+ArrowUp');
        await page.waitForFunction(() => window.calls.length === 2 && ! document.querySelector('[data-cms-category-saving]'));
        assert.deepEqual(await ids(), [4, 1, 3, 2]);
        assert.equal(await page.evaluate(() => window.calls[1].version), 'v2');
        // The parent moves as a subtree, even though descendants are separate table rows.
        await page.getByRole('button', { name: 'Geser Artikel', exact: true }).focus();
        await page.keyboard.press('Alt+ArrowUp');
        await page.waitForFunction(() => window.calls.length === 3 && ! document.querySelector('[data-cms-category-saving]'));
        assert.deepEqual(await ids(), [1, 3, 2, 4]);
        await page.evaluate(() => { window.outcome = 'network'; });
        await page.getByRole('button', { name: 'Geser Artikel', exact: true }).focus();
        await page.keyboard.press('Alt+ArrowDown');
        await page.waitForFunction(() => window.calls.length === 4 && ! document.querySelector('[data-cms-category-saving]'));
        assert.deepEqual(await ids(), [1, 3, 2, 4]);
        assert.match(await page.locator('[data-cms-category-feedback]').textContent(), /belum tersimpan/);
        await page.evaluate(() => { window.outcome = 'validation'; });
        await page.keyboard.press('Alt+ArrowDown');
        await page.waitForFunction(() => window.calls.length === 5 && ! document.querySelector('[data-cms-category-saving]'));
        assert.deepEqual(await ids(), [1, 3, 2, 4]);
        // Cross-parent drops cannot silently reparent a child.
        await drag('Teknologi', 'Artikel');
        assert.equal(await page.evaluate(() => window.calls.length), 5);
        assert.deepEqual(await ids(), [1, 3, 2, 4]);
        assert.equal(await page.locator('#filters').count(), 1);
        assert.equal(await page.locator('tr[hidden]').count(), 0);
        await page.evaluate(() => { document.dispatchEvent(new Event('livewire:navigating')); window.outcome = 'success'; StarterCms.init(); });
        await page.getByRole('button', { name: 'Geser Artikel', exact: true }).focus();
        await page.keyboard.press('Alt+ArrowDown');
        await page.waitForFunction(() => window.calls.length === 6 && ! document.querySelector('[data-cms-category-saving]'));
        assert.deepEqual(await ids(), [4, 1, 3, 2]);
        assert.deepEqual(errors, []);
        console.log(`PASS direct category rows ${width}px: drag/autosave, keyboard, hierarchy, rerender, retry, cross-parent guard, cleanup`);
    } finally { await page.close(); }
}
(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
    try { for (const width of [1280, 390]) await check(browser, width); }
    finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
