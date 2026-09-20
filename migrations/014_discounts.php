<?php
/**
 * Migration 014 — Discount code system
 * Tables: discount_codes, discount_usage
 * Orders table: add discount_code, discount_amount columns
 */
return [
    'id'          => '014',
    'title'       => 'Discount code system',
    'description' => 'Creates discount_codes and discount_usage tables; adds discount columns to orders.',
    'steps'       => [
        '014a' => [
            'label' => 'Create discount_codes table',
            'sql'   => "CREATE TABLE IF NOT EXISTS `discount_codes` (
                          `id`            INT(11) NOT NULL AUTO_INCREMENT,
                          `code`          VARCHAR(50) NOT NULL,
                          `description`   VARCHAR(255) DEFAULT NULL,
                          `type`          ENUM('percent','flat') NOT NULL DEFAULT 'percent',
                          `value`         DECIMAL(10,2) NOT NULL,
                          `min_order_ngn` DECIMAL(12,2) NOT NULL DEFAULT 0,
                          `max_uses`      INT(11) NOT NULL DEFAULT 0 COMMENT '0 = unlimited',
                          `uses_count`    INT(11) NOT NULL DEFAULT 0,
                          `valid_from`    DATETIME NOT NULL,
                          `valid_until`   DATETIME NOT NULL,
                          `scope`         ENUM('platform','merchant') NOT NULL DEFAULT 'platform',
                          `shop_id`       INT(11) DEFAULT NULL COMMENT 'NULL = platform-wide',
                          `created_by`    INT(11) NOT NULL,
                          `created_by_role` ENUM('admin','merchant') NOT NULL DEFAULT 'admin',
                          `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
                          `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `uq_code` (`code`),
                          KEY `idx_shop` (`shop_id`),
                          KEY `idx_scope_status` (`scope`,`status`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],
        '014b' => [
            'label' => 'Create discount_usage table',
            'sql'   => "CREATE TABLE IF NOT EXISTS `discount_usage` (
                          `id`              INT(11) NOT NULL AUTO_INCREMENT,
                          `discount_id`     INT(11) NOT NULL,
                          `order_id`        INT(11) NOT NULL,
                          `order_number`    VARCHAR(25) NOT NULL,
                          `user_id`         INT(11) DEFAULT NULL,
                          `guest_email`     VARCHAR(191) DEFAULT NULL,
                          `discount_amount` DECIMAL(12,2) NOT NULL,
                          `subtotal_before` DECIMAL(12,2) NOT NULL,
                          `applied_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          KEY `idx_du_discount` (`discount_id`),
                          KEY `idx_du_order` (`order_id`),
                          CONSTRAINT `fk_du_discount` FOREIGN KEY (`discount_id`)
                            REFERENCES `discount_codes`(`id`),
                          CONSTRAINT `fk_du_order` FOREIGN KEY (`order_id`)
                            REFERENCES `orders`(`id`) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],
        '014c' => [
            'label' => 'Add discount columns to orders',
            'sql'   => "ALTER TABLE `orders`
                        ADD COLUMN `discount_code`   VARCHAR(50)   NULL DEFAULT NULL AFTER `total_amount`,
                        ADD COLUMN `discount_amount` DECIMAL(12,2) NULL DEFAULT NULL AFTER `discount_code`,
                        ADD COLUMN `delivery_fee`    DECIMAL(12,2) NULL DEFAULT NULL AFTER `discount_amount`",
            'safe'  => true,
        ],
    ],
];
