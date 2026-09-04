<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* Khi lưu hỏng vì thiếu ô nào đó, biểu mẫu phải giữ nguyên những gì người dùng
   vừa nhập chứ không đổ lại dữ liệu cũ trong cơ sở dữ liệu — bắt gõ lại từ đầu
   là cách nhanh nhất khiến người ta bỏ cuộc. */
$da_gui = ($this->input->method() === 'post');

/** Giá trị nên hiện ra: ưu tiên thứ vừa gửi lên, chưa gửi thì lấy trong CSDL. */
$goc = function ($k, $mac_dinh = '') use ($da_gui, $me, $pref) {
    if ($da_gui) {
        return isset($_POST[$k]) ? (string) $_POST[$k] : '';
    }
    if (array_key_exists($k, (array) $me))   return (string) ($me[$k] ?? $mac_dinh);
    if (array_key_exists($k, (array) $pref)) return (string) ($pref[$k] ?? $mac_dinh);
    return (string) $mac_dinh;
};
$v   = function ($k, $d = '') use ($goc) { return e($goc($k, $d)); };
$pv  = function ($k, $d = '') use ($goc) { return e($goc($k, $d)); };
/** In ra "selected" nếu giá trị này đang được chọn. */
$chon = function ($k, $gt, $d = '') use ($goc) {
    return $goc($k, $d) === (string) $gt ? 'selected' : '';
};
?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <form class="content-box auth-form" method="post" enctype="multipart/form-data">
            <h1 class="auth-title">Hồ sơ của tôi</h1>
            <p class="form-note">Mục có dấu <b class="req">*</b> là bắt buộc. Các mục còn lại tuỳ chọn, khai thêm thì hồ sơ dễ được tìm thấy hơn.</p>
            <?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

            <?php if (!empty($thieu)): ?>
                <?php
                // Thanh tiến độ cho thấy còn bao nhiêu mục nữa là xong
                $tong = (int) ($tong_muc ?? 8);
                $xong = max(0, $tong - count($thieu));
                ?>
                <div class="ho-so-nhac">
                    <div class="ho-so-nhac-dau">
                        <b>Hồ sơ chưa hoàn thiện</b>
                        <span><?= $xong ?>/<?= $tong ?> mục</span>
                    </div>
                    <div class="ho-so-thanh"><i style="width: <?= round($xong / $tong * 100) ?>%"></i></div>
                    <p>Bạn cần khai nốt <?= count($thieu) ?> mục dưới đây thì mới dùng được các trang khác:</p>
                    <ul>
                        <?php foreach ($thieu as $t): ?><li><?= e($t) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="profile-avatar">
                <img src="<?= avatar_url($me['avatar'], $me['gender']) ?>" alt="Ảnh đại diện">
                <div>
                    <label for="avatar">Ảnh đại diện <b class="req">*</b></label>
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
                    <label for="phone">Số điện thoại (Zalo) <b class="req">*</b></label>
                    <input type="tel" id="phone" name="phone" value="<?= $v('phone') ?>"
                           maxlength="15" inputmode="tel" placeholder="VD: 0912345678" required>
                    <small class="field-hint">Số di động Việt Nam, 10 chữ số. Chỉ thành viên đã đăng nhập mới xem được.</small>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="gender">Giới tính <b class="req">*</b></label>
                    <select id="gender" name="gender" required>
                        <option value="">-- Chọn --</option>
                        <?php foreach (array('female' => 'Nữ', 'male' => 'Nam', 'other' => 'Khác') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= $chon('gender', $k) ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="birthday">Ngày sinh <b class="req">*</b></label>
                    <input type="date" id="birthday" name="birthday" required value="<?= $v('birthday') ?>">
                </div>
            </div>

            <label for="province_id">Khu vực <b class="req">*</b></label>
            <select id="province_id" name="province_id" required>
                <option value="">-- Chọn tỉnh/thành --</option>
                <?php foreach ($provinces as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $chon('province_id', $p['id']) ?>><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <div class="form-row">
                <div><label for="height_cm">Chiều cao (cm)</label><input type="number" id="height_cm" name="height_cm" value="<?= $v('height_cm') ?>"></div>
                <div><label for="weight_kg">Cân nặng (kg)</label><input type="number" id="weight_kg" name="weight_kg" value="<?= $v('weight_kg') ?>"></div>
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
                            <option value="<?= $k ?>" <?= $chon('education', $k) ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="marital_status">Tình trạng hôn nhân</label>
                    <select id="marital_status" name="marital_status">
                        <option value="">-- Chọn --</option>
                        <?php foreach (array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= $chon('marital_status', $k) ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="has_children">Con cái</label>
                    <select id="has_children" name="has_children">
                        <option value="">-- Chọn --</option>
                        <option value="0" <?= $chon('has_children', '0') ?>>Chưa có con</option>
                        <option value="1" <?= $chon('has_children', '1') ?>>Đã có con</option>
                    </select>
                </div>
                <div>
                    <label for="confide_topic">Chủ đề muốn tâm sự</label>
                    <select id="confide_topic" name="confide_topic">
                        <option value="">-- Chọn --</option>
                        <?php foreach (array('lang_nghe' => 'Cần người lắng nghe', 'tro_chuyen' => 'Trò chuyện phiếm',
                                             'cong_viec' => 'Chia sẻ công việc', 'gia_dinh' => 'Chuyện gia đình',
                                             'tinh_cam' => 'Chuyện tình cảm', 'dem_khuya' => 'Trò chuyện đêm khuya') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= $chon('confide_topic', $k) ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="smoking">Hút thuốc</label>
                    <select id="smoking" name="smoking">
                        <option value="">-- Chọn --</option>
                        <?php foreach (array('khong' => 'Không', 'thinh_thoang' => 'Thỉnh thoảng', 'thuong_xuyen' => 'Thường xuyên') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= $chon('smoking', $k) ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="drinking">Uống rượu bia</label>
                    <select id="drinking" name="drinking">
                        <option value="">-- Chọn --</option>
                        <?php foreach (array('khong' => 'Không', 'thinh_thoang' => 'Thỉnh thoảng', 'thuong_xuyen' => 'Thường xuyên') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= $chon('drinking', $k) ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <label>Sở thích <small>(chọn để được gợi ý chính xác hơn)</small></label>
            <div class="pick-row interest-pick">
                <?php foreach ($all_interests as $it): ?>
                    <?php
                    // Vừa gửi lên thì lấy đúng ô người dùng đã tích, không lấy lại trong CSDL
                    $dang_chon = $da_gui
                        ? array_map('intval', (array) $this->input->post('interests'))
                        : $my_interests;
                    $on = in_array((int) $it['id'], $dang_chon, true);
                    ?>
                    <label class="pick <?= $on ? 'on' : '' ?>">
                        <input type="checkbox" name="interests[]" value="<?= (int) $it['id'] ?>" <?= $on ? 'checked' : '' ?>>
                        <?= e($it['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <label for="bio">Giới thiệu bản thân <b class="req">*</b></label>
            <textarea id="bio" name="bio" rows="4" required><?= $v('bio') ?></textarea>

            <h2 class="section-title">Tiêu chí tìm kiếm</h2>

            <div class="form-row">
                <div>
                    <label for="seeking_gender">Muốn tìm <b class="req">*</b></label>
                    <select id="seeking_gender" name="seeking_gender" required>
                        <?php foreach (array('all' => 'Tất cả', 'female' => 'Nữ', 'male' => 'Nam') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= $chon('seeking_gender', $k, 'all') ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="purpose">Mục đích <b class="req">*</b></label>
                    <select id="purpose" name="purpose" required>
                        <option value="">-- Chọn --</option>
                        <?php foreach (array('ket_ban', 'hen_ho', 'nghiem_tuc', 'ket_hon') as $k): ?>
                            <option value="<?= $k ?>" <?= $chon('purpose', $k) ?>><?= purpose_label($k) ?></option>
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
                    <option value="<?= $k ?>" <?= $chon('allow_message', $k, 'all') ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>

            <label class="checkbox">
                <input type="checkbox" name="show_online" value="1"
                       <?= ($da_gui ? $this->input->post('show_online')
                                    : (!isset($pref['show_online']) || $pref['show_online'])) ? 'checked' : '' ?>>
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
