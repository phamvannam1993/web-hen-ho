<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $m một hồ sơ; thẻ dành riêng cho trang Tâm sự */
$age    = age_from($m['birthday']);
$online = is_online($m['last_active_at']);
$me     = isset($user) ? $user : null;
$access = profile_access_card($m,$me['id'] ?? 0);

$ic_pin  = '<svg viewBox="0 0 24 24" class="ic"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg>';
$ic_verified = '<svg viewBox="0 0 24 24" class="ic"><path d="M12 3l7 3v6c0 4.4-3 8-7 9-4-1-7-4.6-7-9V6z"/><path d="M9.2 12.2l2 2 3.6-3.9"/></svg>';
?>
<article class="ccard<?= $access['charge'] ? ' is-profile-paid' : '' ?>" data-user="<?= (int) $m['id'] ?>" tabindex="0" aria-label="Xem hồ sơ" data-profile-preview-card="<?= e($m['slug']) ?>">
    <?php if ($access['label']): ?><a class="profile-price-label<?= $access['purchased'] ? ' is-open' : '' ?>" href="<?= site_url('user/'.$m['slug']) ?>"><?= e($access['label']) ?></a><?php endif; ?>
    <a class="ccard-photo" href="<?= site_url('profile/' . $m['slug']) ?>">
        <img src="<?= avatar_url($m['avatar'], $m['gender']) ?>" alt="<?= e(display_name($m)) ?>" loading="lazy">
        <span class="ccard-tags">
            <?php if ($online): ?><span class="tag tag-online">Online</span><?php endif; ?>
            <?php if (($m['kyc_status'] ?? '') === 'verified'): ?>
                <span class="tag tag-verified"><?= $ic_verified ?>Xác thực</span>
            <?php endif; ?>
        </span>
    </a>

    <div class="ccard-body">
        <h3 class="ccard-name">
            <a href="<?= site_url('profile/' . $m['slug']) ?>"><?= e(display_name($m)) ?><?= $age ? ', ' . $age : '' ?></a>
        </h3>

        <p class="ccard-place">
            <span class="mi"><?= $ic_pin ?><?= !empty($m['province_name']) ? e($m['province_name']) : 'Chưa rõ khu vực' ?></span>
        </p>

        <p class="ccard-note"><?= $user && !empty($m['bio']) ? e(excerpt($m['bio'], 90)) : '' ?></p>

    </div>
</article>
