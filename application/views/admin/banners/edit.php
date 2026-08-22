<?php defined('BASEPATH') OR exit('No direct script access allowed');
$v = function ($k, $d = '') use ($b) { return e($b[$k] ?? $d); };
$positions = array(
    'home_slider' => 'Trang chủ - đầu trang',
    'home_middle' => 'Trang chủ - giữa trang',
    'sidebar'     => 'Cột bên phải',
    'footer'      => 'Chân trang',
);
?>
<form method="post" enctype="multipart/form-data">
    <div class="panel">
        <div class="panel-head"><h2><?= e($title) ?></h2></div>
        <div class="panel-body form-grid">
            <div><label>Tiêu đề</label><input type="text" name="title" value="<?= $v('title') ?>"></div>
            <div>
                <label>Vị trí hiển thị</label>
                <select name="position">
                    <?php foreach ($positions as $k => $t): ?>
                        <option value="<?= $k ?>" <?= ($b['position'] ?? 'home_slider') === $k ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="full">
                <label>Liên kết khi bấm vào</label>
                <input type="text" name="link" value="<?= $v('link') ?>" placeholder="https://...">
            </div>
            <div><label>Thứ tự</label><input type="number" name="sort" value="<?= $v('sort', '0') ?>"></div>
            <div>
                <label>Hiển thị</label>
                <label><input type="checkbox" name="is_active" value="1" <?= !isset($b['is_active']) || $b['is_active'] ? 'checked' : '' ?>> Bật</label>
            </div>
            <div class="full">
                <label>Ảnh banner <?= empty($b) ? '(bắt buộc)' : '(để trống nếu giữ ảnh cũ)' ?></label>
                <input type="file" name="image" accept="image/*" <?= empty($b) ? 'required' : '' ?>>
                <?php if (!empty($b['image'])): ?>
                    <img src="<?= base_url(ltrim($b['image'], '/')) ?>" alt="" style="max-width:400px;margin-top:12px;border-radius:6px">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="actions">
        <button class="btn btn-primary" type="submit">Lưu banner</button>
        <a class="btn btn-light" href="<?= site_url('admin/banners') ?>">Quay lại</a>
    </div>
</form>
