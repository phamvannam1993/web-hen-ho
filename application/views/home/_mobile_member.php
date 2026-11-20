<?php defined('BASEPATH') OR exit('No direct script access allowed');
$home_access = profile_access_card($m, $user['id'] ?? 0);
$home_age = age_from($m['birthday'] ?? null);
$home_online = is_online($m['last_active_at'] ?? null);
$home_tags = $home_access['detail'] && !empty($m['interest_names']) ? array_slice(explode('|', $m['interest_names']), 0, 2) : array();
$home_facts = array_filter(array(
    $home_access['detail'] ? ($m['job'] ?? '') : '',
    $m['province_name'] ?? '',
));
?>
<article class="home-mobile-person<?= $home_access['charge'] ? ' is-profile-paid' : '' ?>" data-user="<?= (int) $m['id'] ?>" data-profile-preview-card="<?= e($m['slug']) ?>" tabindex="0" aria-label="Xem hồ sơ <?= e(display_name($m)) ?>">
    <a class="home-mobile-person-photo" href="<?= site_url('profile/' . $m['slug']) ?>">
        <img src="<?= e(avatar_url($m['avatar'], $m['gender'])) ?>" data-home-avatar-fallback="<?= e(avatar_url(null, $m['gender'])) ?>" alt="<?= e(display_name($m)) ?>" width="112" height="128" loading="lazy">
        <span><?= e($home_label) ?></span>
    </a>
    <div class="home-mobile-person-body">
        <div>
            <div class="home-mobile-person-title">
                <h3><a href="<?= site_url('profile/' . $m['slug']) ?>"><?= e(display_name($m)) ?></a></h3>
                <?php if ($home_age): ?><span class="home-mobile-age"><?= $m['gender'] === 'female' ? '♀' : ($m['gender'] === 'male' ? '♂' : '') ?> <?= (int) $home_age ?></span><?php endif; ?>
                <span class="home-mobile-status<?= $home_online ? ' is-online' : '' ?>" role="img" aria-label="<?= $home_online ? 'Đang online' : 'Ngoại tuyến' ?>" title="<?= $home_online ? 'Đang online' : 'Ngoại tuyến' ?>"></span>
            </div>
            <?php if ($home_tags): ?><div class="home-mobile-tags"><?php foreach ($home_tags as $tag): ?><span><?= e($tag) ?></span><?php endforeach; ?></div><?php endif; ?>
            <?php if ($home_facts): ?><p class="home-mobile-facts"><?= e(implode(' · ', $home_facts)) ?></p><?php endif; ?>
        </div>
        <div class="home-mobile-person-bottom">
            <?php if (isset($m['match_score'])): ?>
            <span class="home-mobile-compatibility"><?= tk_icon('heart') ?> <?= max(60, min(99, (int) round($m['match_score'] * 100 / 120))) ?>% <small>hợp</small></span>
            <?php elseif (!empty($m['kyc_status']) && $m['kyc_status'] === 'verified'): ?><span class="home-mobile-verified">✓ Đã xác thực</span><?php endif; ?>
        </div>
    </div>
</article>
