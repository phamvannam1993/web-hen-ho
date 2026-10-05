# Email kéo thành viên quay lại

Cron `goi_y` chạy lúc 08:05 giờ Việt Nam; worker gửi hàng đợi mỗi phút. `keo_lai` và `nhac_ho_so` nay gọi cùng dispatcher, không cần lịch riêng. Không thay đổi schema database.

Mỗi lượt xét mọi tài khoản member active/pending có email hợp lệ, chưa xóa và chưa hủy nhận email. Người thiếu dòng email_prefs được tạo cấu hình mặc định; người vừa truy cập, chưa xác nhận hoặc chưa hoàn thiện vẫn được xét. Không phục hồi cài đặt của người đã hủy nhận.

Một nội dung được chọn theo trạng thái: xác nhận email → bổ sung hồ sơ → chờ duyệt nếu cần → hồ sơ phù hợp → lời mời khám phá nếu không có ứng viên. Nhắc thiết lập và lời mời khám phá tuân theo công tắc Nhắc nhở; gợi ý tuân theo công tắc Gợi ý người phù hợp.

Tần suất mặc định vẫn 2 ngày, giữ nguyên lựa chọn hiện có. Có thể chọn 1/2/3/7 ngày; tài khoản vắng hơn 30 ngày dùng tối thiểu 7 ngày. Thư đang chờ giữ chỗ cho chu kỳ; thời gian chờ giữa thư thành công áp dụng chung cho các loại retention. Các cron dùng một file lock chung trên cùng server. Worker giữ khóa tới khi gửi xong, không bị ghi đè bởi vòng lặp đường dẫn.

Ứng viên phải active, đủ 18 tuổi, đủ hồ sơ, đúng giới tính/khoảng tuổi/mục đích đã khai, hoạt động trong 30 ngày; loại người đã thích, ghép đôi, bỏ qua, chặn nhau hoặc đã gửi trong 7 ngày. Lọc lịch sử ngay trong truy vấn để chọn được ứng viên tiếp theo. Điểm phần trăm chỉ là điểm chấm theo quy tắc, không phải xác suất kết đôi; đã bỏ sàn 70%.

Trước khi gửi, kiểm tra lại trạng thái/email người nhận và ứng viên; bỏ thư đã lỗi thời. Nhắc kích hoạt tự cập nhật CTA theo tiến độ mới. Email giao dịch không tính vào trần 2 email tổng hợp/ngày; mail đang chờ chạm trần được hoãn đến 08:05 hôm sau. Tùy chọn hủy nhận, bounce và retry SMTP vẫn được giữ.

Kiểm tra bằng `php -d extension=pdo_sqlite tests/retention_emails.php` và các test hồi quy. Test không gửi SMTP. Chưa thực hiện thay đổi cron hay gửi hàng loạt trên production. Cần theo dõi queue/log, tỷ lệ mở/bấm/hủy và số lỗi SMTP sau triển khai. Khóa file chống chạy chồng trên một server; nếu có nhiều worker trên nhiều server cần cơ chế khóa chung trong database.
