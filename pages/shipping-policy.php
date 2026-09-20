<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Shipping Policy'; $activePage = '';
include __DIR__ . '/../includes/header_customer.php';
?>
<div class="wrap-full" style="padding-top:20px;padding-bottom:48px;max-width:820px;margin:0 auto">
  <div style="text-align:center;margin-bottom:32px;padding:28px;background:linear-gradient(135deg,var(--navy),var(--ju-dk));border-radius:var(--r-lg)">
    <div style="font-size:2.4rem;margin-bottom:8px">🚚</div>
    <h1 style="font-family:var(--ff-head);font-size:1.7rem;color:#fff;margin-bottom:6px">Shipping Policy</h1>
    <p style="color:rgba(255,255,255,.7);font-size:.86rem">Last updated: January 1, 2025 · www.shopafricanattire.com</p>
  </div>
  <div style="background:#fff;border-radius:var(--r-lg);padding:28px 32px;box-shadow:var(--sh-xs)">
  <?php $sections = [
    ['🏪 How Shipping Works on African Attire',
     "African Attire is a marketplace connecting customers with independent African fashion merchants. Each merchant is responsible for packaging and shipping their own products. Shipping timelines, costs, and carriers vary by merchant.\n\nWhen you place an order, the relevant merchant receives an instant notification and begins preparing your items. You will receive email updates as your order progresses from Processing → Shipped → Delivered."],
    ['⏱ Processing Time',
     "Most merchants process and dispatch orders within 1–3 business days of confirmed payment. Some merchants may take up to 5 business days for custom or made-to-order items.\n\nOrders placed on weekends or public holidays are typically processed on the next business day.\n\nYou will receive an email notification when your order status changes to 'Shipped'."],
    ['📦 Delivery Timeframes',
     "Delivery times depend on the merchant's location and your delivery address:\n\n• Same city / state:  1–3 business days\n• Cross-state (Nigeria):  2–5 business days\n• Remote areas:  5–10 business days\n• International shipping (select merchants):  7–21 business days\n\nThese are estimates only and not guaranteed. Delays can occur due to courier issues, public holidays, or adverse weather conditions."],
    ['💰 Shipping Costs',
     "Shipping fees are set by individual merchants and are displayed at checkout before you confirm payment. Costs vary based on:\n\n• The merchant's location\n• Your delivery address\n• Order weight and size\n• Selected shipping speed\n\nSome merchants offer free delivery on qualifying orders — look for the 'Free Delivery' badge on product listings. Free delivery thresholds are set individually by each merchant."],
    ['🌍 Delivery Coverage',
     "Our merchants primarily ship within Nigeria. Coverage includes all 36 states and the FCT.\n\nSome merchants offer international shipping to selected countries, particularly in West Africa and to the African diaspora. International shipping availability is listed on individual merchant shop pages and product listings.\n\nFor international orders, the customer is responsible for any customs duties, import taxes, or local fees charged by their country's authorities."],
    ['📍 Delivery Address',
     "Please ensure your delivery address is complete and accurate at checkout. African Attire and our merchants are not responsible for non-delivery due to an incorrect address provided by the customer.\n\nIf you need to change your delivery address after placing an order, contact us immediately at hello@shopafricanattire.com. Address changes may not be possible once an order has been shipped."],
    ['📲 Order Tracking',
     "You can track your order status at any time on our Track Order page at " . BASE_URL . "/customer/track-order.php\n\nEnter your order number and the email address used at checkout. Registered customers can also view order status in My Account → My Orders.\n\nYou will receive email notifications at each status change: Processing, Shipped, and Delivered."],
    ['❓ Shipping Issues',
     "If your order has not arrived within the estimated delivery timeframe, please:\n\n1. Check the Track Order page for the latest status\n2. Allow 2 extra business days past the estimate before contacting us\n3. Contact our support team at hello@shopafricanattire.com with your order number\n\nFor damaged or lost shipments, please contact us within 48 hours of the expected delivery date with photos and your order number."],
  ]; foreach ($sections as [$title,$body]): ?>
  <div style="margin-bottom:22px">
    <h2 style="font-family:var(--ff-head);font-size:1rem;font-weight:700;color:var(--navy);margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid var(--ju-pale)"><?= e($title) ?></h2>
    <p style="font-size:.88rem;color:var(--text);white-space:pre-line;line-height:1.75"><?= e($body) ?></p>
  </div>
  <?php endforeach; ?>
  </div>
  <div style="text-align:center;margin-top:18px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>/pages/returns.php" style="color:var(--ju);font-weight:600;font-size:.86rem">Returns &amp; Refunds Policy →</a>
    <span style="color:var(--border)">·</span>
    <a href="<?= BASE_URL ?>/pages/customer-faq.php" style="color:var(--text-muted);font-size:.86rem">Customer FAQ</a>
    <span style="color:var(--border)">·</span>
    <a href="<?= BASE_URL ?>/" style="color:var(--text-muted);font-size:.86rem">← Back to Store</a>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
