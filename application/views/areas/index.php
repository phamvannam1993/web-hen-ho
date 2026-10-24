<?php defined('BASEPATH') OR exit('No direct script access allowed');
$regions = array('bac' => 'Miền Bắc', 'trung' => 'Miền Trung', 'nam' => 'Miền Nam');
$groups = array();
foreach ($list as $p) {
    $groups[$p['region'] ?: 'khac'][] = $p;
}
?>
<div class="container" data-area-directory>
    <h1 class="block-title">Tìm bạn hẹn hò theo khu vực</h1>
    <p class="result-count">Chọn tỉnh/thành để xem những người đang tìm bạn quanh bạn.</p>
    <div class="area-search">
        <label for="area-search">Tìm khu vực</label>
        <input type="search" id="area-search" placeholder="Nhập tên tỉnh/thành, ví dụ: Hà Nội" autocomplete="off" aria-controls="area-results">
    </div>

    <div id="area-results">
    <?php foreach ($groups as $region => $items): ?>
        <section class="area-block">
            <h2 class="area-region"><?= e($regions[$region] ?? 'Khu vực khác') ?></h2>
            <div class="area-grid">
                <?php foreach ($items as $p): ?>
                    <a class="area-item" href="<?= site_url($p['slug']) ?>">
                        <span class="area-name"><?= e($p['name']) ?></span>
                        <span class="area-count"><?= number_format($p['member_count']) ?> thành viên</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    </div>
    <p class="area-empty" role="status" hidden>Không tìm thấy khu vực phù hợp.</p>
</div>
<script>
(function () {
    var directory = document.querySelector('[data-area-directory]');
    var input = directory.querySelector('#area-search');
    var empty = directory.querySelector('.area-empty');
    function normalize(value) {
        return value.toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').trim().replace(/\s+/g, ' ');
    }
    var sections = Array.from(directory.querySelectorAll('.area-block'), function (section) {
        return {element: section, items: Array.from(section.querySelectorAll('.area-item'), function (item) {
            return {element: item, name: normalize(item.querySelector('.area-name').textContent)};
        })};
    });
    input.addEventListener('input', function () {
        var query = normalize(input.value), total = 0;
        sections.forEach(function (section) {
            var count = 0;
            section.items.forEach(function (item) {
                var matches = item.name.indexOf(query) !== -1;
                item.element.hidden = !matches;
                if (matches) count++;
            });
            section.element.hidden = count === 0;
            total += count;
        });
        empty.hidden = total !== 0;
    });
}());
</script>
