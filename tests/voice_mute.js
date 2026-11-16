'use strict';
const assert = require('node:assert/strict');
const {createVoice} = require('../websoket/src/voice');
const events=[], preferences=new Map([[1,true],[2,false]]);
global.fetch=async (_url,options)=>{
  const {uid}=JSON.parse(options.body);
  return {ok:true,json:async()=>({ok:true,peer:{id:uid===1?2:1,name:'Peer'},calls_notifications_enabled:preferences.get(uid)})};
};
function socket(uid){return {uid,readyState:1,authExpiresAt:Date.now()+3600000};}
const caller=socket(1),receiver=socket(2),clients=new Map([[1,new Set([caller])],[2,new Set([receiver])]]);
const voice=createVoice({config:{appUrl:'https://example.test',secret:'test-only'},clients,send:(ws,event)=>events.push({uid:ws.uid,...event})});
const first='10000000-0000-4000-8000-000000000001',second='10000000-0000-4000-8000-000000000002';
(async()=>{
 await voice.message(caller,{t:'voice.invite',callId:first,conversationId:9});
 assert(events.some(e=>e.uid===1&&e.t==='voice.ringing'));
 assert(!events.some(e=>e.uid===2&&e.t==='voice.incoming'));
 await voice.message(receiver,{t:'voice.accept',callId:first});
 assert(!events.some(e=>e.t==='voice.accepted'));
 // Muted receiver is free to place a separate call; only the actual caller is busy.
 await voice.message(receiver,{t:'voice.invite',callId:second,conversationId:9});
 assert(events.some(e=>e.uid===2&&e.callId===second&&e.t==='voice.ended'&&e.reason==='busy'));
 assert(!events.some(e=>e.uid===2&&e.callId===second&&e.t==='voice.error'));
 await voice.message(caller,{t:'voice.end',callId:first});
 assert(!events.some(e=>e.uid===2&&e.callId===first&&e.t==='voice.ended'));
 // New sockets/instance: an enabled receiver receives a normal incoming call.
 preferences.set(2,true);events.length=0;
 const normal=createVoice({config:{appUrl:'https://example.test',secret:'test-only'},clients,send:(ws,event)=>events.push({uid:ws.uid,...event})});
 await normal.message(caller,{t:'voice.invite',callId:first,conversationId:9});
 assert(events.some(e=>e.uid===2&&e.t==='voice.incoming'));
 preferences.set(2,false);
 await normal.message(receiver,{t:'voice.accept',callId:first});
 assert.equal(events.filter(e=>e.t==='voice.accepted').length,2);
 console.log('PASS: suppressed calls do not alert/reserve receiver; enabled calls ring; mute never becomes active-call permission.');
})().catch(error=>{console.error(error);process.exitCode=1;});
