<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Bước "Bắt đầu" sau khi đăng ký — ba việc nhỏ, việc nào cũng tuỳ chọn.
 * Làm xong ảnh + khu vực là hồ sơ hiện với mọi người (xem M_user::thieu_thong_tin).
 */
$co_anh = !empty($me['avatar']);
$co_tinh = !empty($me['province_id']);
$co_bio = trim((string) ($me['bio'] ?? '')) !== '';
$xong   = (int) $co_anh + (int) $co_tinh + (int) $co_bio;

$buoc = function ($so, $da_xong) {
    return $da_xong
        ? '<span class="tk-ob-step__n is-done">' . tk_icon('check') . '</span>'
        : '<span class="tk-ob-step__n">' . $so . '</span>';
};
?>
<header class="tk-ph">
    <div class="tk-ph__b">
        <h1>Chào mừng, <?= e(display_name($me)) ?>!</h1>
        <p>Ba bước nhỏ để mọi người thấy bạn. Làm bước nào cũng được, phần còn lại để sau.</p>
    </div>
    <span class="tk-pill tk-pill--brand tk-ph__act"><?= $xong ?>/3 đã xong</span>
</header>

<?= validation_errors('<div class="tk-alert tk-alert--danger">', '</div>') ?>

<form method="post" enctype="multipart/form-data" class="tk-stack">
    <!-- 1. Ảnh -->
    <section class="tk-card tk-ob-step">
        <?= $buoc(1, $co_anh) ?>
        <div class="tk-ob-step__b">
            <h2 class="tk-card__t">Thêm ảnh đại diện</h2>
            <p class="tk-card__d">Hồ sơ có ảnh nhận nhiều lượt thả tim hơn hẳn — và cần có ảnh thì hồ sơ mới hiện với mọi người.</p>
            <div class="tk-ob-av">
                <img id="tk-ob-av" src="<?= e(avatar_url($me['avatar'] ?? null, $me['gender'] ?? 'other')) ?>" alt="" width="80" height="80">
                <label class="tk-btn tk-btn--<?= $co_anh ? 'outline' : 'brand' ?> tk-ob-file" for="avatar">
                    <?= tk_icon('camera') ?><?= $co_anh ? 'Đổi ảnh khác' : 'Chọn ảnh' ?>
                    <input type="file" id="avatar" name="avatar" accept="image/*">
                </label>
            </div>
            <p class="tk-hint">JPG, PNG hoặc WEBP. Ảnh rõ mặt, chụp một mình là đẹp nhất.</p>
        </div>
    </section>

    <!-- 2. Khu vực -->
    <section class="tk-card tk-ob-step">
        <?= $buoc(2, $co_tinh) ?>
        <div class="tk-ob-step__b">
            <h2 class="tk-card__t"><label for="province_id">Chọn khu vực</label></h2>
            <p class="tk-card__d">Để gợi ý người ở gần bạn.</p>
            <div class="tk-field" style="margin-top:12px">
                <?php /* Gõ để tìm — cuộn 34 tỉnh thành trên điện thoại rất mỏi */ ?>
                <select id="province_id" name="province_id" data-searchable data-search-placeholder="Gõ tên tỉnh/thành...">
                    <option value="">-- Chọn tỉnh/thành --</option>
                    <?php foreach ($provinces as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= (int) ($me['province_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <!-- 3. Giới thiệu -->
    <section class="tk-card tk-ob-step">
        <?= $buoc(3, $co_bio) ?>
        <div class="tk-ob-step__b">
            <h2 class="tk-card__t"><label for="bio">Viết một câu giới thiệu</label></h2>
            <p class="tk-card__d">Một câu thôi cũng được — người ta hay nhắn tin cho hồ sơ có lời giới thiệu.</p>
            <div class="tk-field" style="margin-top:12px">
                <textarea id="bio" name="bio" rows="3" maxlength="500"
                          placeholder="VD: Mình thích cà phê sáng, chạy bộ và những cuộc nói chuyện thật lòng."><?= e($me['bio'] ?? '') ?></textarea>
            </div>
        </div>
    </section>

    <div class="tk-ob-acts">
        <button type="submit" class="tk-btn tk-btn--brand tk-btn--lg"><?= tk_icon('check') ?>Lưu và tiếp tục</button>
        <a class="tk-btn tk-btn--ghost tk-btn--lg" href="<?= site_url('tai-khoan') ?>">Bỏ qua, để sau</a>
    </div>
</form>

<script>
/* Xem trước ảnh vừa chọn */
(function () {
    var input = document.getElementById('avatar'), img = document.getElementById('tk-ob-av');
    if (!input || !img || !window.URL) { return; }
    input.addEventListener('change', function () {
        if (input.files && input.files[0]) { img.src = URL.createObjectURL(input.files[0]); }
    });
})();
</script>
