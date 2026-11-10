-- Nhóm riêng trong inbox. Chỉ chạy khi được duyệt cho môi trường đích.
-- Tệp này chỉ tạo cấu trúc, không thêm dữ liệu hoặc chạy từ request web.

CREATE TABLE IF NOT EXISTS `inbox_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `owner_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('active','closed') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inbox_groups_owner` (`owner_id`),
  CONSTRAINT `fk_inbox_groups_owner`
    FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inbox_group_members` (
  `group_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `left_at` DATETIME DEFAULT NULL,
  `last_read_message_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`group_id`, `user_id`),
  KEY `idx_inbox_members_user` (`user_id`, `left_at`, `group_id`),
  CONSTRAINT `fk_inbox_members_group`
    FOREIGN KEY (`group_id`) REFERENCES `inbox_groups` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_inbox_members_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inbox_group_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` BIGINT UNSIGNED NOT NULL,
  `sender_id` BIGINT UNSIGNED NOT NULL,
  `type` ENUM('text','image','system') NOT NULL DEFAULT 'text',
  `content` TEXT NOT NULL,
  `client_message_id` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inbox_messages_group` (`group_id`, `id`),
  KEY `idx_inbox_messages_sender` (`sender_id`),
  UNIQUE KEY `uq_inbox_message_retry` (`group_id`, `sender_id`, `client_message_id`),
  CONSTRAINT `fk_inbox_messages_group`
    FOREIGN KEY (`group_id`) REFERENCES `inbox_groups` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_inbox_messages_sender`
    FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
