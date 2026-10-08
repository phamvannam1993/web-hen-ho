<?php defined('BASEPATH') OR exit('No direct script access allowed');
$flash = $this->session->flashdata('flash');
?><!DOCTYPE html>
<html lang="vi">
<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-NXYG6XSVEK"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-NXYG6XSVEK');
</script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
/*
 * Tiêu đề trang: nếu controller đặt sẵn $meta_title thì dùng nguyên văn,
 * ngược lại ghép "Tên trang - Tên website".
 */
$site_name = $settings['site_name'] ?? 'Saigon Cupid';
$page_title = !empty($meta_title)
    ? $meta_title
    : (($title ?? 'Hẹn hò kết bạn') . ' - ' . $site_name);
?>
<meta name="description" content="<?= e($meta_desc ?? '') ?>">
<?php
$seo_path = trim(uri_string(), '/');
$can_index = seo_indexable($seo_path);
$canonical_url = site_url($seo_path);
if (preg_match('~/trang/([0-9]+)$~', $seo_path, $match)) {
    $page_title .= ' - Trang ' . (int) $match[1];
}
$this->load->view('layouts/seo', array('seo_path' => $seo_path, 'can_index' => $can_index,
    'canonical_url' => $canonical_url, 'page_title' => $page_title, 'site_name' => $site_name,
    'meta_desc' => $meta_desc ?? '', 'title' => $title ?? $site_name,
    'settings' => $settings, 'article' => $article ?? null));
?>
<link rel="stylesheet" href="<?= base_url('assets/site/css/style.css') ?>?v=<?= @filemtime(FCPATH.'assets/site/css/style.css') ?>">
<link rel="icon" type="image/x-icon" href="<?= base_url('assets/site/img/favicon.ico?v=1232312131') ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/site/img/apple-touch-icon.png?V=1243324243') ?>">

<?php
/* Khu Tài khoản bọc nội dung trong khung riêng (cột trái + thanh dưới trên điện
   thoại) theo bản thiết kế SaigonCupid, nằm giữa đầu trang và chân trang của site. */
$tk_app = !empty($tk) && !empty($content_view) && strpos($content_view, 'account/') === 0;
?>
<?php if ($tk_app): ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/site/css/account.css') ?>?v=<?= @filemtime(FCPATH.'assets/site/css/account.css') ?>">
<?php /* Phần riêng của từng trang (nếu có): assets/site/css/account/<tên view>.css */ ?>
<?php $tk_css = 'assets/site/css/account/' . basename($content_view) . '.css'; ?>
<?php if (is_file(FCPATH . $tk_css)): ?>
<link rel="stylesheet" href="<?= base_url($tk_css) ?>?v=<?= @filemtime(FCPATH . $tk_css) ?>">
<?php endif; ?>
<?php endif; ?>
<link rel="stylesheet" href="<?= base_url('assets/site/css/mobile-nav.css') ?>?v=<?= @filemtime(FCPATH.'assets/site/css/mobile-nav.css') ?>">
</head>
<body class="<?= !empty($bare) ? 'is-bare' : '' ?><?= $tk_app ? 'tk-app' : '' ?><?= $content_view === 'account/messages' ? ' is-messages-page' : '' ?>">

<!-- Dải mảnh trên cùng: khẩu hiệu + hotline -->
<?php /* Chế độ toàn màn hình (trang Khám phá): bỏ thanh trên, menu và chân trang */ ?>
<?php if (empty($bare)): ?>
<?php
/* Thanh trên cùng: liên hệ bên trái, trang tĩnh và mạng xã hội bên phải.
   Mục nào chưa khai trong Quản trị -> Cấu hình thì tự ẩn đi. */
$mxh = array();
foreach (array('facebook', 'instagram', 'youtube', 'tiktok') as $social_name) {
    $social_url = trim($settings[$social_name . '_url'] ?? '');
    if ($social_url === '') continue;
    if (strpos($social_url, '//') === 0) $social_url = 'https:' . $social_url;
    elseif (!preg_match('~^[a-z][a-z0-9+.-]*:~i', $social_url)) $social_url = 'https://' . $social_url;
    if (filter_var($social_url, FILTER_VALIDATE_URL)
        && in_array(strtolower(parse_url($social_url, PHP_URL_SCHEME) ?? ''), array('http', 'https'), true)) {
        $mxh[$social_name] = $social_url;
    }
}
?>
<div class="topbar">
    <div class="container topbar-inner">
        <div class="topbar-contact">
            <?php if (!empty($settings['hotline'])): ?>
                <a class="topbar-item" href="tel:<?= e(preg_replace('/\D+/', '', $settings['hotline'])) ?>">
                    <svg viewBox="0 0 24 24" class="ic" aria-hidden="true">
                        <path d="M6.5 3.5h3l1.5 4-2 1.4a12 12 0 0 0 6.1 6.1l1.4-2 4 1.5v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4.5 5.7a2 2 0 0 1 2-2.2z"/>
                    </svg>
                    Hỗ trợ: <b><?= e($settings['hotline']) ?></b>
                </a>
            <?php endif; ?>
            <?php if (!empty($settings['contact_email'])): ?>
                <a class="topbar-item" href="mailto:<?= e($settings['contact_email']) ?>">
                    <svg viewBox="0 0 24 24" class="ic" aria-hidden="true">
                        <rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="m3.8 6.8 8.2 5.7 8.2-5.7"/>
                    </svg>
                    <?= e($settings['contact_email']) ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="topbar-right">
            <a href="<?= site_url('dieu-khoan') ?>">Điều khoản</a>
            <a href="<?= site_url('noi-quy') ?>">Nội quy</a>
            <a href="<?= site_url('lien-he') ?>">Liên hệ</a>
            <?php foreach ($mxh as $ten => $url): ?>
                <a class="topbar-social" href="<?= e($url) ?>" target="_blank" rel="noopener"
                   aria-label="<?= e(ucfirst($ten)) ?>">
                    <img src="<?= base_url('assets/images/' . ($ten === 'instagram' ? 'insta' : $ten) . '.png') ?>"
                         alt="<?= e(ucfirst($ten)) ?>" loading="lazy">
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Thanh chính: logo - menu - nút hành động, dính khi cuộn -->
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= site_url() ?>">
    <img src="/assets/images/logo.png" alt="<?= e($settings['site_name'] ?? 'Saigon Cupid') ?>" class="brand-logo">
    <span class="brand-text"><?= e($settings['site_name'] ?? 'Saigon Cupid') ?></span>
</a>

        <?php /* Ô tìm kiếm gọn ở giữa hàng trên, lấp khoảng trống giữa logo và nhóm nút.
                 Chỉ hiện trên màn rộng; màn hẹp dùng bộ lọc trong trang Thành viên. */ ?>
        <form id="header-search" class="header-search" method="get" action="<?= site_url('tim-kiem') ?>" role="search">
            <svg viewBox="0 0 24 24" class="ic" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/>
            </svg>
            <input type="text" name="q" value="<?= e($this->input->get('q')) ?>"
                   placeholder="Tìm theo tên, khu vực hoặc nghề nghiệp..." aria-label="Tìm thành viên">
            <button class="header-search-submit" type="submit">Tìm</button>
        </form>

        <?php /* Nhóm nút cạnh nút mở menu: tìm kiếm (chỉ mobile) + chuông thông báo.
                 Chuông ĐƯỢC CHUYỂN RA ĐÂY khỏi .nav-drawer. Trước nó nằm trong ngăn
                 kéo, mà trên điện thoại ngăn kéo là panel trượt ra — nên người dùng
                 không thấy chuông cho tới khi mở menu.
                 Chuyển hẳn chứ không nhân bản: hai khối cùng id (#noti-toggle,
                 #noti-panel) thì JS bắt nhầm phần tử, khay không xổ được. */ ?>
        <div class="hd-mini">
            <button type="button" id="header-search-toggle" class="hd-mini-btn" aria-label="Tìm kiếm" aria-controls="header-search" aria-expanded="false">
                <svg viewBox="0 0 24 24" class="ic" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/>
                </svg>
            </button>
            <?php /* Chuỗi ngày hoạt động: chỉ hiện khi đã có chuỗi, để không
                     làm rối thanh đầu trang với con số 0.
                     Trên điện thoại ô này ẩn đi và hiện lại TRONG ngăn kéo menu
                     (xem `.hd-streak--head` / `--drawer` ở style.css): thanh đầu
                     trang ở khổ hẹp đã chật vì logo, tìm kiếm, chuông và nút menu. */ ?>
            <?php $this->load->view('layouts/_streak_pill', array('tk_lop' => 'hd-streak--head')); ?>

            <?php /* Chuông thông báo + khay xổ xuống; chỉ có nghĩa khi đã đăng nhập */ ?>
            <?php if ($user): ?>
                <div class="hd-noti" id="hd-noti" data-base="<?= site_url() ?>">
                    <button type="button" class="hd-bell" id="noti-toggle"
                            aria-label="Thông báo" aria-expanded="false" aria-haspopup="dialog">
                        <svg viewBox="0 0 24 24" class="ic" aria-hidden="true">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                            <path d="M10 21a2 2 0 0 0 4 0"/>
                        </svg>
                        <span class="hd-bell-badge" id="noti-badge"
                              <?= empty($unread_noti) ? 'hidden' : '' ?>><?= $unread_noti > 99 ? '99+' : (int) $unread_noti ?></span>
                    </button>

                    <div class="noti-panel" id="noti-panel" role="dialog" aria-label="Thông báo" hidden>
                        <header class="noti-head">
                            <h3>Thông báo</h3>
                            <button type="button" class="noti-readall" id="noti-readall">Đánh dấu đã đọc</button>
                            <button type="button" class="noti-close" id="noti-close" aria-label="Đóng">&times;</button>
                        </header>
                        <div class="noti-list" id="noti-list">
                            <p class="noti-empty">Đang tải…</p>
                        </div>
                        <footer class="noti-foot">
                            <a href="<?= site_url('tai-khoan/thong-bao') ?>">Xem tất cả thông báo</a>
                        </footer>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <button class="nav-toggle" type="button" id="nav-toggle"
                aria-label="Mở menu" aria-expanded="false" aria-controls="nav-drawer">
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
        </button>

        <div class="nav-drawer" id="nav-drawer">
        <div class="drawer-head">
            <span class="drawer-title">Menu</span>
            <button type="button" class="drawer-close" id="drawer-close" aria-label="Đóng menu">&times;</button>
        </div>

        <?php /* Bản dành cho điện thoại của ô chuỗi — chỉ hiện khi ngăn kéo là
                 panel trượt (≤900px). Cùng partial với bản ở thanh đầu trang. */ ?>
        <?php $this->load->view('layouts/_streak_pill', array('tk_lop' => 'hd-streak--drawer', 'tk_nhan' => true)); ?>

        <?php
        // Đánh dấu mục đang xem theo đường dẫn hiện tại.
        // 'match' liệt kê các nhánh URL cùng thuộc một mục, ví dụ trang cá nhân
        // /profile/... vẫn tính là đang ở mục Thành viên.
        $nav_items = array(
            array('url' => '',              'label' => 'Trang chủ',  'match' => array('')),
            array('url' => 'hen-ho',        'label' => 'Hẹn hò',     'match' => array('hen-ho')),
            array('url' => 'tam-su',        'label' => 'Tâm sự',     'match' => array('tam-su')),
            array('url' => 'thanh-vien',    'label' => 'Thành viên', 'match' => array('thanh-vien', 'profile', 'tim-kiem')),
            array('url' => 'swipe-match',      'label' => 'Ghép đôi ẩn',   'match' => array('swipe-match')),
            array('url' => 'khu-vuc',       'label' => 'Khu vực',    'match' => array('khu-vuc')),
            array('url' => 'tin-tuc',       'label' => 'Sforum',   'match' => array('tin-tuc')),
            array('url' => 'noi-quy',       'label' => 'Thông báo',    'match' => array('noi-quy', 'trang')),
        );
        $seg1 = (string) $this->uri->segment(1);
        // Trang tỉnh nay nằm ở gốc (/ha-noi) nên phải đối chiếu với danh mục tỉnh
        // để mục "Khu vực" vẫn sáng khi đang xem một tỉnh.
        if ($seg1 !== '' && in_array($seg1, array_column($provinces, 'slug'), true)) {
            $seg1 = 'khu-vuc';
        }
        ?>
        <ul class="nav-list">
            <?php foreach ($nav_items as $item): ?>
                <?php $is_active = in_array($seg1, $item['match'], true); ?>
                <li><a<?= $is_active ? ' class="active" aria-current="page"' : '' ?>
                       href="<?= site_url($item['url']) ?>"><?= $item['label'] ?></a></li>
            <?php endforeach; ?>
        </ul>

        <div class="header-actions">
            <?php if ($user): ?>
                <a class="btn-account" href="<?= site_url('tai-khoan') ?>">
                    <img src="<?= avatar_url($user['avatar'], $user['gender']) ?>" alt="">
                    <span><?= e(display_name($user)) ?></span>
                    <span class="account-total-badge" data-account-total-count <?= empty($account_badge_total) ? 'hidden' : '' ?> aria-label="<?= (int) ($account_badge_total ?? 0) ?> thông báo trong tài khoản"><?= ($account_badge_total ?? 0) > 99 ? '99+' : (int) ($account_badge_total ?? 0) ?></span>
                </a>
                <a class="btn-nav-ghost" href="<?= site_url('dang-xuat') ?>">Đăng xuất</a>
            <?php else: ?>
                <a class="btn-nav-ghost" href="<?= site_url('dang-nhap') ?>">Đăng nhập</a>
                <a class="btn-nav-solid" href="<?= site_url('dang-ky') ?>">Đăng ký</a>
            <?php endif; ?>

        </div>
        </div><!-- /.nav-drawer -->

        <div class="nav-overlay" id="nav-overlay" hidden></div>
    </div>
</header>
<?php $this->load->view('layouts/_nudge'); ?>
<?php endif; ?>

<?php if ($tk_app): ?>
<?php /* Khung khu Tài khoản tự hiện thông báo flash bên trong cột nội dung */ ?>
<?php $this->load->view('account/_shell', array('flash' => $flash)); ?>
<?php else: ?>
<div class="container">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
</div>

<main<?= empty($bare) && $content_view !== 'home/index' ? ' class="site-page-content"' : '' ?>>
    <?php $this->load->view($content_view, array('user' => $user)); ?>
</main>
<?php endif; ?>

<?php if (empty($bare)): ?>
<footer class="site-footer">
    <div class="site-footer__container">
        <div class="site-footer__top">
            <div class="site-footer__brand"><?= e($settings['site_name'] ?? 'Saigon Cupid') ?></div>
            <div class="site-footer__top-right">
                <p class="site-footer__slogan"><?= e($settings['site_slogan'] ?? '') ?></p>
            </div>
        </div>

        <nav class="site-footer__policy-nav" aria-label="Liên kết chính sách">
            <a href="<?= site_url('gioi-thieu') ?>">Về chúng tôi</a>
            <a href="<?= site_url('noi-quy') ?>">Nội quy cộng đồng</a>
            <a href="<?= site_url('dieu-khoan') ?>">Điều khoản sử dụng</a>
            <a href="<?= site_url('an-toan') ?>">Hẹn hò an toàn</a>
            <a href="<?= site_url('bao-mat') ?>">Chính sách bảo mật</a>
            <a href="<?= site_url('lien-he') ?>">Liên hệ</a>
        </nav>

        <div class="site-footer__line"></div>

        <div class="site-footer__middle">
            <div class="site-footer__links">
                <div class="footer-col">
                    <h3 class="footer-col__title">Về chúng tôi</h3>
                    <ul class="footer-col__list">
                        <li><a href="<?= site_url('gioi-thieu') ?>">Giới thiệu</a></li>
                        <li><a href="#">Văn hóa doanh nghiệp</a></li>
                        <li><a href="#">Đội ngũ phát triển</a></li>
                        <li><a href="#">Phần thưởng</a></li>
                        <li><a href="#">Quảng cáo</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3 class="footer-col__title">Hướng dẫn chung</h3>
                    <ul class="footer-col__list">
                        <li><a href="<?= site_url('dang-ky') ?>">Cách tạo tài khoản</a></li>
                        <li><a href="<?= site_url('swipe-match') ?>">Hướng dẫn ghép đôi</a></li>
                        <li><a href="<?= site_url('thanh-vien') ?>">Cộng đồng thành viên</a></li>
                        <li><a href="<?= site_url('noi-quy') ?>">Thông báo cộng đồng</a></li>
                        <li><a href="#">Download Apps</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3 class="footer-col__title">Khu vực nổi bật</h3>
                    <ul class="footer-col__list">
                        <?php foreach (array_slice($provinces, 0, 5) as $p): ?>
                            <li><a href="<?= site_url($p['slug']) ?>"><?= e($p['name']) ?></a></li>
                        <?php endforeach; ?>
                        
                    </ul>
                </div>

                <div class="footer-col">
                    <h3 class="footer-col__title">Kết nối chúng tôi</h3>
                    <ul class="footer-col__list footer-col__list--social">
                        <?php foreach ($mxh as $name => $url): ?>
                        <li><a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><img class="footer-social-icon" src="<?= base_url('assets/images/' . ($name === 'instagram' ? 'insta' : $name) . '.png') ?>" width="20" height="20" alt="" aria-hidden="true" loading="lazy"><?= e(ucfirst($name)) ?></a></li>
                        <?php endforeach; ?>
                        
                        
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="site-footer__line"></div>

    <div class="site-footer__company">
        <div class="company-info-block">
            <address class="site-footer__entity-address">
                <h3 class="site-footer__company-title"><?= e($settings['company_name'] ?? ($settings['site_name'] ?? 'Saigon Cupid')) ?></h3>
                <ul class="site-footer__company-list">
                    <?php if (!empty($settings['tax_code'])): ?>
                        <li>Mã số thuế: <?= e($settings['tax_code']) ?></li>
                    <?php endif; ?>
                    <?php if (!empty($settings['contact_email'])): ?>
                        <li>Email: <a href="mailto:<?= e($settings['contact_email']) ?>"><?= e($settings['contact_email']) ?></a></li>
                    <?php endif; ?>
                    <?php if (!empty($settings['hotline'])): ?>
                        <li><a href="tel:<?= e(preg_replace('/\s+/', '', $settings['hotline'])) ?>">Hotline: <?= e($settings['hotline']) ?></a></li>
                    <?php endif; ?>
                    <?php if (!empty($settings['zalo'])): ?>
                        <li>Zalo: <?= e($settings['zalo']) ?></li>
                    <?php endif; ?>
                    <?php if (!empty($settings['address'])): ?>
                        <li>Địa chỉ: <?= e($settings['address']) ?></li>
                    <?php endif; ?>
                </ul>
            </address>
        </div>

        
    </div>

    <div class="site-footer__line"></div>

    <div class="site-footer__bottom">
        <p>Copyright ©<?= date('Y') ?> <?= e(($settings['site_name'] ?? 'Saigon Cupid')) ?>. All Rights Reserved.</p>
    </div>

</footer>
<?php endif; ?>

<!-- Chat nổi: khách xem được phòng chung, muốn gửi thì phải đăng nhập.
     Thành viên chưa khai xong hồ sơ cũng chỉ được xem như khách. -->
<div id="chat-widget" class="chat-widget" data-base="<?= site_url() ?>"
     data-ws-url="<?= e($ws_url ?? '') ?>" data-ws-token="<?= e($ws_token ?? '') ?>"
     data-guest="<?= $user ? '0' : '1' ?>"
     data-need-verify="<?= !empty($chua_xac_thuc) ? '1' : '0' ?>">
    <button type="button" class="cw-tab" id="cw-bubble" aria-label="Mở Chat" aria-expanded="false" aria-controls="cw-panel">
        <span class="cw-tab-arrow" aria-hidden="true">&laquo;</span>
        <span class="cw-tab-label">Chat</span>
        <span class="cw-tab-online" id="cw-tab-online" hidden><i class="cw-tab-dot"></i><b id="cw-tab-count"></b></span>
        <span class="cw-tab-icons" aria-hidden="true">
            <svg viewBox="0 0 40 32" class="cw-tab-bubbles">
                <path class="cw-b-back" d="M23 8h11a5 5 0 0 1 5 5v6a5 5 0 0 1-5 5h-2v5l-5.5-5H23a5 5 0 0 1-5-5v-6a5 5 0 0 1 5-5z"/>
                <path class="cw-b-front" d="M7 2h14a6 6 0 0 1 6 6v7a6 6 0 0 1-6 6h-7l-7 6v-6a6 6 0 0 1-6-6V8a6 6 0 0 1 6-6z"/>
                <circle class="cw-b-eye" cx="11" cy="11.5" r="1.6"/>
                <circle class="cw-b-eye" cx="17.5" cy="11.5" r="1.6"/>
            </svg>
        </span>
        <span class="cw-badge" id="cw-badge" hidden>0</span>
    </button>

    <div class="cw-panel" id="cw-panel" hidden>
        <?php /* Cột trái: danh sách hội thoại. Mở chat ra là thấy cái này trước,
                 phòng chat chung nằm trong danh sách như một mục bình thường. */ ?>
        <aside class="cw-side" id="cw-side">
            <header class="cw-side-head">
                <h3>Tin nhắn</h3>
                <button type="button" class="cw-close" data-close aria-label="Đóng">&times;</button>
            </header>

            <div class="cw-search">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/></svg>
                <input type="text" id="cw-search" placeholder="Tìm theo tên…" autocomplete="off" aria-label="Tìm hội thoại">
            </div>

            <div class="cw-side-list" id="cw-side-list">
                <?php /* Phòng chung luôn đứng đầu, ai cũng vào được kể cả khách */ ?>
                <button type="button" class="cw-row is-room" id="cw-row-room" data-name="Nhóm Hẹn Hò 4 Phương">
                    <span class="cw-row-avatar cw-row-room-ic" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="9" cy="8.5" r="3.2"/><path d="M3 19a6 6 0 0 1 12 0"/><path d="M16.2 5.8a3.2 3.2 0 0 1 0 5.4M17.5 19a6 6 0 0 0-1.6-4"/></svg>
                    </span>
                    <span class="cw-row-text">
                        <span class="cw-room-meta">
                            <i class="cw-tag">Cộng đồng</i>
                            <small id="cw-room-time"></small>
                        </span>
                        <span class="cw-row-top">
                            <b><span class="cw-row-name">Nhóm Hẹn Hò 4 Phương</span></b>
                        </span>
                        <span class="cw-row-last" id="cw-room-last">Đang tải…</span>
                    </span>
                </button>

                <?php if ($user): ?>
                    <p class="cw-side-label">Trò chuyện riêng</p>
                    <div id="cw-list"><p class="cw-empty">Đang tải…</p></div>
                <?php else: ?>
                    <p class="cw-empty">
                        <a href="<?= site_url('dang-nhap') ?>">Đăng nhập</a> để nhắn tin riêng với thành viên khác.
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($user): ?>
                <footer class="cw-foot">
                    <a href="<?= site_url('tai-khoan/tin-nhan') ?>">Xem tất cả tin nhắn</a>
                </footer>
            <?php endif; ?>
        </aside>

        <?php /* Cột phải: nội dung hội thoại đang mở (phòng chung hoặc chat riêng) */ ?>
        <section class="cw-main" id="cw-main">
            <div class="cw-idle" id="cw-idle">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5h16v11H9.5L5.5 20v-3.5H4z"/></svg>
                <p>Chọn một hội thoại để bắt đầu</p>
            </div>

            <div class="cw-convo" id="cw-convo" hidden>
                <header class="cw-head">
                    <button type="button" class="cw-back" id="cw-back" aria-label="Về danh sách">‹</button>
                    <img class="cw-avatar" id="cw-avatar" alt="" hidden>
                    <span class="cw-avatar cw-row-room-ic" id="cw-avatar-room" hidden aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="9" cy="8.5" r="3.2"/><path d="M3 19a6 6 0 0 1 12 0"/><path d="M16.2 5.8a3.2 3.2 0 0 1 0 5.4M17.5 19a6 6 0 0 0-1.6-4"/></svg>
                    </span>
                    <div class="cw-peer">
                        <b id="cw-name"></b>
                        <small id="cw-status"></small>
                    </div>
                    <button type="button" class="cw-close" data-close aria-label="Đóng">&times;</button>
                </header>

                <div class="cw-body-wrap">
                    <div class="cw-body" id="cw-body"></div>
                    <aside class="cw-online-panel" id="cw-online-panel" hidden aria-labelledby="cw-online-title">
                        <div class="cw-online-head">
                            <b id="cw-online-title">Đang online</b>
                            <button type="button" id="cw-online-close" aria-label="Đóng danh sách online">×</button>
                        </div>
                        <p class="cw-online-hint">Hoạt động trong 5 phút gần đây</p>
                        <div class="cw-online-list" id="cw-online-list" aria-live="polite"></div>
                    </aside>
                    <button type="button" class="cw-jump" id="cw-jump" hidden>
                        <span class="cw-jump-arrow" aria-hidden="true">&raquo;</span>
                        <b class="cw-jump-text">Tin nhắn mới</b>
                    </button>
                </div>

                <form class="cw-form" id="cw-form">
                    <input type="hidden" name="receiver_id" id="cw-receiver">
                    <label class="cw-attach<?= $user ? '' : ' is-disabled' ?>" title="Gửi ảnh">
                        <input type="file" name="image" accept="image/*" hidden id="cw-file"
                               data-no-preview <?= $user ? '' : 'disabled' ?>>
                        <?php /* Vẽ bằng SVG thay vì emoji 🖼: emoji mỗi máy một cỡ, có máy
                                 không có glyph màu nên hiện ra hình thay thế rất to. */ ?>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2.5"/>
                            <circle cx="8.5" cy="10" r="1.6"/>
                            <path d="M4 17l4.5-4.5 3 3L15 12l5 5"/>
                        </svg>
                    </label>
                    <div class="cw-input-wrap">
                        <input type="text" name="content" id="cw-input" autocomplete="off"
                               placeholder="<?= $user ? 'Nhập tin nhắn...' : 'Đăng nhập để trò chuyện…' ?>"
                               <?= $user ? '' : 'disabled' ?>>
                        <button type="button" class="cw-emoji-btn" id="cw-emoji-btn"
                                title="Biểu tượng cảm xúc" aria-label="Biểu tượng cảm xúc" <?= $user ? '' : 'disabled' ?>><?= tk_icon('smile') ?></button>
                    </div>
                    <button class="cw-send" type="submit" aria-label="Gửi" <?= $user ? '' : 'disabled' ?>>
                        <svg viewBox="0 0 24 24" class="cw-send-ic" aria-hidden="true"><path d="M21.4 3.6 2.9 10.3c-1 .4-1 1.8 0 2.1l6.2 2 2.4 6.6c.3.9 1.6 1 2 .1l8-16.2c.4-.8-.4-1.6-1.2-1.3z"/><path d="M9.4 14.6 21 3.9"/></svg>
                    </button>
                </form>

                <?php if (!$user): ?>
                    <p class="cw-guest-note">
                        <a href="<?= site_url('dang-nhap') ?>">Đăng nhập</a> để tham gia trò chuyện
                    </p>
                <?php endif; ?>

                <div class="cw-emoji-panel" id="cw-emoji-panel" hidden>
                    <div class="cw-emoji-tabs"></div>
                    <div class="cw-emoji-list"></div>
                </div>
            </div>
        </section>
    </div>
</div>

<script defer src="<?= base_url('assets/site/js/password-toggle.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/password-toggle.js') ?>"></script>
<script defer src="<?= base_url('assets/site/js/app.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/app.js') ?>" data-base="<?= e(rtrim(site_url(), '/') . '/') ?>"></script>
<script defer src="<?= base_url('assets/site/js/date-select.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/date-select.js') ?>"></script>
<script defer src="<?= base_url('assets/site/js/searchable-select.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/searchable-select.js') ?>"></script>
<script defer src="<?= base_url('assets/site/js/notifications.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/notifications.js') ?>"></script>
<!-- Chat nạp cho cả khách: xem được phòng chung, muốn gửi thì phải đăng nhập -->
<script defer src="<?= base_url('assets/site/js/realtime.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/realtime.js') ?>"></script>
<script defer src="<?= base_url('assets/site/js/chat-widget.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/chat-widget.js') ?>"></script>

<?php $this->load->view('layouts/_mobile_nav'); ?>
</body>
</html>
