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
    var buttons = banner.querySelectorAll('[data-home-slide-to]');
    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            var next = Number(button.dataset.homeSlideTo);
            slides.forEach(function (slide, index) { slide.hidden = index !== next; });
            buttons.forEach(function (dot, index) { dot.setAttribute('aria-pressed', String(index === next)); });
        });
    });
})();
