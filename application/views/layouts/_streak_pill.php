<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ô "chuỗi ngày hoạt động" 🔥.
 *
 * Tách ra partial vì nó xuất hiện ở HAI chỗ: thanh đầu trang (máy tính) và
 * trong ngăn kéo menu (điện thoại) — xem chú thích tại `main.php`. Một bản mã,
 * hai chỗ dùng, khỏi sửa hai nơi khi đổi nội dung.
 *
 * $tk_lop : lớp phụ để CSS ẩn/hiện theo khổ màn.
 * Chỉ hiện khi đã đăng nhập VÀ đã có chuỗi — con số 0 chỉ làm rối.
 */
if (empty($user) || empty($streak['streak'])) {
    return;
}
$so = (int) $streak['streak'];
?>
<a class="hd-streak <?= isset($tk_lop) ? $tk_lop : '' ?>" href="<?= site_url('tai-khoan/chuoi') ?>"
   title="Bạn đã hoạt động <?= $so ?> ngày liên tiếp">
    <span aria-hidden="true">🔥</span><b><?= $so ?></b>
    <?php if (!empty($tk_nhan)): ?><span class="hd-streak__nhan">ngày liên tiếp</span><?php endif; ?>
</a>
