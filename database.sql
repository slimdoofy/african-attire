-- ============================================================
-- African Attire Platform — MySQL Database Schema
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `african_attire`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `african_attire`;

-- ─── Users ──────────────────────────────────────────────────
CREATE TABLE `users` (
  `id`             INT(11) NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(150) NOT NULL,
  `email`          VARCHAR(191) NOT NULL,
  `phone`          VARCHAR(25) DEFAULT NULL,
  `password_hash`  VARCHAR(255) NOT NULL,
  `role`           ENUM('customer','merchant','admin') NOT NULL DEFAULT 'customer',
  `status`         ENUM('active','suspended','pending') NOT NULL DEFAULT 'active',
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `avatar`         VARCHAR(255) DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Customer Profiles ──────────────────────────────────────
CREATE TABLE `customer_profiles` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`      INT(11) NOT NULL,
  `address_line1`VARCHAR(255) DEFAULT NULL,
  `address_line2`VARCHAR(255) DEFAULT NULL,
  `city`         VARCHAR(100) DEFAULT NULL,
  `state`        VARCHAR(100) DEFAULT NULL,
  `country`      VARCHAR(100) DEFAULT 'Nigeria',
  `postal_code`  VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_cp_user` (`user_id`),
  CONSTRAINT `fk_cp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Shops ──────────────────────────────────────────────────
CREATE TABLE `shops` (
  `id`               INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`          INT(11) NOT NULL,
  `shop_name`        VARCHAR(150) NOT NULL,
  `slug`             VARCHAR(180) NOT NULL,
  `description`      TEXT DEFAULT NULL,
  `logo`             VARCHAR(255) DEFAULT NULL,
  `banner`           VARCHAR(255) DEFAULT NULL,
  `city`             VARCHAR(100) DEFAULT NULL,
  `country`          VARCHAR(100) DEFAULT 'Nigeria',
  `bank_name`        VARCHAR(100) DEFAULT NULL,
  `bank_account`     VARCHAR(30)  DEFAULT NULL,
  `bank_account_name`VARCHAR(150) DEFAULT NULL,
  `id_document`      VARCHAR(255) DEFAULT NULL,
  `status`           ENUM('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
  `commission_rate`  DECIMAL(5,2) NOT NULL DEFAULT 20.00,
  `total_revenue`    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_withdrawn`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_unique` (`slug`),
  KEY `fk_shop_user` (`user_id`),
  CONSTRAINT `fk_shop_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Categories ─────────────────────────────────────────────
CREATE TABLE `categories` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `slug`       VARCHAR(110) NOT NULL,
  `icon`       VARCHAR(10)  DEFAULT '👗',
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cat_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`name`,`slug`,`icon`,`sort_order`) VALUES
('Ankara',      'ankara',      '🎨', 1),
('Agbada',      'agbada',      '👘', 2),
('Aso Ebi',     'aso-ebi',     '👗', 3),
('Kaftan',      'kaftan',      '🥻', 4),
('Kente',       'kente',       '🌟', 5),
('Dashiki',     'dashiki',     '👕', 6),
('Boubou',      'boubou',      '🥻', 7),
('Adire',       'adire',       '🎭', 8),
('Accessories', 'accessories', '💍', 9),
('Footwear',    'footwear',    '👡', 10);

-- ─── Products ───────────────────────────────────────────────
CREATE TABLE `products` (
  `id`                  INT(11) NOT NULL AUTO_INCREMENT,
  `shop_id`             INT(11) NOT NULL,
  `category_id`         INT(11) NOT NULL,
  `name`                VARCHAR(220) NOT NULL,
  `slug`                VARCHAR(250) NOT NULL,
  `description`         TEXT DEFAULT NULL,
  `price`               DECIMAL(12,2) NOT NULL,
  `original_price`      DECIMAL(12,2) DEFAULT NULL,
  `sizes`               VARCHAR(255) DEFAULT NULL,
  `gender`              ENUM('male','female','unisex','kids') NOT NULL DEFAULT 'unisex',
  `quantity`            INT(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` INT(11) NOT NULL DEFAULT 5,
  `status`              ENUM('pending','approved','rejected','archived') NOT NULL DEFAULT 'pending',
  `featured`            TINYINT(1) NOT NULL DEFAULT 0,
  `sales_count`         INT(11) NOT NULL DEFAULT 0,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_prod_shop` (`shop_id`),
  KEY `fk_prod_cat`  (`category_id`),
  CONSTRAINT `fk_prod_shop` FOREIGN KEY (`shop_id`)     REFERENCES `shops`(`id`)      ON DELETE CASCADE,
  CONSTRAINT `fk_prod_cat`  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Product Images ──────────────────────────────────────────
CREATE TABLE `product_images` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_pi_product` (`product_id`),
  CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Cart ───────────────────────────────────────────────────
CREATE TABLE `cart_items` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `quantity`   INT(11) NOT NULL DEFAULT 1,
  `size`       VARCHAR(20) DEFAULT NULL,
  `added_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_prod_size` (`user_id`,`product_id`,`size`),
  KEY `fk_cart_prod` (`product_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_cart_prod` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Wishlists ───────────────────────────────────────────────
CREATE TABLE `wishlists` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `added_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_prod_wl` (`user_id`,`product_id`),
  KEY `fk_wl_prod` (`product_id`),
  CONSTRAINT `fk_wl_user` FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_wl_prod` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Orders ─────────────────────────────────────────────────
CREATE TABLE `orders` (
  `id`               INT(11) NOT NULL AUTO_INCREMENT,
  `order_number`     VARCHAR(25) NOT NULL,
  `user_id`          INT(11) NOT NULL,
  `total_amount`     DECIMAL(12,2) NOT NULL,
  `delivery_address` TEXT NOT NULL,
  `payment_method`   VARCHAR(50) NOT NULL DEFAULT 'paystack',
  `payment_reference`VARCHAR(120) DEFAULT NULL,
  `payment_status`   ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `status`           ENUM('pending','processing','shipped','delivered','cancelled','disputed') NOT NULL DEFAULT 'pending',
  `notes`            TEXT DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_num_unique` (`order_number`),
  KEY `fk_order_user` (`user_id`),
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Order Items ─────────────────────────────────────────────
CREATE TABLE `order_items` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `order_id`     INT(11) NOT NULL,
  `product_id`   INT(11) NOT NULL,
  `shop_id`      INT(11) NOT NULL,
  `product_name` VARCHAR(220) NOT NULL,
  `price`        DECIMAL(12,2) NOT NULL,
  `quantity`     INT(11) NOT NULL,
  `size`         VARCHAR(20) DEFAULT NULL,
  `status`       ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `fk_oi_order`   (`order_id`),
  KEY `fk_oi_product` (`product_id`),
  KEY `fk_oi_shop`    (`shop_id`),
  CONSTRAINT `fk_oi_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
  CONSTRAINT `fk_oi_shop`    FOREIGN KEY (`shop_id`)    REFERENCES `shops`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Payouts ─────────────────────────────────────────────────
CREATE TABLE `payouts` (
  `id`                  INT(11) NOT NULL AUTO_INCREMENT,
  `shop_id`             INT(11) NOT NULL,
  `amount`              DECIMAL(12,2) NOT NULL,
  `commission_deducted` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `net_amount`          DECIMAL(12,2) NOT NULL,
  `status`              ENUM('pending','approved','paid','rejected') NOT NULL DEFAULT 'pending',
  `requested_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at`        DATETIME DEFAULT NULL,
  `notes`               TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_payout_shop` (`shop_id`),
  CONSTRAINT `fk_payout_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Banners ─────────────────────────────────────────────────
CREATE TABLE `banners` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(220) NOT NULL,
  `subtitle`   VARCHAR(255) DEFAULT NULL,
  `image`      VARCHAR(255) DEFAULT NULL,
  `link`       VARCHAR(255) DEFAULT NULL,
  `position`   ENUM('hero','mid','sidebar') NOT NULL DEFAULT 'hero',
  `active`     TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Settings ────────────────────────────────────────────────
CREATE TABLE `settings` (
  `key`   VARCHAR(100) NOT NULL,
  `value` TEXT DEFAULT NULL,
  `label` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings`(`key`,`value`,`label`) VALUES
('platform_commission','20','Default Platform Commission (%)'),
('min_payout','5000','Minimum Payout Amount (₦)'),
('paystack_public_key','pk_test_xxxxxxxxxxxxxxxx','Paystack Public Key'),
('paystack_secret_key','sk_test_xxxxxxxxxxxxxxxx','Paystack Secret Key'),
('site_name','African Attire','Site Name'),
('site_email','hello@africanattire.com','Support Email'),
('low_stock_threshold','5','Low Stock Alert Threshold'),
('site_tagline','Wear the Continent\'s Finest','Site Tagline'),
('maintenance_mode','0','Maintenance Mode (1=on, 0=off)');

-- ─── Notifications ───────────────────────────────────────────
CREATE TABLE `notifications` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11) NOT NULL,
  `type`       VARCHAR(60) NOT NULL,
  `title`      VARCHAR(220) NOT NULL,
  `message`    TEXT NOT NULL,
  `link`       VARCHAR(255) DEFAULT NULL,
  `read_at`    DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Disputes ────────────────────────────────────────────────
CREATE TABLE `disputes` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `order_id`   INT(11) NOT NULL,
  `user_id`    INT(11) NOT NULL,
  `reason`     TEXT NOT NULL,
  `status`     ENUM('open','under_review','resolved','closed') NOT NULL DEFAULT 'open',
  `resolution` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_dispute_order` (`order_id`),
  KEY `fk_dispute_user`  (`user_id`),
  CONSTRAINT `fk_dispute_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`),
  CONSTRAINT `fk_dispute_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Seed: Admin user (password: Admin@1234) ─────────────────
INSERT INTO `users`(`name`,`email`,`phone`,`password_hash`,`role`,`status`,`email_verified`) VALUES
('Platform Admin','admin@africanattire.com','+2348000000001','$2y$11$FeErqucwElYZUGNv6MUQ6u8khM8h.iHV.CAEsrVGyAlt1aCf8OdeK','admin','active',1);
-- Password: Admin@1234
