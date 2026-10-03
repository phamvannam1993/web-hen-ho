<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Trang Đổi mật khẩu — dựng theo `src/routes/tai-khoan.doi-mat-khau.tsx`.
 * Máy chủ chỉ đòi tối thiểu 6 ký tự và hai ô mới phải khớp; các mục còn lại
 * trong bảng yêu cầu chỉ là khuyến nghị, không chặn gửi.
 */
$sao = '<span class="tk-req">*</span>';
?>
<div class="tk-ph">
    <div class="tk-ph__b">
        <h1>Đổi mật khẩu</h1>
        <p>Nên dùng mật khẩu riêng, không trùng với email.</p>
    </div>
</div>

<form class="tk-pw auth-form" method="post">
    <section class="tk-card">
        <div class="tk-stack tk-stack--sm tk-pw-f">
            <?php if (validation_errors()): ?>
                <div class="tk-alert tk-alert--danger"><?= validation_errors() ?></div>
            <?php endif; ?>

            <div class="tk-field">
                <label for="current">Mật khẩu hiện tại <?= $sao ?></label>
                <div class="tk-input-wrap">
                    <input type="password" id="current" name="current" required data-no-toggle autocomplete="current-password">
                    <button type="button" class="tk-btn tk-btn--ghost tk-btn--icon-sm tk-input-wrap__btn" id="tk-pw-eye"
                            aria-label="Hiện mật khẩu" aria-pressed="false">
                        <span class="tk-pw-eye--on"><?= tk_icon('eye') ?></span>
                        <span class="tk-pw-eye--off" hidden><?= tk_icon('eye-off') ?></span>
                    </button>
                </div>
            </div>

            <div class="tk-field">
                <label for="password">Mật khẩu mới <?= $sao ?></label>
                <input type="password" id="password" name="password" required data-no-toggle autocomplete="new-password">
                <p class="tk-hint">Tối thiểu 6 ký tự, nên có chữ in hoa và số</p>
            </div>

            <div id="tk-pw-meter" hidden>
                <div class="tk-progress"><i style="width:0%"></i></div>
                <p class="tk-hint" style="margin-top:6px;font-weight:600">Độ mạnh: <span></span></p>
            </div>

            <div class="tk-field">
                <label for="password_confirm">Xác nhận mật khẩu mới <?= $sao ?></label>
                <input type="password" id="password_confirm" name="password_confirm" required data-no-toggle autocomplete="new-password">
            </div>
            <p class="tk-pw-miss" id="tk-pw-miss" hidden><?= tk_icon('alert') ?>Hai mật khẩu chưa khớp nhau.</p>

            <div>
                <button class="tk-btn tk-btn--brand tk-btn--lg tk-pw-go" type="submit">Cập nhật mật khẩu</button>
            </div>
        </div>
    </section>

    <section class="tk-card">
        <div class="tk-card__h"><div><h2 class="tk-card__t">Yêu cầu mật khẩu</h2></div></div>
        <ul class="tk-pw-req" id="tk-pw-req">
            <li data-rule="len"><?= tk_icon('check') ?>Ít nhất 6 ký tự</li>
            <li data-rule="upper"><?= tk_icon('check') ?>Có ít nhất một chữ in hoa (khuyến nghị)</li>
            <li data-rule="digit"><?= tk_icon('check') ?>Có ít nhất một chữ số (khuyến nghị)</li>
            <li data-rule="special"><?= tk_icon('check') ?>Có ký tự đặc biệt (khuyến nghị)</li>
        </ul>
        <p class="tk-pw-tip"><?= tk_icon('shield') ?>Mật khẩu được mã hoá trước khi lưu, không ai xem được, kể cả quản trị viên.</p>
    </section>
</form>

<script>
/* Một nút mắt cho cả ba ô (thay password-toggle.js, nên các ô có data-no-toggle); độ mạnh và bảng yêu cầu — chỉ để gợi ý, không chặn gửi */
(function () {
    var cur = document.getElementById('current'), mk = document.getElementById('password'),
        xn = document.getElementById('password_confirm'), eye = document.getElementById('tk-pw-eye'),
        meter = document.getElementById('tk-pw-meter'), miss = document.getElementById('tk-pw-miss'),
        nhan = ['Rất yếu', 'Yếu', 'Trung bình', 'Mạnh', 'Rất mạnh'];

    eye.addEventListener('click', function () {
        var hien = cur.type === 'password';
        [cur, mk, xn].forEach(function (o) { o.type = hien ? 'text' : 'password'; });
        eye.setAttribute('aria-pressed', hien);
        eye.setAttribute('aria-label', hien ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        eye.querySelector('.tk-pw-eye--on').hidden = hien;
        eye.querySelector('.tk-pw-eye--off').hidden = !hien;
    });

    function ve() {
        var v = mk.value, r = {
            len: v.length >= 6, upper: /[A-Z]/.test(v), digit: /[0-9]/.test(v), special: /[^A-Za-z0-9]/.test(v)
        };
        var s = (v.length >= 8) + r.upper + r.digit + r.special;
        document.querySelectorAll('#tk-pw-req li').forEach(function (li) {
            li.classList.toggle('is-ok', r[li.getAttribute('data-rule')]);
        });
        meter.hidden = !v;
        meter.querySelector('i').style.width = (s / 4 * 100) + '%';
        meter.querySelector('span').textContent = nhan[s];
        var lech = xn.value !== '' && xn.value !== v;
        miss.hidden = !lech;
        xn.classList.toggle('is-bad', lech);
    }
    mk.addEventListener('input', ve);
    xn.addEventListener('input', ve);
})();
</script>
