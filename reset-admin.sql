-- ============================================================
-- reset-admin.sql
-- Run this if you cannot login as admin.
-- Usage: mysql -u root -p african_attire < reset-admin.sql
-- ============================================================

USE `african_attire`;

-- Insert or update admin account
INSERT INTO `users`
  (`name`, `email`, `phone`, `password_hash`, `role`, `status`, `email_verified`, `created_at`)
VALUES
  ('Platform Admin', 'admin@africanattire.com', '+2348000000001',
   '$2y$11$gu/MvQAotXTknLzxfWCXO.ovby8/BwM82lYccUyz6t2Fl2ym88B2C',
   'admin', 'active', 1, NOW())
ON DUPLICATE KEY UPDATE
  `password_hash`  = '$2y$11$gu/MvQAotXTknLzxfWCXO.ovby8/BwM82lYccUyz6t2Fl2ym88B2C',
  `role`           = 'admin',
  `status`         = 'active',
  `email_verified` = 1,
  `name`           = 'Platform Admin';

-- Confirm
SELECT id, name, email, role, status FROM users WHERE email = 'admin@africanattire.com';

-- ============================================================
-- Login:    admin@africanattire.com / Admin@1234
-- URL:      http://local.africanattire/admin/login.php
-- Live URL: https://www.shopafricanattire.com/admin/login.php
-- ============================================================
