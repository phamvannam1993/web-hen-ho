<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="cg-chat" data-group-chat data-group-id="<?= (int) $group['id'] ?>" data-api="<?= site_url('ajax/nhom/' . $group['id']) ?>" data-csrf="<?= e($chat_csrf) ?>" data-guest="<?= $this->auth->check() ? '0' : '1' ?>" data-can-send="<?= $group['status'] === 'active' && ($this->auth->check() || $group['identity_mode'] === 'guest') ? '1' : '0' ?>">
<header class="cg-header"><div class="cg-header-who"><span class="cg-room-icon" aria-hidden="true"><?= tk_icon($group['identity_mode'] === 'guest' ? 'eye-off' : 'message', 'cg-icon') ?></span><div><strong><?= e($group['name']) ?></strong><small data-cg-online>Đang tải…</small></div></div><div class="cg-toolbar"><button type="button" data-cg-follow>Theo dõi</button><button type="button" data-cg-notifications>Tắt thông báo</button><button type="button" data-cg-share>Chia sẻ</button></div></header>
<details class="cg-info"><summary>Nội quy &amp; người đang trò chuyện</summary><p><?= nl2br(e($group['rules'])) ?></p><div data-cg-people></div></details>
<?php if ($group['identity_mode'] === 'guest'): ?><p class="cg-notice">Khách dùng nickname ẩn danh theo trình duyệt. Khi đăng nhập, tin mới dùng tên và ảnh hồ sơ thật. Nội dung tự chia sẻ có thể tiết lộ danh tính.</p><?php endif; ?>
<div class="cg-pin" data-cg-pin hidden></div>
<p class="cg-loading" data-cg-loading role="status" aria-live="polite" hidden>Đang tải tin nhắn…</p>
<div class="cg-feed" data-cg-feed role="log" aria-label="Tin nhắn nhóm" tabindex="0"></div>
<button type="button" class="cg-jump" data-cg-jump hidden>Có tin mới ↓</button>
<p class="cg-status" data-cg-status role="status" aria-live="polite"></p>
<?php if ($group['status'] !== 'active'): ?><p class="cg-notice"><?= $group['status'] === 'closed' ? 'Nhóm đã đóng. Bạn đang xem với quyền kiểm duyệt.' : 'Nhóm hiện chỉ cho phép đọc.' ?></p>
<?php elseif (!$this->auth->check() && $group['identity_mode'] !== 'guest'): ?><p class="cg-notice"><a href="<?= site_url('dang-nhap') . '?next=' . rawurlencode('nhom/' . $group['slug']) ?>">Đăng nhập</a> để gửi tin nhắn.</p>
<?php else: ?>
<div class="cg-reply" data-cg-reply hidden><span></span><button type="button" data-cg-cancel-reply aria-label="Hủy trả lời">×</button></div>
<form class="cg-composer" data-cg-form><label class="cg-sr" for="cg-input-<?= (int) $group['id'] ?>">Tin nhắn</label><textarea id="cg-input-<?= (int) $group['id'] ?>" name="content" maxlength="2000" rows="1" placeholder="Nhập tin nhắn…" required></textarea><button type="button" data-cg-emoji aria-label="Thêm biểu tượng cảm xúc"><?= tk_icon('smile', 'cg-icon') ?></button><button type="submit">Gửi</button></form>
<?php if ($this->auth->check() && $group['allow_images']): ?><label class="cg-attach">Gửi ảnh <input type="file" accept="image/jpeg,image/png,image/webp" data-cg-file></label><?php endif; ?>
<?php endif; ?>
</section>
