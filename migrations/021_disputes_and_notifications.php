<?php
return [
    'id'    => '021',
    'title' => 'Disputes enhancements + notification email settings',
    'steps' => [
        '021a' => [
            'label' => 'Allow guest disputes — make user_id nullable, add guest fields + description + evidence + resolved_at',
            'sql'   => "ALTER TABLE `disputes`
                        MODIFY COLUMN `user_id`      INT(11)       NULL DEFAULT NULL,
                        ADD COLUMN `guest_name`      VARCHAR(150)  NULL DEFAULT NULL AFTER `user_id`,
                        ADD COLUMN `guest_email`     VARCHAR(191)  NULL DEFAULT NULL AFTER `guest_name`,
                        ADD COLUMN `order_number`    VARCHAR(25)   NULL DEFAULT NULL AFTER `guest_email`,
                        ADD COLUMN `description`     TEXT          NULL DEFAULT NULL AFTER `reason`,
                        ADD COLUMN `evidence_url`    VARCHAR(255)  NULL DEFAULT NULL AFTER `description`,
                        ADD COLUMN `admin_note`      TEXT          NULL DEFAULT NULL AFTER `resolution`,
                        ADD COLUMN `resolved_at`     DATETIME      NULL DEFAULT NULL AFTER `admin_note`,
                        ADD COLUMN `updated_at`      DATETIME      NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `resolved_at`",
            'safe'  => true,
        ],
        '021b' => [
            'label' => 'Notification email — merchant registration',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`,`value`,`label`)
                        VALUES ('notify_merchant_registration','','Email(s) to notify on new merchant registration (comma-separated)')",
        ],
        '021c' => [
            'label' => 'Notification email — product submitted for review',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`,`value`,`label`)
                        VALUES ('notify_product_review','','Email(s) to notify when a product is submitted for review (comma-separated)')",
        ],
        '021d' => [
            'label' => 'Notification email — new order placed',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`,`value`,`label`)
                        VALUES ('notify_new_order','','Email(s) to notify on new order (comma-separated)')",
        ],
        '021e' => [
            'label' => 'Notification email — new dispute raised',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`,`value`,`label`)
                        VALUES ('notify_new_dispute','','Email(s) to notify on new dispute (comma-separated)')",
        ],
    ],
];
