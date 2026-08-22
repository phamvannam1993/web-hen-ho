<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<form class="filter-form" method="get">
    <div>
        <label>Từ khoá</label>
        <input type="text" name="q" value="<?= e($this->input->get('q')) ?>" placeholder="Tiêu đề, người đăng...">
    </div>
    <div>
        <label>Trạng thái</label>
        <select name="status">
            <option value="">Tất cả</option>
            <?php foreach (array('pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối', 'expired' => 'Hết hạn', 'hidden' => 'Đã ẩn') as $k => $v): ?>
                <option value="<?= $k ?>" <?= $this->input->get('status') === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>Danh mục</label>
        <select name="category_id">
            <option value="">Tất cả</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $this->input->get('category_id') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>Khu vực</label>
        <select name="province_id">
            <option value="">Tất cả</option>
            <?php foreach ($provinces as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $this->input->get('province_id') == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <button class="btn btn-primary" type="submit">Lọc</button>
        <a class="btn btn-blue" href="<?= site_url('admin/posts/edit') ?>">+ Thêm tin</a>
    </div>
</form>

<form method="post" action="<?= site_url('admin/posts/bulk') ?>">
    <div class="panel">
        <div class="panel-head">
            <h2>Tổng <?= number_format($total) ?> tin</h2>
            <div class="actions">
                <select name="bulk_action">
                    <option value="approved">Duyệt</option>
                    <option value="rejected">Từ chối</option>
                    <option value="hidden">Ẩn</option>
                    <option value="feature">Đánh dấu nổi bật</option>
                    <option value="delete">Xoá</option>
                </select>
                <button class="btn btn-light btn-sm" type="submit"
                        onclick="return confirm('Áp dụng cho các tin đã chọn?')">Áp dụng</button>
            </div>
        </div>
        <div class="table-scroll">
            <table class="table">
                <thead>
                <tr>
                    <th><input type="checkbox" onclick="document.querySelectorAll('[name=\'ids[]\']').forEach(c=>c.checked=this.checked)"></th>
                    <th>Ảnh</th><th>Tiêu đề</th><th>Người đăng</th><th>Danh mục</th>
                    <th>Khu vực</th><th>Trạng thái</th><th>Lượt xem</th><th>Ngày tạo</th><th>Thao tác</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($posts)): ?>
                    <tr><td colspan="10">Không có tin nào.</td></tr>
                <?php endif; ?>
                <?php foreach ($posts as $p): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= (int) $p['id'] ?>"></td>
                        <td><img class="thumb" src="<?= $p['cover'] ? base_url(ltrim($p['cover'], '/')) : base_url('assets/site/img/placeholder.svg') ?>" alt=""></td>
                        <td>
                            <a href="<?= site_url('admin/posts/edit/' . $p['id']) ?>"><?= e($p['title']) ?></a>
                            <?php if ($p['is_featured']): ?><span class="badge bg-warning">Nổi bật</span><?php endif; ?>
                            <?php if ($p['is_verified']): ?><span class="badge bg-success">Kiểm định</span><?php endif; ?>
                        </td>
                        <td><a href="<?= site_url('admin/users/view/' . $p['user_id']) ?>"><?= e($p['display_name']) ?></a></td>
                        <td><?= e($p['category_name']) ?></td>
                        <td><?= e($p['province_name']) ?></td>
                        <td><?= status_label($p['status']) ?></td>
                        <td><?= number_format($p['view_count']) ?></td>
                        <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                        <td class="actions">
                            <?php if ($p['status'] !== 'approved'): ?>
                                <a class="btn btn-success btn-sm" href="<?= site_url('admin/posts/moderate/' . $p['id'] . '/approved') ?>">Duyệt</a>
                            <?php else: ?>
                                <a class="btn btn-light btn-sm" href="<?= site_url('tin/' . $p['slug']) ?>" target="_blank">Xem</a>
                            <?php endif; ?>
                            <a class="btn btn-danger btn-sm" href="<?= site_url('admin/posts/delete/' . $p['id']) ?>"
                               onclick="return confirm('Xoá tin này?')">Xoá</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<?= $pagination ?>
