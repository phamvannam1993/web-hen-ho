# Saigon Cupid – Cập nhật cho dev app

**Ngày tổng hợp:** 16/11/2026
**Nguồn:** Các tính năng cải tiến mới Saigon Cupid.docx và trao đổi chỉnh web ngày 16/11/2026.

## 1. Mục đích và phạm vi

Tài liệu bàn giao yêu cầu cập nhật ứng dụng Saigon Cupid theo tài liệu cũ “Các tính năng cải tiến mới Saigon Cupid.docx” và các điều chỉnh đã chốt trên web trong ngày 16/11/2026. Khi có thay đổi nhiều lần trong trao đổi, tài liệu này ghi nhận phương án cuối cùng.

Phần 2–8 là yêu cầu đồng bộ app theo thay đổi hôm nay. Phần 9 giữ lại các đầu việc trong tài liệu cũ chưa được hoàn thiện đặc tả trong buổi làm việc này. Tài liệu không xác nhận app hoặc server production đã cập nhật.

Các kích thước bên dưới là số đo CSS tham chiếu từ web. Dev app chuyển đổi phù hợp sang dp/pt và safe area của iOS/Android; giữ bố cục, tỉ lệ và hành vi thay vì sao chép số pixel máy móc.


## 2. Danh sách Tin nhắn

- Bỏ tiêu đề “Tin nhắn” ở trên thanh tìm kiếm. Web chỉ ẩn header của website trên mobile; trên PC vẫn hiển thị header. Không bỏ phần tên và nút thao tác của hội thoại.
- Màu thanh hệ thống/phần trên cùng: chỉ màn hình DANH SÁCH Tin nhắn dùng hồng nhạt #FFEAED. Chi tiết tin nhắn riêng, chi tiết nhóm và tất cả màn hình khác dùng đỏ #D1273F. Khi vào chi tiết hoặc quay về danh sách, cập nhật màu theo màn hình đang hiển thị.
- Thanh tìm kiếm và nút “+” nằm cùng một hàng, trên nền hồng chung. Ô nhập mặc định trong suốt; chỉ khi focus mới chuyển nền trắng, không có viền hoặc quầng focus màu đậm.
- Ô tìm kiếm cao khoảng 36px. Vùng nền hồng có khoảng đệm trên/dưới gọn, khoảng 5–6px theo bố cục web cuối cùng; không quay về thanh cao như thiết kế ban đầu.
- Khi focus tìm kiếm, hiện nút quay lại bên trái dạng chevron “<”, chỉ gồm hai nét. Bấm nút: bỏ focus, đóng bàn phím, ẩn nút; giữ nội dung đã nhập và bộ lọc hiện tại. Không điều hướng ra khỏi màn hình.
- Nút quay lại hiện/ẩn bằng hiệu ứng trượt và mờ nhẹ, ô nhập co giãn mượt khoảng 180ms. Tôn trọng cài đặt giảm chuyển động của hệ điều hành.
- Nút “+” mở luồng tạo nhóm do user tạo. Bỏ khối/tiêu đề riêng “Nhóm của bạn” và khu vực tạo nhóm chen giữa danh sách; không hiển thị thêm một nút tạo nhóm trùng lặp tại đó.
- Tab Ưu tiên: trộn hội thoại riêng, nhóm do user tạo và nhóm ẩn danh trong cùng danh sách. Sắp xếp giảm dần theo thời điểm tin nhắn cuối; không ghim các nhóm lên trước chỉ vì chúng là nhóm. Vẫn dùng thời điểm gốc để sắp xếp dù không hiển thị thời gian.
- Giữ tìm kiếm theo tên hội thoại và bộ lọc tab. Tab Clubs lọc nhóm ẩn danh. Yêu cầu đổi nhãn “Clubs Chat’s” thành “Clubs” được giữ từ tài liệu cũ; web hiện còn nhãn cũ ở một số vị trí, cần rà soát khi đồng bộ app.
- Dòng hội thoại trên mobile: avatar khoảng 52 × 52px, cách nội dung 12px; khoảng đệm trên/dưới khoảng 12px. Đây là mức cuối đã thu gọn lại sau khi thử giãn dòng hơn.
- Thêm đường phân cách xám rất nhạt #F0EDEF giữa các hội thoại. Avatar, tên, preview và menu ba chấm căn đều ở tin nhắn riêng lẫn nhóm.
- Ẩn thời điểm tin nhắn cuối trong preview của cả tin nhắn riêng và các nhóm. Yêu cầu này cũng áp dụng danh sách chat sticky trên web. Không xoá dữ liệu thời gian dùng sắp xếp và không suy ra phải ẩn giờ ở từng bong bóng tin nhắn.
- Preview nhóm do user tạo hiển thị “Tên người gửi: nội dung”; tin của chính mình hiển thị “Bạn: nội dung”. Nhóm chưa có tin giữ trạng thái rỗng/số thành viên, không thêm tên người gửi giả.
- Nhóm đang chọn phải có tên in đậm và nền nhận biết đang chọn. Khi đổi hội thoại, trạng thái chọn chuyển đúng sang mục mới.
- Danh sách phải cuộn được bằng vuốt trên mobile; trên web PC cuộn bằng chuột. Không để một lớp cuộn lồng hoặc lớp chặn touch giữ thao tác vuốt.

## 3. Chi tiết hội thoại và thao tác nhập tin

- Phần đầu chi tiết hội thoại riêng chỉ giữ tên người dùng và các nút thao tác. Bỏ avatar tại phần đầu này; bỏ cả dòng “Đang online”, thời gian hoạt động cuối và dòng thay thế “Chưa có thông tin hoạt động” dưới tên.
- Không bỏ avatar trong danh sách hội thoại. Có thể tiếp tục hiển thị chấm online trên avatar danh sách theo dữ liệu hiện có; không thêm lại avatar hoặc dòng online ở đầu chi tiết.
- Ba nút gọi thoại, gọi video và ba chấm: cùng vùng bấm khoảng 36 × 36px, căn trên một hàng, không có khung viền/nền nút cố định. Hai biểu tượng gọi phải cân đối thị giác; web tham chiếu icon thoại 18px, video 22px vì hình vẽ có tỉ lệ khác nhau.
- Bấm ba chấm lần đầu mở menu, bấm lại chính nút đó đóng menu; chuyển nút ở hội thoại khác mở đúng menu tương ứng. Bấm ra ngoài hoặc Back/Escape phù hợp nền tảng sẽ đóng menu.
- Trong nhóm do user tạo: gom Thành viên, Thêm người, Đổi tên nhóm và Sao chép liên kết nhóm vào menu ba chấm ở đầu khung chat; không bày các nút này thành hàng riêng gây chật màn hình.
- Enter gửi tin trong nhóm; Shift + Enter xuống dòng khi dùng bàn phím hỗ trợ. Không gửi khi Enter đang dùng để xác nhận từ của bộ gõ/IME. Giữ kiểm tra nội dung rỗng và chống gửi trùng.
- CHỈ TRÊN MOBILE: khi bấm/focus ô nhập nội dung tin nhắn, tự cuộn vùng tin xuống dưới cùng để thấy tin mới nhất; điều chỉnh lại sau khi bàn phím mở xong. Áp dụng cho tin riêng, nhóm và khung chat nổi nếu app có.
- Không áp dụng tự cuộn này cho ô tìm kiếm; không áp dụng trên PC. Khi user chủ động kéo xem tin cũ, dừng các lần tự điều chỉnh đang chờ để không giành thao tác cuộn.

## 4. Tìm tin nhắn cũ

Chức năng tìm tin nhắn được mở từ menu hội thoại/nhóm hoặc nút tìm trong chat nổi. Giao diện nằm ngay bên trong khung chat, không mở modal toàn cửa sổ và không tạo popup riêng.

- Hiện thanh tìm kiếm phía trên vùng tin nhắn, cùng kiểu nền hồng với thanh tìm kiếm danh sách: ô nhập nền trắng khi focus, không có viền đậm, nút quay lại dạng “<”. Chỉ có MỘT biểu tượng kính lúp trong ô; không thêm nút kính lúp trùng bên phải.
- Từ khoá dài 2–100 ký tự. Tự tìm sau khoảng 300ms dừng nhập; Enter có thể tìm ngay. Tìm trong đúng hội thoại hiện tại.
- Sau khi tìm thành công, tự mở kết quả khớp đầu tiên kèm đoạn hội thoại xung quanh; cuộn đến và đánh dấu rõ tin được chọn. Kết quả mặc định theo tin mới hơn trước.
- Bấm bộ đếm kết quả để xem danh sách kết quả; bấm một kết quả bất kỳ để mở vị trí tin tương ứng và các tin lân cận, kể cả khi tin đó chưa được tải trong khung chat ban đầu.
- Nút ↑ đi đến kết quả CŨ HƠN, nút ↓ đi đến kết quả MỚI HƠN. Hiển thị vị trí hiện tại, ví dụ “2 / 20+ kết quả”; dấu “+” nghĩa là còn trang chưa tải, không phải tổng chính xác.
- Khi đi hết trang hiện có bằng nút ↑, tự tải trang kết quả cũ tiếp theo rồi mở kết quả kế tiếp. Khi thật sự hết kết quả, disable nút phù hợp; không quay vòng vô hạn.
- Web tham chiếu tải tối đa 15 tin trước và 16 tin từ tin đích trở đi cho một đoạn ngữ cảnh. Không tải toàn bộ lịch sử chỉ để nhảy đến một tin.
- Đổi từ khoá phải huỷ/bỏ qua phản hồi cũ; không để kết quả của từ khoá trước đè kết quả mới. Ngăn thao tác điều hướng trùng khi đang tải.
- Có trạng thái đang tìm, không tìm thấy, lỗi mạng và tin không còn khả dụng. Tin đã thu hồi, lịch sử người dùng đã xoá hoặc tin không có quyền xem không được xuất hiện trở lại qua chức năng nhảy.
- Nút quay lại đóng chế độ tìm và trở về chat; giữ bản nháp đang soạn. Khi đổi hội thoại, đóng tìm kiếm của hội thoại trước và huỷ các yêu cầu đang chờ.
- Phạm vi API web hiện có: tin nhắn riêng và nhóm cộng đồng/ẩn danh. Nhóm do user tạo chưa có endpoint tìm kiếm/ngữ cảnh tương đương trong phần triển khai này; dev app cần phối hợp backend bổ sung trước khi bật tính năng cho loại nhóm đó.

## 5. Ảnh gửi trong tin nhắn

- Hiển thị ảnh theo đúng tỉ lệ gốc. KHÔNG dùng khung cố định 240 × 180px như phương án thử trước đó và không kéo giãn/cắt ảnh để lấp đầy khung.
- Giới hạn ảnh xem trước vừa khung chat: tối đa khoảng 280px ngang và 320px cao theo CSS web; tự thu nhỏ nếu vùng chat hẹp hơn. Ảnh dọc/ngang phải giữ đúng tỉ lệ, không tràn toàn chiều cao cuộc trò chuyện.
- Bấm ảnh mở cửa sổ xem ảnh lớn ngay trong ứng dụng, không điều hướng sang tab/trang ảnh riêng. Có nút đóng rõ ràng; hỗ trợ Back/Escape và bấm nền ngoài khi phù hợp nền tảng.
- Đóng cửa sổ ảnh trở về đúng cuộc trò chuyện; không mất bản nháp hoặc làm reload lịch sử. Không để lớp khoá cuộn còn tồn tại sau khi đóng.
- Áp dụng thống nhất cho tin riêng, nhóm do user tạo, nhóm cộng đồng/ẩn danh và chat nổi nếu có. Có trạng thái ảnh không tải được thay vì ảnh vỡ chiếm nhiều diện tích.

## 6. Avatar và quyền quản lý nhóm

Ảnh mặc định nhóm được người dùng cung cấp đã lưu trên web tại assets/images/group-default.jpg. Dùng cùng ảnh cho app; đây là avatar mặc định CHỈ CỦA NHÓM DO USER TẠO.

- Hiển thị avatar mặc định ở danh sách hội thoại và đầu chi tiết nhóm do user tạo. Nhóm do admin tạo/Clubs vẫn dùng avatar cũ hoặc avatar riêng đã cấu hình, không thay bằng ảnh mới.
- Chưa yêu cầu thêm UI tải lên/đổi avatar nhóm trong đợt này; dự kiến làm sau. Không hiển thị nút thay avatar chưa hoạt động.
- Khi đồng bộ nhóm, giữ đúng quyền hiện có: mọi thành viên hiện tại được thêm người; chỉ người tạo nhóm được đổi tên và xoá thành viên. Không cho xoá chính người tạo nhóm.
- Popup đổi tên nhóm gọn, vừa nội dung gồm ô nhập và nút lưu; không dùng cửa sổ cao như danh sách chọn thành viên. Tên nhóm từ 2–120 ký tự.
- Xác nhận xoá thành viên bằng popup cùng giao diện sản phẩm, có “Huỷ” và “Xoá khỏi nhóm”; không dùng hộp confirm mặc định của trình duyệt trên web. Huỷ không gửi yêu cầu; xác nhận chỉ gửi một lần.
- Người bị xoá mất quyền đọc/gửi; lịch sử nhóm được giữ. Theo quyền hiện có, thành viên còn trong nhóm có thể thêm lại người đã bị xoá.
- Sao chép liên kết nhóm phải báo thành công dạng flash/toast nổi, tự ẩn khoảng 2,5 giây; không chèn thông báo thành công vào lịch sử chat. URL nhóm không được coi là cách bỏ qua kiểm tra quyền truy cập.

## 7. Gọi thoại và gọi video

- Giữ cả gọi thoại và gọi video ở đầu hội thoại, đồng bộ kiểu icon/nút theo phần 3. Popup cuộc gọi hiển thị người gọi, trạng thái đang chuẩn bị, đang đổ chuông, đang kết nối, đã kết nối/đang gọi và đã kết thúc.
- Có thời lượng từ lúc kết nối media thành công; có nhận/từ chối, tắt/bật mic, kết thúc và nút đóng sau khi cuộc gọi kết thúc. Với video giữ camera, đổi camera, chọn loa/tai nghe theo khả năng thiết bị và các tính năng hiện có.
- Khi tắt mic/camera, biểu tượng và trạng thái phải phản ánh đúng. Kết thúc cuộc gọi phải giải phóng microphone, camera, audio/video player và timer.
- Nút gọi chỉ sẵn sàng khi thiết bị hỗ trợ và kênh realtime đã kết nối. Không ép bật nút để che lỗi kết nối/cấu hình. Cần trạng thái hoặc lý do dễ hiểu khi không thể gọi.
- Web đã phát hiện và sửa lỗi merge làm voice-calls.js không chạy cùng phần khởi tạo video/camera/loa bị thiếu. Dev app cần xác nhận bản backend/realtime triển khai thực tế tương thích; việc kiểm thử giả lập không thay thế cuộc gọi thật giữa hai thiết bị.
- Ứng dụng cần xin quyền microphone/camera đúng lúc, xử lý từ chối quyền và kiểm tra cuộc gọi trên mạng Wi‑Fi/di động. Đây là điều kiện nghiệm thu tích hợp, không phải xác nhận các cuộc gọi production đã được kiểm chứng.

## 8. Quan tâm và ghép đôi

- Trong “Những người tôi thích” và “Những người thích tôi”, bỏ nút “Xem hồ sơ” và nút “Nhắn tin” ngay trên thẻ danh sách.
- Giữ ảnh và tên có thể bấm để vào trang hồ sơ. User cần vào hồ sơ để sử dụng nhắn tin, theo dõi/quan tâm và các thao tác hồ sơ tương ứng.
- Không gỡ quyền truy cập hồ sơ hoặc tự mở khoá hồ sơ đang bị giới hạn. Giữ các kiểm tra quyền/VIP/mở khoá hiện có.
- Yêu cầu này không tự động xoá các nút “Thích lại”, “Bỏ qua” hoặc thay đổi riêng tab “Đã ghép đôi”, vì hôm nay chưa có yêu cầu bỏ những chức năng đó.

## 9. Các yêu cầu từ tài liệu cũ cần tiếp tục làm rõ

Những mục dưới đây được giữ để dev app không bỏ sót tài liệu gốc. Chưa xem chúng là chức năng đã hoàn tất trong đợt trao đổi hôm nay; cần chốt thiết kế/API và nghiệp vụ trước khi triển khai.

- Clubs: đổi nhãn “Clubs Chat’s” thành “Clubs” đồng bộ các vị trí. Luồng tạo nhóm bằng dấu “+” đã được đưa vào phần 2.
- Hồ sơ mở khoá bằng xu: người mua VIP có thể tự tuỳ chỉnh giá xu mở khoá hồ sơ, theo định hướng tài liệu cũ. Cần chốt giới hạn giá, điều kiện VIP, quyền xem, thanh toán và đối soát.
- Khám phá trên iOS cũ: tài liệu cũ báo lỗi còn thanh cuộn khi mở Khám phá từ Trang chủ hoặc Hẹn hò. Cần tái hiện trên thiết bị/iOS mục tiêu và kiểm tra lại; không ghi nhận là đã sửa chỉ dựa trên các thay đổi Tin nhắn.
- Tìm kiếm chung: thu gọn CSS/giao diện tương tự thanh tìm kiếm Tin nhắn. Thay đổi hôm nay đặc tả cụ thể cho Tin nhắn và tìm lịch sử tin; chưa xác nhận toàn bộ tìm kiếm chung đã đồng bộ.
- Thêm nhiều ảnh trong hồ sơ để người khác mở khoá và xem. Phần 5 chỉ nói về ảnh gửi trong CHAT, không đồng nghĩa tính năng album hồ sơ/mở khoá nhiều ảnh đã được làm.
- Huy hiệu hồ sơ ẩn danh: chuyển sang Điểm ưu tú, tự lên level theo điểm thay cho mua và chọn. Cần đặc tả nguồn điểm, ngưỡng level, quy tắc tăng/giảm và việc chuyển dữ liệu cũ.
- Donate/tặng quà theo định hướng tài liệu gốc. Cần chốt danh mục quà, giá xu, quyền tặng/nhận, ghi nhận giao dịch và trạng thái hiển thị; không suy ra các quy tắc tài chính chưa được thống nhất.

## 10. Tham chiếu kỹ thuật cho dev app và backend

Các đường dẫn sau là endpoint WEB hiện có để tham khảo hành vi. Không mặc định dùng trực tiếp cho app native: cần kiểm tra lớp API app, xác thực, session/CSRF và chính sách lỗi. Không đưa secret, TURN credential hoặc nội dung .env vào tài liệu/client.

- Tìm tin riêng: GET ajax/tin-nhan/{conversation_id}/tim-kiem?q=...&before={cursor}. Phản hồi: messages, has_more, next_before; mỗi tin có id, mine, type, content, created_at.
- Ngữ cảnh tin riêng: GET cùng endpoint với around={message_id}. Phản hồi: messages theo thứ tự thời gian tăng dần và target_id. Backend kiểm tra thành viên hội thoại, mốc lịch sử đã xoá và tin đã thu hồi; không dùng API nhảy để đánh dấu đọc toàn bộ lịch sử.
- Tìm trong nhóm cộng đồng/ẩn danh: GET ajax/nhom/{group_id}/search?q=...&before_id={cursor}. Ngữ cảnh dùng cùng endpoint với around={message_id}; giữ cùng quy tắc quyền xem, chặn và loại tin đã xoá.
- Danh sách nhóm do user tạo: GET ajax/nhom-inbox. Preview dùng last_at để sắp xếp, last_content, last_sender_name và last_mine để hiển thị người gửi. Không hiển thị trường time trong preview dù backend còn trả dữ liệu.
- Nhóm user tạo: chi-tiet (GET); tao, them, doi-ten, xoa-thanh-vien, gui, da-doc (POST) dưới ajax/nhom-inbox, theo routes hiện có. Với API web, thao tác POST dùng action_token và được kiểm tra quyền phía server.
- Gọi: tham chiếu ajax/voice-config/{conversation_id} và kênh realtime đã cấu hình. Không trả hoặc log secret cấu hình khi báo lỗi cho user.
- Ảnh mặc định user-group: assets/images/group-default.jpg. Không thay các ảnh mặc định chat-guest.svg/chat-community.svg của nhóm admin.

## 11. Checklist nghiệm thu

- Giao diện: chỉ danh sách Tin nhắn có thanh trên cùng hồng; vào chi tiết chuyển đỏ, quay lại chuyển hồng. Web PC vẫn có header.
- Tìm hội thoại: nút back chỉ hiện khi tìm, blur đúng; nền trắng không viền đậm; hiệu ứng không giật, hỗ trợ giảm chuyển động; nút + mở đúng tạo nhóm.
- Danh sách: ba loại hội thoại trộn theo tin mới nhất; không có khối tạo nhóm chen giữa; avatar/dòng cân đối, đường phân cách nhạt, cuộn được và không lặp dòng khi refresh.
- Preview: không hiện thời điểm tin cuối; nhóm user tạo có “Tên: nội dung” hoặc “Bạn: nội dung”. Nhóm admin không bị thay avatar mới.
- Chi tiết: không có avatar/thời gian hoạt động dưới tên user; ba nút gọn, không khung, bấm lại ba chấm đóng được menu.
- Tìm tin cũ: một icon tìm kiếm; auto mở tin khớp và ngữ cảnh; bấm kết quả/↑/↓ đúng tin, tự tải trang cũ; phản hồi cũ không đè từ khoá mới; trường hợp rỗng/lỗi/thu hồi/không có quyền hiển thị đúng.
- Ảnh chat: thử ảnh ngang, dọc và vuông; không méo/cắt do ép tỉ lệ; mở/đóng ảnh lớn trở về chat và không kẹt khoá cuộn.
- Nhập tin: mobile bấm ô nhập cuộn tới cuối sau khi bàn phím mở; không giành cuộn thủ công, không ảnh hưởng ô tìm kiếm hoặc desktop; Enter không gửi nhầm khi bộ gõ đang composition.
- Quản lý nhóm: chỉ chủ nhóm đổi tên/xoá; thành viên thường vẫn thêm người được; popup nhỏ và cùng tông; Huỷ không gọi API; xoá xong người bị gỡ không còn quyền truy cập.
- Gọi thật: thử hai thiết bị cho cả gọi đến/đi, thoại/video, mic/camera, đổi đầu ra khi hỗ trợ, mất mạng, từ chối quyền và kết thúc; không rò media/timer sau khi đóng.
- Quan tâm: hai danh sách không có nút Xem hồ sơ/Nhắn tin; bấm tên/ảnh vẫn vào hồ sơ, quyền mở khoá/VIP giữ đúng.
