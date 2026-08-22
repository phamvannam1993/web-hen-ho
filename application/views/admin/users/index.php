<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<form class="filter-form" method="get">
    <div>
        <label>Từ khoá</label>
        <input type="text" name="q" value="<?= e($this->input->get('q')) ?>" placeholder="Tên, email, SĐT...">
    </div>
    <div>
        <label>Trạng thái</label>
        <select name="status">
            <option value="">Tất cả</option>
            <?php foreach (array('active' => 'Hoạt động', 'pending' => 'Chờ duyệt', 'locked' => 'Tạm khoá', 'banned' => 'Cấm') as $k => $t): ?>
                <option value="<?= $k ?>" <?= $this->input->get('status') === $k ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>Vai trò</label>
        <select name="role">
            <option value="">Tất cả</option>
            <?php foreach (array('member' => 'Thành viên', 'moderator' => 'Kiểm duyệt', 'admin' => 'Quản trị') as $k => $t): ?>
                <option value="<?= $k ?>" <?= $this->input->get('role') === $k ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>Giới tính</label>
        <select name="gender">
            <option value="">Tất cả</option>
            <option value="female" <?= $this->input->get('gender') === 'female' ? 'selected' : '' ?>>Nữ</option>
            <option value="male" <?= $this->input->get('gender') === 'male' ? 'selected' : '' ?>>Nam</option>
        </select>
    </div>
    <div><button class="btn btn-primary" type="submit">Lọc</button></div>
</form>

<div class="panel">
    <div class="panel-head"><h2>Tổng <?= number_format($total) ?> thành viên</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr><th>Thành viên</th><th>Liên hệ</th><th>Giới tính</th><th>Khu vực</th><th>Xu</th>
                <th>VIP</th><th>Hồ sơ</th><th>Trạng thái</th><th>Hoạt động</th><th>Thao tác</th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><a href="<?= site_url('admin/users/view/' . $u['id']) ?>"><?= e($u['display_name']) ?></a></td>
                    <td><?= e($u['email'] ?: $u['phone']) ?></td>
                    <td><?= gender_label($u['gender']) ?></td>
                    <td><?= e($u['province_name']) ?></td>
                    <td><?= number_format($u['coin_balance']) ?></td>
                    <td><?= $u['is_vip'] ? '<span class="badge bg-warning">VIP</span>' : '—' ?></td>
                    <td><?= (int) $u['profile_score'] ?>%</td>
                    <td><?= status_label($u['status']) ?></td>
                    <td><?= time_ago($u['last_active_at']) ?></td>
                    <td class="actions">
                        <a class="btn btn-light btn-sm" href="<?= site_url('admin/users/edit/' . $u['id']) ?>">Sửa</a>
                        <?php if ($u['status'] === 'active'): ?>
                            <a class="btn btn-danger btn-sm" href="<?= site_url('admin/users/set_status/' . $u['id'] . '/locked') ?>">Khoá</a>
                        <?php else: ?>
                            <a class="btn btn-success btn-sm" href="<?= site_url('admin/users/set_status/' . $u['id'] . '/active') ?>">Mở</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $pagination ?>
