<?php
/**
 * Migration 001 — Guest order support
 *
 * Makes orders.user_id nullable so guests can place orders
 * without an account. Adds guest_name, guest_email, guest_phone,
 * guest_token columns to the orders table.
 */
return [
    'id'          => '001',
    'title'       => 'Guest order support',
    'description' => 'Allows customers to checkout without creating an account.',
    'steps'       => [

        '001a' => [
            'label' => 'Make orders.user_id nullable',
            'sql'   => "ALTER TABLE `orders` MODIFY `user_id` INT(11) NULL DEFAULT NULL",
        ],

        '001b' => [
            'label' => 'Drop FK on orders.user_id (if exists)',
            'sql'   => "ALTER TABLE `orders` DROP FOREIGN KEY `fk_order_user`",
            'safe'  => true, // allowed to fail (FK may not exist)
        ],

        '001c' => [
            'label' => 'Re-add FK as nullable (ON DELETE SET NULL)',
            'sql'   => "ALTER TABLE `orders` ADD CONSTRAINT `fk_order_user`
                        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL",
            'safe'  => true,
        ],

        '001d' => [
            'label' => 'Add guest_name column',
            'sql'   => "ALTER TABLE `orders`
                        ADD COLUMN `guest_name` VARCHAR(150) NULL DEFAULT NULL AFTER `user_id`",
            'safe'  => true,
        ],

        '001e' => [
            'label' => 'Add guest_email column',
            'sql'   => "ALTER TABLE `orders`
                        ADD COLUMN `guest_email` VARCHAR(191) NULL DEFAULT NULL AFTER `guest_name`",
            'safe'  => true,
        ],

        '001f' => [
            'label' => 'Add guest_phone column',
            'sql'   => "ALTER TABLE `orders`
                        ADD COLUMN `guest_phone` VARCHAR(30) NULL DEFAULT NULL AFTER `guest_email`",
            'safe'  => true,
        ],

        '001g' => [
            'label' => 'Add guest_token column (for order tracking)',
            'sql'   => "ALTER TABLE `orders`
                        ADD COLUMN `guest_token` VARCHAR(64) NULL DEFAULT NULL AFTER `guest_phone`",
            'safe'  => true,
        ],

        '001h' => [
            'label' => 'Index guest_email',
            'sql'   => "ALTER TABLE `orders` ADD INDEX `idx_guest_email` (`guest_email`)",
            'safe'  => true,
        ],

        '001i' => [
            'label' => 'Unique index on guest_token',
            'sql'   => "ALTER TABLE `orders` ADD UNIQUE INDEX `idx_guest_token` (`guest_token`)",
            'safe'  => true,
        ],
    ],
];
