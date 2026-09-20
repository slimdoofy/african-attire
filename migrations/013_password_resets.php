<?php
/**
 * Migration 013 — Password reset tokens table
 * Used by customer, merchant, and logistics portals.
 */
return [
    'id'          => '013',
    'title'       => 'Password reset tokens table',
    'description' => 'Creates password_resets table for all portal forgot-password flows.',
    'steps'       => [
        '013a' => [
            'label' => 'Create password_resets table',
            'sql'   => "CREATE TABLE IF NOT EXISTS `password_resets` (
                          `id`         INT(11) NOT NULL AUTO_INCREMENT,
                          `email`      VARCHAR(191) NOT NULL,
                          `token`      VARCHAR(100) NOT NULL,
                          `portal`     ENUM('customer','merchant','logistics') NOT NULL DEFAULT 'customer',
                          `expires_at` DATETIME NOT NULL,
                          `used`       TINYINT(1) NOT NULL DEFAULT 0,
                          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `uq_token` (`token`),
                          KEY `idx_email_portal` (`email`, `portal`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],
        '013b' => [
            'label' => 'Auto-expire old tokens cleanup event',
            'sql'   => "DELETE FROM `password_resets` WHERE `expires_at` < NOW() - INTERVAL 7 DAY",
        ],
    ],
];
