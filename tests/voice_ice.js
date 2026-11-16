'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

// Exercise the browser's SDP gathering flow without microphone access or real timers.
const source = fs.readFileSync(require.resolve('../assets/site/js/voice-calls.js'), 'utf8');
const start = source.indexOf('    async function gatherDescription(s, type) {');
const end = source.indexOf("    document.addEventListener('click'", start);
assert(start >= 0 && end > start);
const descriptionSource = source.slice(start, end);
const relaySdp = 'v=0\r\na=candidate:1 1 udp 1 127.0.0.1 49160 typ relay\r\n';

async function scenario({ relay, complete, cancel = false, trickle = false }) {
    let timeout, removed = 0, timedOut = false;
    const listeners = new Map();
    const sent = [];
    const pc = {
        iceGatheringState: complete ? 'complete' : 'gathering',
        localDescription: { sdp: relay ? relaySdp : 'v=0\r\n' },
        createOffer: async () => ({ type: 'offer', sdp: 'v=0\r\n' }),
        setLocalDescription: async () => {},
        addEventListener: (type, fn) => { listeners.set(type,fn); },
        removeEventListener: (type, fn) => { assert.equal(fn, listeners.get(type)); removed++; },
    };
    const s = { pc, trickle };
    const context = vm.createContext({
        current: s,
        setTimeout: fn => { timeout = fn; return 1; },
        clearTimeout: () => {},
        send: (_, type, payload) => sent.push({ type, payload }),
        fail: () => {},
    });
    vm.runInContext(descriptionSource, context);
    const pending = context.description(s, 'offer');
    // Allow createOffer/setLocalDescription promises to settle.
    while (!timeout) await Promise.resolve();
    if (cancel) s.cancelGather();
    else if (!complete && !(trickle && relay)) { timedOut = true; timeout(); }
    let error;
    try { await pending; } catch (e) { error = e; }
    assert.equal(removed, 2);
    assert.equal(s.cancelGather, null);
    return { sent, error, timedOut };
}

(async () => {
    const normal = await scenario({ relay: true, complete: true });
    assert.equal(normal.error, undefined);
    assert.equal(normal.sent[0].type, 'voice.offer');

    const slowInterface = await scenario({ relay: true, complete: false });
    assert.equal(slowInterface.error, undefined);
    assert.equal(slowInterface.sent[0].payload.sdp, relaySdp);
    assert.equal(slowInterface.timedOut,true,'Legacy peers still wait for complete gathering or deadline');
    const fast = await scenario({relay:true,complete:false,trickle:true});
    assert.equal(fast.error,undefined);
    assert.equal(fast.timedOut,false,'Negotiated trickle sends first relay without waiting for gathering timeout');
    assert.equal(fast.sent[0].type,'voice.offer');

    const unavailable = await scenario({ relay: false, complete: false });
    assert.match(unavailable.error.message, /TURN/);
    assert.equal(unavailable.sent.length, 0);

    const noRelay = await scenario({ relay: false, complete: true });
    assert.match(noRelay.error.message, /TURN/);
    assert.equal(noRelay.sent.length, 0);

    const canceled = await scenario({ relay: true, complete: false, cancel: true });
    assert.match(canceled.error.message, /hủy/);
    assert.equal(canceled.sent.length, 0);

    let offers = 0, failCount = 0;
    const sent = [];
    const s = {};
    const context = vm.createContext({current:s,send:(_,type)=>sent.push(type),fail:()=>{failCount++;}});
    vm.runInContext(descriptionSource, context);
    context.gatherDescription = async () => { offers++; return relaySdp; };
    await context.warmOffer(s);
    assert.equal(sent.length, 0, 'Preparing during ringing must not send the offer');
    await context.description(s, 'offer');
    assert.equal(offers, 1, 'Acceptance reuses the prepared offer');
    assert.deepEqual(sent, ['voice.offer']);
    assert.equal(failCount, 0);

    let reject;
    const canceledCall = {};
    context.current = canceledCall;
    context.gatherDescription = () => new Promise((_,rejectOffer) => { reject = rejectOffer; });
    const warming = context.warmOffer(canceledCall);
    context.current = {};
    reject(new Error('canceled'));
    await assert.rejects(warming, /canceled/);
    assert.equal(failCount, 0, 'A canceled warmup must not affect a subsequent call');
    assert.equal(sent.length, 1);

    const candidates=[],remote={trickle:true,pc:{addIceCandidate:async c=>candidates.push(c)}};
    context.current=remote;
    context.addRemoteIce(remote,{candidate:'first'});
    context.addRemoteIce(remote,null);
    assert.equal(candidates.length,0,'Candidates arriving before SDP are queued');
    remote.remoteReady=true;
    remote.remoteIce.forEach(c=>context.addRemoteIce(remote,c));
    await remote.remoteIceTask;
    assert.equal(candidates.length,2);
    assert.equal(candidates[1],null);
    context.current={};context.addRemoteIce(remote,{candidate:'stale'});
    await new Promise(resolve=>setImmediate(resolve));
    assert.equal(candidates.length,2,'Old calls cannot inject candidates into a new call');
    console.log('voice ICE: fast trickle, legacy fallback, relay failures, cancellation, warmup and remote queues passed');
})().catch(error => { console.error(error); process.exitCode = 1; });
