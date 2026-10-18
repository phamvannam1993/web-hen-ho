<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="cg-chat" data-group-chat data-group-id="<?= (int) $group['id'] ?>" data-api="<?= site_url('ajax/nhom/' . $group['id']) ?>" data-csrf="<?= e($chat_csrf) ?>" data-guest="<?= $this->auth->check() ? '0' : '1' ?>" data-can-send="<?= $group['status'] === 'active' && ($this->auth->check() || $group['identity_mode'] === 'guest') ? '1' : '0' ?>">
<header class="cg-header"><div class="cg-header-who"><a class="cg-groups-back" href="<?= site_url('clubs') ?>" aria-label="Về danh sách nhóm" title="Về danh sách nhóm"><?= tk_icon('chevron-left', 'cg-icon') ?></a><span class="cg-room-icon" aria-hidden="true"><?= tk_icon($group['identity_mode'] === 'guest' ? 'eye-off' : 'message', 'cg-icon') ?></span><div><strong><?= e($group['name']) ?></strong><button type="button" class="cg-online-trigger" data-cg-online aria-haspopup="dialog">Đang tải…</button></div></div><div class="cg-header-more"><button type="button" class="cg-group-menu-toggle" data-cg-group-menu-toggle aria-label="Tùy chọn nhóm" aria-haspopup="true" aria-expanded="false"><span aria-hidden="true">⋯</span></button><div class="cg-group-menu" data-cg-group-menu popover="auto"><button type="button" data-cg-notifications>Tắt thông báo</button><button type="button" data-cg-share>Chia sẻ</button><button type="button" data-cg-rules-open>Nội quy</button></div></div></header>
<dialog class="cg-info-dialog" data-cg-online-dialog aria-label="Người đang online"><h3>Người đang online</h3><div class="cg-online-people" data-cg-people></div><button type="button" data-cg-close-info>Đóng</button></dialog>
<dialog class="cg-info-dialog" data-cg-rules-dialog aria-label="Nội quy nhóm"><h3>Nội quy nhóm</h3><p class="cg-rules-text"><?= nl2br(e($group['rules'])) ?></p><button type="button" data-cg-close-info>Đóng</button></dialog>
<?php if ($group['identity_mode'] === 'guest'): ?><p class="cg-notice">Mọi người đều dùng tên giả và avatar ẩn danh trong nhóm này. Thành viên đăng nhập được chọn tên giả. Nội dung tự chia sẻ có thể tiết lộ danh tính.</p><?php endif; ?>
<div class="cg-pin" data-cg-pin hidden></div>
<p class="cg-loading" data-cg-loading role="status" aria-live="polite" hidden>Đang tải tin nhắn…</p>
<div class="cg-feed" data-cg-feed role="log" aria-label="Tin nhắn nhóm" tabindex="0"></div>
<button type="button" class="cg-jump" data-cg-jump hidden>Có tin mới ↓</button>
<div class="cg-identity" data-cg-identity hidden><span data-cg-identity-name></span><?php if ($group['identity_mode'] === 'guest' && $this->auth->check() && $group['status'] === 'active'): ?><button type="button" data-cg-edit-name>Đổi tên</button><?php endif; ?></div>
<p class="cg-status" data-cg-status role="status" aria-live="polite"></p>
<?php if ($group['status'] !== 'active'): ?><p class="cg-notice"><?= $group['status'] === 'closed' ? 'Nhóm đã đóng. Bạn đang xem với quyền kiểm duyệt.' : 'Nhóm hiện chỉ cho phép đọc.' ?></p>
<?php elseif (!$this->auth->check() && $group['identity_mode'] !== 'guest'): ?><p class="cg-notice"><a href="<?= site_url('dang-nhap') . '?next=' . rawurlencode('clubs/' . $group['slug']) ?>">Đăng nhập</a> để gửi tin nhắn.</p>
<?php else: ?>
<?php if ($group['identity_mode'] === 'guest' && $this->auth->check()): ?>
<dialog class="cg-nickname-dialog" data-cg-nickname-dialog aria-label="Đổi tên giả">
<form data-cg-nickname class="cg-nickname">
    <h3>Đổi tên giả</h3>
    <label for="cg-nickname-<?= (int) $group['id'] ?>">Tên giả của bạn</label>
    <div class="cg-nickname-controls">
        <input id="cg-nickname-<?= (int) $group['id'] ?>" name="nickname" minlength="2" maxlength="40" required autocomplete="off" placeholder="Nhập tên bạn muốn dùng…" aria-describedby="cg-nickname-help-<?= (int) $group['id'] ?>">

    </div>
    <small id="cg-nickname-help-<?= (int) $group['id'] ?>">Chọn tên giả trước khi trò chuyện. Tránh dùng tên thật hoặc thông tin riêng tư.</small>
    <p class="cg-nickname-error" data-cg-nickname-error role="alert"></p>
    <div class="cg-nickname-dialog-actions"><button type="button" data-cg-cancel-name>Hủy</button><button type="submit">Xác nhận</button></div>
</form>
</dialog>
<?php endif; ?>
<?php if (!$this->auth->check() && $group['allow_images']): ?>
<dialog class="cg-upload-auth" data-cg-upload-auth aria-label="Đăng nhập để gửi ảnh">
    <span class="cg-upload-auth-icon" aria-hidden="true"><?= tk_icon('image', 'cg-icon') ?></span>
    <h3>Chia sẻ khoảnh khắc, kết nối gần hơn</h3>
    <p>Đăng nhập hoặc tạo tài khoản để gửi ảnh trong nhóm. Bạn vẫn dùng tên giả và avatar ẩn danh khi trò chuyện tại đây.</p>
    <div class="cg-upload-auth-actions">
        <a class="cg-button" href="<?= e(site_url('dang-ky') . '?next=' . rawurlencode('clubs/' . $group['slug'])) ?>">Đăng ký tài khoản</a>
        <a class="cg-upload-login" href="<?= e(site_url('dang-nhap') . '?next=' . rawurlencode('clubs/' . $group['slug'])) ?>">Đã có tài khoản? Đăng nhập</a>
    </div>
    <button type="button" data-cg-upload-auth-close>Để sau</button>
</dialog>
<?php endif; ?>
<div class="cg-reply" data-cg-reply hidden><span></span><button type="button" data-cg-cancel-reply aria-label="Hủy trả lời">×</button></div>
<form class="cg-composer" data-cg-form><?php if ($group['allow_images']): ?><button class="cg-attach" type="button" data-cg-attach aria-label="Gửi ảnh" title="Gửi ảnh"><?= tk_icon('image', 'cg-icon') ?></button><?php if ($this->auth->check()): ?><input type="file" accept="image/jpeg,image/png,image/webp" data-cg-file hidden><?php endif; ?><?php endif; ?><label class="cg-sr" for="cg-input-<?= (int) $group['id'] ?>">Tin nhắn</label><textarea id="cg-input-<?= (int) $group['id'] ?>" name="content" maxlength="2000" rows="1" placeholder="Nhập tin nhắn…" required></textarea><button type="button" data-cg-emoji aria-label="Thêm biểu tượng cảm xúc"><?= tk_icon('smile', 'cg-icon') ?></button><button type="submit">Gửi</button></form>

<?php endif; ?>
</section>
