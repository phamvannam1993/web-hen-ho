<?php defined('BASEPATH') OR exit('No direct script access allowed');
$trang_thai = array('on' => 'Đang bật', 'off' => 'Đã tắt');
?>
<form class="filter-form" method="get">
    <div>
        <label>Từ khoá</label>
        <input type="text" name="q" value="<?= e($this->input->get('q')) ?>" placeholder="Tên nghề...">
    </div>
    <div>
        <label>Trạng thái</label>
        <select name="status">
            <option value="">Tất cả</option>
            <?php foreach ($trang_thai as $k => $t): ?>
                <option value="<?= $k ?>" <?= $this->input->get('status') === $k ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <button class="btn btn-primary" type="submit">Lọc</button>
        <a class="btn btn-blue" href="<?= site_url('admin/jobs/edit') ?>">+ Thêm nghề</a>
    </div>
</form>

<div class="panel">
    <div class="panel-head"><h2>Tổng <?= number_format(count($jobs)) ?> nghề nghiệp</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr><th>Tên nghề</th><th>Thành viên</th><th>Thứ tự</th><th>Trạng thái</th><th>Thao tác</th></tr>
            </thead>
            <tbody>
            <?php if (empty($jobs)): ?>
                <tr><td colspan="5">Chưa có nghề nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($jobs as $j): ?>
                <tr>
                    <td><a href="<?= site_url('admin/jobs/edit/' . $j['id']) ?>"><?= e($j['name']) ?></a></td>
                    <td><?= number_format($j['member_count']) ?></td>
                    <td><?= (int) $j['sort'] ?></td>
                    <td><?= $j['is_active'] ? 'Đang bật' : 'Đã tắt' ?></td>
                    <td class="actions">
                        <a class="btn btn-light btn-sm" href="<?= site_url('admin/jobs/edit/' . $j['id']) ?>">Sửa</a>
                        <a class="btn btn-danger btn-sm" href="<?= site_url('admin/jobs/delete/' . $j['id']) ?>"
                           data-confirm="Xoá nghề này?<?= $j['member_count'] > 0
                               ? ' ' . number_format($j['member_count']) . ' hồ sơ sẽ bỏ trống nghề nghiệp.' : '' ?>"
                           data-confirm-danger>Xoá</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
