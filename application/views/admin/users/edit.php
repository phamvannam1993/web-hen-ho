<?php defined('BASEPATH') OR exit('No direct script access allowed');
$v = function ($k) use ($u) { return e($u[$k] ?? ''); };
?>
<form method="post">
    <div class="panel">
        <div class="panel-head"><h2>Thông tin thành viên</h2></div>
        <div class="panel-body form-grid">
            <div><label>Họ và tên</label><input type="text" name="display_name" value="<?= $v('display_name') ?>" required></div>
            <div><label>Biệt danh</label><input type="text" name="nickname" value="<?= $v('nickname') ?>" maxlength="60"></div>
            <div><label>Email</label><input type="email" name="email" value="<?= $v('email') ?>"></div>
            <div><label>Điện thoại</label><input type="text" name="phone" value="<?= $v('phone') ?>"></div>
            <div>
                <label>Giới tính</label>
                <select name="gender">
                    <?php foreach (array('female' => 'Nữ', 'male' => 'Nam', 'other' => 'Khác') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= $u['gender'] === $k ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Ngày sinh</label><input type="date" name="birthday" value="<?= $v('birthday') ?>"></div>
            <div>
                <label>Tỉnh/thành</label>
                <select name="province_id">
                    <option value="">-- Chọn --</option>
                    <?php foreach ($provinces as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= $u['province_id'] == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Nghề nghiệp</label><input type="text" name="job" value="<?= $v('job') ?>"></div>
            <div><label>Mật khẩu mới</label><input type="password" name="password" placeholder="Để trống nếu không đổi"></div>
            <div class="full"><label>Giới thiệu</label><textarea name="bio"><?= $v('bio') ?></textarea></div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>Phân quyền &amp; trạng thái</h2></div>
        <div class="panel-body form-grid">
            <div>
                <label>Vai trò</label>
                <select name="role">
                    <?php foreach (array('member' => 'Thành viên', 'moderator' => 'Kiểm duyệt', 'admin' => 'Quản trị') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= $u['role'] === $k ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Trạng thái</label>
                <select name="status">
                    <?php foreach (array('active' => 'Hoạt động', 'pending' => 'Chờ duyệt', 'locked' => 'Tạm khoá', 'banned' => 'Cấm') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= $u['status'] === $k ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Xác minh danh tính</label>
                <select name="kyc_status">
                    <?php foreach (array('none' => 'Chưa gửi', 'pending' => 'Chờ duyệt', 'verified' => 'Đã xác minh', 'rejected' => 'Từ chối') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= $u['kyc_status'] === $k ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="actions">
        <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
        <a class="btn btn-light" href="<?= site_url('admin/users/view/' . $u['id']) ?>">Quay lại</a>
    </div>
</form>
