<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $c hồ sơ ứng viên; thẻ lớn dùng cho khung vuốt */
$marital = array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp');
$score   = min(100, (int) ($c['match_score'] ?? 0));
$online  = is_online($c['last_active_at']);
$is_new  = !empty($c['created_at']) && strtotime($c['created_at']) > strtotime('-7 days');
$user    = isset($user) ? $user : null;
$topics  = class_exists('Confide') ? Confide::topics() : array();

$ic_pin   = '<svg viewBox="0 0 24 24" class="ic"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg>';
$ic_ruler = '<svg viewBox="0 0 24 24" class="ic"><path d="M3.6 14.4 14.4 3.6l6 6L9.6 20.4z"/><path d="M7 11l2 2M10 8l2 2M13 5l2 2"/></svg>';
$ic_case  = '<svg viewBox="0 0 24 24" class="ic"><rect x="3" y="7.5" width="18" height="12" rx="2"/><path d="M9 7.5V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.5"/></svg>';
$ic_heart = '<svg viewBox="0 0 24 24" class="ic"><path d="M12 20.5s-7-4.3-7-9.1A4.4 4.4 0 0 1 12 8a4.4 4.4 0 0 1 7 3.4c0 4.8-7 9.1-7 9.1z"/></svg>';
?>
<article class="sw-card" data-user="<?= (int) $c['id'] ?>">
    <div class="sw-photo">
        <img src="<?= avatar_url($c['avatar'], $c['gender']) ?>" alt="<?= e(display_name($c)) ?>" draggable="false">

        <span class="sw-badges">
            <?php if ($online): ?><span class="tag tag-online">Online</span><?php endif; ?>
            <?php if ($is_new): ?><span class="tag tag-new">Mới tham gia</span><?php endif; ?>
            <?php if (($c['kyc_status'] ?? '') === 'verified'): ?><span class="tag tag-verified">Xác thực</span><?php endif; ?>
        </span>

        <?php if ($score > 0): ?>
            <span class="sw-score" title="Mức độ tương hợp"><?= $score ?>% hợp</span>
        <?php endif; ?>

        <?php /* Hai con dấu hiện dần khi kéo thẻ sang trái hoặc phải */ ?>
        <span class="sw-stamp sw-stamp-like">THÍCH</span>
        <span class="sw-stamp sw-stamp-nope">BỎ QUA</span>

        <div class="sw-info">
            <h3><?= e(display_name($c)) ?><?= !empty($c['age']) ? ', ' . (int) $c['age'] : '' ?></h3>
            <p class="sw-meta">
                <span class="mi"><?= $ic_pin ?><?= !empty($c['province_name']) ? e($c['province_name']) : 'Chưa rõ khu vực' ?></span>
                <?php if (!empty($c['job'])): ?><span class="mi"><?= $ic_case ?><?= e($c['job']) ?></span><?php endif; ?>
            </p>
            <p class="sw-meta">
                <?php if (!empty($c['height_cm'])): ?>
                    <span class="mi"><?= $ic_ruler ?><?= (int) $c['height_cm'] ?> cm</span>
                <?php endif; ?>
                <?php if (!empty($c['marital_status'])): ?>
                    <span class="mi"><?= $ic_heart ?><?= e($marital[$c['marital_status']] ?? '') ?></span>
                <?php endif; ?>
            </p>
            <?php if (!empty($c['bio'])): ?>
                <p class="sw-bio"><?= e(excerpt($c['bio'], 120)) ?></p>
            <?php endif; ?>
            <a class="sw-more" href="<?= site_url('profile/' . $c['slug']) ?>">Xem hồ sơ đầy đủ →</a>
        </div>
    </div>
</article>
