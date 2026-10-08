(function () {
    'use strict';
    var card = document.querySelector('.tk-ms.is-open');
    if (!card) return;
    var viewport = window.visualViewport;
    document.documentElement.classList.add('tk-inbox-open');
    function fitInbox() {
        var bottom = viewport ? viewport.offsetTop + viewport.height : window.innerHeight;
        document.querySelectorAll('.tk-bnav, .site-mobile-nav').forEach(function (nav) {
            if (nav.getClientRects().length && getComputedStyle(nav).visibility !== 'hidden') {
                bottom = Math.min(bottom, nav.getBoundingClientRect().top);
            }
        });
        card.style.setProperty('--inbox-height', Math.max(0, bottom - card.getBoundingClientRect().top) + 'px');
    }
    var frame;
    function scheduleFit() {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(fitInbox);
    }
    window.addEventListener('resize', scheduleFit);
    window.addEventListener('load', scheduleFit);
    if (viewport) {
        viewport.addEventListener('resize', scheduleFit);
        viewport.addEventListener('scroll', scheduleFit);
    }
    if (window.ResizeObserver) {
        var observer = new ResizeObserver(scheduleFit);
        document.querySelectorAll('.tk-bnav, .site-mobile-nav, .site-header, .tk-mhead').forEach(function (element) {
            observer.observe(element);
        });
    }
    // Contain touch scrolling even on older Safari without overscroll-behavior.
    var previousY = 0;
    document.addEventListener('touchstart', function (event) {
        if (event.touches.length === 1) previousY = event.touches[0].clientY;
    }, { passive: true });
    document.addEventListener('touchmove', function (event) {
        if (event.touches.length !== 1) return;
        var area = event.target.closest('.tk-ms-body, .tk-ms-list, .tk-ms .emoji-list, .tk-side__in, .modal-body');
        var delta = event.touches[0].clientY - previousY;
        previousY = event.touches[0].clientY;
        if (!area || area.scrollHeight <= area.clientHeight ||
            (delta > 0 && area.scrollTop <= 0) ||
            (delta < 0 && area.scrollTop + area.clientHeight >= area.scrollHeight - 1)) {
            if (event.cancelable) event.preventDefault();
        }
    }, { passive: false });
    fitInbox();
})();
