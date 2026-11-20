// Run: node tests/message_badge.js. Exercise the actual account badge updater.
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const source = fs.readFileSync('assets/site/js/app.js', 'utf8');
const code = source.slice(source.indexOf('    var interestCount ='), source.indexOf('    function post(url, data)'));
const events = {}, realtime = {}, timers = new Map();
let timerId = 0, interval, payload = {ok: true, msg: 3}, requests = 0;
const badge = {dataset: {accountMenuCount: 'msg'}, hidden: true, textContent: '',
    setAttribute(name, value) { this[name] = value; }};
const context = {
    base: '/', Promise,
    document: {
        hidden: false,
        querySelector(selector) { return selector === '[data-account-menu-count="msg"]' ? badge : null; },
        querySelectorAll(selector) { return selector === '[data-account-menu-count]' ? [badge] : []; },
        addEventListener(name, fn) { events[name] = fn; }
    },
    window: {
        addEventListener(name, fn) { events['window:' + name] = fn; },
        Realtime: {on(name, fn) { realtime[name] = fn; }}
    },
    setTimeout(fn) { timers.set(++timerId, fn); return timerId; },
    clearTimeout(id) { timers.delete(id); },
    setInterval(fn) { interval = fn; },
    fetch: async () => { requests++; return {json: async () => payload}; }
};
vm.runInNewContext(code, context);
async function flush() {
    for (const [id, fn] of [...timers]) { timers.delete(id); fn(); }
    for (let i = 0; i < 12; i++) await Promise.resolve();
}
(async () => {
    events.DOMContentLoaded();
    realtime['chat.message'](); realtime['chat.message']();
    await flush();
    assert.equal(requests, 1, 'Burst of messages is coalesced');
    assert.equal(badge.hidden, false);
    assert.equal(badge.textContent, 3, 'Updates even when only the mobile message badge exists');
    assert.equal(badge['aria-label'], '3 tin nhắn chưa đọc');
    payload = {ok: true, msg: 123}; interval(); await flush();
    assert.equal(badge.textContent, '99+');
    payload = {ok: true, msg: 0}; events['account-counts-changed'](); await flush();
    assert.equal(badge.hidden, true, 'Reading the last message hides the badge');
    payload = {ok: true, msg: 2}; events['window:focus'](); await flush();
    assert.equal(badge.textContent, 2);
    payload = {ok: false}; realtime['chat.seen'](); await flush();
    assert.equal(badge.textContent, 2, 'Failed response preserves the previous count');
    context.document.hidden = true;
    const before = requests; interval(); await flush();
    assert.equal(requests, before, 'Background tab does not poll');
    context.document.hidden = false; events.visibilitychange(); await flush();
    assert.equal(requests, before + 1, 'Returning to the tab refreshes counts');
    console.log('PASS: mobile badge realtime, polling, read updates, 99+, failures and request coalescing.');
})().catch(error => { console.error(error); process.exit(1); });
