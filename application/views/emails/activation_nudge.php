<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<h2><?= e($name) ?>, hãy bắt đầu kết nối theo cách của bạn</h2>
<?php if ($verify): ?>
<p>Bạn đã đăng ký Saigon Cupid. Xác nhận email để có thể thả tim và trò chuyện khi tìm được người phù hợp.</p>
<p>Mở trang xác nhận để yêu cầu gửi lại mã hoặc đường dẫn xác thực nếu thư cũ đã hết hạn.</p>
<?php elseif (!empty($review)): ?>
<p>Tài khoản của bạn đang chờ duyệt. Bạn có thể quay lại theo dõi trạng thái hoặc kiểm tra thông tin đã đăng ký.</p>
<?php else: ?>
<p>Thêm thông tin hồ sơ và tiêu chí tìm bạn để hồ sơ được hiển thị và nhận gợi ý phù hợp hơn.</p>
<?php if ($missing): ?><p>Các mục cần bổ sung: <?= e(implode(', ', $missing)) ?>.</p><?php endif; ?>
<?php endif; ?>
<?php $this->load->view('emails/_cta', array('url' => $link, 'label' => $verify ? 'Xác nhận email' : (!empty($review) ? 'Xem tài khoản' : 'Hoàn thiện hồ sơ'))); ?>
