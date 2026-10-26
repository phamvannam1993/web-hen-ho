<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<dialog class="vip-benefits" id="vip-benefits-dialog" aria-labelledby="vip-benefits-title">
    <span class="vip-benefits__crown" aria-hidden="true"><?= tk_icon('crown') ?></span>
    <h2 id="vip-benefits-title">Quyền lợi thành viên VIP</h2>
    <?php if (super_admin_unlimited($user, 'vip')): ?>
        <p class="vip-benefits__expiry">VIP vĩnh viễn · Không hết hạn</p>
    <?php elseif (!empty($user['vip_expired_at'])): ?>
        <p class="vip-benefits__expiry">Hết hạn lúc <?= e(date('H:i · d/m/Y', strtotime($user['vip_expired_at']))) ?></p>
    <?php endif; ?>
    <ul>
        <li>Mở thông tin liên hệ không giới hạn, không tốn xu.</li>
        <li>Xem đầy đủ ai đã thích bạn và thả tim lại.</li>
        <li>Xem ai đã xem hồ sơ và thời điểm xem.</li>
        <li>Nhắn tin cho người chỉ nhận tin từ thành viên VIP.</li>
        <li>Viền avatar và nhãn VIP nổi bật trong tài khoản và nhóm chat.</li>
    </ul>
    <p class="vip-benefits__note">Xem hồ sơ thật trong nhóm ẩn danh vẫn cần Xu mỗi lần.</p>
    <form method="dialog"><button type="submit" autofocus>Đã hiểu</button></form>
</dialog>
