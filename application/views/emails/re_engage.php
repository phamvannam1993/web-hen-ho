<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var string $name @var int $so @var array $avatars @var string $link */ ?>
<p style="margin:0 0 14px; font-size:19px; font-weight:700;">
    <?= e($name) ?>, tiếp tục khám phá kết nối tại Saigon Cupid
</p>
<p style="margin:0 0 22px; color:#6d6d6d;">
    <?= $so > 0
        ? 'Bạn có <b>' . (int) $so . ' lượt thích</b>. Ghé xem và chọn người bạn muốn kết nối.'
        : 'Ghé xem cộng đồng, cập nhật tiêu chí tìm bạn hoặc tham gia Nhóm Hẹn Hò 4 Phương để bắt đầu trò chuyện.' ?>
</p>

<?php if (!empty($avatars)): ?>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 22px;">
    <tr>
        <?php foreach (array_slice($avatars, 0, 3) as $a): ?>
            <td style="padding:0 6px;">
                <img src="<?= $a ?>" width="66" height="66" alt=""
                     style="width:66px; height:66px; border-radius:50%; display:block;
                            border:3px solid #f7ccd3; opacity:.45;">
            </td>
        <?php endforeach; ?>
    </tr>
    </table>
<?php endif; ?>

<?php $this->load->view('emails/_cta', array('url' => $link, 'label' => 'Xem ngay')); ?>
