# Cập nhật SEO theo audit ngày 29/09/2026

## Đã sửa trong mã nguồn

- Cho phép index trang chủ, hẹn hò/tâm sự, cẩm nang, giới thiệu, liên hệ, nội quy, điều khoản, bảo mật và an toàn. Hồ sơ, tìm kiếm, hub thành viên/khu vực và các tỉnh ít nội dung dùng `noindex, follow`.
- Canonical không chứa query; trang phân trang có canonical riêng và tiêu đề có số trang.
- Organization, WebSite, CollectionPage, BlogPosting, AboutPage, ContactPage và BreadcrumbList; OG/Twitter dùng ảnh bài viết hoặc logo hiện có. Không thêm tác giả, đánh giá hoặc tài khoản xã hội chưa được xác minh.
- Sitemap thêm trang chủ/bảo mật/an toàn, loại URL noindex và URL trùng, bỏ priority/changefreq và lastmod giả. Bài chưa đến ngày xuất bản không được đưa vào sitemap hoặc hiển thị công khai.
- Link xã hội chỉ hiển thị URL HTTPS đúng tên miền được cấu hình; placeholder tự ẩn. Sửa nhãn link footer.
- Trang `/bao-mat`, `/an-toan`, `/lien-he` có nội dung và đường dẫn riêng, hoạt động không phụ thuộc bản ghi pages. Nội dung liên hệ dùng cấu hình doanh nghiệp và bỏ email/hotline placeholder.
- Bộ lọc hồ sơ hoàn chỉnh loại ngày sinh chưa đủ 18 tuổi ở danh sách và gợi ý. Bio trên thẻ không hiển thị với khách. Đăng ký đã có kiểm tra 18 tuổi trên máy chủ.
- Bỏ số thành viên/cặp đôi tối thiểu giả và đánh giá 4.8/5; bỏ lời hứa bảo mật tuyệt đối.
- Bỏ CSS nạp hai lần, cho phép zoom, defer script ngoài, thêm H1 cho khám phá và sửa avatar chat có src rỗng.
- Apache: redirect 301 `/index.php` và dấu `/` cuối URL ứng dụng.

## Cần thực hiện khi triển khai

1. Trong Quản trị → Cấu hình, tắt **Chặn Google lập chỉ mục** (`site_noindex=0`). Nếu bật, robots động hoặc robots do quản trị ghi lại vẫn chặn crawl. Kiểm tra `/robots.txt` thực tế sau khi lưu.
2. Site dùng nginx: chuyển quy tắc redirect trong `.htaccess` sang cấu hình nginx. Thêm DNS/certificate cho www rồi 301 về domain chính. Không redirect query tracking; canonical đã loại query.
3. Kiểm tra thông tin doanh nghiệp, địa chỉ, email/hotline và URL mạng xã hội trong quản trị. Trang liên hệ có giá trị dự phòng từ audit, không tự xác nhận chúng đã hoạt động.
4. Rà soát chính sách bảo mật với người phụ trách vận hành/pháp lý: thời hạn lưu, đơn vị xử lý, quyền dữ liệu và quy trình xóa cần phản ánh hoạt động thực tế. Nội dung mới chưa phải xác nhận tuân thủ pháp luật.
5. Kiểm tra dữ liệu thực tế để xử lý tài khoản khai tuổi thật dưới 18 trong bio. Bộ lọc ngày sinh không phát hiện người khai sai tuổi. Blocklist/queue kiểm duyệt tên và bio chưa được triển khai; chức năng report và quản trị báo cáo đã có sẵn.
6. Các tỉnh hiện đều noindex. Cần biên tập nội dung địa phương và kiểm tra thành viên thật trước khi bật index và thêm vào sitemap; không tạo số liệu địa phương giả.
7. Chưa viết lại toàn bộ bài blog, giới thiệu/nội quy/điều khoản trong database. Cần tác giả, lịch sử doanh nghiệp, nguồn tham khảo và nội dung được chủ website xác nhận. Ảnh chia sẻ 1200×630, logo nhỏ và thumbnail WebP còn cần chuẩn bị; metadata hiện dùng ảnh có sẵn.
8. Không bật cache HTML công khai vì trang có phiên đăng nhập, CSRF và nội dung cá nhân. Tối ưu session/chat khách cần thay đổi luồng riêng và kiểm tra chức năng. Chưa minify toàn bộ CSS, thêm kích thước mọi ảnh, CSP, IndexNow hay llms.txt.
9. Xác minh và gửi sitemap trong Search Console/Bing bằng tài khoản chủ sở hữu. Công việc ngoài mã nguồn như backlink, mạng xã hội, theo dõi và audit production chưa thực hiện.

## Kiểm tra

Chạy `php tests/seo_metadata.php` để kiểm tra index/noindex, lọc URL xã hội, schema an toàn và metadata; chạy các test hồi quy hiện có. Chạy lint PHP cho các file sửa. Những kiểm tra này không thay thế crawl/render website production, DNS hoặc kiểm tra dữ liệu thật.
