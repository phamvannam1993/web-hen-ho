<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<p style="margin:0 0 14px; font-size:18px; font-weight:700;">Đặt lại mật khẩu</p>

<p style="margin:0 0 16px;">
    Xin chào <b><?= e($name) ?></b>,
</p>

<p style="margin:0 0 20px;">
    Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.
    Bấm nút bên dưới để tạo mật khẩu mới.
</p>

<!-- Nút bấm: dùng bảng để Outlook hiển thị đúng -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 22px;">
<tr><td align="center" style="border-radius:26px; background:#e91e8c;">
    <a href="<?= $link ?>"
       style="display:inline-block; padding:13px 34px; color:#ffffff; font-size:15.5px;
              font-weight:700; text-decoration:none; border-radius:26px;">
        Đặt lại mật khẩu
    </a>
</td></tr>
</table>

<p style="margin:0 0 8px; color:#6d6d6d; font-size:13.5px;">
    Nút không bấm được? Sao chép đường dẫn sau vào trình duyệt:
</p>
<p style="margin:0 0 22px; padding:11px 14px; background:#faf7f9; border-radius:8px;
          word-break:break-all; font-size:13px; color:#c2126f;">
    <?= $link ?>
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#fff8e6; border-left:4px solid #f0ad2e; border-radius:6px;">
<tr><td style="padding:13px 16px; font-size:13.5px; color:#6b5a2a;">
    Liên kết chỉ dùng được <b>một lần</b> và hết hạn sau <b><?= (int) $hours ?> giờ</b>.
    Nếu bạn không yêu cầu đổi mật khẩu, hãy bỏ qua thư này &mdash; tài khoản của bạn vẫn an toàn.
</td></tr>
</table>
