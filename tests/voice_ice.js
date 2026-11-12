'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

// Exercise the browser's SDP gathering flow without microphone access or real timers.
const source = fs.readFileSync(require.resolve('../assets/site/js/voice-calls.js'), 'utf8');
const start = source.indexOf('    async function description(s, type) {');
const end = source.indexOf("    document.addEventListener('click'", start);
assert(start >= 0 && end > start);
const descriptionSource = source.slice(start, end);
const relaySdp = 'v=0\r\na=candidate:1 1 udp 1 127.0.0.1 49160 typ relay\r\n';

async function scenario({ relay, complete, cancel = false }) {
    let timeout, listener, removed = 0;
    const sent = [];
    const pc = {
        iceGatheringState: complete ? 'complete' : 'gathering',
        localDescription: { sdp: relay ? relaySdp : 'v=0\r\n' },
        createOffer: async () => ({ type: 'offer', sdp: 'v=0\r\n' }),
        setLocalDescription: async () => {},
        addEventListener: (_, fn) => { listener = fn; },
        removeEventListener: (_, fn) => { assert.equal(fn, listener); removed++; },
    };
    const s = { pc };
    const context = vm.createContext({
        current: s,
        setTimeout: fn => { timeout = fn; return 1; },
        clearTimeout: () => {},
        send: (_, type, payload) => sent.push({ type, payload }),
    });
    vm.runInContext(descriptionSource, context);
    const pending = context.description(s, 'offer');
    // Allow createOffer/setLocalDescription promises to settle.
    while (!timeout) await Promise.resolve();
    if (cancel) s.cancelGather();
    else if (!complete) timeout();
    let error;
    try { await pending; } catch (e) { error = e; }
    assert.equal(removed, 1);
    assert.equal(s.cancelGather, null);
    return { sent, error };
}

(async () => {
    const normal = await scenario({ relay: true, complete: true });
    assert.equal(normal.error, undefined);
    assert.equal(normal.sent[0].type, 'voice.offer');

    const slowInterface = await scenario({ relay: true, complete: false });
    assert.equal(slowInterface.error, undefined);
    assert.equal(slowInterface.sent[0].payload.sdp, relaySdp);

    const unavailable = await scenario({ relay: false, complete: false });
    assert.match(unavailable.error.message, /TURN/);
    assert.equal(unavailable.sent.length, 0);

    const noRelay = await scenario({ relay: false, complete: true });
    assert.match(noRelay.error.message, /TURN/);
    assert.equal(noRelay.sent.length, 0);

    const canceled = await scenario({ relay: true, complete: false, cancel: true });
    assert.match(canceled.error.message, /hủy/);
    assert.equal(canceled.sent.length, 0);
    console.log('voice ICE: complete, slow interface, unavailable relay, cancellation passed');
})().catch(error => { console.error(error); process.exitCode = 1; });
