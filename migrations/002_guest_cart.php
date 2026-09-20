<?php
/**
 * Migration 002 — Guest cart (session-based)
 *
 * Creates the guest_cart_items table so unauthenticated visitors
 * can add items to a cart tied to their PHP session.
 */
return [
    'id'          => '002',
    'title'       => 'Guest cart (session-based)',
    'description' => 'Stores cart items for non-logged-in visitors using session IDs.',
    'steps'       => [

        '002a' => [
            'label' => 'Create guest_cart_items table',
            'sql'   => "CREATE TABLE IF NOT EXISTS `guest_cart_items` (
                          `id`         INT(11) NOT NULL AUTO_INCREMENT,
                          `session_id` VARCHAR(128) NOT NULL,
                          `product_id` INT(11) NOT NULL,
                          `quantity`   INT(11) NOT NULL DEFAULT 1,
                          `size`       VARCHAR(20) DEFAULT NULL,
                          `added_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          KEY `idx_session` (`session_id`),
                          KEY `fk_gc_prod` (`product_id`),
                          CONSTRAINT `fk_gc_prod` FOREIGN KEY (`product_id`)
                            REFERENCES `products`(`id`) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],

        '002b' => [
            'label' => 'Unique index on session + product + size',
            'sql'   => "ALTER TABLE `guest_cart_items`
                        ADD UNIQUE INDEX `uq_session_prod_size`
                        (`session_id`(128), `product_id`, `size`(20))",
            'safe'  => true,
        ],

        '002c' => [
            'label' => 'Purge guest carts older than 30 days',
            'sql'   => "DELETE FROM `guest_cart_items`
                        WHERE `added_at` < DATE_SUB(NOW(), INTERVAL 30 DAY)",
        ],
    ],
];
