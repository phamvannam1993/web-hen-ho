-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost
-- Thời gian đã tạo: Th12 17, 2024 lúc 08:24 AM
-- Phiên bản máy phục vụ: 10.4.34-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `mobi_manta`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `title_name` text DEFAULT NULL,
  `slug` text DEFAULT NULL,
  `svg` text DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `title` longtext DEFAULT NULL,
  `meta_des` longtext DEFAULT NULL,
  `keyword` longtext DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `title_name`, `slug`, `svg`, `color`, `title`, `meta_des`, `keyword`, `type`, `description`, `created_at`, `updated_at`) VALUES
(1, 'League of Legends', 'Kí tự đặc biệt LOL hấp dẫn nhất dành cho nam nữ - Mobifonts', 'ki-tu-league-of-legends', '', 'secondary', 'Kí tự đặc biệt LOL hấp dẫn nhất dành cho nam nữ - Mobifonts', 'Công cụ tạo tên Game League of Legends thành kí tự đặc biệt. Bạn có thể sử dụngkí tự để đặt tên game cho nhân vật, dùng đặt biệt danh Khoảng trống cho các ứng dụng mạng xã hội hoặc nhắn tin cho bạn bè.', 'khoảng trống', NULL, 'Công cụ tạo tên Game League of Legends thành kí tự đặc biệt. Bạn có thể sử dụngkí tự để đặt tên game cho nhân vật, dùng đặt biệt danh Khoảng trống cho các ứng dụng mạng xã hội hoặc nhắn tin cho bạn bè.', '2024-11-10 21:17:27', '2024-11-29 10:17:50'),
(2, 'Audition', 'Kí tự đặc biệt Audition', 'ki-tu-dac-biet-audition', '', 'danger', 'Web tạo kí tự đặc biệt Auditon đẹp nhất dành cho bạn', '11.11.2024 Vietnam Fonts giúp bạn phần mềm tạo kí tự đặc biệt Audition đẹp mắt nhất giúp bạn có thể tỏa sáng với những bước nhảy thật tuyệt vời trên các bản nhạc đỉnh cao.', 'kí tự đặc biệt audition', NULL, '', '2024-11-10 21:25:57', '2024-11-13 14:47:47'),
(3, 'Free Fire', 'Công cụ tạo tên Game FF (Free Fire) cực sáng tạo ', 'ki-tu-free-fire', '', 'warning', 'Công cụ tạo kí tự đặc biệt FF (Free Fire) cực sáng tạo ', '11.11.2024 Vietnam Fonts đã nghiên cứu & phát triển công cụ tạo tên Game FF (Free Fire) giúp cho Gamers có thể sáng tạo được những kí tự game cực kì sáng tạo cho ingame của chính mình.', 'kí tự free fire', NULL, '', '2024-11-10 21:27:19', '2024-11-13 13:59:57'),
(4, 'VNG CF', 'Kí tự đặc biệt Game CF (Crossfire) mang phong cách độc đáo', 'ki-tu-dac-biet-cf', '', 'danger', 'Kí tự đặc biệt Game CF (Crossfire) mang phong cách độc đáo', '11.11.2021 Chúng tôi phát triển dư án tạo kí tự đặc biệt CF của tựa Game Crossfire với thể loại game bắn súng được đông đảo người trẻ tham gia và phám phá.', 'Kí tự CF', NULL, '11.11.2021 Chúng tôi phát triển dư án tạo kí tự đặc biệt CF của tựa Game Crossfire với thể loại game bắn súng được đông đảo người trẻ tham gia và phám phá.', '2024-11-10 21:28:25', '2024-11-13 15:12:50'),
(5, 'Liên Quân', 'Kí tự đặc biệt Game Liên Quân Mobile đẹp cho Nam và Nữ ', 'ki-tu-lien-quan-mobile', '', 'danger', 'Kí tự đặc biệt Game Liên Quân Mobile đẹp cho Nam và Nữ ', 'Cập nhật dành đến cho bạn kí tự đặc biệt Liên Quân Mobile đẹp giúp cho bạn tạo được nhiều ấn tượng cho những đồng đội của mình khi nhìn được cái biệt danh đặc biệt nhất.', 'Kí tự đặc biệt Liên Quân', NULL, '', '2024-11-10 21:29:07', '2024-11-13 13:49:23'),
(6, 'PUBG Mobile', 'Kí tự đặc biệt Pubg Mobile đẹp mang lại ấn tượng cho bạn', 'ki-tu-pubg-mobile', '', 'primary', 'Kí tự đặc biệt Pubg Mobile đẹp mang lại ấn tượng cho bạn', '11.11.2024 Chúng tôi tiến hành phát triển Tools kí tự đặc biệt Pubg Mobile đẹp giúp bạn có thể mang được lại nhiều ấn tượng cho đồng đội của mình mỗi khi tiến hành vào trận chiến.', 'Kí tự Pubg Mobile', NULL, '', '2024-11-10 21:30:03', '2024-11-13 14:03:28'),
(7, 'Quả Táo', 'Kí Tự Đặc Biệt Quả Táo', 'ki-tu-dac-biet-qua-tao', '<svg fill=\"#ffffff\" xmlns=\"http://www.w3.org/2000/svg\" width=\"18\" height=\"18\" shape-rendering=\"geometricPrecision\" text-rendering=\"geometricPrecision\" image-rendering=\"optimizeQuality\" fill-rule=\"evenodd\" clip-rule=\"evenodd\" viewBox=\"0 0 640 640\"><path d=\"M494.782 340.02c-.803-81.025 66.084-119.907 69.072-121.832-37.595-54.993-96.167-62.552-117.037-63.402-49.843-5.032-97.242 29.362-122.565 29.362-25.253 0-64.277-28.607-105.604-27.85-54.32.803-104.4 31.594-132.403 80.245C29.81 334.457 71.81 479.58 126.816 558.976c26.87 38.882 58.914 82.56 100.997 81 40.512-1.594 55.843-26.244 104.848-26.244 48.993 0 62.753 26.245 105.64 25.406 43.606-.803 71.232-39.638 97.925-78.65 30.887-45.12 43.548-88.75 44.316-90.994-.969-.437-85.029-32.634-85.879-129.439l.118-.035zM414.23 102.178C436.553 75.095 451.636 37.5 447.514-.024c-32.162 1.311-71.163 21.437-94.253 48.485-20.729 24.012-38.836 62.28-33.993 99.036 35.918 2.8 72.591-18.248 94.926-45.272l.036-.047z\"></path></svg>', 'success', 'Kí tự đặc biệt Quả táo đẹp mắt ấn tượng đa dạng nhiều màu sắc', '28.11.2024 Chúng tôi phát triển kí tự đăc biệt Quả táo màu đỏ trở thành một trong nhiều biểu tượng nổi bật giúp các Player có thể làm đẹp cho nhân vật Games của mình một cách ấn tượng.', 'Kí tự Quả táo', NULL, '28.11.2024 MobiFonts phát triển kí tự đăc biệt Quả táo màu đỏ trở thành một trong nhiều biểu tượng nổi bật giúp các Player có thể làm đẹp cho nhân vật Games của mình một cách ấn tượng.', '2024-11-10 21:31:15', '2024-11-28 04:11:15'),
(8, 'Vương miện 亗', 'Kí tự vương miện 亗 FF ', 'ki-tu-vuong-mien', '', 'danger', 'Kí tự đặc biệt Vượng Miện sử dụng cho Game cực ấn tượng', '28.11.2024 Nhà phát triển MobiFonts phát triển kí tự đặc biệt Vương Miện hiện tại đang trở thành một biểu tượng được khá nhiều Game thủ sử dụng cho nhân vật hoặc các tài khoản mạng xã hội nhiều nhất.', 'Kí tự vương miện', NULL, '28.11.2024 Nhà phát triển MobiFonts phát triển kí tự đặc biệt Vương Miện hiện tại đang trở thành một biểu tượng được khá nhiều Game thủ sử dụng cho nhân vật hoặc các tài khoản mạng xã hội nhiều nhất.', '2024-11-10 21:32:13', '2024-11-28 04:14:25'),
(9, 'Mặt Quỷ ╰‿╯', 'Kí Tự Đặc Biệt Mặt quỷ', 'ki-tu-mat-quy', '', 'dark', 'Kí tự đặc biệt Mặt Quỷ biểu tượng sự độc đáo cho Gamers', '20.11.2024 Chúng tôi phát triển ứng dụng tạo kí tự đặc biệt Mặt Quỷ để hỗ trợ cho cộng đồng thích kí tự đặc biệt sử dụng với phiên bản V1.2.41 cho các nền tảng Games Online.', 'Kí tự Mặt Quỷ', NULL, '20.11.2024 Chúng tôi phát triển ứng dụng tạo kí tự đặc biệt Mặt Quỷ để hỗ trợ cho cộng đồng thích kí tự đặc biệt sử dụng với phiên bản V1.2.41 cho các nền tảng Games Online.', '2024-11-10 21:33:08', '2024-11-28 05:39:36'),
(10, 'Chữ Kiểu', 'Kí tự đặc biệt chữ kiểu văn bản trên các nền tảng Facebook, Tiktok', 'ki-tu-dac-biet-chu-kieu', '', 'secondary', 'Kí tự đặc biệt chữ kiểu văn bản trên các nền tảng Facebook, Tiktok', '20.11.2024 Mobifonts phát triển ứng dụng công cụ kí tự đặc biệt chữ kiểu văn bản dành cho các nền tảng Facebook, Tiktok đổi định dạng văn bản phù hợp trong công việc.', 'Kí tự chữ kiểu văn bản', NULL, '20.11.2024 Mobifonts phát triển ứng dụng công cụ kí tự đặc biệt chữ kiểu văn bản dành cho các nền tảng Facebook, Tiktok đổi định dạng văn bản phù hợp trong công việc.', '2024-11-10 21:35:22', '2024-11-28 07:01:31'),
(11, 'Chữ Ngược', 'Viết chữ đảo ngược từ 180 độ đến 360 độ - Mobifonts', 'ki-tu-chu-nguoc', '', 'primary', 'Viết chữ đảo ngược từ 180 độ đến 360 độ - Mobifonts', '30.11.2024 Mobifonts phát triển phần mềm viết chữ đảo ngược từ 180 độ cho dén 360 độ để giúp cho bạn có thể biến những bộ văn bản phù hợp với mục đích của bản thân.', 'Viết chữ đảo ngược', NULL, '30.11.2024 Mobifonts phát triển phần mềm viết chữ đảo ngược từ 180 độ cho dén 360 độ để giúp cho bạn có thể biến những bộ văn bản phù hợp với mục đích của bản thân.', '2024-11-10 21:36:29', '2024-11-30 04:25:00'),
(12, 'Chữ Viết Tay', 'Kí tự fonts chữ viết tay cho cá nhân cực độc đáo - Mobifonts', 'chu-viet-tay', '', 'secondary', 'Kí tự fonts chữ viết tay cho cá nhân cực độc đáo - Mobifonts', '30.11.2024 Chúng tôi phát triển ứng dụng fonts chữ viết tay dành cho cá nhân có thể sử dụng để chỉnh sửa văn bản trên các nền tảng Social Media như Facebook, Tiktok, Zalo.', 'Chữ viết tay', NULL, '30.11.2024 Chúng tôi phát triển ứng dụng fonts chữ viết tay dành cho cá nhân có thể sử dụng để chỉnh sửa văn bản trên các nền tảng Social Media như Facebook, Tiktok, Zalo.', '2024-11-10 21:37:21', '2024-11-30 08:55:20');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
