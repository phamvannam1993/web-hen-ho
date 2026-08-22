<?php defined('BASEPATH') OR exit('No direct script access allowed');
$current = $this->uri->segment(2);
$items = array(
    ''          => 'Tổng quan',
    'ho-so'     => 'Hồ sơ của tôi',
    'anh'       => 'Ảnh của tôi',
    'tin-dang'  => 'Tin đăng của tôi',
    'quan-tam'  => 'Quan tâm & ghép đôi',
    'tin-nhan'  => 'Tin nhắn',
    'thong-bao' => 'Thông báo',
    'nap-xu'    => 'Nạp xu / VIP',
    'doi-mat-khau' => 'Đổi mật khẩu',
);
?>
<nav class="account-nav">
    <ul>
        <?php foreach ($items as $slug => $label): ?>
            <li>
                <a class="<?= $current === ($slug ?: null) || (!$current && $slug === '') ? 'active' : '' ?>"
                   href="<?= site_url('tai-khoan' . ($slug ? '/' . $slug : '')) ?>"><?= e($label) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
