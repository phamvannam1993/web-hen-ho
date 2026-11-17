<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Quan tâm & ghép đôi — dựng theo `src/routes/tai-khoan.quan-tam.tsx`.
 *
 * Các thẻ chỉ hiển thị thông tin; chạm vào thẻ để mở hồ sơ và tương tác.
 *
 * Bốn danh sách chia thành bốn thẻ. Cả bốn đều in sẵn từ máy chủ, thẻ đang mở
 * lấy từ ?tab= nên dán link hay tải lại vẫn đúng thẻ; JS bên dưới chỉ đổi
 * thẻ tại chỗ cho khỏi tải lại trang.
 */
$tabs = array(
    'ban-thich' => array('Bạn đã thích',    $interest_totals['ban-thich'], 'Bạn thích'),
    'thich-ban' => array('Người thích bạn', $interest_totals['thich-ban'], 'Thích bạn'),
    'ghep-doi'  => array('Đã ghép đôi',     $interest_totals['ghep-doi'],  'Ghép đôi'),
    'da-xem'    => array('Đã xem hồ sơ',    $interest_totals['da-xem'],    'Đã xem'),
);
$tab = (string) $this->input->get('tab');
if (!isset($tabs[$tab])) { $tab = 'ban-thich'; }

// Render all interest lists with the shared dating card.
$luoi = function ($list, $type) {
    echo '<div class="dating-page tk-lk-dating"><div class="member-grid">';
    foreach ($list as $member) {
        $this->load->view('members/_card', array(
            'm' => $member,
            'dating_card' => true,
            'hide_profile_price' => true,
            'hide_actions' => true,
            'interest_card_type' => $type,
        ));
    }
    echo '</div></div>';
};
?>
<link rel="stylesheet" href="<?= base_url('assets/site/css/dating-discovery.css') ?>?v=<?= filemtime(FCPATH.'assets/site/css/dating-discovery.css') ?>">
<header class="tk-ph">
    <div class="tk-ph__b">
        <h1>Quan tâm &amp; ghép đôi</h1>
        <p>Bạn có thể nhắn tin ngay từ hồ sơ để làm quen.</p>
    </div>
    <div class="tk-ph__act">
        <a class="tk-btn tk-btn--brand" href="<?= site_url('tai-khoan/goi-y') ?>"><?= tk_icon('sparkles') ?>Gợi ý hôm nay</a>
    </div>
</header>

<nav class="tk-tabs tk-lk-tabs" role="tablist" aria-label="Danh sách quan tâm và ghép đôi" data-lk-tabs>
    <?php foreach ($tabs as $k => $t): ?>
        <?php $unread = (int) ($tk['interest_tabs'][$k] ?? 0); ?>
        <a href="?tab=<?= $k ?>" role="tab" id="tk-lk-tab-<?= $k ?>" data-tab="<?= $k ?>"
           aria-controls="tk-lk-<?= $k ?>" aria-selected="<?= $k === $tab ? 'true' : 'false' ?>"
           title="<?= e($t[0]) ?>"
           class="<?= $k === $tab ? 'is-active' : '' ?><?= $unread ? ' has-unread' : '' ?>"><span class="tk-lk-tab-label"><?= e($t[0]) ?></span><span class="tk-lk-tab-short"><?= e($t[2]) ?></span><span class="tk-tabs__n" title="Tổng số hồ sơ"><?= $t[1] ?></span><span class="tk-tab-unread" data-interest-tab-count="<?= $k ?>" <?= $unread ? '' : 'hidden' ?> aria-label="<?= $unread ?> mục mới chưa xem" title="<?= $unread ?> mục mới chưa xem"><?= $unread > 99 ? '99+' : $unread ?> mới</span></a>
    <?php endforeach; ?>
</nav>

<!-- Người thích bạn -->
<div class="tk-lk-panel" id="tk-lk-thich-ban" role="tabpanel" aria-labelledby="tk-lk-tab-thich-ban"<?= $tab === 'thich-ban' ? '' : ' hidden' ?>>
    <?php if (empty($liked_me)): ?>
        <div class="tk-empty">
            <span class="tk-empty__ic"><?= tk_icon('heart') ?></span>
            <h3>Chưa có ai đang chờ bạn trả lời</h3>
            <p>Hồ sơ đầy đủ, có ảnh đẹp sẽ nhận được nhiều lượt thích hơn.</p>
            <div class="tk-empty__act"><a class="tk-btn tk-btn--brand" href="<?= site_url('tai-khoan/ho-so') ?>">Hoàn thiện hồ sơ</a></div>
        </div>
    <?php else: ?>
        <?php $luoi($liked_me, 'request'); ?>
    <?php endif; ?>
</div>

<!-- Đã ghép đôi -->
<div class="tk-lk-panel" id="tk-lk-ghep-doi" role="tabpanel" aria-labelledby="tk-lk-tab-ghep-doi"<?= $tab === 'ghep-doi' ? '' : ' hidden' ?>>
    <?php if (empty($matches)): ?>
        <div class="tk-empty">
            <span class="tk-empty__ic"><?= tk_icon('match') ?></span>
            <h3>Chưa có ghép đôi nào</h3>
            <p>Thích lại để thể hiện sự quan tâm, hoặc nhắn tin ngay từ hồ sơ để làm quen.</p>
            <div class="tk-empty__act"><a class="tk-btn tk-btn--brand" href="<?= site_url('swipe-match') ?>"><?= tk_icon('match') ?>Khám phá ngay</a></div>
        </div>
    <?php else: ?>
        <?php $luoi($matches, 'matched'); ?>
    <?php endif; ?>
</div>

<!-- Đã xem hồ sơ -->
<div class="tk-lk-panel" id="tk-lk-da-xem" role="tabpanel" aria-labelledby="tk-lk-tab-da-xem"<?= $tab === 'da-xem' ? '' : ' hidden' ?>>
    <?php if (empty($viewers)): ?>
        <div class="tk-empty">
            <span class="tk-empty__ic"><?= tk_icon('eye') ?></span>
            <h3>Chưa có ai ghé xem hồ sơ của bạn</h3>
            <p>Cập nhật ảnh đại diện và lời giới thiệu để hồ sơ nổi bật hơn.</p>
        </div>
    <?php else: ?>
        <?php $luoi($viewers, 'viewed'); ?>
    <?php endif; ?>
</div>

<!-- Bạn đã thích -->
<div class="tk-lk-panel" id="tk-lk-ban-thich" role="tabpanel" aria-labelledby="tk-lk-tab-ban-thich"<?= $tab === 'ban-thich' ? '' : ' hidden' ?>>
    <?php if (empty($my_likes)): ?>
        <div class="tk-empty">
            <span class="tk-empty__ic"><?= tk_icon('heart') ?></span>
            <h3>Bạn chưa thích ai</h3>
            <p>Hãy khám phá những hồ sơ phù hợp và thả tim cho người bạn thấy hợp gu.</p>
            <div class="tk-empty__act"><a class="tk-btn tk-btn--brand tk-btn--lg" href="<?= site_url('swipe-match') ?>"><?= tk_icon('match') ?>Khám phá ngay</a></div>
        </div>
    <?php else: ?>
        <?php $luoi($my_likes, 'liked'); ?>
    <?php endif; ?>
</div>

<script>
/* Đổi thẻ tại chỗ, ghi ?tab= lên thanh địa chỉ để dán link / tải lại vẫn đúng thẻ */
(function () {
    var nav = document.querySelector('[data-lk-tabs]');
    if (!nav) { return; }
    function mo(k, ghi) {
        var a = nav.querySelector('[data-tab="' + k + '"]');
        if (!a) { return; }
        nav.querySelectorAll('[data-tab]').forEach(function (t) {
            var on = t === a;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            document.getElementById('tk-lk-' + t.getAttribute('data-tab')).hidden = !on;
        });
        if (ghi && window.history.replaceState) {
            var u = new URL(window.location.href);
            u.searchParams.set('tab', k);
            history.replaceState(null, '', u);
        }
        if (ghi) {
            var body = new URLSearchParams({tab: k, token: <?= json_encode($interest_seen_token) ?>});
            body.set(<?= json_encode($this->security->get_csrf_token_name()) ?>, <?= json_encode($this->security->get_csrf_hash()) ?>);
            fetch(<?= json_encode(site_url('ajax/quan-tam/da-xem')) ?>, {method: 'POST', credentials: 'same-origin', body: body})
                .then(function (response) { return response.json(); })
                .then(function (result) {
                    if (result.ok) document.dispatchEvent(new CustomEvent('account-counts-changed'));
                }).catch(function () {});
        }
    }
    nav.addEventListener('click', function (e) {
        var a = e.target.closest('[data-tab]');
        if (!a || e.metaKey || e.ctrlKey || e.shiftKey) { return; }
        e.preventDefault();
        mo(a.getAttribute('data-tab'), true);
    });
})();
</script>
