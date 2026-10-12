<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Trang Chuỗi hoạt động — dựng theo `src/routes/tai-khoan.chuoi.tsx`.
 *
 * @var array $s @var array $badges @var array|null $ke_tiep @var int $boost @var array $tat_ca_moc
 */
$da_dat  = array_column($badges, 'ma');
$chuoi   = (int) $s['current_streak'];
$ky_luc  = (int) $s['longest_streak'];
$giu     = (int) $s['streak_freeze_count'];

/* 7 ngày gần nhất: ngày nằm trong chuỗi hiện tại (tính lùi từ ngày hoạt động
   cuối) thì sáng lên. Chỉ suy ra từ current_streak + last_active_date. */
$thu   = array('CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7');
$cuoi  = !empty($s['last_active_date']) ? strtotime($s['last_active_date']) : null;
$dau   = $cuoi && $chuoi > 0 ? strtotime('-' . ($chuoi - 1) . ' day', $cuoi) : null;
$tuan  = array();
for ($i = 6; $i >= 0; $i--) {
    $ngay = strtotime(date('Y-m-d') . ' -' . $i . ' day');
    $tuan[] = array(
        'thu' => $i === 0 ? 'Nay' : $thu[(int) date('w', $ngay)],
        'on'  => $dau && $ngay >= $dau && $ngay <= $cuoi,
        'ngay' => date('d/m', $ngay),
    );
}

// Tiến độ tới mốc kế tiếp, tính từ mốc vừa qua
$moc_truoc = 0;
foreach ($tat_ca_moc as $ngay => $m) {
    if ($ngay <= $chuoi) $moc_truoc = $ngay;
}
$tien_do = $ke_tiep ? (int) round(($chuoi - $moc_truoc) / max(1, $ke_tiep['ngay'] - $moc_truoc) * 100) : 100;
?>
<div class="tk-ph">
    <div class="tk-ph__b">
        <h1>Chuỗi hoạt động</h1>
        <p>Đăng nhập mỗi ngày để giữ chuỗi và nhận thưởng.</p>
    </div>
</div>

<!-- Ba ô số -->
<div class="tk-sk-stats">
    <div class="tk-sk-stat tk-sk-stat--brand">
        <?= tk_icon('flame') ?>
        <b><?= $chuoi ?></b>
        <span>ngày liên tiếp</span>
        <?php if ($boost > 0): ?>
            <span class="tk-pill tk-pill--onbrand tk-sk-boost"><?= tk_icon('zap') ?>Ưu tiên hiển thị +<?= (int) $boost ?>%</span>
        <?php endif; ?>
    </div>
    <div class="tk-sk-stat">
        <span class="tk-sk-stat__ic tk-sk-gold"><?= tk_icon('trophy') ?></span>
        <b><?= $ky_luc ?></b>
        <span>kỷ lục của bạn</span>
    </div>
    <div class="tk-sk-stat">
        <span class="tk-sk-stat__ic tk-sk-pri"><?= tk_icon('snowflake') ?></span>
        <b><?= $giu ?></b>
        <span>lượt giữ chuỗi còn lại</span>
    </div>
</div>

<!-- 7 ngày gần nhất + tiến độ -->
<section class="tk-card">
    <div class="tk-card__h">
        <div><h2 class="tk-card__t">7 ngày gần nhất</h2></div>
    </div>
    <div class="tk-sk-week">
        <?php foreach ($tuan as $d): ?>
            <div title="<?= $d['ngay'] ?>">
                <span class="tk-sk-day<?= $d['on'] ? ' is-on' : '' ?>"><?= $d['on'] ? tk_icon('flame') : '—' ?></span>
                <small><?= $d['thu'] ?></small>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="tk-sk-next">
        <div class="tk-sk-next__row">
            <?php if ($ke_tiep): ?>
                <b>Còn <?= (int) $ke_tiep['con'] ?> ngày đến huy hiệu <?= e($ke_tiep['ten']) ?><?= $ke_tiep['xu'] > 0 ? ' (+' . (int) $ke_tiep['xu'] . ' xu)' : '' ?></b>
                <span class="tk-muted"><?= $chuoi ?>/<?= (int) $ke_tiep['ngay'] ?></span>
            <?php else: ?>
                <b>Bạn đã đạt toàn bộ huy hiệu. Quá bền bỉ!</b>
            <?php endif; ?>
        </div>
        <div class="tk-progress"><i style="width:<?= $tien_do ?>%"></i></div>
    </div>
</section>

<!-- Huy hiệu -->
<section class="tk-card">
    <div class="tk-card__h">
        <div>
            <h2 class="tk-card__t">Huy hiệu</h2>
            <p class="tk-card__d">Huy hiệu đã đạt được giữ vĩnh viễn</p>
        </div>
    </div>
    <div class="tk-sk-badges">
        <?php foreach ($tat_ca_moc as $ngay => $m): ?>
            <?php
            $co   = in_array($m[0], $da_dat, true);
            $toi  = !$co && $ke_tiep && (int) $ke_tiep['ngay'] === (int) $ngay;
            $lop  = $co ? ' is-on' : ($toi ? ' is-next' : '');
            ?>
            <div class="tk-sk-badge<?= $lop ?>" title="<?= (int) $ngay ?> ngày liên tiếp">
                <span class="tk-sk-badge__ic"><?= tk_icon($co ? 'check' : ($toi ? 'flame' : 'lock')) ?></span>
                <div class="tk-sk-badge__b">
                    <p class="tk-sk-badge__n"><?= e($m[1]) ?></p>
                    <p class="tk-sk-badge__d"><?= (int) $ngay ?> ngày<?= $m[2] > 0 ? ' • +' . (int) $m[2] . ' xu' : '' ?><?= $m[3] > 0 ? ' • hiển thị +' . (int) $m[3] . '%' : '' ?></p>
                    <div class="tk-sk-badge__p">
                        <?php if ($co): ?>
                            <span class="tk-pill tk-pill--success">Đã đạt</span>
                        <?php elseif ($toi): ?>
                            <span class="tk-pill tk-pill--gold">Đang tiến tới</span>
                        <?php else: ?>
                            <span class="tk-pill">Chưa mở khoá</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Lượt giữ chuỗi -->
<section class="tk-card">
    <div class="tk-card__h">
        <div>
            <h2 class="tk-card__t">Lượt giữ chuỗi</h2>
            <p class="tk-card__d">Dùng khi bạn bỏ lỡ một ngày đăng nhập · bạn đang có <?= $giu ?> lượt</p>
        </div>
    </div>
    <form method="post" class="tk-row tk-sk-acts">
        <button type="submit" class="tk-btn tk-btn--brand" <?= $giu < 1 ? 'disabled' : '' ?>><?= tk_icon('snowflake') ?>Dùng 1 lượt giữ chuỗi</button>
        <button type="submit" name="mua" value="1" class="tk-btn tk-btn--outline"
                data-confirm="Dùng 30 xu để mua 1 lượt giữ chuỗi?<?= isset($user['coin_balance']) ? ' Số dư hiện tại: ' . number_format((int) $user['coin_balance']) . ' xu.' : '' ?>"><?= tk_icon('coins') ?>Mua thêm lượt — 30 xu</button>
    </form>
    <ul class="tk-sk-rules">
        <li>• Mỗi ngày bạn ghé vào là chuỗi cộng thêm một. Vào nhiều lần trong ngày vẫn chỉ tính một ngày.</li>
        <li>• Bỏ lỡ một ngày là chuỗi về 0. Lượt giữ chuỗi cứu được đúng một ngày bị lỡ.</li>
        <li>• Chuỗi càng dài, hồ sơ càng được ưu tiên hiển thị và bạn nhận thêm xu ở mỗi mốc.</li>
        <li>• Huy hiệu đã đạt được giữ vĩnh viễn, kể cả khi chuỗi bị đứt.</li>
    </ul>
</section>
