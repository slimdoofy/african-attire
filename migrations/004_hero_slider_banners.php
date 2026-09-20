<?php
/**
 * Migration 004 — Hero slider banner enhancements
 *
 * The banners table already exists from database.sql.
 * This migration adds sample hero slider records so the
 * homepage has slides immediately after setup.
 *
 * Admins manage slides at: /admin/banners.php
 */
return [
    'id'          => '004',
    'title'       => 'Hero slider — seed default banners',
    'description' => 'Adds three default hero slider banners using Unsplash images.',
    'steps'       => [

        '004a' => [
            'label' => 'Ensure banners table has sort_order column',
            'sql'   => "ALTER TABLE `banners`
                        ADD COLUMN IF NOT EXISTS `sort_order` INT(11) NOT NULL DEFAULT 0",
            'safe'  => true,
        ],

        '004b' => [
            'label' => 'Insert default hero slide 1 — Authentic African Fashion',
            'sql'   => "INSERT IGNORE INTO `banners`
                          (`title`,`subtitle`,`image`,`link`,`position`,`active`,`sort_order`,`created_at`)
                        VALUES
                          ('Authentic African Fashion Delivered',
                           \"Africa's #1 Fashion Marketplace\",
                           'https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=1280&h=420&fit=crop&q=85',
                           '/customer/shop.php',
                           'hero', 1, 1, NOW())",
        ],

        '004c' => [
            'label' => 'Insert default hero slide 2 — Royal Agbada Collections',
            'sql'   => "INSERT IGNORE INTO `banners`
                          (`title`,`subtitle`,`image`,`link`,`position`,`active`,`sort_order`,`created_at`)
                        VALUES
                          ('Royal Agbada & Aso-Oke',
                           'Premium Menswear · Wedding Season',
                           'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1280&h=420&fit=crop&q=85',
                           '/customer/shop.php?category=2',
                           'hero', 1, 2, NOW())",
        ],

        '004d' => [
            'label' => 'Insert default hero slide 3 — Aso Ebi & Bridal',
            'sql'   => "INSERT IGNORE INTO `banners`
                          (`title`,`subtitle`,`image`,`link`,`position`,`active`,`sort_order`,`created_at`)
                        VALUES
                          ('Aso Ebi & Bridal Collections',
                           'Shop the Wedding Season',
                           'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=1280&h=420&fit=crop&q=85',
                           '/customer/shop.php?category=3',
                           'hero', 1, 3, NOW())",
        ],

    ],
];
