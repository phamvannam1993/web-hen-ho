<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var string $name @var string $match_name @var string $my_avatar @var string $match_avatar @var string $link */ ?>
<p style="margin:0 0 8px; font-size:21px; font-weight:700; text-align:center;">Ghép đôi thành công!</p>
<p style="margin:0 0 22px; text-align:center; color:#6d6d6d;">
    <?= e($name) ?> và <?= e($match_name) ?> đã thích nhau.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 24px;">
<tr>
    <td><img src="<?= $my_avatar ?>" width="78" height="78" alt=""
             style="width:78px; height:78px; border-radius:50%; display:block; border:3px solid #f7ccd3;"></td>
    <td style="padding:0 14px; font-size:26px; color:#d1273f;">&#9829;</td>
    <td><img src="<?= $match_avatar ?>" width="78" height="78" alt=""
             style="width:78px; height:78px; border-radius:50%; display:block; border:3px solid #f7ccd3;"></td>
</tr>
</table>

<?php $this->load->view('emails/_cta', array('url' => $link, 'label' => 'Nhắn tin ngay')); ?>
