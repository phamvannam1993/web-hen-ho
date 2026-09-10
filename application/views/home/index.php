<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Trang chủ.
 *
 * Bố cục theo bản thiết kế: mở đầu bằng khối giới thiệu lớn, rồi tới lý do
 * chọn, ba bước bắt đầu, hai khối dữ liệu thật (ai đã thích bạn / gợi ý ghép
 * đôi), các khối thành viên sẵn có, cảm nhận và dải thống kê chốt trang.
 */

$ic = function ($path, $extra = '') {
    return '<svg viewBox="0 0 24 24" class="hm-ic ' . $extra . '">' . $path . '</svg>';
};
$ic_heart  = '<path d="M12 20.8s-7.2-4.5-7.2-9.6a4.5 4.5 0 0 1 7.2-3.6 4.5 4.5 0 0 1 7.2 3.6c0 5.1-7.2 9.6-7.2 9.6z"/>';
$ic_shield = '<path d="M12 3l7.5 3.2v5.4c0 4.7-3.2 8.6-7.5 9.6-4.3-1-7.5-4.9-7.5-9.6V6.2z"/>';
$ic_users  = '<circle cx="9" cy="8.5" r="3.4"/><path d="M2.8 19.5a6.2 6.2 0 0 1 12.4 0"/><path d="M16.4 5.6a3.4 3.4 0 0 1 0 5.8M17.6 19.5a6.2 6.2 0 0 0-1.9-4.5"/>';
$ic_chat   = '<path d="M4 5.5h16v11H9.5L5.5 20v-3.5H4z"/>';
$ic_pen    = '<path d="M4 20h4l10-10-4-4L4 16z"/><path d="M13.5 6.5l4 4"/>';
$ic_search = '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>';
$ic_star   = '<path d="M12 3.8l2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.2-4.1 5.8-.8z"/>';
$ic_pin    = '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>';
$ic_userpl = '<circle cx="10" cy="8" r="3.6"/><path d="M3.5 19.5a6.5 6.5 0 0 1 13 0"/><path d="M18 8.5v5M20.5 11h-5"/>';
$ic_close  = '<path d="M6 6l12 12M18 6L6 18"/>';
?>

<?php /* ===================== Khối mở đầu ===================== */ ?>
<?php
/* Ảnh nền khối mở đầu. Thay ảnh khác thì chỉ cần ghi đè file này, hoặc đặt
   khoá cấu hình home_hero_image (Quản trị -> Cấu hình) trỏ tới ảnh khác. */
$hero_img = setting('home_hero_image') ?: 'assets/images/banner.png';
?>
<section class="hm-hero">
    <div class="hm-hero-bg" aria-hidden="true"
         style="background-image:url('<?= base_url(ltrim($hero_img, '/')) ?>')"></div>
    <div class="container hm-hero-inner">
        <h1 class="hm-hero-title">Nơi trái tim Việt Nam tìm thấy nhau</h1>
        <p class="hm-hero-sub">Kết nối với người phù hợp – Chỉ nhắn tin khi cả hai cùng thả tim ❤️</p>

        <form class="hm-hero-search" method="get" action="<?= site_url('tim-kiem') ?>">
            <span class="hm-hero-search-ic" aria-hidden="true"><?= $ic($ic_search) ?></span>
            <input type="text" name="q" value="<?= e($this->input->get('q')) ?>"
                   placeholder="Tìm kiếm bạn đời theo sở thích, nghề nghiệp, địa điểm…">
            <button class="btn-hm btn-hm-solid" type="submit">Tìm kiếm</button>
        </form>

        <a class="btn-hm btn-hm-solid btn-hm-lg hm-hero-cta" href="<?= site_url('swipe-match') ?>">Ghép Đôi Online</a>

        <ul class="hm-hero-stats">
            <li><?= $ic($ic_userpl) ?><b><?= number_format(max(50000, $stats['total']), 0, ',', '.') ?>+</b> Thành viên</li>
            <li><?= $ic($ic_heart) ?><b><?= number_format(max(5000, $couple_count), 0, ',', '.') ?>+</b> Cặp đôi</li>
            <li><?= $ic($ic_star) ?><b>4.8/5</b> Đánh giá</li>
        </ul>
    </div>
</section>

<?php /* ===================== Vì sao chọn ===================== */ ?>
<section class="hm-sec">
    <div class="container">
        <h2 class="hm-title">Vì sao chọn <?= e($settings['site_name'] ?? 'Saigon Cupid') ?>?</h2>
        <div class="hm-why">
            <?php
            $whys = array(
                array($ic_heart,  'Kết nối chân thật',  'Chỉ những người thực sự quan tâm mới có thể match và nhắn tin với nhau.'),
                array($ic_shield, 'An toàn &amp; Bảo mật', 'Thông tin cá nhân được mã hóa và bảo vệ tuyệt đối.'),
                array($ic_users,  'Cộng đồng đông đảo', 'Kết nối với hàng nghìn thành viên từ khắp các tỉnh thành.'),
                array($ic_chat,   'Tìm kiếm thông minh', 'Gợi ý đối tượng phù hợp dựa trên sở thích và tính cách của bạn.'),
            );
            foreach ($whys as $w): ?>
                <article class="hm-why-card">
                    <span class="hm-why-ic"><?= $ic($w[0]) ?></span>
                    <h3><?= $w[1] ?></h3>
                    <p><?= $w[2] ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php /* ===================== Ba bước ===================== */ ?>
<section class="hm-sec hm-sec-pink">
    <div class="container">
        <h2 class="hm-title">Bắt đầu tình yêu chỉ với 3 bước</h2>
        <div class="hm-steps">
            <?php
            $steps = array(
                array($ic_pen,   'Tạo hồ sơ', 'Đăng ký nhanh chóng, thêm ảnh và thông tin về bản thân bạn.'),
                array($ic_heart, 'Kết nối',   'Thả tim những người bạn ấn tượng – Họ sẽ biết và có thể đáp lại.'),
                array($ic_chat,  'Trò chuyện','Khi cả hai cùng thả tim, mở khóa chat và bắt đầu câu chuyện tình yêu.'),
            );
            foreach ($steps as $i => $st): ?>
                <?php if ($i > 0): ?><span class="hm-step-sep" aria-hidden="true">›</span><?php endif; ?>
                <article class="hm-step">
                    <span class="hm-step-ic">
                        <?= $ic($st[0]) ?>
                        <i class="hm-step-num"><?= $i + 1 ?></i>
                    </span>
                    <div class="hm-step-text">
                        <h3><?= $st[1] ?></h3>
                        <p><?= $st[2] ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="hm-center">
            <a class="btn-hm btn-hm-soft btn-hm-lg" href="<?= site_url('swipe-match') ?>">Tìm ngay nửa kia</a>
        </div>
    </div>
</section>

<?php /* ===================== Ai đã thích bạn ===================== */ ?>
<?php if (!empty($liked_me)): ?>
<section class="hm-sec">
    <div class="container">
        <div class="hm-head">
            <div>
                <h2 class="hm-title is-left">Có <?= number_format($liked_me_total) ?> người đã thích bạn</h2>
                <p class="hm-sub">Hãy thả tim lại để kết nối!</p>
            </div>
            <a class="hm-link" href="<?= site_url('tai-khoan/quan-tam') ?>">Xem tất cả →</a>
        </div>

        <div class="hm-likers">
            <?php foreach ($liked_me as $m): $tuoi = age_from($m['birthday']); ?>
                <a class="hm-liker" href="<?= site_url('profile/' . $m['slug']) ?>">
                    <img src="<?= avatar_url($m['avatar'], $m['gender']) ?>" alt="<?= e(display_name($m)) ?>" loading="lazy">
                    <b><?= e(display_name($m)) ?><?= $tuoi ? ', ' . $tuoi : '' ?></b>
                    <small><?= !empty($m['province_name']) ? e($m['province_name']) : 'Chưa rõ khu vực' ?></small>
                </a>
            <?php endforeach; ?>

            <?php $con_lai = $liked_me_total - count($liked_me); ?>
            <?php if ($con_lai > 0): ?>
                <a class="hm-liker hm-liker-more" href="<?= site_url('tai-khoan/quan-tam') ?>">
                    <span class="hm-liker-plus">+<?= number_format($con_lai) ?> nữa</span>
                    <b>Thành viên khác</b>
                    <small>Đang chờ bạn</small>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php /* ===================== Gợi ý ghép đôi ===================== */ ?>
<?php if (!empty($suggestions)): ?>
<section class="hm-sec hm-sec-grey">
    <div class="container">
        <h2 class="hm-title is-left">Gợi ý ghép đôi cho bạn</h2>
        <p class="hm-sub">Sắp xếp theo mức độ tương hợp với tiêu chí của bạn</p>

        <div class="hm-sugs">
            <?php foreach ($suggestions as $m):
                $tuoi     = age_from($m['birthday']);
                $da_match = in_array((int) $m['id'], $matched_ids, true);
                // match_score tối đa lý thuyết là 155 nhưng thực tế hiếm khi vượt
                // 120, nên lấy 120 làm mốc 100% rồi kẹp trong khoảng 60–99 để
                // con số vừa sát thực vừa không hiện những mức khó tin.
                $hop = max(60, min(99, (int) round(($m['match_score'] ?? 0) * 100 / 120)));
                $chips = array_filter(array(
                    !empty($m['province_name']) ? $m['province_name'] : null,
                    !empty($m['job'])           ? $m['job'] : null,
                    !empty($m['height_cm'])     ? (int) $m['height_cm'] . 'cm' : null,
                    !empty($m['marital_status']) && $m['marital_status'] === 'doc_than' ? 'Độc thân' : null,
                ));
            ?>
                <article class="hm-sug" data-user="<?= (int) $m['id'] ?>">
                    <a class="hm-sug-photo" href="<?= site_url('profile/' . $m['slug']) ?>">
                        <img src="<?= avatar_url($m['avatar'], $m['gender']) ?>" alt="<?= e(display_name($m)) ?>" loading="lazy">
                    </a>

                    <div class="hm-sug-body">
                        <div class="hm-sug-top">
                            <h3><a href="<?= site_url('profile/' . $m['slug']) ?>"><?= e(display_name($m)) ?><?= $tuoi ? ', ' . $tuoi : '' ?></a></h3>
                            <span class="hm-pill hm-pill-pink">Tương hợp: <?= $hop ?>%</span>
                            <?php if ($da_match): ?>
                                <span class="hm-pill hm-pill-green">Đã match!</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($chips): ?>
                            <p class="hm-sug-chips">
                                <?php foreach ($chips as $c): ?><span><?= e($c) ?></span><?php endforeach; ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="hm-sug-actions">
                        <?php if ($da_match): ?>
                            <button type="button" class="btn-hm btn-hm-solid" data-chat-with="<?= (int) $m['id'] ?>">
                                <?= $ic($ic_chat) ?>Trò chuyện ngay
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn-hm btn-hm-line" data-card-action="pass">
                                <?= $ic($ic_close) ?>Bỏ qua
                            </button>
                            <button type="button" class="btn-hm btn-hm-solid <?= !empty($m['liked']) ? 'is-liked' : '' ?>"
                                    data-card-action="like" data-like-label="Thả tim">
                                <?= $ic($ic_heart) ?><span class="js-like-text"><?= !empty($m['liked']) ? 'Đã thích' : 'Thả tim' ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="hm-center">
            <a class="hm-link" href="<?= site_url('swipe-match') ?>">Xem thêm đề xuất khác →</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php /* ===================== Cảm nhận ===================== */ ?>
<section class="hm-sec">
    <div class="container">
        <h2 class="hm-title">Họ đã tìm thấy tình yêu</h2>
        <div class="hm-quotes">
            <?php
            $quotes = array(
                array('Nhờ ' . ($settings['site_name'] ?? 'Saigon Cupid') . ' mà mình đã gặp được người ấy sau 3 năm độc thân. '
                    . 'Quy trình kết nối thông minh giúp tụi mình tìm thấy nhiều tiếng nói chung trước khi trò chuyện trực tiếp.',
                    'Chị Minh Anh', 'Hà Nội', 'female'),
                array('Ứng dụng tuyệt vời, an toàn và cực kỳ bảo mật. Mình thích tính năng thả tim hai chiều rồi mới trò chuyện, '
                    . 'giúp tránh được rất nhiều tin nhắn làm phiền.',
                    'Anh Quốc Bảo', 'TP. Hồ Chí Minh', 'male'),
                array('Ban đầu mình khá e dè khi hẹn hò online. Nhưng ' . ($settings['site_name'] ?? 'Saigon Cupid')
                    . ' đã cho mình một trải nghiệm cực kỳ yên tâm với tính năng xác thực tài khoản chặt chẽ.',
                    'Chị Thu Thảo', 'Đà Nẵng', 'female'),
            );
            foreach ($quotes as $q): ?>
                <figure class="hm-quote">
                    <p class="hm-quote-stars" aria-label="5 trên 5 sao">
                        <?= str_repeat($ic($ic_star, 'is-star'), 5) ?>
                    </p>
                    <blockquote>“<?= e($q[0]) ?>”</blockquote>
                    <figcaption>
                        <img src="<?= avatar_url(null, $q[3]) ?>" alt="" loading="lazy">
                        <span><b><?= e($q[1]) ?></b><small><?= e($q[2]) ?></small></span>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php /* ===================== Dải thống kê + kêu gọi ===================== */ ?>
<section class="hm-band">
    <div class="container">
        <ul class="hm-band-stats">
            <li><span class="hm-band-ic"><?= $ic($ic_userpl) ?></span>
                <b><?= number_format(max(50000, $stats['total']), 0, ',', '.') ?>+</b><small>Thành viên đăng ký</small></li>
            <li><span class="hm-band-ic"><?= $ic($ic_heart) ?></span>
                <b><?= number_format(max(5000, $couple_count), 0, ',', '.') ?>+</b><small>Cặp đôi kết nối</small></li>
            <li><span class="hm-band-ic"><?= $ic($ic_pin) ?></span>
                <b><?= (int) $province_count ?></b><small>Tỉnh thành kết nối</small></li>
            <li><span class="hm-band-ic"><?= $ic($ic_star) ?></span>
                <b>4.8/5</b><small>Đánh giá hài lòng</small></li>
        </ul>

        <?php if (!$user): ?>
            <div class="hm-band-cta">
                <h2>Sẵn sàng tìm nửa kia?</h2>
                <a class="btn-hm btn-hm-white btn-hm-lg" href="<?= site_url('dang-ky') ?>">Tham gia ngay – Miễn phí</a>
                <p>Đã có tài khoản? <a href="<?= site_url('dang-nhap') ?>">Đăng nhập</a></p>
            </div>
        <?php else: ?>
            <div class="hm-band-cta">
                <h2>Sẵn sàng tìm nửa kia?</h2>
                <a class="btn-hm btn-hm-white btn-hm-lg" href="<?= site_url('swipe-match') ?>">Khám phá ngay</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php /* ===== Thanh điều hướng đáy: chỉ hiện ở trang chủ ===== */ ?>
<div class="bottom-nav" id="bottomNav">
    <div class="bottom-nav-inner">
        <a href="<?= site_url('') ?>" class="bottom-nav-item active">
            <span class="bottom-nav-icon">🏠</span>
            <span class="bottom-nav-label">Trang chủ</span>
        </a>
        <a href="<?= site_url('swipe-match') ?>" class="bottom-nav-item">
            <span class="bottom-nav-icon">👩🏻‍❤️‍👨🏻</span>
            <span class="bottom-nav-label">Ghép đôi</span>
        </a>
        <a href="<?= site_url('hen-ho') ?>" class="bottom-nav-item">
            <span class="bottom-nav-icon">💕</span>
            <span class="bottom-nav-label">Hẹn hò</span>
        </a>
        <a href="<?= site_url('tam-su') ?>" class="bottom-nav-item">
            <span class="bottom-nav-icon">💬</span>
            <span class="bottom-nav-label">Tâm sự</span>
        </a>
    </div>
</div>
