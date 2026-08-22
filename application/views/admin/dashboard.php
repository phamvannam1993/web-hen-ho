<?php defined('BASEPATH') OR exit('No direct script access allowed');
$max = 1;
foreach ($chart as $c) { $max = max($max, $c['users'], $c['posts']); }
?>
<div class="stat-grid">
    <div class="stat-card pink">
        <div class="label">Thành viên</div>
        <div class="value"><?= number_format($user_stats['total']) ?></div>
        <small><?= number_format($user_stats['today']) ?> mới hôm nay · <?= number_format($user_stats['online']) ?> đang online</small>
    </div>
    <div class="stat-card blue">
        <div class="label">Tin đăng</div>
        <div class="value"><?= number_format($post_stats['total']) ?></div>
        <small><?= number_format($post_stats['pending']) ?> chờ duyệt</small>
    </div>
    <div class="stat-card green">
        <div class="label">Doanh thu tháng</div>
        <div class="value"><?= money($revenue['month']) ?></div>
        <small>Hôm nay: <?= money($revenue['today']) ?></small>
    </div>
    <div class="stat-card amber">
        <div class="label">Cần xử lý</div>
        <div class="value"><?= number_format($new_reports + $post_stats['pending'] + $revenue['pending']) ?></div>
        <small><?= (int) $new_reports ?> báo cáo · <?= (int) $revenue['pending'] ?> đơn chờ</small>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Hoạt động 14 ngày gần nhất</h2></div>
    <div class="panel-body">
        <div class="chart">
            <?php foreach ($chart as $c): ?>
                <div class="chart-col">
                    <div class="chart-bars">
                        <div class="chart-bar users" style="height: <?= round($c['users'] / $max * 100) ?>%" title="<?= $c['users'] ?> thành viên"></div>
                        <div class="chart-bar posts" style="height: <?= round($c['posts'] / $max * 100) ?>%" title="<?= $c['posts'] ?> tin"></div>
                    </div>
                    <span class="chart-label"><?= e($c['day']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="chart-legend">
            <span><i style="background:#2563eb"></i>Thành viên mới</span>
            <span><i style="background:#e91e8c"></i>Tin đăng mới</span>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <h2>Tin chờ duyệt</h2>
        <a class="btn btn-light btn-sm" href="<?= site_url('admin/posts?status=pending') ?>">Xem tất cả</a>
    </div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Tiêu đề</th><th>Người đăng</th><th>Danh mục</th><th>Ngày gửi</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php if (empty($pending_posts)): ?>
                <tr><td colspan="5">Không có tin nào chờ duyệt.</td></tr>
            <?php endif; ?>
            <?php foreach ($pending_posts as $p): ?>
                <tr>
                    <td><a href="<?= site_url('admin/posts/edit/' . $p['id']) ?>"><?= e($p['title']) ?></a></td>
                    <td><?= e($p['display_name']) ?></td>
                    <td><?= e($p['category_name']) ?></td>
                    <td><?= time_ago($p['created_at']) ?></td>
                    <td class="actions">
                        <a class="btn btn-success btn-sm" href="<?= site_url('admin/posts/moderate/' . $p['id'] . '/approved') ?>">Duyệt</a>
                        <a class="btn btn-danger btn-sm" href="<?= site_url('admin/posts/moderate/' . $p['id'] . '/rejected') ?>">Từ chối</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <h2>Thành viên mới</h2>
        <a class="btn btn-light btn-sm" href="<?= site_url('admin/users') ?>">Xem tất cả</a>
    </div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Tên</th><th>Email/SĐT</th><th>Giới tính</th><th>Khu vực</th><th>Trạng thái</th><th>Ngày tạo</th></tr></thead>
            <tbody>
            <?php foreach ($recent_users as $u): ?>
                <tr>
                    <td><a href="<?= site_url('admin/users/view/' . $u['id']) ?>"><?= e($u['display_name']) ?></a></td>
                    <td><?= e($u['email'] ?: $u['phone']) ?></td>
                    <td><?= gender_label($u['gender']) ?></td>
                    <td><?= e($u['province_name']) ?></td>
                    <td><?= status_label($u['status']) ?></td>
                    <td><?= time_ago($u['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <h2>Đơn nạp gần đây</h2>
        <a class="btn btn-light btn-sm" href="<?= site_url('admin/orders') ?>">Xem tất cả</a>
    </div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Mã đơn</th><th>Thành viên</th><th>Gói</th><th>Số tiền</th><th>Trạng thái</th><th>Ngày</th></tr></thead>
            <tbody>
            <?php if (empty($recent_orders)): ?>
                <tr><td colspan="6">Chưa có đơn nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($recent_orders as $o): ?>
                <tr>
                    <td><?= e($o['code']) ?></td>
                    <td><?= e($o['display_name']) ?></td>
                    <td><?= e($o['package_name']) ?></td>
                    <td><?= money($o['amount']) ?></td>
                    <td><?= status_label($o['status']) ?></td>
                    <td><?= time_ago($o['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
