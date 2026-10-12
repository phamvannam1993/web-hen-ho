<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Trang Thông báo — dựng theo `src/routes/tai-khoan.thong-bao.tsx`.
 *
 * Mở trang là controller đã đánh dấu tất cả đã đọc (danh sách lấy TRƯỚC khi
 * đánh dấu), nên tô nền những thông báo chưa đọc của lần ghé này.
 * Các thẻ lọc chạy ngay trên trang, không tải lại.
 */
$chua_doc = 0;
foreach ($notifications as $n) {
    if (!$n['read_at']) $chua_doc++;
}

/** Nhóm của thẻ lọc: ghép đôi / tin nhắn / còn lại. */
$nhom = function ($type) {
    if ($type === 'match') return 'match';
    if ($type === 'message' || $type === 'comment') return 'message';
    return 'other';
};

/** Đường dẫn trong thông báo lưu dạng tương đối ("tai-khoan/tin-nhan") — đổi sang tuyệt đối như notifications.js. */
$link = function ($url) {
    if (!$url) return null;
    return preg_match('#^(https?:)?//#', $url) || $url[0] === '/' ? $url : site_url(preg_replace('#^\.?/#', '', $url));
};
?>
<div class="tk-ph">
    <div class="tk-ph__b">
        <h1>Thông báo</h1>
        <p>Nhấn vào thông báo để đi đến trang liên quan.</p>
    </div>
</div>

<?php if (empty($notifications)): ?>
    <div class="tk-empty">
        <span class="tk-empty__ic"><?= tk_icon('bell') ?></span>
        <h3>Không có thông báo nào</h3>
        <p>Khi có người thích bạn hoặc gửi tin nhắn, thông báo sẽ xuất hiện ở đây.</p>
    </div>
<?php else: ?>
    <div class="tk-tabs" id="tk-tb-tabs" role="tablist">
        <button type="button" class="is-active" data-f="all">Tất cả<span class="tk-tabs__n"><?= count($notifications) ?></span></button>
        <button type="button" data-f="unread">Chưa đọc<span class="tk-tabs__n"><?= $chua_doc ?></span></button>
        <button type="button" data-f="match">Ghép đôi</button>
        <button type="button" data-f="message">Tin nhắn</button>
        <button type="button" data-f="other">Hoạt động khác</button>
    </div>

    <section class="tk-card tk-card--flush" id="tk-tb-card">
        <ul class="tk-tb-list" id="tk-tb-list">
            <?php foreach ($notifications as $n): ?>
                <?php $url = $link($n['url']); $tag = $url ? 'a' : 'div'; ?>
                <li data-g="<?= $nhom($n['type']) ?>"<?= $n['read_at'] ? '' : ' data-unread' ?>>
                    <<?= $tag ?> class="tk-tb-item<?= $n['read_at'] ? '' : ' is-unread' ?>"<?= $url ? ' href="' . e($url) . '"' : '' ?>>
                        <span class="tk-tb-ic<?= !empty($n['actor']) ? ' has-avatar' : '' ?>">
                            <?php if (!empty($n['actor'])): ?>
                                <img src="<?= e($n['actor']['avatar']) ?>" alt="<?= e($n['actor']['name']) ?>">
                                <span class="tk-tb-type"><?= tk_icon(tk_noti_icon($n['type'])) ?></span>
                            <?php else: ?>
                                <?= tk_icon(tk_noti_icon($n['type'])) ?>
                            <?php endif; ?>
                        </span>
                        <span class="tk-tb-b">
                            <span class="tk-tb-t"><?= e($n['title']) ?></span>
                            <?php if ($n['body']): ?><span class="tk-tb-d"><?= e($n['body']) ?></span><?php endif; ?>
                            <span class="tk-tb-time"><?= time_ago($n['created_at']) ?></span>
                        </span>
                        <?php if (!$n['read_at']): ?><span class="tk-tb-dot" aria-label="Chưa đọc"></span><?php endif; ?>
                    </<?= $tag ?>>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <div class="tk-empty" id="tk-tb-none" hidden>
        <span class="tk-empty__ic"><?= tk_icon('bell') ?></span>
        <h3>Không có thông báo nào</h3>
        <p>Khi có người thích bạn hoặc gửi tin nhắn, thông báo sẽ xuất hiện ở đây.</p>
    </div>

    <script>
    /* Lọc theo thẻ ngay trên trang */
    (function () {
        var tabs = document.getElementById('tk-tb-tabs');
        var card = document.getElementById('tk-tb-card');
        var none = document.getElementById('tk-tb-none');
        if (!tabs) return;
        tabs.addEventListener('click', function (e) {
            var t = e.target.closest('[data-f]');
            if (!t) return;
            tabs.querySelectorAll('[data-f]').forEach(function (b) { b.classList.toggle('is-active', b === t); });
            var f = t.getAttribute('data-f'), con = 0;
            card.querySelectorAll('li').forEach(function (li) {
                var ok = f === 'all' || (f === 'unread' ? li.hasAttribute('data-unread') : li.getAttribute('data-g') === f);
                li.hidden = !ok;
                if (ok) con++;
            });
            card.hidden = con === 0;
            none.hidden = con > 0;
        });
    })();
    </script>
<?php endif; ?>
