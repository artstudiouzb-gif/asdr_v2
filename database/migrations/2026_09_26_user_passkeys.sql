-- Ключи доступа (passkeys, WebAuthn) — третий канал второго фактора админки.
-- Хранится только открытый ключ (PEM) и счётчик подписей: секрет остаётся
-- на устройстве пользователя.
CREATE TABLE IF NOT EXISTS user_passkeys (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    credential_id VARCHAR(255) NOT NULL COMMENT 'base64url идентификатора ключа',
    public_key    TEXT         NOT NULL COMMENT 'PEM открытого ключа',
    sign_count    INT UNSIGNED NOT NULL DEFAULT 0,
    name          VARCHAR(100) NOT NULL DEFAULT '',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at  DATETIME     NULL,
    UNIQUE KEY uq_user_passkeys_credential (credential_id),
    KEY idx_user_passkeys_user (user_id),
    CONSTRAINT fk_user_passkeys_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
