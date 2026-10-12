<?php defined('BASEPATH') OR exit('No direct script access allowed');
$sorts = array('nearby' => 'Gần bạn nhất', 'active' => 'Vừa online', 'new' => 'Mới tham gia', 'verified' => 'Đã xác thực');
?>
<div class="container dating-page">
    <?php $this->load->view('dating/_discovery', array('sorts' => $sorts)); ?>

    <?php if (empty($members)): ?>
        <p class="empty">Chưa có hồ sơ nào trong mục này.
            <a href="<?= site_url('hen-ho') ?>">Xem tất cả</a> hoặc
            <a href="<?= site_url('thanh-vien') ?>">duyệt toàn bộ thành viên</a>.</p>
    <?php else: ?>
        <div class="member-grid">
            <?php foreach ($members as $m): ?>
                <?php $this->load->view('members/_card', array('m' => $m, 'dating_card' => true)); ?>
            <?php endforeach; ?>
        </div>
        <?= $pagination ?>
    <?php endif; ?>

    <!-- Mẹo hẹn hò an toàn -->
    <section class="safety-box">
        <h2>Hẹn hò an toàn</h2>
        <div class="safety-grid">
            <div class="safety-item">
                <svg viewBox="0 0 24 24" class="ic"><path d="M12 3l7 3v6c0 4.4-3 8-7 9-4-1-7-4.6-7-9V6z"/><path d="M9.2 12.2l2 2 3.6-3.9"/></svg>
                <h3>Xác minh thông tin</h3>
                <p>Ưu tiên hồ sơ có tích xanh. Gọi video ngắn trước khi gặp để chắc chắn đúng người.</p>
            </div>
            <div class="safety-item">
                <svg viewBox="0 0 24 24" class="ic"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg>
                <h3>Hẹn nơi công cộng</h3>
                <p>Lần đầu nên gặp ở quán cà phê, trung tâm thương mại và báo cho người thân biết lịch hẹn.</p>
            </div>
            <div class="safety-item">
                <svg viewBox="0 0 24 24" class="ic"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.3v.2"/></svg>
                <h3>Cảnh giác chuyển tiền</h3>
                <p>Không chuyển tiền, nạp thẻ hay đầu tư theo lời người mới quen, dù lý do nghe hợp lý đến đâu.</p>
            </div>
            <div class="safety-item">
                <svg viewBox="0 0 24 24" class="ic"><path d="M4 5h16v11H8l-4 3z"/><path d="M9 9.5h6M9 12.5h4"/></svg>
                <h3>Giữ trao đổi trên hệ thống</h3>
                <p>Trò chuyện trong ứng dụng để còn bằng chứng khi cần báo cáo hành vi xấu.</p>
            </div>
        </div>
    </section>

    <!-- Nội dung giới thiệu, thu gọn để không đẩy danh sách xuống quá xa -->
    <section class="seo-box" id="seo-box">
        <h2>Về cộng đồng hẹn hò <?= e($settings['site_name'] ?? 'Saigon Cupid') ?></h2>
        <div class="seo-text" id="seo-text">
            <p><?= e($settings['site_name'] ?? 'Saigon Cupid') ?> là nơi những người độc thân nghiêm túc
                gặp nhau. Khác với các ứng dụng lướt nhanh, ở đây mỗi hồ sơ đều có phần giới thiệu, nghề
                nghiệp, chiều cao, tình trạng hôn nhân và sở thích, giúp bạn hình dung rõ về đối phương
                trước khi bắt chuyện.</p>
            <p>Bạn có thể tìm bạn trai, tìm bạn gái, tìm bạn đời sau ly hôn, hoặc kết nối với người Việt
                đang sinh sống ở nước ngoài. Bộ lọc cho phép chọn theo độ tuổi, khu vực, chiều cao, học vấn,
                thói quen hút thuốc, uống rượu và sở thích chung — càng nhiều điểm tương đồng, khả năng hợp
                nhau càng cao.</p>
            <p>Cộng đồng cũng dành riêng mục cho người đồng tính nam và đồng tính nữ, với cùng nguyên tắc:
                tôn trọng, không quấy rối, không quảng cáo. Mọi hồ sơ vi phạm đều có thể được báo cáo và sẽ
                được xem xét.</p>
            <p>Hãy hoàn thiện hồ sơ với ảnh rõ mặt và vài dòng giới thiệu thật lòng. Hồ sơ đầy đủ được ưu
                tiên hiển thị và nhận nhiều lượt quan tâm hơn hẳn so với hồ sơ để trống.</p>
        </div>
        <button type="button" class="seo-toggle" id="seo-toggle" aria-expanded="false">Xem thêm</button>
    </section>
</div>
