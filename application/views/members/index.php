<?php defined('BASEPATH') OR exit('No direct script access allowed');
$g  = function ($k, $d = '') { return $this->input->get($k) !== null ? $this->input->get($k) : $d; };
$sel = function ($k, $v) { return $this->input->get($k) == $v && $this->input->get($k) !== null ? 'selected' : ''; };
$chosen_interests = (array) $this->input->get('interests');
$view_mode = $view_mode ?? 'grid';
// Trang một tỉnh (/ha-noi) dùng chung giao diện này, khác ở chỗ khu vực đã cố định
$province = $province ?? null;

// Nhóm nào đang có điều kiện lọc thì mở sẵn, còn lại đóng cho gọn
$open_shape     = $g('height_min') || $g('height_max') || $g('marital');
$open_lifestyle = $g('smoking') || $g('drinking') || $chosen_interests;
$open_advanced  = $g('education') || $g('has_children') !== '' && $g('has_children') !== null
                  || $g('online') || $g('vip');

// Các điều kiện đang áp dụng, hiển thị thành chip phía trên kết quả
$chips = array();
$labels_gender  = array('male' => 'Nam', 'female' => 'Nữ');
$labels_marital = array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp');
$labels_freq    = array('khong' => 'Không', 'thinh_thoang' => 'Thỉnh thoảng', 'thuong_xuyen' => 'Thường xuyên');
if ($g('gender'))   $chips['gender']   = $labels_gender[$g('gender')] ?? '';
if ($g('age_min') || $g('age_max'))
    $chips['age']  = ($g('age_min') ?: 18) . ' – ' . ($g('age_max') ?: 70) . ' tuổi';
if ($g('height_min') || $g('height_max'))
    $chips['height'] = ($g('height_min') ?: 140) . ' – ' . ($g('height_max') ?: 200) . ' cm';
if ($g('province_id') && empty($province)) {
    foreach ($provinces as $p) { if ($p['id'] == $g('province_id')) $chips['province'] = $p['name']; }
}
if ($g('marital'))  $chips['marital']  = $labels_marital[$g('marital')] ?? '';
if ($g('smoking'))  $chips['smoking']  = 'Hút thuốc: ' . ($labels_freq[$g('smoking')] ?? '');
if ($g('drinking')) $chips['drinking'] = 'Uống rượu: ' . ($labels_freq[$g('drinking')] ?? '');
if ($g('online'))   $chips['online']   = 'Đang online';
if ($g('vip'))      $chips['vip']      = 'Thành viên VIP';
if ($chosen_interests) $chips['interests'] = count($chosen_interests) . ' sở thích';

/** Bỏ một điều kiện khỏi đường dẫn hiện tại. */
$without = function ($keys) use ($base_url) {
    $q = $this->input->get();
    foreach ((array) $keys as $k) { unset($q[$k]); }
    return site_url($base_url) . ($q ? '?' . http_build_query($q) : '');
};
/** Đường dẫn giữ nguyên bộ lọc, chỉ đổi một tham số. */
$with = function ($key, $value) use ($base_url) {
    $q = $this->input->get();
    $q[$key] = $value;
    return site_url($base_url) . '?' . http_build_query($q);
};
?>
<div class="container">
    <?php if ($province): ?>
        <nav class="breadcrumb">
            <a href="<?= site_url() ?>">Trang chủ</a> ›
            <a href="<?= site_url('khu-vuc') ?>">Khu vực</a> ›
            <span><?= e($province['name']) ?></span>
        </nav>
    <?php endif; ?>

    <header class="search-head">
        <h1><?= $province ? 'Thành viên tại ' . e($province['name']) : 'Tìm người phù hợp' ?></h1>
        <p><?= $province
            ? 'Những người đang tìm bạn hẹn hò tại ' . e($province['name']) . '. Lọc thêm theo độ tuổi, lối sống và sở thích.'
            : 'Duyệt hồ sơ với bộ lọc theo giới tính, độ tuổi, khu vực, lối sống và nhiều tiêu chí khác.' ?></p>
    </header>

    <div class="search-layout">
        <!-- ------------------------- Bộ lọc ------------------------- -->
        <aside class="filter-panel" id="filter-panel">
            <form method="get" action="<?= site_url($base_url) ?>">
                <?php if ($g('q')): ?><input type="hidden" name="q" value="<?= e($g('q')) ?>"><?php endif; ?>
                <input type="hidden" name="view" value="<?= e($view_mode) ?>">

                <div class="filter-head">
                    <h2>
                        <svg viewBox="0 0 24 24" class="ic"><path d="M3 5h18l-7 8v6l-4 2v-8z"/></svg>
                        Bộ lọc
                    </h2>
                    <a class="filter-clear" href="<?= site_url($base_url) ?>">Xoá bộ lọc</a><?php /* base_url là slug tỉnh nên vẫn ở đúng khu vực */ ?>
                </div>

                <section class="filter-group">
                    <button type="button" class="filter-group-head" data-toggle-group>
                        <span>Cơ bản</span>
                        <svg viewBox="0 0 24 24" class="ic caret"><path d="M6 15l6-6 6 6"/></svg>
                    </button>
                    <div class="filter-group-body">
                        <label class="filter-label">Giới tính</label>
                        <select name="gender">
                            <option value="">Tất cả</option>
                            <option value="male" <?= $sel('gender', 'male') ?>>Nam</option>
                            <option value="female" <?= $sel('gender', 'female') ?>>Nữ</option>
                        </select>

                        <label class="filter-label">Độ tuổi</label>
                        <div class="range-row">
                            <input type="number" name="age_min" min="18" max="80" placeholder="Từ 18" value="<?= e($g('age_min')) ?>">
                            <span>–</span>
                            <input type="number" name="age_max" min="18" max="80" placeholder="Đến 70" value="<?= e($g('age_max')) ?>">
                        </div>

                        <label class="filter-label">Khu vực</label>
                        <?php if ($province): ?>
                            <div class="filter-fixed">
                                <span><?= e($province['name']) ?></span>
                                <a href="<?= site_url('khu-vuc') ?>">Đổi</a>
                            </div>
                        <?php else: ?>
                            <select name="province_id">
                                <option value="">Tất cả tỉnh/thành</option>
                                <?php foreach ($provinces as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>" <?= $sel('province_id', $p['id']) ?>><?= e($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="filter-group">
                    <button type="button" class="filter-group-head <?= $open_shape ? '' : 'is-closed' ?>" data-toggle-group>
                        <span>Ngoại hình</span>
                        <svg viewBox="0 0 24 24" class="ic caret"><path d="M6 15l6-6 6 6"/></svg>
                    </button>
                    <div class="filter-group-body" <?= $open_shape ? '' : 'hidden' ?>>
                        <label class="filter-label">Chiều cao (cm)</label>
                        <div class="range-row">
                            <input type="number" name="height_min" min="130" max="220" placeholder="Từ 140" value="<?= e($g('height_min')) ?>">
                            <span>–</span>
                            <input type="number" name="height_max" min="130" max="220" placeholder="Đến 200" value="<?= e($g('height_max')) ?>">
                        </div>

                        <label class="filter-label">Tình trạng hôn nhân</label>
                        <select name="marital">
                            <option value="">Tất cả</option>
                            <?php foreach ($labels_marital as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $sel('marital', $k) ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </section>

                <section class="filter-group">
                    <button type="button" class="filter-group-head <?= $open_lifestyle ? '' : 'is-closed' ?>" data-toggle-group>
                        <span>Lối sống</span>
                        <svg viewBox="0 0 24 24" class="ic caret"><path d="M6 15l6-6 6 6"/></svg>
                    </button>
                    <div class="filter-group-body" <?= $open_lifestyle ? '' : 'hidden' ?>>
                        <?php foreach (array('smoking' => 'Hút thuốc', 'drinking' => 'Uống rượu') as $field => $label): ?>
                            <label class="filter-label"><?= $label ?></label>
                            <div class="pick-row">
                                <?php foreach ($labels_freq as $k => $v): ?>
                                    <label class="pick <?= $g($field) === $k ? 'on' : '' ?>">
                                        <input type="radio" name="<?= $field ?>" value="<?= $k ?>" <?= $g($field) === $k ? 'checked' : '' ?>>
                                        <?= $v ?>
                                    </label>
                                <?php endforeach; ?>
                                <?php if ($g($field)): ?>
                                    <a class="pick-reset" href="<?= $without($field) ?>">Bỏ chọn</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <label class="filter-label">Sở thích</label>
                        <div class="pick-row">
                            <?php foreach ($interests as $it): ?>
                                <?php $on = in_array((string) $it['id'], array_map('strval', $chosen_interests), true); ?>
                                <label class="pick <?= $on ? 'on' : '' ?>">
                                    <input type="checkbox" name="interests[]" value="<?= (int) $it['id'] ?>" <?= $on ? 'checked' : '' ?>>
                                    <?= e($it['name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <section class="filter-group">
                    <button type="button" class="filter-group-head <?= $open_advanced ? '' : 'is-closed' ?>" data-toggle-group>
                        <span>Nâng cao</span>
                        <svg viewBox="0 0 24 24" class="ic caret"><path d="M6 15l6-6 6 6"/></svg>
                    </button>
                    <div class="filter-group-body" <?= $open_advanced ? '' : 'hidden' ?>>
                        <label class="filter-label">Học vấn</label>
                        <select name="education">
                            <option value="">Tất cả</option>
                            <?php foreach (array('thpt' => 'THPT', 'trung_cap' => 'Trung cấp', 'cao_dang' => 'Cao đẳng',
                                                 'dai_hoc' => 'Đại học', 'sau_dai_hoc' => 'Sau đại học') as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $sel('education', $k) ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label class="filter-label">Con cái</label>
                        <select name="has_children">
                            <option value="">Tất cả</option>
                            <option value="0" <?= $g('has_children') === '0' ? 'selected' : '' ?>>Chưa có con</option>
                            <option value="1" <?= $g('has_children') === '1' ? 'selected' : '' ?>>Đã có con</option>
                        </select>

                        <div class="filter-checks">
                            <label><input type="checkbox" name="online" value="1" <?= $g('online') ? 'checked' : '' ?>> Đang online</label>
                            <label><input type="checkbox" name="vip" value="1" <?= $g('vip') ? 'checked' : '' ?>> Thành viên VIP</label>
                        </div>
                    </div>
                </section>

                <button class="btn-apply" type="submit">Áp dụng bộ lọc</button>
            </form>
        </aside>

        <!-- ------------------------- Kết quả ------------------------- -->
        <div class="search-toolbar">
            <div class="result-top">
                <p class="result-total"><b><?= number_format($total) ?></b> người phù hợp</p>

                <div class="result-tools">
                    <button type="button" class="btn-filter-toggle" id="filter-toggle" aria-expanded="false" aria-controls="filter-panel">
                        <svg viewBox="0 0 24 24" class="ic"><path d="M3 5h18l-7 8v6l-4 2v-8z"/></svg>
                        Bộ lọc
                    </button>
                    <select class="sort-select" onchange="location.href = this.value">
                        <?php foreach (array('active' => 'Hoạt động gần đây', 'new' => 'Mới tham gia', 'vip' => 'Thành viên VIP') as $k => $v): ?>
                            <option value="<?= $with('sort', $k) ?>" <?= $g('sort', 'active') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>

                    <div class="view-switch">
                        <a class="<?= $view_mode === 'grid' ? 'on' : '' ?>" href="<?= $with('view', 'grid') ?>" title="Dạng lưới" aria-label="Dạng lưới">
                            <svg viewBox="0 0 24 24" class="ic"><rect x="3" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5"/></svg>
                        </a>
                        <a class="<?= $view_mode === 'list' ? 'on' : '' ?>" href="<?= $with('view', 'list') ?>" title="Dạng danh sách" aria-label="Dạng danh sách">
                            <svg viewBox="0 0 24 24" class="ic"><path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($chips): ?>
                <div class="chip-row">
                    <?php foreach ($chips as $key => $text): ?>
                        <?php $drop = $key === 'age' ? array('age_min', 'age_max')
                                    : ($key === 'height' ? array('height_min', 'height_max')
                                    : ($key === 'province' ? array('province_id')
                                    : ($key === 'interests' ? array('interests') : array($key)))); ?>
                        <a class="chip" href="<?= $without($drop) ?>"><?= e($text) ?> <span>×</span></a>
                    <?php endforeach; ?>
                    <a class="chip-clear" href="<?= site_url($base_url) ?>">Xoá tất cả</a>
                </div>
            <?php endif; ?>
        </div><!-- /.search-toolbar -->

        <div class="result-panel">
            <?php if (empty($members)): ?>
                <p class="empty">Không tìm thấy thành viên phù hợp<?= !empty($keyword) ? ' với từ khoá "' . e($keyword) . '"' : '' ?>.
                    Thử bỏ bớt điều kiện lọc<?= $province ? ' hoặc <a href="' . site_url('khu-vuc') . '">chọn khu vực khác</a>' : '' ?>
                    hoặc <a href="<?= site_url('thanh-vien') ?>">xem toàn bộ thành viên</a>.</p>
            <?php else: ?>
                <div class="member-grid <?= $view_mode === 'list' ? 'is-list' : '' ?>">
                    <?php foreach ($members as $m): ?>
                        <?php $this->load->view('members/_card', array('m' => $m)); ?>
                    <?php endforeach; ?>
                </div>
                <?= $pagination ?>
            <?php endif; ?>
        </div>
    </div>
</div>
