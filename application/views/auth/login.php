<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container">
    <div class="auth-card">
        <h1 class="auth-title">Đăng nhập</h1>

        <?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

        <form method="post" class="auth-form">
            <label for="identity">Email hoặc số điện thoại</label>
            <input type="text" id="identity" name="identity" value="<?= set_value('identity') ?>" required>

            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" required>

            <?php if (setting('require_code_login', '1') === '1'): ?>
                <p class="code-hint">
                    Bạn vui lòng bấm vào đây =&gt;&gt;&gt;&gt;
                    <a class="code-link" href="<?= site_url('lay-pass/login') ?>" data-get-code="login">LẤY KHOÁ BẢO MẬT</a>
                </p>
                <input type="text" name="access_code" placeholder="Nhập mã bảo mật vào đây" autocomplete="off">
            <?php endif; ?>

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
