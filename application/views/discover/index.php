<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container discover-page">
    <header class="discover-head">
        <h1>Khám phá</h1>
        <p>Hồ sơ được sắp xếp theo mức tương hợp với bạn. Thích lẫn nhau sẽ mở khung trò chuyện.</p>
        <p class="discover-count">Còn <b><?= number_format($remaining) ?></b> hồ sơ phù hợp</p>
    </header>

    <?php if (empty($candidates)): ?>
        <div class="content-box discover-empty">
            <h2>Hết hồ sơ gợi ý</h2>
            <p>Bạn đã xem hết những người phù hợp hiện có. Thử mở rộng tiêu chí tìm kiếm
                trong <a href="<?= site_url('tai-khoan/ho-so') ?>">hồ sơ của bạn</a>,
                hoặc xem <a href="<?= site_url('thanh-vien') ?>">toàn bộ thành viên</a>.</p>
        </div>
    <?php else: ?>
        <div class="swipe-grid" id="swipe-grid">
            <?php foreach ($candidates as $c): ?>
                <?php $this->load->view('discover/_card', array('c' => $c)); ?>
            <?php endforeach; ?>
        </div>
        <?= $pagination ?>
    <?php endif; ?>

    <?php if ($matches): ?>
        <section class="home-block">
            <h2 class="block-title">Đã ghép đôi</h2>
            <div class="member-grid">
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
