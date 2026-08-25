<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $c một hồ sơ ứng viên kèm điểm tương hợp */
$marital = array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp');
$score   = min(100, (int) ($c['match_score'] ?? 0));
$online  = is_online($c['last_active_at']);
// "Mới tham gia": đăng ký trong vòng 7 ngày
$is_new  = !empty($c['created_at']) && strtotime($c['created_at']) > strtotime('-7 days');
// View này có khi được nạp thẳng từ controller (nạp thêm thẻ qua AJAX) nên
// không chắc có sẵn $user như các trang render qua bố cục chung.
$user    = isset($user) ? $user : null;

// Biểu tượng vẽ bằng SVG để kích thước không phụ thuộc font hệ thống
$ic_pin    = '<svg viewBox="0 0 24 24" class="ic"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg>';
$ic_ruler  = '<svg viewBox="0 0 24 24" class="ic"><path d="M3.6 14.4 14.4 3.6l6 6L9.6 20.4z"/><path d="M7 11l2 2M10 8l2 2M13 5l2 2"/></svg>';
$ic_heart  = '<svg viewBox="0 0 24 24" class="ic"><path d="M12 20.5s-7-4.3-7-9.1A4.4 4.4 0 0 1 12 8a4.4 4.4 0 0 1 7 3.4c0 4.8-7 9.1-7 9.1z"/></svg>';
$ic_close  = '<svg viewBox="0 0 24 24" class="ic"><path d="M6 6l12 12M18 6L6 18"/></svg>';
$ic_case   = '<svg viewBox="0 0 24 24" class="ic"><rect x="3" y="7.5" width="18" height="12" rx="2"/><path d="M9 7.5V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.5"/></svg>';
$ic_male   = '<svg viewBox="0 0 24 24" class="ic"><circle cx="10" cy="14" r="5.2"/><path d="M14.2 9.8 20 4m-4.8 0H20v4.8"/></svg>';
$ic_female = '<svg viewBox="0 0 24 24" class="ic"><circle cx="12" cy="9" r="5.2"/><path d="M12 14.2V21m-3 -3.2h6"/></svg>';
?>
<article class="swipe-card" data-user="<?= (int) $c['id'] ?>">
    <div class="swipe-photo">
        <img src="<?= avatar_url($c['avatar'], $c['gender']) ?>" alt="<?= e(display_name($c)) ?>" loading="lazy">

        <div class="swipe-badges">
            <?php if ($online): ?><span class="tag tag-online">Online</span><?php endif; ?>
            <?php if ($is_new): ?><span class="tag tag-new">Mới tham gia</span><?php endif; ?>
            <?php if (!empty($c['is_vip'])): ?><span class="tag tag-vip">VIP</span><?php endif; ?>
        </div>

        <?php if ($score > 0): ?>
            <span class="swipe-score" title="Mức độ tương hợp"><?= $score ?>%</span>
        <?php endif; ?>

        <span class="swipe-gender <?= $c['gender'] === 'female' ? 'is-female' : 'is-male' ?>">
            <?= $c['gender'] === 'female' ? $ic_female : $ic_male ?>
        </span>
    </div>

    <div class="swipe-body">
        <h3 class="swipe-name"><?= e(display_name($c)) ?><?= !empty($c['age']) ? ', ' . (int) $c['age'] : '' ?></h3>

        <p class="swipe-meta">
            <?php /* Bọc từng cặp biểu tượng + chữ để chúng không tách nhau khi xuống dòng */ ?>
            <span class="mi"><?= $ic_pin ?><?= !empty($c['province_name']) ? e($c['province_name']) : 'Chưa rõ khu vực' ?></span>
            <?php if (!empty($c['job'])): ?><span class="mi"><?= $ic_case ?><?= e($c['job']) ?></span><?php endif; ?>
        </p>

        <?php if (!empty($c['height_cm']) || !empty($c['marital_status'])): ?>
            <p class="swipe-meta">
                <?php if (!empty($c['height_cm'])): ?>
                    <span class="mi"><?= $ic_ruler ?><?= (int) $c['height_cm'] ?> cm</span>
                <?php endif; ?>
                <?php if (!empty($c['marital_status'])): ?>
                    <span class="mi"><?= $ic_heart ?><?= e($marital[$c['marital_status']] ?? '') ?></span>
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <?php /* Luôn giữ khối này kể cả khi trống, để các thẻ cùng hàng cao bằng nhau */ ?>
        <p class="swipe-bio"><?= !empty($c['bio']) ? e(excerpt($c['bio'], 130)) : '' ?></p>

        <div class="swipe-actions">
            <?php if ($user): ?>
                <button type="button" class="btn-swipe btn-swipe-pass" data-action="pass"><?= $ic_close ?>Bỏ qua</button>
                <button type="button" class="btn-swipe btn-swipe-like <?= !empty($c['liked']) ? 'is-liked' : '' ?>"
                        data-action="like"><?= $ic_heart ?><span class="js-like-text"><?= !empty($c['liked']) ? 'Đã thích' : 'Thích' ?></span></button>
            <?php else: ?>
                <a class="btn-swipe btn-swipe-pass" href="<?= site_url('profile/' . $c['slug']) ?>">Hồ sơ</a>
                <a class="btn-swipe btn-swipe-like" href="<?= site_url('dang-nhap') ?>"><?= $ic_heart ?>Thích</a>
            <?php endif; ?>
        </div>
    </div>
</article>
