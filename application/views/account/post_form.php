<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Đăng / sửa tin hẹn hò — bản thiết kế không có trang này, dựng bằng bộ kit.
 * Dùng chung cho /dang-tin và /tai-khoan/sua-tin/<id>.
 */
$v = function ($k, $d = '') use ($p) { return e($p[$k] ?? $d); };

// Phần đầu thẻ có số thứ tự nhóm
$dau_the = function ($so, $tieu_de, $mo_ta = '') {
    return '<div class="tk-card__h"><div class="tk-pf-h"><span class="tk-pf-h__n">' . $so . '</span><div style="min-width:0">'
        . '<h2 class="tk-card__t">' . $tieu_de . '</h2>'
        . ($mo_ta ? '<p class="tk-card__d">' . $mo_ta . '</p>' : '') . '</div></div></div>';
};
?>
<header class="tk-ph">
    <div class="tk-ph__b">
        <h1><?= e($title) ?></h1>
        <p>Điền càng đầy đủ, tin của bạn càng dễ được duyệt và tiếp cận đúng người.</p>
    </div>
    <div class="tk-ph__act">
        <a class="tk-btn tk-btn--outline" href="<?= site_url('tai-khoan/tin-dang') ?>" aria-label="Về danh sách tin"><?= tk_icon('arrow-left') ?><span class="tk-only-desktop">Tin của tôi</span></a>
    </div>
</header>

<?php if (validation_errors()): ?>
    <?= validation_errors('<div class="tk-alert tk-alert--danger">', '</div>') ?>
<?php endif; ?>

<form class="post-form" method="post" enctype="multipart/form-data" style="display:contents">

    <!-- Nhóm 1: nội dung tin -->
    <section class="tk-card">
        <?= $dau_the(1, 'Nội dung tin', 'Tiêu đề và nội dung là phần người xem đọc đầu tiên') ?>
        <div class="tk-stack tk-stack--sm tk-pf-stack">
            <div class="tk-field">
                <label for="title">Tiêu đề tin <span class="tk-req">*</span></label>
                <input type="text" id="title" name="title" value="<?= $v('title') ?>" required
                       placeholder="VD: Nữ 29 tuổi Biên Hoà tìm bạn trai nghiêm túc">
                <p class="tk-hint">Tối thiểu 10 ký tự, nên có giới tính, tuổi và khu vực.</p>
            </div>

            <div class="tk-grid tk-grid--2">
                <div class="tk-field">
                    <label for="category_id">Chuyên mục <span class="tk-req">*</span></label>
                    <select id="category_id" name="category_id" required>
                        <option value="">-- Chọn chuyên mục --</option>
                        <?php foreach ($post_categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= ($p['category_id'] ?? null) == $c['id'] ? 'selected' : '' ?>>
                                <?= $c['parent_id'] ? '— ' : '' ?><?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tk-field">
                    <label for="province_id">Tỉnh/thành</label>
                    <select id="province_id" name="province_id">
                        <option value="">-- Chọn --</option>
                        <?php foreach ($provinces as $pr): ?>
                            <option value="<?= (int) $pr['id'] ?>" <?= ($p['province_id'] ?? null) == $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tk-field">
                    <label for="nickname">Tên hiển thị trên tin</label>
                    <input type="text" id="nickname" name="nickname" value="<?= $v('nickname') ?>" placeholder="VD: Kim Ngọc">
                </div>
                <div class="tk-field">
                    <label for="district">Quận/huyện</label>
                    <input type="text" id="district" name="district" value="<?= $v('district') ?>" placeholder="VD: Cầu Giấy">
                </div>
            </div>

            <div class="tk-field">
                <label for="intro">Giới thiệu ngắn</label>
                <input type="text" id="intro" name="intro" value="<?= $v('intro') ?>" placeholder="VD: Vui vẻ, hoà đồng">
            </div>

            <div class="tk-field">
                <label for="content">Nội dung chi tiết <span class="tk-req">*</span></label>
                <textarea id="content" name="content" rows="6" required
                          placeholder="Giới thiệu về bản thân, mong muốn của bạn ở đối phương..."><?= $v('content') ?></textarea>
                <p class="tk-hint">Tối thiểu 30 ký tự. Không ghi số điện thoại trong nội dung, hãy điền ở mục liên hệ.</p>
            </div>
        </div>
    </section>

    <!-- Nhóm 2: thông tin cá nhân hiển thị trên tin -->
    <section class="tk-card">
        <?= $dau_the(2, 'Thông tin cá nhân', 'Hiển thị trên tin để người xem hiểu về bạn') ?>
        <div class="tk-stack tk-stack--sm tk-pf-stack">
            <div class="tk-grid tk-grid--2">
                <div class="tk-field">
                    <label for="gender">Giới tính của bạn</label>
                    <select id="gender" name="gender">
                        <?php foreach (array('female' => 'Nữ', 'male' => 'Nam', 'other' => 'Khác') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= ($p['gender'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tk-field">
                    <label for="seeking">Bạn muốn tìm</label>
                    <select id="seeking" name="seeking">
                        <?php foreach (array('all' => 'Tất cả', 'male' => 'Nam', 'female' => 'Nữ') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= ($p['seeking'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="tk-grid tk-grid--3 tk-pf-g3">
                <div class="tk-field">
                    <label for="age">Tuổi</label>
                    <input type="number" id="age" name="age" value="<?= $v('age') ?>" min="18" max="99" placeholder="29">
                </div>
                <div class="tk-field">
                    <label for="height_cm">Chiều cao (cm)</label>
                    <input type="number" id="height_cm" name="height_cm" value="<?= $v('height_cm') ?>" placeholder="160">
                </div>
                <div class="tk-field">
                    <label for="weight_kg">Cân nặng (kg)</label>
                    <input type="number" id="weight_kg" name="weight_kg" value="<?= $v('weight_kg') ?>" placeholder="48">
                </div>
            </div>

            <div class="tk-grid tk-grid--2">
                <div class="tk-field">
                    <label for="marital_status">Tình trạng hôn nhân</label>
                    <select id="marital_status" name="marital_status">
                        <option value="">-- Chọn --</option>
                        <?php foreach (array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly dị', 'goa' => 'Goá',
                                             'dang_co_nguoi_yeu' => 'Đang có người yêu', 'phuc_tap' => 'Phức tạp') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= ($p['marital_status'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tk-field">
                    <label for="job">Nghề nghiệp</label>
                    <input type="text" id="job" name="job" value="<?= $v('job') ?>" placeholder="VD: Nhân viên văn phòng">
                </div>
                <div class="tk-field">
                    <label for="personality">Tính cách</label>
                    <input type="text" id="personality" name="personality" value="<?= $v('personality') ?>" placeholder="VD: Hiền lành, chan hoà">
                </div>
                <div class="tk-field">
                    <label for="purpose">Mục đích</label>
                    <select id="purpose" name="purpose">
                        <?php foreach (array('ket_ban', 'hen_ho', 'nghiem_tuc', 'ket_hon') as $k): ?>
                            <option value="<?= $k ?>" <?= ($p['purpose'] ?? '') === $k ? 'selected' : '' ?>><?= purpose_label($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="tk-field">
                <label for="wish">Mong muốn ở người ấy</label>
                <input type="text" id="wish" name="wish" value="<?= $v('wish') ?>" placeholder="VD: Chân thành, nghiêm túc, biết quan tâm">
            </div>
        </div>
    </section>

    <!-- Nhóm 3: liên hệ -->
    <section class="tk-card">
        <?= $dau_the(3, 'Thông tin liên hệ') ?>
        <div class="tk-stack tk-stack--sm tk-pf-stack">
            <div class="tk-grid tk-grid--2">
                <div class="tk-field">
                    <label for="contact_type">Kênh liên hệ</label>
                    <select id="contact_type" name="contact_type">
                        <?php foreach (array('phone' => 'Số điện thoại', 'zalo' => 'Zalo', 'facebook' => 'Facebook', 'app' => 'Nhắn tin trên web') as $k => $t): ?>
                            <option value="<?= $k ?>" <?= ($p['contact_type'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tk-field">
                    <label for="contact_value">Thông tin liên hệ</label>
                    <input type="text" id="contact_value" name="contact_value" value="<?= $v('contact_value') ?>" placeholder="VD: 0912xxxxxx">
                </div>
            </div>
            <p class="tk-pf-lock"><?= tk_icon('lock') ?><span>Thông tin này luôn được che, chỉ hiện với người đã dùng pass để mở khoá.</span></p>
        </div>
    </section>

    <!-- Nhóm 4: hình ảnh -->
    <section class="tk-card">
        <?= $dau_the(4, 'Hình ảnh', 'JPG, PNG, WEBP — tối đa 5MB mỗi ảnh') ?>
        <div class="tk-grid tk-grid--2">
            <div class="tk-field">
                <label for="cover">Ảnh đại diện tin</label>
                <div class="tk-pf-file">
                    <span class="tk-ibox"><?= tk_icon('image') ?></span>
                    <input type="file" id="cover" name="cover" accept="image/*">
                </div>
                <p class="tk-hint">Ảnh này hiển thị ngoài trang chủ, nên chọn ảnh dọc rõ nét.</p>
            </div>
            <div class="tk-field">
                <label for="images">Ảnh khác (chọn nhiều)</label>
                <div class="tk-pf-file">
                    <span class="tk-ibox"><?= tk_icon('images') ?></span>
                    <input type="file" id="images" name="images[]" accept="image/*" multiple>
                </div>
                <p class="tk-hint">Có thể chọn nhiều ảnh cùng lúc.</p>
            </div>
        </div>

        <?php if (!empty($p['cover']) || $images): ?>
            <div class="tk-field" style="margin-top:16px">
                <span class="tk-label">Ảnh đã tải lên</span>
                <div class="tk-pf-imgs">
                    <?php if (!empty($p['cover'])): ?>
                        <figure><img src="<?= e(base_url(ltrim($p['cover'], '/'))) ?>" alt="" loading="lazy"><span class="tk-pill tk-pill--gold">Ảnh bìa</span></figure>
                    <?php endif; ?>
                    <?php foreach ($images as $img): ?>
                        <figure><img src="<?= e(base_url(ltrim($img['path'], '/'))) ?>" alt="" loading="lazy"></figure>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <section class="tk-card tk-pf-acts">
        <p class="tk-pf-acts__note"><?= tk_icon('info') ?><span>Tin sẽ hiển thị sau khi ban quản trị duyệt. Không đăng ảnh phản cảm, thông tin sai sự thật hoặc mời chào dịch vụ.</span></p>
        <div class="tk-row tk-pf-acts__btns">
            <a class="tk-btn tk-btn--outline" href="<?= site_url('tai-khoan/tin-dang') ?>">Huỷ</a>
            <button class="tk-btn tk-btn--brand" type="submit"><?= tk_icon('save') ?>Lưu tin</button>
        </div>
    </section>
</form>
