<?php defined('BASEPATH') OR exit('No direct script access allowed');
$profile_progress = tk_ho_so_day_du($me);
$progress = max(0, min(100, (int)$profile_progress['phan_tram']));
$is_vip = member_has_vip($me);
$vip_forever = $is_vip && (super_admin_unlimited($me, 'vip') || empty($me['vip_expired_at']));
$vip_until = $is_vip && !$vip_forever ? date('d/m/Y', strtotime($me['vip_expired_at'])) : null;
$streak_days = (int)($streak['streak'] ?? 0);
$coins = super_admin_unlimited($me, 'coins') ? '∞' : number_format((int)($me['coin_balance'] ?? 0));
$verified = !empty($me['email_verified_at']);
$account_frame = $me['profile_frame'] ?? 'none';
if (!array_key_exists($account_frame, profile_frame_catalog())) $account_frame = 'none';
$stats = array(
    array('Thích bạn', $liked_count, 'tai-khoan/quan-tam?tab=thich-ban'),
    array('Bạn đã thích', $my_liked_count, 'tai-khoan/quan-tam?tab=ban-thich'),
    array('Đã ghép đôi', $match_count, 'tai-khoan/quan-tam?tab=ghep-doi'),
    array('Lượt xem · 7 ngày', $viewer_count, 'tai-khoan/ai-xem-ho-so'),
);
$feature_groups = array(
    'Hồ sơ của bạn' => array(
        array('user', 'Chỉnh sửa hồ sơ', 'Thông tin cá nhân và tiêu chí tìm kiếm', 'tai-khoan/ho-so', null),
        array('images', 'Ảnh của tôi', 'Quản lý hình ảnh trên hồ sơ', 'tai-khoan/anh', null),
        array('eye', 'Xem trang cá nhân', 'Hồ sơ của bạn hiển thị với mọi người', 'profile/' . $me['slug'], null),
        array('crown', 'Khung hồ sơ', 'Chọn khung đại diện của bạn', 'tai-khoan/ho-so#kho-khung', null),
    ),
    'Kết nối & hoạt động' => array(
        array('match', 'Quan tâm & ghép đôi', 'Những kết nối đang chờ bạn', 'tai-khoan/quan-tam', $tk['interest'] ?? 0),
        array('message', 'Tin nhắn', 'Tiếp tục những cuộc trò chuyện', 'messenger', $tk['msg'] ?? $unread_msg),
        array('eye', 'Ai đã xem hồ sơ', 'Xem những người ghé thăm bạn', 'tai-khoan/ai-xem-ho-so', $tk['viewers'] ?? 0),
        array('sparkles', 'Lịch sử gợi ý', 'Xem lại các gợi ý kết nối mỗi ngày', 'tai-khoan/goi-y', null),
        array('flame', 'Chuỗi hoạt động', 'Theo dõi chuỗi và các mốc thưởng', 'tai-khoan/chuoi', null),
    ),
    'Ví & tài khoản' => array(
        array('wallet', 'Ví xu & VIP', 'Nạp xu, nâng cấp và xem lịch sử giao dịch', 'tai-khoan/nap-xu', null),
        array('bell', 'Thông báo', 'Cập nhật hoạt động mới của bạn', 'tai-khoan/thong-bao', $unread_noti),
        array('mail', 'Cài đặt email', 'Quản lý email và tùy chọn nhận thông báo', 'tai-khoan/email', null),
        array('key', 'Đổi mật khẩu', 'Bảo vệ tài khoản của bạn', 'tai-khoan/doi-mat-khau', null),
    ),
);
if (!empty($settings['enable_posts'])) {
    $feature_groups['Hồ sơ của bạn'][] = array('file', 'Tin đăng của tôi', 'Quản lý các tin bạn đã đăng', 'tai-khoan/tin-dang', $post_count);
}
?>
<div class="account-hub">
    <section class="account-hub-identity" aria-labelledby="account-hub-name">
        <div class="account-hub-person">
            <a class="account-hub-avatar<?= $account_frame !== 'none' ? ' has-frame profile-frame-preview profile-frame-preview--' . e($account_frame) : '' ?>" href="<?= site_url('tai-khoan/ho-so') ?>" aria-label="<?= e('Chỉnh sửa ảnh đại diện' . ($account_frame !== 'none' ? ' · Khung ' . profile_frame_catalog()[$account_frame] : '')) ?>">
                <img src="<?= e(avatar_url($me['avatar'] ?? null, $me['gender'] ?? 'other')) ?>" alt="<?= e(display_name($me)) ?>" width="88" height="88">
                <span class="account-hub-avatar-edit" aria-hidden="true"><?= tk_icon('edit') ?></span>
            </a>
            <div class="account-hub-person-info">
                <p class="account-hub-eyebrow">Tài khoản của tôi</p>
                <h1 id="account-hub-name"><?= e(display_name($me)) ?></h1>
                <p class="account-hub-id">ID: <?= (int)$me['id'] ?></p>
                <div class="account-hub-status">
                    <?php if ($is_vip): ?><button type="button" class="account-hub-vip-pill" data-vip-benefits aria-haspopup="dialog" aria-controls="vip-benefits-dialog"><?= tk_icon('crown') ?>Thành viên VIP</button><?php endif; ?>
                    <?php if ($verified): ?><span class="account-hub-verified"><?= tk_icon('shield-check') ?>Đã xác thực email</span>
                    <?php else: ?><a href="<?= site_url('xac-thuc') ?>"><?= tk_icon('shield-check') ?>Xác thực email</a><?php endif; ?>
                </div>
            </div>
            <a class="account-hub-edit" href="<?= site_url('tai-khoan/ho-so') ?>" aria-label="Chỉnh sửa hồ sơ"><?= tk_icon('edit') ?><span>Chỉnh sửa</span></a>
        </div>
        <nav class="account-hub-stats" aria-label="Thống kê kết nối">
            <?php foreach ($stats as $stat): ?>
            <a href="<?= e(site_url($stat[2])) ?>"><strong><?= number_format((int)$stat[1]) ?></strong><span><?= e($stat[0]) ?></span></a>
            <?php endforeach; ?>
        </nav>
    </section>

    <section class="account-hub-shortcuts" aria-label="Thông tin quan trọng">
        <a class="account-hub-shortcut account-hub-shortcut--vip" href="<?= site_url('tai-khoan/nap-xu') ?>">
            <span class="account-hub-shortcut-icon"><?= tk_icon('crown') ?></span>
            <span><strong><?= $is_vip ? 'VIP đang hiệu lực' : 'Nâng cấp VIP' ?></strong><small><?= $is_vip ? ($vip_forever ? 'Quyền lợi VIP không thời hạn' : 'Đến ' . $vip_until) : 'Khám phá quyền lợi dành riêng cho bạn' ?></small></span>
            <?= tk_icon('chevron-right', 'tk-ic account-hub-arrow') ?>
        </a>
        <a class="account-hub-shortcut account-hub-shortcut--wallet" href="<?= site_url('tai-khoan/nap-xu') ?>">
            <span class="account-hub-shortcut-icon"><?= tk_icon('coins') ?></span>
            <span><strong><?= $coins ?> xu</strong><small>Số dư có thể thanh toán</small></span>
            <?= tk_icon('chevron-right', 'tk-ic account-hub-arrow') ?>
        </a>
        <a class="account-hub-shortcut account-hub-shortcut--streak" href="<?= site_url('tai-khoan/chuoi') ?>">
            <span class="account-hub-shortcut-icon"><?= tk_icon('flame') ?></span>
            <span><strong><?= number_format($streak_days) ?> ngày liên tiếp</strong><small>Chuỗi hoạt động của bạn</small></span>
            <?= tk_icon('chevron-right', 'tk-ic account-hub-arrow') ?>
        </a>
        <a class="account-hub-shortcut account-hub-shortcut--notifications" href="<?= site_url('tai-khoan/thong-bao') ?>">
            <span class="account-hub-shortcut-icon"><?= tk_icon('bell') ?></span>
            <span><strong><?= number_format((int)$unread_noti) ?> thông báo mới</strong><small>Cập nhật những hoạt động gần đây</small></span>
            <?= tk_icon('chevron-right', 'tk-ic account-hub-arrow') ?>
        </a>
    </section>

    <a class="account-hub-completeness" href="<?= site_url('tai-khoan/ho-so') ?>">
        <span><?= tk_icon('user') ?><strong>Hồ sơ hoàn thiện <?= $progress ?>%</strong></span>
        <span class="account-hub-progress" role="progressbar" aria-label="Mức độ hoàn thiện hồ sơ" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $progress ?>"><span style="width:<?= $progress ?>%"></span></span>
        <small><?= $progress < 100 ? 'Bổ sung thông tin để có thêm kết nối phù hợp' : 'Hồ sơ của bạn đã sẵn sàng kết nối' ?><?= tk_icon('chevron-right') ?></small>
    </a>

    <div class="account-hub-feature-groups">
        <?php foreach ($feature_groups as $group_title => $features): ?>
        <section class="account-hub-feature-group" aria-label="<?= e($group_title) ?>">
            <h2><?= e($group_title) ?></h2>
            <ul>
                <?php foreach ($features as $feature): ?>
                <li><a class="account-hub-feature" href="<?= e(site_url($feature[3])) ?>">
                    <span class="account-hub-feature-icon"><?= tk_icon($feature[0]) ?></span>
                    <span class="account-hub-feature-text"><strong><?= e($feature[1]) ?></strong><small><?= e($feature[2]) ?></small></span>
                    <?php if ((int)$feature[4] > 0): ?><span class="account-hub-count"><?= (int)$feature[4] > 99 ? '99+' : (int)$feature[4] ?></span><?php endif; ?>
                    <?= tk_icon('chevron-right', 'tk-ic account-hub-arrow') ?>
                </a></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endforeach; ?>
    </div>
    <a class="account-hub-logout" href="<?= site_url('dang-xuat') ?>"><?= tk_icon('logout') ?>Đăng xuất</a>
</div>
