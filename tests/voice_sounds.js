'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync(require.resolve('../assets/site/js/voice-calls.js'), 'utf8');
const start = source.indexOf('    function createCallSounds(');
const end = source.indexOf('    var callSounds =', start);
assert(start >= 0 && end > start);

function setup() {
    const audio = [];
    class FakeAudio {
        constructor(src) { this.src = src; this.paused = true; this.plays = 0; this.currentTime = 0; audio.push(this); }
        play() {
            this.plays++;
            if (this.blocked) { this.paused = true; return Promise.reject(new Error('autoplay')); }
            this.paused = false;
            return this.pending || Promise.resolve();
        }
        pause() { this.paused = true; }
    }
    const context = vm.createContext({window:{Audio:FakeAudio}});
    vm.runInContext(source.slice(start,end),context);
    return {sound:context.createCallSounds('/audio/'),audio};
}

(async () => {
    const {sound,audio} = setup();
    const [incoming,outgoing,ended] = audio;
    assert.deepEqual(audio.map(a=>a.src), ['/audio/goi-den.mp3','/audio/goi-di.mp3','/audio/end-call.mp3']);
    sound.play('incoming');
    assert(incoming.loop && !incoming.paused);
    incoming.currentTime = 12;
    sound.play('outgoing');
    assert(incoming.paused && incoming.currentTime === 0);
    assert(outgoing.loop && !outgoing.paused);
    sound.stop();
    assert(audio.every(a=>a.paused && a.currentTime === 0));
    sound.play('ended');
    assert(!ended.loop && !ended.paused);
    assert(incoming.paused && outgoing.paused);
    ended.onended();

    const blocked = setup();
    blocked.audio[0].blocked = true;
    blocked.sound.play('incoming');
    await new Promise(resolve=>setImmediate(resolve));
    blocked.audio[0].blocked = false;
    blocked.sound.unlock();
    assert.equal(blocked.audio[0].plays,2,'Blocked ringing retries on interaction');
    blocked.sound.stop();
    blocked.sound.unlock();
    assert(blocked.audio[0].paused || blocked.audio[0].muted,'Stopped ringing must not restart audibly on interaction');
    await new Promise(resolve=>setImmediate(resolve));
    assert(blocked.audio[0].paused,'Silent priming must stop after playback is unlocked');

    const late = setup();
    let resolveUnlock;
    late.audio[0].pending = new Promise(resolve=>{resolveUnlock=resolve;});
    late.sound.unlock();
    late.sound.play('incoming');
    resolveUnlock();
    await new Promise(resolve=>setImmediate(resolve));
    assert(!late.audio[0].paused && !late.audio[0].muted,'Late unlock must not stop an active ringtone');
    late.sound.stop();
    assert(late.audio.every(a=>a.paused));
    console.log('voice sounds: file mapping, looping, transitions, autoplay retry and late unlock passed');
})().catch(error=>{console.error(error);process.exitCode=1;});
