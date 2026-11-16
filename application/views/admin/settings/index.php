<?php defined('BASEPATH') OR exit('No direct script access allowed');
$group_labels = array(
    'general' => 'Thông tin chung', 'contact' => 'Liên hệ', 'moderation' => 'Kiểm duyệt',
    'seo' => 'Công cụ tìm kiếm', 'coin' => 'Xu & VIP', 'security' => 'Bảo mật', 'payment' => 'Thanh toán',
    'company' => 'Thông tin công ty', 'social' => 'Mạng xã hội',
);
?>
<form method="post" enctype="multipart/form-data">
    <div class="panel">
        <div class="panel-head"><h2>Banner trang chủ</h2></div>
        <div class="panel-body form-grid">
            <div class="full">
                <p>Banner đang sử dụng</p>
                <img src="<?= e(base_url(($values['home_banner_image'] ?? '') ?: 'assets/images/banner.webp')) ?>"
                     alt="Banner trang chủ đang sử dụng"
                     style="display:block;width:100%;max-width:480px;height:auto;border-radius:8px;margin-bottom:16px">
                <label for="home_banner">Thay ảnh banner</label>
                <input type="file" id="home_banner" name="home_banner" accept="image/jpeg,image/png,image/webp,image/gif">
                <small>JPG, PNG, WEBP hoặc GIF, tối đa 5MB. Để trống để giữ ảnh đang dùng. Chọn ảnh rồi bấm Lưu cấu hình.</small>
            </div>
        </div>
    </div>
    <?php foreach ($fields as $group => $keys): ?>
        <div class="panel">
            <div class="panel-head"><h2><?= e($group_labels[$group] ?? $group) ?></h2></div>
            <div class="panel-body form-grid">
                <?php foreach ($keys as $key => $label): ?>
                    <div class="<?= in_array($key, array('site_desc', 'bank_info'), true) ? 'full' : '' ?>">
                        <label for="<?= $key ?>"><?= e($label) ?></label>
                        <?php if (in_array($key, array('site_desc', 'bank_info'), true)): ?>
                            <textarea id="<?= $key ?>" name="<?= $key ?>"><?= e($values[$key] ?? '') ?></textarea>
                        <?php else: ?>
                            <input type="text" id="<?= $key ?>" name="<?= $key ?>" value="<?= e($values[$key] ?? '') ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="actions">
        <button class="btn btn-primary" type="submit">Lưu cấu hình</button>
        <a class="btn btn-light" href="<?= site_url('admin/settings/logs') ?>">Xem nhật ký quản trị</a>
    </div>
</form>
