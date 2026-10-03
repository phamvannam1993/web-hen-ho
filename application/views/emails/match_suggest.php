<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Gợi ý ghép đôi định kỳ.
 * Thiếu trường nào thì ẩn hẳn phần đó, không hiện dòng trống.
 */
?>
<p style="margin:0 0 6px; font-size:19px; font-weight:700;">
    <?= e($name) ?>, người phù hợp với bạn hôm nay
</p>
<p style="margin:0 0 22px; color:#6d6d6d;">Gợi ý dựa trên tiêu chí và sở thích bạn đã khai.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="border:1px solid #eeeef2; border-radius:14px; overflow:hidden; margin:0 0 22px;">
<tr><td align="center" style="padding:24px 20px 4px;">
    <img src="<?= $match_avatar ?>" width="140" height="140" alt=""
         style="width:140px; height:140px; border-radius:50%; display:block;
                border:4px solid #f7ccd3;">
</td></tr>
<tr><td align="center" style="padding:14px 20px 0;">
    <div style="font-size:19px; font-weight:700;">
        <?= e($match_name) ?><?= !empty($age) ? ', ' . (int) $age : '' ?>
    </div>
    <?php if (!empty($meta)): ?>
        <div style="margin-top:6px; color:#6d6d6d; font-size:14.5px;"><?= e(implode(' · ', $meta)) ?></div>
    <?php endif; ?>
    <?php if (!empty($score)): ?>
        <div style="margin-top:12px;">
            <span style="display:inline-block; padding:5px 14px; border-radius:999px;
                         background:#fde8ec; color:#b21f35; font-size:13px; font-weight:700;">
                Độ phù hợp: <?= (int) $score ?>%
            </span>
        </div>
    <?php endif; ?>
</td></tr>

<?php if (!empty($tags)): ?>
    <tr><td align="center" style="padding:14px 20px 0;">
        <?php foreach (array_slice($tags, 0, 5) as $t): ?>
            <span style="display:inline-block; margin:0 3px 6px; padding:5px 12px; border-radius:8px;
                         background:#f2f5f9; color:#566374; font-size:13px;"><?= e($t) ?></span>
        <?php endforeach; ?>
    </td></tr>
<?php endif; ?>

<?php if (!empty($bio)): ?>
    <tr><td style="padding:14px 24px 0; color:#46505f; font-size:14.5px; line-height:1.6;
                   font-style:italic; text-align:center;">
        “<?= e($bio) ?>”
    </td></tr>
<?php endif; ?>

<tr><td style="padding:20px;"></td></tr>
</table>

<?php $this->load->view('emails/_cta', array('url' => $link, 'label' => 'Xem hồ sơ')); ?>
<?php $this->load->view('emails/_cta', array('url' => $link_like, 'label' => 'Thả tim ngay', 'phu' => true)); ?>
