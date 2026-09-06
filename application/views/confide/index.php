<?php defined('BASEPATH') OR exit('No direct script access allowed');
$sorts = array('active' => 'Vừa online', 'new' => 'Mới tham gia', 'listened' => 'Lắng nghe nhiều nhất');
$link = function ($key, $value) use ($base_url) {
    $q = $this->input->get();
    if ($value === '') { unset($q[$key]); } else { $q[$key] = $value; }
    return site_url($base_url) . ($q ? '?' . http_build_query($q) : '');
};
?>
<div class="container confide-page">
    <nav class="breadcrumb">
        <a href="<?= site_url() ?>">Trang chủ</a> ›
        <?php if ($tab): ?>
            <a href="<?= site_url('tam-su') ?>">Tâm sự</a> ›
            <span><?= e($tabs[$tab]['label']) ?></span>
        <?php else: ?>
            <span>Tâm sự</span>
        <?php endif; ?>
    </nav>

    <header class="confide-head">
        <h1><?= e($heading) ?></h1>
        <p><?= e($tabs[$tab]['desc']) ?></p>
    </header>

    <nav class="dating-tabs" aria-label="Nhóm tâm sự">
        <?php foreach ($tabs as $key => $t): ?>
            <a class="<?= $key === $tab ? 'on' : '' ?>"
               href="<?= site_url('tam-su' . ($key ? '/' . $key : '')) ?>"><?= e($t['label']) ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="dating-bar">
        <p class="result-total"><b><?= number_format($total) ?></b> người đang muốn trò chuyện</p>
        <div class="sort-tabs">
            <span>Xếp theo:</span>
            <?php foreach ($sorts as $key => $label): ?>
                <a class="<?= $sort === $key ? 'on' : '' ?>" href="<?= $link('sort', $key) ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Lọc nhanh theo chủ đề muốn tâm sự -->
    <div class="topic-row">
        <a class="<?= $topic === '' ? 'on' : '' ?>" href="<?= $link('topic', '') ?>">Mọi chủ đề</a>
        <?php foreach ($topics as $key => $label): ?>
            <a class="<?= $topic === $key ? 'on' : '' ?>" href="<?= $link('topic', $key) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($members)): ?>
        <p class="empty">Chưa có ai trong mục này.
            <a href="<?= site_url('tam-su') ?>">Xem tất cả</a> hoặc
            <a href="<?= site_url('thanh-vien') ?>">duyệt toàn bộ thành viên</a>.</p>
    <?php else: ?>
        <div class="confide-grid">
            <?php foreach ($members as $m): ?>
                <?php $this->load->view('confide/_card', array('m' => $m)); ?>
            <?php endforeach; ?>
        </div>
        <?= $pagination ?>
    <?php endif; ?>

    <section class="safety-box">
        <h2>Quy tắc &amp; an toàn khi tâm sự</h2>
        <div class="safety-grid">
            <div class="safety-item">
                <svg viewBox="0 0 24 24" class="ic"><rect x="4.5" y="10.5" width="15" height="9.5" rx="2"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/></svg>
                <h3>Tôn trọng sự riêng tư</h3>
                <p>Điều người khác kể chỉ nằm lại giữa hai người. Đừng chụp lại hay kể cho bên thứ ba.</p>
            </div>
            <div class="safety-item">
                <svg viewBox="0 0 24 24" class="ic"><path d="M4 5h16v11H8l-4 3z"/><path d="M12 8v3.5M12 13.4v.2"/></svg>
                <h3>Không quấy rầy, gạ gẫm</h3>
                <p>Nhắn một lần, chờ hồi đáp. Không gửi nội dung nhạy cảm khi chưa được đồng ý.</p>
            </div>
            <div class="safety-item">
                <svg viewBox="0 0 24 24" class="ic"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.8h5M9.5 14.2h5M13 7.5v9"/></svg>
                <h3>Không dính tới tiền bạc</h3>
                <p>Tuyệt đối không chuyển khoản, nạp thẻ hay cho vay với người mới quen qua mạng.</p>
            </div>
            <div class="safety-item">
                <svg viewBox="0 0 24 24" class="ic"><path d="M12 3l9 16H3z"/><path d="M12 9.5v4M12 16.3v.2"/></svg>
                <h3>Báo cáo khi thấy bất thường</h3>
                <p>Gặp lời mời đầu tư, xin thông tin cá nhân hay lời lẽ xúc phạm, hãy báo cáo hồ sơ đó.</p>
            </div>
        </div>
    </section>

    <section class="seo-box">
        <h2>Không gian tâm sự tại <?= e($settings['site_name'] ?? 'Saigon Cupid') ?></h2>
        <div class="seo-text" id="seo-text">
            <p>Có những ngày chỉ cần một người chịu ngồi nghe là đủ nhẹ lòng. Mục Tâm sự của
                <?= e($settings['site_name'] ?? 'Saigon Cupid') ?> sinh ra cho những lúc như vậy: không phải để tán tỉnh,
                mà để tìm một người trò chuyện tử tế khi bạn mệt với công việc, buồn chuyện gia đình,
                hay đơn giản là mất ngủ giữa đêm và muốn nói với ai đó vài câu.</p>
            <p>Mỗi hồ sơ ở đây đều ghi rõ chủ đề mình muốn chia sẻ — cần người lắng nghe, trò chuyện phiếm,
                chuyện công việc, chuyện gia đình, chuyện tình cảm hay trò chuyện đêm khuya. Nhờ vậy bạn
                chọn được người cùng tâm trạng thay vì nhắn ngẫu nhiên rồi lạc đề.</p>
            <p>Cộng đồng mở cho tất cả: bạn nam, bạn nữ, người thuộc cộng đồng LGBT, và cả các cô chú lớn tuổi
                muốn tìm bạn già trò chuyện. Nguyên tắc chung rất đơn giản — tôn trọng, không phán xét,
                không gạ gẫm, không lợi dụng câu chuyện của người khác.</p>
            <p>Thông tin cá nhân của bạn do bạn quyết định chia sẻ tới đâu. Số điện thoại, địa chỉ hay nơi làm việc
                không bắt buộc phải công khai. Nếu gặp người có hành vi quấy rầy hoặc dấu hiệu lừa đảo, hãy báo cáo
                để đội ngũ quản trị xử lý và giữ không gian này lành mạnh cho mọi người.</p>
        </div>
        <button type="button" class="seo-toggle" id="seo-toggle" aria-expanded="false">Xem thêm</button>
    </section>
</div>
