<?php
/**
 * Migration 012 — Logistics fee configuration & distance support
 *
 * 1. logistics_base_fare_ngn / logistics_base_fare_usd — admin-configurable
 * 2. logistics_fee_per_km_ngn / logistics_fee_per_km_usd — rate per km
 * 3. logistics_min_fee_ngn — minimum charge floor
 * 4. city_coordinates — Nigerian & major African city lat/lng for distance calc
 * 5. order_logistics.fee_ngn / fee_usd / distance_km — store calculated fee on assignment
 * 6. shops.pickup_address / pickup_city — where merchants ship FROM
 */
return [
    'id'          => '012',
    'title'       => 'Logistics fee configuration and distance calculation',
    'description' => 'Adds fee settings, city coordinates table, and fee columns on order_logistics.',
    'steps'       => [

        '012a' => [
            'label' => 'Base fare NGN setting',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`,`value`,`label`)
                        VALUES ('logistics_base_fare_ngn','2000','Logistics Base Fare (NGN)')",
        ],
        '012b' => [
            'label' => 'Base fare USD setting',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`,`value`,`label`)
                        VALUES ('logistics_base_fare_usd','1.25','Logistics Base Fare (USD)')",
        ],
        '012c' => [
            'label' => 'Fee per km NGN setting',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`,`value`,`label`)
                        VALUES ('logistics_per_km_ngn','150','Logistics Fee per km (NGN)')",
        ],
        '012d' => [
            'label' => 'Fee per km USD setting',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`,`value`,`label`)
                        VALUES ('logistics_per_km_usd','0.09','Logistics Fee per km (USD)')",
        ],
        '012e' => [
            'label' => 'Minimum logistics fee NGN',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`,`value`,`label`)
                        VALUES ('logistics_min_fee_ngn','2500','Minimum Logistics Fee (NGN)')",
        ],
        '012f' => [
            'label' => 'Google Maps API key setting (optional)',
            'sql'   => "INSERT IGNORE INTO `settings` (`key`,`value`,`label`)
                        VALUES ('google_maps_api_key','','Google Maps API Key (optional — for accurate distance)')",
        ],

        '012g' => [
            'label' => 'Create city_coordinates table',
            'sql'   => "CREATE TABLE IF NOT EXISTS `city_coordinates` (
                          `id`      INT(11) NOT NULL AUTO_INCREMENT,
                          `city`    VARCHAR(100) NOT NULL,
                          `state`   VARCHAR(100) DEFAULT NULL,
                          `country` VARCHAR(80)  NOT NULL DEFAULT 'Nigeria',
                          `lat`     DECIMAL(10,7) NOT NULL,
                          `lng`     DECIMAL(10,7) NOT NULL,
                          PRIMARY KEY (`id`),
                          KEY `idx_city` (`city`),
                          KEY `idx_country` (`country`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],

        '012h' => [
            'label' => 'Seed Nigerian state capitals & major cities',
            'sql'   => "INSERT IGNORE INTO `city_coordinates`
                          (`city`,`state`,`country`,`lat`,`lng`) VALUES
                        ('Lagos','Lagos','Nigeria',6.5244,3.3792),
                        ('Abuja','FCT','Nigeria',9.0579,7.4951),
                        ('Kano','Kano','Nigeria',12.0022,8.5920),
                        ('Ibadan','Oyo','Nigeria',7.3775,3.9470),
                        ('Port Harcourt','Rivers','Nigeria',4.8156,7.0498),
                        ('Benin City','Edo','Nigeria',6.3350,5.6271),
                        ('Maiduguri','Borno','Nigeria',11.8333,13.1500),
                        ('Zaria','Kaduna','Nigeria',11.0851,7.7187),
                        ('Aba','Abia','Nigeria',5.1066,7.3667),
                        ('Jos','Plateau','Nigeria',9.9285,8.8921),
                        ('Ilorin','Kwara','Nigeria',8.4966,4.5421),
                        ('Oyo','Oyo','Nigeria',7.8526,3.9347),
                        ('Enugu','Enugu','Nigeria',6.4584,7.5464),
                        ('Abeokuta','Ogun','Nigeria',7.1557,3.3451),
                        ('Onitsha','Anambra','Nigeria',6.1667,6.7833),
                        ('Warri','Delta','Nigeria',5.5167,5.7500),
                        ('Sokoto','Sokoto','Nigeria',13.0622,5.2339),
                        ('Kaduna','Kaduna','Nigeria',10.5264,7.4382),
                        ('Calabar','Cross River','Nigeria',4.9517,8.3220),
                        ('Akure','Ondo','Nigeria',7.2526,5.1947),
                        ('Bauchi','Bauchi','Nigeria',10.3158,9.8442),
                        ('Minna','Niger','Nigeria',9.6139,6.5569),
                        ('Owerri','Imo','Nigeria',5.4836,7.0333),
                        ('Uyo','Akwa Ibom','Nigeria',5.0510,7.9330),
                        ('Asaba','Delta','Nigeria',6.1981,6.7337),
                        ('Abakaliki','Ebonyi','Nigeria',6.3249,8.1137),
                        ('Awka','Anambra','Nigeria',6.2104,7.0739),
                        ('Makurdi','Benue','Nigeria',7.7346,8.5227),
                        ('Lokoja','Kogi','Nigeria',7.8026,6.7368),
                        ('Lafia','Nasarawa','Nigeria',8.4937,8.5141),
                        ('Jalingo','Taraba','Nigeria',8.8921,11.3637),
                        ('Yola','Adamawa','Nigeria',9.2035,12.4954),
                        ('Gombe','Gombe','Nigeria',10.2791,11.1671),
                        ('Damaturu','Yobe','Nigeria',11.7470,11.9607),
                        ('Birnin Kebbi','Kebbi','Nigeria',12.4539,4.1975),
                        ('Gusau','Zamfara','Nigeria',12.1704,6.6641),
                        ('Dutse','Jigawa','Nigeria',11.8607,9.3449),
                        ('Katsina','Katsina','Nigeria',12.9888,7.6006),
                        ('Ado Ekiti','Ekiti','Nigeria',7.6237,5.2216),
                        ('Osogbo','Osun','Nigeria',7.7717,4.5570),
                        -- International cities
                        ('Accra',NULL,'Ghana',5.6037,-0.1870),
                        ('Nairobi',NULL,'Kenya',-1.2921,36.8219),
                        ('London',NULL,'United Kingdom',51.5074,-0.1278),
                        ('New York',NULL,'United States',40.7128,-74.0060),
                        ('Houston',NULL,'United States',29.7604,-95.3698),
                        ('Atlanta',NULL,'United States',33.7490,-84.3880),
                        ('Washington DC',NULL,'United States',38.9072,-77.0369),
                        ('Johannesburg',NULL,'South Africa',-26.2041,28.0473),
                        ('Dakar',NULL,'Senegal',14.7167,-17.4677)",
        ],

        '012i' => [
            'label' => 'Add fee columns to order_logistics',
            'sql'   => "ALTER TABLE `order_logistics`
                        ADD COLUMN `distance_km`   DECIMAL(10,2) NULL DEFAULT NULL
                                   COMMENT 'Calculated km between merchant and delivery city'
                        AFTER `notes`,
                        ADD COLUMN `fee_ngn`       DECIMAL(12,2) NULL DEFAULT NULL
                                   COMMENT 'Calculated logistics fee in NGN'
                        AFTER `distance_km`,
                        ADD COLUMN `fee_usd`       DECIMAL(10,2) NULL DEFAULT NULL
                                   COMMENT 'Calculated logistics fee in USD'
                        AFTER `fee_ngn`,
                        ADD COLUMN `fee_method`    VARCHAR(50)   NULL DEFAULT NULL
                                   COMMENT 'How fee was calculated: haversine|google_maps|manual'
                        AFTER `fee_usd`",
            'safe'  => true,
        ],

        '012j' => [
            'label' => 'Add pickup_address and pickup_city to shops',
            'sql'   => "ALTER TABLE `shops`
                        ADD COLUMN `pickup_address` TEXT NULL DEFAULT NULL
                                   COMMENT 'Merchant collection/pickup address for logistics'
                        AFTER `business_address`,
                        ADD COLUMN `pickup_city` VARCHAR(100) NULL DEFAULT NULL
                        AFTER `pickup_address`",
            'safe'  => true,
        ],
    ],
];
