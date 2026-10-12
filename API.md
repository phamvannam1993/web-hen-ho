# API cho ứng dụng di động

Ứng dụng Expo ở dự án `app-hen-ho` dùng toàn bộ API dưới đây. Mọi luật nghiệp vụ
(ghép đôi, chặn, VIP, xác thực email…) nằm ở các model dùng chung với web, nên
web và ứng dụng luôn chạy cùng một luật.

Tất cả trả về JSON. Thành công có `"ok": true`, lỗi có `"ok": false` kèm `message`.
Thân yêu cầu nhận JSON hoặc form; gửi ảnh thì dùng `multipart/form-data`.

## Xác thực

Đăng nhập (hoặc đăng ký) một lần lấy token, các lần sau gửi kèm header:

    Authorization: Bearer <token>

Token sống **90 ngày**. Máy chủ **chỉ lưu bản băm** của token — rò cơ sở dữ liệu
cũng không ai đăng nhập hộ được.

Một số màn khách cũng xem được (trang chủ, khám phá, danh sách, trang cá nhân,
phòng chat chung). Ở đó token là **không bắt buộc**: không gửi thì là khách, gửi
token hỏng thì vẫn nhận **401** để ứng dụng biết mà đăng nhập lại.

| Phương thức | Đường dẫn | Việc |
|---|---|---|
| POST | `/api/auth/login` | `{identity, password, device}` → `token`, `expires_at`, `user`, `email_verified` |
| POST | `/api/auth/register` | `{display_name, email, password, gender, birthday (Y-m-d), agree, device}` → như login, mã **201** |
| POST | `/api/auth/logout` | Thu hồi token đang dùng |
| GET  | `/api/auth/me` | Hồ sơ, xu, VIP, chuỗi, mục hồ sơ còn thiếu, số chưa đọc (`counts`) |
| POST | `/api/auth/resend-verify` | Gửi lại thư xác thực (chặn bấm liên tục: **429** kèm `wait_seconds`) |
| POST | `/api/auth/forgot` | `{email}` — gửi link đặt lại mật khẩu (người dùng đặt lại trên web) |
| POST | `/api/auth/password` | `{current, password}` — đổi mật khẩu |

Email chưa xác thực **vẫn đăng nhập được**, giống web — chỉ thả tim, nhắn tin và
bình luận bị chặn bằng **403** kèm `"need_verify": true` cho tới khi bấm link
trong thư. Đăng ký lỗi kiểm tra dữ liệu trả **422** kèm `errors` theo từng trường.

---

## Danh mục & trang chủ

| Phương thức | Đường dẫn | Việc |
|---|---|---|
| GET | `/api/meta` | Tỉnh thành, sở thích, nghề nghiệp, nhãn các trường chọn, thông tin site |
| GET | `/api/home` | Thống kê, gợi ý hôm nay, gợi ý ghép đôi, 5 người đã thích bạn, thành viên nổi bật |

## Khám phá (vuốt)

| Phương thức | Đường dẫn | Việc |
|---|---|---|
| GET  | `/api/discover` | `?view=male\|female\|gay\|les` (khách) `&province_id &age_min &age_max &page` |
| POST | `/api/discover/{id}/like` | Thích / bỏ thích — `matched: true` kèm `partner` khi hai bên cùng thích |
| POST | `/api/discover/{id}/pass` | Bỏ qua, lần sau không gợi ý lại |
| POST | `/api/discover/undo` | Hoàn tác lượt bỏ qua gần nhất, trả lại `profile` |

## Thành viên & trang cá nhân

| Phương thức | Đường dẫn | Việc |
|---|---|---|
| GET  | `/api/members` | Danh sách có lọc (xem dưới), phân trang 24 người |
| GET  | `/api/members/{id\|slug}` | Trang cá nhân: hồ sơ đầy đủ, ảnh, bình luận, đã thích / đã ghép đôi |
| POST | `/api/members/{id}/like` | Thích / bỏ thích |
| POST | `/api/members/{id}/contact` | Mở số điện thoại — trừ `unlock_cost` xu (VIP miễn phí). Thiếu xu: **409** + `need_coins` |
| POST | `/api/members/{id}/comment` | `{content, parent_id}` + ảnh `image` |
| POST | `/api/comments/{id}/delete` | Xoá bình luận (tác giả hoặc chủ trang) |
| POST | `/api/members/{id}/block` · `/unblock` | Chặn / bỏ chặn |
| POST | `/api/report` | `{target_type: user\|comment\|message, target_id, reason, note}` |

Bộ lọc `/api/members`: `tab=nam|nu|gay|les` (như `/hen-ho/nam`, `/tam-su/gay`),
`sort=active|new|verified|listened|vip`, `q`, `gender`, `province_id`, `age_min`,
`age_max`, `height_min`, `height_max`, `marital`, `education`, `smoking`,
`drinking`, `has_children`, `online=1`, `vip=1`, `topic` (chủ đề tâm sự),
`interests[]`, `page`.

Xem trang cá nhân khi đã đăng nhập sẽ ghi một lượt "ai đã xem hồ sơ", như web.

---

## Gợi ý mỗi ngày

| Phương thức | Đường dẫn | Việc |
|---|---|---|
| GET  | `/api/daily-match/today` | Gợi ý hôm nay. Chưa có thì hệ thống chốt luôn |
| POST | `/api/daily-match/{id}/like` | Thả tim |
| POST | `/api/daily-match/{id}/skip` | Bỏ qua |
| GET  | `/api/daily-match/history` | Lịch sử đã nhận |

`today` trả `match: null` kèm `message` khi hôm nay không tìm được ai phù hợp.
Trả lời lại một gợi ý đã trả lời hoặc đã hết hạn sẽ nhận **409**.

---

## Ai đã thích bạn

| Phương thức | Đường dẫn | Việc |
|---|---|---|
| GET  | `/api/who-liked-me` | Danh sách |
| GET  | `/api/who-liked-me/count` | Chỉ lấy số lượng |
| POST | `/api/who-liked-me/{id}/like` | Thích lại → ghép đôi |
| POST | `/api/who-liked-me/{id}/skip` | Bỏ qua (người gửi không được báo) |

Bản thường chỉ thấy rõ **2 người đầu**; những người còn lại trả về
`"locked": true` với mọi trường bằng `null`. Việc che làm ở **máy chủ**, không
phải chỉ làm mờ ở giao diện — đọc thẳng JSON cũng không lấy được thông tin.

Thích lại không đòi VIP (giống nút "Thích lại" trên web) — bản thường chỉ biết
id của người không bị che nên cũng chỉ thích lại được những người đó. Chỉ cần đã
xác thực email.

---

## Ai đã xem hồ sơ

| Phương thức | Đường dẫn | Việc |
|---|---|---|
| GET | `/api/profile-viewers` | Danh sách 7 ngày gần nhất |
| GET | `/api/profile-viewers/count` | Chỉ lấy số lượng |

Cùng quy tắc che như trên. VIP có thêm trường `viewed_ago` ("2 giờ trước").

---

## Chuỗi hoạt động

| Phương thức | Đường dẫn | Việc |
|---|---|---|
| GET  | `/api/streak` | Chuỗi hiện tại, kỷ lục, huy hiệu, mốc kế tiếp, toàn bộ mốc (`milestones`) |
| POST | `/api/streak/freeze` | Dùng lượt giữ chuỗi. Gửi `{"buy": 1}` để mua thêm (30 xu) |

Mỗi lần gọi API có token hợp lệ đều **tự chấm công chuỗi**, giống như vào web.

---

## Tài khoản

| Phương thức | Đường dẫn | Việc |
|---|---|---|
| GET  | `/api/account` | Trang Tổng quan: số liệu, hoạt động gần đây, gợi ý |
| GET  | `/api/account/profile` | Dữ liệu form hồ sơ + tiêu chí ghép đôi + id sở thích |
| POST | `/api/account/profile` | Lưu cả form (JSON). Bắt buộc như web: `display_name, gender, birthday, seeking_gender, purpose` |
| POST | `/api/account/avatar` | Ảnh đại diện — multipart, trường `avatar` |
| POST | `/api/account/onboarding` | Bước "Bắt đầu": `province_id`, `bio`, ảnh `avatar` — việc nào cũng được |
| GET  | `/api/account/photos` | Album ảnh (kèm trạng thái duyệt) |
| POST | `/api/account/photos` | Thêm ảnh — multipart, trường `photo` |
| POST | `/api/account/photos/{id}/delete` | Xoá ảnh |
| GET  | `/api/account/likes` | Đã ghép đôi, bạn đã thích, tổng người thích bạn / xem hồ sơ |
| GET  | `/api/account/notifications` | 50 thông báo gần nhất — mở là đánh dấu đã đọc hết, như web |
| POST | `/api/account/notifications/read` | Đánh dấu đã đọc hết |
| GET  | `/api/account/wallet` | Số dư, gói, đơn nạp, biến động xu, thông tin chuyển khoản |
| POST | `/api/account/wallet` | `{package_id, method}` — tạo đơn chuyển khoản, trả về `order` |
| GET/POST | `/api/account/email-prefs` | Cài đặt email; gửi `{"unsubscribe_all": 1}` để huỷ nhận hết |

## Tin nhắn

| Phương thức | Đường dẫn | Việc |
|---|---|---|
| GET  | `/api/conversations` | Hội thoại + `new_matches` (đã ghép đôi nhưng chưa nhắn) |
| POST | `/api/conversations/open/{user_id}` | Mở / tạo hội thoại — chỉ khi đã ghép đôi (**403** + `need_match`) |
| GET  | `/api/conversations/{id}?after=` | Lần đầu: 50 tin gần nhất + `partner`, `can_send`. Sau đó chỉ tin mới hơn `after` |
| POST | `/api/messages` | `{receiver_id, content}` + ảnh `image` |
| GET  | `/api/room?after=` | Phòng chat chung (khách xem được) |
| POST | `/api/room/send` | `{content}` + ảnh `image`. Tối đa 15 tin/phút (**429**) |
| GET  | `/api/realtime` | Địa chỉ + mã WebSocket nếu máy chủ realtime đang bật |

Tin mới tới tức thời qua WebSocket (`GET /api/realtime` cấp địa chỉ + mã; máy
chủ ở thư mục `websoket/`, sự kiện xem `websoket/README.md`). Khi WebSocket không
dùng được, ứng dụng quay về hỏi mỗi 4 giây bằng `?after=<id tin cuối>`. Mở hội
thoại là đánh dấu đã đọc phần của đối phương, như web — người gửi nhận
`chat.seen` ngay.

`POST /api/realtime/read` chỉ dành cho máy chủ Node (header `X-Realtime-Secret`).

---

## Mã lỗi

| Mã | Nghĩa |
|---|---|
| 401 | Thiếu token, token sai hoặc đã hết hạn / bị thu hồi |
| 403 | Tài khoản bị khoá, chưa xác thực email (`need_verify`), chưa ghép đôi (`need_match`), bị chặn |
| 404 | Không tìm thấy thành viên / gợi ý |
| 409 | Thao tác không còn hợp lệ (đã trả lời rồi, hết hạn, không đủ xu…) |
| 422 | Thiếu / sai tham số (kèm `errors` theo từng trường khi có) |
| 429 | Gửi quá nhanh |

## Ví dụ

    # Đăng nhập
    curl -X POST https://saigoncupid.com/api/auth/login \
         -H "Content-Type: application/json" \
         -d '{"identity":"a@b.com","password":"...","device":"iPhone 15"}'

    # Endpoint cần đăng nhập
    curl https://saigoncupid.com/api/daily-match/today \
         -H "Authorization: Bearer <token>"

    # Gửi ảnh trong tin nhắn
    curl -X POST https://saigoncupid.com/api/messages \
         -H "Authorization: Bearer <token>" \
         -F receiver_id=12 -F content="Chào bạn" -F image=@anh.jpg

## Ghi chú

- Token gắn vào `$this->auth` (Userauth::dung_cho_api) cho mỗi yêu cầu, nên mọi
  model đang hỏi `$this->auth->is_vip()`, `->id()`… chạy đúng như trên web mà
  không cần phiên đăng nhập.
- API mở CORS cho mọi tên miền (`Access-Control-Allow-Origin: *`) vì ứng dụng
  chạy ở tên miền khác. Token nằm trong header chứ không phải cookie nên không
  bị tấn công CSRF.
- Chưa có giới hạn số lần gọi (rate limit) chung. Nếu mở API ra công khai thì nên
  thêm ở tầng máy chủ web hoặc CDN.
