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
            <?php
            $is_explore = $item[0] === 'explore';
            $active = $is_explore
                ? ($nav_path === 'swipe-match' || strpos($nav_path, 'swipe-match/') === 0 || $nav_path === 'clubs' || strpos($nav_path, 'clubs/') === 0)
                : ($nav_path === $item[0] || ($item[0] === 'hen-ho' && strpos($nav_path, 'hen-ho/') === 0));
            ?>
            <li>
                <?php if ($is_explore): ?>
                <button type="button" id="mobile-explore-trigger"<?= $active ? ' class="is-active"' : '' ?> aria-haspopup="dialog" aria-controls="mobile-explore-dialog" aria-expanded="false">
                    <svg class="tk-ic" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <rect x="2" y="3" width="8" height="8" rx="1.5"/>
                        <rect x="14" y="2" width="8" height="8" rx="1.5" transform="rotate(35 18 6)"/>
                        <rect x="2" y="14" width="8" height="8" rx="1.5"/>
                        <rect x="14" y="14" width="8" height="8" rx="1.5"/>
                    </svg>
                    <span><?= e($item[1]) ?></span>
                </button>
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
                        <span class="tk-dot-count" data-account-menu-count="msg" <?= $message_count ? '' : 'hidden' ?> aria-label="<?= $message_count ?> tin nhắn chưa đọc"><?= $message_count > 99 ? '99+' : $message_count ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
<dialog class="mobile-explore" id="mobile-explore-dialog" aria-labelledby="mobile-explore-title">
    <header class="mobile-explore__header">
        <h2 id="mobile-explore-title">Khám phá</h2>
        <form method="dialog">
            <button type="submit" aria-label="Đóng khám phá" autofocus>Đóng</button>
        </form>
    </header>
    <a class="mobile-explore__item" href="<?= site_url('swipe-match') ?>">
        <span class="mobile-explore__icon" aria-hidden="true"><?= tk_icon('sparkles') ?></span>
        <span><strong>Ghép đôi ẩn</strong><small>Khám phá những người phù hợp với bạn</small></span>
        <span aria-hidden="true">›</span>
    </a>
    <a class="mobile-explore__item" href="<?= site_url('clubs/hen-ho') ?>">
        <span class="mobile-explore__icon" aria-hidden="true"><?= tk_icon('message') ?></span>
        <span><strong>Chat's ẩn danh</strong><small>Trò chuyện, làm quen những người bạn mới</small></span>
        <span aria-hidden="true">›</span>
    </a>
    <?php if ($user): ?>
    <a class="mobile-explore__item" href="<?= site_url('tai-khoan/quan-tam?tab=thich-ban') ?>">
        <span class="mobile-explore__icon" aria-hidden="true"><?= tk_icon('heart') ?></span>
        <span><strong>Người thích tôi</strong><small>Xem những người đã thích bạn</small></span>
        <span aria-hidden="true">›</span>
    </a>
    <a class="mobile-explore__item" href="<?= site_url('tai-khoan/quan-tam?tab=ban-thich') ?>">
        <span class="mobile-explore__icon" aria-hidden="true"><?= tk_icon('heart') ?></span>
        <span><strong>Người tôi thích</strong><small>Xem những người bạn đã thích</small></span>
        <span aria-hidden="true">›</span>
    </a>
    <?php endif; ?>
</dialog>
<script defer src="<?= base_url('assets/site/js/mobile-nav.js') ?>?v=<?= filemtime(FCPATH.'assets/site/js/mobile-nav.js') ?>"></script>
