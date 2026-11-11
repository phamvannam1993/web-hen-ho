<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="ig-chat" data-private-group="<?= (int) $private_group_id ?>" aria-label="Nhóm riêng trong inbox">
    <header class="ig-head">
        <a href="<?= e(site_url('messenger')) ?>" class="ig-button" aria-label="Về danh sách tin nhắn">‹</a>
        <div class="ig-title"><h2 data-ig-name>Đang tải nhóm…</h2><span data-ig-count></span></div>
        <button type="button" class="ig-header-more" data-ig-header-more aria-label="Tùy chọn nhóm" aria-haspopup="true" aria-expanded="false" disabled>⋯</button>
    </header>
    <button type="button" class="ig-older" data-ig-older hidden>Tải tin nhắn cũ</button>
    <div class="ig-feed" data-ig-feed role="log" aria-label="Tin nhắn trong nhóm" tabindex="0"></div>
    <p class="ig-status" data-ig-status role="status" aria-live="polite"></p>
    <form class="ig-form" data-ig-send>
        <label class="sr-only" for="ig-content">Tin nhắn nhóm</label>
        <textarea id="ig-content" name="content" rows="1" maxlength="2000" required placeholder="Nhập tin nhắn…" disabled></textarea>
        <button type="submit" disabled>Gửi</button>
    </form>
</section>
