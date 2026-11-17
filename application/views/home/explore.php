<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="explore-page" aria-labelledby="explore-title">
    <header class="explore-page__header">
        <h1 id="explore-title">Khám phá</h1>
        <a href="<?= site_url() ?>">Trang chủ</a>
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
</section>
