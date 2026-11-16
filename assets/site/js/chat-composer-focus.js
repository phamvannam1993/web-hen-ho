(function () {
    'use strict';
    var mobile = window.matchMedia('(max-width: 1023.98px)');
    var selector = '#chat-input, #cw-input, .ig-form textarea[name="content"], .cg-composer textarea[name="content"]';
    var field = null, feed = null, until = 0, frame = 0, timer = 0;
    function stop() {
        cancelAnimationFrame(frame); clearTimeout(timer);
        field = feed = null; until = 0;
    }
    function schedule() {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(function () {
            frame = requestAnimationFrame(function () {
                if (!mobile.matches || !field || !field.isConnected || !feed || !feed.isConnected || document.activeElement !== field || Date.now() > until) return;
                feed.scrollTop = feed.scrollHeight;
            });
        });
    }
    function activate(event) {
        var input = event.target.closest(selector);
        if (!mobile.matches || !input || input.disabled) return;
        var host = input.closest('.ig-chat, .cg-chat, .cw-convo, .tk-ms-pane');
        if (!host || host.closest('[hidden]') || host.classList.contains('has-message-history')) return;
        var target = host.querySelector('[data-ig-feed], [data-cg-feed], #cw-body, #chat-body');
        if (!target) return;
        stop(); field = input; feed = target; until = Date.now() + 800;
        schedule(); timer = setTimeout(schedule, 350);
    }
    document.addEventListener('focusin', activate);
    document.addEventListener('click', activate);
    document.addEventListener('focusout', function (event) { if (event.target === field) stop(); });
    // A deliberate scroll takes priority over adjustment for the opening keyboard.
    document.addEventListener('touchstart', function (event) { if (feed && feed.contains(event.target)) stop(); }, {passive:true});
    document.addEventListener('wheel', function (event) { if (feed && feed.contains(event.target)) stop(); }, {passive:true});
    if (window.visualViewport) window.visualViewport.addEventListener('resize', function () { if (Date.now() <= until) schedule(); });
    window.addEventListener('pagehide', stop);
})();
