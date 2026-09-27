// Usage: NODE_PATH=<directory containing playwright> node tests/Browser/navigation.cjs
// Uses the three local demo hosts, their synchronized assets, and real Blade shells.
const { chromium } = require('playwright');
const { execFileSync } = require('node:child_process');
const { createServer } = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const packageRoot = path.resolve(__dirname, '../..');
const hosts = Object.fromEntries(['dashcode', 'tabler', 'vuexy'].map(theme => [theme,
    path.resolve(packageRoot, '../starterkit-larawire-laravel-' + (theme === 'dashcode' ? 'dashbcode' : theme))]));
const evidence = process.env.NAVIGATION_EVIDENCE_DIR || '/tmp/larawire-navigation-evidence';
fs.mkdirSync(evidence, { recursive: true });
const cache = new Map();
let origin;
const server = createServer((req, res) => {
    try {
        const url = new URL(req.url, origin);
        if (url.pathname === '/fixture') {
            const { theme, layout, count, length } = Object.fromEntries(url.searchParams);
            assert(hosts[theme] && ['horizontal', 'vertical'].includes(layout));
            assert(['0', '3', '8', '24'].includes(count) && ['short', 'long'].includes(length));
            if (!cache.has(req.url)) cache.set(req.url, execFileSync('php', [
                path.join(__dirname, 'render-navigation.php'), hosts[theme], layout, count, length, origin,
            ], { cwd: packageRoot, maxBuffer: 5_000_000 }));
            res.setHeader('Content-Type', 'text/html');
            return res.end(cache.get(req.url));
        }
        const theme = new URL(req.headers.referer || origin).searchParams.get('theme') || 'tabler';
        const file = path.resolve(hosts[theme], 'public', '.' + decodeURIComponent(url.pathname));
        assert(file.startsWith(hosts[theme] + '/public/'));
        const types = { '.css': 'text/css', '.js': 'text/javascript', '.svg': 'image/svg+xml', '.png': 'image/png', '.woff2': 'font/woff2' };
        res.setHeader('Content-Type', types[path.extname(file)] || 'application/octet-stream');
        res.end(fs.readFileSync(file));
    } catch (error) {
        res.statusCode = 404;
        res.end(String(error));
    }
});

(async () => {
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    origin = `http://127.0.0.1:${server.address().port}`;
    const browser = await chromium.launch({ headless: true,
        executablePath: process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
    const page = await browser.newPage();
    page.setDefaultTimeout(2500);
    const failures = [], results = [];
    let errors = [];
    page.on('pageerror', error => errors.push(String(error)));
    page.on('response', response => { if (response.status() >= 400) errors.push(`${response.status()} ${response.url()}`); });
    try {
        for (const theme of Object.keys(hosts)) {
            for (const layout of ['horizontal', 'vertical']) {
                for (const length of ['short', 'long']) {
                    for (const count of layout === 'horizontal' ? [0, 3, 8, 24] : [24]) {
                        for (const width of [1920, 1280, 1024, 390]) {
                            const state = { theme, layout, length, count, width };
                            try {
                                errors = [];
                                await page.setViewportSize({ width, height: width === 390 ? 844 : 768 });
                                await page.goto(`${origin}/fixture?${new URLSearchParams(state)}`);
                                await page.evaluate(() => document.fonts.ready);
                                const desktopMenu = layout === 'horizontal' && width >= (theme === 'dashcode' ? 1280 : theme === 'vuexy' ? 1200 : 768);
                                const selector = theme === 'dashcode' ? '.main-menu > ul' : theme === 'tabler' ? '#starter-horizontal-menu > .navbar-nav' : '.menu-horizontal .menu-inner';
                                if (desktopMenu) {
                                    const metrics = await page.locator(selector).evaluate((menu) => {
                                        const items = [...menu.children].filter(item => getComputedStyle(item).display !== 'none');
                                        const rects = items.map(item => item.getBoundingClientRect());
                                        return { rows: new Set(rects.map(rect => Math.round(rect.top))).size,
                                            right: Math.max(...rects.map(rect => rect.right)),
                                            menuBottom: menu.getBoundingClientRect().bottom,
                                            contentTop: document.querySelector('#content').getBoundingClientRect().top };
                                    });
                                    Object.assign(state, metrics);
                                    assert(metrics.right <= width, 'Menu item spills outside viewport');
                                    assert(metrics.contentTop >= metrics.menuBottom - 1, 'Menu overlaps page content');
                                    if (count === 24 && width === 1280) assert(metrics.rows > 1, 'Long menu must wrap');
                                    if (count > 0) {
                                        const last = page.locator(selector + ' > [data-starter-menu-item]').last();
                                        await last.locator('summary, [data-vuexy-submenu]').first().click();
                                        const submenu = last.locator('.sub-menu, .starter-horizontal-submenu, .menu-sub').first();
                                        await submenu.waitFor({ state: 'visible' });
                                        const box = await submenu.boundingBox();
                                        assert(box.x >= 0 && box.x + box.width <= width + 1, 'Dropdown spills outside viewport');
                                        assert(box.y >= 0 && box.y + box.height <= 768, 'Dropdown is clipped vertically');
                                        await last.locator('summary, [data-vuexy-submenu]').first().click();
                                    }
                                } else if (width === 390 || (layout === 'horizontal' && !desktopMenu)) {
                                    const toggle = theme === 'dashcode' ? '[data-starter-sidebar-open]' : theme === 'tabler' ? '.navbar-toggler' : '.layout-navbar [data-vuexy-menu-toggle]';
                                    if (theme === 'vuexy') {
                                        const closed = await page.locator('#layout-menu').boundingBox();
                                        assert(closed.x + closed.width <= 1, 'Closed mobile drawer remains in viewport');
                                    }
                                    await page.locator(toggle + ':visible').first().click();
                                    const navigation = theme === 'dashcode' ? '.sidebar-wrapper .sidebar-menu' : theme === 'tabler' ? '#starter-horizontal-menu > .navbar-nav, #starter-sidebar-menu > .navbar-nav' : '#layout-menu .menu-inner';
                                    await page.locator(navigation).first().waitFor({ state: 'visible' });
                                    if (theme === 'vuexy') {
                                        const opened = await page.locator('#layout-menu').boundingBox();
                                        assert(Math.abs(opened.y) <= 1 && opened.height >= page.viewportSize().height - 1, 'Drawer must fill the viewport height');
                                    }
                                    if (count > 0) {
                                        const items = page.locator(navigation).first().locator(':scope > [data-starter-menu-item]');
                                        await items.last().scrollIntoViewIfNeeded();
                                        const box = await items.last().boundingBox();
                                        assert(box.x >= 0 && box.x + box.width <= width + 1, 'Mobile menu spills horizontally');
                                        await items.last().locator('summary, [data-vuexy-submenu]').first().click();
                                        const submenu = items.last().locator('.sidebar-submenu, .starter-sidebar-submenu, .starter-horizontal-submenu, .menu-sub').first();
                                        await submenu.waitFor({ state: 'visible' });
                                        await submenu.scrollIntoViewIfNeeded();
                                        const childBox = await submenu.boundingBox();
                                        assert(childBox.x >= 0 && childBox.x + childBox.width <= width + 1, 'Mobile submenu spills horizontally');
                                    }
                                    if (layout === 'horizontal' && count === 24 && length === 'long' && width === 390) {
                                        await page.screenshot({ path: path.join(evidence, `${theme}-390-drawer.png`) });
                                    }
                                    await page.keyboard.press('Escape');
                                    if (theme === 'vuexy') {
                                        const closed = await page.locator('#layout-menu').boundingBox();
                                        assert(closed.x + closed.width <= 1, 'Escape must close mobile drawer');
                                    }
                                }
                                const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
                                state.overflow = overflow;
                                assert.equal(overflow, 0, 'Root page overflows horizontally');
                                assert.deepEqual(errors, [], 'Browser asset or JavaScript errors');
                                if (layout === 'horizontal' && count === 24 && length === 'long' && [1280, 390].includes(width)) {
                                    await page.screenshot({ path: path.join(evidence, `${theme}-${width}.png`) });
                                }
                                if (length === 'long' && desktopMenu) {
                                    await page.evaluate(() => scrollTo(0, 500));
                                    const navTop = await page.locator(theme === 'vuexy' ? '.menu-horizontal' : theme === 'tabler' ? '.starter-navbar-horizontal' : '#app_header').evaluate(el => el.getBoundingClientRect().top);
                                    if (theme !== 'dashcode') assert(navTop >= -1, 'Sticky navigation leaves viewport');
                                }
                                results.push(state);
                            } catch (error) {
                                failures.push({ ...state, error: String(error), browserErrors: errors });
                                console.log(`FAIL ${JSON.stringify(state)}: ${String(error).split('\n')[0]}`);
                            }
                        }
                    }
                }
            }
        }
    } finally {
        fs.writeFileSync(path.join(evidence, 'results.json'), JSON.stringify({ results, failures }, null, 2));
        await browser.close();
        server.close();
    }
    console.log(`${results.length} navigation cases passed; ${failures.length} failed. Evidence: ${evidence}`);
    for (const failure of failures) console.log(JSON.stringify(failure));
    if (failures.length) process.exitCode = 1;
})().catch(error => { console.error(error); server.close(); process.exitCode = 1; });
