// Run: node tests/profile_preview_navigation.js. No real accounts or payments.
const fs = require('fs'), vm = require('vm'), assert = require('assert');
const events = {}, navigation = [], requests = [];
function element() {
    return {dataset: {}, children: [], textContent: '', append(...items) { this.children.push(...items); },
        setAttribute() {}, replaceChildren() { this.children = []; }, addEventListener() {}};
}
const body = element(), status = element(), buy = element(), close = element(), cancel = element();
let opens = 0;
const dialog = Object.assign(element(), {open: false, dataset: {base: '/', selfId: '1', selfSlug: 'me'},
    querySelector(selector) { return {'[data-preview-body]':body,'[data-preview-status]':status,
        '[data-preview-buy]':buy,'[data-preview-close]':close,'[data-preview-cancel]':cancel}[selector]; },
    showModal() { this.open = true; opens++; }, close() { this.open = false; }});
const basePreview = {logged_in: true, purchased: false, cost: 20, balance: 100, slug: 'member',
    url: '/user/member', name: 'Member', age: 30, province: 'Hà Nội'};
let response = {ok: true, preview: {...basePreview, purchased: true, cost: 0}};
let fetchImpl = async () => ({ok: true, json: async () => response});
vm.runInNewContext(fs.readFileSync('assets/site/js/profile-preview.js', 'utf8'), {
    document: {getElementById() { return dialog; }, createElement: element,
        addEventListener(type, fn) { events[type] = fn; }},
    location: {assign(url) { navigation.push(url); }},
    fetch(url, options) { requests.push({url, options}); return fetchImpl(); }, URLSearchParams
});
function click(slug) {
    const trigger = {dataset: {profilePreview: slug}};
    events.click({target: {closest(selector) { return selector === '[data-profile-preview]' ? trigger : null; }},
        preventDefault() {}, stopPropagation() {}});
}
async function flush() { for (let i = 0; i < 16; i++) await Promise.resolve(); }
(async () => {
    click('member'); await flush();
    assert.deepEqual(navigation, ['/user/member']);
    assert.equal(opens, 0, 'Purchased profile never flashes a popup');
    assert(requests.every(r => r.url.endsWith('/preview')), 'Revisiting never calls the purchase endpoint');
    navigation.length = 0;
    response = {ok: true, preview: {...basePreview}};
    click('member'); await flush();
    assert(dialog.open && !buy.disabled, 'Unpaid profile still asks for explicit confirmation');
    assert.equal(navigation.length, 0);
    response = {ok: true, preview: {...basePreview, logged_in: false, purchased: true}};
    click('member'); await flush();
    assert.equal(navigation.length, 0, 'Only authenticated purchased access skips preview');
    assert(dialog.open);
    fetchImpl = async () => { throw new Error('Không thể xem hồ sơ này.'); };
    click('member'); await flush();
    assert.equal(status.textContent, 'Không thể xem hồ sơ này.');
    assert.equal(navigation.length, 0, 'Permission/network errors cannot navigate');
    let resolveOld;
    fetchImpl = () => new Promise(resolve => { resolveOld = resolve; });
    click('old-paid-profile');
    fetchImpl = async () => ({ok: true, json: async () => ({ok:true, preview:{...basePreview}})});
    click('new-profile'); await flush();
    resolveOld({ok:true, json:async()=>({ok:true, preview:{...basePreview,purchased:true,url:'/user/old'}})});
    await flush();
    assert.equal(navigation.length, 0, 'Stale purchased response cannot override the latest profile');
    console.log('PASS: purchased profiles navigate without popup or payment; unpaid, guest, error and stale responses stay safe.');
})().catch(error => { console.error(error); process.exit(1); });
