<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Quan tâm & ghép đôi.
 *
 * Chỉ khi hai bên cùng thích nhau mới thành ghép đôi và mở được khung chat,
 * nên khối "Người thích bạn" có hai nút quyết định: Thích lại hoặc Bỏ qua.
 */
?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <div class="content-box">
            <h2 class="section-title">Người thích bạn (<?= count($liked_me) ?>)</h2>
            <?php if (empty($liked_me)): ?>
                <p class="empty">Chưa có ai đang chờ bạn trả lời.</p>
            <?php else: ?>
                <p class="section-hint">Thích lại để ghép đôi và mở khung trò chuyện. Nếu bỏ qua,
                    người kia sẽ không được báo gì cả.</p>
                <div class="member-grid">
                    <?php foreach ($liked_me as $m): ?>
                        <div class="like-request" data-like-request="<?= (int) $m['id'] ?>">
                            <?php $this->load->view('members/_card', array('m' => $m, 'hide_actions' => true)); ?>
                            <div class="like-request-actions">
                                <button type="button" class="btn btn-ghost"
                                        data-like-reply="skip" data-user="<?= (int) $m['id'] ?>">Bỏ qua</button>
                                <button type="button" class="btn btn-primary"
                                        data-like-reply="accept" data-user="<?= (int) $m['id'] ?>">Thích lại</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="content-box">
            <h2 class="section-title">Đã ghép đôi với bạn (<?= count($matches) ?>)</h2>
            <?php if (empty($matches)): ?>
                <p class="empty">Chưa có ghép đôi nào.</p>
            <?php else: ?>
                <div class="member-grid">
                    <?php foreach ($matches as $m): ?>
                        <?php $this->load->view('members/_card', array('m' => $m)); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="content-box">
            <h2 class="section-title">Bạn đã thích (<?= count($my_likes) ?>)</h2>
            <?php if (empty($my_likes)): ?>
                <p class="empty">Bạn chưa thích ai.</p>
            <?php else: ?>
                <p class="section-hint">Đang chờ người ấy thích lại. Khi cả hai cùng thích, chat sẽ mở ra.</p>
                <div class="member-grid">
                    <?php foreach ($my_likes as $m): ?>
                        <?php $this->load->view('members/_card', array('m' => $m)); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Ghép đôi hoạt động thế nào?</h3>
            <p>Bấm <strong>Thích</strong> là gửi lời quan tâm một chiều — chưa nhắn tin được.
                Người nhận sẽ thấy bạn trong mục “Người thích bạn” và có thể thích lại hoặc bỏ qua.</p>
            <p>Chỉ khi <strong>cả hai cùng thích nhau</strong>, hệ thống mới tạo ghép đôi
                và mở khung chat giữa hai bạn.</p>
        </div>
    </aside>
</div>
