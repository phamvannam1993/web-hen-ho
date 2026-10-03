<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Banner nhắc ngay dưới đầu trang — không chặn trang, bấm ✕ thì ẩn 24 giờ.
 * Mỗi lúc chỉ hiện MỘT banner, ưu tiên việc đang khoá tính năng:
 *   1. Chưa xác thực email  → thả tim / nhắn tin đang bị khoá.
 *   2. Hồ sơ chưa đủ để hiện công khai (thiếu ảnh, khu vực…).
 *
 * Biến từ MY_Controller: $user, $chua_xac_thuc, $ho_so_an, $ho_so_pt.
 */
if (empty($user)) {
    return;
}
$uri = uri_string();

if (!empty($chua_xac_thuc) && strpos($uri, 'xac-thuc') !== 0) {
    $loai = 'verify';
} elseif (!empty($ho_so_an) && $uri !== 'tai-khoan/bat-dau' && $uri !== 'tai-khoan/ho-so') {
    $loai = 'profile';
} else {
    return;
}
?>
<div class="nudge-bar nudge-bar--<?= $loai ?>" data-nudge="<?= $loai ?>" hidden>
    <div class="container nudge-bar__in">
        <?php if ($loai === 'verify'): ?>
            <span class="nudge-bar__ic" aria-hidden="true">✉️</span>
            <div class="nudge-bar__b">
                <b>Xác thực email để mở khoá thả tim và nhắn tin</b>
                <small>Bấm link trong thư gửi tới <?= e(mask_email($user['email'])) ?>. Không thấy thì kiểm tra cả thư mục Spam.</small>
            </div>
            <div class="nudge-bar__acts">
                <form method="post" action="<?= site_url('xac-thuc/gui-lai') ?>">
                    <button type="submit" class="nudge-bar__btn">Gửi lại email</button>
                </form>
                <button type="button" class="nudge-bar__x" data-nudge-close aria-label="Ẩn nhắc nhở">×</button>
            </div>
        <?php else: ?>
            <span class="nudge-bar__ic" aria-hidden="true">📸</span>
            <div class="nudge-bar__b">
                <b>Hồ sơ của bạn chưa hiển thị với mọi người</b>
                <small>Thêm <?= e(mb_strtolower(implode(', ', array_slice($ho_so_an, 0, 3)))) ?> để được gợi ý
                    nhiều hơn — hồ sơ đủ thông tin nhận nhiều lượt thả tim hơn hẳn.
                    Đã hoàn thiện <?= (int) $ho_so_pt ?>%.</small>
                <div class="nudge-bar__bar"><i style="width:<?= max(4, min(100, (int) $ho_so_pt)) ?>%"></i></div>
            </div>
            <div class="nudge-bar__acts">
                <a class="nudge-bar__btn" href="<?= site_url('tai-khoan/bat-dau') ?>">Cập nhật ngay</a>
                <button type="button" class="nudge-bar__x" data-nudge-close aria-label="Ẩn nhắc nhở">×</button>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
/* Ẩn tạm 24 giờ khi bấm ✕ (nhớ trong trình duyệt). Mặc định banner `hidden`,
   chỉ bật lên khi chưa bị ẩn — tránh nháy hiện rồi tắt. */
(function () {
    var bar = document.querySelector('[data-nudge]');
    if (!bar) { return; }
    var key = 'nudge-hide-' + bar.getAttribute('data-nudge');
    var an = 0;
    try { an = parseInt(localStorage.getItem(key), 10) || 0; } catch (e) {}
    if (Date.now() < an) { return; }
    bar.hidden = false;
    bar.querySelector('[data-nudge-close]').addEventListener('click', function () {
        bar.hidden = true;
        try { localStorage.setItem(key, String(Date.now() + 864e5)); } catch (e) {}
    });
})();
</script>
