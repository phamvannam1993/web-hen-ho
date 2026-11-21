// Run: node tests/home_banner_swipe.js. Exercise the real banner gesture handlers.
const fs = require('fs'), vm = require('vm'), assert = require('assert');
const handlers = {}, timers = new Map(); let serial = 0;
const slides = Array.from({length: 3}, (_, i) => ({hidden: i !== 0}));
const images = slides.map(() => ({classList: {toggle() {}}, setAttribute() {}}));
const buttons = slides.map((_, i) => ({dataset: {homeSlideTo: String(i)},
    setAttribute(name, value) { this[name] = value; },
    addEventListener(type, fn) { this[type] = fn; }}));
const banner = {
    contains() { return false; },
    querySelectorAll(selector) { return selector === '[data-home-slide]' ? slides : selector === '[data-home-banner-image]' ? images : buttons; },
    addEventListener(type, fn) { handlers[type] = fn; }
};
const mobile = {matches: true, addEventListener() {}}, reduced = {matches: false, addEventListener() {}};
const context = {
    Date, document: {hidden: false, activeElement: null,
        querySelector(selector) { return selector === '[data-home-banner]' ? banner : null; },
        querySelectorAll() { return []; }, addEventListener() {}},
    window: {matchMedia(query) { return query.includes('reduced-motion') ? reduced : mobile; }},
    setTimeout(fn) { timers.set(++serial, fn); return serial; }, clearTimeout(id) { timers.delete(id); }
};
vm.runInNewContext(fs.readFileSync('assets/site/js/home-mobile.js', 'utf8'), context);
const point = (x, y) => ({clientX: x, clientY: y});
const current = () => slides.findIndex(slide => !slide.hidden);
function swipe(x, y, dx, dy) {
    handlers.touchstart({touches: [point(x, y)]});
    assert.equal(timers.size, 0, 'Autoplay pauses while touching');
    handlers.touchmove({touches: [point(x + dx, y + dy)]});
    handlers.touchend({touches: [], changedTouches: [point(x + dx, y + dy)]});
}
swipe(200, 60, -100, 2); assert.equal(current(), 1, 'Swipe left goes forward');
assert.equal(timers.size, 1, 'Autoplay restarts after swipe');
swipe(200, 60, -100, 0); assert.equal(current(), 2);
swipe(200, 60, -100, 0); assert.equal(current(), 0, 'Wrap from last to first');
swipe(100, 60, 100, 0); assert.equal(current(), 2, 'Swipe right wraps to last');
assert.equal(buttons[2]['aria-pressed'], 'true', 'Dots stay synchronized');
swipe(100, 60, 10, 2); assert.equal(current(), 2, 'Small movement is a tap');
swipe(100, 60, 40, 90); assert.equal(current(), 2, 'Vertical scroll does not switch slides');
handlers.touchstart({touches: [point(100, 60)]});
handlers.touchmove({touches: [point(105, 90)]});
handlers.touchend({touches: [], changedTouches: [point(250, 90)]});
assert.equal(current(), 2, 'Vertical gesture stays vertical after turning');
handlers.touchstart({touches: [point(100, 60), point(150, 60)]});
handlers.touchend({touches: [], changedTouches: [point(250, 60)]});
assert.equal(current(), 2, 'Pinching cannot switch banners');
handlers.touchstart({touches: [point(100, 60)]}); handlers.touchcancel();
assert.equal(timers.size, 1, 'Cancelled touch resumes autoplay');
let cancelled = false;
handlers.click({preventDefault() { cancelled = true; }, stopImmediatePropagation() {}});
assert(cancelled, 'Compatibility click after swipe cannot select an accidental dot');
buttons[0].click(); assert.equal(current(), 0, 'Dot handler still selects a slide');
reduced.matches = true; handlers.touchcancel(); assert.equal(timers.size, 0, 'Reduced motion disables autoplay');
swipe(200, 60, -100, 0); assert.equal(current(), 1, 'Manual swipe works with reduced motion');
console.log('PASS: swipe directions, wrapping, dots, vertical scroll, multi-touch, cancellation, click suppression and autoplay.');
