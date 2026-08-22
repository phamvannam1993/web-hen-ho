<?php defined('BASEPATH') OR exit('No direct script access allowed');
$flash = $this->session->flashdata('flash');
$seg   = $this->uri->segment(2);
$menu  = array(
    'dashboard'    => array('Tổng quan', 'admin'),
    'posts'        => array('Tin đăng', 'admin/posts'),
    'users'        => array('Thành viên', 'admin/users'),
    'categories'   => array('Danh mục', 'admin/categories'),
    'reports'      => array('Báo cáo vi phạm', 'admin/reports'),
    'orders'       => array('Đơn nạp / VIP', 'admin/orders'),
    'packages'     => array('Gói dịch vụ', 'admin/packages'),
    'codes'        => array('Mã bảo mật', 'admin/codes'),
    'articles'     => array('Bài viết', 'admin/articles'),
    'banners'      => array('Banner', 'admin/banners'),
    'pages'        => array('Trang tĩnh', 'admin/pages'),
    'settings'     => array('Cấu hình', 'admin/settings'),
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
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand"><?= e($settings['site_name'] ?? 'HenHo24') ?></div>
        <nav>
            <ul>
                <?php foreach ($menu as $key => $item): ?>
                    <li><a class="<?= $seg === $key || ($key === 'dashboard' && !$seg) ? 'active' : '' ?>"
                           href="<?= site_url($item[1]) ?>"><?= e($item[0]) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <h1><?= e($title) ?></h1>
            <div class="admin-user">
                <a href="<?= site_url() ?>" target="_blank">Xem website</a>
                <span><?= e($admin['display_name']) ?></span>
                <a class="btn-logout" href="<?= site_url('admin/dang-xuat') ?>">Đăng xuất</a>
            </div>
        </header>

        <div class="admin-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?php $this->load->view($content_view); ?>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/admin/js/password-toggle.js') ?>?v=<?= @filemtime(FCPATH.'assets/admin/js/password-toggle.js') ?>"></script>
<script src="<?= base_url('assets/admin/js/admin.js') ?>?v=<?= @filemtime(FCPATH.'assets/admin/js/admin.js') ?>"></script>
<script src="<?= base_url('assets/ckeditor/ckeditor.js') ?>"></script>
<script src="<?= base_url('assets/admin/js/editor.js') ?>?v=<?= @filemtime(FCPATH.'assets/admin/js/editor.js') ?>"></script>
</body>
</html>
