'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync(require.resolve('../assets/site/js/voice-calls.js'), 'utf8');
const start = source.indexOf('    function mediaErrorMessage(');
const end = source.indexOf('    function fail(', start);
assert(start >= 0 && end > start);

function setup(errorDevice, errorName) {
    const calls = [], tracks = [];
    const context = vm.createContext({ navigator: { mediaDevices: {
        getUserMedia: async constraints => {
            const device = constraints.video ? 'camera' : 'micro';
            calls.push(device);
            if (device === errorDevice) throw { name: errorName };
            const track = { device, stopped: false, stop() { this.stopped = true; } };
            tracks.push(track);
            const streamTracks = [track];
            return {
                getTracks: () => streamTracks,
                getVideoTracks: () => streamTracks.filter(t => t.device === 'camera'),
                addTrack: t => streamTracks.push(t),
            };
        },
    } } });
    vm.runInContext(source.slice(start, end), context);
    return { context, calls, tracks };
}

(async () => {
    // Identify the failing device and release an already-open microphone on camera failure.
    for (const name of ['NotFoundError', 'NotAllowedError', 'NotReadableError', 'OverconstrainedError', 'AbortError']) {
        const camera = setup('camera', name);
        await assert.rejects(camera.context.callMedia(true), error => {
            assert.match(error.message, /camera/);
            assert.doesNotMatch(error.message, /camera hoặc/);
            return true;
        });
        assert.equal(camera.tracks[0].stopped, true);

        const mic = setup('micro', name);
        await assert.rejects(mic.context.callMedia(true), /micro/);
        assert.deepEqual(mic.calls, ['micro']);
    }

    const voice = setup();
    assert.equal((await voice.context.callMedia(false)).getTracks().length, 1);
    assert.deepEqual(voice.calls, ['micro']);

    const video = setup();
    const stream = await video.context.callMedia(true);
    assert.deepEqual(video.calls, ['micro', 'camera']);
    assert.equal(stream.getTracks().length, 2);
    assert(video.tracks.every(track => !track.stopped));
    stream.getTracks().forEach(track => track.stop());
    assert(video.tracks.every(track => track.stopped));
    console.log('voice media: device-specific errors, cleanup, voice and video passed');
})().catch(error => { console.error(error); process.exitCode = 1; });
