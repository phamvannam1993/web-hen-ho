<?php defined('BASEPATH') OR exit('No direct script access allowed');
$regions = array('bac' => 'Miền Bắc', 'trung' => 'Miền Trung', 'nam' => 'Miền Nam');
$groups = array();
foreach ($list as $p) {
    $groups[$p['region'] ?: 'khac'][] = $p;
}
?>
<div class="container">
    <h1 class="block-title">Thành viên theo khu vực</h1>
    <p class="result-count">Chọn tỉnh/thành để xem những người đang tìm bạn quanh bạn.</p>

    <?php foreach ($groups as $region => $items): ?>
        <section class="area-block">
            <h2 class="area-region"><?= e($regions[$region] ?? 'Khu vực khác') ?></h2>
            <div class="area-grid">
                <?php foreach ($items as $p): ?>
                    <a class="area-item" href="<?= site_url('khu-vuc/' . $p['slug']) ?>">
                        <span class="area-name"><?= e($p['name']) ?></span>
                        <span class="area-count"><?= number_format($p['member_count']) ?> thành viên</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
