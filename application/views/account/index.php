<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Trang Tổng quan tài khoản — dựng theo bản thiết kế SaigonCupid.
 *
 * Khác bản cũ ở ba chỗ: thẻ chào có gradient thay cho dòng chữ nghiêng, ô số
 * liệu bấm được và có biểu tượng, thêm hai khối mới là "Hoạt động gần đây" và
 * "Gợi ý phù hợp hôm nay".
 */

/* Phần trăm và danh sách việc còn thiếu lấy từ CÙNG một hàm với trang Hồ sơ,
   để hai trang không bao giờ nói hai con số khác nhau. */
$hs        = tk_ho_so_day_du($me);
$diem      = $hs['phan_tram'];
$con_thieu = $hs['thieu'];

$stats = array(
    array('coins',    number_format($me['coin_balance']),  'Số dư xu',        site_url('tai-khoan/nap-xu')),
    array('heart',    number_format($liked_count),         'Người thích bạn', site_url('tai-khoan/ai-thich-ban')),
    array('match',    number_format(count($matches)),      'Đã ghép đôi',     site_url('tai-khoan/quan-tam')),
    array('message',  number_format($unread_msg),          'Tin nhắn mới',    site_url('tai-khoan/tin-nhan')),
    array('eye',      number_format($viewer_count),        'Lượt xem hồ sơ',  site_url('tai-khoan/ai-xem-ho-so')),
    array('file',     number_format($post_count),          'Tin đăng',        !empty($settings['enable_posts']) ? site_url('tai-khoan/tin-dang') : null),
    array('bell',     number_format($unread_noti),         'Thông báo',       site_url('tai-khoan/thong-bao')),
    /* `M_streak::cham_cong()` trả khoá `streak`, không phải `current` */
    array('flame',    (int) ($streak['streak'] ?? 0) . ' ngày', 'Chuỗi đăng nhập', site_url('tai-khoan/chuoi')),
);
?>
<div class="container tk-shell">
    <?php $this->load->view('account/_nav'); ?>

    <div>
        <!-- Thẻ chào -->
        <section class="tk-hero">
            <img class="tk-hero__av" src="<?= e(avatar_url($me['avatar'] ?? null, $me['gender'] ?? 'other')) ?>"
                 alt="" width="74" height="74">
            <div class="tk-hero__b">
                <p class="tk-hero__hi">Chào bạn,</p>
                <p class="tk-hero__name"><?= e(display_name($me)) ?></p>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <?php if (!empty($me['is_vip']) && !empty($me['vip_expired_at'])): ?>
                        <span class="tk-pill"><?= tk_icon('crown') ?>VIP đến <?= date('d/m/Y', strtotime($me['vip_expired_at'])) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($streak['streak'])): ?>
                        <span class="tk-pill"><?= tk_icon('flame') ?>Chuỗi <?= (int) $streak['streak'] ?> ngày</span>
                    <?php endif; ?>
                </div>
                <div class="tk-hero__acts">
                    <?php if (!empty($settings['enable_posts'])): ?>
                        <a class="tk-btn tk-btn--gold" href="<?= site_url('dang-tin') ?>"><?= tk_icon('file') ?>Đăng tin hẹn hò</a>
                    <?php else: ?>
                        <a class="tk-btn tk-btn--gold" href="<?= site_url('swipe-match') ?>"><?= tk_icon('sparkles') ?>Khám phá &amp; ghép đôi</a>
                    <?php endif; ?>
                    <a class="tk-btn tk-btn--ghost" href="<?= site_url('tai-khoan/nap-xu') ?>"><?= tk_icon('wallet') ?>Nạp xu / VIP</a>
                </div>
            </div>
        </section>

        <!-- Ô số liệu -->
        <div class="tk-stats">
            <?php foreach ($stats as $s): ?>
                <?php $tag = $s[3] ? 'a' : 'div'; ?>
                <<?= $tag ?> class="tk-stat" <?= $s[3] ? 'href="' . $s[3] . '"' : '' ?>>
                    <span class="tk-stat__ic"><?= tk_icon($s[0]) ?></span>
                    <span>
                        <span class="tk-stat__n"><?= e($s[1]) ?></span><br>
                        <span class="tk-stat__l"><?= e($s[2]) ?></span>
                    </span>
                </<?= $tag ?>>
            <?php endforeach; ?>
        </div>

        <div class="tk-two">
            <!-- Hoàn thiện hồ sơ -->
            <section class="tk-card">
                <div class="tk-card__h">
                    <div>
                        <h2 class="tk-card__t">Hoàn thiện hồ sơ</h2>
                        <p class="tk-card__d">Hồ sơ đầy đủ được ưu tiên hiển thị và nhận nhiều lượt thích hơn</p>
                    </div>
                </div>
                <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:10px">
                    <span class="tk-pc__n"><?= $diem ?>%</span>
                    <span class="tk-card__d">
                        <?= $con_thieu ? 'Còn ' . count($con_thieu) . ' việc cần làm' : 'Đã đầy đủ' ?>
                    </span>
                </div>
                <div class="tk-pc__bar"><div class="tk-pc__fill" style="width:<?= max(2, min(100, $diem)) ?>%"></div></div>

                <?php if ($con_thieu): ?>
                    <?php foreach (array_slice($con_thieu, 0, 4) as $nhan): ?>
                        <div class="tk-pc__row"><span><?= e($nhan) ?></span><span class="tk-pc__miss">Chưa có</span></div>
                    <?php endforeach; ?>
                    <a class="tk-btn tk-btn--brand" style="margin-top:14px" href="<?= site_url('tai-khoan/ho-so') ?>">
                        Bổ sung ngay <?= tk_icon('arrow') ?>
                    </a>
                <?php else: ?>
                    <div class="tk-pc__row"><span>Mọi mục đã khai đủ</span><span class="tk-pc__done"><?= tk_icon('check') ?></span></div>
                <?php endif; ?>
            </section>

            <!-- Hoạt động gần đây -->
            <section class="tk-card">
                <div class="tk-card__h">
                    <div>
                        <h2 class="tk-card__t">Hoạt động gần đây</h2>
                        <p class="tk-card__d">Những gì vừa diễn ra với hồ sơ của bạn</p>
                    </div>
                    <a class="tk-card__d" href="<?= site_url('tai-khoan/thong-bao') ?>">Xem tất cả</a>
                </div>
                <?php if (empty($hoat_dong)): ?>
                    <div class="tk-empty">
                        <div class="tk-empty__ic"><?= tk_icon('inbox') ?></div>
                        Chưa có hoạt động nào. Hãy thích vài hồ sơ để bắt đầu.
                    </div>
                <?php else: ?>
                    <ul class="tk-feed">
                        <?php foreach ($hoat_dong as $n): ?>
                            <li>
                                <span class="tk-feed__ic"><?= tk_icon('bell') ?></span>
                                <div style="min-width:0">
                                    <p class="tk-feed__tx"><?= e($n['title']) ?></p>
                                    <p class="tk-feed__ti"><?= e(time_ago($n["created_at"])) ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>

        <!-- Gợi ý -->
        <section class="tk-card" style="margin-top:18px">
            <div class="tk-card__h">
                <div>
                    <h2 class="tk-card__t">Gợi ý phù hợp hôm nay</h2>
                    <p class="tk-card__d">Chọn theo tiêu chí tìm kiếm bạn đã khai trong hồ sơ</p>
                </div>
                <a class="tk-btn tk-btn--soft" href="<?= site_url('swipe-match') ?>"><?= tk_icon('sparkles') ?>Xem gợi ý</a>
            </div>
            <?php if (empty($goi_y_them)): ?>
                <div class="tk-empty">
                    <div class="tk-empty__ic"><?= tk_icon('sparkles') ?></div>
                    Chưa có gợi ý nào. Khai đủ tiêu chí tìm kiếm để hệ thống tìm người phù hợp.
                </div>
            <?php else: ?>
                <div class="tk-sug">
                    <?php foreach ($goi_y_them as $g): ?>
                        <?php
                        $online = !empty($g['last_active_at']) && strtotime($g['last_active_at']) > time() - 300;
                        $tuoi   = !empty($g['birthday']) ? (int) date_diff(date_create($g['birthday']), date_create('today'))->y : null;
                        ?>
                        <article class="tk-sug__c">
                            <a class="tk-sug__ph" href="<?= site_url('thanh-vien/' . $g['slug']) ?>">
                                <img src="<?= e(avatar_url($g['avatar'] ?? null, $g['gender'] ?? 'other')) ?>"
                                     alt="<?= e(display_name($g)) ?>" loading="lazy">
                                <?php if ($online): ?><span class="tk-sug__on">● Đang online</span><?php endif; ?>
                            </a>
                            <div class="tk-sug__b">
                                <p class="tk-sug__n"><?= e(display_name($g)) ?><?= $tuoi ? ', ' . $tuoi : '' ?></p>
                                <?php if (!empty($g['province_name'])): ?>
                                    <p class="tk-sug__m"><?= tk_icon('pin') ?><?= e($g['province_name']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($g['job'])): ?>
                                    <p class="tk-sug__m"><?= tk_icon('briefcase') ?><?= e($g['job']) ?></p>
                                <?php endif; ?>
                                <div class="tk-sug__act">
                                    <a class="tk-btn tk-btn--brand" href="<?= site_url('thanh-vien/' . $g['slug']) ?>">
                                        <?= tk_icon('heart') ?>Xem hồ sơ
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
