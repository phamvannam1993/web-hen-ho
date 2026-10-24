<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container auth-page">
    <div class="auth-card">
        <h1 class="auth-title">Đăng nhập</h1>
        <p class="auth-sub">Chào mừng bạn trở lại. Tiếp tục trò chuyện và kết nối nhé.</p>

        <?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

        <form method="post" class="auth-form">
            <label for="identity">Email hoặc số điện thoại</label>
            <input type="text" id="identity" name="identity" value="<?= set_value('identity') ?>" autocomplete="username" placeholder="Nhập email hoặc số điện thoại" required>

            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Nhập mật khẩu của bạn" required>


            <label class="checkbox">
                <input type="checkbox" name="remember" value="1"> Ghi nhớ đăng nhập
            </label>

            <div class="auth-actions">
                <button type="submit" class="btn btn-primary">Đăng nhập</button>
                <a class="btn btn-ghost" href="<?= site_url('dang-ky') ?>">Tạo tài khoản</a>
            </div>

            <p class="auth-foot"><a href="<?= site_url('quen-mat-khau') ?>">Quên mật khẩu?</a></p>
        </form>
    </div>
</div>
