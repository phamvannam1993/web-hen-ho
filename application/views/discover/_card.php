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
        <?php /* Luôn giữ khối này kể cả khi trống, để các thẻ cùng hàng cao bằng nhau */ ?>
        <p class="swipe-bio"><?= !empty($c['bio']) ? e(excerpt($c['bio'], 130)) : '' ?></p>

        <div class="swipe-actions">
            <?php
            // Dùng SVG thay ký tự ♥ / ✕: ký tự Unicode bị hệ điều hành thay bằng
            // font emoji nên kích thước không kiểm soát được, dễ tràn khỏi nút tròn.
            $icon_heart = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.5-9A5.2 5.2 0 0 1 12 6.1 5.2 5.2 0 0 1 21.5 12c-2 4.4-9.5 9-9.5 9z"/></svg>';
            $icon_close = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';
            ?>
            <?php if ($user): ?>
                <button type="button" class="btn-pass-round" data-action="pass" title="Bỏ qua" aria-label="Bỏ qua"><?= $icon_close ?></button>
                <a class="btn-view-round" href="<?= site_url('profile/' . $c['slug']) ?>" title="Xem hồ sơ">Hồ sơ</a>
                <button type="button" class="btn-like-round" data-action="like" title="Thích" aria-label="Thích"><?= $icon_heart ?></button>
            <?php else: ?>
                <a class="btn-view-round" href="<?= site_url('profile/' . $c['slug']) ?>">Xem hồ sơ</a>
                <a class="btn-like-round" href="<?= site_url('dang-nhap') ?>" title="Đăng nhập để thích" aria-label="Thích"><?= $icon_heart ?></a>
            <?php endif; ?>
        </div>
    </div>
</article>
