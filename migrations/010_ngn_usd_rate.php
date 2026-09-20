<?php
/**
 * Migration 010 — Add NGN→USD rate setting
 *
 * Complements the existing usd_ngn_rate with the inverse rate.
 * Both are kept in sync whenever either is updated via admin settings.
 */
return [
    'id'          => '010',
    'title'       => 'Add NGN→USD exchange rate setting',
    'description' => 'Adds ngn_usd_rate to settings table (inverse of usd_ngn_rate).',
    'steps'       => [
        '010a' => [
            'label' => 'Insert ngn_usd_rate setting',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`, `value`, `label`)
                        VALUES ('ngn_usd_rate', '0.000625', 'NGN to USD Exchange Rate (1 NGN = X USD)')",
        ],
    ],
];
