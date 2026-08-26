<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $m một hồ sơ; thẻ dành riêng cho trang Tâm sự */
$age    = age_from($m['birthday']);
$online = is_online($m['last_active_at']);
$topics = Confide::topics();
$topic  = !empty($m['confide_topic']) ? ($topics[$m['confide_topic']] ?? '') : '';
$me     = isset($user) ? $user : null;

$ic_pin  = '<svg viewBox="0 0 24 24" class="ic"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg>';
$ic_chat = '<svg viewBox="0 0 24 24" class="ic"><path d="M4 5h16v11H8l-4 3z"/><path d="M9 9.5h6M9 12.5h4"/></svg>';
$ic_star = '<svg viewBox="0 0 24 24" class="ic"><path d="M12 4l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 9.7l5.4-.8z"/></svg>';
$ic_verified = '<svg viewBox="0 0 24 24" class="ic"><path d="M12 3l7 3v6c0 4.4-3 8-7 9-4-1-7-4.6-7-9V6z"/><path d="M9.2 12.2l2 2 3.6-3.9"/></svg>';
?>
<article class="ccard" data-user="<?= (int) $m['id'] ?>">
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

        <?php /* Luôn giữ chỗ để các thẻ cùng hàng cao bằng nhau */ ?>
        <p class="ccard-topic"><?php if ($topic): ?><span><?= e($topic) ?></span><?php endif; ?></p>

        <p class="ccard-note"><?= !empty($m['bio']) ? e(excerpt($m['bio'], 90)) : '' ?></p>

        <div class="ccard-actions">
            <?php if ($me && (int) $me['id'] !== (int) $m['id']): ?>
                <button type="button" class="btn-confide btn-confide-chat" data-chat-with="<?= (int) $m['id'] ?>"><?= $ic_chat ?>Nhắn tin</button>
                <button type="button" class="btn-confide btn-confide-like <?= !empty($m['liked']) ? 'is-liked' : '' ?>"
                        data-card-action="like"><?= $ic_star ?><span class="js-like-text"><?= !empty($m['liked']) ? 'Đã thích' : 'Thích' ?></span></button>
            <?php else: ?>
                <a class="btn-confide btn-confide-chat" href="<?= site_url($me ? 'profile/' . $m['slug'] : 'dang-nhap') ?>"><?= $ic_chat ?>Nhắn tin</a>
                <a class="btn-confide btn-confide-like" href="<?= site_url($me ? 'profile/' . $m['slug'] : 'dang-nhap') ?>"><?= $ic_star ?>Thích</a>
            <?php endif; ?>
        </div>
    </div>
</article>
