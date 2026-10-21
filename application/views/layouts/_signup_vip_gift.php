<?php defined('BASEPATH') OR exit('No direct script access allowed');
if (!$user || (int) $this->session->userdata('signup_vip_gift_user') !== (int) $user['id']) return;
$this->session->unset_userdata('signup_vip_gift_user');
?>
<dialog class="vip-benefits signup-vip-gift" id="signup-vip-gift" aria-labelledby="signup-vip-gift-title">
    <span class="vip-benefits__crown" aria-hidden="true"><?= tk_icon('crown') ?></span>
    <h2 id="signup-vip-gift-title">Bạn đã nhận được 30 ngày VIP!</h2>
    <p>Quà chào mừng từ Saigon Cupid dành riêng cho bạn. VIP đã được kích hoạt miễn phí ngay khi đăng ký.</p>
    <ul><li>Xem ai đã thích và ghé thăm hồ sơ của bạn.</li><li>Mở thông tin liên hệ không tốn xu.</li><li>Nhãn VIP và viền avatar nổi bật.</li></ul>
    <p class="vip-benefits__expiry">VIP đến <?= e(date('H:i · d/m/Y', strtotime($user['vip_expired_at']))) ?></p>
    <form method="dialog"><button type="submit" autofocus>Khám phá ngay</button></form>
</dialog>
<script defer src="<?= base_url('assets/site/js/signup-vip-gift.js') ?>?v=<?= filemtime(FCPATH . 'assets/site/js/signup-vip-gift.js') ?>"></script>
