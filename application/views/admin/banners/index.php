<?php defined('BASEPATH') OR exit('No direct script access allowed');
$positions = array(
    'home_slider' => 'Trang chủ - đầu trang',
    'home_middle' => 'Trang chủ - giữa trang',
    'sidebar'     => 'Cột bên phải',
    'footer'      => 'Chân trang',
);
?>
<div class="filter-form">
    <div><a class="btn btn-blue" href="<?= site_url('admin/banners/edit') ?>">+ Thêm banner</a></div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Danh sách banner</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr><th>Ảnh</th><th>Tiêu đề</th><th>Vị trí</th><th>Liên kết</th>
                <th>Thứ tự</th><th>Hiển thị</th><th>Thao tác</th></tr>
            </thead>
            <tbody>
            <?php if (empty($banners)): ?>
                <tr><td colspan="7">Chưa có banner nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($banners as $b): ?>
                <tr>
                    <td><img class="thumb" style="width:120px;height:60px" src="<?= base_url(ltrim($b['image'], '/')) ?>" alt=""></td>
                    <td><?= e($b['title']) ?></td>
                    <td><?= e($positions[$b['position']] ?? $b['position']) ?></td>
                    <td><?= e(excerpt($b['link'], 40)) ?></td>
                    <td><?= (int) $b['sort'] ?></td>
                    <td><?= $b['is_active']
                            ? '<span class="badge bg-success">Bật</span>'
                            : '<span class="badge bg-secondary">Tắt</span>' ?></td>
                    <td class="actions">
                        <a class="btn btn-light btn-sm" href="<?= site_url('admin/banners/edit/' . $b['id']) ?>">Sửa</a>
                        <a class="btn btn-danger btn-sm" href="<?= site_url('admin/banners/delete/' . $b['id']) ?>"
                           data-confirm="Xoá banner này?" data-confirm-danger>Xoá</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-body">
        <p><b>Lưu ý:</b> trang chủ hiện không hiển thị banner. Nếu muốn bật lại, thêm khối gọi
            <code>$this->m_banner->by_position('home_slider')</code> vào view trang chủ.</p>
    </div>
</div>
