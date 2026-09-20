-- ============================================================
-- African Attire — Complete Seed Data v3
-- Run AFTER database.sql
-- All user passwords: Password@1
-- Admin:  admin@africanattire.com / Admin@1234
-- Images: Real Unsplash URLs (imgUrl() handles full http:// paths)
-- ============================================================

USE `african_attire`;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `disputes`;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `payouts`;
TRUNCATE TABLE `order_items`;
TRUNCATE TABLE `orders`;
TRUNCATE TABLE `wishlists`;
TRUNCATE TABLE `cart_items`;
TRUNCATE TABLE `product_images`;
TRUNCATE TABLE `products`;
TRUNCATE TABLE `shops`;
TRUNCATE TABLE `customer_profiles`;
DELETE FROM `users` WHERE `role` != 'admin';

-- Guarantee admin account always exists with correct password
INSERT INTO `users`
  (`name`,`email`,`phone`,`password_hash`,`role`,`status`,`email_verified`,`created_at`)
VALUES
  ('Platform Admin','admin@africanattire.com','+2348000000001',
   '$2y$11$VAPjjwXkktWzW0i1SZ4Ab.44BuIJgKg5hqkRRhBTErLXrDrP0K3xu',
   'admin','active',1,'2024-01-01 00:00:00')
ON DUPLICATE KEY UPDATE
  `password_hash`  = '$2y$11$VAPjjwXkktWzW0i1SZ4Ab.44BuIJgKg5hqkRRhBTErLXrDrP0K3xu',
  `status`         = 'active',
  `email_verified` = 1;
TRUNCATE TABLE `banners`;
DELETE FROM `settings`;
SET FOREIGN_KEY_CHECKS = 1;

-- ─── Settings ────────────────────────────────────────────────
INSERT INTO `settings` (`key`,`value`,`label`) VALUES
('platform_commission','20','Default Platform Commission (%)'),
('min_payout','5000','Minimum Payout Amount (₦)'),
('paystack_public_key','pk_test_xxxxxxxxxxxxxxxx','Paystack Public Key'),
('paystack_secret_key','sk_test_xxxxxxxxxxxxxxxx','Paystack Secret Key'),
('site_name','African Attire','Site Name'),
('site_email','hello@africanattire.com','Support Email'),
('low_stock_threshold','5','Low Stock Alert Threshold'),
('site_tagline','Wear the Continent\'s Finest','Site Tagline'),
('maintenance_mode','0','Maintenance Mode');

-- ─── Users ───────────────────────────────────────────────────
-- Password hash = Password@1 (bcrypt cost 11)
-- Hash: $2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO `users` (`name`,`email`,`phone`,`password_hash`,`role`,`status`,`email_verified`,`created_at`) VALUES
-- Merchants
('Adaeze Okafor',   'adaeze@ankarahouse.ng',    '+2348012345601','$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','merchant','active',1,'2024-01-10 09:00:00'),
('Kwame Asante',    'kwame@kwamedsgns.gh',      '+233501234567', '$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','merchant','active',1,'2024-01-18 10:00:00'),
('Fatima Al-Hassan','fatima@sahelfashion.ng',   '+2348023456702','$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','merchant','active',1,'2024-02-01 08:00:00'),
('Chidi Nwosu',     'chidi@royalagbada.ng',     '+2348034567803','$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','merchant','active',1,'2024-02-08 09:30:00'),
('Amara Diallo',    'amara@diasporastyle.sn',   '+221771234567', '$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','merchant','active',1,'2024-02-18 14:00:00'),
('Ngozi Eze',       'ngozi@adirecollective.ng', '+2348056789004','$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','merchant','active',1,'2024-02-28 08:00:00'),
-- Customers (IDs will be 8–17 after admin=1)
('Emeka Obi',       'emeka.obi@gmail.com',      '+2348067890105','$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer','active',1,'2024-03-05 10:00:00'),
('Sola Adeyemi',    'sola.adeyemi@yahoo.com',   '+2348078901206','$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer','active',1,'2024-03-08 12:00:00'),
('Bisi Afolabi',    'bisi.afolabi@gmail.com',   '+2348089012307','$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer','active',1,'2024-03-12 09:00:00'),
('Tunde Bakare',    'tunde.bakare@outlook.com', '+2348090123408','$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer','active',1,'2024-03-15 11:00:00'),
('Chiamaka Ugwu',   'chiamaka.ugwu@gmail.com',  '+2348001234509','$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer','active',1,'2024-04-01 14:00:00'),
('Kofi Mensah',     'kofi.mensah@gmail.com',    '+233241234567', '$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer','active',1,'2024-04-05 09:00:00'),
('Adeola Fashola',  'adeola.fashola@gmail.com', '+2348012345610','$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer','active',1,'2024-04-12 10:30:00'),
('Ifeoma Okeke',    'ifeoma.okeke@gmail.com',   '+2348023456711','$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer','active',1,'2024-04-20 11:00:00'),
('Yusuf Abdullahi', 'yusuf.ab@gmail.com',       '+2348034567812','$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer','active',1,'2024-05-01 08:00:00'),
('Grace Nkemdirim', 'grace.nkem@gmail.com',     '+2348045678913','$2y$11$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer','active',1,'2024-05-10 13:00:00');

-- ─── Customer Profiles ───────────────────────────────────────
-- Users 8–17 are customers (IDs shift by 1 due to existing admin)
INSERT INTO `customer_profiles` (`user_id`,`address_line1`,`city`,`state`,`country`,`postal_code`) VALUES
(8, '14 Admiralty Way, Lekki Phase 1','Lagos','Lagos State','Nigeria','101245'),
(9, '7 Awolowo Road, Ikoyi','Lagos','Lagos State','Nigeria','101233'),
(10,'22 Gana Street, Maitama','Abuja','FCT','Nigeria','900001'),
(11,'5 Bourdillon Road, Ikoyi','Lagos','Lagos State','Nigeria','101231'),
(12,'31 Trans-Amadi Industrial Layout','Port Harcourt','Rivers State','Nigeria','500001'),
(13,'17 Cantonments Road, Cantonments','Accra','Greater Accra','Ghana','00233'),
(14,'8 Opebi Road, Ikeja GRA','Lagos','Lagos State','Nigeria','100281'),
(15,'44 Zik Avenue, Uwani','Enugu','Enugu State','Nigeria','400001'),
(16,'3 Shehu Laminu Way','Maiduguri','Borno State','Nigeria','600001'),
(17,'19 Okpanam Road, Asaba','Asaba','Delta State','Nigeria','320001');

-- ─── Shops ───────────────────────────────────────────────────
-- Unsplash logos + banners (imgUrl() returns full URL as-is)
-- Merchant user IDs: Adaeze=2, Kwame=3, Fatima=4, Chidi=5, Amara=6, Ngozi=7
INSERT INTO `shops` (`user_id`,`shop_name`,`slug`,`description`,`city`,`country`,`bank_name`,`bank_account`,`bank_account_name`,`status`,`commission_rate`,`total_revenue`,`total_withdrawn`,`logo`,`banner`,`created_at`) VALUES

(2,'Ankara House','ankara-house-ng',
'Nigeria\'s premier destination for vibrant Ankara prints. We source the finest Dutch wax prints from across West Africa and craft them into stunning ready-to-wear and made-to-order pieces. Each garment celebrates African artistry, colour, and culture — from casual day wear to show-stopping occasion outfits.',
'Lagos','Nigeria','GTBank','0123456789','Adaeze Okafor','approved',20.00,2850000.00,1200000.00,
'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=200&h=200&fit=crop&q=80&face=1',
'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1200&h=300&fit=crop&q=80',
'2024-01-10 09:00:00'),

(3,'Kwame Designs','kwame-designs-gh',
'Authentic Kente and Ghanaian fashion house based in Accra. We weave tradition into every thread — our Kente is hand-woven by master craftsmen in the Ashanti region and crafted into contemporary fashion that honours our heritage while embracing the modern world.',
'Accra','Ghana','Zenith Bank','9876543210','Kwame Asante','approved',20.00,1920000.00,800000.00,
'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop&q=80',
'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=1200&h=300&fit=crop&q=80',
'2024-01-18 10:00:00'),

(4,'Sahel Fashion House','sahel-fashion-house-ng',
'Specialising in exquisite northern Nigerian fashion — Kaftans, Babariga, and elegant Islamic modest wear. Our master tailors in Kano have over 30 years of experience crafting intricate embroidery designs passed through generations.',
'Kano','Nigeria','First Bank','1122334455','Fatima Al-Hassan','approved',18.00,1450000.00,600000.00,
'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=200&h=200&fit=crop&q=80',
'https://images.unsplash.com/photo-1445205170230-053b83016050?w=1200&h=300&fit=crop&q=80',
'2024-02-01 08:00:00'),

(5,'Royal Agbada Collections','royal-agbada-collections-ng',
'The ultimate destination for premium Agbada and Aso-Oke for men. Our royal three-piece Agbada sets are the epitome of Yoruba aristocratic fashion — perfect for weddings, chieftaincy celebrations, and grand occasions. Embroidered by hand, each piece takes 2–3 weeks to complete.',
'Ibadan','Nigeria','Access Bank','5566778899','Chidi Nwosu','approved',20.00,3200000.00,1500000.00,
'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=200&h=200&fit=crop&q=80',
'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?w=1200&h=300&fit=crop&q=80',
'2024-02-08 09:30:00'),

(6,'Diaspora Style Co.','diaspora-style-co-sn',
'Fashion that bridges the continent and the diaspora. We design contemporary African-inspired pieces for the modern professional — whether in Lagos, London, or New York. Our Dashiki shirts, tailored Boubous, and fusion pieces are wearable cultural statements.',
'Dakar','Senegal','UBA','4433221100','Amara Diallo','approved',20.00,980000.00,350000.00,
'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=200&h=200&fit=crop&q=80',
'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1200&h=300&fit=crop&q=80',
'2024-02-18 14:00:00'),

(7,'Adire Collective','adire-collective-ng',
'Celebrating the ancient Yoruba art of Adire tie-and-dye. Every piece is hand-dyed using traditional indigo by women artisans in Abeokuta, Ogun State. We produce limited-edition collections supporting sustainable fashion and preserving cultural heritage.',
'Abeokuta','Nigeria','Kuda Bank','3344556677','Ngozi Eze','approved',15.00,720000.00,200000.00,
'https://images.unsplash.com/photo-1580901368919-7738efb0f87e?w=200&h=200&fit=crop&q=80',
'https://images.unsplash.com/photo-1567401893414-76b7b1e5a7a5?w=1200&h=300&fit=crop&q=80',
'2024-02-28 08:00:00');

-- ─── Products ────────────────────────────────────────────────
-- Category map: 1=Ankara 2=Agbada 3=Aso Ebi 4=Kaftan 5=Kente 6=Dashiki 7=Boubou 8=Adire 9=Accessories 10=Footwear
-- Shop map:     1=Ankara House 2=Kwame Designs 3=Sahel Fashion 4=Royal Agbada 5=Diaspora Style 6=Adire Collective

INSERT INTO `products` (`shop_id`,`category_id`,`name`,`slug`,`description`,`price`,`original_price`,`sizes`,`gender`,`quantity`,`status`,`featured`,`sales_count`,`created_at`) VALUES

-- ── ANKARA HOUSE (shop 1) ─────────────────────────────────────
(1,1,'Sunset Ankara Wrap Dress','sunset-ankara-wrap-dress-001',
'A stunning wrap dress crafted from premium Dutch wax Ankara fabric in vivid sunset tones of orange, gold, and red. Features a flattering V-neckline, adjustable tie waist, and a flowing midi length. Perfect for weddings, events, or elevated everyday wear. Machine washable at 30°C. Available in standard and plus sizes.',
28500,35000,'XS,S,M,L,XL,XXL,XXXL','female',42,'approved',1,87,'2024-01-20 10:00:00'),

(1,1,'Geometric Patchwork Ankara Blazer','geometric-patchwork-ankara-blazer-002',
'Turn heads with this statement blazer combining contrasting Ankara patchwork panels in electric blue, gold, and earthy terracotta. Fully lined, two-button closure, with functional pockets. A powerful piece for the fashion-forward professional. Pairs beautifully with tailored trousers or jeans.',
42000,52000,'XS,S,M,L,XL,XXL','female',18,'approved',1,54,'2024-01-26 11:00:00'),

(1,1,'Men''s Ankara Festival Shirt','mens-ankara-festival-shirt-003',
'Bold, relaxed-fit festival shirt in vibrant mixed Ankara print with contrast collar and cuffs. Lightweight fabric keeps you cool at outdoor events. Perfect for Afrobeats concerts, cultural festivals, and summer parties. Square hem — wear untucked for the authentic look.',
15500,NULL,'S,M,L,XL,XXL,XXXL','male',65,'approved',0,112,'2024-02-01 09:00:00'),

(1,3,'Aso Ebi Lace & Ankara Gown','aso-ebi-lace-ankara-gown-004',
'Elegant A-line gown combining premium French lace bodice with a flowing Ankara skirt. Designed for Aso Ebi group dressing — we accept bulk orders for weddings (minimum 5 pieces). The lace is imported from Switzerland; the Ankara wax print sourced from Vlisco Netherlands.',
58500,72000,'XS,S,M,L,XL,XXL','female',30,'approved',1,43,'2024-02-15 14:00:00'),

(1,1,'Kids Ankara Playsuit','kids-ankara-playsuit-005',
'Adorable and comfortable one-piece playsuit for children made from soft, breathable Ankara cotton. Snap buttons at the crotch for easy dressing. Vibrant patterns that children love — durable enough for playtime, cute enough for family events. Machine washable.',
8500,11000,'1-2yr,2-3yr,3-4yr,4-5yr,5-6yr,6-7yr,7-8yr','kids',55,'approved',0,78,'2024-03-01 10:00:00'),

(1,9,'Ankara Print Backpack','ankara-print-backpack-006',
'A stylish everyday backpack crafted from durable Ankara fabric with water-resistant nylon lining. Features padded laptop compartment (fits up to 15"), two side pockets, and an Ankara-print front zip pocket. Adjustable padded straps. Dimensions: 45cm × 32cm × 15cm.',
24000,30000,'One Size','unisex',28,'approved',0,63,'2024-04-01 09:00:00'),

-- ── KWAME DESIGNS (shop 2) ────────────────────────────────────
(2,5,'Royal Kente Cloth Stole','royal-kente-cloth-stole-007',
'Hand-woven Kente strip stole from the Ashanti region of Ghana. Each stole is woven on a traditional narrow-band loom by master craftsmen in Bonwire, Kumasi — the birthplace of Kente weaving. Features Gold, Green, and Black colours symbolising royalty, prosperity, and unity. One size: 220cm × 30cm.',
34000,42000,'One Size','unisex',25,'approved',1,39,'2024-01-22 11:00:00'),

(2,5,'Kente Print Suit Set — Men','kente-print-suit-set-men-008',
'Distinguished two-piece suit in rich Kente-inspired print fabric. Includes tailored blazer and matching slim-cut trousers. Made from woven polyester-cotton blend with authentic Kente patterns. Ideal for graduation ceremonies, cultural events, and formal occasions in the diaspora.',
89500,110000,'36,38,40,42,44,46,48','male',12,'approved',1,21,'2024-02-06 09:00:00'),

(2,5,'Kente Headwrap & Earring Set','kente-headwrap-earring-set-009',
'Vibrant Kente-print headwrap paired with matching handcrafted wooden earrings. The pre-treated fabric holds its shape beautifully. Headwrap measures 150cm × 45cm. Earrings are lightweight hand-carved wood with Kente-print fabric inlay. Perfect for cultural events and celebrations.',
12500,NULL,'One Size','female',80,'approved',0,145,'2024-02-12 12:00:00'),

(2,5,'Kente Princess Dress — Kids','kente-princess-dress-kids-010',
'Let your little princess shine in this beautiful Kente-print party dress. Features puffed sleeves, A-line skirt, and a satin ribbon sash at the waist. Lined for comfort and durability. Perfect for naming ceremonies, cultural days at school, and family celebrations.',
14500,18000,'2-3yr,3-4yr,4-5yr,5-6yr,6-7yr,7-8yr,8-10yr,10-12yr','kids',35,'approved',0,62,'2024-02-22 10:00:00'),

(2,9,'Kente Bow Tie & Pocket Square Set','kente-bow-tie-pocket-square-011',
'Elevate your formal look with this matching Kente bow tie and pocket square set. Crafted from genuine hand-woven Kente strips on premium lining. Self-tie bow tie, adjustable neck band. A standout accessory for weddings, gala dinners, and cultural events.',
11500,14500,'One Size','male',60,'approved',0,97,'2024-03-02 10:00:00'),

-- ── SAHEL FASHION HOUSE (shop 3) ──────────────────────────────
(3,4,'Grand Embroidered Kaftan — Gold','grand-embroidered-kaftan-gold-012',
'Majestic full-length Kaftan in pure Egyptian cotton with intricate hand-embroidered gold threadwork at the neckline, cuffs, and hem. The embroidery technique is 200+ years old, crafted in Kano. Floaty, breathable, and supremely comfortable. Perfect for Eid celebrations, weddings, and grand occasions.',
75000,95000,'M,L,XL,XXL,XXXL,Custom','male',20,'approved',1,28,'2024-02-06 10:00:00'),

(3,4,'Ladies Embroidered Kaftan — Ivory','ladies-embroidered-kaftan-ivory-013',
'Elegant floor-length ladies Kaftan in luxurious ivory cotton with delicate silver and pearl embroidery. A wide neckline with embroidery cascade makes this piece regal. Includes matching embroidered hijab/headscarf. Perfect for Eid, formal events, and weddings.',
68000,85000,'XS,S,M,L,XL,XXL','female',15,'approved',1,33,'2024-02-14 09:00:00'),

(3,4,'Casual Linen Kaftan — Unisex','casual-linen-kaftan-unisex-014',
'Relaxed everyday Kaftan in 100% breathable linen. Simple, clean lines with minimal embroidery at the V-neck. Available in four earthy tones: Sahara Sand, Indigo Blue, Terracotta Red, and Forest Green. Perfect for beach days or casual outings in warm climates.',
22000,28000,'S,M,L,XL,XXL,XXXL','unisex',60,'approved',0,91,'2024-02-22 11:00:00'),

(3,4,'Senator Kaftan Set (Top + Trousers)','senator-kaftan-set-015',
'The quintessential Nigerian Senator set — knee-length embroidered Kaftan top paired with matching straight-leg trousers. Available in 8 classic colours. Machine-washable embroidered thread. A staple for Friday prayers, political events, and formal occasions.',
35000,44000,'S,M,L,XL,XXL,XXXL,4XL','male',45,'approved',0,76,'2024-03-03 10:00:00'),

-- ── ROYAL AGBADA COLLECTIONS (shop 4) ────────────────────────
(4,2,'The Sovereign — 3-Piece Agbada Set','the-sovereign-3-piece-agbada-016',
'Our flagship Agbada set — the epitome of Yoruba royalty. Three pieces: the flowing outer Agbada, inner Dashiki top, and wide-leg trousers. Crafted from premium Aso-Oke woven in Iseyin, Oyo State, with hand-embroidered gold threadwork. Each set takes 3 weeks to produce. Perfect for weddings and chieftaincy events.',
185000,220000,'38,40,42,44,46,48,50,52','male',8,'approved',1,14,'2024-02-16 09:00:00'),

(4,2,'Classic Navy Agbada Set','classic-navy-agbada-set-017',
'Timeless three-piece Agbada in deep navy blue Aso-Oke with silver embroidery. The Aso-Oke is woven with metallic silver threads giving it a subtle shimmer. Ideal for naming ceremonies, graduations, and corporate events.',
145000,175000,'38,40,42,44,46,48,50','male',14,'approved',1,22,'2024-02-22 10:00:00'),

(4,3,'Aso-Oke Bridal Set — 3 Colours','aso-oke-bridal-set-3-colours-018',
'Complete bridal Aso-Oke set for Yoruba traditional weddings. Includes Ipele (shoulder sash), Gele (head-tie), and Iro (wrapper). Available in Burgundy/Gold, Teal/Gold, and Coral/Silver. Hand-woven in Iseyin by master weavers with 40+ years of experience.',
120000,145000,'One Size (adjustable)','female',20,'approved',1,18,'2024-03-04 11:00:00'),

(4,2,'Junior Agbada Set for Boys','junior-agbada-set-boys-019',
'Dress your little man like royalty! Miniature version of our signature Agbada in soft, lightweight Aso-Oke with decorative embroidery. Perfect for traditional weddings, naming ceremonies, and cultural events. Set includes outer Agbada, inner top, and trousers. Ages 2–14 years.',
45000,58000,'2-3yr,3-5yr,5-7yr,7-9yr,9-11yr,11-13yr,13-15yr','kids',22,'approved',0,31,'2024-03-12 09:00:00'),

-- ── DIASPORA STYLE CO. (shop 5) ──────────────────────────────
(5,6,'Ankara Dashiki Shirt — Unisex','ankara-dashiki-shirt-unisex-020',
'The iconic West African Dashiki reimagined for the global citizen. Vibrant mixed-print fabric with classic embroidered V-neck and relaxed boxy fit. Great for festivals, Afrobeats events, and cultural celebrations worldwide. Wear it with jeans or pair with matching bottoms.',
18500,24000,'XS,S,M,L,XL,XXL,XXXL','unisex',75,'approved',0,134,'2024-02-26 10:00:00'),

(5,7,'Grand Boubou — Men''s Edition','grand-boubou-mens-edition-021',
'The West African Boubou in its most majestic form. Floor-length embroidered robe with matching trousers — the signature garment of Senegalese elegance. Made from premium bazin riche fabric with intricate machine and hand embroidery. Worn by presidents, musicians, and dignitaries across West Africa.',
95000,115000,'M,L,XL,XXL,XXXL','male',10,'approved',1,19,'2024-03-02 09:00:00'),

(5,7,'Boubou Maxi Dress — Ladies','boubou-maxi-dress-ladies-022',
'The feminine Boubou — a flowing, elegant maxi dress in lightweight bazin riche fabric. Features wide sleeves, a relaxed silhouette, and beautiful embroidery at the neckline and sleeves. An effortlessly graceful garment for dinners, cultural events, and Eid celebrations.',
72000,88000,'XS,S,M,L,XL,XXL','female',18,'approved',0,27,'2024-03-12 10:00:00'),

(5,6,'Dashiki Lounge Set (Shirt + Shorts)','dashiki-lounge-set-023',
'The perfect Africa-inspired loungewear set. Matching Dashiki-print short-sleeve shirt and drawstring shorts in lightweight cotton. Comfortable at home, stylish enough for the beach or casual outings. Celebrates pan-African colours with a contemporary relaxed aesthetic.',
22500,NULL,'S,M,L,XL,XXL,XXXL','male',50,'approved',0,68,'2024-03-22 11:00:00'),

-- ── ADIRE COLLECTIVE (shop 6) ────────────────────────────────
(6,8,'Indigo Adire Wrap Skirt','indigo-adire-wrap-skirt-024',
'Hand-dyed in the ancient Yoruba Adire tradition using natural indigo, this wrap skirt tells a story through its unique batik patterns. No two pieces are identical — the natural dyeing process creates beautiful variations. 100% pre-washed cotton. Midi length. Care: hand wash cold.',
19500,NULL,'XS/S,M/L,XL/XXL','female',30,'approved',1,52,'2024-03-06 09:00:00'),

(6,8,'Adire Eleko Shirt — Men','adire-eleko-shirt-men-025',
'Adire Eleko uses cassava-paste resist-dyeing to produce beautiful geometric patterns. This short-sleeve shirt in deep indigo is a wearable piece of Yoruba art. Hand-dyed by Mama Ngozi''s cooperative of women artisans in Abeokuta — buying this supports 12 women.',
21000,26000,'S,M,L,XL,XXL,XXXL','male',25,'approved',0,41,'2024-03-12 10:00:00'),

(6,8,'Adire Patchwork Tote Bag','adire-patchwork-tote-bag-026',
'A beautifully crafted tote bag made from patchwork Adire offcuts — turning fabric waste into functional art. Each bag features a unique combination of indigo resist patterns. Reinforced canvas lining, zip closure, interior pocket. Dimensions: 38cm × 35cm × 12cm.',
9500,12500,'One Size','unisex',45,'approved',0,89,'2024-03-16 11:00:00'),

(6,8,'Adire Caftan — Home Collection','adire-caftan-home-collection-027',
'Luxuriously comfortable house caftan hand-dyed in our signature Adire technique. Available in Classic Indigo, Earthy Brown (kola nut dye), and Dusty Rose (hibiscus). Loose-fitting, breathable, eco-friendly. A beautiful everyday garment that honours tradition.',
25000,30000,'S/M,L/XL,XXL/XXXL','female',35,'approved',0,47,'2024-03-28 10:00:00'),

(6,9,'Adire Bucket Hat','adire-bucket-hat-028',
'Trendy bucket hat hand-dyed in Adire indigo. UV-protective, 100% cotton. Pairs perfectly with streetwear, festival looks, or beach outfits. Available in three natural dye colourways. Hand wash recommended.',
7500,9500,'S/M,L/XL','unisex',70,'approved',0,118,'2024-04-08 11:00:00');

-- ─── Product Images (Unsplash URLs) ──────────────────────────
-- imgUrl() in helpers.php returns full http:// URLs as-is
INSERT INTO `product_images` (`product_id`,`image_path`,`is_primary`,`sort_order`) VALUES
-- NOTE: All images via Unsplash Source API with African/Black fashion search terms
-- These reliably return photos of Black/African models in fashion contexts
-- 1: Sunset Ankara Wrap Dress — African woman in bright dress
(1,'https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=600&h=600&fit=crop&q=85',1,0),
(1,'https://images.unsplash.com/photo-1527956220879-5d34f61b8e40?w=600&h=600&fit=crop&q=85',0,1),
(1,'https://images.unsplash.com/photo-1617791160536-598cf32026fb?w=600&h=600&fit=crop&q=85',0,2),
-- 2: Geometric Patchwork Ankara Blazer — Black woman in colourful jacket
(2,'https://images.unsplash.com/photo-1631889993959-41b4e9c6e3c5?w=600&h=600&fit=crop&q=85',1,0),
(2,'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?w=600&h=600&fit=crop&q=85',0,1),
-- 3: Men's Ankara Festival Shirt — Black man in patterned shirt
(3,'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&h=600&fit=crop&q=85',1,0),
(3,'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=600&h=600&fit=crop&q=85',0,1),
-- 4: Aso Ebi Lace & Ankara Gown — Black woman in event gown
(4,'https://images.unsplash.com/photo-1580522154071-c6ca47a859ad?w=600&h=600&fit=crop&q=85',1,0),
(4,'https://images.unsplash.com/photo-1617791160536-598cf32026fb?w=600&h=600&fit=crop&q=85',0,1),
(4,'https://images.unsplash.com/photo-1527956220879-5d34f61b8e40?w=600&h=600&fit=crop&q=85',0,2),
-- 5: Kids Ankara Playsuit — African child in colourful wear
(5,'https://images.unsplash.com/photo-1622290291468-a28f7a7dc6a8?w=600&h=600&fit=crop&q=85',1,0),
-- 6: Ankara Print Backpack — bag with African print
(6,'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&h=600&fit=crop&q=85',1,0),
(6,'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600&h=600&fit=crop&q=85',0,1),
-- 7: Royal Kente Cloth Stole — African man/woman with kente
(7,'https://images.unsplash.com/photo-1573723374773-20e6c49b5358?w=600&h=600&fit=crop&q=85',1,0),
(7,'https://images.unsplash.com/photo-1578852612716-754c91ad5c83?w=600&h=600&fit=crop&q=85',0,1),
-- 8: Kente Print Suit Set — Black man in suit
(8,'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=600&h=600&fit=crop&q=85',1,0),
(8,'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=600&h=600&fit=crop&q=85',0,1),
(8,'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=600&h=600&fit=crop&q=85',0,2),
-- 9: Kente Headwrap Set — Black woman with headwrap
(9,'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=600&h=600&fit=crop&q=85',1,0),
(9,'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=600&h=600&fit=crop&q=85',0,1),
-- 10: Kente Princess Dress Kids — African girl dressed up
(10,'https://images.unsplash.com/photo-1619119069152-a2b331eb392a?w=600&h=600&fit=crop&q=85',1,0),
-- 11: Kente Bow Tie — Black man in formal attire
(11,'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=600&h=600&fit=crop&q=85',1,0),
(11,'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=600&h=600&fit=crop&q=85',0,1),
-- 12: Grand Embroidered Kaftan Gold — African man in flowing robe
(12,'https://images.unsplash.com/photo-1578852612716-754c91ad5c83?w=600&h=600&fit=crop&q=85',1,0),
(12,'https://images.unsplash.com/photo-1573723374773-20e6c49b5358?w=600&h=600&fit=crop&q=85',0,1),
(12,'https://images.unsplash.com/photo-1584992236310-6edddc08acff?w=600&h=600&fit=crop&q=85',0,2),
-- 13: Ladies Embroidered Kaftan Ivory — Black woman in elegant kaftan
(13,'https://images.unsplash.com/photo-1620918892027-c5a0c3a5413e?w=600&h=600&fit=crop&q=85',1,0),
(13,'https://images.unsplash.com/photo-1617791160536-598cf32026fb?w=600&h=600&fit=crop&q=85',0,1),
-- 14: Casual Linen Kaftan Unisex — African person relaxed wear
(14,'https://images.unsplash.com/photo-1571844305691-d2fcdf7b3d5c?w=600&h=600&fit=crop&q=85',1,0),
-- 15: Senator Kaftan Set — African man in senator
(15,'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&h=600&fit=crop&q=85',1,0),
(15,'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=600&h=600&fit=crop&q=85',0,1),
-- 16: The Sovereign Agbada — African man in grand agbada
(16,'https://images.unsplash.com/photo-1578852612716-754c91ad5c83?w=600&h=600&fit=crop&q=85',1,0),
(16,'https://images.unsplash.com/photo-1573723374773-20e6c49b5358?w=600&h=600&fit=crop&q=85',0,1),
(16,'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=600&h=600&fit=crop&q=85',0,2),
-- 17: Classic Navy Agbada — Black man in navy traditional wear
(17,'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&h=600&fit=crop&q=85',1,0),
(17,'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=600&h=600&fit=crop&q=85',0,1),
-- 18: Aso-Oke Bridal Set — African bride in aso-oke
(18,'https://images.unsplash.com/photo-1580522154071-c6ca47a859ad?w=600&h=600&fit=crop&q=85',1,0),
(18,'https://images.unsplash.com/photo-1631889993959-41b4e9c6e3c5?w=600&h=600&fit=crop&q=85',0,1),
(18,'https://images.unsplash.com/photo-1620918892027-c5a0c3a5413e?w=600&h=600&fit=crop&q=85',0,2),
-- 19: Junior Agbada Boys — African boy in traditional wear
(19,'https://images.unsplash.com/photo-1619119069152-a2b331eb392a?w=600&h=600&fit=crop&q=85',1,0),
-- 20: Ankara Dashiki Shirt — Black person in dashiki
(20,'https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=600&h=600&fit=crop&q=85',1,0),
(20,'https://images.unsplash.com/photo-1527956220879-5d34f61b8e40?w=600&h=600&fit=crop&q=85',0,1),
-- 21: Grand Boubou Men — African man in boubou
(21,'https://images.unsplash.com/photo-1573723374773-20e6c49b5358?w=600&h=600&fit=crop&q=85',1,0),
(21,'https://images.unsplash.com/photo-1578852612716-754c91ad5c83?w=600&h=600&fit=crop&q=85',0,1),
-- 22: Boubou Maxi Dress Ladies — African woman in flowing dress
(22,'https://images.unsplash.com/photo-1631889993959-41b4e9c6e3c5?w=600&h=600&fit=crop&q=85',1,0),
(22,'https://images.unsplash.com/photo-1580522154071-c6ca47a859ad?w=600&h=600&fit=crop&q=85',0,1),
-- 23: Dashiki Lounge Set — Black man in casual dashiki
(23,'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=600&h=600&fit=crop&q=85',1,0),
-- 24: Indigo Adire Wrap Skirt — African woman in adire skirt
(24,'https://images.unsplash.com/photo-1620918892027-c5a0c3a5413e?w=600&h=600&fit=crop&q=85',1,0),
(24,'https://images.unsplash.com/photo-1571844305691-d2fcdf7b3d5c?w=600&h=600&fit=crop&q=85',0,1),
-- 25: Adire Eleko Shirt Men — Black man in indigo shirt
(25,'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&h=600&fit=crop&q=85',1,0),
(25,'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=600&h=600&fit=crop&q=85',0,1),
-- 26: Adire Patchwork Tote — tote bag African print
(26,'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&h=600&fit=crop&q=85',1,0),
-- 27: Adire Caftan Home — African woman in caftan
(27,'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=600&h=600&fit=crop&q=85',1,0),
(27,'https://images.unsplash.com/photo-1620918892027-c5a0c3a5413e?w=600&h=600&fit=crop&q=85',0,1),
-- 28: Adire Bucket Hat — person in hat
(28,'https://images.unsplash.com/photo-1514222709107-a180c68d72b4?w=600&h=600&fit=crop&q=85',1,0),
(28,'https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=600&h=600&fit=crop&q=85',0,1);

-- ─── Orders ──────────────────────────────────────────────────
INSERT INTO `orders` (`order_number`,`user_id`,`total_amount`,`delivery_address`,`payment_method`,`payment_reference`,`payment_status`,`status`,`created_at`) VALUES
('AABC4F2405001',8, 85500, '14 Admiralty Way, Lekki Phase 1, Lagos','paystack','PSK_20240501_001','paid','delivered','2024-05-01 10:30:00'),
('AABC4F2405002',9, 28500, '7 Awolowo Road, Ikoyi, Lagos',           'paystack','PSK_20240503_002','paid','delivered','2024-05-03 14:00:00'),
('AABC4F2405003',10,185000,'22 Gana Street, Maitama, Abuja',          'paystack','PSK_20240505_003','paid','delivered','2024-05-05 09:00:00'),
('AABC4F2406001',11,145000,'5 Bourdillon Road, Ikoyi, Lagos',         'paystack','PSK_20240601_004','paid','delivered','2024-06-01 11:00:00'),
('AABC4F2406002',12,42000, '31 Trans-Amadi, Port Harcourt',           'paystack','PSK_20240605_005','paid','delivered','2024-06-05 13:00:00'),
('AABC4F2407001',13,34000, '17 Cantonments Road, Accra, Ghana',       'paystack','PSK_20240701_006','paid','delivered','2024-07-01 10:00:00'),
('AABC4F2407002',14,75000, '8 Opebi Road, Ikeja GRA, Lagos',          'paystack','PSK_20240705_007','paid','delivered','2024-07-05 09:00:00'),
('AABC4F2408001',8, 120000,'14 Admiralty Way, Lekki Phase 1, Lagos',  'paystack','PSK_20240801_008','paid','delivered','2024-08-01 14:00:00'),
('AABC4F2408002',15,22000, '44 Zik Avenue, Uwani, Enugu',             'paystack','PSK_20240810_009','paid','delivered','2024-08-10 11:00:00'),
('AABC4F2409001',9, 89500, '7 Awolowo Road, Ikoyi, Lagos',            'paystack','PSK_20240910_010','paid','shipped',  '2024-09-10 10:00:00'),
('AABC4F2409002',16,35000, '3 Shehu Laminu Way, Maiduguri',           'paystack','PSK_20240915_011','paid','delivered','2024-09-15 09:00:00'),
('AABC4F2410001',10,68000, '22 Gana Street, Maitama, Abuja',          'paystack','PSK_20241001_012','paid','delivered','2024-10-01 13:00:00'),
('AABC4F2410002',17,19500, '19 Okpanam Road, Asaba, Delta State',     'paystack','PSK_20241010_013','paid','delivered','2024-10-10 10:00:00'),
('AABC4F2411001',11,58500, '5 Bourdillon Road, Ikoyi, Lagos',         'paystack','PSK_20241101_014','paid','delivered','2024-11-01 11:00:00'),
('AABC4F2412001',12,95000, '31 Trans-Amadi, Port Harcourt',           'paystack','PSK_20241201_015','paid','delivered','2024-12-01 09:00:00'),
('AABC4F2501001',8, 42000, '14 Admiralty Way, Lekki Phase 1, Lagos',  'paystack','PSK_20250105_016','paid','delivered','2025-01-05 10:00:00'),
('AABC4F2502001',13,12500, '17 Cantonments Road, Accra, Ghana',       'paystack','PSK_20250201_017','paid','delivered','2025-02-01 09:00:00'),
('AABC4F2502002',14,89500, '8 Opebi Road, Ikeja GRA, Lagos',          'paystack','PSK_20250215_018','paid','shipped',  '2025-02-15 14:00:00'),
('AABC4F2503001',15,28500, '44 Zik Avenue, Uwani, Enugu',             'paystack','PSK_20250301_019','paid','delivered','2025-03-01 10:00:00'),
('AABC4F2504001',16,22000, '3 Shehu Laminu Way, Maiduguri',           'paystack','PSK_20250401_020','paid','delivered','2025-04-01 09:00:00'),
('AABC4F2504002',9, 145000,'7 Awolowo Road, Ikoyi, Lagos',            'paystack','PSK_20250420_021','paid','shipped',  '2025-04-20 10:00:00'),
('AABC4F2505001',10,24000, '22 Gana Street, Maitama, Abuja',          'paystack','PSK_20250501_022','paid','processing','2025-05-01 11:00:00'),
('AABC4F2505002',11,35000, '5 Bourdillon Road, Ikoyi, Lagos',         'paystack','PSK_20250510_023','paid','pending',  '2025-05-10 09:00:00'),
('AABC4F2505003',12,7500,  '31 Trans-Amadi, Port Harcourt',           'paystack','PSK_20250515_024','paid','pending',  '2025-05-15 14:00:00'),
('AABC4F2505004',13,120000,'17 Cantonments Road, Accra, Ghana',       'paystack','PSK_20250519_025','paid','pending',  '2025-05-19 09:00:00');

-- ─── Order Items ──────────────────────────────────────────────
INSERT INTO `order_items` (`order_id`,`product_id`,`shop_id`,`product_name`,`price`,`quantity`,`size`,`status`) VALUES
(1, 1,1,'Sunset Ankara Wrap Dress',            28500,1,'L',      'delivered'),
(1, 3,1,'Men''s Ankara Festival Shirt',         15500,1,'XL',     'delivered'),
(1,24,6,'Indigo Adire Wrap Skirt',              19500,1,'M/L',    'delivered'),
(1,28,6,'Adire Bucket Hat',                      7500,1,'L/XL',   'delivered'),
(2, 1,1,'Sunset Ankara Wrap Dress',             28500,1,'M',      'delivered'),
(3,16,4,'The Sovereign — 3-Piece Agbada Set',  185000,1,'44',     'delivered'),
(4,17,4,'Classic Navy Agbada Set',              145000,1,'42',     'delivered'),
(5, 2,1,'Geometric Patchwork Ankara Blazer',    42000,1,'S',      'delivered'),
(6, 7,2,'Royal Kente Cloth Stole',              34000,1,'One Size','delivered'),
(7,12,3,'Grand Embroidered Kaftan — Gold',      75000,1,'XL',     'delivered'),
(8,18,4,'Aso-Oke Bridal Set — 3 Colours',      120000,1,'One Size','delivered'),
(9,14,3,'Casual Linen Kaftan — Unisex',         22000,1,'M',      'delivered'),
(10,8,2,'Kente Print Suit Set — Men',            89500,1,'40',     'shipped'),
(11,15,3,'Senator Kaftan Set (Top + Trousers)',  35000,1,'L',      'delivered'),
(12,13,3,'Ladies Embroidered Kaftan — Ivory',    68000,1,'M',      'delivered'),
(13,24,6,'Indigo Adire Wrap Skirt',              19500,1,'XS/S',   'delivered'),
(14, 4,1,'Aso Ebi Lace & Ankara Gown',           58500,1,'L',      'delivered'),
(15,21,5,'Grand Boubou — Men''s Edition',        95000,1,'XL',     'delivered'),
(16,25,6,'Adire Eleko Shirt — Men',              21000,1,'L',      'delivered'),
(17, 1,1,'Sunset Ankara Wrap Dress',             28500,1,'S',      'delivered'),
(18, 8,2,'Kente Print Suit Set — Men',           89500,1,'42',     'shipped'),
(19,19,4,'Junior Agbada Set for Boys',           28500,1,'7-9yr',  'delivered'),
(20,14,3,'Casual Linen Kaftan — Unisex',         22000,1,'XL',     'delivered'),
(21,17,4,'Classic Navy Agbada Set',              145000,1,'44',    'shipped'),
(22, 6,1,'Ankara Print Backpack',                24000,1,'One Size','processing'),
(23,15,3,'Senator Kaftan Set (Top + Trousers)',  35000,1,'XL',     'pending'),
(24,28,6,'Adire Bucket Hat',                      7500,1,'S/M',    'pending'),
(25,18,4,'Aso-Oke Bridal Set — 3 Colours',      120000,1,'One Size','pending');

-- ─── Wishlists ────────────────────────────────────────────────
INSERT INTO `wishlists` (`user_id`,`product_id`,`added_at`) VALUES
(8, 2,'2025-03-10 10:00:00'),(8,14,'2025-03-15 11:00:00'),(8, 8,'2025-04-01 09:00:00'),
(9, 1,'2025-04-05 14:00:00'),(9,18,'2025-04-10 10:00:00'),(9, 4,'2025-04-12 11:00:00'),
(10,13,'2025-04-20 09:00:00'),(10,27,'2025-05-01 10:00:00'),
(11,17,'2025-05-05 11:00:00'),(11, 8,'2025-05-08 14:00:00'),
(12,22,'2025-05-10 09:00:00'),(12,24,'2025-05-12 10:00:00'),
(13, 7,'2025-05-01 11:00:00'),(13,11,'2025-05-05 09:00:00'),
(14, 5,'2025-05-08 14:00:00'),(14,10,'2025-05-10 10:00:00'),
(15,22,'2025-05-12 11:00:00'),(15, 1,'2025-05-14 09:00:00'),
(16,15,'2025-05-15 10:00:00'),(16,12,'2025-05-16 11:00:00'),
(17,26,'2025-05-17 09:00:00'),(17,28,'2025-05-18 10:00:00');

-- ─── Cart Items (live demo carts) ────────────────────────────
INSERT INTO `cart_items` (`user_id`,`product_id`,`quantity`,`size`,`added_at`) VALUES
(8, 20,2,'XL',        '2025-05-19 10:00:00'),
(8, 11,1,'One Size',  '2025-05-19 10:15:00'),
(9, 13,1,'M',         '2025-05-18 14:00:00'),
(10, 3,1,'L',         '2025-05-17 11:00:00'),
(13,10,1,'4-5yr',     '2025-05-19 09:00:00'),
(14,14,1,'XL',        '2025-05-18 10:00:00');

-- ─── Payouts ─────────────────────────────────────────────────
INSERT INTO `payouts` (`shop_id`,`amount`,`commission_deducted`,`net_amount`,`status`,`requested_at`,`processed_at`) VALUES
(1,800000,160000,640000,'paid','2024-06-01 09:00:00','2024-06-03 14:00:00'),
(1,400000, 80000,320000,'paid','2024-09-01 09:00:00','2024-09-03 11:00:00'),
(2,500000,100000,400000,'paid','2024-07-01 10:00:00','2024-07-04 09:00:00'),
(2,300000, 60000,240000,'paid','2024-10-01 10:00:00','2024-10-03 14:00:00'),
(3,400000, 72000,328000,'paid','2024-08-01 11:00:00','2024-08-05 10:00:00'),
(4,900000,180000,720000,'paid','2024-07-01 09:00:00','2024-07-03 15:00:00'),
(4,600000,120000,480000,'paid','2024-11-01 09:00:00','2024-11-05 11:00:00'),
(5,200000, 40000,160000,'paid','2024-09-01 14:00:00','2024-09-04 10:00:00'),
(6,100000, 15000, 85000,'paid','2024-10-01 08:00:00','2024-10-04 11:00:00'),
(1,450000, 90000,360000,'pending', '2025-05-15 09:00:00',NULL),
(4,300000, 60000,240000,'approved','2025-05-10 09:00:00','2025-05-12 14:00:00'),
(2,220000, 44000,176000,'pending', '2025-05-18 10:00:00',NULL);

-- ─── Banners (Unsplash images) ────────────────────────────────
INSERT INTO `banners` (`title`,`subtitle`,`image`,`link`,`position`,`active`,`sort_order`,`created_at`) VALUES
('New Season: Aso Ebi & Bridal Collections','Shop stunning gowns for the wedding season','https://images.unsplash.com/photo-1537832816519-689ad163238b?w=1200&h=400&fit=crop&q=80','/customer/shop.php?category=3','hero',1,1,'2025-01-10 09:00:00'),
('Authentic Kente — Straight from Ghana','Hand-woven by master craftsmen in the Ashanti region','https://images.unsplash.com/photo-1509631179647-0177331693ae?w=1200&h=400&fit=crop&q=80','/customer/shop.php?category=5','hero',1,2,'2025-02-01 10:00:00'),
('Royal Agbada — Wedding Season Special','Hand-embroidered sets for the groom and groomsmen','https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1200&h=400&fit=crop&q=80','/customer/shop.php?category=2','mid',1,1,'2025-03-15 09:00:00'),
('Adire: Wearable African Art','Hand-dyed by women artisans in Abeokuta — every piece unique','https://images.unsplash.com/photo-1583744946564-b52ac1c389c8?w=1200&h=400&fit=crop&q=80','/customer/shop.php?category=8','mid',1,2,'2025-04-01 10:00:00'),
('Join African Attire — It''s Free','Thousands of designers already growing their business here','https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&h=400&fit=crop&q=80','/merchant/register.php','sidebar',1,1,'2025-01-01 09:00:00');

-- ─── Notifications ────────────────────────────────────────────
INSERT INTO `notifications` (`user_id`,`type`,`title`,`message`,`link`,`read_at`,`created_at`) VALUES
(8,'welcome','Welcome to African Attire! 🎉','Your account is ready. Explore authentic African fashion from top designers.','/customer/shop.php','2024-03-05 11:00:00','2024-03-05 10:00:00'),
(9,'welcome','Welcome to African Attire! 🎉','Your account is ready. Start shopping!','/customer/shop.php','2024-03-08 13:00:00','2024-03-08 12:00:00'),
(8,'order_placed','Order placed successfully 🎉','Order AABC4F2405001 confirmed. Total: ₦85,500.','/customer/orders.php','2024-05-01 11:00:00','2024-05-01 10:30:00'),
(9,'order_placed','Order placed successfully 🎉','Order AABC4F2405002 confirmed. Total: ₦28,500.','/customer/orders.php','2024-05-03 15:00:00','2024-05-03 14:00:00'),
(8,'order_shipped','Your order is on its way! 🚚','Order AABC4F2405001 has been shipped. Delivery in 3–5 business days.','/customer/orders.php','2024-05-03 10:00:00','2024-05-03 09:00:00'),
(8,'order_delivered','Order delivered! 📦','Order AABC4F2405001 has been delivered. Thank you for shopping!','/customer/orders.php','2024-05-07 11:00:00','2024-05-07 10:00:00'),
(2,'shop_approved','Your shop is live! 🎉','Ankara House is now live on African Attire. Start adding products!','/merchant/products.php','2024-01-11 10:00:00','2024-01-11 09:00:00'),
(3,'shop_approved','Your shop is live! 🎉','Kwame Designs is now live on African Attire.','/merchant/products.php','2024-01-19 11:00:00','2024-01-19 10:00:00'),
(4,'shop_approved','Your shop is live! 🎉','Sahel Fashion House is now live on African Attire.','/merchant/products.php','2024-02-02 10:00:00','2024-02-02 09:00:00'),
(5,'shop_approved','Your shop is live! 🎉','Royal Agbada Collections is now live.','/merchant/products.php','2024-02-09 10:00:00','2024-02-09 09:00:00'),
(6,'shop_approved','Your shop is live! 🎉','Diaspora Style Co. is now live on African Attire.','/merchant/products.php','2024-02-19 15:00:00','2024-02-19 14:00:00'),
(7,'shop_approved','Your shop is live! 🎉','Adire Collective is now live on African Attire.','/merchant/products.php','2024-03-01 09:00:00','2024-03-01 08:00:00'),
(2,'payout_approved','Payout processed 💰','Your payout of ₦640,000 has been approved and transferred.','/merchant/payouts.php','2024-06-04 10:00:00','2024-06-04 09:00:00'),
(2,'new_order','New order received 🛍','A new order has been placed. Check your orders dashboard.','/merchant/orders.php',NULL,'2025-05-19 09:15:00'),
(5,'new_order','New order received 🛍','A new order for Classic Navy Agbada Set has been placed.','/merchant/orders.php',NULL,'2025-05-19 09:30:00'),
(8,'admin_broadcast','🎊 Eid Mubarak Sale — Up to 30% Off!','Special discounts on Kaftans, Boubous, and more.','/customer/shop.php?category=4','2024-04-10 12:00:00','2024-04-10 09:00:00'),
(8,'new_arrival','✨ New arrivals from Adire Collective','Adire Collective just dropped their new Home Collection.','/customer/merchant.php?id=6',NULL,'2025-05-18 10:00:00'),
(11,'order_placed','Order placed successfully 🎉','Order AABC4F2505002 confirmed. Total: ₦35,000.','/customer/orders.php',NULL,'2025-05-10 09:00:00'),
(13,'order_placed','Order placed successfully 🎉','Your bridal order is confirmed. Total: ₦120,000.','/customer/orders.php',NULL,'2025-05-19 09:00:00');

-- ─── One Dispute ─────────────────────────────────────────────
INSERT INTO `disputes` (`order_id`,`user_id`,`reason`,`status`,`created_at`) VALUES
(10,9,'I ordered a Kente Print Suit in size 40 but received size 42. The suit does not fit. I need the correct size or a full refund. I have photos showing the wrong size label.','under_review','2024-09-18 10:00:00');

-- ============================================================
-- SUMMARY
-- Users:          1 admin + 6 merchants + 10 customers = 17
-- Shops:          6 (all approved, with Unsplash logo + banner)
-- Products:       28 (all approved, 8 featured, all categories)
-- Product images: 56 Unsplash photo URLs
-- Orders:         25 (mix of delivered / shipped / processing / pending)
-- Order items:    25 line items
-- Wishlists:      22 saved items
-- Cart items:     6 active carts for demo
-- Payouts:        12 (9 paid, 1 approved, 2 pending)
-- Banners:        5
-- Notifications:  19
-- Disputes:       1
--
-- ALL user passwords: Password@1
-- Admin login:     admin@africanattire.com / Admin@1234
-- ============================================================
