<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="filter-form">
    <div><a class="btn btn-blue" href="<?= site_url('admin/pages/edit') ?>">+ Thêm trang</a></div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Trang tĩnh</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr><th>Tiêu đề</th><th>Đường dẫn</th><th>Hiển thị</th><th>Cập nhật</th><th>Thao tác</th></tr>
            </thead>
            <tbody>
            <?php if (empty($pages)): ?>
                <tr><td colspan="5">Chưa có trang nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($pages as $p): ?>
                <tr>
                    <td>
                        <a href="<?= site_url('admin/pages/edit/' . $p['id']) ?>"><?= e($p['title']) ?></a>
                        <br><small><?= e(excerpt($p['content'], 70)) ?></small>
                    </td>
                    <td><code>/trang/<?= e($p['slug']) ?></code></td>
                    <td><?= $p['is_active']
                            ? '<span class="badge bg-success">Bật</span>'
                            : '<span class="badge bg-secondary">Tắt</span>' ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($p['updated_at'])) ?></td>
                    <td class="actions">
                        <a class="btn btn-light btn-sm" href="<?= site_url('trang/' . $p['slug']) ?>" target="_blank">Xem</a>
                        <a class="btn btn-light btn-sm" href="<?= site_url('admin/pages/edit/' . $p['id']) ?>">Sửa</a>
                        <a class="btn btn-danger btn-sm" href="<?= site_url('admin/pages/delete/' . $p['id']) ?>"
                           onclick="return confirm('Xoá trang này?')">Xoá</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
