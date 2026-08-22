<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="stat-grid">
    <div class="stat-card green"><div class="label">Doanh thu hôm nay</div><div class="value"><?= money($revenue['today']) ?></div></div>
    <div class="stat-card blue"><div class="label">Doanh thu tháng</div><div class="value"><?= money($revenue['month']) ?></div></div>
    <div class="stat-card pink"><div class="label">Tổng doanh thu</div><div class="value"><?= money($revenue['total']) ?></div></div>
    <div class="stat-card amber"><div class="label">Đơn chờ xác nhận</div><div class="value"><?= number_format($revenue['pending']) ?></div></div>
</div>

<form class="filter-form" method="get">
    <div>
        <label>Từ khoá</label>
        <input type="text" name="q" value="<?= e($this->input->get('q')) ?>" placeholder="Mã đơn, tên thành viên...">
    </div>
    <div>
        <label>Trạng thái</label>
        <select name="status">
            <option value="">Tất cả</option>
            <?php foreach (array('pending' => 'Chờ thanh toán', 'paid' => 'Đã thanh toán', 'failed' => 'Thất bại',
                                 'refunded' => 'Hoàn tiền', 'canceled' => 'Đã huỷ') as $k => $t): ?>
                <option value="<?= $k ?>" <?= $this->input->get('status') === $k ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div><button class="btn btn-primary" type="submit">Lọc</button></div>
</form>

<div class="panel">
    <div class="panel-head"><h2>Tổng <?= number_format($total) ?> đơn</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Mã đơn</th><th>Thành viên</th><th>Gói</th><th>Số tiền</th><th>Hình thức</th>
                <th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php if (empty($orders)): ?><tr><td colspan="8">Chưa có đơn nào.</td></tr><?php endif; ?>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><b><?= e($o['code']) ?></b></td>
                    <td><a href="<?= site_url('admin/users/view/' . $o['user_id']) ?>"><?= e($o['display_name']) ?></a></td>
                    <td><?= e($o['package_name']) ?></td>
                    <td><?= money($o['amount']) ?></td>
                    <td><?= e($o['method']) ?></td>
                    <td><?= status_label($o['status']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                    <td class="actions">
                        <?php if ($o['status'] === 'pending'): ?>
                            <a class="btn btn-success btn-sm" href="<?= site_url('admin/orders/set_status/' . $o['id'] . '/paid') ?>"
                               onclick="return confirm('Xác nhận đã nhận tiền? Hệ thống sẽ cộng xu/VIP ngay.')">Xác nhận</a>
                            <a class="btn btn-danger btn-sm" href="<?= site_url('admin/orders/set_status/' . $o['id'] . '/canceled') ?>">Huỷ</a>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= $o['paid_at'] ? date('d/m/Y H:i', strtotime($o['paid_at'])) : '—' ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $pagination ?>
