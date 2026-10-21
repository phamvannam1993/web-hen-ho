<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="panel">
    <div class="panel-head"><h2>Cấp VIP hàng loạt</h2></div>
    <form class="panel-body form-grid" method="post" action="<?= site_url('admin/users/grant_vip_all') ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="full">
            <p>Áp dụng cho toàn bộ <strong><?= number_format($member_count) ?> thành viên</strong> chưa bị xóa, không phụ thuộc bộ lọc danh sách.</p>
            <p>VIP còn hạn được cộng tiếp số ngày đã chọn. VIP đã hết hạn tính từ hôm nay. VIP vĩnh viễn giữ nguyên. Mỗi người được cấp VIP sẽ nhận thông báo.</p>
        </div>
        <div>
            <label for="bulk-vip-days">Số ngày VIP</label>
            <input id="bulk-vip-days" type="number" name="days" min="1" max="3650" step="1" value="30" list="bulk-vip-presets" required>
            <datalist id="bulk-vip-presets"><option value="30"><option value="60"><option value="90"><option value="180"><option value="365"></datalist>
            <p>Chọn 30, 60, 90, 180, 365 ngày hoặc nhập số ngày tùy chỉnh.</p>
        </div>
        <div class="full"><label><input type="checkbox" name="confirmed" value="1" required> Tôi xác nhận cấp thêm VIP cho tất cả thành viên.</label></div>
        <div class="full"><button class="btn btn-primary" type="submit">Xác nhận cấp VIP</button> <a class="btn btn-light" href="<?= site_url('admin/users') ?>">Hủy</a></div>
    </form>
</div>
