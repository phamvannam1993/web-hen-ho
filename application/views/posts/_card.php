<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $post */
$img = $post['cover'] ? base_url(ltrim($post['cover'], '/')) : base_url('assets/site/img/placeholder.svg');
$place = trim(($post['district'] ? $post['district'] . ', ' : '') . ($post['province_name'] ?? ''), ', ');
$marital = array(
    'doc_than' => 'Độc thân', 'ly_hon' => 'Ly dị', 'goa' => 'Goá',
    'dang_co_nguoi_yeu' => 'Đang có người yêu', 'phuc_tap' => 'Phức tạp',
);
?>
<article class="post-card">
    <a class="post-thumb" href="<?= site_url('tin/' . $post['slug']) ?>">
        <img src="<?= $img ?>" alt="<?= e($post['title']) ?>" loading="lazy">
        <?php if (!empty($post['is_verified'])): ?>
            <span class="badge-verified">Kiểm định</span>
        <?php endif; ?>
        <?php if (!empty($post['is_featured'])): ?>
            <span class="badge-featured">Nổi bật</span>
        <?php endif; ?>
        <time class="post-date"><?= date('d/m/Y', strtotime($post['published_at'] ?: $post['created_at'])) ?></time>
    </a>

    <div class="post-body">
        <h3 class="post-title">
            <?php if ($place): ?><span class="place-tag"><?= e($place) ?></span><?php endif; ?>
            <a href="<?= site_url('tin/' . $post['slug']) ?>"><?= e($post['title']) ?></a>
        </h3>
        <p class="post-meta">
            <span class="heart">♥</span>
            <?php if ($post['height_cm']): ?>Cao <?= (int) $post['height_cm'] ?> cm <?php endif; ?>
            <?php if ($post['weight_kg']): ?>Nặng <?= (int) $post['weight_kg'] ?> kg <?php endif; ?>
            <?php if ($post['marital_status']): ?><?= e($marital[$post['marital_status']] ?? '') ?><?php endif; ?>
        </p>
    </div>
</article>
