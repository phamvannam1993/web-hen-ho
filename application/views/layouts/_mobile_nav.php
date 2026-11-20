<?php defined('BASEPATH') OR exit('No direct script access allowed');
$nav_path = trim($this->uri->uri_string(), '/');
$mobile_items = array(
    array('', 'Trang chủ', 'home'),
    array('hen-ho', 'Hẹn hò', 'match'),
    array('kham-pha', 'Khám phá', ''),
    array('messenger', 'Tin nhắn', 'message'),
    array('tai-khoan/ho-so', 'Hồ sơ', 'user'),
);
?>
<nav class="tk-bnav site-mobile-nav<?= $nav_path === 'swipe-match' ? ' site-mobile-nav--swipe' : ($nav_path === 'kham-pha' ? ' site-mobile-nav--explore' : '') ?>" aria-label="Điều hướng nhanh">
    <ul>
        <?php foreach ($mobile_items as $item): ?>
            <?php
            $is_explore = $item[0] === 'kham-pha';
            $active = $is_explore
                ? ($nav_path === 'kham-pha' || $nav_path === 'swipe-match' || strpos($nav_path, 'swipe-match/') === 0 || $nav_path === 'clubs' || strpos($nav_path, 'clubs/') === 0)
                : ($nav_path === $item[0] || ($item[0] === 'hen-ho' && strpos($nav_path, 'hen-ho/') === 0));
            ?>
            <li>
                <?php if ($is_explore): ?>
                <a href="<?= site_url($item[0]) ?>"<?= $active ? ' class="is-active"' : '' ?><?= $nav_path === 'kham-pha' ? ' aria-current="page"' : '' ?>>
                    <svg class="tk-ic" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <rect x="2" y="3" width="8" height="8" rx="1.5"/>
                        <rect x="14" y="2" width="8" height="8" rx="1.5" transform="rotate(35 18 6)"/>
                        <rect x="2" y="14" width="8" height="8" rx="1.5"/>
                        <rect x="14" y="14" width="8" height="8" rx="1.5"/>
                    </svg>
                    <span><?= e($item[1]) ?></span>
                </a>
                <?php else: ?>
                <a href="<?= site_url($item[0]) ?>"<?= $active ? ' class="is-active" aria-current="page"' : '' ?>>
                    <?= tk_icon($item[2]) ?>
                    <span><?= e($item[1]) ?></span>
                    <?php if ($item[0] === 'tai-khoan/ho-so' && $user): ?>
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
