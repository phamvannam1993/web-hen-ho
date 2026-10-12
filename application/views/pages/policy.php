<?php defined('BASEPATH') OR exit('No direct script access allowed');
$email = $settings['contact_email'] ?? 'contact@saigoncupid.com';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || substr($email, -6) === '.local') $email = 'contact@saigoncupid.com';
$phone = $settings['hotline'] ?? '0899.015.709';
if (!$phone || preg_replace('/\D/', '', $phone) === '0900000000') $phone = '0899.015.709';
?>
<div class="container"><article class="content-box static-page">
<h1><?= e($title) ?></h1>
<?php if ($policy_slug === 'lien-he'): ?>
<p>Liên hệ để được hỗ trợ tài khoản, phản ánh hồ sơ hoặc gửi yêu cầu liên quan đến dữ liệu cá nhân.</p>
<h2>Đơn vị vận hành</h2>
<p><?= e($settings['company_name'] ?? 'CÔNG TY TNHH KỸ THUẬT TÂM QUANG EMT') ?></p>
<p>Mã số thuế: <?= e($settings['tax_code'] ?? '3702478970') ?></p>
<?php if (!empty($settings['address'])): ?><p>Địa chỉ: <?= e($settings['address']) ?></p><?php endif; ?>
<?php elseif ($policy_slug === 'bao-mat'): ?>
<p>Trang này giải thích cách thông tin được sử dụng khi bạn tham gia Saigon Cupid. Chỉ cung cấp dữ liệu cần thiết và cân nhắc trước khi chia sẻ thông tin nhạy cảm.</p>
<h2>Thông tin được xử lý</h2>
<p>Thông tin tài khoản gồm email, mật khẩu và ngày sinh. Hồ sơ có thể gồm tên hiển thị, ảnh, giới tính, khu vực, tình trạng hôn nhân, sở thích và tiêu chí tìm bạn. Dịch vụ cũng xử lý lượt thích, kết đôi, tin nhắn, báo cáo vi phạm và hoạt động tài khoản.</p>
<h2>Mục đích sử dụng</h2>
<p>Dữ liệu được dùng để đăng nhập, hiển thị hồ sơ, gợi ý kết nối, gửi thông báo, hỗ trợ thành viên và xử lý hành vi vi phạm. Website sử dụng cookie phiên để duy trì đăng nhập và Google Analytics để đo hoạt động truy cập.</p>
<h2>Hiển thị và chia sẻ</h2>
<p>Ảnh, tên hiển thị, tuổi và khu vực có thể xuất hiện trong danh sách công khai. Người xem hồ sơ có thể thấy thêm thông tin bạn cung cấp. Nội dung trò chuyện được chuyển đến người nhận. Không đưa địa chỉ nhà, giấy tờ tùy thân, thông tin ngân hàng hoặc thông tin của người khác vào hồ sơ công khai.</p>
<h2>Lưu trữ và bảo vệ</h2>
<p>Thông tin được lưu để cung cấp dịch vụ và giải quyết yêu cầu hỗ trợ. HTTPS bảo vệ dữ liệu trong quá trình truyền; không có hệ thống nào bảo đảm an toàn tuyệt đối. Liên hệ bộ phận hỗ trợ để được xác nhận thời hạn lưu trữ, phạm vi xử lý và tình trạng yêu cầu xóa dữ liệu cụ thể.</p>
<h2>Yêu cầu về dữ liệu cá nhân</h2>
<p>Bạn có thể sửa hồ sơ trong khu vực tài khoản hoặc liên hệ để yêu cầu truy cập, chỉnh sửa, xóa dữ liệu hay dừng sử dụng tài khoản. Khi gửi yêu cầu, dùng email đăng ký và mô tả phạm vi yêu cầu; không gửi mật khẩu. Bộ phận hỗ trợ có thể cần xác minh quyền sở hữu tài khoản.</p>
<?php else: ?>
<p>Saigon Cupid dành cho người từ đủ 18 tuổi. Tìm hiểu đối phương từng bước và chủ động bảo vệ thông tin cá nhân.</p>
<h2>Nhận biết dấu hiệu lừa đảo</h2>
<p>Cảnh giác khi người mới quen yêu cầu chuyển tiền, đầu tư, mã OTP, ảnh giấy tờ hoặc gửi đường dẫn đăng nhập lạ. Không chuyển tiền chỉ dựa vào lời hứa hay tình cảm qua mạng.</p>
<h2>Gặp mặt an toàn</h2>
<p>Chọn nơi công cộng, tự chủ phương tiện đi lại và báo cho người tin cậy về kế hoạch gặp. Bạn có quyền kết thúc cuộc trò chuyện hoặc buổi gặp bất cứ lúc nào.</p>
<h2>Báo cáo vi phạm</h2>
<p>Dùng chức năng báo cáo trên hồ sơ hoặc liên hệ hỗ trợ khi gặp quấy rối, mạo danh hay nghi ngờ người dùng chưa đủ tuổi. Ghi lại đường dẫn hồ sơ và thông tin cần thiết; tránh phát tán dữ liệu riêng tư.</p>
<?php endif; ?>
<h2>Liên hệ hỗ trợ</h2>
<p>Email: <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><br>Điện thoại: <a href="tel:<?= e(preg_replace('/\D/', '', $phone)) ?>"><?= e($phone) ?></a></p>
<p><a href="<?= site_url('bao-mat') ?>">Chính sách bảo mật</a> · <a href="<?= site_url('an-toan') ?>">Hẹn hò an toàn</a> · <a href="<?= site_url('noi-quy') ?>">Nội quy</a></p>
</article></div>
