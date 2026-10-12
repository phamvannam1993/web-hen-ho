<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Trang "Kiểm tra email": nhắc bấm link xác thực trong thư, có nút gửi lại.
 * Không bắt nhập mã — người dùng đã vào được web, chỉ chưa thả tim/nhắn tin được.
 *
 * @var string $email  @var int $cho_giay  @var int $gio_song
 */
?>
<div class="container">
    <div class="auth-card">
        <h1 class="auth-title">Xác thực email</h1>

        <p class="otp-lead">
            Email cần xác thực: <b><?= e($email) ?></b>. Bấm “Gửi lại email xác thực” để nhận thư.
            Bấm vào link trong thư là xong, link dùng được trong <?= (int) $gio_song ?> giờ.
        </p>

        <div class="alert alert-info">
            <b>Không thấy thư?</b> Kiểm tra cả thư mục <b>Spam</b> hoặc <b>Quảng cáo</b>
            (Gmail). Nếu thấy, bấm "Không phải thư rác" để lần sau thư vào thẳng hộp thư đến.
        </div>

        <form method="post" action="<?= site_url('xac-thuc/gui-lai') ?>" class="auth-form">
            <div class="auth-actions">
                <button type="submit" class="btn btn-primary" id="verify-resend"
                        data-wait="<?= (int) $cho_giay ?>">Gửi lại email xác thực</button>
                <a class="btn btn-ghost" href="<?= site_url('tai-khoan') ?>">Để sau</a>
            </div>
        </form>

        <p class="otp-resend">
            <span class="otp-hint">Trong lúc chờ, bạn vẫn xem hồ sơ và hoàn thiện hồ sơ của mình được.
            Thả tim và nhắn tin sẽ mở ngay khi email được xác thực.</span>
        </p>
    </div>
</div>

<script>
(function () {
    'use strict';
    // Đếm ngược nút gửi lại để người dùng biết còn phải chờ bao lâu
    var btn = document.getElementById('verify-resend');
    if (!btn) { return; }
    var conLai = parseInt(btn.getAttribute('data-wait'), 10) || 0;
    var chuGoc = btn.textContent;

    function ve() {
        if (conLai <= 0) {
            btn.textContent = chuGoc;
            btn.disabled = false;
            return;
        }
        btn.textContent = 'Gửi lại sau ' + conLai + 's';
        btn.disabled = true;
        conLai--;
        setTimeout(ve, 1000);
    }
    ve();
})();
</script>
