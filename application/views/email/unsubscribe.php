<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container">
    <div class="auth-card">
        <h1 class="auth-title">Huỷ nhận email</h1>

        <?php if ($p['disabled_at']): ?>
            <p class="otp-lead">Bạn đã huỷ nhận toàn bộ email. Muốn nhận lại thì đăng nhập rồi vào
                <a href="<?= site_url('tai-khoan/email') ?>">Cài đặt email</a>.</p>
        <?php else: ?>
            <p class="otp-lead">Chọn loại thư bạn không muốn nhận nữa. Việc này có hiệu lực ngay.</p>

            <div class="email-prefs">
                <?php foreach ($loai as $key => $nhan): ?>
                    <?php if (empty($p[$key])) { continue; } ?>
                    <form method="post" class="email-unsub-row">
                        <input type="hidden" name="loai" value="<?= $key ?>">
                        <span><?= e($nhan) ?></span>
                        <button type="submit" class="btn btn-ghost btn-sm">Tắt loại này</button>
                    </form>
                <?php endforeach; ?>
            </div>

            <form method="post">
                <input type="hidden" name="loai" value="tat_ca">
                <div class="auth-actions">
                    <button type="submit" class="btn btn-primary">Huỷ nhận tất cả</button>
                </div>
            </form>
        <?php endif; ?>

        <p class="auth-foot"><a href="<?= site_url() ?>">&larr; Về trang chủ</a></p>
    </div>
</div>
