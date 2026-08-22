<?php defined('BASEPATH') OR exit('No direct script access allowed');
$reasons = array('lua_dao' => 'Lừa đảo', 'noi_dung_xau' => 'Nội dung xấu',
                 'mao_danh' => 'Mạo danh', 'spam' => 'Spam', 'khac' => 'Khác');
?>
<form class="filter-form" method="get">
    <div>
        <label>Trạng thái</label>
        <select name="status">
            <option value="">Tất cả</option>
            <?php foreach (array('new' => 'Mới', 'reviewing' => 'Đang xem xét', 'resolved' => 'Đã xử lý', 'dismissed' => 'Bỏ qua') as $k => $t): ?>
                <option value="<?= $k ?>" <?= $this->input->get('status') === $k ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div><button class="btn btn-primary" type="submit">Lọc</button></div>
</form>

<div class="panel">
    <div class="panel-head"><h2>Tổng <?= number_format($total) ?> báo cáo</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Người báo cáo</th><th>Đối tượng</th><th>Lý do</th><th>Nội dung</th>
                <th>Trạng thái</th><th>Thời gian</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php if (empty($reports)): ?><tr><td colspan="7">Không có báo cáo nào.</td></tr><?php endif; ?>
            <?php foreach ($reports as $r): ?>
                <tr>
                    <td><?= e($r['reporter_name']) ?></td>
                    <td>
                        <?php if ($r['target_link']): ?>
                            <a href="<?= $r['target_link'] ?>"><?= e($r['target_label']) ?></a>
                        <?php else: ?>
                            <?= e($r['target_label']) ?>
                        <?php endif; ?>
                        <br><small><?= e($r['target_type']) ?></small>
                    </td>
                    <td><?= e($reasons[$r['reason']] ?? $r['reason']) ?></td>
                    <td><?= e(excerpt($r['note'], 80)) ?></td>
                    <td><?= status_label($r['status']) ?></td>
                    <td><?= time_ago($r['created_at']) ?></td>
                    <td class="actions">
                        <?php if ($r['target_type'] === 'post'): ?>
                            <a class="btn btn-danger btn-sm" href="<?= site_url('admin/reports/act/' . $r['id'] . '/hide_post') ?>"
                               onclick="return confirm('Ẩn tin bị báo cáo?')">Ẩn tin</a>
                        <?php elseif ($r['target_type'] === 'user'): ?>
                            <a class="btn btn-danger btn-sm" href="<?= site_url('admin/reports/act/' . $r['id'] . '/ban_user') ?>"
                               onclick="return confirm('Cấm tài khoản này?')">Cấm TK</a>
                        <?php endif; ?>
                        <a class="btn btn-success btn-sm" href="<?= site_url('admin/reports/resolve/' . $r['id'] . '/resolved') ?>">Đã xử lý</a>
                        <a class="btn btn-light btn-sm" href="<?= site_url('admin/reports/resolve/' . $r['id'] . '/dismissed') ?>">Bỏ qua</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $pagination ?>
