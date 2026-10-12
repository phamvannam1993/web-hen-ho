<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ảnh của tôi — dựng theo `src/routes/tai-khoan.anh.tsx`.
 *
 * Bản thiết kế có nút đặt ảnh đại diện và kéo thả sắp xếp; backend chưa hỗ trợ
 * hai việc này nên không đưa vào.
 */

// trạng thái ảnh => (nhãn, sắc thái pill)
$trang_thai = array(
    'approved' => array('Đã duyệt', 'success'),
    'pending'  => array('Chờ duyệt', 'warning'),
    'rejected' => array('Bị từ chối', 'danger'),
);
$so_duyet = count(array_filter($photos, function ($ph) { return $ph['status'] === 'approved'; }));
?>
<header class="tk-ph">
    <div class="tk-ph__b">
        <h1>Ảnh của tôi</h1>
        <p>Album càng nhiều ảnh thật, hồ sơ càng được tin tưởng.</p>
    </div>
</header>

<!-- Ô tải ảnh: chọn hoặc kéo thả, chọn xong là gửi luôn -->
<form class="tk-ap-drop" id="tk-ap-form" method="post" enctype="multipart/form-data">
    <span class="tk-ap-drop__ic"><?= tk_icon('upload') ?></span>
    <h2 class="tk-ap-drop__t">Kéo thả ảnh vào đây</h2>
    <p class="tk-ap-drop__d">Hoặc chọn nhiều ảnh từ thiết bị. Hỗ trợ JPG, PNG, WEBP — tối đa 5MB mỗi ảnh.</p>
    <label class="tk-ap-drop__pick">
        <input class="tk-sr" type="file" id="photos" name="photos[]" accept="image/*" multiple required>
        <span class="tk-btn tk-btn--brand tk-btn--lg"><?= tk_icon('upload') ?>Chọn ảnh</span>
    </label>
    <noscript><button class="tk-btn tk-btn--outline" type="submit" style="margin-top:12px">Tải lên</button></noscript>
    <p class="tk-ap-drop__busy" hidden>Đang tải ảnh lên…</p>
</form>

<section class="tk-card">
    <div class="tk-card__h">
        <div>
            <h2 class="tk-card__t">Album của bạn</h2>
            <p class="tk-card__d"><?= count($photos) ?> ảnh • <?= $so_duyet ?> đã duyệt</p>
        </div>
    </div>

    <?php if (empty($photos)): ?>
        <div class="tk-empty">
            <span class="tk-empty__ic"><?= tk_icon('images') ?></span>
            <h3>Bạn chưa có ảnh nào</h3>
            <p>Tải lên vài ảnh thật, rõ mặt để hồ sơ được chú ý hơn.</p>
            <label class="tk-empty__act tk-btn tk-btn--soft" for="photos"><?= tk_icon('upload') ?>Chọn ảnh</label>
        </div>
    <?php else: ?>
        <div class="tk-ap-grid">
            <?php foreach ($photos as $ph): ?>
                <?php $tt = $trang_thai[$ph['status']] ?? $trang_thai['pending']; ?>
                <?php $src = base_url(ltrim($ph['path'], '/')); ?>
                <figure class="tk-ap-ph">
                    <button type="button" class="tk-ap-ph__img profile-image-trigger" data-profile-image aria-label="Xem ảnh lớn hơn">
                        <img src="<?= e($src) ?>" alt="Ảnh <?= (int) $ph['id'] ?>" loading="lazy" width="768" height="960">
                        <span class="tk-ap-ph__tag"><span class="tk-pill tk-pill--<?= $tt[1] ?>"><?= $tt[0] ?></span></span>
                    </button>
                    <figcaption class="tk-ap-ph__acts">
                        <a class="tk-btn tk-btn--ghost tk-btn--icon-sm" href="<?= e($src) ?>" data-profile-image
                           aria-label="Xem ảnh" title="Xem ảnh"><?= tk_icon('eye') ?></a>
                        <a class="tk-btn tk-btn--ghost tk-btn--icon-sm tk-ap-ph__del" href="<?= site_url('tai-khoan/xoa-anh/' . $ph['id']) ?>"
                           data-confirm="Xoá ảnh này?" data-confirm-danger aria-label="Xoá ảnh" title="Xoá ảnh"><?= tk_icon('trash') ?></a>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="tk-card">
    <div class="tk-card__h">
        <div><h2 class="tk-card__t">Quy định ảnh &amp; kiểm duyệt</h2></div>
    </div>
    <ul class="tk-ap-rules">
        <li><?= tk_icon('shield') ?><span>Chỉ đăng ảnh của chính bạn, rõ mặt, không phản cảm, không chèn số điện thoại hay liên kết.</span></li>
        <li><?= tk_icon('shield') ?><span>Ảnh mới tải lên ở trạng thái <strong>Chờ duyệt</strong> và chỉ hiển thị công khai sau khi được duyệt.</span></li>
        <li><?= tk_icon('shield') ?><span>Ảnh <strong>Bị từ chối</strong> không hiển thị với người khác; bạn có thể xoá và tải ảnh khác.</span></li>
    </ul>
</section>

<script>
(function () {
    var form = document.getElementById('tk-ap-form');
    var input = document.getElementById('photos');
    if (!form || !input) return;

    function gui() {
        if (!input.files || !input.files.length) return;
        form.classList.add('is-busy');
        form.querySelector('.tk-ap-drop__busy').hidden = false;
        form.submit();
    }
    input.addEventListener('change', gui);

    // Kéo thả: gán tệp vào ô input rồi gửi như khi chọn bằng nút
    ['dragenter', 'dragover'].forEach(function (ev) {
        form.addEventListener(ev, function (e) { e.preventDefault(); form.classList.add('is-drag'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        form.addEventListener(ev, function (e) { e.preventDefault(); form.classList.remove('is-drag'); });
    });
    form.addEventListener('drop', function (e) {
        var files = e.dataTransfer && e.dataTransfer.files;
        if (!files || !files.length) return;
        try { input.files = files; } catch (err) { return; }
        gui();
    });
})();
</script>
