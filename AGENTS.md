# Quy trình làm việc
- Giao tiếp bằng tiếng Việt, ngắn gọn; ưu tiên bằng chứng và kết quả.
- Đọc các quy tắc hiện có trước khi làm; giữ nguyên thay đổi của người dùng.
- Trước mọi chỉnh sửa: khảo sát chỉ đọc, lập kế hoạch các pha nhỏ, nêu cách kiểm tra và trình bày diff đầy đủ của pha kế tiếp.
- Chờ người dùng nói GO hoặc phê duyệt rõ ràng pha đó rồi mới sửa file.
- Mỗi lần chỉ triển khai một pha nhỏ đã được duyệt; không tự mở rộng phạm vi.
- Sau mỗi pha, chạy kiểm tra phù hợp, báo kết quả và giới hạn; dừng chờ duyệt pha kế tiếp.
- Nếu cần thay đổi diff đã duyệt, trình bày diff mới và chờ duyệt trước khi áp dụng.
- Không tự push, deploy, publish hoặc chạy migration trên production; mỗi hành động cần yêu cầu riêng rõ ràng.
- Không đọc hoặc đưa bí mật từ .env, khóa hay thông tin xác thực vào đầu ra.

# Điều phối agent
- Agent chính chịu trách nhiệm kế hoạch, diff, triển khai và xác minh.
- Không spawn agent cho việc đơn giản, sửa nhỏ hoặc tra cứu một vị trí.
- Với việc phức tạp, được phép gọi agent chuyên trách có nhiệm vụ hẹp:
  advisor để thẩm định; search để lần luồng code; grep để tìm vị trí; docs để kiểm tra tài liệu.
- Bắt buộc gọi advisor trước khi trình kế hoạch triển khai phức tạp để duyệt,
  sau hai lần thất bại liên tiếp cùng một vấn đề trước khi thử tiếp,
  và trước khi báo hoàn tất toàn bộ công việc phức tạp.
- Việc phức tạp gồm thay đổi nhiều hệ thống, kiến trúc, xác thực, phân quyền,
  thanh toán, schema dữ liệu hoặc có rủi ro hồi quy đáng kể.
- Gửi advisor phạm vi, diff hoặc kế hoạch và bằng chứng kiểm tra; giải quyết phát hiện nghiêm trọng.
- Nếu advisor không khả dụng, báo rõ và chờ hướng dẫn thay vì bỏ qua thẩm định.
- Các agent chuyên trách chỉ đọc, không sửa file, không spawn thêm agent,
  không thay người dùng phê duyệt; trả kết quả về agent chính.
- Tối đa hai agent phụ đồng thời; tái sử dụng agent khi phù hợp.
- Ưu tiên rg và đọc có mục tiêu; tránh quét lại, log dài và giao việc trùng lặp.
