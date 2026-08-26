<?php defined('BASEPATH') OR exit('No direct script access allowed');
$flash = $this->session->flashdata('flash');
?><!DOCTYPE html>
<html lang="vi">
<head>
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
/*
 * Chặn công cụ tìm kiếm lập chỉ mục.
 * Bật/tắt tại Quản trị -> Cấu hình -> "Chặn Google lập chỉ mục".
 * Khi website còn đang phát triển nên để bật, lúc chạy thật thì tắt đi.
 */
$noindex = ($settings['site_noindex'] ?? '1') === '1';
?>
<?php if ($noindex): ?>
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
    <meta name="googlebot" content="noindex, nofollow">
<?php else: ?>
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= current_url() ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= base_url('assets/site/css/style.css') ?>?v=<?= @filemtime(FCPATH.'assets/site/css/style.css') ?>">
<link rel="icon" type="image/x-icon" href="<?= base_url('assets/site/img/favicon.ico?v=1232312131') ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/site/img/apple-touch-icon.png?V=1243324243') ?>">

<link rel="stylesheet" href="<?= base_url('assets/site/css/style.css') ?>?v=<?= @filemtime(FCPATH.'assets/site/css/style.css') ?>">
</head>
<body>

<!-- Dải mảnh trên cùng: khẩu hiệu + hotline -->
<div class="topbar">
    <div class="container topbar-inner">
        
        <?php if (!empty($settings['hotline'])): ?>
            <span class="topbar-hotline">Hỗ trợ: <b><?= e($settings['hotline']) ?></b></span>
        <?php endif; ?>
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
            array('url' => 'kham-pha',      'label' => 'Khám phá',   'match' => array('kham-pha')),
            array('url' => 'khu-vuc',       'label' => 'Khu vực',    'match' => array('khu-vuc')),
            array('url' => 'tin-tuc',       'label' => 'Sforum',   'match' => array('tin-tuc')),
            array('url' => 'trang/noi-quy', 'label' => 'Thông báo',    'match' => array('trang')),
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
                <a class="btn-nav-solid" href="<?= site_url('dang-ky') ?>">Tạo tài khoản</a>
            <?php endif; ?>
        </div>
        </div><!-- /.nav-drawer -->

        <div class="nav-overlay" id="nav-overlay" hidden></div>
    </div>
</header>

<div class="container">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
</div>

<main>
    <?php $this->load->view($content_view); ?>
</main>

<footer class="site-footer">
    <div class="site-footer__container">
        <div class="site-footer__top">
            <div class="site-footer__brand"><?= e($settings['site_name'] ?? 'Saigon Cupid') ?></div>
            <div class="site-footer__top-right">
                <p class="site-footer__slogan"><?= e($settings['site_slogan'] ?? '') ?></p>
            </div>
        </div>

        <nav class="site-footer__policy-nav" aria-label="Liên kết chính sách">
            <a href="<?= site_url('trang/gioi-thieu') ?>">Về chúng tôi</a>
            <a href="<?= site_url('trang/noi-quy') ?>">Người điều hành</a>
            <a href="<?= site_url('trang/dieu-khoan') ?>">Faqs</a>
            <a href="<?= site_url('trang/bao-mat') ?>">Chính sách bảo mật</a>
            <a href="<?= site_url('trang/lien-he') ?>">Liên hệ</a>
        </nav>

        <div class="site-footer__line"></div>

        <div class="site-footer__middle">
            <div class="site-footer__links">
                <div class="footer-col">
                    <h3 class="footer-col__title">Về chúng tôi</h3>
                    <ul class="footer-col__list">
                        <li><a href="<?= site_url('trang/gioi-thieu') ?>">Giới thiệu</a></li>
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
                        <li><a href="<?= site_url('kham-pha') ?>">Hướng dẫn ghép đôi</a></li>
                        <li><a href="<?= site_url('thanh-vien') ?>">Cộng đồng thành viên</a></li>
                        <li><a href="<?= site_url('trang/noi-quy') ?>">Thông báo cộng đồng</a></li>
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
            <li><a target="_blank" href="#"><span><img src="/assets/images/youtube.png" alt="Youtube" /></span> Youtube</a></li>
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

<!-- Chat nổi: khách xem được phòng chung, muốn gửi thì phải đăng nhập -->
<div id="chat-widget" class="chat-widget" data-base="<?= site_url() ?>"
     data-ws-url="<?= e($ws_url ?? '') ?>" data-ws-token="<?= e($ws_token ?? '') ?>"
     data-guest="<?= $user ? '0' : '1' ?>">
    <button type="button" class="cw-bubble" id="cw-bubble" aria-label="Mở tin nhắn">
        <span class="cw-bubble-icon">💬</span>
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

            <div class="cw-body" id="cw-room-body"></div>

            <form class="cw-form" id="cw-room-form">
                <label class="cw-attach<?= $user ? '' : ' is-disabled' ?>" title="Gửi ảnh">
                    <input type="file" name="image" accept="image/*" hidden id="cw-room-file"
                           <?= $user ? '' : 'disabled' ?>>
                    <span>🖼</span>
                </label>
                <div class="cw-input-wrap">
                    <input type="text" name="content" id="cw-room-input" autocomplete="off"
                           placeholder="<?= $user ? 'Nhắn cho cả phòng…' : 'Đăng nhập để trò chuyện…' ?>"
                           <?= $user ? '' : 'disabled' ?>>
                    <button type="button" class="cw-emoji-btn" id="cw-room-emoji-btn" title="Biểu tượng cảm xúc">☺</button>
                    <div class="cw-emoji-panel" id="cw-room-emoji-panel" hidden></div>
                </div>
                <button class="cw-send" type="submit" aria-label="Gửi"
                        <?= $user ? '' : 'disabled' ?>>➤</button>
            </form>
            <?php if (!$user): ?>
                <p class="cw-guest-note">
                    <a href="<?= site_url('dang-nhap') ?>">Đăng nhập</a> để tham gia trò chuyện
                </p>
            <?php endif; ?>
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

            <div class="cw-body" id="cw-body"></div>

            <form class="cw-form" id="cw-form">
                <input type="hidden" name="receiver_id" id="cw-receiver">
                <label class="cw-attach" title="Gửi ảnh">
                    <input type="file" name="image" accept="image/*" hidden id="cw-file">
                    <span>🖼</span>
                </label>
                <div class="cw-input-wrap">
                    <input type="text" name="content" id="cw-input" placeholder="Nhắn gì đó…" autocomplete="off">
                    <button type="button" class="cw-emoji-btn" id="cw-emoji-btn" title="Biểu tượng cảm xúc">☺</button>
                    <div class="cw-emoji-panel" id="cw-emoji-panel" hidden></div>
                </div>
                <button class="cw-send" type="submit" aria-label="Gửi">➤</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="<?= base_url('assets/site/js/password-toggle.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/password-toggle.js') ?>"></script>
<script src="<?= base_url('assets/site/js/app.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/app.js') ?>"></script>
<!-- Chat nạp cho cả khách: xem được phòng chung, muốn gửi thì phải đăng nhập -->
<script src="<?= base_url('assets/site/js/realtime.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/realtime.js') ?>"></script>
<script src="<?= base_url('assets/site/js/chat-widget.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/chat-widget.js') ?>"></script>
</body>
</html>
