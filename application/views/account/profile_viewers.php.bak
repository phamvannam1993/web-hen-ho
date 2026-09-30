<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $list @var int $tong @var bool $la_vip */
$so_ro = $la_vip ? count($list) : 2;
?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <div class="content-box">
            <h2 class="section-title"><?= number_format($tong) ?> người đã xem hồ sơ bạn</h2>

            <?php if (empty($list)): ?>
                <p class="empty">Chưa có ai xem hồ sơ bạn tuần này.</p>
            <?php else: ?>
                <p class="section-hint">
                    Tính trong 7 ngày gần nhất.
                    <?php if (!$la_vip): ?>
                        Bản thường chỉ thấy rõ <?= min(2, count($list)) ?> người đầu —
                        <a href="<?= site_url('tai-khoan/nap-xu') ?>">nâng cấp VIP</a> để xem hết.
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
                                <small><?= e(time_ago($m['viewed_at'])) ?></small>
                                <div class="liker-actions">
                                    <button type="button" class="btn-hm btn-hm-solid"
                                            data-card-action="like" data-like-label="Thả tim">
                                        <span class="js-like-text">Thả tim</span>
                                    </button>
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
            <?php endif; ?>
        </div>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Ai đang để ý bạn?</h3>
            <p>Người đã ghé xem hồ sơ thường là người đang cân nhắc. Thả tim trước
                có thể là cú hích để họ đáp lại.</p>
        </div>
    </aside>
</div>
