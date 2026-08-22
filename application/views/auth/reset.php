<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container">
    <div class="auth-card">
        <h1 class="auth-title">Đặt lại mật khẩu</h1>
        <?= validation_errors('<div class="alert alert-danger">', '</div>') ?>
        <form method="post" class="auth-form">
            <label for="password">Mật khẩu mới</label>
            <input type="password" id="password" name="password" required>
            <label for="password_confirm">Xác nhận mật khẩu</label>
            <input type="password" id="password_confirm" name="password_confirm" required>
            <div class="auth-actions">
                <button class="btn btn-primary" type="submit">Đổi mật khẩu</button>
            </div>
        </form>
    </div>
</div>
