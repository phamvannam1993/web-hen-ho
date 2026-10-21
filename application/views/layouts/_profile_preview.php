<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<dialog class="profile-preview" id="profile-preview" data-base="<?= e(site_url()) ?>" aria-labelledby="profile-preview-title">
    <button type="button" class="profile-preview-close" data-preview-close aria-label="Đóng">×</button>
    <p class="profile-preview-eyebrow">KẾT NỐI CHÂN THÀNH</p>
    <div data-preview-body></div>
    <p class="profile-preview-status" role="status" aria-live="polite" data-preview-status></p>
    <div class="profile-preview-actions"><button type="button" data-preview-cancel>Hủy</button><button type="button" data-preview-buy disabled>Xem hồ sơ chi tiết</button></div>
</dialog>
<script defer src="<?= base_url('assets/site/js/profile-preview.js') ?>?v=<?= filemtime(FCPATH.'assets/site/js/profile-preview.js') ?>"></script>
