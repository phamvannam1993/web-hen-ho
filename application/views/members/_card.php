<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $m một hồ sơ thành viên */
$age     = age_from($m['birthday']);
$marital = array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp');
$online  = is_online($m['last_active_at']);
$is_new  = !empty($m['created_at']) && strtotime($m['created_at']) > strtotime('-7 days');
$tags    = !empty($m['interest_names']) ? array_slice(explode('|', $m['interest_names']), 0, 3) : array();
$me      = isset($user) ? $user : null;

$ic_pin    = '<svg viewBox="0 0 24 24" class="ic"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg>';
$ic_ruler  = '<svg viewBox="0 0 24 24" class="ic"><path d="M3.6 14.4 14.4 3.6l6 6L9.6 20.4z"/><path d="M7 11l2 2M10 8l2 2M13 5l2 2"/></svg>';
$ic_case   = '<svg viewBox="0 0 24 24" class="ic"><rect x="3" y="7.5" width="18" height="12" rx="2"/><path d="M9 7.5V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.5"/></svg>';
$ic_heart  = '<svg viewBox="0 0 24 24" class="ic"><path d="M12 20.5s-7-4.3-7-9.1A4.4 4.4 0 0 1 12 8a4.4 4.4 0 0 1 7 3.4c0 4.8-7 9.1-7 9.1z"/></svg>';
$ic_close  = '<svg viewBox="0 0 24 24" class="ic"><path d="M6 6l12 12M18 6L6 18"/></svg>';
$ic_male   = '<svg viewBox="0 0 24 24" class="ic"><circle cx="10" cy="14" r="5.2"/><path d="M14.2 9.8 20 4m-4.8 0H20v4.8"/></svg>';
$ic_female = '<svg viewBox="0 0 24 24" class="ic"><circle cx="12" cy="9" r="5.2"/><path d="M12 14.2V21m-3 -3.2h6"/></svg>';
$ic_gender = $m['gender'] === 'female' ? $ic_female : $ic_male;
$gclass    = $m['gender'] === 'female' ? 'is-female' : 'is-male';
?>
<article class="pcard" data-user="<?= (int) $m['id'] ?>">
    <a class="pcard-photo" href="<?= site_url('profile/' . $m['slug']) ?>">
        <img src="<?= avatar_url($m['avatar'], $m['gender']) ?>" alt="<?= e(display_name($m)) ?>" loading="lazy">
        <span class="pcard-tags">
            <?php if ($online): ?><span class="tag tag-online">Online</span><?php endif; ?>
            <?php if ($is_new): ?><span class="tag tag-new">Mới tham gia</span><?php endif; ?>
            <?php if (!empty($m['is_vip'])): ?><span class="tag tag-vip">VIP</span><?php endif; ?>
        </span>
        <span class="pcard-gender <?= $gclass ?>"><?= $ic_gender ?></span>
    </a>

    <div class="pcard-body">
        <h3 class="pcard-name">
            <a href="<?= site_url('profile/' . $m['slug']) ?>"><?= e(display_name($m)) ?><?= $age ? ', ' . $age : '' ?></a>
            <span class="pcard-gender-inline <?= $gclass ?>"><?= $ic_gender ?></span>
        </h3>

        <p class="pcard-meta">
            <?= $ic_pin ?><span><?= !empty($m['province_name']) ? e($m['province_name']) : 'Chưa rõ khu vực' ?></span>
        </p>

        <?php if (!empty($m['height_cm']) || !empty($m['job']) || !empty($m['marital_status'])): ?>
            <p class="pcard-meta">
                <?php if (!empty($m['height_cm'])): ?>
                    <?= $ic_ruler ?><span><?= (int) $m['height_cm'] ?> cm<?= !empty($m['weight_kg']) ? ' · ' . (int) $m['weight_kg'] . ' kg' : '' ?></span>
                <?php endif; ?>
                <?php if (!empty($m['job'])): ?><?= $ic_case ?><span><?= e($m['job']) ?></span><?php endif; ?>
                <?php if (!empty($m['marital_status'])): ?><?= $ic_heart ?><span><?= e($marital[$m['marital_status']] ?? '') ?></span><?php endif; ?>
            </p>
        <?php endif; ?>

        <?php /* Luôn giữ khối này kể cả khi trống để các thẻ cùng hàng cao bằng nhau */ ?>
        <p class="pcard-bio"><?= !empty($m['bio']) ? e(excerpt($m['bio'], 110)) : '' ?></p>

        <?php /* Luôn render để thẻ có sở thích và chưa có vẫn cao bằng nhau */ ?>
        <p class="pcard-chips">
            <?php foreach ($tags as $t): ?><span><?= e($t) ?></span><?php endforeach; ?>
        </p>

        <div class="pcard-actions">
            <?php if ($me && (int) $me['id'] !== (int) $m['id']): ?>
                <button type="button" class="btn-swipe btn-swipe-pass" data-card-action="pass"><?= $ic_close ?>Bỏ qua</button>
                <button type="button" class="btn-swipe btn-swipe-like" data-card-action="like"><?= $ic_heart ?><span class="js-like-text">Thích</span></button>
            <?php else: ?>
                <a class="btn-swipe btn-swipe-pass" href="<?= site_url('profile/' . $m['slug']) ?>">Xem hồ sơ</a>
                <a class="btn-swipe btn-swipe-like" href="<?= site_url($me ? 'profile/' . $m['slug'] : 'dang-nhap') ?>"><?= $ic_heart ?>Thích</a>
            <?php endif; ?>
        </div>
    </div>
</article>
