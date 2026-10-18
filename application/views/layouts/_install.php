<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<dialog class="install-dialog" id="install-dialog" aria-labelledby="install-title">
    <img src="<?= base_url('assets/site/img/android-chrome-192x192.png') ?>" width="64" height="64" alt="">
    <h2 id="install-title">Saigon Cupid trên màn hình chính</h2>
    <p>Mở nhanh bằng một biểu tượng trên điện thoại hoặc máy tính, không cần tải từ kho ứng dụng.</p>
    <ol data-install-steps></ol>
    <p class="install-feedback" data-install-feedback role="status"></p>
    <div class="install-dialog-actions">
        <button type="button" data-install-close>Đóng</button>
        <button type="button" class="install-confirm" data-install-confirm hidden>Thêm ngay</button>
    </div>
</dialog>
