<?php defined('BASEPATH') OR exit('No direct script access allowed');
$statuses = array('pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối');
$colors = array('pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger');
$query = http_build_query(array_merge($filters, array('page' => max(1, (int) $this->uri->segment(4)))));
?>
<div class="photo-admin-page">
<form class="filter-form photo-filters" method="get" action="<?= site_url('admin/photos') ?>">
    <div><label>Tên hoặc email thành viên</label><input type="text" name="q" value="<?= e($filters['q']) ?>"></div>
    <div>
        <label>Trạng thái</label>
        <select name="status">
            <?php foreach (array_merge(array('all' => 'Tất cả'), $statuses) as $key => $label): ?>
                <option value="<?= $key ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div><button class="btn btn-primary" type="submit">Lọc</button></div>
</form>
<div class="panel">
    <div class="panel-head"><h2><?= number_format($total) ?> ảnh</h2></div>
    <div class="table-scroll">
        <table class="table photo-table">
            <thead><tr><th>Ảnh</th><th>Thành viên</th><th>Ngày tải lên</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>
                <?php if (!$photos): ?><tr><td colspan="5">Không có ảnh trong danh sách này.</td></tr><?php endif; ?>
                <?php foreach ($photos as $photo): ?>
                    <?php $src = base_url(ltrim($photo['path'], '/')); ?>
                    <tr>
                        <td class="photo-preview">
                            <a href="<?= e($src) ?>" target="_blank" rel="noopener" aria-label="Xem ảnh đầy đủ">
                                <img src="<?= e($src) ?>" alt="Ảnh của <?= e(display_name($photo)) ?>" loading="lazy" width="120" height="150">
                            </a>
                        </td>
                        <td><a href="<?= site_url('admin/users/view/' . (int) $photo['user_id']) ?>"><?= e(display_name($photo)) ?></a><br><small><?= e($photo['email']) ?></small></td>
                        <td><?= e($photo['created_at']) ?></td>
                        <td><span class="badge bg-<?= $colors[$photo['status']] ?? 'warning' ?>"><?= e($statuses[$photo['status']] ?? $photo['status']) ?></span></td>
                        <td>
                            <?= form_open('admin/photos/moderate/' . (int) $photo['id'] . '?' . $query, array('class' => 'photo-actions')) ?>
                                <?php if ($photo['status'] !== 'approved'): ?><button class="btn btn-success btn-sm" type="submit" name="status" value="approved">Duyệt</button><?php endif; ?>
                                <?php if ($photo['status'] !== 'rejected'): ?><button class="btn btn-danger btn-sm" type="submit" name="status" value="rejected">Từ chối</button><?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $pagination ?>
</div>
