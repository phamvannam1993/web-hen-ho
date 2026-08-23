<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $c một hồ sơ ứng viên kèm điểm tương hợp */
$marital = array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp');
$score = min(100, (int) ($c['match_score'] ?? 0));
?>
<article class="swipe-card" data-user="<?= (int) $c['id'] ?>">
    <div class="swipe-photo">
        <img src="<?= avatar_url($c['avatar'], $c['gender']) ?>" alt="<?= e(display_name($c)) ?>">
        <span class="match-score" title="Mức độ tương hợp"><?= $score ?>%</span>
        <?php if (is_online($c['last_active_at'])): ?><span class="dot-online"></span><?php endif; ?>
        <?php if (!empty($c['is_vip'])): ?><span class="badge-vip">VIP</span><?php endif; ?>

    </div>

    <div class="swipe-body">
        <div class="swipe-caption">
            <h3><?= e(display_name($c)) ?><?= !empty($c['age']) ? ', ' . (int) $c['age'] : '' ?></h3>
            <p>
                <?= !empty($c['province_name']) ? e($c['province_name']) : 'Chưa rõ khu vực' ?>
                <?= !empty($c['job']) ? ' · ' . e($c['job']) : '' ?>
            </p>
        </div>
        <p class="swipe-tags">
            <?php if (!empty($c['height_cm'])): ?><span><?= (int) $c['height_cm'] ?> cm</span><?php endif; ?>
            <?php if (!empty($c['weight_kg'])): ?><span><?= (int) $c['weight_kg'] ?> kg</span><?php endif; ?>
            <?php if (!empty($c['marital_status'])): ?><span><?= e($marital[$c['marital_status']] ?? '') ?></span><?php endif; ?>
        </p>
        <?php if (!empty($c['bio'])): ?>
            <p class="swipe-bio"><?= e(excerpt($c['bio'], 130)) ?></p>
        <?php endif; ?>

        <div class="swipe-actions">
            <?php if ($user): ?>
                <button type="button" class="btn-pass-round" data-action="pass" title="Bỏ qua">✕</button>
                <a class="btn-view-round" href="<?= site_url('thanh-vien/' . $c['slug']) ?>" title="Xem hồ sơ">Hồ sơ</a>
                <button type="button" class="btn-like-round" data-action="like" title="Thích">♥</button>
            <?php else: ?>
                <a class="btn-view-round" href="<?= site_url('thanh-vien/' . $c['slug']) ?>">Xem hồ sơ</a>
                <a class="btn-like-round" href="<?= site_url('dang-nhap') ?>" title="Đăng nhập để thích">♥</a>
            <?php endif; ?>
        </div>
    </div>
</article>
