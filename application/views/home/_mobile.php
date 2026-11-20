<?php defined('BASEPATH') OR exit('No direct script access allowed');
$home_groups = array(
    array('Cùng phong cách của bạn', $user ? 'Những người có cùng sở thích, phong cách với bạn' : 'Khám phá những kết nối mới trong cộng đồng', 'Cùng gu', array_slice($mobile_members, 0, 5)),
    array($user ? 'Phù hợp với bạn' : 'Có thể bạn muốn gặp', $user ? 'Những người phù hợp nhất với hồ sơ của bạn' : 'Làm quen và bắt đầu câu chuyện của riêng bạn', $user ? 'Hợp nhau' : 'Khám phá', array_slice($mobile_members, 5)),
);
?>
<div class="home-mobile">
    <section class="home-mobile-banner" aria-label="Kết nối cùng Saigon Cupid" data-home-banner>
        <img src="<?= base_url('assets/site/img/home-mobile/banner-top.jpg') ?>" width="1536" height="640" alt="Đôi bạn bên nhau trong không gian trái tim hồng" fetchpriority="high">
        <div class="home-mobile-banner-copy">
            <div data-home-slide><h1>Gặp đúng người</h1><p>Bắt đầu một kết nối chân thành</p></div>
            <div data-home-slide hidden><h2>Cùng nhịp trái tim</h2><p>Tìm người đồng điệu với bạn</p></div>
            <div data-home-slide hidden><h2>Chat ẩn danh</h2><p>Trò chuyện, làm quen bạn mới</p></div>
        </div>
        <div class="home-mobile-dots" aria-label="Chọn banner">
            <?php for ($i = 0; $i < 3; $i++): ?>
            <button type="button" data-home-slide-to="<?= $i ?>" aria-label="Banner <?= $i + 1 ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"></button>
            <?php endfor; ?>
        </div>
    </section>

    <section class="home-mobile-tasks" aria-label="Hoạt động mỗi ngày">
        <a class="home-mobile-task" href="<?= site_url('clubs') ?>">
            <img src="<?= base_url('assets/site/img/home-mobile/banner-task-long.jpg') ?>" width="1152" height="576" alt="" loading="lazy">
            <div><h2>Chat's</h2><p>Trò chuyện ẩn danh, làm quen bạn mới</p></div>
        </a>
        <a class="home-mobile-task" href="<?= site_url('tai-khoan/chuoi') ?>">
            <img src="<?= base_url('assets/site/img/home-mobile/banner-task-short.jpg') ?>" width="992" height="672" alt="" loading="lazy">
            <div><h2>Điểm ưu tú</h2></div>
        </a>
    </section>

    <nav class="home-mobile-shortcuts" aria-label="Khám phá tính năng">
        <?php foreach (array(array('hen-ho', 'Hẹn hò', 'heart'), array('swipe-match', 'Ghép đôi', 'sparkles'), array('clubs/hen-ho', 'Chat ẩn danh', 'message')) as $shortcut): ?>
        <a href="<?= site_url($shortcut[0]) ?>"><span class="home-mobile-shortcut-ring"><span><?= tk_icon($shortcut[2]) ?></span></span><strong><?= e($shortcut[1]) ?></strong></a>
        <?php endforeach; ?>
    </nav>

    <?php foreach ($home_groups as $group_index => $group): ?>
    <section class="home-mobile-members" aria-label="<?= e($group[0]) ?>">
        <div class="home-mobile-heading"><h2><?= e($group[0]) ?></h2><a href="<?= site_url('thanh-vien') ?>">Tất cả <span aria-hidden="true">›</span></a></div>
        <p class="home-mobile-description"><?= e($group[1]) ?></p>
        <div class="home-mobile-list<?= $group_index === 0 ? ' home-mobile-list--carousel' : ' home-mobile-list--suggestions hm-sugs' ?>"<?= $group_index === 1 && $user ? ' data-account-suggestions="home"' : '' ?>>
            <?php foreach ($group[3] as $member): ?>
                <?php if ($group_index === 1): ?>
                <?php $this->load->view('home/_suggestion', array('m' => $member, 'matched_ids' => $matched_ids ?? array())); ?>
                <?php else: ?>
                <?php $this->load->view('home/_mobile_member', array('m' => $member, 'home_label' => $group[2], 'home_carousel' => $group_index === 0)); ?>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$group[3]): ?><p class="home-mobile-empty">Chưa có hồ sơ phù hợp. Bạn hãy quay lại sau nhé.</p><?php endif; ?>
        </div>
        <?php if ($group_index === 1 && !empty($mobile_members_has_more)): ?>
        <button type="button" class="home-mobile-load-more" data-home-load-more data-url="<?= e(site_url('ajax/goi-y-trang-chu')) ?>" data-offset="<?= count($mobile_members) ?>" aria-live="polite">Xem thêm hồ sơ</button>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>
</div>
<script src="<?= base_url('assets/site/js/home-mobile.js') ?>?v=<?= filemtime(FCPATH . 'assets/site/js/home-mobile.js') ?>" defer></script>
