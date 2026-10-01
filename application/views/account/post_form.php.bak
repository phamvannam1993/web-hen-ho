<?php defined('BASEPATH') OR exit('No direct script access allowed');
$v = function ($k, $d = '') use ($p) { return e($p[$k] ?? $d); };
?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <form class="post-form auth-form" method="post" enctype="multipart/form-data">
            <header class="form-head">
                <h1><?= e($title) ?></h1>
                <p>Điền càng đầy đủ, tin của bạn càng dễ được duyệt và tiếp cận đúng người.</p>
            </header>

            <?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

            <!-- Nhóm 1: nội dung tin -->
            <section class="form-card">
                <h2 class="form-card-title"><span>1</span> Nội dung tin</h2>

                <div class="field">
                    <label for="title">Tiêu đề tin <em>*</em></label>
                    <input type="text" id="title" name="title" value="<?= $v('title') ?>" required
                           placeholder="VD: Nữ 29 tuổi Biên Hoà tìm bạn trai nghiêm túc">
                    <small>Tối thiểu 10 ký tự, nên có giới tính, tuổi và khu vực.</small>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label for="category_id">Chuyên mục <em>*</em></label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Chọn chuyên mục --</option>
                            <?php foreach ($post_categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= ($p['category_id'] ?? null) == $c['id'] ? 'selected' : '' ?>>
                                    <?= $c['parent_id'] ? '— ' : '' ?><?= e($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="province_id">Tỉnh/thành</label>
                        <select id="province_id" name="province_id">
                            <option value="">-- Chọn --</option>
                            <?php foreach ($provinces as $pr): ?>
                                <option value="<?= (int) $pr['id'] ?>" <?= ($p['province_id'] ?? null) == $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label for="nickname">Tên hiển thị trên tin</label>
                        <input type="text" id="nickname" name="nickname" value="<?= $v('nickname') ?>" placeholder="VD: Kim Ngọc">
                    </div>
                    <div class="field">
                        <label for="district">Quận/huyện</label>
                        <input type="text" id="district" name="district" value="<?= $v('district') ?>" placeholder="VD: Cầu Giấy">
                    </div>
                </div>

                <div class="field">
                    <label for="intro">Giới thiệu ngắn</label>
                    <input type="text" id="intro" name="intro" value="<?= $v('intro') ?>" placeholder="VD: Vui vẻ, hoà đồng">
                </div>

                <div class="field">
                    <label for="content">Nội dung chi tiết <em>*</em></label>
                    <textarea id="content" name="content" rows="6" required
                              placeholder="Giới thiệu về bản thân, mong muốn của bạn ở đối phương..."><?= $v('content') ?></textarea>
                    <small>Tối thiểu 30 ký tự. Không ghi số điện thoại trong nội dung, hãy điền ở mục liên hệ.</small>
                </div>
            </section>

            <!-- Nhóm 2: thông tin cá nhân hiển thị trên tin -->
            <section class="form-card">
                <h2 class="form-card-title"><span>2</span> Thông tin cá nhân</h2>

                <div class="form-row">
                    <div class="field">
                        <label for="gender">Giới tính của bạn</label>
                        <select id="gender" name="gender">
                            <?php foreach (array('female' => 'Nữ', 'male' => 'Nam', 'other' => 'Khác') as $k => $t): ?>
                                <option value="<?= $k ?>" <?= ($p['gender'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="seeking">Bạn muốn tìm</label>
                        <select id="seeking" name="seeking">
                            <?php foreach (array('all' => 'Tất cả', 'male' => 'Nam', 'female' => 'Nữ') as $k => $t): ?>
                                <option value="<?= $k ?>" <?= ($p['seeking'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row three">
                    <div class="field">
                        <label for="age">Tuổi</label>
                        <input type="number" id="age" name="age" value="<?= $v('age') ?>" min="18" max="99" placeholder="29">
                    </div>
                    <div class="field">
                        <label for="height_cm">Chiều cao (cm)</label>
                        <input type="number" id="height_cm" name="height_cm" value="<?= $v('height_cm') ?>" placeholder="160">
                    </div>
                    <div class="field">
                        <label for="weight_kg">Cân nặng (kg)</label>
                        <input type="number" id="weight_kg" name="weight_kg" value="<?= $v('weight_kg') ?>" placeholder="48">
                    </div>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label for="marital_status">Tình trạng hôn nhân</label>
                        <select id="marital_status" name="marital_status">
                            <option value="">-- Chọn --</option>
                            <?php foreach (array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly dị', 'goa' => 'Goá',
                                                 'dang_co_nguoi_yeu' => 'Đang có người yêu', 'phuc_tap' => 'Phức tạp') as $k => $t): ?>
                                <option value="<?= $k ?>" <?= ($p['marital_status'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="job">Nghề nghiệp</label>
                        <input type="text" id="job" name="job" value="<?= $v('job') ?>" placeholder="VD: Nhân viên văn phòng">
                    </div>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label for="personality">Tính cách</label>
                        <input type="text" id="personality" name="personality" value="<?= $v('personality') ?>" placeholder="VD: Hiền lành, chan hoà">
                    </div>
                    <div class="field">
                        <label for="purpose">Mục đích</label>
                        <select id="purpose" name="purpose">
                            <?php foreach (array('ket_ban', 'hen_ho', 'nghiem_tuc', 'ket_hon') as $k): ?>
                                <option value="<?= $k ?>" <?= ($p['purpose'] ?? '') === $k ? 'selected' : '' ?>><?= purpose_label($k) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label for="wish">Mong muốn ở người ấy</label>
                    <input type="text" id="wish" name="wish" value="<?= $v('wish') ?>" placeholder="VD: Chân thành, nghiêm túc, biết quan tâm">
                </div>
            </section>

            <!-- Nhóm 3: liên hệ -->
            <section class="form-card">
                <h2 class="form-card-title"><span>3</span> Thông tin liên hệ</h2>

                <div class="form-row">
                    <div class="field">
                        <label for="contact_type">Kênh liên hệ</label>
                        <select id="contact_type" name="contact_type">
                            <?php foreach (array('phone' => 'Số điện thoại', 'zalo' => 'Zalo', 'facebook' => 'Facebook', 'app' => 'Nhắn tin trên web') as $k => $t): ?>
                                <option value="<?= $k ?>" <?= ($p['contact_type'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="contact_value">Thông tin liên hệ</label>
                        <input type="text" id="contact_value" name="contact_value" value="<?= $v('contact_value') ?>" placeholder="VD: 0912xxxxxx">
                    </div>
                </div>
                <p class="field-note">Thông tin này luôn được che, chỉ hiện với người đã dùng pass để mở khoá.</p>
            </section>

            <!-- Nhóm 4: hình ảnh -->
            <section class="form-card">
                <h2 class="form-card-title"><span>4</span> Hình ảnh</h2>

                <div class="field">
                    <label for="cover">Ảnh đại diện tin</label>
                    <div class="upload-field">
                        <input type="file" id="cover" name="cover" accept="image/*">
                        <p class="upload-hint">Ảnh này hiển thị ngoài trang chủ, nên chọn ảnh dọc rõ nét.</p>
                    </div>
                </div>

                <div class="field">
                    <label for="images">Ảnh khác (chọn nhiều)</label>
                    <div class="upload-field">
                        <input type="file" id="images" name="images[]" accept="image/*" multiple>
                        <p class="upload-hint">JPG, PNG, WEBP — tối đa 5MB mỗi ảnh.</p>
                    </div>
                </div>

                <?php if ($images): ?>
                    <div class="field">
                        <label>Ảnh đã tải lên</label>
                        <div class="photo-grid">
                            <?php foreach ($images as $img): ?>
                                <figure><img src="<?= base_url(ltrim($img['path'], '/')) ?>" alt="" loading="lazy"></figure>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Lưu tin</button>
                <a class="btn btn-ghost" href="<?= site_url('tai-khoan/tin-dang') ?>">Huỷ</a>
                <span class="form-actions-note">Tin sẽ hiển thị sau khi ban quản trị duyệt.</span>
            </div>
        </form>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Quy định đăng tin</h3>
            <p>Không đăng ảnh phản cảm, thông tin sai sự thật hoặc mời chào dịch vụ. Tin vi phạm sẽ bị gỡ
                và tài khoản có thể bị khoá.</p>
        </div>
        <div class="sidebar-box">
            <h3>Mẹo được duyệt nhanh</h3>
            <ul class="sidebar-list">
                <li>Tiêu đề rõ giới tính, tuổi, khu vực</li>
                <li>Dùng ảnh thật của bạn</li>
                <li>Nội dung từ 100 chữ trở lên</li>
                <li>Không ghi số điện thoại trong nội dung</li>
            </ul>
        </div>
    </aside>
</div>
