'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const source=fs.readFileSync(require.resolve('../assets/site/js/voice-calls.js'),'utf8');
const start=source.indexOf('    function ensureVideo('),end=source.indexOf('    function show(',start);
assert(start>=0&&end>start);
function fixture(){
  const cam={kind:'video',stopped:false,stop(){this.stopped=true;}};
  const mic={kind:'audio',stopped:false,stop(){this.stopped=true;}};
  const tracks=[mic],added=[];
  const s={stream:{addTrack:t=>tracks.push(t)},pc:{addTrack(t){added.push(t);return {track:t};}}};
  const context=vm.createContext({current:s,title:{},dialog:{classList:{add(){}}},status:{},
    navigator:{mediaDevices:{getUserMedia:async()=>({getVideoTracks:()=>[cam],getTracks:()=>[cam]})}},
    showVideo(){},mediaErrorMessage:()=>null});
  vm.runInContext(source.slice(start,end),context);
  return {cam,mic,tracks,added,s,context};
}
(async()=>{
  let f=fixture();
  let resolve;
  f.context.navigator.mediaDevices.getUserMedia=()=>new Promise(r=>{resolve=r;});
  const first=f.context.ensureVideo(f.s),second=f.context.ensureVideo(f.s);
  assert.equal(first,second,'Accepted/offer share one pending camera request');
  resolve({getVideoTracks:()=>[f.cam],getTracks:()=>[f.cam]});
  assert.equal(await first,true);assert.equal(f.added.length,1);assert.equal(f.tracks.length,2);
  assert(!f.mic.stopped&&!f.cam.stopped,'Camera upgrade preserves microphone');

  f=fixture();f.context.navigator.mediaDevices.getUserMedia=()=>new Promise(r=>{resolve=r;});
  const pending=f.context.ensureVideo(f.s);f.context.current=null;
  resolve({getVideoTracks:()=>[f.cam],getTracks:()=>[f.cam]});
  assert.equal(await pending,false);assert(f.cam.stopped);assert.equal(f.added.length,0);

  f=fixture();f.context.navigator.mediaDevices.getUserMedia=async()=>{throw Error('camera denied');};
  await assert.rejects(f.context.ensureVideo(f.s),/camera denied/);assert.equal(f.added.length,0);
  f=fixture();f.s.pc.addTrack=()=>{throw Error('connection closed');};
  await assert.rejects(f.context.ensureVideo(f.s),/connection closed/);assert(f.cam.stopped,'Failed adoption releases camera');
  console.log('PASS: receiver camera setup is shared, keeps microphone, releases camera after cancellation/failure and reports denied permissions.');
})().catch(error=>{console.error(error);process.exitCode=1;});
