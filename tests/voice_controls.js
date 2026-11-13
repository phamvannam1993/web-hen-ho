'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require.resolve('../assets/site/js/voice-calls.js'),'utf8');
function extract(start,end) {
    const a=source.indexOf(start),b=source.indexOf(end,a);
    assert(a>=0&&b>a);return source.slice(a,b);
}
function track(kind) { return {kind,stopped:false,stop(){this.stopped=true;}}; }
function stream(tracks) {
    return {getTracks:()=>tracks.slice(),getVideoTracks:()=>tracks.filter(t=>t.kind==='video'),
        addTrack:t=>tracks.push(t),removeTrack:t=>{const i=tracks.indexOf(t);if(i>=0)tracks.splice(i,1);}};
}
(async()=>{
    const mic=track('audio'),cam=track('video'),fresh=track('video');
    const sender={track:cam,replaceTrack:async function(t){this.track=t;}};
    const s={stream:stream([mic,cam]),videoSender:sender,cameraEnabled:true};
    let requested;
    const context=vm.createContext({current:s,navigator:{mediaDevices:{getUserMedia:async c=>{requested=c;return stream([fresh]);}}}});
    vm.runInContext(extract('    async function changeCamera(', '    camera.onclick ='),context);
    assert.equal(await context.changeCamera(s,false),true);
    assert(cam.stopped&&!mic.stopped);
    assert.equal(sender.track,null);
    assert.equal(s.cameraEnabled,false);
    assert.equal(await context.changeCamera(s,true),true);
    assert.equal(requested.audio,false);
    assert.equal(sender.track,fresh);
    assert(!fresh.stopped&&!mic.stopped&&s.cameraEnabled);

    const failed=track('video');
    context.navigator.mediaDevices.getUserMedia=async()=>stream([failed]);
    sender.replaceTrack=async()=>{throw Error('replace failed');};
    await assert.rejects(context.changeCamera(s,true),/replace failed/);
    assert(failed.stopped&&!fresh.stopped,'Failed replacement releases fresh camera, preserves old camera');

    let resolveMedia;
    const canceled=track('video');
    context.navigator.mediaDevices.getUserMedia=()=>new Promise(r=>{resolveMedia=r;});
    const opening=context.changeCamera(s,true);
    context.current=null;resolveMedia(stream([canceled]));
    assert.equal(await opening,false);
    assert(canceled.stopped,'Camera granted after hangup must stop');

    let completeOld;
    const audio={sinkId:'',setSinkId(id){return new Promise(r=>{completeOld=()=>{this.sinkId=id;r();};});}};
    const video={sinkId:'',setSinkId:async function(id){this.sinkId=id;}};
    const oldCall={video:false},newCall={video:false};
    const routing=vm.createContext({current:oldCall,audio,remoteVideo:video,outputTask:Promise.resolve()});
    vm.runInContext(extract('    function routeOutput(', '    function outputError('),routing);
    const oldChange=routing.routeOutput(oldCall,'headset');
    await new Promise(r=>setImmediate(r));
    routing.current=newCall;
    const reset=routing.routeOutput(newCall,'');
    completeOld();
    assert.equal(await oldChange,false);
    await new Promise(r=>setImmediate(r));completeOld();
    assert.equal(await reset,true);
    assert.equal(audio.sinkId,'','New call reset overrides late old selection');
    routing.current={video:true};
    assert.equal(await routing.routeOutput(routing.current,'speakers'),true);
    assert.equal(video.sinkId,'speakers');
    assert.equal(audio.sinkId,'');
    console.log('voice controls: camera off/on, mic preservation, failure/cancel cleanup and output routing races passed');
})().catch(error=>{console.error(error);process.exitCode=1;});
