<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<form class="filter-form" method="get">
    <div>
        <label>Từ khoá</label>
        <input type="text" name="q" value="<?= e($this->input->get('q')) ?>" placeholder="Tiêu đề bài viết...">
    </div>
    <div>
        <button class="btn btn-primary" type="submit">Lọc</button>
        <a class="btn btn-blue" href="<?= site_url('admin/articles/edit') ?>">+ Thêm bài viết</a>
    </div>
</form>

<div class="panel">
    <div class="panel-head"><h2>Tổng <?= number_format($total) ?> bài viết</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr><th>Ảnh</th><th>Tiêu đề</th><th>Trạng thái</th><th>Lượt xem</th>
                <th>Ngày đăng</th><th>Thao tác</th></tr>
            </thead>
            <tbody>
            <?php if (empty($articles)): ?>
                <tr><td colspan="6">Chưa có bài viết nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($articles as $a): ?>
                <tr>
                    <td>
                        <img class="thumb" src="<?= $a['thumbnail'] ? base_url(ltrim($a['thumbnail'], '/')) : base_url('assets/site/img/placeholder.svg') ?>" alt="">
                    </td>
                    <td>
                        <a href="<?= site_url('admin/articles/edit/' . $a['id']) ?>"><?= e($a['title']) ?></a>
                        <br><small><?= e(excerpt($a['excerpt'] ?: $a['content'], 70)) ?></small>
                    </td>
                    <td><?= $a['status'] === 'published'
                            ? '<span class="badge bg-success">Đã đăng</span>'
                            : '<span class="badge bg-secondary">Nháp</span>' ?></td>
                    <td><?= number_format($a['view_count']) ?></td>
                    <td><?= $a['published_at'] ? date('d/m/Y', strtotime($a['published_at'])) : '—' ?></td>
                    <td class="actions">
                        <?php if ($a['status'] === 'published'): ?>
                            <a class="btn btn-light btn-sm" href="<?= site_url('tin-tuc/' . $a['slug']) ?>" target="_blank">Xem</a>
                        <?php endif; ?>
                        <a class="btn btn-light btn-sm" href="<?= site_url('admin/articles/edit/' . $a['id']) ?>">Sửa</a>
                        <a class="btn btn-danger btn-sm" href="<?= site_url('admin/articles/delete/' . $a['id']) ?>"
                           data-confirm="Xoá bài viết này?" data-confirm-danger>Xoá</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $pagination ?>
