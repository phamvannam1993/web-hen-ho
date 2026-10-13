(function () {
    'use strict';
    document.querySelectorAll('[data-member-feed]').forEach(function (feed) {
        var grid = feed.previousElementSibling;
        var link = feed.querySelector('[data-feed-next]');
        var status = feed.querySelector('[data-feed-status]');
        var next = link.href;
        var busy = false;
        var failed = false;
        var seen = new Set();
        grid.querySelectorAll('[data-user]').forEach(function (card) { seen.add(card.dataset.user); });
        var observer = 'IntersectionObserver' in window ? new IntersectionObserver(function (entries) {
            if (entries.some(function (entry) { return entry.isIntersecting; }) && !failed) load();
        }, { rootMargin: '0px', threshold: 0.01 }) : null;

        async function load() {
            if (busy || !next) return;
            busy = true;
            failed = false;
            feed.classList.add('is-loading');
            link.hidden = true;
            grid.setAttribute('aria-busy', 'true');
            status.textContent = 'Đang tải thêm hồ sơ…';
            try {
                var url = new URL(next);
                url.searchParams.set('member_feed', '1');
                var response = await fetch(url, { credentials: 'same-origin' });
                if (!response.ok) throw new Error('Load failed');
                var data = await response.json();
                if (typeof data.html !== 'string' || !(data.next === null || typeof data.next === 'string')) {
                    throw new Error('Invalid response');
                }
                var template = document.createElement('template');
                template.innerHTML = data.html;
                template.content.querySelectorAll('[data-user]').forEach(function (card) {
                    if (seen.has(card.dataset.user)) card.remove();
                    else seen.add(card.dataset.user);
                });
                grid.appendChild(template.content);
                next = data.next;
                status.textContent = next ? '' : 'Bạn đã xem hết hồ sơ phù hợp.';
                if (next) {
                    link.href = next;
                    link.textContent = 'Xem thêm';
                    link.hidden = false;
                } else if (observer) observer.disconnect();
            } catch (error) {
                failed = true;
                status.textContent = 'Chưa tải được hồ sơ. Bạn hãy thử lại.';
                link.textContent = 'Thử lại';
                link.hidden = false;
            } finally {
                busy = false;
                feed.classList.remove('is-loading');
                grid.removeAttribute('aria-busy');
            }
            if (observer && next && !failed) {
                observer.unobserve(feed);
                observer.observe(feed);
            }
        }
        link.addEventListener('click', function (event) { event.preventDefault(); load(); });
        if (observer) observer.observe(feed);
    });
}());
