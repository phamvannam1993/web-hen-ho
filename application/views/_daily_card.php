<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Thẻ "Người phù hợp với bạn hôm nay".
 * Dùng chung ở trang chủ và trang Khám phá. @var array|null $daily
 */
// Nơi gọi đã tự kiểm tra người dùng đã đăng nhập chưa.
// Không dùng isset() ở đây: isset(null) trả về false, mà $daily bằng null
// chính là trường hợp cần hiện thông báo "chưa có ai phù hợp".

// Không tìm được ai hợp: nói rõ thay vì để trống trơn,
// người dùng mới biết là hệ thống có chạy chứ không phải hỏng.
if (empty($daily)) { ?>
    <section class="dm-wrap">
        <div class="container">
            <div class="dm-empty">
                <b>Hôm nay chưa có ai phù hợp</b>
                Bạn đã xem hết những người hợp tiêu chí. Thử nới rộng tiêu chí trong
                <a href="<?= site_url('tai-khoan/ho-so') ?>">hồ sơ</a> hoặc quay lại vào 8h sáng mai nhé.
            </div>
        </div>
    </section>
<?php
    return;
}
$tuoi  = age_from($daily['birthday']);
$tags  = !empty($daily['interest_names']) ? array_slice(explode('|', $daily['interest_names']), 0, 5) : array();
$chips = array_filter(array(
    $daily['province_name'] ?? null,
    $daily['job'] ?? null,
    !empty($daily['height_cm']) ? (int) $daily['height_cm'] . 'cm' : null,
));
?>
<section class="dm-wrap" data-daily="<?= (int) $daily['id'] ?>"
         data-expires="<?= strtotime($daily['expires_at']) ?>">
    <div class="container">
        <div class="dm-card is-<?= e($daily['status']) ?>">
            <header class="dm-head">
                <div>
                    <h2>Người phù hợp với bạn hôm nay</h2>
                    <p class="dm-sub">Mỗi ngày một người, hết hạn lúc 8h sáng mai.</p>
                </div>
                <?php if ($daily['status'] === 'pending'): ?>
                    <span class="dm-countdown" id="dm-countdown">—</span>
                <?php endif; ?>
            </header>

            <div class="dm-body">
                <a class="dm-photo" href="<?= site_url('profile/' . $daily['slug']) ?>">
                    <img src="<?= avatar_url($daily['avatar'], $daily['gender']) ?>"
                         alt="<?= e(display_name($daily)) ?>" loading="lazy">
                </a>

                <div class="dm-info">
                    <h3>
                        <a href="<?= site_url('profile/' . $daily['slug']) ?>">
                            <?= e(display_name($daily)) ?><?= $tuoi ? ', ' . $tuoi : '' ?>
                        </a>
                        <span class="hm-pill hm-pill-pink">Độ phù hợp <?= min(99, 60 + (int) $daily['score'] * 2) ?>%</span>
                    </h3>

                    <?php if ($chips): ?>
                        <p class="dm-chips">
                            <?php foreach ($chips as $c): ?><span><?= e($c) ?></span><?php endforeach; ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($daily['bio'])): ?>
                        <p class="dm-bio"><?= e(excerpt($daily['bio'], 180)) ?></p>
                    <?php endif; ?>

                    <?php if ($tags): ?>
                        <p class="dm-tags">
                            <?php foreach ($tags as $t): ?><span><?= e($t) ?></span><?php endforeach; ?>
                        </p>
                    <?php endif; ?>

                    <div class="dm-actions" id="dm-actions">
                        <?php if ($daily['status'] === 'pending'): ?>
                            <button type="button" class="btn-hm btn-hm-line" data-daily-act="skip">Bỏ qua</button>
                            <button type="button" class="btn-hm btn-hm-solid" data-daily-act="like">Thả tim</button>
                        <?php elseif ($daily['status'] === 'liked'): ?>
                            <p class="dm-state">Đã thả tim — đang chờ người ấy trả lời.</p>
                        <?php elseif ($daily['status'] === 'matched'): ?>
                            <p class="dm-state is-ok">Đã ghép đôi!</p>
                            <a class="btn-hm btn-hm-solid" href="<?= site_url('tai-khoan/tin-nhan') ?>">Nhắn tin ngay</a>
                        <?php elseif ($daily['status'] === 'skipped'): ?>
                            <p class="dm-state">Đã bỏ qua — hẹn gặp ngày mai.</p>
                        <?php else: ?>
                            <p class="dm-state">Đã hết hạn — quay lại lúc 8h sáng mai nhé.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
