<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container">
    <div class="auth-card">
        <h1 class="auth-title">Tạo tài khoản</h1>

        <?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

        <form method="post" class="auth-form">
            <label for="display_name">Họ và tên</label>
            <input type="text" id="display_name" name="display_name" value="<?= set_value('display_name') ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= set_value('email') ?>" required>

            <label for="phone">Số điện thoại (Zalo)</label>
            <input type="text" id="phone" name="phone" value="<?= set_value('phone') ?>">

            <div class="form-row">
                <div>
                    <label for="gender">Giới tính</label>
                    <select id="gender" name="gender" required>
                        <option value="female">Nữ</option>
                        <option value="male">Nam</option>
                        <option value="other">Khác</option>
                    </select>
                </div>
                <div>
                    <label for="birthday">Ngày sinh</label>
                    <input type="date" id="birthday" name="birthday" value="<?= set_value('birthday') ?>">
                </div>
            </div>

            <label for="province_id">Khu vực</label>
            <select id="province_id" name="province_id">
                <option value="">-- Chọn tỉnh/thành --</option>
                <?php foreach ($provinces as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= set_select('province_id', $p['id']) ?>><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" required>

            <label for="password_confirm">Xác nhận mật khẩu</label>
            <input type="password" id="password_confirm" name="password_confirm" required>


            <label class="checkbox">
                <input type="checkbox" name="agree" value="1" required>
                Tôi đồng ý với <a href="<?= site_url('trang/noi-quy') ?>" target="_blank">nội quy</a> của website
            </label>

            <div class="auth-actions">
                <button type="submit" class="btn btn-primary">Đăng ký</button>
                <a class="btn btn-ghost" href="<?= site_url('dang-nhap') ?>">Đã có tài khoản</a>
            </div>
        </form>
    </div>
</div>
