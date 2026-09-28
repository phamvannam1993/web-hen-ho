<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $s @var array $badges @var array|null $ke_tiep @var int $boost @var array $tat_ca_moc */
$da_dat = array_column($badges, 'ma');
?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <div class="content-box">
            <h2 class="section-title">Chuỗi hoạt động</h2>

            <div class="streak-hero">
                <div class="streak-now">
                    <span class="streak-fire" aria-hidden="true">🔥</span>
                    <b><?= (int) $s['current_streak'] ?></b>
                    <small>ngày liên tiếp</small>
                </div>
                <div class="streak-meta">
                    <p>Kỷ lục của bạn: <b><?= (int) $s['longest_streak'] ?> ngày</b></p>
                    <?php if ($boost > 0): ?>
                        <p>Hồ sơ đang được ưu tiên hiển thị <b>+<?= (int) $boost ?>%</b></p>
                    <?php endif; ?>
                    <?php if ($ke_tiep): ?>
                        <p>Còn <b><?= (int) $ke_tiep['con'] ?> ngày</b> nữa là đạt huy hiệu
                            <b><?= e($ke_tiep['ten']) ?></b><?= $ke_tiep['xu'] > 0 ? ' (+' . (int) $ke_tiep['xu'] . ' xu)' : '' ?>.</p>
                    <?php else: ?>
                        <p>Bạn đã đạt toàn bộ huy hiệu. Quá bền bỉ!</p>
                    <?php endif; ?>
                </div>
            </div>

            <h3 class="streak-sub">Huy hiệu</h3>
            <div class="badge-grid">
                <?php foreach ($tat_ca_moc as $ngay => $m): $co = in_array($m[0], $da_dat, true); ?>
                    <div class="badge <?= $co ? 'is-on' : '' ?>" title="<?= (int) $ngay ?> ngày liên tiếp">
                        <span class="badge-ic"><?= $co ? '🏅' : '🔒' ?></span>
                        <b><?= e($m[1]) ?></b>
                        <small><?= (int) $ngay ?> ngày<?= $m[2] > 0 ? ' · ' . (int) $m[2] . ' xu' : '' ?></small>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 class="streak-sub">Giữ chuỗi</h3>
            <p class="section-hint">
                Bỏ lỡ một ngày là chuỗi về 0. Lượt giữ chuỗi cứu được đúng một ngày bị lỡ.
                Bạn đang có <b><?= (int) $s['streak_freeze_count'] ?> lượt</b>.
            </p>
            <form method="post" class="streak-form">
                <button type="submit" class="btn-hm btn-hm-line"
                        <?= (int) $s['streak_freeze_count'] < 1 ? 'disabled' : '' ?>>Dùng 1 lượt giữ chuỗi</button>
                <button type="submit" name="mua" value="1" class="btn-hm btn-hm-solid">Mua thêm lượt (30 xu)</button>
            </form>
        </div>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Chuỗi hoạt động là gì?</h3>
            <p>Mỗi ngày bạn ghé vào là chuỗi cộng thêm một. Vào nhiều lần trong ngày
                vẫn chỉ tính một ngày.</p>
            <p>Chuỗi càng dài, hồ sơ của bạn càng được ưu tiên hiển thị với người khác,
                và bạn nhận thêm xu ở mỗi mốc.</p>
            <p>Huy hiệu đã đạt thì giữ mãi, kể cả khi chuỗi bị đứt.</p>
        </div>
    </aside>
</div>
