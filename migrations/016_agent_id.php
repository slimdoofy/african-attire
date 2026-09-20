<?php
/**
 * Migration 016 — Agent ID on shops table
 */
return [
    'id'    => '016',
    'title' => 'Add agent_id to shops',
    'steps' => [
        '016a' => [
            'label' => 'Add agent_id column',
            'sql'   => "ALTER TABLE `shops` ADD COLUMN `agent_id` VARCHAR(50) NULL DEFAULT NULL
                        COMMENT 'Optional referral agent ID' AFTER `pickup_city`",
            'safe'  => true,
        ],
    ],
];
