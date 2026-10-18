const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'assets/site/js/install-app.js'), 'utf8');
function environment(ua, standalone = false) {
    const events = {}, media = {matches:standalone, addEventListener() {}};
    const buttons = [{hidden:false}, {hidden:false}, {hidden:false}];
    buttons.forEach((button, index) => { button.hasAttribute = name => name === 'data-install-guide' && index === 2; });
    const banner = {hidden:true}, bannerClose = {}, storage = new Map();
    const steps = {items:[], replaceChildren() { this.items = []; }, append(li) { this.items.push(li.textContent); }};
    const confirm = {}, close = {}, feedback = {};
    const dialog = {open:false, showModal() { this.open = true; }, close() { this.open = false; }, querySelector(selector) {
        return {'[data-install-confirm]':confirm, '[data-install-close]':close, '[data-install-feedback]':feedback, '[data-install-steps]':steps}[selector];
    }};
    const registrations = [];
    const context = {
        document:{currentScript:{dataset:{worker:'/service-worker.js'}}, getElementById() { return dialog; }, querySelector(selector) { return {'[data-install-banner]':banner, '[data-install-banner-close]':bannerClose}[selector]; }, querySelectorAll() { return buttons; }, createElement() { return {}; }},
        sessionStorage:{getItem(key) { return storage.get(key); }, setItem(key, value) { storage.set(key, value); }},
        Event:class { constructor(type) { this.type = type; } },
        window:{matchMedia() { return media; }, addEventListener(name, fn) { events[name] = fn; }, dispatchEvent() {}, isSecureContext:true},
        navigator:{userAgent:ua, platform:'', maxTouchPoints:0, serviceWorker:{register(url, options) { registrations.push({url,options}); return Promise.resolve(); }}}
    };
    vm.runInNewContext(source, context);
    return {events,buttons,steps,confirm,close,feedback,dialog,registrations,banner,bannerClose,storage};
}
(async function () {
    const ios = environment('iPhone Safari');
    ios.buttons[0].onclick();
    assert.equal(ios.dialog.open, true);
    assert.ok(ios.steps.items.some(text => text.includes('Thêm vào Màn hình chính')));
    assert.equal(ios.confirm.hidden, true);
    ios.close.onclick(); assert.equal(ios.dialog.open, false);
    const mac = environment('Macintosh Safari'); mac.buttons[0].onclick();
    assert.ok(mac.steps.items.some(text => text.includes('Thêm vào Dock')));
    const chrome = environment('Chrome');
    let prompted = 0, prevented = false;
    chrome.events.beforeinstallprompt({preventDefault() { prevented = true; }, async prompt() { prompted++; }, userChoice:Promise.resolve({outcome:'dismissed'})});
    const directInstall = chrome.buttons[0].onclick();
    assert.equal(prevented, true);
    assert.equal(prompted, 1, 'Main CTA opens browser install prompt immediately');
    assert.equal(chrome.dialog.open, false, 'Supported browsers skip the instruction popup');
    await directInstall;
    assert.equal(chrome.dialog.open, false, 'Dismissal does not open another popup'); assert.equal(chrome.confirm.hidden, true);
    chrome.events.beforeinstallprompt({preventDefault() {}, async prompt() { prompted++; }, userChoice:Promise.resolve({outcome:'accepted'})});
    await chrome.buttons[0].onclick(); assert.equal(chrome.dialog.open, false);
    const failed = environment('Android Chrome');
    failed.events.beforeinstallprompt({preventDefault() {}, async prompt() { throw new Error('Not available'); }});
    await failed.buttons[0].onclick(); assert.equal(failed.dialog.open, true, 'Unavailable install prompt falls back to instructions');
    chrome.events.appinstalled(); assert.ok(chrome.buttons.every(button => button.hidden));
    assert.ok(environment('Chrome', true).buttons.every(button => button.hidden));
    assert.equal(chrome.registrations[0].url, '/service-worker.js');
    assert.equal(chrome.registrations[0].options.updateViaCache, 'none');
    const bannerFlow = environment('Android Chrome');
    let bannerPrompted = false;
    bannerFlow.events.beforeinstallprompt({preventDefault() {}, async prompt() { bannerPrompted = true; }, userChoice:Promise.resolve({outcome:'accepted'})});
    bannerFlow.buttons[2].onclick();
    assert.equal(bannerFlow.dialog.open, true, 'Mobile banner opens illustrated guide even when native installation is available');
    assert.equal(bannerPrompted, false);
    assert.equal(bannerFlow.confirm.hidden, false);
    await bannerFlow.confirm.onclick(); assert.equal(bannerPrompted, true);
    bannerFlow.bannerClose.onclick(); assert.equal(bannerFlow.banner.hidden, true);
    assert.equal(bannerFlow.storage.get('sgcp-install-banner-dismissed'), '1');
    assert.equal(environment('iPhone Safari', true).banner.hidden, true, 'Standalone app hides install banner');
    const manifest = JSON.parse(fs.readFileSync(path.join(root, 'manifest.webmanifest'), 'utf8'));
    assert.equal(manifest.display, 'standalone');
    for (const icon of manifest.icons) assert.ok(fs.existsSync(path.join(root, icon.src)));
    assert.deepEqual(manifest.icons.map(icon => icon.sizes), ['192x192','512x512']);
    console.log('PASS install prompt acceptance/dismissal, Safari guidance, installed state, worker registration and manifest icons.');
})().catch(error => { console.error(error); process.exitCode = 1; });
