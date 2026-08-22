<?php defined('BASEPATH') OR exit('No direct script access allowed');
$purposes = array('register' => 'Đăng ký', 'login' => 'Đăng nhập', 'contact' => 'Xem liên hệ');
?>
<div class="panel">
    <div class="panel-head"><h2>Phát mã dùng chung</h2></div>
    <div class="panel-body">
        <form method="post" action="<?= site_url('admin/codes/create') ?>" class="filter-form" style="margin:0">
            <div>
                <label>Mục đích</label>
                <select name="purpose">
                    <?php foreach ($purposes as $k => $t): ?>
                        <option value="<?= $k ?>"><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Số lượt dùng</label><input type="number" name="max_uses" value="100"></div>
            <div><label>Hiệu lực (ngày)</label><input type="number" name="days" value="30"></div>
            <div><button class="btn btn-primary" type="submit">Tạo mã</button></div>
        </form>
    </div>
</div>

<form class="filter-form" method="get">
    <div>
        <label>Lọc theo mục đích</label>
        <select name="purpose">
            <option value="">Tất cả</option>
            <?php foreach ($purposes as $k => $t): ?>
                <option value="<?= $k ?>" <?= $this->input->get('purpose') === $k ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div><button class="btn btn-primary" type="submit">Lọc</button></div>
</form>

<div class="panel">
    <div class="panel-head"><h2>Tổng <?= number_format($total) ?> mã</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Mã</th><th>Mục đích</th><th>Người nhận</th><th>Lượt dùng</th>
                <th>Xu đã trừ</th><th>Hết hạn</th><th>Tạo lúc</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($codes)): ?><tr><td colspan="8">Chưa có mã nào.</td></tr><?php endif; ?>
            <?php foreach ($codes as $c): ?>
                <tr>
                    <td><b><?= e($c['code']) ?></b></td>
                    <td><?= e($purposes[$c['purpose']] ?? $c['purpose']) ?></td>
                    <td><?= e($c['display_name'] ?: 'Mã dùng chung') ?></td>
                    <td><?= (int) $c['used_count'] ?>/<?= (int) $c['max_uses'] ?></td>
                    <td><?= number_format($c['coin_spent']) ?></td>
                    <td><?= $c['expires_at'] ? date('d/m/Y H:i', strtotime($c['expires_at'])) : 'Không giới hạn' ?></td>
                    <td><?= time_ago($c['created_at']) ?></td>
                    <td><a class="btn btn-danger btn-sm" href="<?= site_url('admin/codes/delete/' . $c['id']) ?>">Xoá</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $pagination ?>
