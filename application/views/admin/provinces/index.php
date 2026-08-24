<?php defined('BASEPATH') OR exit('No direct script access allowed');
$regions = array('bac' => 'Miền Bắc', 'trung' => 'Miền Trung', 'nam' => 'Miền Nam');
?>
<form class="filter-form" method="get">
    <div>
        <label>Từ khoá</label>
        <input type="text" name="q" value="<?= e($this->input->get('q')) ?>" placeholder="Tên tỉnh/thành...">
    </div>
    <div>
        <label>Miền</label>
        <select name="region">
            <option value="">Tất cả</option>
            <?php foreach ($regions as $k => $t): ?>
                <option value="<?= $k ?>" <?= $this->input->get('region') === $k ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <button class="btn btn-primary" type="submit">Lọc</button>
        <a class="btn btn-blue" href="<?= site_url('admin/provinces/edit') ?>">+ Thêm tỉnh/thành</a>
    </div>
</form>

<div class="panel">
    <div class="panel-head"><h2>Tổng <?= number_format(count($provinces)) ?> tỉnh/thành</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr><th>Tên</th><th>Đường dẫn</th><th>Miền</th><th>Thành viên</th>
                <th>Thứ tự</th><th>Thao tác</th></tr>
            </thead>
            <tbody>
            <?php if (empty($provinces)): ?>
                <tr><td colspan="6">Chưa có tỉnh/thành nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($provinces as $p): ?>
                <tr>
                    <td><a href="<?= site_url('admin/provinces/edit/' . $p['id']) ?>"><?= e($p['name']) ?></a></td>
                    <td><code><?= e($p['slug']) ?></code></td>
                    <td><?= e($regions[$p['region']] ?? '—') ?></td>
                    <td><?= number_format($p['member_count']) ?></td>
                    <td><?= (int) $p['sort'] ?></td>
                    <td class="actions">
                        <a class="btn btn-light btn-sm" href="<?= site_url('khu-vuc/' . $p['slug']) ?>" target="_blank">Xem</a>
                        <a class="btn btn-light btn-sm" href="<?= site_url('admin/provinces/edit/' . $p['id']) ?>">Sửa</a>
                        <a class="btn btn-danger btn-sm" href="<?= site_url('admin/provinces/delete/' . $p['id']) ?>"
                           data-confirm="Xoá tỉnh/thành này?<?= $p['member_count'] > 0
                               ? ' ' . number_format($p['member_count']) . ' thành viên sẽ chuyển sang chưa rõ khu vực.' : '' ?>"
                           data-confirm-danger>Xoá</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
