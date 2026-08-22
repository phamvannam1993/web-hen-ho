<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <div class="content-box">
            <div class="box-head">
                <h1 class="section-title">Tin đăng của tôi</h1>
                <a class="btn btn-primary" href="<?= site_url('dang-tin') ?>">+ Đăng tin mới</a>
            </div>

            <?php if (empty($posts)): ?>
                <p class="empty">Bạn chưa có tin nào. Hãy đăng tin đầu tiên để bắt đầu kết bạn.</p>
            <?php else: ?>
                <table class="simple-table">
                    <tr><th>Tiêu đề</th><th>Danh mục</th><th>Trạng thái</th><th>Lượt xem</th><th>Hết hạn</th><th></th></tr>
                    <?php foreach ($posts as $p): ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('tin/' . $p['slug']) ?>"><?= e($p['title']) ?></a>
                                <?php if ($p['status'] === 'rejected' && $p['reject_reason']): ?>
                                    <br><small style="color:#c00">Lý do: <?= e($p['reject_reason']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($p['category_name']) ?></td>
                            <td><?= status_label($p['status']) ?></td>
                            <td><?= number_format($p['view_count']) ?></td>
                            <td><?= $p['expired_at'] ? date('d/m/Y', strtotime($p['expired_at'])) : '—' ?></td>
                            <td>
                                <a href="<?= site_url('tai-khoan/sua-tin/' . $p['id']) ?>">Sửa</a> ·
                                <a href="<?= site_url('tai-khoan/xoa-tin/' . $p['id']) ?>"
                                   onclick="return confirm('Xoá tin này?')">Xoá</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Lưu ý</h3>
            <p>Tin cần được ban quản trị duyệt trước khi hiển thị. Tin tự hết hạn sau
                <?= (int) setting('post_expire_days', 30) ?> ngày, bạn có thể sửa để đăng lại.</p>
        </div>
    </aside>
</div>
