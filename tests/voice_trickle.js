'use strict';
const assert=require('node:assert/strict');
const {createVoice}=require('../websoket/src/voice');
const sdp='v=0\r\na=candidate:1 1 udp 1 192.0.2.1 49160 typ relay\r\n';
const candidate={candidate:'candidate:2 1 udp 1 192.0.2.2 49161 typ relay raddr 0.0.0.0 rport 0',sdpMid:'0',sdpMLineIndex:0};
const id='10000000-0000-4000-8000-000000000001';
let pending=[],held=false,fetches=0;
global.fetch=async(_,options)=>{
 fetches++;const {uid}=JSON.parse(options.body);
 const response={ok:true,json:async()=>({ok:true,peer:{id:uid===1?2:1,name:'Peer'},calls_notifications_enabled:true})};
 return held?new Promise(resolve=>pending.push(()=>resolve(response))):response;
};
function fixture(){
 const events=[],a={uid:1,readyState:1,authExpiresAt:Date.now()+3600000},b={uid:2,readyState:1,authExpiresAt:a.authExpiresAt};
 const voice=createVoice({config:{appUrl:'https://example.test',secret:'fixture'},clients:new Map([[1,new Set([a])],[2,new Set([b])]]),send:(ws,m)=>events.push({uid:ws.uid,...m})});
 return {voice,events,a,b};
}
async function release(){held=false;pending.splice(0).forEach(fn=>fn());await new Promise(r=>setImmediate(r));}
(async()=>{
 let f=fixture();
 try{
  await f.voice.message(f.a,{t:'voice.invite',callId:id,conversationId:9,trickle:true});
  held=true;const accepting=f.voice.message(f.b,{t:'voice.accept',callId:id,trickle:true});
  assert.equal(pending.length,2,'Both known participants are authorized concurrently');
  await release();await accepting;
  assert(f.events.filter(e=>e.t==='voice.accepted').every(e=>e.trickle===true));
  await f.voice.message(f.a,{t:'voice.ice',callId:id,candidate});
  assert(!f.events.some(e=>e.t==='voice.ice'),'No ICE before an offer starts');

  held=true;const offering=f.voice.message(f.a,{t:'voice.offer',callId:id,sdp});
  assert.equal(pending.length,2);
  const before=fetches;
  await f.voice.message(f.a,{t:'voice.ice',callId:id,candidate});
  assert.equal(fetches,before,'ICE does not add PHP round trips');
  assert.equal(f.events.filter(e=>e.t==='voice.ice').length,1,'Relay may arrive while offer authorization is pending');
  assert.equal(f.events.at(-1).uid,2);
  const count=()=>f.events.filter(e=>e.t==='voice.ice').length;
  const initial=count();
  await f.voice.message({uid:1,readyState:1,authExpiresAt:f.a.authExpiresAt},{t:'voice.ice',callId:id,candidate});
  await f.voice.message(f.a,{t:'voice.ice',callId:id,candidate:{...candidate,candidate:candidate.candidate.replace('typ relay','typ host')}});
  await f.voice.message(f.a,{t:'voice.ice',callId:id,candidate:{...candidate,sdpMLineIndex:-1}});
  await f.voice.message(f.a,{t:'voice.ice',callId:id,candidate:{...candidate,candidate:'candidate:'+ 'x'.repeat(3000)+' typ relay'}});
  assert.equal(count(),initial,'Reject outsider sockets, host candidates and malformed payloads');
  await release();await offering;
  held=true;const answering=f.voice.message(f.b,{t:'voice.answer',callId:id,sdp});
  await f.voice.message(f.b,{t:'voice.ice',callId:id,candidate});
  assert.equal(f.events.at(-1).uid,1);
  await release();await answering;
  await f.voice.message(f.a,{t:'voice.ice',callId:id,candidate:null});
  assert.equal(f.events.at(-1).candidate,null);
  for(let n=0;n<150;n++)await f.voice.message(f.a,{t:'voice.ice',callId:id,candidate});
  assert.equal(f.events.filter(e=>e.t==='voice.ice'&&e.uid===2).length,128,'Bound candidate count per participant');
  await f.voice.message(f.a,{t:'voice.end',callId:id});
  const ended=count();await f.voice.message(f.b,{t:'voice.ice',callId:id,candidate});assert.equal(count(),ended);
 }finally{f.voice.shutdown();held=false;await release();}
 f=fixture();
 try{
  await f.voice.message(f.a,{t:'voice.invite',callId:id,conversationId:9,trickle:true});
  await f.voice.message(f.b,{t:'voice.accept',callId:id});
  assert(f.events.filter(e=>e.t==='voice.accepted').every(e=>e.trickle===false),'Old clients keep non-trickle flow');
  await f.voice.message(f.a,{t:'voice.offer',callId:id,sdp});
  await f.voice.message(f.a,{t:'voice.ice',callId:id,candidate});
  assert(!f.events.some(e=>e.t==='voice.ice'));
 }finally{f.voice.shutdown();}
 console.log('voice trickle: negotiation, concurrent authorization, early/late ICE, relay-only validation, rate bounds and legacy fallback passed');
})().catch(error=>{console.error(error);process.exitCode=1;});
