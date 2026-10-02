<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Khung chung của khu Tài khoản — dựng theo `src/routes/tai-khoan.tsx` của bản
 * thiết kế SaigonCupid, đặt GIỮA đầu trang và chân trang của site (logo, chuông,
 * đăng xuất đã có trên đầu trang nên không lặp lại ở đây).
 *
 *  - Máy tính: cột trái dính ngay dưới đầu trang, thu gọn được còn biểu tượng
 *    (nhớ lựa chọn trong localStorage).
 *  - Điện thoại: dải "Menu tài khoản" dưới đầu trang, cột trái trượt ra thành
 *    ngăn kéo, và thanh điều hướng 5 mục dính dưới đáy.
 *
 * Cùng MỘT thẻ <aside> vừa là cột trái vừa là ngăn kéo — CSS đổi vị trí theo
 * bề rộng màn hình, khỏi phải in danh sách mục hai lần.
 *
 * Layout chính nạp tệp này thay cho đầu/chân trang của site, rồi tệp này nạp
 * view của từng trang vào cột nội dung.
 */
$me      = $tk['me'];
$current = (string) $this->uri->segment(2);
// Trang sửa tin thuộc mục "Tin đăng của tôi"
if ($current === 'sua-tin') {
    $current = 'tin-dang';
}

/* Nhóm mục. Khoá mảng là slug sau `tai-khoan/`; chuỗi rỗng là trang Tổng quan.
   Phần tử thứ ba (nếu có) là khoá đếm trong $tk — chỉ hiện khi lớn hơn 0. */
$groups = array(
    'Hồ sơ cá nhân' => array(
        ''      => array('Tổng quan',     'dashboard'),
        'ho-so' => array('Hồ sơ của tôi', 'user'),
        'anh'   => array('Ảnh của tôi',   'images'),
    ),
    'Kết nối' => array(
        'quan-tam'     => array('Quan tâm & ghép đôi', 'match'),
        'ai-thich-ban' => array('Ai đã thích bạn',     'heart',   'liked'),
        'ai-xem-ho-so' => array('Ai đã xem hồ sơ',     'eye'),
        'tin-nhan'     => array('Tin nhắn',            'message', 'msg'),
    ),
    'Hoạt động' => array(
        'chuoi'     => array('Chuỗi hoạt động', 'flame'),
        'goi-y'     => array('Lịch sử gợi ý',   'sparkles'),
        'thong-bao' => array('Thông báo',       'bell', 'noti'),
    ),
    'Ví & VIP' => array(
        'nap-xu' => array('Nạp xu / VIP', 'wallet'),
    ),
    'Cài đặt' => array(
        'email'        => array('Cài đặt email', 'mail'),
        'doi-mat-khau' => array('Đổi mật khẩu',  'key'),
    ),
);

// Tin đăng chỉ hiện khi Cấu hình bật lại tính năng — giữ đúng luật của bản cũ
if (!empty($settings['enable_posts'])) {
    $groups['Hồ sơ cá nhân']['tin-dang'] = array('Tin đăng của tôi', 'file');
}

/* Thanh dưới trên điện thoại. "Khám phá" dẫn tới trang vuốt hồ sơ thật của site. */
$bottom = array(
    array(site_url('tai-khoan'),          'Tổng quan', 'dashboard', $current === ''),
    array(site_url('swipe-match'),        'Khám phá',  'match',     false),
    array(site_url('tai-khoan/quan-tam'), 'Ghép đôi',  'sparkles',  $current === 'quan-tam'),
    array(site_url('tai-khoan/tin-nhan'), 'Tin nhắn',  'message',   $current === 'tin-nhan', $tk['msg']),
    array(site_url('tai-khoan/ho-so'),    'Tài khoản', 'user',      $current === 'ho-so'),
);

$so_xu    = number_format((int) ($me['coin_balance'] ?? 0));
$la_vip   = !empty($me['is_vip']) && (empty($me['vip_expired_at']) || strtotime($me['vip_expired_at']) > time());
$dem      = function ($n) { return $n > 99 ? '99+' : (int) $n; };

// Tên trang đang xem, in trên dải menu của điện thoại
$trang_nay = 'Tổng quan';
foreach ($groups as $items) {
    if (isset($items[$current])) { $trang_nay = $items[$current][0]; }
}
?>
<div class="tk-layout">
    <!-- Dải mở menu tài khoản — chỉ hiện trên điện thoại -->
    <div class="tk-mhead">
        <button type="button" class="tk-mhead__menu" data-tk-drawer="open"
                aria-controls="tk-side" aria-expanded="false">
            <?= tk_icon('menu') ?>
            <span class="tk-truncate"><span class="tk-mhead__k">Tài khoản ·</span> <?= e($trang_nay) ?></span>
        </button>
        <a class="tk-mhead__coin" href="<?= site_url('tai-khoan/nap-xu') ?>"><?= tk_icon('coins') ?><?= $so_xu ?> xu</a>
    </div>

    <div class="tk-overlay" data-tk-drawer="close" hidden></div>

    <aside class="tk-side" id="tk-side" aria-label="Menu tài khoản">
        <button type="button" class="tk-btn tk-btn--ghost tk-btn--icon-sm tk-side__close" data-tk-drawer="close" aria-label="Đóng menu">
            <?= tk_icon('x') ?>
        </button>
        <button type="button" class="tk-btn tk-btn--outline tk-btn--icon-sm tk-side__collapse" data-tk-collapse
                aria-label="Thu gọn menu" title="Thu gọn menu">
            <?= tk_icon('panel-close', 'tk-ic tk-ic--close') ?><?= tk_icon('panel-open', 'tk-ic tk-ic--open') ?>
        </button>

        <div class="tk-side__in">
            <div class="tk-me">
                <div class="tk-me__row">
                    <img class="tk-me__av" src="<?= e(avatar_url($me['avatar'] ?? null, $me['gender'] ?? 'other')) ?>"
                         alt="<?= e(display_name($me)) ?>" width="40" height="40" loading="lazy">
                    <div class="tk-me__b tk-side__txt">
                        <p class="tk-me__name"><?= e(display_name($me)) ?></p>
                        <?php if ($la_vip): ?>
                            <span class="tk-pill tk-pill--gold"><?= tk_icon('crown') ?>VIP</span>
                        <?php else: ?>
                            <span class="tk-pill">Thành viên thường</span>
                        <?php endif; ?>
                    </div>
                </div>
                <a class="tk-me__bal tk-side__txt" href="<?= site_url('tai-khoan/nap-xu') ?>">
                    <span class="tk-me__bal-l"><?= tk_icon('coins') ?>Số dư</span>
                    <strong><?= $so_xu ?> xu</strong>
                </a>
            </div>

            <nav class="tk-nav">
                <?php foreach ($groups as $nhom => $items): ?>
                    <div class="tk-nav__group">
                        <p class="tk-nav__label"><?= e($nhom) ?></p>
                        <ul>
                            <?php foreach ($items as $slug => $it): ?>
                                <?php
                                $active = $current === $slug;
                                $so     = isset($it[2]) ? $tk[$it[2]] : 0;
                                ?>
                                <li>
                                    <a class="tk-nav__a<?= $active ? ' is-active' : '' ?>" title="<?= e($it[0]) ?>"
                                       <?= $active ? 'aria-current="page"' : '' ?>
                                       href="<?= site_url('tai-khoan' . ($slug !== '' ? '/' . $slug : '')) ?>">
                                        <?= tk_icon($it[1]) ?>
                                        <span class="tk-nav__txt"><?= e($it[0]) ?></span>
                                        <?php if ($so > 0): ?><span class="tk-count"><?= $dem($so) ?></span><?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>

            </nav>
        </div>
    </aside>

    <main class="tk-main">
        <div class="tk-main__in">
            <?php if (!empty($flash)): ?>
                <div class="tk-alert tk-alert--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?php $this->load->view($content_view); ?>
        </div>
    </main>

    <!-- Thanh dưới — chỉ hiện trên điện thoại -->
    <nav class="tk-bnav" aria-label="Điều hướng nhanh">
        <ul>
            <?php foreach ($bottom as $b): ?>
                <li>
                    <a class="<?= $b[3] ? 'is-active' : '' ?>" href="<?= $b[0] ?>" <?= $b[3] ? 'aria-current="page"' : '' ?>>
                        <?= tk_icon($b[2]) ?>
                        <span><?= e($b[1]) ?></span>
                        <?php if (!empty($b[4])): ?><span class="tk-dot-count"><?= $dem($b[4]) ?></span><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</div>

<script>
/* Ngăn kéo trên điện thoại + thu gọn cột trái trên máy tính */
(function () {
    var body = document.body, side = document.getElementById('tk-side');
    var overlay = document.querySelector('.tk-overlay');
    var opener = document.querySelector('.tk-mhead [data-tk-drawer="open"]');

    function drawer(open) {
        body.classList.toggle('tk-drawer-open', open);
        overlay.hidden = !open;
        if (opener) opener.setAttribute('aria-expanded', open ? 'true' : 'false');
        // Đợi một nhịp cho ngăn kéo hết `visibility: hidden` rồi mới chuyển tiêu điểm vào
        if (open) { setTimeout(function () { var c = side.querySelector('.tk-side__close'); if (c) c.focus(); }, 30); }
        else if (opener) opener.focus();
    }
    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-tk-drawer]');
        if (t) drawer(t.getAttribute('data-tk-drawer') === 'open');
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && body.classList.contains('tk-drawer-open')) drawer(false);
    });

    // Cột trái và dải menu dính ngay dưới đầu trang của site, mà đầu trang cao
    // khác nhau theo bề rộng màn hình — đo lại mỗi khi đổi cỡ.
    var head = document.querySelector('.site-header');
    function doCao() {
        body.style.setProperty('--tk-head', (head && getComputedStyle(head).position !== 'static' ? head.offsetHeight : 0) + 'px');
    }
    doCao();
    window.addEventListener('resize', doCao);

    var KEY = 'tk-side-collapsed', btn = document.querySelector('[data-tk-collapse]');
    function collapse(on) {
        body.classList.toggle('tk-collapsed', on);
        var nhan = on ? 'Mở rộng menu' : 'Thu gọn menu';
        btn.setAttribute('aria-label', nhan); btn.title = nhan;
    }
    try { collapse(localStorage.getItem(KEY) === '1'); } catch (e) {}
    btn.addEventListener('click', function () {
        var on = !body.classList.contains('tk-collapsed');
        collapse(on);
        try { localStorage.setItem(KEY, on ? '1' : '0'); } catch (e) {}
    });
})();
</script>
