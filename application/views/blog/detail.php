<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container page-layout">
    <div>
        <article class="content-box static-page">
            <h1 class="detail-title"><?= e($article['title']) ?></h1>
            <p class="static-meta">
                <?php if ($article['category_name']): ?>
                    <a href="<?= site_url('tin-tuc') ?>?chuyen-muc=<?= e($article['category_slug']) ?>"><?= e($article['category_name']) ?></a> ·
                <?php endif; ?>
                <?= date('d/m/Y', strtotime($article['published_at'])) ?> ·
                <?= number_format($article['view_count']) ?> lượt xem
            </p>

            <?php if ($article['thumbnail']): ?>
                <img src="<?= base_url(ltrim($article['thumbnail'], '/')) ?>" alt="<?= e($article['title']) ?>"
                     style="border-radius:10px;margin-bottom:20px">
            <?php endif; ?>

            <div class="static-content"><?= $article['content'] ?></div>
        </article>

        <?php if ($related): ?>
            <section class="home-block">
                <h2 class="block-title">Bài viết liên quan</h2>
                <div class="article-grid">
                    <?php foreach ($related as $a): ?>
                        <?php if ((int) $a['id'] === (int) $article['id']) continue; ?>
                        <article class="article-card">
                            <a class="article-thumb" href="<?= site_url('tin-tuc/' . $a['slug']) ?>">
                                <img src="<?= $a['thumbnail'] ? base_url(ltrim($a['thumbnail'], '/')) : base_url('assets/site/img/placeholder.svg') ?>"
                                     alt="<?= e($a['title']) ?>" loading="lazy">
                            </a>
                            <div class="article-body">
                                <h3><a href="<?= site_url('tin-tuc/' . $a['slug']) ?>"><?= e($a['title']) ?></a></h3>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
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
    </aside>
</div>
