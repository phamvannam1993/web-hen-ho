'use strict';
const assert=require('node:assert/strict');
const {createVoice}=require('../websoket/src/voice');
global.fetch=async(_,options)=>{
  const {uid}=JSON.parse(options.body);
  return {ok:true,json:async()=>({ok:true,peer:{id:uid===1?2:1,name:'Peer'}})};
};
const a={uid:1,readyState:1,authExpiresAt:Date.now()+3600000};
const b={uid:2,readyState:1,authExpiresAt:a.authExpiresAt};
const clients=new Map([[1,new Set([a])],[2,new Set([b])]]),events=[];
const voice=createVoice({config:{appUrl:'https://example.test',secret:'fixture'},clients,
  send:(ws,event)=>events.push({uid:ws.uid,...event})});
const id=n=>'10000000-0000-4000-8000-'+String(n).padStart(12,'0');
(async()=>{
  try{
    await voice.message(a,{t:'voice.invite',callId:id(1),conversationId:9});
    await voice.message(a,{t:'voice.invite',callId:id(2),conversationId:9});
    assert.equal(events.at(-1).reason,'caller_busy');
    await voice.message(a,{t:'voice.end',callId:id(1)});
    await voice.message(a,{t:'voice.invite',callId:id(3),conversationId:9});
    assert.equal(events.at(-1).reason,'cooldown');
    assert(events.at(-1).retryAfter>=1&&events.at(-1).retryAfter<=10);
  }finally{await voice.shutdown();}
  const offlineEvents=[];
  const offline=createVoice({config:{appUrl:'https://example.test',secret:'fixture'},
    clients:new Map([[1,new Set([a])]]),send:(ws,event)=>offlineEvents.push(event)});
  try{
    await offline.message(a,{t:'voice.invite',callId:id(4),conversationId:9});
    assert.equal(offlineEvents.at(-1).reason,'offline');
    await offline.message(a,{t:'voice.invite',callId:id(5),conversationId:9});
    assert.equal(offlineEvents.at(-1).reason,'cooldown');
  }finally{await offline.shutdown();}
  console.log('PASS: active caller, offline recipient and retry cooldown have distinct reasons; cooldown includes remaining seconds.');
})().catch(error=>{console.error(error);process.exitCode=1;});
