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
<meta name="viewport" content="width=device-width, initial-scale=1">
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
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($meta_desc ?? '') ?>">
<?php
// Kiểm tra biến $allow_index hoặc $data['allow_index'] truyền từ controller
$force_allow_index = (isset($allow_index) && $allow_index === true) 
                  || (isset($data['allow_index']) && $data['allow_index'] === true);

// Cấu hình chung của website
$site_blocked = ($settings['site_noindex'] ?? '1') === '1';

// Ưu tiên nếu controller chủ động bật allow_index = true thì LUÔN INDEX
$can_index = $force_allow_index || !$site_blocked;
?>

<?php if ($can_index): ?>
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= current_url() ?>">
<?php else: ?>
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
    <meta name="googlebot" content="noindex, nofollow">
<?php endif; ?>
<link rel="stylesheet" href="<?= base_url('assets/site/css/style.css') ?>?v=<?= @filemtime(FCPATH.'assets/site/css/style.css') ?>">
<link rel="icon" type="image/x-icon" href="<?= base_url('assets/site/img/favicon.ico?v=1232312131') ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/site/img/apple-touch-icon.png?V=1243324243') ?>">

<link rel="stylesheet" href="<?= base_url('assets/site/css/style.css') ?>?v=<?= @filemtime(FCPATH.'assets/site/css/style.css') ?>">
</head>
<body class="<?= !empty($bare) ? 'is-bare' : '' ?>">

<!-- Dải mảnh trên cùng: khẩu hiệu + hotline -->
<?php /* Chế độ toàn màn hình (trang Khám phá): bỏ thanh trên, menu và chân trang */ ?>
<?php if (empty($bare)): ?>
<?php
/* Thanh trên cùng: liên hệ bên trái, trang tĩnh và mạng xã hội bên phải.
   Mục nào chưa khai trong Quản trị -> Cấu hình thì tự ẩn đi. */
$mxh = array_filter(array(
    'facebook'  => $settings['facebook_url']  ?? '',
    'instagram' => $settings['instagram_url'] ?? '',
    'youtube'   => $settings['youtube_url']   ?? '',
    'tiktok'    => $settings['tiktok_url']    ?? '',
));
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
            <span class="brand-mark">♥</span>
            <span class="brand-text"><?= e($settings['site_name'] ?? 'Saigon Cupid') ?></span>
        </a>

        <?php /* Ô tìm kiếm gọn ở giữa hàng trên, lấp khoảng trống giữa logo và nhóm nút.
                 Chỉ hiện trên màn rộng; màn hẹp dùng bộ lọc trong trang Thành viên. */ ?>
        <form class="header-search" method="get" action="<?= site_url('tim-kiem') ?>" role="search">
            <svg viewBox="0 0 24 24" class="ic" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/>
            </svg>
            <input type="text" name="q" value="<?= e($this->input->get('q')) ?>"
                   placeholder="Tìm theo tên, khu vực hoặc nghề nghiệp..." aria-label="Tìm thành viên">
        </form>

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

        <?php
        // Đánh dấu mục đang xem theo đường dẫn hiện tại.
        // 'match' liệt kê các nhánh URL cùng thuộc một mục, ví dụ trang cá nhân
        // /profile/... vẫn tính là đang ở mục Thành viên.
        $nav_items = array(
            array('url' => '',              'label' => 'Trang chủ',  'match' => array('')),
            array('url' => 'hen-ho',        'label' => 'Hẹn hò',     'match' => array('hen-ho')),
            array('url' => 'tam-su',        'label' => 'Tâm sự',     'match' => array('tam-su')),
            array('url' => 'thanh-vien',    'label' => 'Thành viên', 'match' => array('thanh-vien', 'profile', 'tim-kiem')),
            array('url' => 'swipe-match',      'label' => 'Khám phá',   'match' => array('swipe-match')),
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
                </a>
                <a class="btn-nav-ghost" href="<?= site_url('dang-xuat') ?>">Đăng xuất</a>
            <?php else: ?>
                <a class="btn-nav-ghost" href="<?= site_url('dang-nhap') ?>">Đăng nhập</a>
                <a class="btn-nav-solid" href="<?= site_url('dang-ky') ?>">Đăng ký</a>
            <?php endif; ?>

            <?php /* Chuông thông báo + khay xổ xuống; chỉ có nghĩa khi đã đăng nhập */ ?>
            <?php if ($user): ?>
                <div class="hd-noti" id="hd-noti" data-base="<?= site_url() ?>">
                    <button type="button" class="hd-bell" id="noti-toggle"
                            aria-label="Thông báo" aria-expanded="false" aria-haspopup="dialog">
                        <svg viewBox="0 0 24 24" class="ic" aria-hidden="true">
                            <path d="M18 16.5V11a6 6 0 1 0-12 0v5.5L4.5 18.5h15z"/>
                            <path d="M10 21.2a2.2 2.2 0 0 0 4 0"/>
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
        </div><!-- /.nav-drawer -->

        <div class="nav-overlay" id="nav-overlay" hidden></div>
    </div>
</header>
<?php endif; ?>

<div class="container">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
</div>

<main>
    <?php $this->load->view($content_view); ?>
</main>

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
            <a href="<?= site_url('noi-quy') ?>">Người điều hành</a>
            <a href="<?= site_url('dieu-khoan') ?>">Faqs</a>
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
                        <ul class="footer-col__list footer-col__list--social">
            <li><a target="_blank" href="#"><span><img src="/assets/images/tiktok.png" alt="Tiktok" /></span> Tiktok</a></li>
            <li><a target="_blank" href="#"><span><img src="/assets/images/youtube.png?v=345345435435" alt="Youtube" /></span> Youtube</a></li>
            <li><a target="_blank" href="#"><span><img src="/assets/images/insta.png" alt="Instagram" /></span> Instagram</a></li>
            <li><a target="_blank" href="#"><span><img src="/assets/images/facebook.png" alt="Facebook" /></span> Facebook</a></li>
          </ul>
                        
                        
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
     data-guest="<?= ($user && empty($ho_so_chua_xong)) ? '0' : '1' ?>"
     data-need-profile="<?= !empty($ho_so_chua_xong) ? '1' : '0' ?>">
    <button type="button" class="cw-tab" id="cw-bubble" aria-label="Mở trò chuyện">
        <span class="cw-tab-arrow" aria-hidden="true">&laquo;</span>
        <span class="cw-tab-label">Trò chuyện</span>
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
        <!-- Màn hình mặc định: phòng chat chung -->
        <div class="cw-view" id="cw-room-view">
            <header class="cw-head">
                <div class="cw-peer">
                    <b>Phòng chat chung</b>
                    <small id="cw-room-online">Đang tải…</small>
                </div>
                <?php if ($user): ?>
                    <button type="button" class="cw-icon-btn" id="cw-to-list" title="Tin nhắn riêng">✉</button>
                <?php endif; ?>
                <button type="button" class="cw-close" data-close aria-label="Đóng">&times;</button>
            </header>

            <div class="cw-body-wrap">
                <div class="cw-body" id="cw-room-body"></div>
                <button type="button" class="cw-jump" id="cw-room-jump" hidden>
                    <span class="cw-jump-arrow" aria-hidden="true">&raquo;</span>
                    <b class="cw-jump-text">Tin nhắn mới</b>
                </button>
            </div>

            <form class="cw-form" id="cw-room-form">
                <label class="cw-attach<?= $user ? '' : ' is-disabled' ?>" title="Gửi ảnh">
                    <input type="file" name="image" accept="image/*" hidden id="cw-room-file"
                           <?= $user ? '' : 'disabled' ?>>
                    <span>🖼</span>
                </label>
                <div class="cw-input-wrap">
                    <input type="text" name="content" id="cw-room-input" autocomplete="off"
                           placeholder="<?= $user ? 'Vui lòng nhập tin nhắn' : 'Đăng nhập để trò chuyện…' ?>"
                           <?= $user ? '' : 'disabled' ?>>
                    <button type="button" class="cw-emoji-btn" id="cw-room-emoji-btn"
                            title="Biểu tượng cảm xúc" <?= $user ? '' : 'disabled' ?>>☺</button>
                </div>
                <button class="cw-send" type="submit" aria-label="Gửi"
                        <?= $user ? '' : 'disabled' ?>><svg viewBox="0 0 24 24" class="cw-send-ic" aria-hidden="true"><path d="M21.4 3.6 2.9 10.3c-1 .4-1 1.8 0 2.1l6.2 2 2.4 6.6c.3.9 1.6 1 2 .1l8-16.2c.4-.8-.4-1.6-1.2-1.3z"/><path d="M9.4 14.6 21 3.9"/></svg></button>
            </form>
            <?php if (!$user): ?>
                <p class="cw-guest-note">
                    <a href="<?= site_url('dang-nhap') ?>">Đăng nhập</a> để tham gia trò chuyện
                </p>
            <?php endif; ?>
            <?php /* Bảng icon nằm dưới ô nhập, đẩy khung tin ngắn lại chứ không đè lên */ ?>
            <div class="cw-emoji-panel" id="cw-room-emoji-panel" hidden>
                <div class="cw-emoji-tabs"></div>
                <div class="cw-emoji-list"></div>
            </div>
        </div>

        <?php if ($user): ?>
        <!-- Danh sách hội thoại riêng -->
        <div class="cw-view" id="cw-list-view" hidden>
            <header class="cw-head">
                <button type="button" class="cw-back" id="cw-to-room" aria-label="Về phòng chung">‹</button>
                <h3>Tin nhắn riêng</h3>
                <button type="button" class="cw-close" data-close aria-label="Đóng">&times;</button>
            </header>
            <div class="cw-list" id="cw-list">
                <p class="cw-empty">Đang tải…</p>
            </div>
            <footer class="cw-foot">
                <a href="<?= site_url('tai-khoan/tin-nhan') ?>">Xem tất cả tin nhắn</a>
            </footer>
        </div>

        <!-- Màn hình 2: khung trò chuyện -->
        <div class="cw-view" id="cw-chat-view" hidden>
            <header class="cw-head">
                <button type="button" class="cw-back" id="cw-back" aria-label="Quay lại">‹</button>
                <img class="cw-avatar" id="cw-avatar" src="" alt="">
                <div class="cw-peer">
                    <b id="cw-name"></b>
                    <small id="cw-status"></small>
                </div>
                <button type="button" class="cw-close" data-close aria-label="Đóng">&times;</button>
            </header>

            <div class="cw-body-wrap">
                <div class="cw-body" id="cw-body"></div>
                <button type="button" class="cw-jump" id="cw-jump" hidden>
                    <span class="cw-jump-arrow" aria-hidden="true">&raquo;</span>
                    <b class="cw-jump-text">Tin nhắn mới</b>
                </button>
            </div>

            <form class="cw-form" id="cw-form">
                <input type="hidden" name="receiver_id" id="cw-receiver">
                <label class="cw-attach" title="Gửi ảnh">
                    <input type="file" name="image" accept="image/*" hidden id="cw-file">
                    <span>🖼</span>
                </label>
                <div class="cw-input-wrap">
                    <input type="text" name="content" id="cw-input" placeholder="Vui lòng nhập tin nhắn" autocomplete="off">
                    <button type="button" class="cw-emoji-btn" id="cw-emoji-btn" title="Biểu tượng cảm xúc">☺</button>
                </div>
                <button class="cw-send" type="submit" aria-label="Gửi"><svg viewBox="0 0 24 24" class="cw-send-ic" aria-hidden="true"><path d="M21.4 3.6 2.9 10.3c-1 .4-1 1.8 0 2.1l6.2 2 2.4 6.6c.3.9 1.6 1 2 .1l8-16.2c.4-.8-.4-1.6-1.2-1.3z"/><path d="M9.4 14.6 21 3.9"/></svg></button>
            </form>
            <div class="cw-emoji-panel" id="cw-emoji-panel" hidden>
                <div class="cw-emoji-tabs"></div>
                <div class="cw-emoji-list"></div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script src="<?= base_url('assets/site/js/password-toggle.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/password-toggle.js') ?>"></script>
<script src="<?= base_url('assets/site/js/app.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/app.js') ?>"></script>
<script src="<?= base_url('assets/site/js/searchable-select.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/searchable-select.js') ?>"></script>
<script src="<?= base_url('assets/site/js/notifications.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/notifications.js') ?>"></script>
<!-- Chat nạp cho cả khách: xem được phòng chung, muốn gửi thì phải đăng nhập -->
<script src="<?= base_url('assets/site/js/realtime.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/realtime.js') ?>"></script>
<script src="<?= base_url('assets/site/js/chat-widget.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/chat-widget.js') ?>"></script>

</body>
</html>
