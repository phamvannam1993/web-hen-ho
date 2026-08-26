<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container discover-page">
    <header class="discover-head">
        <h1>Khám phá</h1>
        <p>Vuốt sang phải nếu thấy hợp, sang trái để bỏ qua. Thích lẫn nhau sẽ mở khung trò chuyện.</p>
    </header>

    <?php if (empty($candidates)): ?>
        <div class="content-box discover-empty">
            <h2>Hết hồ sơ gợi ý</h2>
            <p>Bạn đã xem hết những người phù hợp hiện có. Thử mở rộng tiêu chí tìm kiếm
                trong <a href="<?= site_url('tai-khoan/ho-so') ?>">hồ sơ của bạn</a>,
                hoặc xem <a href="<?= site_url('thanh-vien') ?>">toàn bộ thành viên</a>.</p>
        </div>
    <?php else: ?>
        <div class="sw-stage">
            <!-- Chồng thẻ: thẻ trên cùng nhận thao tác vuốt -->
            <div class="sw-deck" id="sw-deck" data-remaining="<?= (int) $remaining ?>">
                <?php foreach (array_reverse($candidates) as $c): ?>
                    <?php $this->load->view('discover/_card', array('c' => $c, 'user' => $user)); ?>
                <?php endforeach; ?>
            </div>

            <p class="sw-empty" id="sw-empty" hidden>
                Đã xem hết lượt này. <a href="<?= site_url('kham-pha') ?>">Tải thêm hồ sơ</a>
            </p>

            <div class="sw-controls" id="sw-controls">
                <button type="button" class="sw-btn sw-btn-nope" data-swipe="left" aria-label="Bỏ qua" title="Bỏ qua">
                    <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
                <button type="button" class="sw-btn sw-btn-info" data-swipe="info" aria-label="Xem hồ sơ" title="Xem hồ sơ">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.6v.2"/></svg>
                </button>
                <button type="button" class="sw-btn sw-btn-like" data-swipe="right" aria-label="Thích" title="Thích">
                    <svg viewBox="0 0 24 24"><path d="M12 20.5s-7-4.3-7-9.1A4.4 4.4 0 0 1 12 8a4.4 4.4 0 0 1 7 3.4c0 4.8-7 9.1-7 9.1z"/></svg>
                </button>
            </div>

            <p class="sw-hint">Còn <b id="sw-count"><?= number_format($remaining) ?></b> hồ sơ phù hợp
                · <span class="sw-hint-key">←</span> bỏ qua <span class="sw-hint-key">→</span> thích</p>
        </div>
    <?php endif; ?>

    <?php if ($matches): ?>
        <section class="home-block">
            <h2 class="block-title">Đã ghép đôi</h2>
            <div class="member-grid is-compact">
                <?php foreach ($matches as $m): ?>
                    <?php $this->load->view('members/_card', array('m' => $m)); ?>
                <?php endforeach; ?>
            </div>
            <div class="block-more">
                <a class="btn btn-more" href="<?= site_url('tai-khoan/tin-nhan') ?>">Nhắn tin →</a>
            </div>
        </section>
    <?php endif; ?>
</div>
