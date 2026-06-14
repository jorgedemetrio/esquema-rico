--
-- Esquema Rico — criação de tabelas
-- Compatível com MySQL 5.6+/MariaDB 10.3+ (InnoDB, utf8mb4, índices <= 190 chars)
--

CREATE TABLE IF NOT EXISTS `#__esquemarico` (
    `id`               INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `title`            VARCHAR(190)      NOT NULL DEFAULT '',
    `contenttype`      VARCHAR(50)       NOT NULL DEFAULT '',
    `params`           MEDIUMTEXT        NULL,
    `plugin`           VARCHAR(50)       NOT NULL DEFAULT '0',
    `appview`          VARCHAR(50)       NOT NULL DEFAULT '*',
    `created`          DATETIME          NULL,
    `created_by`       INT UNSIGNED      NOT NULL DEFAULT 0,
    `modified`         DATETIME          NULL,
    `modified_by`      INT UNSIGNED      NOT NULL DEFAULT 0,
    `ordering`         INT               NOT NULL DEFAULT 0,
    `language`         VARCHAR(7)        NOT NULL DEFAULT '*',
    `note`             VARCHAR(255)      NOT NULL DEFAULT '',
    `state`            TINYINT           NOT NULL DEFAULT 0,
    `checked_out`      INT UNSIGNED      NULL,
    `checked_out_time` DATETIME          NULL,
    PRIMARY KEY (`id`),
    KEY `idx_state` (`state`),
    KEY `idx_plugin` (`plugin`),
    KEY `idx_language` (`language`),
    KEY `idx_contenttype` (`contenttype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__esquemarico_config` (
    `name`   VARCHAR(190) NOT NULL,
    `params` MEDIUMTEXT   NOT NULL,
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
