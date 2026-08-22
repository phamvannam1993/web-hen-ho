<?php defined('BASEPATH') OR exit('No direct script access allowed');
$blocks = array(
    'Đã ghép đôi với bạn' => $matches,
    'Người thích bạn'     => $liked_me,
    'Bạn đã thích'        => $my_likes,
);
?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <?php foreach ($blocks as $label => $list): ?>
            <div class="content-box">
                <h2 class="section-title"><?= e($label) ?> (<?= count($list) ?>)</h2>
                <?php if (empty($list)): ?>
                    <p class="empty">Chưa có ai trong danh sách này.</p>
                <?php else: ?>
                    <div class="member-grid">
                        <?php foreach ($list as $m): ?>
                            <?php $this->load->view('members/_card', array('m' => $m)); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Ghép đôi hoạt động thế nào?</h3>
            <p>Khi hai người cùng bấm thích nhau, hệ thống tạo ghép đôi và mở khung chat giữa hai bạn.</p>
        </div>
    </aside>
</div>
