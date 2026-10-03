<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Đăng ký: chỉ những gì cần để tài khoản tồn tại. Khu vực, số điện thoại, ảnh…
 * hỏi sau ở bước "Bắt đầu" (tai-khoan/bat-dau) và trang Hồ sơ.
 * Không có ô "xác nhận mật khẩu" — ô mật khẩu có sẵn nút 👁 để xem lại.
 *
 * @var string $max_dob  ngày sinh muộn nhất còn đủ 18 tuổi (Y-m-d)
 */
$gioi_tinh = array('female' => 'Nữ', 'male' => 'Nam', 'other' => 'Khác');
?>
<div class="container">
    <div class="auth-card auth-card--narrow">
        <h1 class="auth-title">Tạo tài khoản</h1>
        <p class="auth-sub">Chưa đến một phút. Ảnh và khu vực bạn thêm sau cũng được.</p>

        <?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

        <form method="post" class="auth-form">
            <label for="display_name">Tên hiển thị <b class="req">*</b></label>
            <input type="text" id="display_name" name="display_name" value="<?= set_value('display_name') ?>"
                   autocomplete="nickname" maxlength="100" placeholder="Tên mọi người sẽ thấy" required>

            <label for="email">Email <b class="req">*</b></label>
            <input type="email" id="email" name="email" value="<?= set_value('email') ?>"
                   inputmode="email" autocomplete="email" autocapitalize="off" spellcheck="false" required>

            <label for="password">Mật khẩu <b class="req">*</b></label>
            <input type="password" id="password" name="password" minlength="6"
                   autocomplete="new-password" placeholder="Ít nhất 6 ký tự" required>

            <fieldset class="gender-pick">
                <legend>Giới tính <b class="req">*</b></legend>
                <?php foreach ($gioi_tinh as $v => $nhan): ?>
                    <label>
                        <input type="radio" name="gender" value="<?= $v ?>" <?= set_radio('gender', $v) ?> required>
                        <span><?= $nhan ?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <label for="birthday">Ngày sinh <b class="req">*</b></label>
            <?php /* Ô ngày của trình duyệt: điện thoại bật sẵn bộ chọn ngày.
                     max chặn luôn ngày sinh chưa đủ 18 tuổi; máy chủ vẫn kiểm tra lại. */ ?>
            <input type="date" id="birthday" name="birthday" value="<?= set_value('birthday') ?>"
                   max="<?= e($max_dob) ?>" min="1940-01-01" autocomplete="bday" required>
            <p class="auth-hint">Bạn cần đủ 18 tuổi. Ngày sinh dùng để tính tuổi khi ghép đôi, không hiện công khai.</p>

            <label class="checkbox">
                <input type="checkbox" name="agree" value="1" <?= set_checkbox('agree', '1') ?> required>
                Tôi đồng ý với <a href="<?= site_url('noi-quy') ?>" target="_blank">nội quy</a> của website
            </label>

            <div class="auth-actions">
                <button type="submit" class="btn btn-primary">Đăng ký</button>
                <a class="btn btn-ghost" href="<?= site_url('dang-nhap') ?>">Đã có tài khoản</a>
            </div>
        </form>
    </div>
</div>
