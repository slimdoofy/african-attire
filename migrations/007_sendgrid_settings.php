<?php
/**
 * Migration 007 — SendGrid email settings
 *
 * Adds SendGrid API key, verified sender email, and sender name
 * to the settings table so all transactional emails use SendGrid.
 */
return [
    'id'          => '007',
    'title'       => 'SendGrid email settings',
    'description' => 'Adds sendgrid_api_key, sendgrid_from_email, sendgrid_from_name settings.',
    'steps'       => [

        '007a' => [
            'label' => 'Add sendgrid_api_key setting',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`, `value`, `label`)
                        VALUES ('sendgrid_api_key', '', 'SendGrid API Key')",
        ],

        '007b' => [
            'label' => 'Add sendgrid_from_email setting',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`, `value`, `label`)
                        VALUES ('sendgrid_from_email', '', 'SendGrid Verified Sender Email')",
        ],

        '007c' => [
            'label' => 'Add sendgrid_from_name setting',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`, `value`, `label`)
                        VALUES ('sendgrid_from_name', 'African Attire', 'SendGrid Sender Name')",
        ],

    ],
];
