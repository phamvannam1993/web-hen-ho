<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container">
    <div class="auth-card">
        <h1 class="auth-title">Quên mật khẩu</h1>
        <?= validation_errors('<div class="alert alert-danger">', '</div>') ?>
        <p>Nhập email đã đăng ký, chúng tôi sẽ gửi liên kết đặt lại mật khẩu.</p>
        <form method="post" class="auth-form">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= set_value('email') ?>" required>
            <div class="auth-actions">
                <button class="btn btn-primary" type="submit">Gửi yêu cầu</button>
                <a class="btn btn-ghost" href="<?= site_url('dang-nhap') ?>">Quay lại đăng nhập</a>
            </div>
        </form>
    </div>
</div>
