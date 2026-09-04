<?php defined('BASEPATH') OR exit('No direct script access allowed');
$v = function ($k, $d = '') use ($p) { return e($p[$k] ?? $d); };
?>
<form method="post">
    <div class="panel">
        <div class="panel-head"><h2><?= e($title) ?></h2></div>
        <div class="panel-body form-grid">
            <div>
                <label>Tiêu đề</label>
                <input type="text" name="title" value="<?= $v('title') ?>" required>
            </div>
            <div>
                <label>Đường dẫn (để trống sẽ tự sinh)</label>
                <input type="text" name="slug" value="<?= $v('slug') ?>" placeholder="vd: noi-quy">
            </div>
            <div class="full">
                <label>Nội dung (cho phép HTML)</label>
                <textarea class="rich-editor" name="content" data-upload-url="<?= site_url('admin/pages/upload_image') ?>" required><?= $v('content') ?></textarea>
            </div>
            <div>
                <label>Hiển thị</label>
                <label>
                    <input type="checkbox" name="is_active" value="1" <?= !isset($p['is_active']) || $p['is_active'] ? 'checked' : '' ?>>
                    Bật (trang tắt sẽ trả về 404)
                </label>
            </div>
        </div>
    </div>

    <div class="actions">
        <button class="btn btn-primary" type="submit">Lưu trang</button>
        <a class="btn btn-light" href="<?= site_url('admin/pages') ?>">Quay lại</a>
        <?php if (!empty($p['slug'])): ?>
            <a class="btn btn-light" href="<?= site_url($p['slug']) ?>" target="_blank">Xem trang</a>
        <?php endif; ?>
    </div>
</form>
