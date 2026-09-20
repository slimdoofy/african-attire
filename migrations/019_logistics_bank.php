<?php
return [
    'id'    => '019',
    'title' => 'Add bank account fields to logistics_companies',
    'steps' => [
        '019a' => [
            'label' => 'Add bank columns to logistics_companies',
            'sql'   => "ALTER TABLE `logistics_companies`
                        ADD COLUMN `bank_name`         VARCHAR(100) NULL DEFAULT NULL AFTER `city`,
                        ADD COLUMN `bank_account`      VARCHAR(30)  NULL DEFAULT NULL AFTER `bank_name`,
                        ADD COLUMN `bank_account_name` VARCHAR(150) NULL DEFAULT NULL AFTER `bank_account`,
                        ADD COLUMN `bank_sort_code`    VARCHAR(20)  NULL DEFAULT NULL AFTER `bank_account_name`",
            'safe'  => true,
        ],
    ],
];
