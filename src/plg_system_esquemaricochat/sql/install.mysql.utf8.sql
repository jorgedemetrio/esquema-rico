--
-- Esquema Rico Chat — Criação da tabela de histórico de chat
-- Compatível com MySQL 5.6+ / MariaDB 10.3+ (InnoDB, utf8mb4)
--

CREATE TABLE IF NOT EXISTS `#__esquemarico_chat_history` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `sender`     VARCHAR(10)  NOT NULL DEFAULT 'user',
    `message`    TEXT         NOT NULL,
    `created_at` DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
