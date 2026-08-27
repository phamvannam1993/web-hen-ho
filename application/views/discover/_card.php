<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $c hồ sơ ứng viên — thẻ lớn, cuộn được bên trong */
$marital = array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp');
$edu     = array('thpt' => 'THPT', 'trung_cap' => 'Trung cấp', 'cao_dang' => 'Cao đẳng',
                 'dai_hoc' => 'Đại học', 'sau_dai_hoc' => 'Sau đại học');
$freq    = array('khong' => 'Không', 'thinh_thoang' => 'Thỉnh thoảng', 'thuong_xuyen' => 'Thường xuyên');
$purpose = array('ket_ban' => 'Tìm bạn', 'hen_ho' => 'Hẹn hò', 'nghiem_tuc' => 'Nghiêm túc', 'ket_hon' => 'Tiến tới hôn nhân');

$age    = age_from($c['birthday']);
$online = is_online($c['last_active_at']);
$cung   = zodiac($c['birthday']);
$anh    = !empty($c['photo_paths']) ? array_slice(explode('|', $c['photo_paths']), 0, 4) : array();
$sothich= !empty($c['interest_names']) ? array_slice(explode('|', $c['interest_names']), 0, 6) : array();
$muc    = !empty($c['purpose']) ? ($purpose[$c['purpose']] ?? '') : '';

// Có toạ độ hai bên thì hiện khoảng cách, không thì hiện tên tỉnh
$vitri = !empty($c['province_name']) ? e($c['province_name']) : 'Chưa rõ khu vực';
if (isset($c['khoang_cach']) && $c['khoang_cach'] !== null) {
    $km = (float) $c['khoang_cach'];
    $vitri = $km < 1
        ? 'Cách bạn dưới 1 km'
        : 'Cách bạn ' . ($km < 10 ? number_format($km, 1) : number_format(round($km))) . ' km';
}

$ic = function ($d, $extra = '') { return '<svg viewBox="0 0 24 24" class="ic">' . $d . '</svg>'; };
$ic_pin   = $ic('<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>');
$ic_ruler = $ic('<path d="M3.6 14.4 14.4 3.6l6 6L9.6 20.4z"/><path d="M7 11l2 2M10 8l2 2M13 5l2 2"/>');
$ic_case  = $ic('<rect x="3" y="7.5" width="18" height="12" rx="2"/><path d="M9 7.5V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.5"/>');
$ic_heart = $ic('<path d="M12 20.5s-7-4.3-7-9.1A4.4 4.4 0 0 1 12 8a4.4 4.4 0 0 1 7 3.4c0 4.8-7 9.1-7 9.1z"/>');
$ic_book  = $ic('<path d="M4 5.5h6a2 2 0 0 1 2 2v11a2 2 0 0 0-2-2H4z"/><path d="M20 5.5h-6a2 2 0 0 0-2 2v11a2 2 0 0 1 2-2h6z"/>');
$ic_star  = $ic('<path d="M12 4l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 9.7l5.4-.8z"/>');
?>
<article class="sw-card" data-user="<?= (int) $c['id'] ?>">
    <div class="sw-scroll">
        <div class="sw-photo">
            <img src="<?= avatar_url($c['avatar'], $c['gender']) ?>" alt="<?= e(display_name($c)) ?>" draggable="false">

            <span class="sw-badges">
                <?php if ($online): ?><span class="tag tag-online">Online</span><?php endif; ?>
                <?php if (($c['kyc_status'] ?? '') === 'verified'): ?><span class="tag tag-verified">Xác thực</span><?php endif; ?>
            </span>

            <span class="sw-place"><?= $ic_pin ?><?= $vitri ?></span>

            <span class="sw-stamp sw-stamp-like">THÍCH</span>
            <span class="sw-stamp sw-stamp-nope">BỎ QUA</span>

        </div>

        <?php /* Thông tin nằm trên nền trắng dưới ảnh, không đè lên mặt người */ ?>
        <div class="sw-bar">
            <div class="sw-bar-main">
                <h3><?= e(display_name($c)) ?><?= $age ? ', ' . $age : '' ?></h3>
                <p><?= $vitri ?><?= $muc ? ' · ' . e($muc) : '' ?></p>
            </div>
            <button type="button" class="sw-down" data-scroll-more aria-label="Xem thêm thông tin">
                <svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
            </button>
        </div>

        <!-- Phần chi tiết trượt lên khi bấm "Xem thêm" -->
        <div class="sw-detail">
            <button type="button" class="sw-detail-close" data-close-detail>
                <svg viewBox="0 0 24 24"><path d="M6 15l6-6 6 6"/></svg>
                Thu gọn
            </button>
            <div class="sw-detail-body">
            <?php if (!empty($c['bio'])): ?>
                <section>
                    <h4>Giới thiệu</h4>
                    <p class="sw-bio"><?= nl2br(e($c['bio'])) ?></p>
                </section>
            <?php endif; ?>

            <section>
                <h4>Thông tin</h4>
                <ul class="sw-facts">
                    <?php if (!empty($c['province_name'])): ?>
                        <li><?= $ic_pin ?><span><?= e($c['province_name']) ?></span></li>
                    <?php endif; ?>
                    <?php if (!empty($c['height_cm'])): ?>
                        <li><?= $ic_ruler ?><span><?= (int) $c['height_cm'] ?> cm<?= !empty($c['weight_kg']) ? ' · ' . (int) $c['weight_kg'] . ' kg' : '' ?></span></li>
                    <?php endif; ?>
                    <?php if (!empty($c['job'])): ?><li><?= $ic_case ?><span><?= e($c['job']) ?></span></li><?php endif; ?>
                    <?php if (!empty($c['education'])): ?><li><?= $ic_book ?><span><?= e($edu[$c['education']] ?? '') ?></span></li><?php endif; ?>
                    <?php if (!empty($c['marital_status'])): ?><li><?= $ic_heart ?><span><?= e($marital[$c['marital_status']] ?? '') ?></span></li><?php endif; ?>
                    <?php if ($cung): ?><li><?= $ic_star ?><span><?= e($cung) ?></span></li><?php endif; ?>
                    <?php if (!empty($c['smoking'])): ?><li><span class="lbl">Hút thuốc</span><span><?= e($freq[$c['smoking']] ?? '') ?></span></li><?php endif; ?>
                    <?php if (!empty($c['drinking'])): ?><li><span class="lbl">Uống rượu</span><span><?= e($freq[$c['drinking']] ?? '') ?></span></li><?php endif; ?>
                </ul>
            </section>

            <?php if ($sothich): ?>
                <section>
                    <h4>Sở thích</h4>
                    <p class="sw-chips"><?php foreach ($sothich as $t): ?><span><?= e($t) ?></span><?php endforeach; ?></p>
                </section>
            <?php endif; ?>

            <?php if ($anh): ?>
                <section>
                    <h4>Ảnh khác</h4>
                    <div class="sw-album">
                        <?php foreach ($anh as $a): ?>
                            <img src="<?= base_url(ltrim($a, '/')) ?>" alt="" loading="lazy" draggable="false">
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

                <a class="sw-more" href="<?= site_url('profile/' . $c['slug']) ?>">Xem trang cá nhân đầy đủ →</a>
            </div>
        </div>
    </div>
</article>
