<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var string $name @var int $so @var string $link */ ?>
<p style="margin:0 0 14px; font-size:19px; font-weight:700;">
    <?= (int) $so ?> người đã xem hồ sơ của bạn
</p>
<p style="margin:0 0 22px; color:#6d6d6d;">
    Hồ sơ của bạn đang được chú ý. Ghé xem họ là ai và thả tim nếu thấy hợp nhé.
</p>

<?php $this->load->view('emails/_cta', array('url' => $link, 'label' => 'Xem ai đã xem bạn')); ?>
