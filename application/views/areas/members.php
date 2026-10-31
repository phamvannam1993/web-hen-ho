<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<link rel="stylesheet" href="<?= base_url('assets/site/css/dating-discovery.css') ?>?v=<?= @filemtime(FCPATH.'assets/site/css/dating-discovery.css') ?>">
<div class="container dating-page province-page">
    <nav class="breadcrumb">
        <a href="<?= site_url() ?>">Trang chủ</a> ›
        <a href="<?= site_url('khu-vuc') ?>">Khu vực</a> ›
        <span><?= e($province['name']) ?></span>
    </nav>
    <header class="search-head">
        <h1>Hẹn hò tại <?= e($province['name']) ?></h1>
        <p><b><?= number_format($total) ?></b> thành viên</p>
    </header>
    <?php if (empty($members)): ?>
        <p class="empty">Chưa có thành viên phù hợp.
            <a href="<?= site_url('khu-vuc') ?>">Xem khu vực khác</a>.
        </p>
    <?php else: ?>
        <div class="member-grid">
            <?php foreach ($members as $m): ?>
                <?php $this->load->view('members/_card', array('m' => $m, 'dating_card' => true)); ?>
            <?php endforeach; ?>
        </div>
        <?= $pagination ?>
    <?php endif; ?>
</div>
