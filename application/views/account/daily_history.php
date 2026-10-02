<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Gợi ý mỗi ngày + lịch sử — dựng theo `src/routes/tai-khoan.goi-y.tsx`.
 * @var array $list @var array|null $today
 *
 * Thẻ hôm nay dùng chung móc JS với _daily_card.php: `[data-daily]` +
 * `data-expires`, `#dm-countdown`, `#dm-actions` > `[data-daily-act]`
 * (app.js gửi ajax/goi-y-hom-nay rồi thay cụm nút bằng `p.dm-state`).
 */
// array(nhãn, sắc thái pill)
$nhan = array(
    'pending' => array('Đang chờ', 'brand'),   'liked'   => array('Đã thích', 'success'),
    'matched' => array('Đã ghép đôi', 'success'), 'skipped' => array('Đã bỏ qua', ''),
    'expired' => array('Hết hạn', 'danger'),
);
$hon_nhan = array('doc_than' => 'Độc thân', 'ly_hon' => 'Đã ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp');

$cho = $today && $today['status'] === 'pending';
if ($today) {
    $tuoi = age_from($today['birthday']);
    $link = site_url('profile/' . $today['slug']);
    $nghe = implode(' • ', array_filter(array($today['job'] ?? '', $hon_nhan[$today['marital_status'] ?? ''] ?? '')));
    $tags = !empty($today['interest_names']) ? array_slice(explode('|', $today['interest_names']), 0, 5) : array();
    $con  = max(0, strtotime($today['expires_at']) - time());
}
?>
<header class="tk-ph">
    <div class="tk-ph__b">
        <h1>Gợi ý mỗi ngày</h1>
        <p>Mỗi ngày lúc 8h sáng, hệ thống chọn một hồ sơ phù hợp nhất với tiêu chí của bạn.</p>
    </div>
    <?php if ($cho): ?>
        <div class="tk-ph__act">
            <span class="tk-pill tk-pill--brand" id="dm-countdown">Còn <?= floor($con / 3600) ?>h <?= floor($con % 3600 / 60) ?>p</span>
        </div>
    <?php endif; ?>
</header>

<?php if ($today): ?>
    <section class="tk-card tk-gy-today" data-daily="<?= (int) $today['id'] ?>" data-expires="<?= strtotime($today['expires_at']) ?>">
        <a class="tk-gy-ph" href="<?= $link ?>">
            <img src="<?= e(avatar_url($today['avatar'], $today['gender'])) ?>" alt="<?= e(display_name($today)) ?>">
            <span class="tk-gy-tags">
                <span class="tk-pill tk-pill--brand"><?= tk_icon('sparkles') ?>Gợi ý hôm nay</span>
            </span>
        </a>
        <div class="tk-gy-b">
            <h2 class="tk-gy-n"><a href="<?= $link ?>"><?= e(display_name($today)) ?><?= $tuoi ? ', ' . $tuoi : '' ?></a></h2>
            <p style="margin-top:8px"><span class="tk-pill tk-pill--gold">Độ phù hợp <?= min(99, 60 + (int) $today['score'] * 2) ?>%</span></p>
            <div class="tk-gy-m">
                <p><?= tk_icon('pin') ?><span><?= e($today['province_name'] ?: 'Chưa rõ khu vực') ?></span></p>
                <p><?= tk_icon('briefcase') ?><span><?= e($nghe ?: 'Chưa cập nhật') ?><?= !empty($today['height_cm']) ? ' • ' . (int) $today['height_cm'] . 'cm' : '' ?></span></p>
            </div>
            <?php if (!empty($today['bio'])): ?>
                <p class="tk-gy-bio"><?= e(excerpt($today['bio'], 180)) ?></p>
            <?php endif; ?>
            <?php if ($tags): ?>
                <div class="tk-row tk-gy-int">
                    <?php foreach ($tags as $t): ?><span class="tk-pill tk-pill--brand"><?= e($t) ?></span><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="tk-gy-act" id="dm-actions">
                <?php if ($cho): ?>
                    <button type="button" class="tk-btn tk-btn--brand tk-btn--lg" data-daily-act="like"><?= tk_icon('heart') ?>Thích</button>
                    <button type="button" class="tk-btn tk-btn--outline tk-btn--lg" data-daily-act="skip"><?= tk_icon('x') ?>Bỏ qua</button>
                <?php elseif ($today['status'] === 'liked'): ?>
                    <p class="dm-state">Đã thả tim — đang chờ người ấy trả lời.</p>
                <?php elseif ($today['status'] === 'matched'): ?>
                    <p class="dm-state is-ok">Đã ghép đôi!</p>
                    <a class="btn-hm btn-hm-solid" href="<?= site_url('tai-khoan/tin-nhan') . '?to=' . (int) $today['match_user_id'] ?>">Nhắn tin ngay</a>
                <?php elseif ($today['status'] === 'skipped'): ?>
                    <p class="dm-state">Đã bỏ qua — hẹn gặp ngày mai.</p>
                <?php else: ?>
                    <p class="dm-state">Đã hết hạn — quay lại lúc 8h sáng mai nhé.</p>
                <?php endif; ?>
            </div>
            <p class="tk-gy-note">Hồ sơ đã bỏ qua sẽ không được gợi ý lại.</p>
        </div>
    </section>
<?php else: ?>
    <div class="tk-empty">
        <span class="tk-empty__ic"><?= tk_icon('sparkles') ?></span>
        <h3>Hôm nay chưa có gợi ý</h3>
        <p>Gợi ý mới được chọn lúc 8h sáng mỗi ngày. Khai đủ tiêu chí tìm kiếm để hệ thống dễ tìm người hợp với bạn.</p>
        <div class="tk-empty__act"><a class="tk-btn tk-btn--brand" href="<?= site_url('tai-khoan/ho-so') ?>">Cập nhật tiêu chí</a></div>
    </div>
<?php endif; ?>

<section class="tk-card">
    <div class="tk-card__h">
        <div><h2 class="tk-card__t">Lịch sử gợi ý</h2></div>
    </div>
    <?php if (empty($list)): ?>
        <div class="tk-empty">
            <span class="tk-empty__ic"><?= tk_icon('clock') ?></span>
            <h3>Chưa có gợi ý nào</h3>
            <p>Quay lại vào 8h sáng mai nhé!</p>
        </div>
    <?php else: ?>
        <ul class="tk-list tk-gy-list">
            <?php foreach ($list as $d): $tuoi = age_from($d['birthday']); $s = $nhan[$d['status']] ?? array($d['status'], ''); ?>
                <li>
                    <a href="<?= site_url('profile/' . $d['slug']) ?>">
                        <img class="tk-avatar" src="<?= e(avatar_url($d['avatar'], $d['gender'])) ?>" alt="" loading="lazy">
                    </a>
                    <div class="tk-gy-lb">
                        <a class="tk-gy-ln" href="<?= site_url('profile/' . $d['slug']) ?>"><?= e(display_name($d)) ?><?= $tuoi ? ', ' . $tuoi : '' ?></a>
                        <p class="tk-gy-lt"><?= date('d/m/Y', strtotime($d['match_date'])) ?></p>
                    </div>
                    <span class="tk-pill<?= $s[1] ? ' tk-pill--' . $s[1] : '' ?>"><?= e($s[0]) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
