<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $m */
$age = age_from($m['birthday']);
$marital = array(
    'doc_than' => 'Độc thân', 'ly_hon' => 'Ly dị', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp',
);
?>
<article class="member-card">
    <a class="member-thumb" href="<?= site_url('thanh-vien/' . $m['slug']) ?>">
        <img src="<?= avatar_url($m['avatar'], $m['gender']) ?>" alt="<?= e(display_name($m)) ?>" loading="lazy">
        <?php if (($m['kyc_status'] ?? '') === 'verified'): ?>
            <span class="badge-verified">Kiểm định</span>
        <?php endif; ?>
        <?php if (!empty($m['is_vip'])): ?><span class="badge-vip">VIP</span><?php endif; ?>
        <?php if (is_online($m['last_active_at'])): ?><span class="dot-online" title="Đang online"></span><?php endif; ?>
        <time class="post-date"><?= date('d/m/Y', strtotime($m['created_at'])) ?></time>
    </a>

    <div class="member-body">
        <h3 class="member-name">
            <?php if (!empty($m['province_name'])): ?>
                <span class="place-tag"><?= e($m['province_name']) ?></span>
            <?php endif; ?>
            <a href="<?= site_url('thanh-vien/' . $m['slug']) ?>"><?= e(display_name($m)) ?></a>
        </h3>

        <p class="member-meta">
            <span class="heart">♥</span>
            <?php if ($age): ?><?= $age ?> tuổi <?php endif; ?>
            <?php if (!empty($m['height_cm'])): ?>Cao <?= (int) $m['height_cm'] ?> cm <?php endif; ?>
            <?php if (!empty($m['weight_kg'])): ?>Nặng <?= (int) $m['weight_kg'] ?> kg <?php endif; ?>
            <?php if (!empty($m['marital_status'])): ?><?= e($marital[$m['marital_status']] ?? '') ?><?php endif; ?>
        </p>

        <?php if (!empty($m['job'])): ?>
            <p class="member-job"><?= e($m['job']) ?></p>
        <?php endif; ?>
    </div>
</article>
