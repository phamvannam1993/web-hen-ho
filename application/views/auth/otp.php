<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var string $email  @var string $purpose  @var int $cho_giay  @var int $phut_song */
$la_dang_ky = $purpose === 'register';
?>
<div class="container">
    <div class="auth-card">
        <h1 class="auth-title"><?= $la_dang_ky ? 'Xác thực email' : 'Xác minh đăng nhập' ?></h1>

        <p class="otp-lead">
            Chúng tôi vừa gửi mã gồm <b>6 chữ số</b> tới <b><?= e($email) ?></b>.
            Mã có hiệu lực trong <?= (int) $phut_song ?> phút.
        </p>

        <?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

        <form method="post" class="auth-form" id="otp-form">
            <label for="code">Mã xác thực</label>
            <?php /* inputmode numeric để điện thoại bật sẵn bàn phím số */ ?>
            <input type="text" id="code" name="code" class="otp-input"
                   inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*"
                   maxlength="6" placeholder="······" required autofocus>

            <div class="auth-actions">
                <button type="submit" class="btn btn-primary">Xác nhận</button>
            </div>
        </form>

        <p class="otp-resend">
            Không nhận được thư?
            <a href="<?= site_url('xac-thuc/gui-lai') ?>" id="otp-resend"
               data-wait="<?= (int) $cho_giay ?>">Gửi lại mã</a>
            <span class="otp-hint">Nhớ kiểm tra cả mục Spam.</span>
        </p>

        <p class="auth-foot">
            <a href="<?= site_url($la_dang_ky ? 'dang-ky' : 'dang-nhap') ?>">&larr; Quay lại</a>
        </p>
    </div>
</div>

<script>
(function () {
    'use strict';

    // Chỉ cho gõ số, và đủ 6 chữ số thì gửi luôn cho đỡ phải bấm nút
    var o = document.getElementById('code');
    var form = document.getElementById('otp-form');
    if (o) {
        o.addEventListener('input', function () {
            o.value = o.value.replace(/\D+/g, '').slice(0, 6);
            if (o.value.length === 6) { form.submit(); }
        });
    }

    // Đếm ngược nút gửi lại để người dùng biết còn phải chờ bao lâu
    var link = document.getElementById('otp-resend');
    if (!link) { return; }
    var conLai = parseInt(link.getAttribute('data-wait'), 10) || 0;
    var chuGoc = link.textContent;

    function ve() {
        if (conLai <= 0) {
            link.textContent = chuGoc;
            link.classList.remove('is-off');
            return;
        }
        link.textContent = 'Gửi lại mã sau ' + conLai + 's';
        link.classList.add('is-off');
        conLai--;
        setTimeout(ve, 1000);
    }
    link.addEventListener('click', function (e) {
        if (conLai > 0) { e.preventDefault(); }
    });
    ve();
})();
</script>
