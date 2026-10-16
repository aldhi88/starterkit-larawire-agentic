// Run with: node tests/Browser/application-branding.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const favicon = { href: '/native-icon.svg', dataset: { fallbackSrc: '/native-icon.svg' } };
const logo = {
    src: '/default-wordmark.svg', alt: '', dataset: { fallbackSrc: '/default-wordmark.svg' },
    toggleAttribute(name, enabled) { this[name] = enabled; },
};
const document = {
    addEventListener() {},
    querySelectorAll: (selector) => selector === '[data-starter-favicon]' ? [favicon]
        : selector === '[data-starter-brand-logo]' ? [logo] : [],
};
const window = { addEventListener() {} };
vm.runInNewContext(fs.readFileSync(path.resolve(__dirname, '../../public/assets/starter/js/starter-runtime.js'), 'utf8'), { window, document });
const update = (logoUrl, faviconUrl) => window.StarterTemplate.updateClientBranding({ logoUrl, faviconUrl, clientName: 'Application' });

update('/wide-logo.png', '/square-icon.png');
assert.equal(logo.src, '/wide-logo.png');
assert.equal(favicon.href, '/square-icon.png');
update(null, '/square-icon.png');
assert.equal(logo.src, '/default-wordmark.svg');
assert.equal(favicon.href, '/square-icon.png');
update('/wide-logo.png', null);
assert.equal(logo.src, '/wide-logo.png');
assert.equal(favicon.href, '/native-icon.svg');
assert.equal(logo['data-company-logo'], true);
console.log('Application logo/favicon live update and independent resets passed.');
