# API cho ứng dụng di động

Tất cả trả về JSON. Thành công có `"ok": true`, lỗi có `"ok": false` kèm `message`.

## Xác thực

Đăng nhập một lần lấy token, các lần sau gửi kèm header:

    Authorization: Bearer <token>

Token sống **90 ngày**. Máy chủ **chỉ lưu bản băm** của token — rò cơ sở dữ liệu
cũng không ai đăng nhập hộ được.

### POST /api/auth/login

    { "identity": "email hoặc số điện thoại", "password": "...", "device": "iPhone 15" }

Trả về `token`, `expires_at`, `user`.

Email chưa xác thực thì trả **403** kèm `"need_verify": true` — ứng dụng phải
đưa người dùng qua bước nhập mã OTP trước.

### POST /api/auth/logout

Thu hồi token đang dùng. Dùng lại token đó sẽ nhận **401**.

### GET /api/auth/me

Trả về hồ sơ, số xu, trạng thái VIP và chuỗi ngày hiện tại.

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
| POST | `/api/who-liked-me/{id}/like` | Thả tim lại — **chỉ VIP** |
| POST | `/api/who-liked-me/{id}/skip` | Bỏ qua |

Bản thường chỉ thấy rõ **2 người đầu**; những người còn lại trả về
`"locked": true` với mọi trường bằng `null`. Việc che làm ở **máy chủ**, không
phải chỉ làm mờ ở giao diện — đọc thẳng JSON cũng không lấy được thông tin.

Thả tim lại khi chưa VIP nhận **403** kèm `"need_vip": true`.

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
| GET  | `/api/streak` | Chuỗi hiện tại, kỷ lục, huy hiệu, mốc kế tiếp |
| POST | `/api/streak/freeze` | Dùng lượt giữ chuỗi. Gửi `{"buy": 1}` để mua thêm (30 xu) |

Mỗi lần gọi API có token hợp lệ đều **tự chấm công chuỗi**, giống như vào web.

---

## Mã lỗi

| Mã | Nghĩa |
|---|---|
| 401 | Thiếu token, token sai hoặc đã hết hạn / bị thu hồi |
| 403 | Tài khoản bị khoá, chưa xác thực email, hoặc cần VIP |
| 409 | Thao tác không còn hợp lệ (đã trả lời rồi, hết hạn, không đủ xu…) |
| 422 | Thiếu tham số bắt buộc |

## Ví dụ

    # Đăng nhập
    curl -X POST https://saigoncupid.com/api/auth/login \
         -H "Content-Type: application/json" \
         -d '{"identity":"a@b.com","password":"...","device":"iPhone 15"}'

    # Gọi endpoint cần đăng nhập
    curl https://saigoncupid.com/api/daily-match/today \
         -H "Authorization: Bearer <token>"

## Ghi chú

- API mở CORS cho mọi tên miền (`Access-Control-Allow-Origin: *`) vì ứng dụng
  chạy ở tên miền khác. Token nằm trong header chứ không phải cookie nên không
  bị tấn công CSRF.
- Chưa có giới hạn số lần gọi (rate limit). Nếu mở API ra công khai thì nên
  thêm ở tầng máy chủ web hoặc CDN.
