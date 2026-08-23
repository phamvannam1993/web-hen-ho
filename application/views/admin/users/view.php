<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="panel">
    <div class="panel-head">
        <h2><?= e($u['display_name']) ?> <?= status_label($u['status']) ?></h2>
        <div class="actions">
            <a class="btn btn-light btn-sm" href="<?= site_url('admin/users/edit/' . $u['id']) ?>">Sửa hồ sơ</a>
            <a class="btn btn-light btn-sm" href="<?= site_url('profile/' . $u['slug']) ?>" target="_blank">Xem trang cá nhân</a>
            <a class="btn btn-danger btn-sm" href="<?= site_url('admin/users/delete/' . $u['id']) ?>"
               data-confirm="Xoá thành viên này?" data-confirm-danger>Xoá</a>
        </div>
    </div>
    <div class="panel-body form-grid">
        <div><b>Email:</b> <?= e($u['email']) ?></div>
        <div><b>Điện thoại:</b> <?= e($u['phone']) ?></div>
        <div><b>Giới tính:</b> <?= gender_label($u['gender']) ?></div>
        <div><b>Tuổi:</b> <?= age_from($u['birthday']) ?: '—' ?></div>
        <div><b>Khu vực:</b> <?= e($u['province_name']) ?></div>
        <div><b>Nghề nghiệp:</b> <?= e($u['job']) ?></div>
        <div><b>Số dư xu:</b> <?= number_format($u['coin_balance']) ?></div>
        <div><b>VIP:</b> <?= $u['is_vip'] ? 'đến ' . date('d/m/Y', strtotime($u['vip_expired_at'])) : 'Không' ?></div>
        <div><b>Hoàn thiện hồ sơ:</b> <?= (int) $u['profile_score'] ?>%</div>
        <div><b>Hoạt động cuối:</b> <?= time_ago($u['last_active_at']) ?></div>
        <div class="full"><b>Giới thiệu:</b> <?= nl2br(e($u['bio'])) ?></div>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Thao tác nhanh</h2></div>
    <div class="panel-body form-grid">
        <form method="post" action="<?= site_url('admin/users/adjust_coin/' . $u['id']) ?>">
            <label>Cộng / trừ xu (số âm để trừ)</label>
            <input type="number" name="amount" value="0" required>
            <label>Ghi chú</label>
            <input type="text" name="note" placeholder="Lý do điều chỉnh">
            <button class="btn btn-blue" type="submit" style="margin-top:12px">Cập nhật xu</button>
        </form>
        <form method="post" action="<?= site_url('admin/users/grant_vip/' . $u['id']) ?>">
            <label>Cấp VIP (số ngày)</label>
            <input type="number" name="days" value="30" min="1" required>
            <button class="btn btn-primary" type="submit" style="margin-top:12px">Cấp VIP</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Tin đăng gần đây</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Tiêu đề</th><th>Trạng thái</th><th>Lượt xem</th><th>Ngày tạo</th></tr></thead>
            <tbody>
            <?php if (empty($posts)): ?><tr><td colspan="4">Chưa có tin nào.</td></tr><?php endif; ?>
            <?php foreach ($posts as $p): ?>
                <tr>
                    <td><a href="<?= site_url('admin/posts/edit/' . $p['id']) ?>"><?= e($p['title']) ?></a></td>
                    <td><?= status_label($p['status']) ?></td>
                    <td><?= number_format($p['view_count']) ?></td>
                    <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Lịch sử xu</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Thời gian</th><th>Biến động</th><th>Số dư sau</th><th>Lý do</th><th>Ghi chú</th></tr></thead>
            <tbody>
            <?php if (empty($coins)): ?><tr><td colspan="5">Chưa có giao dịch.</td></tr><?php endif; ?>
            <?php foreach ($coins as $c): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                    <td style="color:<?= $c['amount'] >= 0 ? '#16a34a' : '#dc2626' ?>">
                        <?= $c['amount'] > 0 ? '+' : '' ?><?= number_format($c['amount']) ?>
                    </td>
                    <td><?= number_format($c['balance_after']) ?></td>
                    <td><?= e($c['reason']) ?></td>
                    <td><?= e($c['note']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
