<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var string $name  @var string $code  @var int $minutes  @var string $purpose */
$la_dang_ky = ($purpose ?? '') === 'register';
?>
<p style="margin:0 0 14px; font-size:18px; font-weight:700;">
    <?= $la_dang_ky ? 'Xác thực email của bạn' : 'Mã đăng nhập một lần' ?>
</p>

<p style="margin:0 0 16px;">
    Xin chào <b><?= e($name) ?></b>,
</p>

<p style="margin:0 0 22px;">
    <?= $la_dang_ky
        ? 'Cảm ơn bạn đã đăng ký. Nhập mã bên dưới để xác thực email và kích hoạt tài khoản.'
        : 'Đây là mã xác minh cho lần đăng nhập vừa rồi. Nhập mã bên dưới để hoàn tất.' ?>
</p>

<!-- Ô mã: dùng bảng để Outlook canh giữa đúng -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 22px;">
<tr><td align="center"
        style="padding:18px 38px; background:#faf7f9; border:1px dashed #e7c3cb; border-radius:12px;">
    <span style="font-family:'Courier New',Courier,monospace; font-size:34px; font-weight:700;
                 letter-spacing:10px; color:#b21f35;"><?= e($code) ?></span>
</td></tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#fff8e6; border-left:4px solid #f0ad2e; border-radius:6px;">
<tr><td style="padding:13px 16px; font-size:13.5px; color:#6b5a2a;">
    Mã có hiệu lực trong <b><?= (int) $minutes ?> phút</b> và chỉ dùng được <b>một lần</b>.
    Tuyệt đối không chia sẻ mã này cho bất kỳ ai, kể cả người xưng là nhân viên hỗ trợ.
    <?= $la_dang_ky
        ? 'Nếu bạn không đăng ký tài khoản, hãy bỏ qua thư này.'
        : 'Nếu bạn không thực hiện đăng nhập, hãy đổi mật khẩu ngay.' ?>
</td></tr>
</table>
