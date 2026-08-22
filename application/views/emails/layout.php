<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($site_name) ?></title>
</head>
<!--
  Email dùng bảng và kiểu nội tuyến vì phần lớn ứng dụng thư (Gmail, Outlook)
  loại bỏ thẻ <style> và không hỗ trợ flexbox hay grid.
-->
<body style="margin:0; padding:0; background:#f1f1f4;
             font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#f1f1f4; padding:28px 12px;">
<tr><td align="center">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="max-width:560px; background:#ffffff; border-radius:14px; overflow:hidden;
                  box-shadow:0 2px 12px rgba(0,0,0,.07);">

        <!-- Đầu thư -->
        <tr>
            <td align="center" style="background:#e91e8c; padding:26px 24px;">
                <span style="display:inline-block; width:42px; height:42px; line-height:42px;
                             border-radius:50%; background:rgba(255,255,255,.22);
                             color:#ffffff; font-size:21px;">&#9829;</span>
                <div style="margin-top:10px; color:#ffffff; font-size:21px; font-weight:700;
                            letter-spacing:.3px;"><?= e($site_name) ?></div>
            </td>
        </tr>

        <!-- Nội dung -->
        <tr>
            <td style="padding:30px 30px 26px; color:#2c2c2c; font-size:15.5px; line-height:1.65;">
                <?= $content ?>
            </td>
        </tr>

        <!-- Chân thư -->
        <tr>
            <td style="background:#fafafb; border-top:1px solid #eeeef2;
                       padding:20px 30px; color:#8a8a94; font-size:12.5px; line-height:1.6;">
                Thư này được gửi tự động, vui lòng không trả lời.<br>
                &copy; <?= date('Y') ?> <?= e($site_name) ?>
                <?php if (setting('hotline')): ?>
                    &nbsp;&middot;&nbsp; Hỗ trợ: <?= e(setting('hotline')) ?>
                <?php endif; ?>
            </td>
        </tr>
    </table>

</td></tr>
</table>
</body>
</html>
