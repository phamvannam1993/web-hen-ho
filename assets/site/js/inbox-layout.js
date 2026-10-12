(function () {
    'use strict';
    var card = document.querySelector('.tk-ms');
    if (!card) return;
    var viewport = window.visualViewport;
    var isOpen = card.classList.contains('is-open');
    var mobile = window.matchMedia('(max-width: 1023.98px)');
    document.documentElement.classList.toggle('tk-inbox-open', isOpen);
    function fitInbox() {
        document.documentElement.classList.toggle('tk-inbox-list', !isOpen && mobile.matches);
        if (!isOpen && !mobile.matches) return;
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
        document.querySelectorAll('.tk-bnav, .site-mobile-nav, .topbar, .site-header, .tk-mhead, .tk-main__in > .tk-alert').forEach(function (element) {
            observer.observe(element);
        });
    }
    // Contain touch scrolling even on older Safari without overscroll-behavior.
    var previousY = 0;
    document.addEventListener('touchstart', function (event) {
        if (event.touches.length === 1) previousY = event.touches[0].clientY;
    }, { passive: true });
    document.addEventListener('touchmove', function (event) {
        if (!isOpen && !mobile.matches) return;
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
