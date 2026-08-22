<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <form class="content-box auth-form" method="post" enctype="multipart/form-data">
            <h1 class="section-title">Ảnh của tôi</h1>
            <label for="photos">Tải ảnh lên (có thể chọn nhiều)</label>
            <div class="upload-field">
                <input type="file" id="photos" name="photos[]" accept="image/*" multiple required>
                <p class="upload-hint">Định dạng JPG, PNG, WEBP — tối đa 5MB mỗi ảnh. Ảnh hiển thị sau khi được duyệt.</p>
            </div>
            <div class="auth-actions">
                <button class="btn btn-primary" type="submit">Tải lên</button>
            </div>
        </form>

        <div class="content-box">
            <h2 class="section-title">Album (<?= count($photos) ?>)</h2>
            <?php if (empty($photos)): ?>
                <p class="empty">Bạn chưa có ảnh nào.</p>
            <?php else: ?>
                <div class="photo-grid">
                    <?php foreach ($photos as $ph): ?>
                        <figure>
                            <img src="<?= base_url(ltrim($ph['path'], '/')) ?>" alt="">
                            <figcaption>
                                <?= status_label($ph['status'] === 'approved' ? 'approved' : ($ph['status'] === 'rejected' ? 'rejected' : 'pending')) ?>
                                <a href="<?= site_url('tai-khoan/xoa-anh/' . $ph['id']) ?>"
                                   data-confirm="Xoá ảnh này?" data-confirm-danger>Xoá</a>
                            </figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Quy định ảnh</h3>
            <p>Chỉ đăng ảnh của chính bạn, không phản cảm. Ảnh được kiểm duyệt trước khi hiển thị công khai.</p>
        </div>
    </aside>
</div>
