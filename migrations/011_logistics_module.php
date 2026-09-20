<?php
/**
 * Migration 011 — Logistics module
 *
 * Creates tables for invite-only logistics partners:
 *   - logistics_companies  (the company)
 *   - logistics_users      (company staff accounts)
 *   - order_logistics      (assignment of orders to logistics)
 */
return [
    'id'          => '011',
    'title'       => 'Logistics module — companies, users, order assignments',
    'description' => 'Creates the full logistics partner schema for the invite-only logistics portal.',
    'steps'       => [

        '011a' => [
            'label' => 'Create logistics_companies table',
            'sql'   => "CREATE TABLE IF NOT EXISTS `logistics_companies` (
                          `id`             INT(11)      NOT NULL AUTO_INCREMENT,
                          `company_name`   VARCHAR(200) NOT NULL,
                          `contact_first`  VARCHAR(80)  NOT NULL,
                          `contact_last`   VARCHAR(80)  NOT NULL,
                          `email`          VARCHAR(191) NOT NULL,
                          `phone`          VARCHAR(30)  NOT NULL,
                          `country`        VARCHAR(80)  NOT NULL,
                          `city`           VARCHAR(80)  NOT NULL,
                          `status`         ENUM('active','suspended') NOT NULL DEFAULT 'active',
                          `created_by`     INT(11)      NOT NULL COMMENT 'admin user_id',
                          `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `uq_lc_email` (`email`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],

        '011b' => [
            'label' => 'Create logistics_users table',
            'sql'   => "CREATE TABLE IF NOT EXISTS `logistics_users` (
                          `id`              INT(11)      NOT NULL AUTO_INCREMENT,
                          `company_id`      INT(11)      NOT NULL,
                          `first_name`      VARCHAR(80)  NOT NULL,
                          `last_name`       VARCHAR(80)  NOT NULL,
                          `email`           VARCHAR(191) NOT NULL,
                          `phone`           VARCHAR(30)  DEFAULT NULL,
                          `password_hash`   VARCHAR(255) NOT NULL,
                          `role`            ENUM('admin','staff') NOT NULL DEFAULT 'staff'
                                            COMMENT 'admin = company admin, staff = regular user',
                          `status`          ENUM('active','suspended') NOT NULL DEFAULT 'active',
                          `temp_password`   TINYINT(1)   NOT NULL DEFAULT 1
                                            COMMENT '1 = must change password on next login',
                          `last_login_at`   DATETIME     DEFAULT NULL,
                          `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `uq_lu_email` (`email`),
                          KEY `idx_lu_company` (`company_id`),
                          CONSTRAINT `fk_lu_company` FOREIGN KEY (`company_id`)
                            REFERENCES `logistics_companies`(`id`) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],

        '011c' => [
            'label' => 'Create order_logistics table (assignment + tracking)',
            'sql'   => "CREATE TABLE IF NOT EXISTS `order_logistics` (
                          `id`              INT(11) NOT NULL AUTO_INCREMENT,
                          `order_id`        INT(11) NOT NULL,
                          `shop_id`         INT(11) NOT NULL,
                          `company_id`      INT(11) NOT NULL,
                          `assigned_by`     INT(11) NOT NULL COMMENT 'merchant user_id',
                          `tracking_number` VARCHAR(100) DEFAULT NULL,
                          `status`          ENUM('assigned','picked_up','in_transit','out_for_delivery','delivered','failed','returned')
                                            NOT NULL DEFAULT 'assigned',
                          `notes`           TEXT DEFAULT NULL,
                          `assigned_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `uq_ol_order_shop` (`order_id`, `shop_id`),
                          KEY `idx_ol_order`   (`order_id`),
                          KEY `idx_ol_company` (`company_id`),
                          KEY `idx_ol_shop`    (`shop_id`),
                          CONSTRAINT `fk_ol_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
                          CONSTRAINT `fk_ol_company` FOREIGN KEY (`company_id`) REFERENCES `logistics_companies`(`id`),
                          CONSTRAINT `fk_ol_shop`    FOREIGN KEY (`shop_id`)    REFERENCES `shops`(`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],

        '011d' => [
            'label' => 'Create logistics_status_log table (full audit)',
            'sql'   => "CREATE TABLE IF NOT EXISTS `logistics_status_log` (
                          `id`           INT(11) NOT NULL AUTO_INCREMENT,
                          `assignment_id`INT(11) NOT NULL,
                          `changed_by`   INT(11) NOT NULL COMMENT 'logistics_user id',
                          `user_name`    VARCHAR(160) NOT NULL,
                          `old_status`   VARCHAR(50)  NOT NULL,
                          `new_status`   VARCHAR(50)  NOT NULL,
                          `note`         TEXT DEFAULT NULL,
                          `changed_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          KEY `idx_lsl_assignment` (`assignment_id`),
                          CONSTRAINT `fk_lsl_assignment` FOREIGN KEY (`assignment_id`)
                            REFERENCES `order_logistics`(`id`) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],
    ],
];
