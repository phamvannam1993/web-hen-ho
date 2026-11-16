'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs'),vm=require('node:vm');
let now=1000000,tick,failHistory=0,denied=false;
const records=[],events=[],logs=[];
class Clock extends Date {static now(){return now;}}
const sandbox=vm.createContext({module:{exports:{}},Date:Clock,AbortSignal:{timeout:()=>null},
  setInterval:fn=>{tick=fn;return {unref(){}};},clearInterval(){},
  setTimeout:fn=>{queueMicrotask(fn);},
  fetch:async(url,options)=>{
    const body=JSON.parse(options.body);
    assert.equal(options.headers['X-Realtime-Secret'],'fixture');
    if(url.endsWith('/completed')){
      records.push(body);
      if(failHistory-->0) throw Error('temporary network failure');
      return {ok:true,json:async()=>({ok:true})};
    }
    return {ok:!denied,json:async()=>({ok:true,peer:{id:body.uid===1?2:1,name:'Peer'}})};
  }});
vm.runInContext(fs.readFileSync(require.resolve('../websoket/src/voice.js'),'utf8'),sandbox);
const a={uid:1,readyState:1,authExpiresAt:now+3600000},b={uid:2,readyState:1,authExpiresAt:now+3600000};
const stranger={uid:99,readyState:1,authExpiresAt:now+3600000};
const clients=new Map([[1,new Set([a])],[2,new Set([b])]]);
const voice=sandbox.module.exports.createVoice({config:{appUrl:'https://example.test',secret:'fixture'},clients,
  send:(ws,event)=>events.push({uid:ws.uid,...event}),log:event=>logs.push(event)});
const sdp='v=0\r\na=candidate:1 1 udp 1 192.0.2.1 49160 typ relay\r\n';
let n=0;
async function invite(video=false){
  now+=10001;
  const callId='10000000-0000-4000-8000-'+String(++n).padStart(12,'0');
  await voice.message(a,{t:'voice.invite',callId,conversationId:9,video});return callId;
}
async function accept(callId){
  await voice.message(b,{t:'voice.accept',callId});
  await voice.message(a,{t:'voice.offer',callId,sdp});
  await voice.message(b,{t:'voice.answer',callId,sdp});
}
const flush=()=>new Promise(resolve=>setImmediate(resolve));
(async()=>{
  let callId=await invite(true);now+=30000;await accept(callId);
  await voice.message(stranger,{t:'voice.connected',callId});now+=5000;
  await voice.message(a,{t:'voice.connected',callId});now+=600000;
  await voice.message(b,{t:'voice.connected',callId}); // must not restart duration
  failHistory=1;
  await voice.message(a,{t:'voice.end',callId});await flush();
  assert.equal(records.length,2,'temporary failure retries');
  assert.equal(JSON.stringify(records[0]),JSON.stringify(records[1]),'stable idempotency payload');
  assert.equal(records[0].outcome,'completed');assert.equal(records[0].durationSeconds,600);
  assert.equal(records[0].video,true);assert.equal(records[0].uid,1);assert.equal(records[0].peerId,2);
  await voice.message(b,{t:'voice.end',callId});assert.equal(records.length,2,'both endings persist once');

  callId=await invite();now+=35001;tick();await flush();
  assert.equal(records.at(-1).outcome,'missed');assert.equal(records.at(-1).durationSeconds,0);
  callId=await invite();await voice.message(b,{t:'voice.end',callId});await flush();
  assert.equal(records.at(-1).outcome,'declined');
  callId=await invite();await voice.message(a,{t:'voice.end',callId});await flush();
  assert.equal(records.at(-1).outcome,'missed','caller cancels before answer');
  clients.delete(2);callId=await invite();await flush();
  assert.equal(records.at(-1).outcome,'missed','offline peer');clients.set(2,new Set([b]));
  callId=await invite();await accept(callId);voice.close(a);await flush();
  assert.equal(records.at(-1).outcome,'failed','SDP answer alone is not a connected call');
  const count=records.length;denied=true;await invite();await flush();
  assert.equal(records.length,count,'failed authorization must not create history');denied=false;
  callId=await invite();await accept(callId);await voice.message(b,{t:'voice.connected',callId});
  now+=12345;voice.close(a);await flush();assert.equal(records.at(-1).durationSeconds,12);
  await voice.shutdown();
  console.log('PASS: actual connected duration, missed/declined/offline/failed calls, disconnect, no forged history, retries and deduplication.');
})().catch(error=>{console.error(error);process.exitCode=1;voice.shutdown();});
