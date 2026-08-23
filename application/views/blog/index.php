<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container page-layout">
    <div>
        <h1 class="block-title"><?= e($title) ?></h1>

        <?php if (empty($articles)): ?>
            <p class="empty">Chưa có bài viết nào trong mục này.</p>
        <?php else: ?>
            <div class="article-grid blog-list">
                <?php foreach ($articles as $a): ?>
                    <article class="article-card">
                        <a class="article-thumb" href="<?= site_url('tin-tuc/' . $a['slug']) ?>">
                            <img src="<?= $a['thumbnail'] ? base_url(ltrim($a['thumbnail'], '/')) : base_url('assets/site/img/placeholder.svg') ?>"
                                 alt="<?= e($a['title']) ?>" loading="lazy">
                        </a>
                        <div class="article-body">
                        <h3><a href="<?= site_url('tin-tuc/' . $a['slug']) ?>"><?= e($a['title']) ?></a></h3>
                        <p class="article-meta">
                            <?php if ($a['category_name']): ?><span><?= e($a['category_name']) ?></span><?php endif; ?>
                            <?= time_ago($a['published_at']) ?> · <?= number_format($a['view_count']) ?> lượt xem
                        </p>
                        <p><?= e(excerpt($a['excerpt'] ?: $a['content'], 120)) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?= $pagination ?>
        <?php endif; ?>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Chuyên mục</h3>
            <ul class="sidebar-list">
                <li><a href="<?= site_url('tin-tuc') ?>">Tất cả bài viết</a></li>
                <?php foreach ($blog_cats as $c): ?>
                    <li><a href="<?= site_url('tin-tuc') ?>?chuyen-muc=<?= e($c['slug']) ?>"><?= e($c['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="sidebar-box">
            <h3>Bắt đầu hẹn hò</h3>
            <p>Tạo hồ sơ miễn phí để được gợi ý những người phù hợp với bạn.</p>
            <p><a class="btn btn-primary" href="<?= site_url('kham-pha') ?>">Khám phá ngay</a></p>
        </div>
    </aside>
</div>
