<?php
/**
 * Migration 008 — Merchant business contact fields
 *
 * Adds business_email, business_phone, business_address columns
 * to the shops table to store the new merchant registration fields.
 */
return [
    'id'          => '008',
    'title'       => 'Merchant business contact fields',
    'description' => 'Adds business_email, business_phone, and business_address to shops table.',
    'steps'       => [

        '008a' => [
            'label' => 'Add business_email column',
            'sql'   => "ALTER TABLE `shops`
                        ADD COLUMN `business_email` VARCHAR(191) NULL DEFAULT NULL
                        AFTER `shop_name`",
            'safe'  => true,
        ],

        '008b' => [
            'label' => 'Add business_phone column',
            'sql'   => "ALTER TABLE `shops`
                        ADD COLUMN `business_phone` VARCHAR(30) NULL DEFAULT NULL
                        AFTER `business_email`",
            'safe'  => true,
        ],

        '008c' => [
            'label' => 'Add business_address column',
            'sql'   => "ALTER TABLE `shops`
                        ADD COLUMN `business_address` TEXT NULL DEFAULT NULL
                        AFTER `business_phone`",
            'safe'  => true,
        ],

        '008d' => [
            'label' => 'Add country column (if not present)',
            'sql'   => "ALTER TABLE `shops`
                        ADD COLUMN `country` VARCHAR(80) NULL DEFAULT 'Nigeria'
                        AFTER `city`",
            'safe'  => true,
        ],

    ],
];
