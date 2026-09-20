<?php
/**
 * Migration 018 — Switch from Commission to Markup model
 *
 * Key changes:
 * - Add `cost_price` to products (merchant's cost of goods — what they entered)
 * - `price` column now stores the CUSTOMER PRICE (cost × (1 + markup/100))
 * - Add `platform_markup` to settings (replaces platform_commission)
 * - Add `markup_rate` to shops (per-shop override)
 * - Add `cost_price` and `markup_rate` to order_items (snapshot at purchase)
 * - Add `merchant_amount` and `logistics_amount` to orders (for payout clarity)
 * - Add `payout_type` to payouts (merchant | logistics)
 * - Add `logistics_fee` to payouts for logistics payouts
 */
return [
    'id'          => '018',
    'title'       => 'Markup pricing model',
    'description' => 'Replaces commission with a markup model. Merchants receive cost of goods; platform keeps markup.',
    'steps'       => [
        '018a' => [
            'label' => 'Add cost_price to products',
            'sql'   => "ALTER TABLE `products`
                        ADD COLUMN `cost_price` DECIMAL(12,2) NULL DEFAULT NULL
                        COMMENT 'Merchant cost of goods (NGN) — price before markup'
                        AFTER `price`",
            'safe'  => true,
        ],
        '018b' => [
            'label' => 'Add markup_rate to shops',
            'sql'   => "ALTER TABLE `shops`
                        ADD COLUMN `markup_rate` DECIMAL(5,2) NULL DEFAULT NULL
                        COMMENT 'Per-shop markup override. NULL = use global platform_markup setting.'
                        AFTER `commission_rate`",
            'safe'  => true,
        ],
        '018c' => [
            'label' => 'Add platform_markup setting',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`,`value`,`label`)
                        VALUES ('platform_markup','5','Platform Markup Rate (%)')",
        ],
        '018d' => [
            'label' => 'Seed cost_price from existing prices (cost = price / 1.05 at 5% default)',
            'sql'   => "UPDATE `products` SET `cost_price` = ROUND(`price` / 1.05, 2)
                        WHERE `cost_price` IS NULL AND `price` > 0",
        ],
        '018e' => [
            'label' => 'Add cost_price and markup_rate snapshot to order_items',
            'sql'   => "ALTER TABLE `order_items`
                        ADD COLUMN `cost_price`  DECIMAL(12,2) NULL DEFAULT NULL
                        COMMENT 'Merchant cost of goods at time of order'
                        AFTER `price`,
                        ADD COLUMN `markup_rate` DECIMAL(5,2)  NULL DEFAULT NULL
                        COMMENT 'Markup % applied at time of order'
                        AFTER `cost_price`",
            'safe'  => true,
        ],
        '018f' => [
            'label' => 'Add merchant_amount and logistics_amount to orders',
            'sql'   => "ALTER TABLE `orders`
                        ADD COLUMN `merchant_amount`  DECIMAL(12,2) NULL DEFAULT NULL
                        COMMENT 'Sum of cost_price × qty — what merchants receive'
                        AFTER `delivery_fee`,
                        ADD COLUMN `platform_markup_amount` DECIMAL(12,2) NULL DEFAULT NULL
                        COMMENT 'Total markup earned by platform'
                        AFTER `merchant_amount`",
            'safe'  => true,
        ],
        '018g' => [
            'label' => 'Add payout_type and logistics columns to payouts',
            'sql'   => "ALTER TABLE `payouts`
                        ADD COLUMN `payout_type` ENUM('merchant','logistics') NOT NULL DEFAULT 'merchant'
                        AFTER `shop_id`,
                        ADD COLUMN `logistics_company_id` INT(11) NULL DEFAULT NULL
                        AFTER `payout_type`,
                        ADD COLUMN `logistics_fee_amount` DECIMAL(12,2) NULL DEFAULT NULL
                        COMMENT 'Total logistics fees being paid out'
                        AFTER `logistics_company_id`",
            'safe'  => true,
        ],
    ],
];
