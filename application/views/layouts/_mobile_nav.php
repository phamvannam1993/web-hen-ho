<?php defined('BASEPATH') OR exit('No direct script access allowed');
$nav_path = trim($this->uri->uri_string(), '/');
$mobile_items = array(
    array('', 'Trang chủ', 'home'),
    array('hen-ho', 'Hẹn hò', 'match'),
    array('explore', 'Khám phá', ''),
    array('messenger', 'Tin nhắn', 'message'),
    array('tai-khoan/ho-so', 'Hồ sơ', 'user'),
);
?>
<nav class="tk-bnav site-mobile-nav<?= $nav_path === 'swipe-match' ? ' site-mobile-nav--swipe' : '' ?>" aria-label="Điều hướng nhanh">
    <ul>
        <?php foreach ($mobile_items as $item): ?>
            <?php if ($item[0] === 'explore'): ?>
                <li>
                    <button type="button" class="site-explore-trigger"
                            aria-haspopup="dialog" aria-controls="site-explore">
                        <svg class="tk-ic" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.7"
                             stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                            <path d="m17.5 2 4.5 4.5-4.5 4.5L13 6.5Z"/>
                            <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                            <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                        </svg>
                        <span>Khám phá</span>
                    </button>
                </li>
                <?php continue; ?>
            <?php endif; ?>
            <?php $active = $nav_path === $item[0]; ?>
            <li>
                <a href="<?= site_url($item[0]) ?>"<?= $active ? ' class="is-active" aria-current="page"' : '' ?>>
                    <?= tk_icon($item[2]) ?>
                    <span><?= e($item[1]) ?></span>
                    <?php if ($item[0] === 'tai-khoan/ho-so' && $user): ?>
                        <?php $interest_count = (int) ($account_menu_counts['interest'] ?? 0); ?>
                        <span class="tk-dot-count" data-account-menu-count="interest" <?= $interest_count ? '' : 'hidden' ?> aria-label="<?= $interest_count ?> mục quan tâm chưa xem"><?= $interest_count > 99 ? '99+' : $interest_count ?></span>
                    <?php endif; ?>
                    <?php if ($item[0] === 'messenger' && $user): ?>
                        <?php $message_count = (int) ($account_menu_counts['msg'] ?? 0); ?>
                        <span class="tk-dot-count" data-account-menu-count="msg" <?= $message_count ? '' : 'hidden' ?> aria-label="<?= $message_count ?> tin nhắn chưa đọc"><?= $message_count > 99 ? '99+' : $message_count ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
<dialog id="site-explore" class="site-explore" aria-labelledby="site-explore-title">
    <header class="site-explore-header">
        <h2 id="site-explore-title">Khám phá</h2>
        <form method="dialog">
            <button type="submit" autofocus>Đóng <span aria-hidden="true">×</span></button>
        </form>
    </header>
    <ul class="site-explore-list">
        <li><a href="<?= site_url('clubs') ?>">
            <?= tk_icon('message') ?><span>Chat's ẩn danh</span>
            <?= tk_icon('chevron-right') ?>
        </a></li>
        <li><a href="<?= site_url('swipe-match') ?>">
            <?= tk_icon('sparkles') ?><span>Ghép đôi ẩn</span>
            <?= tk_icon('chevron-right') ?>
        </a></li>
    </ul>
</dialog>
