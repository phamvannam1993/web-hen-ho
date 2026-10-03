<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Trang Hồ sơ của tôi — dựng theo `src/routes/tai-khoan.ho-so.tsx`.
 * Vẫn là MỘT biểu mẫu, giữ nguyên tên mọi ô như bản cũ.
 */

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

// Dùng chung hàm với trang Tổng quan — cột profile_score trong DB hay cũ
$diem = tk_ho_so_day_du($me)['phan_tram'];
$sao  = '<span class="tk-req">*</span>';

// Sở thích: vừa gửi lên thì lấy đúng ô người dùng đã tích, không lấy lại trong CSDL
$dang_chon = $da_gui ? array_map('intval', (array) $this->input->post('interests')) : $my_interests;

$hien_online = $da_gui ? $this->input->post('show_online')
                       : (!isset($pref['show_online']) || $pref['show_online']);
?>
<div class="tk-ph">
    <div class="tk-ph__b">
        <h1>Hồ sơ của tôi</h1>
        <p>Thông tin càng đầy đủ, cơ hội ghép đôi càng cao. Trường có dấu * là bắt buộc.</p>
    </div>
    <a class="tk-btn tk-btn--outline tk-ph__act" href="<?= site_url('profile/' . $me['slug']) ?>"><?= tk_icon('eye') ?>Xem trước trang cá nhân</a>
</div>

<form class="tk-pf auth-form" method="post" enctype="multipart/form-data">
    <?php if (validation_errors()): ?>
        <div class="tk-alert tk-alert--danger"><?= validation_errors() ?></div>
    <?php endif; ?>

    <!-- Mức hoàn thiện -->
    <section class="tk-card">
        <div class="tk-pf-pc">
            <span class="tk-pf-pc__t">Mức hoàn thiện hồ sơ</span>
            <span class="tk-pf-pc__n"><?= $diem ?>%</span>
        </div>
        <div class="tk-progress" style="margin-top:8px" role="progressbar" aria-valuenow="<?= $diem ?>" aria-valuemin="0" aria-valuemax="100">
            <i style="width:<?= max(0, min(100, $diem)) ?>%"></i>
        </div>
        <?php if (!empty($thieu)): ?>
            <?php /* Thiếu mục cần để hiện công khai: không khoá gì, chỉ báo hồ sơ đang bị ẩn */ ?>
            <div class="tk-alert tk-alert--warning" style="margin-top:16px">
                <p>Hồ sơ của bạn <b>chưa hiển thị với mọi người</b>. Thêm
                    <b><?= e(mb_strtolower(implode(', ', $thieu))) ?></b> để xuất hiện trong danh sách
                    Hẹn hò, Ghép đôi ẩn và Thành viên.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- Ảnh đại diện -->
    <section class="tk-card">
        <div class="tk-card__h">
            <div>
                <h2 class="tk-card__t">Ảnh đại diện</h2>
                <p class="tk-card__d">Cần có ảnh thì hồ sơ mới hiện với mọi người. Ảnh rõ mặt nhận nhiều lượt thích hơn.</p>
            </div>
        </div>
        <div class="tk-pf-av">
            <img id="tk-pf-av-img" src="<?= e(avatar_url($me['avatar'] ?? null, $me['gender'] ?? 'other')) ?>" alt="Ảnh đại diện" width="96" height="96">
            <div>
                <?php /* Ô chọn tệp thật nằm trong nhãn, nhãn mang dáng nút */ ?>
                <label class="tk-btn tk-btn--brand tk-pf-file" for="avatar">
                    <?= tk_icon('camera') ?>Đổi ảnh đại diện
                    <input type="file" id="avatar" name="avatar" accept="image/*" data-preview="#tk-pf-av-img">
                </label>
                <p class="tk-hint" style="margin-top:8px">JPG, PNG, WEBP hoặc GIF, tối đa 5MB.</p>
            </div>
        </div>
    </section>

    <!-- Thông tin cơ bản -->
    <section class="tk-card">
        <div class="tk-card__h"><div><h2 class="tk-card__t">Thông tin cơ bản</h2></div></div>
        <div class="tk-grid tk-grid--2">
            <div class="tk-field">
                <label for="display_name">Họ và tên <?= $sao ?></label>
                <input type="text" id="display_name" name="display_name" value="<?= $v('display_name') ?>" required>
                <p class="tk-hint">Có biệt danh thì mọi nơi công khai sẽ hiện biệt danh thay cho họ tên.</p>
            </div>
            <div class="tk-field">
                <label for="nickname">Biệt danh</label>
                <input type="text" id="nickname" name="nickname" value="<?= $v('nickname') ?>"
                       placeholder="VD: Bằng Lăng Tím" maxlength="60">
                <p class="tk-hint">Có thể dùng thay cho họ tên thật</p>
            </div>
            <div class="tk-field">
                <label for="phone">Số điện thoại Zalo</label>
                <input type="tel" id="phone" name="phone" value="<?= $v('phone') ?>"
                       maxlength="15" inputmode="tel" autocomplete="tel" placeholder="VD: 0912345678">
                <p class="tk-hint">Không bắt buộc. Có số thì người đã mở liên hệ với bạn mới xem được.</p>
            </div>
            <div class="tk-field">
                <label for="gender">Giới tính <?= $sao ?></label>
                <select id="gender" name="gender" required>
                    <option value="">-- Chọn --</option>
                    <?php foreach (array('female' => 'Nữ', 'male' => 'Nam', 'other' => 'Khác') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= $chon('gender', $k) ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tk-field">
                <label for="birthday">Ngày sinh <?= $sao ?></label>
                <input type="date" id="birthday" name="birthday" required value="<?= $v('birthday') ?>">
            </div>
            <div class="tk-field">
                <label for="province_id">Khu vực</label>
                <?php /* 34 tỉnh thành cuộn rất mỏi trên điện thoại — cho gõ để tìm */ ?>
                <select id="province_id" name="province_id" data-searchable data-search-placeholder="Tìm tỉnh/thành...">
                    <option value="">-- Chọn tỉnh/thành --</option>
                    <?php foreach ($provinces as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= $chon('province_id', $p['id']) ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <!-- Thông tin cá nhân -->
    <section class="tk-card">
        <div class="tk-card__h"><div><h2 class="tk-card__t">Thông tin cá nhân</h2></div></div>
        <div class="tk-grid tk-pf-g3">
            <div class="tk-field">
                <label for="height_cm">Chiều cao (cm)</label>
                <input type="number" id="height_cm" name="height_cm" value="<?= $v('height_cm') ?>">
            </div>
            <div class="tk-field">
                <label for="weight_kg">Cân nặng (kg)</label>
                <input type="number" id="weight_kg" name="weight_kg" value="<?= $v('weight_kg') ?>">
            </div>
            <div class="tk-field">
                <label for="job">Nghề nghiệp</label>
                <?php
                // Nghề cũ đã bị gỡ khỏi danh mục vẫn phải hiện ra, kẻo lưu hồ sơ là mất
                $nghe_hien = (string) ($me['job'] ?? '');
                $ds_nghe   = ($nghe_hien !== '' && !in_array($nghe_hien, $jobs, true))
                    ? array_merge(array($nghe_hien), $jobs) : $jobs;
                ?>
                <?php /* Ô chọn thường; JS nâng cấp thành ô chọn có tìm kiếm */ ?>
                <select id="job" name="job" data-searchable data-search-placeholder="Tìm nghề...">
                    <option value="">-- Chọn nghề nghiệp --</option>
                    <?php foreach ($ds_nghe as $ten): ?>
                        <option value="<?= e($ten) ?>" <?= $chon('job', $ten) ?>><?= e($ten) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tk-field">
                <label for="education">Học vấn</label>
                <select id="education" name="education">
                    <option value="">-- Chọn --</option>
                    <?php foreach (array('thpt' => 'THPT', 'trung_cap' => 'Trung cấp', 'cao_dang' => 'Cao đẳng',
                                         'dai_hoc' => 'Đại học', 'sau_dai_hoc' => 'Sau đại học') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= $chon('education', $k) ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tk-field">
                <label for="marital_status">Tình trạng hôn nhân</label>
                <select id="marital_status" name="marital_status">
                    <option value="">-- Chọn --</option>
                    <?php foreach (array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= $chon('marital_status', $k) ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tk-field">
                <label for="has_children">Con cái</label>
                <select id="has_children" name="has_children">
                    <option value="">-- Chọn --</option>
                    <option value="0" <?= $chon('has_children', '0') ?>>Chưa có con</option>
                    <option value="1" <?= $chon('has_children', '1') ?>>Đã có con</option>
                </select>
            </div>
            <div class="tk-field">
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
            <div class="tk-field">
                <label for="smoking">Hút thuốc</label>
                <select id="smoking" name="smoking">
                    <option value="">-- Chọn --</option>
                    <?php foreach (array('khong' => 'Không', 'thinh_thoang' => 'Thỉnh thoảng', 'thuong_xuyen' => 'Thường xuyên') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= $chon('smoking', $k) ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tk-field">
                <label for="drinking">Uống rượu bia</label>
                <select id="drinking" name="drinking">
                    <option value="">-- Chọn --</option>
                    <?php foreach (array('khong' => 'Không', 'thinh_thoang' => 'Thỉnh thoảng', 'thuong_xuyen' => 'Thường xuyên') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= $chon('drinking', $k) ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <!-- Sở thích -->
    <section class="tk-card">
        <div class="tk-card__h">
            <div>
                <h2 class="tk-card__t">Sở thích</h2>
                <p class="tk-card__d">Chọn nhiều sở thích để hệ thống gợi ý chính xác hơn</p>
            </div>
        </div>
        <div class="tk-chips">
            <?php foreach ($all_interests as $it): ?>
                <?php $on = in_array((int) $it['id'], $dang_chon, true); ?>
                <label class="tk-chip">
                    <input type="checkbox" name="interests[]" value="<?= (int) $it['id'] ?>" <?= $on ? 'checked' : '' ?>>
                    <?= e($it['name']) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Giới thiệu -->
    <section class="tk-card">
        <div class="tk-card__h"><div><h2 class="tk-card__t">Giới thiệu bản thân</h2></div></div>
        <div class="tk-field">
            <label for="bio">Vài dòng về bạn</label>
            <textarea id="bio" name="bio" rows="5" maxlength="500"
                      placeholder="Mình thích những buổi sáng yên tĩnh và một ly cà phê đen..."><?= $v('bio') ?></textarea>
        </div>
    </section>

    <!-- Tiêu chí tìm kiếm -->
    <section class="tk-card">
        <div class="tk-card__h"><div><h2 class="tk-card__t">Tiêu chí tìm kiếm</h2></div></div>
        <div class="tk-grid tk-grid--2">
            <div class="tk-field">
                <label for="seeking_gender">Giới tính muốn tìm <?= $sao ?></label>
                <select id="seeking_gender" name="seeking_gender" required>
                    <?php foreach (array('all' => 'Tất cả', 'female' => 'Nữ', 'male' => 'Nam') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= $chon('seeking_gender', $k, 'all') ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tk-field">
                <label for="purpose">Mục đích kết nối <?= $sao ?></label>
                <select id="purpose" name="purpose" required>
                    <option value="">-- Chọn --</option>
                    <?php foreach (array('ket_ban', 'hen_ho', 'nghiem_tuc', 'ket_hon') as $k): ?>
                        <option value="<?= $k ?>" <?= $chon('purpose', $k) ?>><?= purpose_label($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tk-field">
                <label for="age_min">Tuổi từ</label>
                <input type="number" id="age_min" name="age_min" value="<?= $pv('age_min', '18') ?>">
            </div>
            <div class="tk-field">
                <label for="age_max">Tuổi đến</label>
                <input type="number" id="age_max" name="age_max" value="<?= $pv('age_max', '60') ?>">
            </div>
        </div>
    </section>

    <!-- Quyền riêng tư -->
    <section class="tk-card">
        <div class="tk-card__h"><div><h2 class="tk-card__t">Quyền riêng tư</h2></div></div>
        <div class="tk-toggles">
            <?php /* Mọi tin nhắn đều đã yêu cầu ghép đôi, mục này chỉ để siết thêm */ ?>
            <div class="tk-toggle tk-pf-sel">
                <span class="tk-toggle__b">
                    <label class="tk-toggle__t" for="allow_message">Ai được nhắn tin cho tôi</label>
                    <span class="tk-toggle__d">Chỉ người đã ghép đôi với bạn mới nhắn tin được. Bạn có thể siết thêm ở đây.</span>
                </span>
                <span class="tk-field">
                    <select id="allow_message" name="allow_message">
                        <?php foreach (array('all' => 'Mọi người đã ghép đôi', 'vip' => 'Chỉ người đã ghép đôi và là VIP') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= $chon('allow_message', $k, 'all') ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </div>
            <label class="tk-toggle">
                <span class="tk-toggle__b">
                    <span class="tk-toggle__t">Hiển thị trạng thái online</span>
                    <span class="tk-toggle__d">Người khác thấy bạn đang hoạt động.</span>
                </span>
                <span class="tk-switch">
                    <input type="checkbox" name="show_online" value="1" <?= $hien_online ? 'checked' : '' ?>>
                    <span class="tk-switch__track"></span><span class="tk-switch__knob"></span>
                </span>
            </label>
        </div>
    </section>

    <div class="tk-pf-save">
        <div class="tk-pf-save__text">
            <strong>Hồ sơ của bạn</strong>
            <p>Lưu lại sau khi cập nhật thông tin nhé.</p>
        </div>
        <button class="tk-btn tk-btn--brand tk-btn--lg" type="submit"><?= tk_icon('save') ?>Lưu hồ sơ</button>
    </div>
</form>
