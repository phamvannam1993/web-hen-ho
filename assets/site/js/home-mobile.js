(function () {
    'use strict';
    document.querySelectorAll('[data-home-avatar-fallback]').forEach(function (img) {
        function fallback() {
            if (!img.dataset.homeAvatarFallback) return;
            var src = img.dataset.homeAvatarFallback;
            delete img.dataset.homeAvatarFallback;
            img.src = src;
        }
        img.addEventListener('error', fallback, { once: true });
        if (img.complete && !img.naturalWidth) fallback();
    });
    var banner = document.querySelector('[data-home-banner]');
    if (!banner) return;
    var slides = banner.querySelectorAll('[data-home-slide]');
    var buttons = banner.querySelectorAll('[data-home-slide-to]');
    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            var next = Number(button.dataset.homeSlideTo);
            slides.forEach(function (slide, index) { slide.hidden = index !== next; });
            buttons.forEach(function (dot, index) { dot.setAttribute('aria-pressed', String(index === next)); });
        });
    });
})();
