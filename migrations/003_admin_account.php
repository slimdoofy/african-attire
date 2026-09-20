<?php
/**
 * Migration 003 — Admin account setup / reset
 *
 * Creates or resets the platform admin account.
 * Safe to re-run at any time — uses ON DUPLICATE KEY UPDATE.
 *
 * Credentials:
 *   Email:    admin@africanattire.com
 *   Password: Admin@1234
 *   URL:      /admin/login.php
 */
return [
    'id'          => '003',
    'title'       => 'Admin account — create or reset',
    'description' => 'Inserts or resets the platform admin account (admin@africanattire.com / Admin@1234).',
    'steps'       => [
        '003a' => [
            'label' => 'Create or reset admin account in users table',
            'sql'   => "INSERT INTO `users`
                          (`name`, `email`, `phone`, `password_hash`,
                           `role`, `status`, `email_verified`, `created_at`)
                        VALUES
                          ('Platform Admin', 'admin@africanattire.com', '+2348000000001',
                           '\$2y\$11\$gu/MvQAotXTknLzxfWCXO.ovby8/BwM82lYccUyz6t2Fl2ym88B2C',
                           'admin', 'active', 1, NOW())
                        ON DUPLICATE KEY UPDATE
                          `password_hash`  = '\$2y\$11\$gu/MvQAotXTknLzxfWCXO.ovby8/BwM82lYccUyz6t2Fl2ym88B2C',
                          `role`           = 'admin',
                          `status`         = 'active',
                          `email_verified` = 1,
                          `name`           = 'Platform Admin'",
            'safe'  => true,
        ],
    ],
];
