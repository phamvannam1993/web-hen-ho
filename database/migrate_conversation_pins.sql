CREATE TABLE IF NOT EXISTS `conversation_pins` (
  `conversation_id` BIGINT UNSIGNED NOT NULL,
  `message_id` BIGINT UNSIGNED NOT NULL,
  `pinned_by_user_id` BIGINT UNSIGNED NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`conversation_id`),
  KEY `idx_cp_message` (`message_id`),
  KEY `idx_cp_user` (`pinned_by_user_id`),
  CONSTRAINT `fk_cp_conversation`
    FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_cp_message`
    FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_cp_user`
    FOREIGN KEY (`pinned_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
