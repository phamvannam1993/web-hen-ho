<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <div class="content-box">
            <h1 class="auth-title">Chào <?= e(display_name($me)) ?>!</h1>

            <div class="account-stats">
                <div><span><?= number_format($me['coin_balance']) ?></span>Xu</div>
                <div><span><?= number_format($post_count) ?></span>Tin đăng</div>
                <div><span><?= number_format(count($liked_me)) ?></span>Người thích bạn</div>
                <div><span><?= number_format(count($matches)) ?></span>Ghép đôi</div>
                <div><span><?= number_format($unread_msg) ?></span>Tin nhắn mới</div>
                <div><span><?= number_format($unread_noti) ?></span>Thông báo</div>
            </div>

            <?php if ((int) $me['profile_score'] < 80): ?>
                <div class="alert alert-warning">
                    Hồ sơ mới hoàn thiện <?= (int) $me['profile_score'] ?>%.
                    <a href="<?= site_url('tai-khoan/ho-so') ?>">Bổ sung ngay</a> để được ưu tiên hiển thị.
                </div>
            <?php endif; ?>

            <div class="auth-actions">
                <a class="btn btn-primary" href="<?= site_url('dang-tin') ?>">Đăng tin hẹn hò</a>
                <a class="btn btn-ghost" href="<?= site_url('tai-khoan/nap-xu') ?>">Nạp xu / mua VIP</a>
            </div>
        </div>

        <div class="content-box">
            <h2 class="section-title">Tin đăng gần đây</h2>
            <?php if (empty($recent_posts)): ?>
                <p class="empty">Bạn chưa đăng tin nào.</p>
            <?php else: ?>
                <table class="simple-table">
                    <tr><th>Tiêu đề</th><th>Trạng thái</th><th>Lượt xem</th><th></th></tr>
                    <?php foreach ($recent_posts as $p): ?>
                        <tr>
                            <td><?= e($p['title']) ?></td>
                            <td><?= status_label($p['status']) ?></td>
                            <td><?= number_format($p['view_count']) ?></td>
                            <td><a href="<?= site_url('tai-khoan/sua-tin/' . $p['id']) ?>">Sửa</a></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($liked_me): ?>
            <div class="content-box">
                <h2 class="section-title">Người vừa quan tâm bạn</h2>
                <div class="member-grid">
                    <?php foreach ($liked_me as $m): ?>
                        <?php $this->load->view('members/_card', array('m' => $m)); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Tài khoản</h3>
            <p>Trạng thái: <?= status_label($me['status']) ?></p>
            <p>VIP: <?= $me['is_vip'] ? 'đến ' . date('d/m/Y', strtotime($me['vip_expired_at'])) : 'Chưa kích hoạt' ?></p>
            <p>Số dư: <b><?= number_format($me['coin_balance']) ?> xu</b></p>
        </div>
    </aside>
</div>
