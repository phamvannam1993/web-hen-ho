<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Trang Tổng quan tài khoản — dựng theo `src/routes/tai-khoan.index.tsx`.
 *
 * Khung (cột trái, thanh trên/dưới trên điện thoại) do account/_shell.php lo,
 * tệp này chỉ in phần nội dung.
 */

/* Phần trăm và danh sách việc còn thiếu lấy từ CÙNG một hàm với trang Hồ sơ,
   để hai trang không bao giờ nói hai con số khác nhau. */
$hs        = tk_ho_so_day_du($me);
$diem      = $hs['phan_tram'];
$con_thieu = $hs['thieu'];

$co_tin_dang = !empty($settings['enable_posts']);
$chuoi       = (int) ($streak['streak'] ?? 0);   // M_streak::cham_cong() trả khoá `streak`
$vip_den     = !empty($me['is_vip']) && !empty($me['vip_expired_at']) && strtotime($me['vip_expired_at']) > time()
    ? date('d/m/Y', strtotime($me['vip_expired_at'])) : null;

// array(biểu tượng, giá trị, nhãn, đường dẫn, sắc thái)
$stats = array(
    array('coins',   number_format((int) $me['coin_balance']), 'Số dư xu',        site_url('tai-khoan/nap-xu'),       'gold'),
    array('heart',   number_format($liked_count),              'Người thích bạn', site_url('tai-khoan/ai-thich-ban'), 'brand'),
    array('match',   number_format(count($matches)),           'Đã ghép đôi',     site_url('tai-khoan/quan-tam'),     ''),
    array('message', number_format($unread_msg),               'Tin nhắn mới',    site_url('tai-khoan/tin-nhan'),     ''),
    array('eye',     number_format($viewer_count),             'Lượt xem hồ sơ',  site_url('tai-khoan/ai-xem-ho-so'), ''),
    array('file',    number_format($post_count),               'Tin đăng',        $co_tin_dang ? site_url('tai-khoan/tin-dang') : null, ''),
    array('bell',    number_format($unread_noti),              'Thông báo',       site_url('tai-khoan/thong-bao'),    ''),
    array('flame',   $chuoi . ' ngày',                         'Chuỗi đăng nhập', site_url('tai-khoan/chuoi'),        ''),
);
?>
<!-- Thẻ chào -->
<section class="tk-card tk-hero">
    <div class="tk-hero__in">
        <div class="tk-hero__top">
            <img class="tk-hero__av" src="<?= e(avatar_url($me['avatar'] ?? null, $me['gender'] ?? 'other')) ?>"
                 alt="<?= e(display_name($me)) ?>" width="80" height="80">
            <div style="min-width:0">
                <p class="tk-hero__hi"><?= tk_loi_chao() ?>,</p>
                <h1 class="tk-hero__name"><?= e(display_name($me)) ?></h1>
                <?php if ($vip_den || $chuoi > 0): ?>
                    <div class="tk-hero__pills">
                        <?php if ($vip_den): ?>
                            <span class="tk-pill tk-pill--gold"><?= tk_icon('crown') ?>VIP đến <?= $vip_den ?></span>
                        <?php endif; ?>
                        <?php if ($chuoi > 0): ?>
                            <span class="tk-pill tk-pill--onbrand"><?= tk_icon('flame') ?>Chuỗi <?= $chuoi ?> ngày</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="tk-hero__acts">
            <a class="tk-btn tk-btn--gold" href="<?= site_url('swipe-match') ?>"><?= tk_icon('match') ?>Khám phá &amp; ghép đôi</a>
            <a class="tk-btn tk-btn--onbrand" href="<?= site_url('tai-khoan/nap-xu') ?>"><?= tk_icon('coins') ?>Nạp xu / VIP</a>
        </div>
    </div>
</section>

<!-- Ô số liệu -->
<div class="tk-stats">
    <?php foreach ($stats as $s): ?>
        <?php $tag = $s[3] ? 'a' : 'div'; ?>
        <<?= $tag ?> class="tk-stat<?= $s[4] ? ' tk-stat--' . $s[4] : '' ?>"<?= $s[3] ? ' href="' . $s[3] . '"' : '' ?>>
            <span class="tk-stat__ic"><?= tk_icon($s[0]) ?></span>
            <span class="tk-stat__b">
                <span class="tk-stat__n"><?= e($s[1]) ?></span>
                <span class="tk-stat__l"><?= e($s[2]) ?></span>
            </span>
        </<?= $tag ?>>
    <?php endforeach; ?>
</div>

<div class="tk-split">
    <!-- Hoàn thiện hồ sơ -->
    <section class="tk-card">
        <div class="tk-card__h">
            <div>
                <h2 class="tk-card__t">Hoàn thiện hồ sơ</h2>
                <p class="tk-card__d">Hồ sơ đầy đủ được ưu tiên hiển thị và nhận nhiều lượt thích hơn</p>
            </div>
        </div>
        <div class="tk-pc__top">
            <span class="tk-pc__n"><?= $diem ?>%</span>
            <span class="tk-card__d"><?= $con_thieu ? 'Còn ' . count($con_thieu) . ' việc cần làm' : 'Hồ sơ đã đầy đủ' ?></span>
        </div>
        <div class="tk-progress" style="margin-top:12px" role="progressbar" aria-valuenow="<?= $diem ?>" aria-valuemin="0" aria-valuemax="100">
            <i style="width:<?= max(0, min(100, $diem)) ?>%"></i>
        </div>

        <?php if ($con_thieu): ?>
            <ul class="tk-pc__list">
                <?php foreach (array_slice($con_thieu, 0, 4) as $nhan): ?>
                    <li><span><?= e($nhan) ?></span><span class="tk-pc__miss">Chưa có</span></li>
                <?php endforeach; ?>
            </ul>
            <a class="tk-btn tk-btn--brand" style="margin-top:16px" href="<?= site_url('tai-khoan/ho-so') ?>">
                Bổ sung ngay <?= tk_icon('arrow') ?>
            </a>
        <?php else: ?>
            <ul class="tk-pc__list">
                <li><span>Mọi mục đã khai đủ</span><span class="tk-pc__miss" style="color:var(--tk-success)"><?= tk_icon('check') ?></span></li>
            </ul>
        <?php endif; ?>
    </section>

    <!-- Hoạt động gần đây -->
    <section class="tk-card">
        <div class="tk-card__h">
            <div><h2 class="tk-card__t">Hoạt động gần đây</h2></div>
            <?php if (!empty($hoat_dong)): ?>
                <a class="tk-btn tk-btn--ghost tk-btn--sm tk-card__act" href="<?= site_url('tai-khoan/thong-bao') ?>">Xem tất cả</a>
            <?php endif; ?>
        </div>
        <?php if (empty($hoat_dong)): ?>
            <div class="tk-empty">
                <span class="tk-empty__ic"><?= tk_icon('bell') ?></span>
                <h3>Chưa có hoạt động nào</h3>
                <p>Hãy thích vài hồ sơ để bắt đầu nhận lượt thích và ghép đôi.</p>
            </div>
        <?php else: ?>
            <ul class="tk-feed">
                <?php foreach ($hoat_dong as $n): ?>
                    <li>
                        <span class="tk-ibox"><?= tk_icon(tk_noti_icon($n['type'] ?? '')) ?></span>
                        <div style="min-width:0">
                            <p class="tk-feed__tx"><?= e($n['title']) ?></p>
                            <p class="tk-feed__ti"><?= e(time_ago($n['created_at'])) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<!-- Gợi ý -->
<section class="tk-card">
    <div class="tk-card__h">
        <div>
            <h2 class="tk-card__t">Gợi ý phù hợp hôm nay</h2>
            <p class="tk-card__d">Chọn theo tiêu chí tìm kiếm bạn đã khai trong hồ sơ</p>
        </div>
        <a class="tk-btn tk-btn--soft tk-btn--sm tk-card__act" href="<?= site_url('tai-khoan/goi-y') ?>"><?= tk_icon('sparkles') ?>Xem gợi ý</a>
    </div>
    <?php if (empty($goi_y_them)): ?>
        <div class="tk-empty">
            <span class="tk-empty__ic"><?= tk_icon('sparkles') ?></span>
            <h3>Chưa có gợi ý nào</h3>
            <p>Khai đủ tiêu chí tìm kiếm để hệ thống tìm người phù hợp với bạn.</p>
            <div class="tk-empty__act"><a class="tk-btn tk-btn--brand" href="<?= site_url('tai-khoan/ho-so') ?>">Cập nhật tiêu chí</a></div>
        </div>
    <?php else: ?>
        <div class="tk-stack tk-stack--sm tk-only-mobile">
            <?php foreach ($goi_y_them as $g): ?>
                <?php $this->load->view('account/_person', array('p' => $g, 'o' => array('compact' => true))); ?>
            <?php endforeach; ?>
        </div>
        <div class="tk-people tk-people--3 tk-only-desktop">
            <?php foreach ($goi_y_them as $g): ?>
                <?php $this->load->view('account/_person', array('p' => $g, 'o' => array())); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($co_tin_dang): ?>
    <!-- Tin đăng -->
    <section class="tk-card">
        <div class="tk-card__h">
            <div><h2 class="tk-card__t">Tin đăng của bạn</h2></div>
            <?php if (!empty($recent_posts)): ?>
                <a class="tk-btn tk-btn--ghost tk-btn--sm tk-card__act" href="<?= site_url('tai-khoan/tin-dang') ?>">Xem tất cả</a>
            <?php endif; ?>
        </div>
        <?php if (empty($recent_posts)): ?>
            <div class="tk-empty">
                <span class="tk-empty__ic"><?= tk_icon('file') ?></span>
                <h3>Chưa có tin đăng nào</h3>
                <p>Đăng một tin để nhiều người phù hợp tìm thấy bạn nhanh hơn.</p>
                <div class="tk-empty__act"><a class="tk-btn tk-btn--brand" href="<?= site_url('dang-tin') ?>">Đăng tin mới</a></div>
            </div>
        <?php else: ?>
            <ul class="tk-list">
                <?php foreach ($recent_posts as $p): ?>
                    <li>
                        <span class="tk-ibox"><?= tk_icon('file') ?></span>
                        <div style="min-width:0;flex:1">
                            <a class="tk-feed__tx tk-truncate" style="display:block;font-weight:600" href="<?= site_url('tai-khoan/sua-tin/' . $p['id']) ?>"><?= e($p['title']) ?></a>
                            <p class="tk-feed__ti"><?= e(time_ago($p['created_at'])) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>
