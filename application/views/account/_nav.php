<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Cột điều hướng khu Tài khoản.
 *
 * Trước đây là một dải tab ngang: 13 mục nhét vào một hàng nên phải cuộn ngang
 * và chữ bị cắt giữa chừng ("Ai đã xem hồ s…"). Nay xếp dọc theo 5 nhóm, mỗi
 * mục có biểu tượng và số việc chưa xử lý — bám theo bản thiết kế SaigonCupid.
 *
 * Trên màn hẹp CSS tự trải lại thành dải cuộn ngang (xem account.css).
 */
$current = $this->uri->segment(2);

/* Nhóm mục. Khoá mảng là slug sau `tai-khoan/`; chuỗi rỗng là trang Tổng quan.
   `badge` là tên biến đếm — chỉ hiện khi lớn hơn 0. */
$groups = array(
    'Hồ sơ cá nhân' => array(
        ''         => array('Tổng quan',           'dashboard'),
        'ho-so'    => array('Hồ sơ của tôi',       'user'),
        'anh'      => array('Ảnh của tôi',         'images'),
    ),
    'Kết nối' => array(
        'quan-tam'     => array('Quan tâm & ghép đôi', 'match'),
        'ai-thich-ban' => array('Ai đã thích bạn',     'heart',   'liked_count'),
        'ai-xem-ho-so' => array('Ai đã xem hồ sơ',     'eye'),
        'tin-nhan'     => array('Tin nhắn',            'message', 'unread_msg'),
    ),
    'Hoạt động' => array(
        'chuoi'     => array('Chuỗi hoạt động', 'flame'),
        'goi-y'     => array('Lịch sử gợi ý',   'sparkles'),
        'thong-bao' => array('Thông báo',       'bell', 'unread_noti'),
    ),
    'Ví & VIP' => array(
        'nap-xu' => array('Nạp xu / VIP', 'wallet'),
    ),
    'Cài đặt' => array(
        'email'        => array('Cài đặt email', 'mail'),
        'doi-mat-khau' => array('Đổi mật khẩu',  'key'),
    ),
);

// Tin đăng chỉ hiện khi Cấu hình bật lại tính năng — giữ đúng luật của bản cũ
if (!empty($settings['enable_posts'])) {
    $groups['Hồ sơ cá nhân']['tin-dang'] = array('Tin đăng của tôi', 'file');
}

$dem = array(
    'liked_count' => isset($liked_count) ? (int) $liked_count : 0,
    'unread_msg'  => isset($unread_msg)  ? (int) $unread_msg  : 0,
    'unread_noti' => isset($unread_noti) ? (int) $unread_noti : 0,
);
// Cột thật trong bảng users là `is_vip` + `vip_expired_at` (không phải vip_until)
$vip_con_han = !empty($me['is_vip']);
?>
<aside class="tk-side">
    <div class="tk-me">
        <img class="tk-me__av" src="<?= e(avatar_url($me['avatar'] ?? null, $me['gender'] ?? 'other')) ?>"
             alt="" width="44" height="44" loading="lazy">
        <div class="tk-me__b">
            <p class="tk-me__name"><?= e(display_name($me)) ?></p>
            <p class="tk-me__coin">
                <?php if ($vip_con_han): ?>
                    <span class="tk-pill tk-pill--gold"><?= tk_icon('crown') ?>VIP</span>
                <?php else: ?>
                    <span class="tk-pill">Thành viên thường</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <nav class="tk-nav" aria-label="Menu tài khoản">
        <?php foreach ($groups as $nhom => $items): ?>
            <div class="tk-nav__group">
                <p class="tk-nav__label"><?= e($nhom) ?></p>
                <ul>
                    <?php foreach ($items as $slug => $it): ?>
                        <?php
                        $active = ($slug === '' && !$current) || ($slug !== '' && $current === $slug);
                        $so     = isset($it[2]) ? $dem[$it[2]] : 0;
                        ?>
                        <li>
                            <a class="<?= $active ? 'is-active' : '' ?>"
                               <?= $active ? 'aria-current="page"' : '' ?>
                               href="<?= site_url('tai-khoan' . ($slug ? '/' . $slug : '')) ?>">
                                <?= tk_icon($it[1]) ?>
                                <span class="tk-nav__txt"><?= e($it[0]) ?></span>
                                <?php if ($so > 0): ?>
                                    <span class="tk-badge"><?= $so > 99 ? '99+' : $so ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>
