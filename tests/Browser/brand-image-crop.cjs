// Run with: node tests/Browser/brand-image-crop.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

(async () => {
    let id = 0;
    const revoked = [], events = [], calls = [];
    const background = { inert: false };
    const document = { addEventListener() {}, body: { style: { overflow: '' }, children: [background] }, activeElement: { focus() {} } };
    const window = { addEventListener() {}, requestAnimationFrame: (fn) => fn(), URL: {
        createObjectURL: () => `blob:${++id}`, revokeObjectURL: (url) => revoked.push(url),
    } };
    class Image {
        naturalWidth = 1600; naturalHeight = 800;
        async decode() {}
    }
    vm.runInNewContext(fs.readFileSync(path.resolve(__dirname, '../../public/assets/starter/js/starter-runtime.js'), 'utf8'), { window, document, Image, File });
    const create = (kind) => {
        const modal = {};
        const canvas = { width: 0, height: 0, closest: () => modal, focus() {},
            getContext: () => ({ clearRect: (...args) => calls.push(['clear', ...args]), drawImage: (...args) => calls.push(['draw', ...args.slice(1)]) }),
            toBlob: (callback, type) => { assert.equal(type, 'image/png'); callback(new Blob(['png'], { type })); },
        };
        const crop = window.StarterTemplate.brandImageCrop('/saved.png', kind, kind);
        Object.assign(crop, { $refs: { cropCanvas: canvas }, $nextTick: (fn) => fn(), $dispatch: (...args) => events.push(args),
            $wire: { upload: (field, file, success) => { assert.equal(field, kind); assert.equal(file.type, 'image/png'); success(); } } });
        return crop;
    };
    const select = (crop, overrides = {}) => crop.select({ target: { value: 'file', files: [{ type: 'image/webp', size: 3000000, ...overrides }] } });
    const logo = create('logo');
    await select(logo);
    assert.equal(logo.cropOpen, true);
    assert.equal(background.inert, true);
    assert.equal(logo.$refs.cropCanvas.width, 800);
    assert.equal(logo.$refs.cropCanvas.height, 200);
    // Contain keeps the entire source visible with transparent horizontal space.
    assert.equal(logo.cropGeometry().width, 400);
    assert.equal(logo.cropGeometry().x, 200);
    assert.equal(calls.filter(([type]) => type === 'clear').length > 0, true);
    assert.equal(logo.previewUrl, '/saved.png');
    logo.closeCrop();
    assert.equal(logo.previewUrl, '/saved.png');
    assert.equal(background.inert, false);
    assert.equal(document.body.style.overflow, '');
    assert.equal(revoked.includes('blob:1'), true);
    await select(logo);
    logo.fit(true);
    logo.zoom = 2;
    logo.move(0, 100000);
    assert.equal(logo.panY, 100);
    const area = logo.cropGeometry();
    assert.equal(area.y, 0); // Cannot drag a filled crop past the image edge.
    await logo.applyCrop();
    assert.equal(logo.cropOpen, false);
    assert.equal(logo.uploading, false);
    assert.match(logo.previewUrl, /^blob:/);
    logo.destroy();
    assert.equal(logo.objectUrl, null);
    assert.equal(logo.sourceUrl, null);

    const favicon = create('favicon');
    await select(favicon);
    assert.equal(favicon.$refs.cropCanvas.width, 256);
    assert.equal(favicon.$refs.cropCanvas.height, 256);
    assert.equal(favicon.cropGeometry().height, 256);
    favicon.$wire.upload = (_, file, success, failure) => failure();
    await favicon.applyCrop();
    assert.equal(favicon.cropOpen, true);
    assert.equal(favicon.uploading, false);
    assert.equal(favicon.previewUrl, '/saved.png');
    assert.match(favicon.error, /Unggahan gagal/);
    favicon.$wire.upload = (_, file, success) => success();
    await favicon.applyCrop();
    assert.equal(favicon.cropOpen, false);
    favicon.destroy();
    await select(create('logo'), { type: 'image/svg+xml' });
    const invalid = create('logo');
    await select(invalid, { size: 11 * 1024 * 1024 });
    assert.equal(invalid.cropOpen, false);
    assert.match(invalid.error, /10 MB/);
    Image.prototype.decode = async () => { throw new Error('Corrupt image'); };
    const corrupt = create('logo');
    await select(corrupt);
    assert.equal(corrupt.cropOpen, false);
    assert.equal(corrupt.decoding, false);
    assert.match(corrupt.error, /tidak dapat dibaca/);
    assert.equal(corrupt.previewUrl, '/saved.png');
    let finishDecode;
    Image.prototype.decode = () => new Promise((resolve) => { finishDecode = resolve; });
    const stale = create('logo');
    const pending = select(stale);
    assert.equal(stale.decoding, true);
    await select(stale, { type: 'text/plain' });
    finishDecode();
    await pending;
    assert.equal(stale.cropOpen, false);
    assert.equal(stale.decoding, false);
    assert.equal(stale.previewUrl, '/saved.png');
    assert.equal(events.at(-1)[1], false);
    console.log('Crop geometry, transparent contain, bounds, PNG export, cancel, upload failure/retry, isolation and cleanup passed.');
})().catch((error) => { console.error(error); process.exitCode = 1; });
