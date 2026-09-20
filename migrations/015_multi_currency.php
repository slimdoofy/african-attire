<?php
/**
 * Migration 015 — Euro and Pound Sterling support
 * Adds EUR and GBP exchange rate settings and enables them in the platform.
 */
return [
    'id'    => '015',
    'title' => 'EUR and GBP multi-currency support',
    'steps' => [
        '015a' => ['label'=>'EUR/NGN rate',    'sql'=>"INSERT IGNORE INTO `settings` (`key`,`value`,`label`) VALUES ('eur_ngn_rate','1740','EUR to NGN Rate (1 EUR = X NGN)')"],
        '015b' => ['label'=>'NGN/EUR rate',    'sql'=>"INSERT IGNORE INTO `settings` (`key`,`value`,`label`) VALUES ('ngn_eur_rate','0.000575','NGN to EUR Rate')"],
        '015c' => ['label'=>'GBP/NGN rate',    'sql'=>"INSERT IGNORE INTO `settings` (`key`,`value`,`label`) VALUES ('gbp_ngn_rate','2030','GBP to NGN Rate (1 GBP = X NGN)')"],
        '015d' => ['label'=>'NGN/GBP rate',    'sql'=>"INSERT IGNORE INTO `settings` (`key`,`value`,`label`) VALUES ('ngn_gbp_rate','0.000493','NGN to GBP Rate')"],
        '015e' => ['label'=>'EUR enabled',     'sql'=>"INSERT IGNORE INTO `settings` (`key`,`value`,`label`) VALUES ('currency_eur_enabled','1','Enable EUR (Euro) currency')"],
        '015f' => ['label'=>'GBP enabled',     'sql'=>"INSERT IGNORE INTO `settings` (`key`,`value`,`label`) VALUES ('currency_gbp_enabled','1','Enable GBP (Pound Sterling) currency')"],
    ],
];
