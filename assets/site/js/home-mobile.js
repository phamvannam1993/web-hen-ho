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
    var more = document.querySelector('[data-home-load-more]');
    if (more) {
        var list = more.previousElementSibling;
        var loading = false;
        var ended = false;
        var failed = false;
        var seen = new Set();
        function rememberCards() {
            document.querySelectorAll('.home-mobile [data-user]').forEach(function (card) {
                seen.add(card.dataset.user);
            });
        }
        rememberCards();
        // Giữ cả hồ sơ đã bị thay sau khi thả tim/bỏ qua để không thêm lại.
        var changes = new MutationObserver(rememberCards);
        changes.observe(list, { childList: true });
        var observer = 'IntersectionObserver' in window ? new IntersectionObserver(function (entries) {
            if (entries.some(function (entry) { return entry.isIntersecting; }) && !failed) loadMore();
        }, { rootMargin: '0px 0px 240px 0px' }) : null;
        function loadMore() {
            if (loading || ended || !window.matchMedia('(max-width: 767px)').matches) return;
            loading = true;
            failed = false;
            more.disabled = true;
            more.textContent = 'Đang tải hồ sơ…';
            list.setAttribute('aria-busy', 'true');
            rememberCards();
            if (observer) observer.unobserve(more);
            fetch(more.dataset.url + '?offset=' + encodeURIComponent(more.dataset.offset), {
                credentials: 'same-origin', headers: { Accept: 'application/json' }
            }).then(function (response) {
                if (!response.ok) throw new Error('load');
                return response.json();
            }).then(function (data) {
                if (!data.ok) throw new Error('load');
                var template = document.createElement('template');
                template.innerHTML = data.html;
                rememberCards();
                template.content.querySelectorAll('[data-user]').forEach(function (card) {
                    if (seen.has(card.dataset.user)) card.remove();
                    else seen.add(card.dataset.user);
                });
                list.appendChild(template.content);
                more.dataset.offset = data.next_offset;
                ended = !data.has_more;
                more.hidden = ended;
                more.textContent = 'Xem thêm hồ sơ';
                if (ended) changes.disconnect();
            }).catch(function () {
                failed = true;
                more.textContent = 'Chưa tải được hồ sơ. Bấm để thử lại';
            }).finally(function () {
                loading = false;
                more.disabled = false;
                list.removeAttribute('aria-busy');
                if (observer && !ended && !failed) observer.observe(more);
            });
        }
        more.addEventListener('click', loadMore);
        if (observer) observer.observe(more);
    }
    var banner = document.querySelector('[data-home-banner]');
    if (!banner) return;
    var slides = banner.querySelectorAll('[data-home-slide]');
    var images = banner.querySelectorAll('[data-home-banner-image]');
    var buttons = banner.querySelectorAll('[data-home-slide-to]');
    var activeSlide = 0, slideTimer, swipe = null, suppressClickUntil = 0;
    var mobileBanner = window.matchMedia('(max-width: 767px)');
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    function scheduleSlide() {
        clearTimeout(slideTimer);
        if (swipe || document.hidden || !mobileBanner.matches || reducedMotion.matches || banner.contains(document.activeElement)) return;
        slideTimer = setTimeout(function () { showSlide((activeSlide + 1) % slides.length); }, 4000);
    }
    function showSlide(next) {
        if (next < 0 || next >= slides.length) return;
        activeSlide = next;
        slides.forEach(function (slide, index) { slide.hidden = index !== next; });
        images.forEach(function (img, index) {
            img.classList.toggle('is-active', index === next);
            img.setAttribute('aria-hidden', String(index !== next));
        });
        buttons.forEach(function (dot, index) { dot.setAttribute('aria-pressed', String(index === next)); });
        scheduleSlide();
    }
    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            showSlide(Number(button.dataset.homeSlideTo));
        });
    });
    banner.addEventListener('touchstart', function (event) {
        swipe = null;
        if (!mobileBanner.matches || event.touches.length !== 1) { scheduleSlide(); return; }
        swipe = { x: event.touches[0].clientX, y: event.touches[0].clientY };
        clearTimeout(slideTimer);
    }, { passive: true });
    banner.addEventListener('touchmove', function (event) {
        if (!swipe) return;
        if (event.touches.length !== 1) { swipe = null; scheduleSlide(); return; }
        var dx = Math.abs(event.touches[0].clientX - swipe.x);
        var dy = Math.abs(event.touches[0].clientY - swipe.y);
        // A vertical gesture belongs to page scrolling, even if the finger later turns.
        if (dy > 12 && dy > dx) { swipe = null; scheduleSlide(); }
    }, { passive: true });
    banner.addEventListener('touchend', function (event) {
        var start = swipe; swipe = null;
        if (!start || event.touches.length || event.changedTouches.length !== 1) { scheduleSlide(); return; }
        var dx = event.changedTouches[0].clientX - start.x;
        var dy = event.changedTouches[0].clientY - start.y;
        if (Math.abs(dx) >= 36 && Math.abs(dx) > Math.abs(dy) * 1.25) {
            suppressClickUntil = Date.now() + 350;
            showSlide((activeSlide + (dx < 0 ? 1 : -1) + slides.length) % slides.length);
        } else scheduleSlide();
    }, { passive: true });
    banner.addEventListener('touchcancel', function () { swipe = null; scheduleSlide(); }, { passive: true });
    banner.addEventListener('click', function (event) {
        if (Date.now() < suppressClickUntil) { event.preventDefault(); event.stopImmediatePropagation(); }
    }, true);
    banner.addEventListener('focusin', function () { clearTimeout(slideTimer); });
    banner.addEventListener('focusout', function () { setTimeout(scheduleSlide, 0); });
    document.addEventListener('visibilitychange', scheduleSlide);
    [mobileBanner, reducedMotion].forEach(function (query) {
        if (query.addEventListener) query.addEventListener('change', scheduleSlide);
        else if (query.addListener) query.addListener(scheduleSlide);
    });
    scheduleSlide();
})();
