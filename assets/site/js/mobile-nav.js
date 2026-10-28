(function () {
    'use strict';

    var trigger = document.getElementById('mobile-explore-trigger');
    var dialog = document.getElementById('mobile-explore-dialog');
    if (!trigger || !dialog) return;

    var nav = document.querySelector('.site-mobile-nav');
    var viewport = window.visualViewport;
    var closing = false;

    function closeExplore() {
        if (!dialog.open || closing) return;
        if (!dialog.animate || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            dialog.close();
            return;
        }
        closing = true;
        var animation = dialog.animate([
            { transform: getComputedStyle(dialog).transform },
            { transform: 'translateX(100%)' }
        ], {
            duration: 240,
            easing: 'ease-in',
            fill: 'forwards'
        });
        function finishClose() {
            dialog.close();
            animation.cancel();
            closing = false;
        }
        animation.finished.then(finishClose, finishClose);
    }

    function fitAboveNav() {
        var navHeight = nav && nav.getClientRects().length ? nav.offsetHeight : 0;
        var top = viewport ? viewport.offsetTop : 0;
        var height = viewport ? viewport.height : window.innerHeight;
        dialog.style.setProperty('--explore-top', top + 'px');
        dialog.style.setProperty('--explore-height', Math.max(0, height - navHeight) + 'px');
    }

    trigger.addEventListener('click', function () {
        if (dialog.open) {
            closeExplore();
            return;
        }
        fitAboveNav();
        dialog.show();
        document.documentElement.classList.add('mobile-explore-open');
        trigger.setAttribute('aria-expanded', 'true');
    });

    dialog.addEventListener('close', function () {
        document.documentElement.classList.remove('mobile-explore-open');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.focus();
    });

    dialog.querySelector('form[method="dialog"]').addEventListener('submit', function (event) {
        event.preventDefault();
        closeExplore();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && dialog.open) {
            event.preventDefault();
            closeExplore();
        }
    });

    if (nav) {
        nav.addEventListener('click', function (event) {
            if (dialog.open && event.target.closest('a')) closeExplore();
        });
    }

    window.addEventListener('resize', fitAboveNav);
    if (viewport) {
        viewport.addEventListener('resize', fitAboveNav);
        viewport.addEventListener('scroll', fitAboveNav);
    }
    if (nav && window.ResizeObserver) {
        new ResizeObserver(fitAboveNav).observe(nav);
    }
}());
