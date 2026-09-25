<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var string $name @var int $so @var array $avatars @var string $link @var bool $mo_anh */ ?>
<p style="margin:0 0 14px; font-size:19px; font-weight:700;">
    <?= e($name) ?>, bạn có <?= (int) $so ?> người mới thích hồ sơ!
</p>
<p style="margin:0 0 22px; color:#6d6d6d;">
    Thả tim lại để mở khung trò chuyện — chỉ khi cả hai cùng thích, hai bạn mới nhắn tin được.
</p>

<?php if (!empty($avatars)): ?>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 22px;">
    <tr>
        <?php foreach (array_slice($avatars, 0, 4) as $a): ?>
            <td style="padding:0 6px;">
                <?php /* Thành viên thường thấy ảnh mờ, xem rõ thì vào web — nhưng làm mờ
                         thật bằng CSS không chạy trong Gmail, nên dùng lớp phủ nhạt */ ?>
                <img src="<?= $a ?>" width="62" height="62" alt=""
                     style="width:62px; height:62px; border-radius:50%; display:block;
                            border:3px solid #f7ccd3;<?= empty($mo_anh) ? ' opacity:.45;' : '' ?>">
            </td>
        <?php endforeach; ?>
        <?php if ($so > 4): ?>
            <td style="padding:0 6px;">
                <span style="display:inline-block; width:62px; height:62px; line-height:62px;
                             border-radius:50%; background:#f7bcc6; color:#b21f35;
                             font-size:14px; font-weight:700; text-align:center;">+<?= (int) $so - 4 ?></span>
            </td>
        <?php endif; ?>
    </tr>
    </table>
<?php endif; ?>

<?php $this->load->view('emails/_cta', array('url' => $link, 'label' => 'Xem ngay')); ?>
