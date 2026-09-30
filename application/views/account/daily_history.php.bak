<?php defined('BASEPATH') OR exit('No direct script access allowed');
$nhan = array(
    'pending' => 'Đang chờ bạn trả lời', 'liked' => 'Bạn đã thả tim',
    'skipped' => 'Bạn đã bỏ qua', 'matched' => 'Đã ghép đôi', 'expired' => 'Hết hạn',
);
?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <div class="content-box">
            <h2 class="section-title">Lịch sử gợi ý</h2>
            <p class="section-hint">Mỗi ngày hệ thống chọn một người phù hợp nhất với bạn.</p>

            <?php if (empty($list)): ?>
                <p class="empty">Chưa có gợi ý nào. Quay lại vào 8h sáng mai nhé!</p>
            <?php else: ?>
                <div class="dh-list">
                    <?php foreach ($list as $d): $tuoi = age_from($d['birthday']); ?>
                        <div class="dh-row">
                            <a class="dh-photo" href="<?= site_url('profile/' . $d['slug']) ?>">
                                <img src="<?= avatar_url($d['avatar'], $d['gender']) ?>" alt="" loading="lazy">
                            </a>
                            <div class="dh-info">
                                <b><a href="<?= site_url('profile/' . $d['slug']) ?>">
                                    <?= e(display_name($d)) ?><?= $tuoi ? ', ' . $tuoi : '' ?></a></b>
                                <small><?= date('d/m/Y', strtotime($d['match_date'])) ?></small>
                            </div>
                            <span class="dh-status is-<?= e($d['status']) ?>">
                                <?= e($nhan[$d['status']] ?? $d['status']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Gợi ý mỗi ngày</h3>
            <p>Mỗi 8h sáng hệ thống chọn ra một người hợp tiêu chí nhất với bạn.
                Bạn có 24 giờ để trả lời trước khi gợi ý hết hạn.</p>
            <p>Người bạn đã bỏ qua sẽ không được gợi ý lại.</p>
        </div>
    </aside>
</div>
