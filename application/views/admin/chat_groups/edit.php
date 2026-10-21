<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($error): ?><p class="alert alert-danger"><?= e($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="admin-group-editor">
<input type="hidden" name="chat_csrf" value="<?= e($chat_csrf) ?>">
<section class="age-section"><header class="age-heading"><h2>Thông tin nhóm</h2><p>Tên, hình đại diện và nội dung giới thiệu hiển thị với thành viên.</p></header><div class="age-fields">
<p><label>Tên nhóm <input name="name" maxlength="120" required value="<?= e($g['name'] ?? '') ?>"></label></p>
<p><label>Avatar nhóm (PNG/WebP, tối đa 2 MB) <input type="file" name="avatar" accept="image/png,image/webp"></label><?php if (!empty($g['avatar'])): ?><img src="<?= base_url($g['avatar']) ?>" width="48" height="48" alt="Avatar nhóm"><?php endif; ?></p>
<p><label>Slug <input name="slug" maxlength="100" pattern="[a-z0-9]+(-[a-z0-9]+)*" required value="<?= e($this->input->method() === 'post' ? $this->input->post('slug') : ($g['slug'] ?? '')) ?>"></label><small>Chỉ dùng chữ thường không dấu, số và gạch ngang. Đổi slug sẽ đổi đường dẫn nhóm; đường dẫn cũ sẽ không còn hoạt động.</small></p>
<p><label>Mô tả <textarea name="description" maxlength="2000"><?= e($g['description'] ?? '') ?></textarea></label></p>
<p><label>Nội quy <textarea name="rules" maxlength="4000"><?= e($g['rules'] ?? '') ?></textarea></label></p>
</div></section>
<section class="age-section"><header class="age-heading"><h2>Cấu hình trò chuyện</h2><p>Quản lý quyền tham gia, trạng thái và cách khám phá nhóm.</p></header><div class="age-fields">
<p><label>Chế độ <select name="identity_mode"><option value="public" <?= ($g['identity_mode'] ?? '') === 'public' ? 'selected' : '' ?>>Công khai – đăng nhập để gửi</option><option value="guest" <?= ($g['identity_mode'] ?? '') === 'guest' ? 'selected' : '' ?>>Ẩn danh – sử dụng tên giả</option></select></label><small>Có thể chuyển chế độ bất cứ lúc nào, kể cả nhóm gốc đã có tin nhắn. Lịch sử được giữ lại và hiển thị theo chế độ hiện tại; khi chuyển sang công khai, tin của thành viên sẽ hiện tên và hồ sơ thật.</small></p>
<p><label>Trạng thái <select name="status"><?php foreach (array('active'=>'Hoạt động','readonly'=>'Chỉ đọc','closed'=>'Đóng') as $v=>$label): ?><option value="<?= $v ?>" <?= ($g['status'] ?? 'active') === $v ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label></p>
<p><label><input type="checkbox" name="allow_images" value="1" <?= !empty($g['allow_images']) ? 'checked' : '' ?>> Cho thành viên gửi ảnh (khách luôn chỉ gửi chữ/emoji)</label></p>
<p><label>Chủ đề <input name="category" maxlength="80" value="<?= e($g['category'] ?? '') ?>"></label></p>
<p><label>ID tỉnh/thành (để trống nếu toàn quốc) <input type="number" min="1" name="province_id" value="<?= e($g['province_id'] ?? '') ?>"></label></p>
<p><label>Ưu tiên <input type="number" name="sort_order" value="<?= (int) ($g['sort_order'] ?? 0) ?>"></label></p>
</div></section>
<section class="age-section"><header class="age-heading"><h2>Người điều hành</h2><p>Chỉ quản trị viên phân công điều hành. Điều hành chỉ thao tác trong nhóm được giao.</p></header><div class="age-fields">
<p class="age-full"><label>ID tài khoản điều hành <input name="moderators" placeholder="Ví dụ: 12, 25, 38" value="<?= e($moderator_ids) ?>"></label><small>Nhập ID tài khoản admin hoặc moderator, ngăn cách bằng dấu phẩy.</small></p>
</div></section>
<div class="age-actions"><a class="btn btn-light" href="<?= site_url('admin/chat_groups') ?>">Quay lại</a><button class="btn btn-primary" type="submit">Lưu nhóm</button></div>
</form>
