-- =====================================================================
-- DỮ LIỆU KHỞI TẠO
-- Tài khoản quản trị: admin@henho24.local / admin123
-- =====================================================================
USE `web_hen_ho`;
SET NAMES utf8mb4;

/* ------------------------- Cấu hình ------------------------- */
INSERT INTO `settings` (`key`,`value`,`group`) VALUES
('site_name','Saigon Cupid','general'),
('site_slogan','Hẹn hò kết bạn nghiêm túc, chia sẻ buồn vui','general'),
('site_desc','Website kết bạn, hẹn hò nghiêm túc dành cho người Việt trên toàn quốc.','general'),
('hotline','0900 000 000','contact'),
('contact_email','support@henho24.local','contact'),
('auto_approve_user','1','moderation'),
('auto_approve_post','0','moderation'),
('post_expire_days','30','moderation'),
('unlock_cost','20','coin'),
('signup_bonus_coin','50','coin'),
('bank_info','Vietcombank - 0123456789 - CTY HENHO24','payment'),
('site_noindex','1','seo'),
('home_title','Saigon Cupid - Cộng đồng tìm kiếm đối tượng hẹn hò và bạn bè','seo')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

/* Tỉnh thành: 34 đơn vị hành chính theo sắp xếp có hiệu lực 01/07/2025 */
INSERT INTO `provinces` (`name`,`slug`,`region`,`sort`) VALUES
('Hà Nội','ha-noi','bac',10),
('TP Hồ Chí Minh','tp-ho-chi-minh','nam',20),
('Hải Phòng','hai-phong','bac',30),
('Đà Nẵng','da-nang','trung',40),
('Huế','hue','trung',50),
('Cần Thơ','can-tho','nam',60),
('Quảng Ninh','quang-ninh','bac',70),
('Bắc Ninh','bac-ninh','bac',80),
('Hưng Yên','hung-yen','bac',90),
('Ninh Bình','ninh-binh','bac',100),
('Phú Thọ','phu-tho','bac',110),
('Thái Nguyên','thai-nguyen','bac',120),
('Lào Cai','lao-cai','bac',130),
('Tuyên Quang','tuyen-quang','bac',140),
('Cao Bằng','cao-bang','bac',150),
('Lạng Sơn','lang-son','bac',160),
('Sơn La','son-la','bac',170),
('Điện Biên','dien-bien','bac',180),
('Lai Châu','lai-chau','bac',190),
('Thanh Hóa','thanh-hoa','trung',200),
('Nghệ An','nghe-an','trung',210),
('Hà Tĩnh','ha-tinh','trung',220),
('Quảng Trị','quang-tri','trung',230),
('Quảng Ngãi','quang-ngai','trung',240),
('Gia Lai','gia-lai','trung',250),
('Đắk Lắk','dak-lak','trung',260),
('Khánh Hòa','khanh-hoa','trung',270),
('Lâm Đồng','lam-dong','trung',280),
('Đồng Nai','dong-nai','nam',290),
('Tây Ninh','tay-ninh','nam',300),
('Vĩnh Long','vinh-long','nam',310),
('Đồng Tháp','dong-thap','nam',320),
('An Giang','an-giang','nam',330),
('Cà Mau','ca-mau','nam',340);

/* ------------------------- Danh mục tin ------------------------- */
INSERT INTO `categories` (`id`,`parent_id`,`type`,`name`,`slug`,`sort`) VALUES
(1,NULL,'post','Máy bay bà già','may-bay-ba-gia',1),
(2,NULL,'post','Tìm bạn trai','tim-ban-trai',2),
(3,NULL,'post','Tìm bạn gái','tim-ban-gai',3),
(4,NULL,'post','Tìm bạn tình','tim-ban-tinh',4);

INSERT INTO `categories` (`parent_id`,`type`,`name`,`slug`,`sort`) VALUES
-- con của "Máy bay bà già"
(1,'post','Máy bay bà già Hà Nội','may-bay-ba-gia-ha-noi',1),
(1,'post','Máy bay bà già TpHCM','may-bay-ba-gia-tphcm',2),
(1,'post','Máy bay bà già Đà Nẵng','may-bay-ba-gia-da-nang',3),
(1,'post','Máy bay bà già có số điện thoại','may-bay-ba-gia-co-so-dien-thoai',4),
-- con của "Tìm bạn gái"
(3,'post','Tìm bạn gái Hà Nội','tim-ban-gai-ha-noi',1),
(3,'post','Tìm bạn gái TpHCM','tim-ban-gai-tphcm',2),
(3,'post','Tìm bạn gái Đà Nẵng','tim-ban-gai-da-nang',3),
(3,'post','Tìm bạn gái độc thân','tim-ban-gai-doc-than',4),
(3,'post','Tìm bạn gái đã ly dị','tim-ban-gai-da-ly-di',5),
(3,'post','Tìm bạn gái làm quen','tim-ban-gai-lam-quen',6),
(3,'post','Tìm bạn gái để tâm sự','tim-ban-gai-de-tam-su',7),
(3,'post','Tìm bạn gái kết hôn','tim-ban-gai-ket-hon',8),
-- con của "Tìm bạn tình"
(4,'post','Tìm bạn tình Hà Nội','tim-ban-tinh-ha-noi',1),
(4,'post','Tìm bạn tình TpHCM','tim-ban-tinh-tphcm',2),
(4,'post','Tìm bạn tình Đà Nẵng','tim-ban-tinh-da-nang',3),
(4,'post','Tìm bạn tình kín đáo','tim-ban-tinh-kin-dao',4),
(4,'post','Tìm bạn tình không ràng buộc','tim-ban-tinh-khong-rang-buoc',5),
(4,'post','Tìm bạn tình công sở','tim-ban-tinh-cong-so',6);

INSERT INTO `categories` (`type`,`name`,`slug`,`sort`) VALUES
('blog','Cẩm nang hẹn hò','cam-nang-hen-ho',1),
('blog','Tâm sự','tam-su',2),
('blog','Kỹ năng tán tỉnh','ky-nang-tan-tinh',3);

/* ------------------------- Sở thích ------------------------- */
INSERT INTO `interests` (`name`,`slug`) VALUES
('Du lịch','du-lich'),('Cà phê','ca-phe'),('Xem phim','xem-phim'),
('Đọc sách','doc-sach'),('Thể thao','the-thao'),('Nấu ăn','nau-an'),
('Âm nhạc','am-nhac'),('Thú cưng','thu-cung');

/* ------------------------- Tài khoản quản trị ------------------------- */
-- Mật khẩu: admin123
INSERT INTO `users`
(`uuid`,`email`,`password_hash`,`display_name`,`slug`,`gender`,`role`,`status`,`coin_balance`,`email_verified_at`)
VALUES
(UUID(),'admin@henho24.local','$2y$10$0fuAGbWNrjFNEISFcSkG2OgEnguS6MLJSidTD1pBc57lykV.2eY.i',
 'Quản trị viên','quan-tri-vien','other','admin','active',0,NOW());

/* ------------------------- Gói dịch vụ ------------------------- */
INSERT INTO `packages` (`type`,`name`,`price`,`duration_days`,`coin_amount`,`bonus_coin`,`description`,`sort`) VALUES
('coin','Gói 100 xu',20000,NULL,100,0,'Dùng để mở số điện thoại của tin đăng',1),
('coin','Gói 300 xu',50000,NULL,300,50,'Tặng thêm 50 xu',2),
('coin','Gói 700 xu',100000,NULL,700,150,'Tặng thêm 150 xu',3),
('vip','VIP 1 tháng',99000,30,NULL,0,'Xem số điện thoại không giới hạn, ưu tiên hiển thị',4),
('vip','VIP 3 tháng',249000,90,NULL,0,'Tiết kiệm 16% so với gói tháng',5),
('vip','VIP 1 năm',799000,365,NULL,0,'Ưu đãi tốt nhất cho thành viên lâu dài',6);

/* ------------------------- Trang tĩnh ------------------------- */
INSERT INTO `pages` (`title`,`slug`,`content`) VALUES
('Nội quy','noi-quy','<p>Nghiêm cấm đăng ảnh phản cảm, thông tin lừa đảo, mua bán dịch vụ trái pháp luật. Tin vi phạm sẽ bị gỡ và khoá tài khoản.</p>'),
('Điều khoản sử dụng','dieu-khoan','<p>Khi sử dụng website, bạn đồng ý chịu trách nhiệm với nội dung mình đăng tải.</p>'),
('Liên hệ','lien-he','<p>Email: support@henho24.local — Hotline: 0900 000 000</p>');

/* ------------------------- Banner mẫu ------------------------- */
INSERT INTO `banners` (`position`,`title`,`image`,`link`,`sort`) VALUES
('home_slider','Kết bạn hẹn hò nghiêm túc','assets/site/img/placeholder.svg','/',1);
