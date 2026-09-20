<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle  = 'Customer FAQ';
$activePage = '';
include __DIR__ . '/../includes/header_customer.php';
$faqs = [
  'Shopping & Orders' => [
    ['How do I place an order?', 'Browse products, click "Add to Cart", then go to Checkout. Enter your delivery details and complete payment via Paystack (card, bank transfer, or USSD). You\'ll receive an order confirmation email immediately after payment.'],
    ['Do I need an account to shop?', 'No — you can checkout as a guest using just your name and email address. However, creating an account lets you track orders, save a wishlist, and view your order history.'],
    ['Can I order from multiple designers in one checkout?', 'Yes. You can add products from different merchants to your cart and check out in one payment. Each merchant will be notified separately and fulfils their own items.'],
    ['How do I track my order?', 'Visit the Track Order page and enter your order number and email address. You\'ll see real-time status updates. Registered customers can also track orders in My Account → My Orders.'],
    ['Can I cancel an order?', 'Orders can be cancelled while in "Pending" status — go to My Orders and click Cancel. Once an order moves to "Processing" or "Shipped", cancellations require contacting our support team.'],
  ],
  'Payment' => [
    ['What payment methods do you accept?', 'We accept all major cards (Visa, Mastercard, Verve), bank transfers, and USSD payments via Paystack — Nigeria\'s most trusted payment gateway.'],
    ['Is it safe to pay on African Attire?', 'Yes. All payments are processed by Paystack using 256-bit SSL encryption. African Attire never stores your card details. Look for the padlock icon in your browser address bar.'],
    ['Can I pay in USD?', 'The platform displays prices in both NGN and USD based on your currency preference. All payments are processed in Nigerian Naira (NGN) at the current rate. Your bank may apply its own conversion fee for international cards.'],
    ['I was charged but my order didn\'t go through. What happens?', 'If Paystack confirms your payment but an order wasn\'t created, contact us at hello@shopafricanattire.com with your payment reference number. We investigate all such cases within 24 hours and process refunds within 5 business days.'],
  ],
  'Delivery & Shipping' => [
    ['How long does delivery take?', 'Delivery times depend on the merchant\'s location and your delivery address. Most merchants dispatch within 1–3 business days. Delivery typically takes 2–7 business days across Nigeria.'],
    ['Do you deliver outside Nigeria?', 'Currently our merchants primarily ship within Nigeria. Some merchants may offer international shipping — check the product listing or contact the merchant\'s shop page for details.'],
    ['How much does delivery cost?', 'Delivery fees are set by individual merchants and shown at checkout. Some orders qualify for free delivery — look for the free delivery badge on product listings.'],
    ['What if my delivery is late?', 'Check your order status on the Track Order page. If your order is significantly delayed beyond the estimated time, contact our support team at hello@shopafricanattire.com with your order number.'],
  ],
  'Returns & Refunds' => [
    ['What is your return policy?', 'You can request a return within 7 days of delivery if the item is significantly different from the description, damaged, or the wrong item was sent. See our full Returns & Refunds policy for details.'],
    ['How do I request a return?', 'Contact us at hello@shopafricanattire.com within 7 days of delivery with your order number and photos of the item. Our team will review your request and respond within 2 business days.'],
    ['When will I receive my refund?', 'Once a refund is approved, it is processed back to your original payment method within 5–10 business days. You\'ll receive an email confirmation when the refund is initiated.'],
    ['Are custom or personalised items returnable?', 'Custom-made or personalised items are non-refundable unless they are defective or not as described. Please verify measurements and specifications carefully before ordering.'],
  ],
  'Account & Profile' => [
    ['How do I create an account?', 'Click "Join Free" in the header and fill in your name, email, and password. You\'ll receive a welcome email. Creating an account lets you track orders, save items to your wishlist, and checkout faster.'],
    ['I forgot my password. How do I reset it?', 'Click "Sign In", then "Forgot password?" and enter your email address. You\'ll receive a password reset link within a few minutes. Check your spam folder if you don\'t see it.'],
    ['Can I change my email address?', 'For security reasons, email address changes require contacting our support team at hello@shopafricanattire.com with proof of identity.'],
    ['How do I delete my account?', 'To request account deletion, email privacy@shopafricanattire.com. We will process your request within 5 business days, subject to our data retention obligations.'],
  ],
];
?>
<div class="wrap-full" style="padding-top:20px;padding-bottom:48px;max-width:860px;margin:0 auto">
  <div style="text-align:center;margin-bottom:32px;padding:32px;background:linear-gradient(135deg,var(--navy),var(--ju-dk));border-radius:var(--r-lg)">
    <div style="font-size:2.5rem;margin-bottom:10px">💬</div>
    <h1 style="font-family:var(--ff-head);font-size:1.8rem;color:#fff;margin-bottom:8px">Customer FAQ</h1>
    <p style="color:rgba(255,255,255,.7);font-size:.9rem;max-width:480px;margin:0 auto">
      Quick answers to the most common questions from our shoppers
    </p>
  </div>
  <?php foreach ($faqs as $section => $items): ?>
  <div style="margin-bottom:24px">
    <h2 style="font-family:var(--ff-head);font-size:.95rem;font-weight:700;color:var(--navy);margin-bottom:10px;padding:8px 14px;background:var(--navy-pale);border-left:4px solid var(--navy);border-radius:0 var(--r-sm) var(--r-sm) 0"><?= e($section) ?></h2>
    <div style="display:flex;flex-direction:column;gap:6px">
      <?php foreach ($items as [$q,$a]): ?>
      <div style="background:#fff;border:1px solid var(--border-lt);border-radius:var(--r-md);overflow:hidden">
        <button onclick="toggleFaq(this)" style="width:100%;text-align:left;padding:13px 16px;background:none;border:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:12px;font-family:var(--ff);font-size:.88rem;font-weight:600;color:var(--black)">
          <span><?= e($q) ?></span>
          <span style="font-size:1rem;flex-shrink:0;color:var(--navy)">＋</span>
        </button>
        <div style="display:none;padding:0 16px 13px;font-size:.85rem;color:var(--text-soft);line-height:1.72;border-top:1px solid var(--border-lt)"><?= nl2br(e($a)) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px">
    <a href="<?= BASE_URL ?>/pages/shipping-policy.php" style="display:flex;align-items:center;gap:10px;background:#fff;border:1px solid var(--border-lt);border-radius:var(--r-md);padding:14px 16px;text-decoration:none;color:var(--black)">
      <span style="font-size:1.5rem">🚚</span>
      <div><div style="font-weight:700;font-size:.88rem">Shipping Policy</div><div style="font-size:.76rem;color:var(--text-muted)">Delivery times &amp; costs</div></div>
    </a>
    <a href="<?= BASE_URL ?>/pages/returns.php" style="display:flex;align-items:center;gap:10px;background:#fff;border:1px solid var(--border-lt);border-radius:var(--r-md);padding:14px 16px;text-decoration:none;color:var(--black)">
      <span style="font-size:1.5rem">↩️</span>
      <div><div style="font-weight:700;font-size:.88rem">Returns &amp; Refunds</div><div style="font-size:.76rem;color:var(--text-muted)">7-day return policy</div></div>
    </a>
  </div>
</div>
<script>
function toggleFaq(btn){var a=btn.nextElementSibling,s=btn.querySelector('span:last-child'),o=a.style.display!=='none';a.style.display=o?'none':'block';s.textContent=o?'＋':'－';s.style.color=o?'var(--navy)':'var(--orange)';}
</script>
<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
