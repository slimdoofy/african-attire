<?php
return [
    'id'    => '022',
    'title' => 'Allow NULL shop_id on payouts for logistics payouts',
    'steps' => [
        '022a' => [
            'label' => 'Drop FK constraint on payouts.shop_id',
            'sql'   => "ALTER TABLE `payouts` DROP FOREIGN KEY `fk_payout_shop`",
            'safe'  => true,
        ],
        '022b' => [
            'label' => 'Make shop_id nullable',
            'sql'   => "ALTER TABLE `payouts` MODIFY COLUMN `shop_id` INT(11) NULL DEFAULT NULL",
            'safe'  => true,
        ],
        '022c' => [
            'label' => 'Re-add FK with NULL allowed',
            'sql'   => "ALTER TABLE `payouts`
                        ADD CONSTRAINT `fk_payout_shop`
                        FOREIGN KEY (`shop_id`) REFERENCES `shops`(`id`)
                        ON DELETE SET NULL",
            'safe'  => true,
        ],
    ],
];
