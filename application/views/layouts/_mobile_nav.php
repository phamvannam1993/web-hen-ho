<?php defined('BASEPATH') OR exit('No direct script access allowed');
$nav_path = trim($this->uri->uri_string(), '/');
$mobile_items = array(
    array('', 'Trang chủ', 'home'),
    array('hen-ho', 'Hẹn hò', 'match'),
    array('clubs', 'Clubs', ''),
    array('messenger', 'Tin nhắn', 'message'),
    array('tai-khoan', 'Hồ sơ', 'user'),
);
?>
<nav class="tk-bnav site-mobile-nav<?= $nav_path === 'swipe-match' ? ' site-mobile-nav--swipe' : ($nav_path === 'kham-pha' ? ' site-mobile-nav--explore' : '') ?>" aria-label="Điều hướng nhanh">
    <ul>
        <?php foreach ($mobile_items as $item): ?>
            <?php
            $is_clubs = $item[0] === 'clubs';
            $active = $is_clubs
                ? ($nav_path === 'clubs' || strpos($nav_path, 'clubs/') === 0)
                : ($nav_path === $item[0] || ($item[0] === 'hen-ho' && strpos($nav_path, 'hen-ho/') === 0) || ($item[0] === 'tai-khoan' && strpos($nav_path, 'tai-khoan/') === 0));
            ?>
            <li>
                <?php if ($is_clubs): ?>
                <a href="<?= site_url($item[0]) ?>"<?= $active ? ' class="is-active" aria-current="page"' : '' ?>>
                    <svg class="tk-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6M21 20v-2a6 6 0 0 0-4-5.65"/>
                    </svg>
                    <span><?= e($item[1]) ?></span>
                </a>
                <?php else: ?>
                <a href="<?= site_url($item[0]) ?>"<?= $active ? ' class="is-active" aria-current="page"' : '' ?>>
                    <?= tk_icon($item[2]) ?>
                    <span><?= e($item[1]) ?></span>
                    <?php if ($item[0] === 'tai-khoan' && $user): ?>
                        <?php $interest_count = (int) ($account_menu_counts['interest'] ?? 0); ?>
                        <span class="tk-dot-count" data-account-menu-count="interest" <?= $interest_count ? '' : 'hidden' ?> aria-label="<?= $interest_count ?> mục quan tâm chưa xem"><?= $interest_count > 99 ? '99+' : $interest_count ?></span>
                    <?php endif; ?>
                    <?php if ($item[0] === 'messenger' && $user): ?>
                        <?php $message_count = (int) ($account_menu_counts['msg'] ?? 0); ?>
                        <span class="tk-dot-count" data-account-menu-count="msg" role="status" aria-live="polite" aria-atomic="true" <?= $message_count ? '' : 'hidden' ?> aria-label="<?= $message_count ?> tin nhắn chưa đọc"><?= $message_count > 99 ? '99+' : $message_count ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
