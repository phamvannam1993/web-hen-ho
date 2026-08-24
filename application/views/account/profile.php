<?php defined('BASEPATH') OR exit('No direct script access allowed');
$v = function ($k, $d = '') use ($me) { return e($me[$k] ?? $d); };
$pv = function ($k, $d = '') use ($pref) { return e($pref[$k] ?? $d); };
?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <form class="content-box auth-form" method="post" enctype="multipart/form-data">
            <h1 class="auth-title">Hồ sơ của tôi</h1>
            <?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

            <div class="profile-avatar">
                <img src="<?= avatar_url($me['avatar'], $me['gender']) ?>" alt="Ảnh đại diện">
                <div>
                    <label for="avatar">Đổi ảnh đại diện</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*">
                    <p>Hoàn thiện hồ sơ: <b><?= (int) $me['profile_score'] ?>%</b></p>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="display_name">Họ và tên</label>
                    <input type="text" id="display_name" name="display_name" value="<?= $v('display_name') ?>" required>
                </div>
                <div>
                    <label for="nickname">Biệt danh</label>
                    <input type="text" id="nickname" name="nickname" value="<?= $v('nickname') ?>"
                           placeholder="VD: Bằng Lăng Tím" maxlength="60">
                </div>
            </div>
            <small class="field-hint">Có biệt danh thì mọi nơi công khai sẽ hiện biệt danh thay cho họ tên.</small>

            <div class="form-row">
                <div>
                    <label for="gender">Giới tính</label>
                    <select id="gender" name="gender">
                        <?php foreach (array('female' => 'Nữ', 'male' => 'Nam', 'other' => 'Khác') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= $me['gender'] === $k ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="birthday">Ngày sinh</label>
                    <input type="date" id="birthday" name="birthday" value="<?= $v('birthday') ?>">
                </div>
            </div>

            <label for="province_id">Khu vực</label>
            <select id="province_id" name="province_id">
                <option value="">-- Chọn tỉnh/thành --</option>
                <?php foreach ($provinces as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $me['province_id'] == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <div class="form-row">
                <div><label for="height_cm">Chiều cao (cm)</label><input type="text" id="height_cm" name="height_cm" value="<?= $v('height_cm') ?>"></div>
                <div><label for="weight_kg">Cân nặng (kg)</label><input type="text" id="weight_kg" name="weight_kg" value="<?= $v('weight_kg') ?>"></div>
            </div>

            <label for="job">Nghề nghiệp</label>
            <input type="text" id="job" name="job" value="<?= $v('job') ?>">

            <div class="form-row">
                <div>
                    <label for="education">Học vấn</label>
                    <select id="education" name="education">
                        <option value="">-- Chọn --</option>
                        <?php foreach (array('thpt' => 'THPT', 'trung_cap' => 'Trung cấp', 'cao_dang' => 'Cao đẳng',
                                             'dai_hoc' => 'Đại học', 'sau_dai_hoc' => 'Sau đại học') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= $me['education'] === $k ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="marital_status">Tình trạng hôn nhân</label>
                    <select id="marital_status" name="marital_status">
                        <option value="">-- Chọn --</option>
                        <?php foreach (array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= $me['marital_status'] === $k ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <label for="bio">Giới thiệu bản thân</label>
            <textarea id="bio" name="bio" rows="4"><?= $v('bio') ?></textarea>

            <h2 class="section-title">Tiêu chí tìm kiếm</h2>

            <div class="form-row">
                <div>
                    <label for="seeking_gender">Muốn tìm</label>
                    <select id="seeking_gender" name="seeking_gender">
                        <?php foreach (array('all' => 'Tất cả', 'female' => 'Nữ', 'male' => 'Nam') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= ($pref['seeking_gender'] ?? 'all') === $k ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="purpose">Mục đích</label>
                    <select id="purpose" name="purpose">
                        <?php foreach (array('ket_ban', 'hen_ho', 'nghiem_tuc', 'ket_hon') as $k): ?>
                            <option value="<?= $k ?>" <?= ($pref['purpose'] ?? '') === $k ? 'selected' : '' ?>><?= purpose_label($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div><label for="age_min">Tuổi từ</label><input type="number" id="age_min" name="age_min" value="<?= $pv('age_min', '18') ?>"></div>
                <div><label for="age_max">Đến</label><input type="number" id="age_max" name="age_max" value="<?= $pv('age_max', '60') ?>"></div>
            </div>

            <label for="allow_message">Ai được nhắn tin cho tôi</label>
            <select id="allow_message" name="allow_message">
                <?php foreach (array('all' => 'Mọi thành viên', 'vip' => 'Chỉ thành viên VIP', 'matched' => 'Chỉ người đã ghép đôi') as $k => $t): ?>
                    <option value="<?= $k ?>" <?= ($pref['allow_message'] ?? 'all') === $k ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>

            <label class="checkbox">
                <input type="checkbox" name="show_online" value="1" <?= !isset($pref['show_online']) || $pref['show_online'] ? 'checked' : '' ?>>
                Hiển thị trạng thái online
            </label>

            <div class="auth-actions">
                <button class="btn btn-primary" type="submit">Lưu hồ sơ</button>
                <a class="btn btn-ghost" href="<?= site_url('profile/' . $me['slug']) ?>">Xem trang cá nhân</a>
            </div>
        </form>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Mẹo tăng tương tác</h3>
            <ul class="sidebar-list">
                <li>Dùng ảnh thật, rõ mặt</li>
                <li>Viết giới thiệu từ 100 chữ trở lên</li>
                <li>Cập nhật khu vực và nghề nghiệp</li>
                <li>Đăng nhập thường xuyên để lên đầu danh sách</li>
            </ul>
        </div>
    </aside>
</div>
