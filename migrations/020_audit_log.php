<?php
return [
    'id'    => '020',
    'title' => 'Audit log and admin user management',
    'steps' => [
        '020a' => [
            'label' => 'Create audit_log table',
            'sql'   => "CREATE TABLE IF NOT EXISTS `audit_log` (
                          `id`          BIGINT(20)   NOT NULL AUTO_INCREMENT,
                          `user_id`     INT(11)      NULL DEFAULT NULL,
                          `user_email`  VARCHAR(191) NULL DEFAULT NULL,
                          `portal`      ENUM('customer','merchant','admin','logistics') NOT NULL DEFAULT 'customer',
                          `action`      VARCHAR(100) NOT NULL,
                          `entity_type` VARCHAR(60)  NULL DEFAULT NULL COMMENT 'e.g. order, product, user',
                          `entity_id`   INT(11)      NULL DEFAULT NULL,
                          `description` TEXT         NULL DEFAULT NULL,
                          `ip_address`  VARCHAR(45)  NULL DEFAULT NULL,
                          `user_agent`  VARCHAR(255) NULL DEFAULT NULL,
                          `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          KEY `idx_al_user`    (`user_id`),
                          KEY `idx_al_portal`  (`portal`),
                          KEY `idx_al_created` (`created_at`),
                          KEY `idx_al_action`  (`action`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],
        '020b' => [
            'label' => 'Create admin_users table',
            'sql'   => "CREATE TABLE IF NOT EXISTS `admin_users` (
                          `id`            INT(11)      NOT NULL AUTO_INCREMENT,
                          `name`          VARCHAR(150) NOT NULL,
                          `email`         VARCHAR(191) NOT NULL,
                          `password_hash` VARCHAR(255) NOT NULL,
                          `modules`       TEXT         NULL DEFAULT NULL COMMENT 'JSON array of allowed modules',
                          `status`        ENUM('active','suspended') NOT NULL DEFAULT 'active',
                          `temp_password` TINYINT(1)   NOT NULL DEFAULT 1,
                          `created_by`    INT(11)      NULL DEFAULT NULL,
                          `last_login_at` DATETIME     NULL DEFAULT NULL,
                          `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `uq_au_email` (`email`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],
    ],
];
