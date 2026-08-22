<?php defined('BASEPATH') OR exit('No direct script access allowed');
$v = function ($k, $d = '') use ($c) { return e($c[$k] ?? $d); };
?>
<form method="post">
    <div class="panel">
        <div class="panel-head"><h2><?= e($title) ?></h2></div>
        <div class="panel-body form-grid">
            <div><label>Tên danh mục</label><input type="text" name="name" value="<?= $v('name') ?>" required></div>
            <div><label>Đường dẫn (để trống sẽ tự sinh)</label><input type="text" name="slug" value="<?= $v('slug') ?>"></div>
            <div>
                <label>Loại</label>
                <select name="type">
                    <option value="post" <?= ($c['type'] ?? 'post') === 'post' ? 'selected' : '' ?>>Tin đăng</option>
                    <option value="blog" <?= ($c['type'] ?? '') === 'blog' ? 'selected' : '' ?>>Bài viết</option>
                </select>
            </div>
            <div>
                <label>Danh mục cha</label>
                <select name="parent_id">
                    <option value="">-- Không có --</option>
                    <?php foreach ($parents as $p): ?>
                        <?php if (!empty($c['id']) && $p['id'] == $c['id']) continue; ?>
                        <option value="<?= (int) $p['id'] ?>" <?= ($c['parent_id'] ?? null) == $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['name']) ?> (<?= e($p['type']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Thứ tự</label><input type="number" name="sort" value="<?= $v('sort', '0') ?>"></div>
            <div>
                <label>Hiển thị</label>
                <label><input type="checkbox" name="is_active" value="1" <?= !isset($c['is_active']) || $c['is_active'] ? 'checked' : '' ?>> Bật</label>
            </div>
            <div class="full"><label>Mô tả</label><textarea class="rich-editor rich-editor-basic" name="description"><?= $v('description') ?></textarea></div>
            <div><label>Tiêu đề SEO</label><input type="text" name="seo_title" value="<?= $v('seo_title') ?>"></div>
            <div><label>Mô tả SEO</label><input type="text" name="seo_desc" value="<?= $v('seo_desc') ?>"></div>
        </div>
    </div>
    <div class="actions">
        <button class="btn btn-primary" type="submit">Lưu</button>
        <a class="btn btn-light" href="<?= site_url('admin/categories') ?>">Quay lại</a>
    </div>
</form>
