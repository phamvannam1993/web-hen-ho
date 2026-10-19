<?php defined('BASEPATH') OR exit('No direct script access allowed');
$flash = $this->session->flashdata('flash');
$seg   = $this->uri->segment(2);
$menu  = array(
    'dashboard'    => array('Tổng quan', 'admin'),
    'posts'        => array('Tin đăng', 'admin/posts'),
    'users'        => array('Thành viên', 'admin/users'),
    'photos'       => array('Duyệt ảnh', 'admin/photos'),
    'categories'   => array('Danh mục', 'admin/categories'),
    'provinces'    => array('Tỉnh/thành', 'admin/provinces'),
    'jobs'         => array('Nghề nghiệp', 'admin/jobs'),
    'emails'       => array('Email', 'admin/emails'),
    'reports'      => array('Báo cáo vi phạm', 'admin/reports'),
    'chat_groups'  => array('Nhóm chat', 'admin/chat_groups'),
    'orders'       => array('Đơn nạp / VIP', 'admin/orders'),
    'packages'     => array('Gói dịch vụ', 'admin/packages'),
    'codes'        => array('Mã bảo mật', 'admin/codes'),
    'articles'     => array('Bài viết', 'admin/articles'),
    'banners'      => array('Banner', 'admin/banners'),
    'pages'        => array('Trang tĩnh', 'admin/pages'),
    'settings'     => array('Cấu hình', 'admin/settings'),
);
$nav_icons = array(
    'dashboard'=>'<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
    'posts'=>'<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 11h8M8 15h5"/>',
    'users'=>'<circle cx="9" cy="8" r="3"/><path d="M3 21v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6M18 15a6 6 0 0 1 3 6"/>',
    'photos'=>'<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="2"/><path d="m3 17 5-5 4 4 4-6 5 7"/>',
    'categories'=>'<path d="M3 7h7l2 2h9v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Zm0 0V5a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v2"/>',
    'provinces'=>'<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
    'jobs'=>'<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V3h8v4M3 12a22 22 0 0 0 18 0M12 11v4"/>',
    'emails'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>',
    'reports'=>'<path d="M12 3 2 21h20L12 3ZM12 9v5M12 17h.01"/>',
    'chat_groups'=>'<path d="M21 14a3 3 0 0 1-3 3H8l-5 4V6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v8ZM7 8h10M7 12h6"/>',
    'orders'=>'<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 10h18M7 15h4"/>',
    'packages'=>'<path d="m12 3 9 5v9l-9 5-9-5V8l9-5Zm0 9 9-4M12 12 3 8M12 12v10M7 5l10 5"/>',
    'codes'=>'<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V6a4 4 0 0 1 8 0v4M12 14v3"/>',
    'articles'=>'<path d="M4 4h12v16H4zM16 8h4v10a2 2 0 0 1-2 2H6M8 8h4M8 12h4M8 16h4"/>',
    'banners'=>'<rect x="2" y="4" width="20" height="14" rx="2"/><path d="M8 22h8M12 18v4M6 9h5M6 13h8M17 8v5"/>',
    'pages'=>'<path d="M14 2H5v20h14V7l-5-5ZM14 2v6h5M8 12h8M8 16h8"/>',
    'settings'=>'<path d="M4 6h16M4 12h16M4 18h16"/><circle cx="8" cy="6" r="2" fill="currentColor"/><circle cx="16" cy="12" r="2" fill="currentColor"/><circle cx="10" cy="18" r="2" fill="currentColor"/>',
);
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> - Quản trị</title>
<!-- Khu quản trị luôn chặn lập chỉ mục, không phụ thuộc cấu hình -->
<meta name="robots" content="noindex, nofollow, noarchive">
<link rel="stylesheet" href="<?= base_url('assets/admin/css/admin.css') ?>?v=<?= @filemtime(FCPATH.'assets/admin/css/admin.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/admin/css/theme.css') ?>?v=<?= filemtime(FCPATH.'assets/admin/css/theme.css') ?>">
</head>
<body class="admin-app">
<a class="admin-skip" href="#admin-content">Đến nội dung chính</a>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?= site_url('admin') ?>"><span class="admin-brand-mark" aria-hidden="true">♡</span><span><?= e($settings['site_name'] ?? 'Saigon Cupid') ?><small>TRANG QUẢN TRỊ</small></span></a>
        <nav aria-label="Điều hướng quản trị">
            <ul>
                <?php foreach ($menu as $key => $item): ?>
                    <?php if (in_array($key, array('dashboard','categories','orders','articles','settings'), true)): ?><li class="admin-nav-label"><?= e(array('dashboard'=>'Tổng quan & cộng đồng','categories'=>'Danh mục','orders'=>'Dịch vụ & thanh toán','articles'=>'Nội dung website','settings'=>'Hệ thống')[$key]) ?></li><?php endif; ?>
                    <?php $nav_active = $seg === $key || ($key === 'dashboard' && !$seg); ?>
                    <li><a class="<?= $nav_active ? 'active' : '' ?>" <?= $nav_active ? 'aria-current="page"' : '' ?> href="<?= site_url($item[1]) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $nav_icons[$key] ?></svg><span><?= e($item[0]) ?></span></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-page-heading"><p>Không gian quản trị <span>/</span> <?= e($menu[$seg ?: 'dashboard'][0] ?? 'Quản lý') ?></p><h1><?= e($title) ?></h1></div>
            <div class="admin-user">
                <a href="<?= site_url() ?>" target="_blank">Xem website</a>
                <span class="admin-user-name"><i aria-hidden="true"><?= e(mb_substr($admin['display_name'], 0, 1)) ?></i><span><?= e($admin['display_name']) ?><small>Quản trị viên</small></span></span>
                <a class="btn-logout" href="<?= site_url('admin/dang-xuat') ?>">Đăng xuất</a>
            </div>
        </header>

        <main class="admin-content" id="admin-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?php $this->load->view($content_view); ?>
        </main>
    </div>
</div>
<script src="<?= base_url('assets/admin/js/password-toggle.js') ?>?v=<?= @filemtime(FCPATH.'assets/admin/js/password-toggle.js') ?>"></script>
<script src="<?= base_url('assets/admin/js/admin.js') ?>?v=<?= @filemtime(FCPATH.'assets/admin/js/admin.js') ?>"></script>
<script src="<?= base_url('assets/site/js/searchable-select.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/searchable-select.js') ?>"></script>
<script src="<?= base_url('assets/ckeditor/ckeditor.js') ?>"></script>
<script src="<?= base_url('assets/admin/js/editor.js') ?>?v=<?= @filemtime(FCPATH.'assets/admin/js/editor.js') ?>"></script>
</body>
</html>
