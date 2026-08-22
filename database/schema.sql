-- =====================================================================
-- WEB HẸN HÒ - THIẾT KẾ CSDL (MySQL 8 / MariaDB 10.4+)
-- Charset: utf8mb4_unicode_ci
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `web_hen_ho`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `web_hen_ho`;

-- ---------------------------------------------------------------------
-- 1. DANH MỤC DÙNG CHUNG
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `provinces`;
CREATE TABLE `provinces` (
  `id`        SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`      VARCHAR(100) NOT NULL,
  `slug`      VARCHAR(120) NOT NULL,
  `region`    ENUM('bac','trung','nam') DEFAULT NULL,
  `sort`      SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provinces_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id`   INT UNSIGNED DEFAULT NULL,
  `type`        ENUM('post','blog') NOT NULL DEFAULT 'post',
  `name`        VARCHAR(150) NOT NULL,
  `slug`        VARCHAR(180) NOT NULL,
  `icon`        VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `seo_title`   VARCHAR(255) DEFAULT NULL,
  `seo_desc`    VARCHAR(500) DEFAULT NULL,
  `sort`        SMALLINT NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`type`,`slug`),
  KEY `idx_categories_parent` (`parent_id`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`)
    REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `interests`;
CREATE TABLE `interests` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `icon` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_interests_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. NGƯỜI DÙNG
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`            CHAR(36) NOT NULL,
  `email`           VARCHAR(190) DEFAULT NULL,
  `phone`           VARCHAR(20)  DEFAULT NULL,
  `password_hash`   VARCHAR(255) NOT NULL,
  `display_name`    VARCHAR(100) NOT NULL,
  `nickname`        VARCHAR(60) DEFAULT NULL COMMENT 'biệt danh hiển thị công khai',
  `slug`            VARCHAR(140) NOT NULL,
  `gender`          ENUM('male','female','other') NOT NULL DEFAULT 'other',
  `birthday`        DATE DEFAULT NULL,
  `province_id`     SMALLINT UNSIGNED DEFAULT NULL,
  `avatar`          VARCHAR(255) DEFAULT NULL,
  `cover`           VARCHAR(255) DEFAULT NULL,
  `bio`             TEXT DEFAULT NULL,
  `height_cm`       SMALLINT UNSIGNED DEFAULT NULL,
  `weight_kg`       SMALLINT UNSIGNED DEFAULT NULL,
  `job`             VARCHAR(150) DEFAULT NULL,
  `education`       ENUM('thpt','trung_cap','cao_dang','dai_hoc','sau_dai_hoc') DEFAULT NULL,
  `marital_status`  ENUM('doc_than','ly_hon','goa','phuc_tap') DEFAULT NULL,
  `has_children`    TINYINT(1) NOT NULL DEFAULT 0,
  `smoking`         ENUM('khong','thinh_thoang','thuong_xuyen') DEFAULT NULL,
  `drinking`        ENUM('khong','thinh_thoang','thuong_xuyen') DEFAULT NULL,
  `role`            ENUM('member','moderator','admin') NOT NULL DEFAULT 'member',
  `status`          ENUM('pending','active','locked','banned') NOT NULL DEFAULT 'pending',
  `email_verified_at` DATETIME DEFAULT NULL,
  `phone_verified_at` DATETIME DEFAULT NULL,
  `kyc_status`      ENUM('none','pending','verified','rejected') NOT NULL DEFAULT 'none',
  `is_vip`          TINYINT(1) NOT NULL DEFAULT 0,
  `vip_expired_at`  DATETIME DEFAULT NULL,
  `coin_balance`    INT NOT NULL DEFAULT 0,
  `profile_score`   TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '% hoàn thiện hồ sơ',
  `last_active_at`  DATETIME DEFAULT NULL,
  `last_login_ip`   VARCHAR(45) DEFAULT NULL,
  `referrer_id`     BIGINT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`      DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_uuid` (`uuid`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_phone` (`phone`),
  UNIQUE KEY `uq_users_slug` (`slug`),
  KEY `idx_users_filter` (`status`,`gender`,`province_id`),
  KEY `idx_users_active` (`last_active_at`),
  CONSTRAINT `fk_users_province` FOREIGN KEY (`province_id`)
    REFERENCES `provinces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `user_photos`;
CREATE TABLE `user_photos` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `path`       VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `status`     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `sort`       SMALLINT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_photos_user` (`user_id`,`status`),
  CONSTRAINT `fk_user_photos_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `user_interests`;
CREATE TABLE `user_interests` (
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `interest_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`,`interest_id`),
  KEY `idx_ui_interest` (`interest_id`),
  CONSTRAINT `fk_ui_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ui_interest` FOREIGN KEY (`interest_id`) REFERENCES `interests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `user_preferences`;
CREATE TABLE `user_preferences` (
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `seeking_gender` ENUM('male','female','all') NOT NULL DEFAULT 'all',
  `age_min`        TINYINT UNSIGNED NOT NULL DEFAULT 18,
  `age_max`        TINYINT UNSIGNED NOT NULL DEFAULT 60,
  `province_id`    SMALLINT UNSIGNED DEFAULT NULL,
  `purpose`        ENUM('ket_ban','hen_ho','nghiem_tuc','ket_hon') NOT NULL DEFAULT 'hen_ho',
  `show_online`    TINYINT(1) NOT NULL DEFAULT 1,
  `allow_message`  ENUM('all','vip','matched') NOT NULL DEFAULT 'all',
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_pref_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `user_tokens`;
CREATE TABLE `user_tokens` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `type`       ENUM('verify_email','reset_password','remember','otp') NOT NULL,
  `token`      VARCHAR(128) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at`    DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tokens_token` (`token`),
  KEY `idx_tokens_user` (`user_id`,`type`),
  CONSTRAINT `fk_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. TIN ĐĂNG HẸN HÒ
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      BIGINT UNSIGNED NOT NULL,
  `category_id`  INT UNSIGNED DEFAULT NULL,
  `province_id`  SMALLINT UNSIGNED DEFAULT NULL,
  `title`        VARCHAR(255) NOT NULL,
  `slug`         VARCHAR(280) NOT NULL,
  `content`      MEDIUMTEXT NOT NULL,
  `gender`       ENUM('male','female','other') DEFAULT NULL COMMENT 'giới tính người đăng',
  `seeking`      ENUM('male','female','all') NOT NULL DEFAULT 'all',
  `age`          TINYINT UNSIGNED DEFAULT NULL,
  `purpose`      ENUM('ket_ban','hen_ho','nghiem_tuc','ket_hon') NOT NULL DEFAULT 'hen_ho',
  -- thông tin hiển thị trên thẻ tin ngoài trang chủ
  `nickname`       VARCHAR(100) DEFAULT NULL COMMENT 'tên hiển thị trên tin, vd: Kim Ngọc',
  `intro`          VARCHAR(255) DEFAULT NULL COMMENT 'Giới thiệu ngắn',
  `job`            VARCHAR(150) DEFAULT NULL COMMENT 'Nghề nghiệp',
  `wish`           VARCHAR(500) DEFAULT NULL COMMENT 'Mong muốn tìm người yêu',
  `personality`    VARCHAR(255) DEFAULT NULL COMMENT 'Tính cách',
  `district`       VARCHAR(120) DEFAULT NULL COMMENT 'quận/huyện, hiển thị cùng tỉnh',
  `height_cm`      SMALLINT UNSIGNED DEFAULT NULL,
  `weight_kg`      SMALLINT UNSIGNED DEFAULT NULL,
  `marital_status` ENUM('doc_than','ly_hon','goa','dang_co_nguoi_yeu','phuc_tap') DEFAULT NULL,
  `is_verified`    TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'nhãn "Kiểm định"',
  `contact_type` ENUM('zalo','facebook','phone','app') NOT NULL DEFAULT 'app',
  `contact_value` VARCHAR(190) DEFAULT NULL,
  `contact_cost` INT NOT NULL DEFAULT 0 COMMENT 'xu để xem liên hệ',
  `cover`        VARCHAR(255) DEFAULT NULL,
  `status`       ENUM('draft','pending','approved','rejected','expired','hidden') NOT NULL DEFAULT 'pending',
  `reject_reason` VARCHAR(500) DEFAULT NULL,
  `is_featured`  TINYINT(1) NOT NULL DEFAULT 0,
  `featured_until` DATETIME DEFAULT NULL,
  `view_count`   INT UNSIGNED NOT NULL DEFAULT 0,
  `like_count`   INT UNSIGNED NOT NULL DEFAULT 0,
  `comment_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `published_at` DATETIME DEFAULT NULL,
  `expired_at`   DATETIME DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`   DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_posts_slug` (`slug`),
  KEY `idx_posts_user` (`user_id`),
  KEY `idx_posts_listing` (`status`,`is_featured`,`published_at`),
  KEY `idx_posts_filter` (`category_id`,`province_id`,`status`),
  FULLTEXT KEY `ft_posts` (`title`,`content`),
  CONSTRAINT `fk_posts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_posts_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `post_images`;
CREATE TABLE `post_images` (
  `id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `path`    VARCHAR(255) NOT NULL,
  `sort`    SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_post_images_post` (`post_id`),
  CONSTRAINT `fk_post_images_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `post_contact_unlocks`;
CREATE TABLE `post_contact_unlocks` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id`    BIGINT UNSIGNED NOT NULL,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `coin_spent` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_unlock` (`post_id`,`user_id`),
  CONSTRAINT `fk_unlock_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_unlock_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mã bảo mật ("pass") dùng cho: đăng ký, đăng nhập và mở số điện thoại của tin.
-- Người dùng bấm "LẤY PASS" -> hệ thống sinh mã (có thể trừ xu / miễn phí với VIP),
-- nhập lại mã vào form tương ứng để hoàn tất.
DROP TABLE IF EXISTS `access_codes`;
CREATE TABLE `access_codes` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`       VARCHAR(20) NOT NULL,
  `purpose`    ENUM('register','login','contact') NOT NULL DEFAULT 'contact',
  `user_id`    BIGINT UNSIGNED DEFAULT NULL COMMENT 'null = mã chung do admin phát / khách chưa đăng nhập',
  `post_id`    BIGINT UNSIGNED DEFAULT NULL COMMENT 'chỉ dùng với purpose = contact',
  `session_id` VARCHAR(64) DEFAULT NULL COMMENT 'ràng buộc phiên với khách chưa đăng nhập',
  `max_uses`   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `used_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `coin_spent` INT NOT NULL DEFAULT 0,
  `expires_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code` (`code`),
  KEY `idx_code_lookup` (`purpose`,`code`,`expires_at`),
  KEY `idx_code_user` (`user_id`),
  CONSTRAINT `fk_code_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_code_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `post_comments`;
CREATE TABLE `post_comments` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id`    BIGINT UNSIGNED NOT NULL,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `parent_id`  BIGINT UNSIGNED DEFAULT NULL,
  `content`    TEXT NOT NULL,
  `image`      VARCHAR(255) DEFAULT NULL COMMENT 'ảnh đính kèm',
  `status`     ENUM('pending','approved','hidden') NOT NULL DEFAULT 'approved',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comments_post` (`post_id`,`status`),
  CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_parent` FOREIGN KEY (`parent_id`) REFERENCES `post_comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bình luận trên trang cá nhân của thành viên
DROP TABLE IF EXISTS `user_comments`;
CREATE TABLE `user_comments` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id` BIGINT UNSIGNED NOT NULL COMMENT 'chủ trang cá nhân',
  `user_id`    BIGINT UNSIGNED NOT NULL COMMENT 'người bình luận',
  `parent_id`  BIGINT UNSIGNED DEFAULT NULL,
  `content`    TEXT NOT NULL,
  `image`      VARCHAR(255) DEFAULT NULL COMMENT 'ảnh đính kèm',
  `status`     ENUM('pending','approved','hidden') NOT NULL DEFAULT 'approved',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ucomments_profile` (`profile_id`,`status`,`id`),
  CONSTRAINT `fk_ucomments_profile` FOREIGN KEY (`profile_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ucomments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ucomments_parent` FOREIGN KEY (`parent_id`) REFERENCES `user_comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. TƯƠNG TÁC
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `likes`;
CREATE TABLE `likes` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     BIGINT UNSIGNED NOT NULL COMMENT 'người thích',
  `target_type` ENUM('user','post') NOT NULL,
  `target_id`   BIGINT UNSIGNED NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_like` (`user_id`,`target_type`,`target_id`),
  KEY `idx_like_target` (`target_type`,`target_id`),
  CONSTRAINT `fk_like_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `matches`;
CREATE TABLE `matches` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_low_id` BIGINT UNSIGNED NOT NULL COMMENT 'id nhỏ hơn, giữ cặp duy nhất',
  `user_high_id` BIGINT UNSIGNED NOT NULL,
  `matched_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_match` (`user_low_id`,`user_high_id`),
  CONSTRAINT `fk_match_low` FOREIGN KEY (`user_low_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_high` FOREIGN KEY (`user_high_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `blocks`;
CREATE TABLE `blocks` (
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `blocked_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`blocked_id`),
  CONSTRAINT `fk_block_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_block_target` FOREIGN KEY (`blocked_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hồ sơ đã bỏ qua khi lướt Khám phá, để không gợi ý lại
DROP TABLE IF EXISTS `user_passes`;
CREATE TABLE `user_passes` (
  `user_id`    BIGINT UNSIGNED NOT NULL COMMENT 'người bỏ qua',
  `passed_id`  BIGINT UNSIGNED NOT NULL COMMENT 'hồ sơ bị bỏ qua',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`passed_id`),
  CONSTRAINT `fk_upass_owner`  FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_upass_target` FOREIGN KEY (`passed_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `conversations`;
CREATE TABLE `conversations` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_low_id`     BIGINT UNSIGNED NOT NULL,
  `user_high_id`    BIGINT UNSIGNED NOT NULL,
  `last_message_id` BIGINT UNSIGNED DEFAULT NULL,
  `last_message_at` DATETIME DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conversation` (`user_low_id`,`user_high_id`),
  KEY `idx_conv_recent` (`last_message_at`),
  CONSTRAINT `fk_conv_low` FOREIGN KEY (`user_low_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conv_high` FOREIGN KEY (`user_high_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` BIGINT UNSIGNED NOT NULL,
  `sender_id`       BIGINT UNSIGNED NOT NULL,
  `type`            ENUM('text','image','system') NOT NULL DEFAULT 'text',
  `content`         TEXT NOT NULL,
  `read_at`         DATETIME DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`      DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_msg_conv` (`conversation_id`,`id`),
  CONSTRAINT `fk_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Phòng chat chung: mọi thành viên đã đăng nhập đều xem và gửi được
DROP TABLE IF EXISTS `room_messages`;
CREATE TABLE `room_messages` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `type`       ENUM('text','image','system') NOT NULL DEFAULT 'text',
  `content`    TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_room_recent` (`id`,`deleted_at`),
  CONSTRAINT `fk_room_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `type`       VARCHAR(50) NOT NULL COMMENT 'like|match|message|post_approved|system',
  `title`      VARCHAR(255) NOT NULL,
  `body`       VARCHAR(500) DEFAULT NULL,
  `url`        VARCHAR(255) DEFAULT NULL,
  `read_at`    DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_noti_user` (`user_id`,`read_at`),
  CONSTRAINT `fk_noti_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporter_id`  BIGINT UNSIGNED NOT NULL,
  `target_type`  ENUM('user','post','comment','message') NOT NULL,
  `target_id`    BIGINT UNSIGNED NOT NULL,
  `reason`       ENUM('lua_dao','noi_dung_xau','mao_danh','spam','khac') NOT NULL DEFAULT 'khac',
  `note`         VARCHAR(1000) DEFAULT NULL,
  `status`       ENUM('new','reviewing','resolved','dismissed') NOT NULL DEFAULT 'new',
  `handled_by`   BIGINT UNSIGNED DEFAULT NULL,
  `handled_at`   DATETIME DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_report_status` (`status`,`created_at`),
  KEY `idx_report_target` (`target_type`,`target_id`),
  CONSTRAINT `fk_report_user` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. THANH TOÁN / GÓI DỊCH VỤ
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `packages`;
CREATE TABLE `packages` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`        ENUM('vip','coin') NOT NULL DEFAULT 'vip',
  `name`        VARCHAR(150) NOT NULL,
  `price`       INT UNSIGNED NOT NULL COMMENT 'VNĐ',
  `duration_days` SMALLINT UNSIGNED DEFAULT NULL COMMENT 'với gói VIP',
  `coin_amount` INT UNSIGNED DEFAULT NULL COMMENT 'với gói xu',
  `bonus_coin`  INT UNSIGNED NOT NULL DEFAULT 0,
  `description` TEXT DEFAULT NULL,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `sort`        SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`        VARCHAR(30) NOT NULL,
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `package_id`  INT UNSIGNED DEFAULT NULL,
  `amount`      INT UNSIGNED NOT NULL,
  `method`      ENUM('bank','momo','vnpay','card','manual') NOT NULL DEFAULT 'bank',
  `status`      ENUM('pending','paid','failed','refunded','canceled') NOT NULL DEFAULT 'pending',
  `payload`     JSON DEFAULT NULL COMMENT 'dữ liệu cổng thanh toán',
  `paid_at`     DATETIME DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_code` (`code`),
  KEY `idx_orders_user` (`user_id`,`status`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orders_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `coin_transactions`;
CREATE TABLE `coin_transactions` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `amount`      INT NOT NULL COMMENT 'dương = cộng, âm = trừ',
  `balance_after` INT NOT NULL,
  `reason`      VARCHAR(50) NOT NULL COMMENT 'nap|unlock_contact|boost_post|admin_adjust|bonus',
  `ref_type`    VARCHAR(30) DEFAULT NULL,
  `ref_id`      BIGINT UNSIGNED DEFAULT NULL,
  `note`        VARCHAR(255) DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_coin_user` (`user_id`,`created_at`),
  CONSTRAINT `fk_coin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. NỘI DUNG & HỆ THỐNG
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `author_id`   BIGINT UNSIGNED DEFAULT NULL,
  `title`       VARCHAR(255) NOT NULL,
  `slug`        VARCHAR(280) NOT NULL,
  `excerpt`     VARCHAR(500) DEFAULT NULL,
  `content`     LONGTEXT NOT NULL,
  `thumbnail`   VARCHAR(255) DEFAULT NULL,
  `seo_title`   VARCHAR(255) DEFAULT NULL,
  `seo_desc`    VARCHAR(500) DEFAULT NULL,
  `view_count`  INT UNSIGNED NOT NULL DEFAULT 0,
  `status`      ENUM('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` DATETIME DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_articles_slug` (`slug`),
  KEY `idx_articles_list` (`status`,`published_at`),
  CONSTRAINT `fk_articles_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(255) NOT NULL,
  `slug`       VARCHAR(280) NOT NULL,
  `content`    LONGTEXT NOT NULL,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pages_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `position`  VARCHAR(50) NOT NULL DEFAULT 'home_slider',
  `title`     VARCHAR(255) DEFAULT NULL,
  `image`     VARCHAR(255) NOT NULL,
  `link`      VARCHAR(255) DEFAULT NULL,
  `sort`      SMALLINT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_banner_pos` (`position`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key`   VARCHAR(100) NOT NULL,
  `value` TEXT DEFAULT NULL,
  `group` VARCHAR(50) NOT NULL DEFAULT 'general',
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `admin_logs`;
CREATE TABLE `admin_logs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`   BIGINT UNSIGNED NOT NULL,
  `action`     VARCHAR(100) NOT NULL,
  `target`     VARCHAR(100) DEFAULT NULL,
  `target_id`  BIGINT UNSIGNED DEFAULT NULL,
  `ip`         VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_admin` (`admin_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
