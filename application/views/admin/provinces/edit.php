<?php defined('BASEPATH') OR exit('No direct script access allowed');
$v = function ($k, $d = '') use ($p) { return e($p[$k] ?? $d); };
?>
<form method="post">
    <div class="panel">
        <div class="panel-head"><h2><?= e($title) ?></h2></div>
        <div class="panel-body form-grid">
            <div>
                <label>Tên tỉnh/thành</label>
                <input type="text" name="name" value="<?= $v('name') ?>" required placeholder="VD: Hà Nội">
            </div>
            <div>
                <label>Đường dẫn (để trống sẽ tự sinh)</label>
                <input type="text" name="slug" value="<?= $v('slug') ?>" placeholder="ha-noi">
            </div>
            <div>
                <label>Miền</label>
                <select name="region">
                    <option value="">-- Chọn --</option>
                    <?php foreach (array('bac' => 'Miền Bắc', 'trung' => 'Miền Trung', 'nam' => 'Miền Nam') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= ($p['region'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Thứ tự hiển thị</label>
                <input type="number" name="sort" value="<?= $v('sort', '0') ?>">
                <small>Số nhỏ hiện trước</small>
            </div>
            <?php if (!empty($p)): ?>
                <div class="full">
                    <p><b><?= number_format($members) ?></b> thành viên đang thuộc khu vực này.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="actions">
        <button class="btn btn-primary" type="submit">Lưu</button>
        <a class="btn btn-light" href="<?= site_url('admin/provinces') ?>">Quay lại</a>
    </div>
</form>
