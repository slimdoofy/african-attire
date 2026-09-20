<?php
/**
 * Migration 009 — Multi-currency support & exchange rate logging
 *
 * 1. Add usd_rate column to orders (captures rate at time of order)
 * 2. Add currency_settings table for USD/NGN rate
 * 3. Add exchange_rate_log table (audit trail: who, when, old, new)
 */
return [
    'id'          => '009',
    'title'       => 'Multi-currency & exchange rate audit log',
    'description' => 'Adds USD rate capture on orders, currency settings, and a full audit log.',
    'steps'       => [

        '009a' => [
            'label' => 'Add usd_rate column to orders',
            'sql'   => "ALTER TABLE `orders`
                        ADD COLUMN `usd_rate` DECIMAL(10,4) NULL DEFAULT NULL
                        COMMENT 'USD/NGN exchange rate at time of order'
                        AFTER `total_amount`",
            'safe'  => true,
        ],

        '009b' => [
            'label' => 'Add usd_amount column to orders (total in USD)',
            'sql'   => "ALTER TABLE `orders`
                        ADD COLUMN `usd_amount` DECIMAL(12,2) NULL DEFAULT NULL
                        COMMENT 'Order total in USD at captured rate'
                        AFTER `usd_rate`",
            'safe'  => true,
        ],

        '009c' => [
            'label' => 'Create exchange_rate_log table',
            'sql'   => "CREATE TABLE IF NOT EXISTS `exchange_rate_log` (
                          `id`           INT(11) NOT NULL AUTO_INCREMENT,
                          `admin_id`     INT(11) NOT NULL,
                          `admin_name`   VARCHAR(150) NOT NULL,
                          `admin_email`  VARCHAR(191) NOT NULL,
                          `rate_key`     VARCHAR(30)  NOT NULL DEFAULT 'usd_ngn_rate',
                          `old_rate`     DECIMAL(10,4) NOT NULL DEFAULT 0,
                          `new_rate`     DECIMAL(10,4) NOT NULL,
                          `note`         VARCHAR(255) NULL DEFAULT NULL,
                          `changed_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          KEY `idx_rate_key` (`rate_key`),
                          KEY `idx_admin` (`admin_id`),
                          KEY `idx_changed_at` (`changed_at`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],

        '009d' => [
            'label' => 'Seed default USD/NGN rate in settings',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`, `value`, `label`)
                        VALUES ('usd_ngn_rate', '1600', 'USD to NGN Exchange Rate')",
        ],

        '009e' => [
            'label' => 'Add currency_mode setting',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`, `value`, `label`)
                        VALUES ('currency_mode', 'ngn', 'Default Display Currency (ngn or usd)')",
        ],

    ],
];
