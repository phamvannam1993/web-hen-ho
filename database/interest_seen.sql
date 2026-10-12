CREATE TABLE IF NOT EXISTS account_interest_seen (
  user_id BIGINT UNSIGNED NOT NULL,
  tab VARCHAR(20) NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  version VARCHAR(64) NOT NULL,
  PRIMARY KEY (user_id, tab, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
