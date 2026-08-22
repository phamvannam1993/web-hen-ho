<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="filter-form">
    <div>
        <a class="btn <?= $type === 'post' ? 'btn-primary' : 'btn-light' ?>" href="<?= site_url('admin/categories?type=post') ?>">Danh mục tin đăng</a>
        <a class="btn <?= $type === 'blog' ? 'btn-primary' : 'btn-light' ?>" href="<?= site_url('admin/categories?type=blog') ?>">Danh mục bài viết</a>
        <a class="btn btn-blue" href="<?= site_url('admin/categories/edit') ?>">+ Thêm danh mục</a>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Danh mục <?= $type === 'post' ? 'tin đăng' : 'bài viết' ?></h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Tên</th><th>Đường dẫn</th><th>Danh mục cha</th><th>Thứ tự</th><th>Hiển thị</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php
            $by_id = array();
            foreach ($categories as $c) { $by_id[$c['id']] = $c['name']; }
            ?>
            <?php if (empty($categories)): ?><tr><td colspan="6">Chưa có danh mục.</td></tr><?php endif; ?>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><?= $c['parent_id'] ? '— ' : '' ?><?= e($c['name']) ?></td>
                    <td><code><?= e($c['slug']) ?></code></td>
                    <td><?= e($by_id[$c['parent_id']] ?? '—') ?></td>
                    <td><?= (int) $c['sort'] ?></td>
                    <td><?= $c['is_active'] ? '<span class="badge bg-success">Bật</span>' : '<span class="badge bg-secondary">Tắt</span>' ?></td>
                    <td class="actions">
                        <a class="btn btn-light btn-sm" href="<?= site_url('admin/categories/edit/' . $c['id']) ?>">Sửa</a>
                        <a class="btn btn-danger btn-sm" href="<?= site_url('admin/categories/delete/' . $c['id']) ?>"
                           onclick="return confirm('Xoá danh mục này?')">Xoá</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
