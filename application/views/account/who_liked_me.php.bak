<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $list @var int $tong @var bool $la_vip */
// Người thường thấy rõ 2 người đầu để tin là thật, còn lại làm mờ
$so_ro = $la_vip ? count($list) : 2;
?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <div class="content-box">
            <h2 class="section-title"><?= number_format($tong) ?> người đã thích bạn</h2>

            <?php if (empty($list)): ?>
                <p class="empty">Chưa có ai thích bạn. Hãy hoàn thiện hồ sơ để tăng cơ hội!</p>
            <?php else: ?>
                <p class="section-hint">
                    <?php if ($la_vip): ?>
                        Thả tim lại là ghép đôi ngay và mở khung trò chuyện.
                    <?php else: ?>
                        Bạn đang xem bản thường nên chỉ thấy rõ <?= min(2, count($list)) ?> người đầu.
                        <a href="<?= site_url('tai-khoan/nap-xu') ?>">Nâng cấp VIP</a> để xem hết và thả tim lại.
                    <?php endif; ?>
                </p>

                <div class="liker-grid">
                    <?php foreach ($list as $i => $m): $ro = $i < $so_ro; $tuoi = age_from($m['birthday']); ?>
                        <div class="liker <?= $ro ? '' : 'is-blur' ?>" data-user="<?= (int) $m['id'] ?>">
                            <?php if ($ro): ?>
                                <a class="liker-photo" href="<?= site_url('profile/' . $m['slug']) ?>">
                                    <img src="<?= avatar_url($m['avatar'], $m['gender']) ?>"
                                         alt="<?= e(display_name($m)) ?>" loading="lazy">
                                </a>
                                <b><?= e(display_name($m)) ?><?= $tuoi ? ', ' . $tuoi : '' ?></b>
                                <small><?= !empty($m['province_name']) ? e($m['province_name']) : 'Chưa rõ khu vực' ?></small>
                                <div class="liker-actions">
                                    <button type="button" class="btn-hm btn-hm-line"
                                            data-like-reply="skip" data-user="<?= (int) $m['id'] ?>">Bỏ qua</button>
                                    <button type="button" class="btn-hm btn-hm-solid"
                                            data-like-reply="accept" data-user="<?= (int) $m['id'] ?>">Thả tim</button>
                                </div>
                            <?php else: ?>
                                <span class="liker-photo">
                                    <img src="<?= avatar_url($m['avatar'], $m['gender']) ?>" alt="" loading="lazy">
                                </span>
                                <b class="liker-hidden">Thành viên</b>
                                <small>Nâng cấp để xem</small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!$la_vip && count($list) > $so_ro): ?>
                    <div class="hm-center">
                        <a class="btn-hm btn-hm-solid" href="<?= site_url('tai-khoan/nap-xu') ?>">
                            Nâng cấp để xem <?= number_format($tong - $so_ro) ?> người còn lại
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Vì sao nên thả tim lại?</h3>
            <p>Những người này đã bày tỏ quan tâm trước. Chỉ cần bạn thả tim lại là
                ghép đôi ngay lập tức và mở được khung trò chuyện.</p>
        </div>
    </aside>
</div>
