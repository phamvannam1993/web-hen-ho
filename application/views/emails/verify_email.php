<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var string $name  @var string $link  @var int $hours */ ?>
<p style="margin:0 0 14px; font-size:18px; font-weight:700;">Xác thực email của bạn</p>

<p style="margin:0 0 16px;">
    Xin chào <b><?= e($name) ?></b>,
</p>

<p style="margin:0 0 22px;">
    Cảm ơn bạn đã đăng ký. Bấm nút bên dưới để xác thực email — chỉ một chạm,
    không cần nhập mã. Xác thực xong bạn sẽ thả tim và nhắn tin được ngay.
</p>

<?php $this->load->view('emails/_cta', array('url' => $link, 'label' => 'Xác thực email')); ?>

<p style="margin:0 0 20px; font-size:13px; color:#6d6d6d; text-align:center; word-break:break-all;">
    Nút không bấm được? Chép link này vào trình duyệt:<br>
    <a href="<?= $link ?>" style="color:#d1273f;"><?= e($link) ?></a>
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#fff8e6; border-left:4px solid #f0ad2e; border-radius:6px;">
<tr><td style="padding:13px 16px; font-size:13.5px; color:#6b5a2a;">
    Link có hiệu lực trong <b><?= (int) $hours ?> giờ</b> và chỉ dùng được <b>một lần</b>.
    Nếu bạn không đăng ký tài khoản, hãy bỏ qua thư này.
</td></tr>
</table>
