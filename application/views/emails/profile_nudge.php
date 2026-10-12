<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var string $name  @var int $lan  @var array $thieu  @var string $link */ ?>
<p style="margin:0 0 14px; font-size:19px; font-weight:700;">
    <?= $lan <= 1 ? 'Mọi người chưa thấy bạn đâu!' : 'Chỉ còn một chút nữa thôi' ?>
</p>

<p style="margin:0 0 16px;">Chào <?= e($name) ?>,</p>

<p style="margin:0 0 18px;">
    Hồ sơ của bạn đang <b>bị ẩn</b> khỏi danh sách Hẹn hò, Ghép đôi ẩn và Thành viên vì còn thiếu
    <b><?= e(mb_strtolower(implode(', ', $thieu))) ?></b>.
    Thêm vào là hồ sơ hiện ngay — hồ sơ có ảnh nhận nhiều lượt thả tim hơn hẳn.
</p>

<?php $this->load->view('emails/_cta', array('url' => $link, 'label' => 'Hoàn thiện trong 1 phút')); ?>

<p style="margin:0; font-size:13px; color:#6d6d6d; text-align:center;">
    <?= $lan <= 1
        ? 'Chúng tôi sẽ chỉ nhắc thêm một lần nữa.'
        : 'Đây là lần nhắc cuối — chúng tôi sẽ không gửi thêm thư về việc này.' ?>
</p>
