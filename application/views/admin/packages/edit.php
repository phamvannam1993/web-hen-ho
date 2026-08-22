<?php defined('BASEPATH') OR exit('No direct script access allowed');
$v = function ($k, $d = '') use ($p) { return e($p[$k] ?? $d); };
?>
<form method="post">
    <div class="panel">
        <div class="panel-head"><h2><?= e($title) ?></h2></div>
        <div class="panel-body form-grid">
            <div><label>Tên gói</label><input type="text" name="name" value="<?= $v('name') ?>" required></div>
            <div>
                <label>Loại gói</label>
                <select name="type">
                    <option value="coin" <?= ($p['type'] ?? 'coin') === 'coin' ? 'selected' : '' ?>>Nạp xu</option>
                    <option value="vip" <?= ($p['type'] ?? '') === 'vip' ? 'selected' : '' ?>>VIP</option>
                </select>
            </div>
            <div><label>Giá (VNĐ)</label><input type="number" name="price" value="<?= $v('price', '0') ?>" required></div>
            <div><label>Số xu (gói nạp xu)</label><input type="number" name="coin_amount" value="<?= $v('coin_amount') ?>"></div>
            <div><label>Xu tặng thêm</label><input type="number" name="bonus_coin" value="<?= $v('bonus_coin', '0') ?>"></div>
            <div><label>Số ngày (gói VIP)</label><input type="number" name="duration_days" value="<?= $v('duration_days') ?>"></div>
            <div><label>Thứ tự</label><input type="number" name="sort" value="<?= $v('sort', '0') ?>"></div>
            <div>
                <label>Hiển thị</label>
                <label><input type="checkbox" name="is_active" value="1" <?= !isset($p['is_active']) || $p['is_active'] ? 'checked' : '' ?>> Bật</label>
            </div>
            <div class="full"><label>Mô tả</label><textarea name="description"><?= $v('description') ?></textarea></div>
        </div>
    </div>
    <div class="actions">
        <button class="btn btn-primary" type="submit">Lưu</button>
        <a class="btn btn-light" href="<?= site_url('admin/packages') ?>">Quay lại</a>
    </div>
</form>
