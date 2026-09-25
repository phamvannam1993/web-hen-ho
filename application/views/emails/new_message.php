<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var string $name @var string $sender @var string $avatar @var string $preview @var string $link */ ?>
<p style="margin:0 0 20px; font-size:19px; font-weight:700;"><?= e($sender) ?> đã nhắn tin cho bạn</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#f7f8fa; border-radius:12px; margin:0 0 22px;">
<tr>
    <td width="70" valign="top" style="padding:16px 0 16px 16px;">
        <img src="<?= $avatar ?>" width="52" height="52" alt=""
             style="width:52px; height:52px; border-radius:50%; display:block;">
    </td>
    <td valign="top" style="padding:16px 16px 16px 0;">
        <b style="font-size:15px;"><?= e($sender) ?></b><br>
        <span style="color:#46505f; font-size:14.5px;">“<?= e($preview) ?>”</span>
    </td>
</tr>
</table>

<?php $this->load->view('emails/_cta', array('url' => $link, 'label' => 'Trả lời ngay')); ?>
