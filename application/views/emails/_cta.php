<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Nút bấm dạng bảng để Outlook hiển thị đúng. @var string $url @var string $label */
$phu = !empty($phu);
?>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 14px;">
<tr><td align="center" style="border-radius:26px; background:<?= $phu ? '#ffffff' : '#d1273f' ?>;
        <?= $phu ? 'border:1.5px solid #d1273f;' : '' ?>">
    <a href="<?= $url ?>" style="display:inline-block; padding:13px 34px; border-radius:26px;
            color:<?= $phu ? '#d1273f' : '#ffffff' ?>; font-size:15.5px; font-weight:700; text-decoration:none;">
        <?= e($label) ?>
    </a>
</td></tr>
</table>
