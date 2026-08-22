<?php defined('BASEPATH') OR exit('No direct script access allowed');
$flash = $this->session->flashdata('flash');
$menu  = $categories ?? array();
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Hẹn hò kết bạn') ?> - <?= e($settings['site_name'] ?? 'HenHo24') ?></title>
<meta name="description" content="<?= e($meta_desc ?? '') ?>">
<link rel="stylesheet" href="<?= base_url('assets/site/css/style.css') ?>?v=<?= @filemtime(FCPATH.'assets/site/css/style.css') ?>">
</head>
<body>

<!-- Dải mảnh trên cùng: khẩu hiệu + hotline -->
<div class="topbar">
    <div class="container topbar-inner">
        <span class="topbar-slogan"><?= e($settings['site_slogan'] ?? 'Hẹn hò kết bạn nghiêm túc, chia sẻ buồn vui') ?></span>
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
            <span class="brand-text"><?= e($settings['site_name'] ?? 'HenHo24') ?></span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Mở menu"
                onclick="document.querySelector('.nav-list').classList.toggle('open')">☰</button>

        <ul class="nav-list">
            <li><a class="active" href="<?= site_url() ?>">Trang chủ</a></li>
            <?php foreach ($menu as $cat): ?>
                <li class="<?= !empty($cat['children']) ? 'has-mega' : '' ?>">
                    <a href="<?= site_url('danh-muc/' . $cat['slug']) ?>"><?= e($cat['name']) ?><?= !empty($cat['children']) ? ' <span class="caret">▾</span>' : '' ?></a>
                    <?php if (!empty($cat['children'])): ?>
                        <!-- mega menu: CSS tự dàn danh mục con thành nhiều cột -->
                        <div class="mega-menu">
                            <div class="container">
                                <ul class="mega-cols">
                                    <?php foreach ($cat['children'] as $sub): ?>
                                        <li><a href="<?= site_url('danh-muc/' . $sub['slug']) ?>"><?= e($sub['name']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <li><a href="<?= site_url('kham-pha') ?>">Khám phá</a></li>
            <li><a href="<?= site_url('thanh-vien') ?>">Thành viên</a></li>
            <li><a href="<?= site_url('trang/noi-quy') ?>">Nội quy</a></li>
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
    <div class="container footer-grid">
        <div>
            <h4><?= e($settings['site_name'] ?? 'HenHo24') ?></h4>
            <p><?= e($settings['site_desc'] ?? 'Nền tảng kết bạn, hẹn hò nghiêm túc dành cho người Việt.') ?></p>
        </div>
        <div>
            <h4>Danh mục</h4>
            <ul>
                <?php foreach (array_slice($menu, 0, 5) as $cat): ?>
                    <li><a href="<?= site_url('danh-muc/' . $cat['slug']) ?>"><?= e($cat['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4>Hỗ trợ</h4>
            <ul>
                <li><a href="<?= site_url('trang/noi-quy') ?>">Nội quy</a></li>
                <li><a href="<?= site_url('trang/dieu-khoan') ?>">Điều khoản</a></li>
                <li><a href="<?= site_url('trang/lien-he') ?>">Liên hệ</a></li>
                <li><a href="<?= site_url('tin-tuc') ?>">Cẩm nang hẹn hò</a></li>
            </ul>
        </div>
        <div>
            <h4>Liên hệ</h4>
            <p>Hotline: <?= e($settings['hotline'] ?? '') ?></p>
            <p>Email: <?= e($settings['contact_email'] ?? '') ?></p>
        </div>
    </div>
    <div class="footer-bottom">© <?= date('Y') ?> <?= e($settings['site_name'] ?? 'HenHo24') ?>. Nội dung do thành viên đăng tải.</div>
</footer>

<?php if ($user): ?>
<!-- Chat nổi: nút bong bóng sát mép phải, bấm xổ ra khung trò chuyện -->
<div id="chat-widget" class="chat-widget" data-base="<?= site_url() ?>"
     data-ws-url="<?= e($ws_url ?? '') ?>" data-ws-token="<?= e($ws_token ?? '') ?>">
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
                <button type="button" class="cw-icon-btn" id="cw-to-list" title="Tin nhắn riêng">✉</button>
                <button type="button" class="cw-close" data-close aria-label="Đóng">&times;</button>
            </header>

            <div class="cw-body" id="cw-room-body"></div>

            <form class="cw-form" id="cw-room-form">
                <label class="cw-attach" title="Gửi ảnh">
                    <input type="file" name="image" accept="image/*" hidden id="cw-room-file">
                    <span>🖼</span>
                </label>
                <div class="cw-input-wrap">
                    <input type="text" name="content" id="cw-room-input" placeholder="Nhắn cho cả phòng…" autocomplete="off">
                    <button type="button" class="cw-emoji-btn" id="cw-room-emoji-btn" title="Biểu tượng cảm xúc">☺</button>
                    <div class="cw-emoji-panel" id="cw-room-emoji-panel" hidden></div>
                </div>
                <button class="cw-send" type="submit" aria-label="Gửi">➤</button>
            </form>
        </div>

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

<script src="<?= base_url('assets/site/js/app.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/app.js') ?>"></script>
<?php if ($user): ?>
<script src="<?= base_url('assets/site/js/realtime.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/realtime.js') ?>"></script>
<script src="<?= base_url('assets/site/js/chat-widget.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/chat-widget.js') ?>"></script>
<?php endif; ?>
</body>
</html>
