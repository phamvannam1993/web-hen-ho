<?php defined('BASEPATH') OR exit('No direct script access allowed');
$value = function ($key, $fallback = '') use ($filters) { return $filters[$key] ?? $fallback; };
$chosen = function ($key, $option) use ($value) { return (string) $value($key) === (string) $option ? 'selected' : ''; };
?>
<link rel="stylesheet" href="<?= base_url('assets/site/css/dating-discovery.css') ?>?v=<?= @filemtime(FCPATH.'assets/site/css/dating-discovery.css') ?>">
<section class="dating-discovery">
    <header class="dating-hero">
        <div class="dating-hero-copy">
            <span class="dating-eyebrow">Kết nối gần bạn</span>
            <h1><?= e($heading) ?></h1>
            <p><?= e($tabs[$tab]['desc']) ?></p>
        </div>
        <?php if ($hero_members): ?>
        <div class="dating-portraits" aria-label="Thành viên trong cộng đồng">
            <?php foreach ($hero_members as $person): ?>
            <a href="<?= site_url('profile/' . ($person['slug'] ?: $person['id'])) ?>" aria-label="<?= e(display_name($person)) ?>">
                <img src="<?= e(avatar_url($person['avatar'], $person['gender'])) ?>" alt="<?= e(display_name($person)) ?>" width="100" height="100">
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </header>
    <form class="dating-discovery-form" id="dating-distance-fields" action="<?= site_url($base_url) ?>" method="get">
        <label class="dating-query">
            <span class="dating-sr-only">Tìm theo tên, khu vực hoặc nghề nghiệp</span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg>
            <input type="search" name="q" value="<?= e($value('keyword')) ?>" placeholder="Tìm theo tên, khu vực..." maxlength="100">
        </label>
        <label><span class="dating-sr-only">Giới tính</span>
            <select name="gender"><option value="">Tất cả giới tính</option><option value="male" <?= $chosen('gender', 'male') ?>>Nam</option><option value="female" <?= $chosen('gender', 'female') ?>>Nữ</option></select>
        </label>
        <div class="dating-distance-row">
            <label><span class="dating-sr-only">Khoảng cách</span>
                <select name="distance_max"><option value="0">Mọi khoảng cách</option>
                    <?php foreach (array(10, 25, 50, 100, 200, 500) as $km): ?><option value="<?= $km ?>" <?= $distance_max === $km ? 'selected' : '' ?>><?= $km ?> km</option><?php endforeach; ?>
                </select>
            </label>
            <button type="button" class="dating-locate" id="dating-location" aria-label="Dùng vị trí hiện tại" title="Dùng vị trí hiện tại" data-url="<?= site_url('hen-ho/vi-tri') ?>" data-token="<?= e($location_token) ?>" data-csrf-name="<?= e($this->security->get_csrf_token_name()) ?>" data-csrf-value="<?= e($this->security->get_csrf_hash()) ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
            </button>
        </div>
        <label><span class="dating-sr-only">Tỉnh thành</span>
            <select name="province_id" data-searchable data-allow-empty data-search-placeholder="Tìm tỉnh thành..."><option value="">Tất cả tỉnh thành</option>
                <?php foreach ($provinces as $province): ?><option value="<?= (int) $province['id'] ?>" <?= $chosen('province_id', $province['id']) ?>><?= e($province['name']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label><span class="dating-sr-only">Độ tuổi từ</span><input type="number" name="age_min" min="18" max="80" inputmode="numeric" value="<?= e($value('age_min')) ?>" placeholder="Từ 18 nếu bỏ trống"></label>
        <label><span class="dating-sr-only">Độ tuổi đến</span><input type="number" name="age_max" min="18" max="80" inputmode="numeric" value="<?= e($value('age_max')) ?>" placeholder="Đến 80 nếu bỏ trống"></label>
        <label><span class="dating-sr-only">Tình trạng hôn nhân</span>
            <select name="marital"><option value="">Tất cả tình trạng</option>
                <?php foreach (array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly hôn', 'goa' => 'Góa', 'phuc_tap' => 'Phức tạp') as $key => $label): ?><option value="<?= $key ?>" <?= $chosen('marital', $key) ?>><?= $label ?></option><?php endforeach; ?>
            </select>
        </label>
        <label><span class="dating-sr-only">Sắp xếp</span><select name="sort"><?php foreach ($sorts as $key => $label): ?><option value="<?= $key ?>" <?= $sort === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
        <a class="dating-reset" href="<?= site_url($base_url) ?>">Xóa bộ lọc</a>
        <?php if ($origin_province): ?><input type="hidden" name="origin_province" value="<?= (int) $origin_province ?>"><?php endif; ?>
        <button type="submit" class="dating-submit">Tìm người phù hợp</button>
        <p id="dating-location-status" role="status"><?= !$distance_origin ? 'Bấm biểu tượng vị trí để tìm người gần bạn. Chọn tỉnh thành để ước tính khoảng cách khi chưa chia sẻ vị trí.' : (empty($distance_origin['real']) ? 'Khoảng cách được ước tính theo tỉnh thành.' : 'Đang dùng vị trí bạn đã chia sẻ để tìm người gần bạn.') ?></p>
    </form>
</section>
<div class="dating-results-heading"><p class="result-total"><b><?= number_format($total) ?></b> hồ sơ phù hợp</p>
</div>
<script defer src="<?= base_url('assets/site/js/dating-location.js') ?>?v=<?= @filemtime(FCPATH.'assets/site/js/dating-location.js') ?>"></script>
