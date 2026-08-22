<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập quản trị</title>
<meta name="robots" content="noindex, nofollow, noarchive">
<link rel="stylesheet" href="<?= base_url('assets/admin/css/admin.css') ?>">
</head>
<body class="login-page">
    <form class="login-card" method="post">
        <h1>Đăng nhập quản trị</h1>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <label for="identity">Email hoặc số điện thoại</label>
        <input type="text" id="identity" name="identity" required autofocus>
        <label for="password">Mật khẩu</label>
        <input type="password" id="password" name="password" required>
        <button class="btn btn-primary" type="submit">Đăng nhập</button>
    </form>
</body>
</html>
