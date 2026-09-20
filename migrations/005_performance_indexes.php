<?php
/**
 * Migration 005 — Performance indexes
 *
 * Adds indexes that speed up common queries:
 * products by status, orders by user, notifications by user.
 */
return [
    'id'          => '005',
    'title'       => 'Performance indexes',
    'description' => 'Adds database indexes to speed up common product, order and notification queries.',
    'steps'       => [

        '005a' => [
            'label' => 'Index products.status (shop listing filters)',
            'sql'   => "ALTER TABLE `products` ADD INDEX `idx_status` (`status`)",
            'safe'  => true,
        ],

        '005b' => [
            'label' => 'Index products.featured (homepage featured section)',
            'sql'   => "ALTER TABLE `products` ADD INDEX `idx_featured` (`featured`)",
            'safe'  => true,
        ],

        '005c' => [
            'label' => 'Index orders.user_id (my orders page)',
            'sql'   => "ALTER TABLE `orders` ADD INDEX `idx_orders_user` (`user_id`)",
            'safe'  => true,
        ],

        '005d' => [
            'label' => 'Index notifications.user_id (notification bell)',
            'sql'   => "ALTER TABLE `notifications` ADD INDEX `idx_notif_user` (`user_id`)",
            'safe'  => true,
        ],

        '005e' => [
            'label' => 'Index wishlists.user_id (wishlist page)',
            'sql'   => "ALTER TABLE `wishlists` ADD INDEX `idx_wish_user` (`user_id`)",
            'safe'  => true,
        ],

        '005f' => [
            'label' => 'Index cart_items.user_id (cart queries)',
            'sql'   => "ALTER TABLE `cart_items` ADD INDEX `idx_cart_user` (`user_id`)",
            'safe'  => true,
        ],

    ],
];
