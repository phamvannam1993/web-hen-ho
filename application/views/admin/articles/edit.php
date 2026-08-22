<?php defined('BASEPATH') OR exit('No direct script access allowed');
$v = function ($k, $d = '') use ($a) { return e($a[$k] ?? $d); };
?>
<form method="post" enctype="multipart/form-data">
    <div class="panel">
        <div class="panel-head"><h2><?= e($title) ?></h2></div>
        <div class="panel-body form-grid">
            <div class="full">
                <label>Tiêu đề</label>
                <input type="text" name="title" value="<?= $v('title') ?>" required>
            </div>
            <div>
                <label>Đường dẫn (để trống sẽ tự sinh)</label>
                <input type="text" name="slug" value="<?= $v('slug') ?>">
            </div>
            <div>
                <label>Chuyên mục</label>
                <select name="category_id">
                    <option value="">-- Chọn --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= ($a['category_id'] ?? null) == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="full">
                <label>Mô tả ngắn</label>
                <textarea class="rich-editor rich-editor-basic" name="excerpt"><?= $v('excerpt') ?></textarea>
            </div>
            <div class="full">
                <label>Nội dung (cho phép HTML)</label>
                <textarea class="rich-editor" name="content" data-upload-url="<?= site_url('admin/articles/upload_image') ?>" required><?= $v('content') ?></textarea>
            </div>
            <div>
                <label>Ảnh đại diện</label>
                <input type="file" name="thumbnail" accept="image/*">
                <?php if (!empty($a['thumbnail'])): ?>
                    <img class="thumb" src="<?= base_url(ltrim($a['thumbnail'], '/')) ?>" alt="">
                <?php endif; ?>
            </div>
            <div>
                <label>Trạng thái</label>
                <select name="status">
                    <option value="draft" <?= ($a['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Nháp</option>
                    <option value="published" <?= ($a['status'] ?? '') === 'published' ? 'selected' : '' ?>>Đăng ngay</option>
                </select>
            </div>
            <div><label>Tiêu đề SEO</label><input type="text" name="seo_title" value="<?= $v('seo_title') ?>"></div>
            <div><label>Mô tả SEO</label><input type="text" name="seo_desc" value="<?= $v('seo_desc') ?>"></div>
        </div>
    </div>

    <div class="actions">
        <button class="btn btn-primary" type="submit">Lưu bài viết</button>
        <a class="btn btn-light" href="<?= site_url('admin/articles') ?>">Quay lại</a>
    </div>
</form>
