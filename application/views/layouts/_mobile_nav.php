<?php defined('BASEPATH') OR exit('No direct script access allowed');
$nav_path = trim($this->uri->uri_string(), '/');
$mobile_items = array(
    array('', 'Trang chủ', 'home'),
    array('hen-ho', 'Hẹn hò', 'match'),
    array('swipe-match', 'Ghép đôi', 'sparkles'),
    array('tai-khoan/tin-nhan', 'Tin nhắn', 'message'),
    array('tai-khoan/ho-so', 'Hồ sơ', 'user'),
);
?>
<nav class="tk-bnav site-mobile-nav<?= $nav_path === 'swipe-match' ? ' site-mobile-nav--swipe' : '' ?>" aria-label="Điều hướng nhanh">
    <ul>
        <?php foreach ($mobile_items as $item): ?>
            <?php $active = $nav_path === $item[0]; ?>
            <li>
                <a href="<?= site_url($item[0]) ?>"<?= $active ? ' class="is-active" aria-current="page"' : '' ?>>
                    <?= tk_icon($item[2]) ?>
                    <span><?= e($item[1]) ?></span>
                    <?php if ($item[0] === 'tai-khoan/ho-so' && $user): ?>
                        <?php $interest_count = (int) ($account_menu_counts['interest'] ?? 0); ?>
                        <span class="tk-dot-count" data-account-menu-count="interest" <?= $interest_count ? '' : 'hidden' ?> aria-label="<?= $interest_count ?> mục quan tâm chưa xem"><?= $interest_count > 99 ? '99+' : $interest_count ?></span>
                    <?php endif; ?>
                    <?php if ($item[0] === 'tai-khoan/tin-nhan' && $user): ?>
                        <?php $message_count = (int) ($account_menu_counts['msg'] ?? 0); ?>
                        <span class="tk-dot-count" data-account-menu-count="msg" <?= $message_count ? '' : 'hidden' ?> aria-label="<?= $message_count ?> tin nhắn chưa đọc"><?= $message_count > 99 ? '99+' : $message_count ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
