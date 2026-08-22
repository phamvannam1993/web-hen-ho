<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container">
    <h1 class="block-title"><?= e($title) ?></h1>

    <form method="get" class="filter-bar">
        <input type="text" name="q" value="<?= e($this->input->get('q')) ?>" placeholder="Từ khoá...">
        <select name="province_id">
            <option value="">Toàn quốc</option>
            <?php foreach ($provinces as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $this->input->get('province_id') == $p['id'] ? 'selected' : '' ?>>
                    <?= e($p['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="gender">
            <option value="">Giới tính</option>
            <option value="female" <?= $this->input->get('gender') === 'female' ? 'selected' : '' ?>>Nữ</option>
            <option value="male" <?= $this->input->get('gender') === 'male' ? 'selected' : '' ?>>Nam</option>
        </select>
        <select name="purpose">
            <option value="">Mục đích</option>
            <?php foreach (array('ket_ban', 'hen_ho', 'nghiem_tuc', 'ket_hon') as $p): ?>
                <option value="<?= $p ?>" <?= $this->input->get('purpose') === $p ? 'selected' : '' ?>><?= purpose_label($p) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sort">
            <option value="new">Mới nhất</option>
            <option value="view" <?= $this->input->get('sort') === 'view' ? 'selected' : '' ?>>Xem nhiều</option>
            <option value="like" <?= $this->input->get('sort') === 'like' ? 'selected' : '' ?>>Được thích nhiều</option>
        </select>
        <button class="btn btn-primary" type="submit">Lọc</button>
    </form>

    <p class="result-count">Tìm thấy <b><?= number_format($total) ?></b> tin đăng.</p>

    <?php if (empty($posts)): ?>
        <p class="empty">Chưa có tin phù hợp. Hãy thử bỏ bớt điều kiện lọc.</p>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($posts as $post): ?>
                <?php $this->load->view('posts/_card', array('post' => $post)); ?>
            <?php endforeach; ?>
        </div>
        <?= $pagination ?>
    <?php endif; ?>
</div>
