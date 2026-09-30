<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <form class="content-box auth-form" method="post">
            <h1 class="section-title">Đổi mật khẩu</h1>
            <?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

            <label for="current">Mật khẩu hiện tại</label>
            <input type="password" id="current" name="current" required>

            <label for="password">Mật khẩu mới</label>
            <input type="password" id="password" name="password" required>

            <label for="password_confirm">Xác nhận mật khẩu mới</label>
            <input type="password" id="password_confirm" name="password_confirm" required>

            <div class="auth-actions">
                <button class="btn btn-primary" type="submit">Đổi mật khẩu</button>
            </div>
        </form>
    </div>
    <aside></aside>
</div>
