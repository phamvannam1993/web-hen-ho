<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="filter-form">
    <div><a class="btn btn-blue" href="<?= site_url('admin/packages/edit') ?>">+ Thêm gói</a></div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Danh sách gói dịch vụ</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Tên gói</th><th>Loại</th><th>Giá</th><th>Xu / Thời hạn</th><th>Tặng thêm</th>
                <th>Hiển thị</th><th>Thứ tự</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php if (empty($packages)): ?><tr><td colspan="8">Chưa có gói nào.</td></tr><?php endif; ?>
            <?php foreach ($packages as $p): ?>
                <tr>
                    <td><b><?= e($p['name']) ?></b><br><small><?= e(excerpt($p['description'], 60)) ?></small></td>
                    <td><?= $p['type'] === 'vip' ? 'VIP' : 'Nạp xu' ?></td>
                    <td><?= money($p['price']) ?></td>
                    <td><?= $p['type'] === 'vip' ? (int) $p['duration_days'] . ' ngày' : number_format($p['coin_amount']) . ' xu' ?></td>
                    <td><?= $p['bonus_coin'] ? '+' . number_format($p['bonus_coin']) . ' xu' : '—' ?></td>
                    <td><?= $p['is_active'] ? '<span class="badge bg-success">Bật</span>' : '<span class="badge bg-secondary">Tắt</span>' ?></td>
                    <td><?= (int) $p['sort'] ?></td>
                    <td class="actions">
                        <a class="btn btn-light btn-sm" href="<?= site_url('admin/packages/edit/' . $p['id']) ?>">Sửa</a>
                        <a class="btn btn-danger btn-sm" href="<?= site_url('admin/packages/delete/' . $p['id']) ?>"
                           onclick="return confirm('Xoá gói này?')">Xoá</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
