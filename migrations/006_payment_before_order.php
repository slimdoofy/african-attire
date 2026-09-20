<?php
/**
 * Migration 006 — Payment-before-order columns
 *
 * Ensures the orders table has the columns needed for the
 * payment-first checkout flow:
 *   - payment_reference  (Paystack transaction reference)
 *   - notes              (internal flags, e.g. amount mismatch)
 *
 * These columns exist in database.sql but may be missing on older
 * installations that ran the schema before this feature.
 */
return [
    'id'          => '006',
    'title'       => 'Payment-before-order schema columns',
    'description' => 'Adds payment_reference and notes columns to orders table if not present.',
    'steps'       => [

        '006a' => [
            'label' => 'Add payment_reference column',
            'sql'   => "ALTER TABLE `orders`
                        ADD COLUMN `payment_reference` VARCHAR(120) DEFAULT NULL
                        AFTER `payment_method`",
            'safe'  => true,
        ],

        '006b' => [
            'label' => 'Add notes column (internal flags)',
            'sql'   => "ALTER TABLE `orders`
                        ADD COLUMN `notes` TEXT DEFAULT NULL
                        AFTER `payment_status`",
            'safe'  => true,
        ],

        '006c' => [
            'label' => 'Index payment_reference for fast duplicate checks',
            'sql'   => "ALTER TABLE `orders`
                        ADD INDEX `idx_payment_ref` (`payment_reference`)",
            'safe'  => true,
        ],

    ],
];
