<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var string $name @var string $link */ ?>
<p style="margin:0 0 14px; font-size:19px; font-weight:700;">Chào mừng <?= e($name) ?>!</p>

<p style="margin:0 0 20px;">
    Cảm ơn bạn đã tham gia <?= e($site_name) ?>. Chỉ ba bước là bạn có thể bắt đầu
    tìm người phù hợp với mình.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
    <?php
    $buoc = array(
        array('1', 'Hoàn thiện hồ sơ', 'Thêm ảnh và vài dòng giới thiệu — hồ sơ đầy đủ được gợi ý nhiều hơn hẳn.'),
        array('2', 'Xem gợi ý ghép đôi', 'Chúng tôi chọn sẵn những người hợp tiêu chí của bạn.'),
        array('3', 'Gửi lời chào', 'Thả tim trước; khi cả hai cùng thích nhau thì khung chat mở ra.'),
    );
    foreach ($buoc as $b): ?>
        <tr>
            <td width="34" valign="top" style="padding:0 0 14px;">
                <span style="display:inline-block; width:26px; height:26px; line-height:26px;
                             border-radius:50%; background:#fce8ec; color:#b21f35;
                             font-size:13px; font-weight:700; text-align:center;"><?= $b[0] ?></span>
            </td>
            <td valign="top" style="padding:0 0 14px;">
                <b style="font-size:15px;"><?= e($b[1]) ?></b><br>
                <span style="color:#6d6d6d; font-size:14px;"><?= e($b[2]) ?></span>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php $this->load->view('emails/_cta', array('url' => $link, 'label' => 'Khám phá ngay')); ?>
