<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container page-layout">
    <div>
        <article class="content-box static-page">
            <h1 class="detail-title"><?= e($page['title']) ?></h1>
            <p class="static-meta">Cập nhật lần cuối: <?= date('d/m/Y', strtotime($page['updated_at'])) ?></p>

            <div class="static-content">
                <?= $page['content'] ?>
            </div>
        </article>
    </div>

    <aside>
        <?php if ($other_pages): ?>
            <div class="sidebar-box">
                <h3>Trang khác</h3>
                <ul class="sidebar-list">
                    <?php foreach ($other_pages as $p): ?>
                        <li><a href="<?= site_url('trang/' . $p['slug']) ?>"><?= e($p['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="sidebar-box">
            <h3>Cần hỗ trợ?</h3>
            <p>Hotline: <?= e($settings['hotline'] ?? '') ?></p>
            <p>Email: <?= e($settings['contact_email'] ?? '') ?></p>
            <p><a class="btn btn-primary" href="<?= site_url('kham-pha') ?>">Khám phá &amp; ghép đôi</a></p>
        </div>
    </aside>
</div>
