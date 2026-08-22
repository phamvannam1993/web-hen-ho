<?php defined('BASEPATH') OR exit('No direct script access allowed');
$v = function ($key, $default = '') use ($p) { return e($p[$key] ?? $default); };
?>
<form method="post" enctype="multipart/form-data">
    <div class="panel">
        <div class="panel-head"><h2>Nội dung tin</h2></div>
        <div class="panel-body form-grid">
            <div class="full">
                <label>Tiêu đề</label>
                <input type="text" name="title" value="<?= $v('title') ?>" required>
            </div>
            <div>
                <label>Tên hiển thị trên tin</label>
                <input type="text" name="nickname" value="<?= $v('nickname') ?>" placeholder="VD: Kim Ngọc">
            </div>
            <div>
                <label>Giới thiệu ngắn</label>
                <input type="text" name="intro" value="<?= $v('intro') ?>" placeholder="VD: Vui vẻ hoạt bát">
            </div>
            <div class="full">
                <label>Nội dung</label>
                <textarea class="rich-editor" name="content" required><?= $v('content') ?></textarea>
            </div>
            <div>
                <label>Danh mục</label>
                <select name="category_id">
                    <option value="">-- Chọn --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= ($p['category_id'] ?? null) == $c['id'] ? 'selected' : '' ?>>
                            <?= $c['parent_id'] ? '— ' : '' ?><?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Tỉnh/thành</label>
                <select name="province_id">
                    <option value="">-- Chọn --</option>
                    <?php foreach ($provinces as $pr): ?>
                        <option value="<?= (int) $pr['id'] ?>" <?= ($p['province_id'] ?? null) == $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Quận/huyện</label>
                <input type="text" name="district" value="<?= $v('district') ?>">
            </div>
            <div>
                <label>Ảnh đại diện tin</label>
                <input type="file" name="cover" accept="image/*">
                <?php if (!empty($p['cover'])): ?>
                    <img class="thumb" src="<?= base_url(ltrim($p['cover'], '/')) ?>" alt="">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>Thông tin cá nhân hiển thị</h2></div>
        <div class="panel-body form-grid">
            <div>
                <label>Giới tính</label>
                <select name="gender">
                    <?php foreach (array('female' => 'Nữ', 'male' => 'Nam', 'other' => 'Khác') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= ($p['gender'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Muốn tìm</label>
                <select name="seeking">
                    <?php foreach (array('all' => 'Tất cả', 'male' => 'Nam', 'female' => 'Nữ') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= ($p['seeking'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Tuổi</label><input type="number" name="age" value="<?= $v('age') ?>" min="18" max="99"></div>
            <div>
                <label>Tình trạng hôn nhân</label>
                <select name="marital_status">
                    <option value="">-- Chọn --</option>
                    <?php foreach (array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly dị', 'goa' => 'Goá',
                                         'dang_co_nguoi_yeu' => 'Đang có người yêu', 'phuc_tap' => 'Phức tạp') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= ($p['marital_status'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Chiều cao (cm)</label><input type="number" name="height_cm" value="<?= $v('height_cm') ?>"></div>
            <div><label>Cân nặng (kg)</label><input type="number" name="weight_kg" value="<?= $v('weight_kg') ?>"></div>
            <div><label>Nghề nghiệp</label><input type="text" name="job" value="<?= $v('job') ?>"></div>
            <div><label>Tính cách</label><input type="text" name="personality" value="<?= $v('personality') ?>"></div>
            <div class="full"><label>Mong muốn tìm người yêu</label><input type="text" name="wish" value="<?= $v('wish') ?>"></div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>Liên hệ &amp; kiểm duyệt</h2></div>
        <div class="panel-body form-grid">
            <div>
                <label>Kênh liên hệ</label>
                <select name="contact_type">
                    <?php foreach (array('phone' => 'Số điện thoại', 'zalo' => 'Zalo', 'facebook' => 'Facebook', 'app' => 'Nhắn tin trên web') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= ($p['contact_type'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Thông tin liên hệ</label><input type="text" name="contact_value" value="<?= $v('contact_value') ?>"></div>
            <div>
                <label>Xu để mở liên hệ</label>
                <input type="number" name="contact_cost" value="<?= $v('contact_cost', '0') ?>">
                <small>0 = dùng mức mặc định trong Cấu hình</small>
            </div>
            <div>
                <label>Trạng thái</label>
                <select name="status">
                    <?php foreach (array('pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối',
                                         'hidden' => 'Ẩn', 'expired' => 'Hết hạn', 'draft' => 'Nháp') as $k => $t): ?>
                        <option value="<?= $k ?>" <?= ($p['status'] ?? 'pending') === $k ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label><input type="checkbox" name="is_verified" value="1" <?= !empty($p['is_verified']) ? 'checked' : '' ?>> Gắn nhãn "Kiểm định"</label>
                <label><input type="checkbox" name="is_featured" value="1" <?= !empty($p['is_featured']) ? 'checked' : '' ?>> Tin nổi bật</label>
            </div>
            <div class="full">
                <label>Thêm ảnh vào thư viện</label>
                <input type="file" name="images[]" accept="image/*" multiple>
            </div>
        </div>
    </div>

    <?php if ($images): ?>
        <div class="panel">
            <div class="panel-head"><h2>Thư viện ảnh</h2></div>
            <div class="panel-body actions">
                <?php foreach ($images as $img): ?>
                    <div>
                        <img class="thumb" src="<?= base_url(ltrim($img['path'], '/')) ?>" alt="">
                        <a class="btn btn-danger btn-sm" href="<?= site_url('admin/posts/delete_image/' . $p['id'] . '/' . $img['id']) ?>">Xoá</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="actions">
        <button class="btn btn-primary" type="submit">Lưu tin</button>
        <a class="btn btn-light" href="<?= site_url('admin/posts') ?>">Quay lại</a>
    </div>
</form>
