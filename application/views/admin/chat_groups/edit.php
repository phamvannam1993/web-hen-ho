<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($error): ?><p class="alert alert-danger"><?= e($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="card">
<input type="hidden" name="chat_csrf" value="<?= e($chat_csrf) ?>">
<p><label>Tên nhóm <input name="name" maxlength="120" required value="<?= e($g['name'] ?? '') ?>"></label></p>
<p><label>Avatar nhóm (PNG/WebP, tối đa 2 MB) <input type="file" name="avatar" accept="image/png,image/webp"></label><?php if (!empty($g['avatar'])): ?><img src="<?= base_url($g['avatar']) ?>" width="48" height="48" alt="Avatar nhóm"><?php endif; ?></p>
<p><label>Slug <input name="slug" maxlength="100" pattern="[a-z0-9]+(-[a-z0-9]+)*" required <?= $g ? 'readonly' : '' ?> value="<?= e($g['slug'] ?? '') ?>"></label></p>
<p><label>Mô tả <textarea name="description" maxlength="2000"><?= e($g['description'] ?? '') ?></textarea></label></p>
<p><label>Nội quy <textarea name="rules" maxlength="4000"><?= e($g['rules'] ?? '') ?></textarea></label></p>
<p><label>Chế độ <select name="identity_mode"><option value="public" <?= ($g['identity_mode'] ?? '') === 'public' ? 'selected' : '' ?>>Công khai – đăng nhập để gửi</option><option value="guest" <?= ($g['identity_mode'] ?? '') === 'guest' ? 'selected' : '' ?>>Cho khách chat ẩn danh – thành viên dùng hồ sơ thật</option></select></label></p>
<p><label>Trạng thái <select name="status"><?php foreach (array('active'=>'Hoạt động','readonly'=>'Chỉ đọc','closed'=>'Đóng') as $v=>$label): ?><option value="<?= $v ?>" <?= ($g['status'] ?? 'active') === $v ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label></p>
<p><label><input type="checkbox" name="allow_images" value="1" <?= !empty($g['allow_images']) ? 'checked' : '' ?>> Cho thành viên gửi ảnh (khách luôn chỉ gửi chữ/emoji)</label></p>
<p><label>Chủ đề <input name="category" maxlength="80" value="<?= e($g['category'] ?? '') ?>"></label></p>
<p><label>ID tỉnh/thành (để trống nếu toàn quốc) <input type="number" min="1" name="province_id" value="<?= e($g['province_id'] ?? '') ?>"></label></p>
<p><label>Ưu tiên <input type="number" name="sort_order" value="<?= (int) ($g['sort_order'] ?? 0) ?>"></label></p>
<p><label>ID tài khoản điều hành, ngăn bằng dấu phẩy <input name="moderators" value="<?= e($moderator_ids) ?>"></label></p>
<p>Chỉ quản trị viên phân công điều hành. Điều hành chỉ thao tác trong nhóm được giao.</p>
<button class="btn" type="submit">Lưu nhóm</button> <a href="<?= site_url('admin/chat_groups') ?>">Quay lại</a>
</form>
