# Bàn giao giao diện mobile web → app Saigon Cupid

Cập nhật: **17/11/2026**. Đối tượng: dev app Expo/React Native và dev API.

Tài liệu mô tả **kết quả cuối cùng** của các chỉnh sửa trong phiên này. Các yêu cầu đã được thay thế, như nền đỏ toàn trang Khám phá hoặc nút tương tác trên thẻ Quan tâm, không còn là thiết kế cần triển khai. Các thay đổi hiện nằm trong repository web; tài liệu này không có nghĩa app native đã được sửa.

## 1. Màu và cách quy đổi

| Thành phần | Giá trị web |
|---|---|
| Đỏ chủ đạo | `#d1273f` |
| Đỏ đậm / đường phân cách thanh Khám phá | `#b21f35` |
| Nền trang hồng nhạt | `#fdf0f4` |
| Nền thanh tìm kiếm | `#ffeaed` |
| Nền thẻ / ô nhập tìm kiếm | `#ffffff` |
| Viền hồ sơ bật phí xem | `#edc15b` |
| Nền hồ sơ bật phí xem | `#fffaf0` |
| Chữ chính / phụ | `#2c2c2c` / `#6d6d6d` |

Kích thước dưới đây là CSS px để đối chiếu. Trên app dùng đơn vị layout của React Native, điều chỉnh theo font scale và safe area. Danh sách phải cuộn dọc được và không nằm dưới bottom navigation/home indicator.

## 2. Khám phá: màn riêng thay cho menu bật lên

Web dùng route **`/kham-pha`**. Nút Khám phá trong bottom navigation dẫn đến route này; app triển khai màn tương ứng trong navigation hiện có. Đây là màn tổng hợp tính năng, khác với màn vuốt ghép đôi `/swipe-match` (API `/api/discover`).

### Bố cục và màu

- Thanh trên có tiêu đề **Khám phá**, chữ trắng, nền đỏ `#d1273f`; bên phải là **Trang chủ**.
- Vùng trên cùng sát status bar/safe area dùng đỏ. Phần trống bên dưới các mục dùng **hồng nhạt `#fdf0f4`**.
- Các hàng tính năng nền trắng, phân cách bằng đường mảnh `#e2e2e2`.
- Bottom navigation vẫn hiển thị và đánh dấu Khám phá đang chọn.
- Màn web này dùng bố cục riêng, không có header/footer thông thường hoặc banner đăng ký của khách.

| Hàng | Mô tả | Đích web / hành vi app tương ứng |
|---|---|---|
| Ghép đôi ẩn | Khám phá những người phù hợp với bạn | `/swipe-match` → màn vuốt ghép đôi |
| Chat's ẩn danh | Trò chuyện, làm quen những người bạn mới | `/clubs/hen-ho` → nhóm chat tương ứng |
| Người thích tôi | Xem những người đã thích bạn | `/tai-khoan/quan-tam?tab=thich-ban` |
| Người tôi thích | Xem những người bạn đã thích | `/tai-khoan/quan-tam?tab=ban-thich` |

Hai hàng cuối chỉ hiện khi đã đăng nhập. Nút Trang chủ trở về màn Trang chủ.

### Thông số hàng đã thu gọn

| Thuộc tính | Giá trị |
|---|---|
| Padding hàng | Dọc 12, ngang 16 |
| Khoảng cách icon–chữ | 10 |
| Ô icon | 36 × 36, bo 10, nền hồng nhạt |
| Icon bên trong | 20 × 20, màu đỏ |
| Tiêu đề / mô tả | 16 đậm / 13 thường |
| Khoảng cách mô tả với tiêu đề | 2 |
| Mũi tên bên phải | `›`, màu đỏ |

Nguồn: [view Khám phá](application/views/home/explore.php), [CSS](assets/site/css/mobile-nav.css), [bottom navigation](application/views/layouts/_mobile_nav.php).

## 3. Quan tâm & ghép đôi: bốn tab cùng một mẫu

Web: **`/tai-khoan/quan-tam`**. Thứ tự và nhãn trên điện thoại:

| Vị trí | Nhãn mobile cuối cùng | Khóa tab | Mảng dữ liệu API |
|---|---|---|---|
| Hàng 1, trái | **Bạn thích** | `ban-thich` | `my_likes` |
| Hàng 1, phải | **Thích bạn** | `thich-ban` | `liked_me` |
| Hàng 2, trái | **Ghép đôi** | `ghep-doi` | `matches` |
| Hàng 2, phải | **Đã xem** | `da-xem` | `viewers` |

**Đã xem là những người đã xem hồ sơ của mình**, không phải lịch sử mình xem người khác.

### Thanh chọn tab

- Dùng lưới **2 cột × 2 hàng**, không vuốt ngang. Mỗi ô có nhãn, tổng số và badge số mới chưa xem nếu có.
- Gap 8; mỗi ô cao tối thiểu 44, padding dọc 8/ngang 10, bo 14.
- Tab đang chọn dùng gradient thương hiệu đỏ/hồng và chữ trắng như style hiện tại; tab còn lại nền trắng.
- Badge mới tối đa hiển thị `99+ mới`; giá trị 0 thì ẩn. Tổng số và số chưa xem là hai dữ liệu khác nhau.
- Khi đổi tab, giữ màn và cập nhật nội dung tại chỗ. Deep link/tải lại phải mở đúng tab, mặc định `ban-thich`.
- Trên web, bố cục này áp dụng ở chiều rộng ≤720px; desktop giữ nhãn đầy đủ.

### Danh sách thẻ — áp dụng giống nhau cho cả bốn tab

Lấy mẫu thẻ của trang **Hẹn hò**. Một hàng có ảnh vuông bên trái, thông tin bên phải, xếp dọc một cột.

- Danh sách và thẻ chiếm **100% chiều rộng vùng nội dung** trên mobile, chỉ giữ lề trang nhỏ (web 12 mỗi bên).
- **Bỏ khung trắng bao ngoài danh sách**, tiêu đề lặp lại như “Bạn đã thích”, đoạn giải thích và link “Xem chi tiết” từng nhóm.
- Giữ tiêu đề chung “Quan tâm & ghép đôi”, mô tả chung và nút “Gợi ý hôm nay”.
- **Không render nút nào trên thẻ**: Nhắn tin, Xem hồ sơ, Hồ sơ, Thích, Thả tim, Thích lại, Bỏ qua… đều bỏ ở cả bốn tab.
- Chạm vào thẻ/ảnh/tên để mở preview hồ sơ bằng luồng hiện có. Chức năng tương tác được thực hiện khi mở hồ sơ.
- Ẩn nhãn số xu, icon khóa và nhãn “Đã mở” trên thẻ danh sách.
- Hồ sơ đang bật phí xem vẫn có **viền vàng + nền vàng nhạt**, kể cả đã mua quyền xem. Web dùng `profile_access_card(...).charge` để quyết định; đây không phải nhãn VIP.
- Ẩn nhãn khóa không đồng nghĩa mở quyền xem. Nội dung chi tiết vẫn theo quyền do server trả về; preview/mua quyền xem dùng luồng hiện tại.
- Tên dài phải xuống dòng, không làm tràn ngang. Giữ trạng thái rỗng phù hợp từng tab nếu không có hồ sơ.

### Thông số thẻ tham chiếu

| Thành phần | Mobile web |
|---|---|
| Gap giữa thẻ | 10 |
| Padding thẻ / gap ảnh–thông tin | 10 / 10 |
| Thẻ thường | Viền `#e7e8ec`, nền trắng, bo 14 |
| Thẻ bật phí | Viền `#edc15b`, nền `#fffaf0`, bo 16 |
| Ảnh | Khung vuông rộng 96, bo 12, ảnh crop cover |
| Tên | 16, đậm |
| Khu vực / tuổi | Icon vị trí hoặc lịch + chữ, cỡ 12 |
| Tuổi | Dòng riêng, dạng `29 Tuổi`; không ghép vào tên |
| Thông tin phụ | Tình trạng hôn nhân, chiều cao/cân nặng nếu được phép và có dữ liệu |
| Chip thông tin phụ | Nền `#f1f3f5`, cỡ 11, padding 3 × 7, bo 8 |

Không hiển thị bio/sở thích trên các thẻ này. Thích bạn có thể có dòng thời gian “Thích bạn …”; Đã xem có thể có “Đã xem …” khi API cho phép. Không giả lập dữ liệu thiếu.

Nguồn: [màn Quan tâm](application/views/account/likes.php), [thẻ dùng chung](application/views/members/_card.php), [CSS Quan tâm](assets/site/css/account/likes.css), [CSS thẻ Hẹn hò](assets/site/css/dating-discovery.css).

## 4. Tìm kiếm header

Giao diện mobile theo mẫu thanh tìm trong Tin nhắn:

- Thanh nền hồng `#ffeaed`, rộng màn hình, padding dọc 5/ngang 12, gap 8.
- Trái: mũi tên quay lại để **đóng tìm kiếm**, không submit.
- Giữa: ô nhập nền trắng, cao 36, bo 10, icon kính lúp bên trái; chữ 16.
- Phải: **giữ nút Tìm**, nền đỏ, chữ trắng, cao tối thiểu 36, bo 10.
- Placeholder: **Nhập tên hoặc số điện thoại...**
- Cho nhập chữ và số, không ép bàn phím điện thoại; giữ nút tìm/submit trên bàn phím phù hợp nền tảng.
- Nút Tìm gửi truy vấn và mở danh sách kết quả. Mũi tên đóng và thao tác submit là hai hành vi riêng.

Tìm được theo **tên hiển thị, biệt danh hoặc số điện thoại**, dùng điều kiện OR trong cùng nhóm và vẫn kết hợp các điều kiện lọc/hiển thị khác. Không thêm tìm email. Web dùng `LIKE` với từ khóa đã trim; không tự khẳng định hỗ trợ tìm tên không dấu vì còn phụ thuộc collation database.

API hiện dùng **`GET /api/members?q=...`**; truyền query bằng URL encoding, không tự nối chuỗi tên/số điện thoại vào URL. Endpoint dùng chung `M_user::build_search()` với web nên thay đổi tìm tên/biệt danh/số điện thoại đã áp dụng ở model server. Không cần một endpoint tìm kiếm mới.

Placeholder ở tìm kiếm Trang chủ và Hẹn hò cũng đã đổi đồng bộ.

Nguồn: [layout header](application/views/layouts/main.php), [CSS header](assets/site/css/style.css), [JS đóng/mở](assets/site/js/app.js), [model tìm kiếm](application/models/M_user.php), [API thành viên](application/controllers/api/Members.php).

## 5. Menu mobile

Drawer trượt từ phải, rộng tham chiếu `min(360px, 90% màn hình)`, nội dung cuộn được trên máy thấp và khi phóng to chữ.

- Nền chuyển nhẹ từ `#fff7f9` sang trắng; header gradient `#fff1f5` → `#ffe2e9`.
- Header có nhãn “SAIGON CUPID”, tiêu đề **Cùng khám phá**, mô tả “Kết nối và tìm người hợp gu”, nút đóng tròn bên phải.
- Giữ ô chuỗi hoạt động và logic hiển thị hiện tại.
- Mỗi mục có icon nằm trong ô hồng nhạt 34 × 34, bo 11; icon 19; nhãn ở giữa; mũi tên bên phải.
- Hàng cao tối thiểu 52, padding 8 × 12, gap 12, bo 14.
- Mục đang chọn: gradient `#d1273f` → `#e55479`, chữ/icon trắng, bóng nhẹ; các mục khác màu trung tính.
- Trước Sforum có nhãn nhóm **Thông tin & tiện ích**.

| Mục | Icon tham chiếu | Đích web |
|---|---|---|
| Trang chủ | home | `/` |
| Hẹn hò | match | `/hen-ho` |
| Tâm sự | heart | `/tam-su` |
| Clubs Chat's | message | `/clubs` |
| Ghép đôi ẩn | sparkles | `/swipe-match` |
| Khu vực | pin | `/khu-vuc` |
| Sforum | dashboard | `/tin-tuc` |
| Thông báo | bell | `/noi-quy` |
| Download Apps | download | Giữ luồng cài đặt của web |

Menu “Thông báo” trên web hiện dẫn `/noi-quy`, không tự đổi sang hộp notification của app. Mục Download Apps dành cho web; bản native có thể bỏ mục cài chính app nếu không có đích phù hợp. Đây là đề xuất thích ứng nền tảng, không phải thay đổi đã làm trên app.

Khối tài khoản nằm dưới cùng, nền `#fff8fa`. **Tài khoản và Đăng xuất cùng chiều rộng, cao 48, bo 12, font 14/600**, căn giữa. Tài khoản VIP vẫn giữ màu vàng/ảnh và badge hiện tại. Với khách, Đăng nhập/Đăng ký dùng cùng kích thước.

Nguồn: [CSS drawer](assets/site/css/mobile-drawer.css), [markup menu](application/views/layouts/main.php). Web dùng icon SVG từ `tk_icon()`; app dùng bộ icon native tương đương.

## 6. Icon màn hình chính

Đã thay bộ icon dùng khi thêm web vào màn hình chính bằng ảnh **nền hồng kín mép, không trong suốt, không bo góc ngoài sẵn**, để tránh viền đen. Hệ điều hành tự áp mask.

| Asset | Kích thước / vai trò |
|---|---|
| `assets/site/img/home-screen-512x512.png` | 512 × 512, PNG RGB không alpha |
| `assets/site/img/home-screen-192x192.png` | 192 × 192, PNG RGB không alpha |
| `assets/site/img/apple-touch-icon-opaque.png` | 180 × 180, Apple touch icon |

[Manifest](manifest.webmanifest) khai báo Android `purpose: "any maskable"`. Layout trỏ Apple touch icon mới và đổi version theo hash. Icon cũ cài trên điện thoại có thể cần xóa rồi thêm lại.

Với native: dùng mẫu này làm tham chiếu. Nếu pipeline yêu cầu icon 1024 hoặc adaptive foreground/background riêng, xuất đúng chuẩn build app từ asset nguồn phù hợp; không mặc định các kích thước PWA trên thay được toàn bộ cấu hình Expo. Không đưa sẵn viền đen/khoảng trong suốt/bo góc ngoài vào icon iOS.

## 7. API cho Quan tâm và các điểm cần dev API xử lý

### Endpoint hiện có

- `GET /api/account/likes?tab=ban-thich|thich-ban|ghep-doi|da-xem`: yêu cầu đăng nhập; trả `tab`, `is_vip`, `totals`, `unread`, `my_likes`, `liked_me`, `matches`, `viewers`. GET này đã đánh dấu tab được yêu cầu là đã xem.
- `POST /api/account/likes/seen` với `{ "tab": "thich-ban" }`: khi app đổi tab bằng dữ liệu đã tải, đánh dấu tab tương ứng đã xem rồi đồng bộ số đếm.
- `GET /api/members/{slug}/preview`: preview hồ sơ.
- `POST /api/members/{slug}/purchase`: dùng luồng mua quyền xem hiện có; tham khảo [API.md](API.md) và controller thực tế cho payload/version, không tự mở quyền tại client.

### Hai khác biệt đã kiểm tra trong source, chưa được sửa trong đợt UI này

1. **Che danh sách theo VIP:** `api/Account::likes()` vẫn trả `locked: true, id: null, name: null` từ hồ sơ thứ ba ở `liked_me` và `viewers` khi chưa VIP. Web hiện render danh sách trực tiếp. Vì vậy chỉ sửa UI app sẽ chưa tạo được toàn bộ trải nghiệm giống web. Cần thống nhất với backend việc giữ hay bỏ cơ chế che này; app không tự suy ra dữ liệu đã bị server ẩn. Phân biệt việc này với phí mở chi tiết một hồ sơ.
2. **Thiếu trạng thái để vẽ viền vàng:** serializer `ho_so()` hiện chưa trả `charge_enabled` cho danh sách; `detail_locked` không thay thế được vì hồ sơ đã mua vẫn cần viền vàng. Đề nghị backend bổ sung một boolean rõ nghĩa như `charge_enabled` hoặc `is_profile_paid` vào từng hồ sơ danh sách. Đây là **đề xuất hợp đồng cần triển khai**, chưa phải field đã có. Dữ liệu tình trạng hôn nhân/cân nặng cũng chưa có trong serializer danh sách hiện tại; muốn khớp chip trên web phải bổ sung đúng quyền xem, hoặc tạm ẩn khi thiếu.

Không dùng `is_vip` hay `locked` để đoán hồ sơ bật thu phí. Field null/thiếu thì không render chi tiết giả và không tự bỏ kiểm tra quyền xem.

Nguồn kiểm tra: [API Quan tâm](application/controllers/api/Account.php), [serializer hồ sơ](application/core/MY_Controller.php), [helper quyền xem](application/helpers/app_helper.php).

## 8. Checklist nghiệm thu cho app

- [ ] Khám phá là màn riêng; header và safe area trên đỏ, nền dưới hồng; không còn màn đỏ toàn bộ.
- [ ] Hàng Khám phá gọn; đăng nhập thấy 4 mục, khách thấy 2 mục; điều hướng đúng tab đích.
- [ ] Quan tâm có 4 tab theo lưới 2 × 2; đủ nhãn/tổng số/badge mới, không phải vuốt ngang ở màn 320–430 đơn vị rộng.
- [ ] Cả 4 danh sách dùng cùng mẫu thẻ, rộng hết vùng nội dung, không có khung nhóm/tiêu đề nhóm/mô tả nhóm.
- [ ] Không có nút thao tác hoặc nhãn xu/khóa/Đã mở trên thẻ. Chạm thẻ vẫn mở preview.
- [ ] Hồ sơ miễn phí, bật phí chưa mua, bật phí đã mua đều đúng viền; ẩn nhãn không làm lộ nội dung bị giới hạn.
- [ ] Tab rỗng có thông báo phù hợp; tên dài/font lớn không tràn ngang; thẻ cuối không bị bottom nav che.
- [ ] Tìm bằng tên, biệt danh, số điện thoại đều chạy; có nút Tìm và nút đóng riêng; bàn phím gõ được chữ.
- [ ] Drawer có icon, trạng thái active, nhóm tiện ích; Tài khoản/Đăng xuất cùng hình dáng; logout giữ luồng xác thực hiện có.
- [ ] Icon iOS/Android không có viền đen và không bị cắt logo khi hệ điều hành áp mask.
- [ ] Đã giải quyết hoặc ghi nhận rõ hai khác biệt API ở mục 7 trước khi nghiệm thu khớp web.

## 9. Kiểm tra đã thực hiện phía web

- Kiểm tra cú pháp PHP/JavaScript cho các file thay đổi và `git diff --check` trong quá trình sửa.
- `php tests/interest_cards_render.php`: kiểm tra thẻ miễn phí/có phí/đã mua, ẩn giá, viền vàng, quyền chi tiết và cả 4 biến thể không còn nút thao tác.
- Hai test rộng hơn đã gặp lỗi fixture trong quá trình làm: `tests/public_profile_render.php` thiếu helper `profile_frame_catalog()`; `tests/dating_discovery.php` thiếu `render_member_list()` trong controller giả. Chưa dùng hai test này làm bằng chứng nghiệm thu; kiểm tra app native và thiết bị thật vẫn cần dev app thực hiện.
