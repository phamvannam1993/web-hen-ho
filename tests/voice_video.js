'use strict';
const assert=require('node:assert/strict');
const {createVoice}=require('../websoket/src/voice');
global.fetch=async(url,options)=>{
  const {uid}=JSON.parse(options.body);
  return {ok:true,json:async()=>({ok:true,peer:{id:uid===1?2:1,name:'Peer'}})};
};
const sdp='v=0\r\nm=audio 9 UDP/TLS/RTP/SAVPF 111\r\nm=video 9 UDP/TLS/RTP/SAVPF 96\r\na=candidate:1 1 udp 1 192.0.2.1 49160 typ relay\r\n';
(async()=>{
  for(const value of [true,1,'1','true',false,'false',undefined]){
    const a={uid:1,readyState:1,authExpiresAt:Date.now()+3600000};
    const b={uid:2,readyState:1,authExpiresAt:a.authExpiresAt},events=[];
    const voice=createVoice({config:{appUrl:'https://example.test',secret:'fixture'},
      clients:new Map([[1,new Set([a])],[2,new Set([b])]]),send:(ws,event)=>events.push({uid:ws.uid,...event})});
    const callId='10000000-0000-4000-8000-000000000001';
    const expected=[true,1,'1','true'].includes(value);
    try{
      await voice.message(a,{t:'voice.invite',callId,conversationId:9,video:value});
      const incoming=events.find(e=>e.uid===2&&e.t==='voice.incoming');
      assert.equal(incoming.video,expected,'Incoming event carries canonical video flag');
      await voice.message(b,{t:'voice.accept',callId});
      const accepted=events.filter(e=>e.t==='voice.accepted');
      assert.equal(accepted.length,2);
      assert(accepted.every(e=>e.video===expected),'Both participants receive the same mode');
      const description=expected?sdp:sdp.replace('m=video 9 UDP/TLS/RTP/SAVPF 96\r\n','');
      await voice.message(a,{t:'voice.offer',callId,sdp:description});
      await voice.message(b,{t:'voice.answer',callId,sdp:description});
      assert.equal(events.find(e=>e.uid===2&&e.t==='voice.offer').video,expected);
      assert.equal(events.find(e=>e.uid===1&&e.t==='voice.answer').video,expected);
    }finally{await voice.shutdown();}
  }
  console.log('PASS: video mode preserved for incoming/accepted/offer/answer on both sides; boolean/string flags and audio calls.');
})().catch(error=>{console.error(error);process.exitCode=1;});
