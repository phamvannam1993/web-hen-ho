<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container">
    <nav class="breadcrumb">
        <a href="<?= site_url() ?>">Trang chủ</a> ›
        <a href="<?= site_url('khu-vuc') ?>">Khu vực</a> ›
        <span><?= e($province['name']) ?></span>
    </nav>

    <h1 class="block-title">Thành viên tại <?= e($province['name']) ?></h1>

    <form class="filter-bar" method="get">
        <input type="text" name="q" value="<?= e($this->input->get('q')) ?>" placeholder="Tên hoặc mô tả...">
        <select name="gender">
            <option value="">Giới tính</option>
            <option value="female" <?= $this->input->get('gender') === 'female' ? 'selected' : '' ?>>Nữ</option>
            <option value="male" <?= $this->input->get('gender') === 'male' ? 'selected' : '' ?>>Nam</option>
        </select>
        <input type="number" name="age_min" value="<?= e($this->input->get('age_min')) ?>" placeholder="Tuổi từ">
        <input type="number" name="age_max" value="<?= e($this->input->get('age_max')) ?>" placeholder="Đến">
        <select name="sort">
            <option value="active">Hoạt động gần đây</option>
            <option value="new" <?= $this->input->get('sort') === 'new' ? 'selected' : '' ?>>Mới tham gia</option>
            <option value="vip" <?= $this->input->get('sort') === 'vip' ? 'selected' : '' ?>>Thành viên VIP</option>
        </select>
        <label class="checkbox">
            <input type="checkbox" name="online" value="1" <?= $this->input->get('online') ? 'checked' : '' ?>> Đang online
        </label>
        <button class="btn btn-primary" type="submit">Lọc</button>
    </form>

    <p class="result-count">Tìm thấy <b><?= number_format($total) ?></b> thành viên tại <?= e($province['name']) ?>.</p>

    <?php if (empty($members)): ?>
        <p class="empty">Chưa có thành viên nào phù hợp tại khu vực này.
            <a href="<?= site_url('thanh-vien') ?>">Xem toàn bộ thành viên →</a></p>
    <?php else: ?>
        <div class="member-grid">
            <?php foreach ($members as $m): ?>
                <?php $this->load->view('members/_card', array('m' => $m)); ?>
            <?php endforeach; ?>
        </div>
        <?= $pagination ?>
    <?php endif; ?>
</div>
