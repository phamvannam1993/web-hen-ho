(function () {
    'use strict';
    var script = document.currentScript;
    if (!script || !window.Audio) return;
    var audio = new Audio(script.dataset.sound);
    audio.preload = 'auto';
    var seen = new Set(), lastPlayed = 0, unlocked = false;
    function ignoreRejection(result) { if (result && result.catch) result.catch(function () {}); }
    function unlock() {
        if (unlocked) return;
        audio.muted = true;
        var result = audio.play();
        if (result && result.then) result.then(function () {
            audio.pause(); audio.currentTime = 0; audio.muted = false; unlocked = true;
        }).catch(function () { audio.muted = false; });
    }
    document.addEventListener('pointerdown', unlock, {passive: true});
    document.addEventListener('keydown', unlock);
    window.MessageSound = {play: function (key) {
        if (key && seen.has(key)) return;
        if (key) {
            seen.add(key);
            if (seen.size > 500) seen.delete(seen.values().next().value);
        }
        // A catch-up batch should produce a single notification.
        if (Date.now() - lastPlayed < 1000) return;
        lastPlayed = Date.now();
        audio.muted = false;
        audio.currentTime = 0;
        ignoreRejection(audio.play());
    }};
}());
