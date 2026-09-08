<?php defined('BASEPATH') OR exit('No direct script access allowed');
$v = function ($k, $d = '') use ($j) { return e($j[$k] ?? $d); };
?>
<form method="post">
    <div class="panel">
        <div class="panel-head"><h2><?= e($title) ?></h2></div>
        <div class="panel-body form-grid">
            <div>
                <label>Tên nghề</label>
                <input type="text" name="name" value="<?= $v('name') ?>" required placeholder="VD: Kỹ sư phần mềm">
            </div>
            <div>
                <label>Thứ tự hiển thị</label>
                <input type="number" name="sort" value="<?= $v('sort', '0') ?>">
                <small>Số nhỏ hiện trước; để 0 thì xếp theo tên</small>
            </div>
            <div>
                <label>Trạng thái</label>
                <select name="is_active">
                    <option value="1" <?= (string) ($j['is_active'] ?? 1) === '1' ? 'selected' : '' ?>>Đang bật</option>
                    <option value="0" <?= isset($j) && (string) $j['is_active'] === '0' ? 'selected' : '' ?>>Đã tắt</option>
                </select>
                <small>Tắt thì không hiện trong ô chọn nghề của hồ sơ nữa</small>
            </div>
            <?php if (!empty($j)): ?>
                <div class="full">
                    <p><b><?= number_format($members) ?></b> thành viên đang chọn nghề này.
                        <?php if ($members > 0): ?>
                            Đổi tên nghề thì hồ sơ của họ được cập nhật theo.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="actions">
        <button class="btn btn-primary" type="submit">Lưu</button>
        <a class="btn btn-light" href="<?= site_url('admin/jobs') ?>">Quay lại</a>
    </div>
</form>
