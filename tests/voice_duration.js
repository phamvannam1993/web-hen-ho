'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
let now = 1000000, tick, allowed = true;
const events = [], logs = [];
class Clock extends Date { static now() { return now; } }
const sandbox = vm.createContext({
    module:{exports:{}}, Date:Clock, AbortSignal:{timeout:()=>null},
    setInterval:fn=>{tick=fn;return {unref(){}};}, clearInterval:()=>{},
    fetch:async (url,options)=>{
        if (url.endsWith('/completed')) return {ok:true,json:async()=>({ok:true})};
        const {uid} = JSON.parse(options.body);
        return {ok:allowed,json:async()=>({ok:true,peer:{id:uid===1?2:1,name:'Peer'},calls_notifications_enabled:true})};
    },
});
vm.runInContext(fs.readFileSync(require.resolve('../websoket/src/voice.js'),'utf8'),sandbox);
const caller={uid:1,readyState:1,authExpiresAt:now+3600000};
const receiver={uid:2,readyState:1,authExpiresAt:now+3600000};
const voice=sandbox.module.exports.createVoice({config:{appUrl:'https://example.test',secret:'fixture'},
    clients:new Map([[1,new Set([caller])],[2,new Set([receiver])]]),
    send:(ws,event)=>events.push({uid:ws.uid,...event}),log:event=>logs.push(event)});
const sdp='v=0\r\na=candidate:1 1 udp 1 192.0.2.1 49160 typ relay\r\n';
async function start(suffix) {
    const callId='10000000-0000-4000-8000-00000000000'+suffix;
    await voice.message(caller,{t:'voice.invite',callId,conversationId:9});
    await voice.message(receiver,{t:'voice.accept',callId});
    await voice.message(caller,{t:'voice.offer',callId,sdp});
    await voice.message(receiver,{t:'voice.answer',callId,sdp});
    assert(events.some(e=>e.callId===callId&&e.t==='voice.answer'));
    return callId;
}
async function advance(ms) { now+=ms;tick();await new Promise(resolve=>setImmediate(resolve)); }
(async()=>{
    const first=await start(1);
    for(let n=0;n<12;n++)await advance(30000);
    assert(!events.some(e=>e.callId===first&&e.t==='voice.ended'),'Active call must survive six minutes');
    allowed=false;await advance(30000);
    assert(events.some(e=>e.callId===first&&e.reason==='permission'),'Revoked permissions still end calls');
    assert.equal(logs.at(-1).reason,'permission');
    assert.equal(logs.at(-1).elapsedSeconds,390);

    allowed=true;
    const second=await start(2);
    await advance(50*60000+1);
    assert(events.some(e=>e.callId===second&&e.reason==='expired'),'Fifty-minute limit remains enforced');
    assert.equal(logs.at(-1).reason,'expired');

    // A page's old token can have only five minutes remaining when a new call starts.
    caller.authExpiresAt=receiver.authExpiresAt=now+5*60000;
    const third=await start(3);
    await advance(5*60000);
    assert(events.some(e=>e.callId===third&&e.reason==='expired'));
    assert.equal(logs.at(-1).elapsedSeconds,300);
    voice.shutdown();
    console.log('voice duration: survives six minutes; revocation, fifty-minute limit and old-token expiry verified');
})().catch(error=>{voice.shutdown();console.error(error);process.exitCode=1;});
