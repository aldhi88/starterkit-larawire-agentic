// NODE_PATH=<directory containing playwright> node tests/Browser/cms-post-actions.cjs
// Modal keyboard/lifecycle regression; persistence and permissions are covered by CmsWorkspaceTest.
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const path = require('node:path');
(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
    try {
        const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent('<div wire:id="table"><div data-starter-region="bulk-actions"><button id="trigger">Aksi</button></div></div>');
        await page.evaluate(() => {
            window.closes = 0;
            window.Livewire = { find: () => ({ cancelPostAction() { window.closes++; document.querySelector('[data-cms-post-action]').remove(); } }) };
            window.openDialog = () => {
                document.querySelector('[wire\\:id]').insertAdjacentHTML('beforeend', '<div data-cms-post-action role="dialog" aria-modal="true" tabindex="-1"><button id="first">Tutup</button><input aria-label="Cari kategori"><button id="last">Simpan kategori</button></div>');
            };
        });
        await page.addScriptTag({ path: path.resolve(__dirname, '../../public/assets/starter/js/cms-workspace.js') });
        for (let run = 0; run < 2; run++) {
            await page.locator('#trigger').focus();
            await page.evaluate(() => window.openDialog());
            await page.waitForFunction(() => document.activeElement.matches('[data-cms-post-action]'));
            assert.equal(await page.evaluate(() => document.body.style.overflow), 'hidden');
            await page.keyboard.press('Shift+Tab');
            assert.equal(await page.evaluate(() => document.activeElement.id), 'last');
            await page.keyboard.press('Tab');
            assert.equal(await page.evaluate(() => document.activeElement.id), 'first');
            await page.locator('#trigger').focus();
            assert.equal(await page.evaluate(() => document.activeElement.matches('[data-cms-post-action]')), true);
            await page.evaluate(() => document.querySelector('[data-cms-post-action]').setAttribute('aria-busy', 'true'));
            await page.keyboard.press('Escape');
            assert.equal(await page.locator('[data-cms-post-action]').count(), 1);
            // Livewire may remove/disable the focused submit while morphing a validation result.
            await page.locator('#last').focus();
            await page.evaluate(() => {
                document.getElementById('last').remove();
                document.querySelector('[data-cms-post-action]').insertAdjacentHTML('beforeend', '<p role="alert">Pilih kategori.</p><button id="last">Simpan kategori</button>');
                document.querySelector('[data-cms-post-action]').removeAttribute('aria-busy');
            });
            await page.waitForFunction(() => document.activeElement.matches('[data-cms-post-action]'));
            await page.keyboard.press('Escape');
            await page.waitForFunction(() => document.activeElement.id === 'trigger');
            assert.equal(await page.evaluate(() => document.body.style.overflow), '');
        }
        await page.evaluate(() => { document.activeElement.blur(); window.openDialog(); });
        await page.waitForFunction(() => document.activeElement.matches('[data-cms-post-action]'));
        await page.keyboard.press('Escape');
        await page.waitForFunction(() => document.activeElement.id === 'trigger');
        assert.equal(await page.evaluate(() => window.closes), 3);
        const selection = await page.evaluate(() => {
            const tree = document.createElement('div');
            tree.innerHTML = [[1,0],[2,1],[3,2],[4,1],[5,0]].map(([id,parent]) => `<label ${id === 3 ? 'hidden' : ''}><input type="checkbox" value="${id}" data-parent="${parent}"></label>`).join('');
            document.body.append(tree);
            const select = (values, id, checked) => StarterCms.categorySelection(tree, values, id, checked).sort();
            const leaf = select([], 3, true);
            const parent = select([], 1, true);
            const branchOff = select(parent, 2, false);
            const allOff = select(parent, 1, false);
            const independent = select([5], 2, true);
            const lastLeafOff = select(leaf, 3, false);
            const lastSiblingOff = select(branchOff, 4, false);
            const keepOtherBranch = select(independent, 3, false);
            tree.remove();
            return { leaf, parent, branchOff, allOff, independent, lastLeafOff, lastSiblingOff, keepOtherBranch };
        });
        assert.deepEqual(selection, { leaf: [1,2,3], parent: [1,2,3,4], branchOff: [1,4], allOff: [], independent: [1,2,3,5], lastLeafOff: [], lastSiblingOff: [], keepOtherBranch: [5] });
        const treeBehavior = await page.evaluate(() => {
            const root = document.createElement('div');
            const nodes = [[1,0,'Artikel'],[2,1,'Teknologi'],[3,2,'Laptop'],[4,1,'Kesehatan'],[5,0,'Berita']];
            root.innerHTML = nodes.map(([id,parent,name]) => `<input type="checkbox" value="${id}" data-parent="${parent}" data-name="${name}">`).join('');
            const state = StarterCms.categoryPicker([]);
            state.$el = root; state.$watch = () => {};
            state.init(); const collapsed = [...state.visible];
            state.$el = document.createElement('button'); // Alpine event magic refers to the clicked toggle, not the picker root.
            state.toggle(1); const opened = [...state.visible];
            state.search = 'Laptop'; state.refresh(); const searched = [...state.visible];
            state.search = 'tidak cocok'; state.refresh(); const empty = [...state.visible];
            state.search = ''; state.refresh(); const restored = [...state.visible];
            const editing = StarterCms.categoryPicker([1,2,3]); editing.$el = root; editing.$watch = () => {}; editing.init();
            return { collapsed, opened, searched, empty, restored, editing: editing.visible };
        });
        assert.deepEqual(treeBehavior, { collapsed: [1,5], opened: [1,2,4,5], searched: [3,2,1], empty: [], restored: [1,2,4,5], editing: [1,2,3,4,5] });
        // A tab that predates categoryPicker must reload instead of keeping the old singleton.
        const legacy = await browser.newPage();
        let loads = 0;
        await legacy.route('http://cms-runtime.test/', route => route.fulfill({ contentType: 'text/html', body: ++loads === 1
            ? '<script>window.StarterCms = { init() {} };</script>' : '<p id="updated">Updated runtime document</p>' }));
        await legacy.goto('http://cms-runtime.test/');
        await legacy.addScriptTag({ path: path.resolve(__dirname, '../../public/assets/starter/js/cms-workspace.js') }).catch(error => {
            if (!/context was destroyed|navigation|Target closed/.test(error.message)) throw error;
        });
        await legacy.locator('#updated').waitFor();
        assert.equal(loads, 2);
        await legacy.close();
        assert.deepEqual(errors, []);
        console.log('PASS hierarchical category selection and post action dialog traps/restores focus, ignores Escape while saving, and reopens without duplicate listeners');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
