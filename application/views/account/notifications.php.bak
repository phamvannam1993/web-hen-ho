<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <div class="content-box">
            <h1 class="section-title">Thông báo</h1>
            <?php if (empty($notifications)): ?>
                <p class="empty">Chưa có thông báo nào.</p>
            <?php else: ?>
                <ul class="noti-list">
                    <?php foreach ($notifications as $n): ?>
                        <li class="<?= $n['read_at'] ? '' : 'unread' ?>">
                            <b><?= e($n['title']) ?></b>
                            <p><?= e($n['body']) ?></p>
                            <small><?= time_ago($n['created_at']) ?></small>
                            <?php if ($n['url']): ?>
                                <a href="<?= e($n['url']) ?>">Xem chi tiết</a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <aside></aside>
</div>
