<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle  = 'Merchant FAQ';
$activePage = '';
include __DIR__ . '/../includes/header_customer.php';
$faqs = [
  'Getting Started' => [
    ['How do I become a merchant on African Attire?',
     'Click "Sell on African Attire" from any page. Fill in your business details, bank account for payouts, and submit. Our team reviews within 24–48 hours and notifies you by email when approved.'],
    ['Is there a fee to register?',
     'Registration is completely free. We only charge a platform commission on each completed sale — no monthly fees, listing fees, or setup costs.'],
    ['What products can I sell?',
     'Authentic African fashion: Ankara, Kente, Agbada, Kaftan, Adire, Aso-Ebi, Boubou, and all traditional and contemporary African attire. Accessories, headwear, and African-inspired jewellery are also welcome.'],
    ['How long does approval take?',
     'We aim to review all applications within 24–48 business hours. You will receive an email notification as soon as your application is approved.'],
  ],
  'Products & Listings' => [
    ['How do I add products?',
     'Go to Merchant Hub → Products → Add Product. Fill in the name, category, price (in NGN or USD — we auto-convert), stock, sizes, and description. Upload up to 5 photos. Products are reviewed before going live, usually within 12–24 hours.'],
    ['Can I enter prices in USD?',
     'Yes. When adding or editing a product, select the $ USD option next to the price field. The platform automatically converts to NGN for storage and shows both currencies to customers based on their preference.'],
    ['How many photos can I upload?',
     'Up to 5 photos per product. The first photo becomes the main listing image. Use clear, well-lit photos with a neutral background — minimum 800×900px recommended.'],
    ['Why is my product "Pending Review"?',
     'All new and edited products are reviewed before going live to ensure quality. Reviews complete within 12–24 hours. You will be notified when approved or if changes are needed.'],
  ],
  'Orders & Fulfilment' => [
    ['How do I know when I receive an order?',
     'You receive an instant email to your registered business email. You can also check Merchant Hub → Orders at any time.'],
    ['What should I do when I receive an order?',
     '(1) Review the order in Merchant Hub → Orders. (2) Package the items securely. (3) Ship to the customer\'s delivery region. (4) Update the order status to "Shipped" — this automatically emails the customer their update.'],
    ['Do I see the customer\'s full address?',
     'For privacy, merchants see the delivery city and region only — not the full street address, email, or phone number. Update order statuses (Processing → Shipped → Delivered) to keep customers informed through the platform.'],
    ['What if I cannot fulfil an order?',
     'Contact our support team immediately at hello@shopafricanattire.com. Do not cancel without notifying us first. Keep your stock quantities accurate to avoid overselling.'],
  ],
  'Payments & Payouts' => [
    ['How and when do I get paid?',
     'Your earnings accumulate in your Merchant Hub wallet. Go to Merchant Hub → Payouts to request a payout at any time. Payments reach your Nigerian bank account within 2–5 business days.'],
    ['How is commission calculated?',
     'African Attire deducts the platform commission from the sale price. For example, at 20% commission, selling at ₦10,000 earns you ₦8,000. The commission rate at the time of each sale applies.'],
    ['What happens if a customer requests a refund?',
     'Our team reviews refund requests and considers evidence from both parties. If approved, the amount (minus processing fees) is deducted from your merchant balance. Accurate descriptions are the best way to prevent disputes.'],
  ],
];
?>
<div class="wrap-full" style="padding-top:20px;padding-bottom:48px;max-width:860px;margin:0 auto">
  <div style="text-align:center;margin-bottom:32px;padding:32px;background:linear-gradient(135deg,var(--navy),var(--ju-dk));border-radius:var(--r-lg)">
    <div style="font-size:2.5rem;margin-bottom:10px">🏪</div>
    <h1 style="font-family:var(--ff-head);font-size:1.8rem;color:#fff;margin-bottom:8px">Merchant FAQ</h1>
    <p style="color:rgba(255,255,255,.7);font-size:.9rem;max-width:500px;margin:0 auto">
      Everything you need to know about selling on African Attire
    </p>
    <div style="margin-top:18px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <a href="<?= BASE_URL ?>/merchant/join.php" class="btn btn-ju">Start Selling →</a>
      <a href="<?= BASE_URL ?>/pages/merchant-how-it-works.php" style="padding:9px 18px;background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:var(--r-sm);text-decoration:none;font-weight:600;font-size:.86rem">How It Works →</a>
    </div>
  </div>
  <?php foreach ($faqs as $section => $items): ?>
  <div style="margin-bottom:24px">
    <h2 style="font-family:var(--ff-head);font-size:.95rem;font-weight:700;color:var(--navy);margin-bottom:10px;padding:8px 14px;background:var(--ju-pale);border-left:4px solid var(--ju);border-radius:0 var(--r-sm) var(--r-sm) 0"><?= e($section) ?></h2>
    <div style="display:flex;flex-direction:column;gap:6px">
      <?php foreach ($items as [$q,$a]): ?>
      <div style="background:#fff;border:1px solid var(--border-lt);border-radius:var(--r-md);overflow:hidden">
        <button onclick="toggleFaq(this)" style="width:100%;text-align:left;padding:13px 16px;background:none;border:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:12px;font-family:var(--ff);font-size:.88rem;font-weight:600;color:var(--black)">
          <span><?= e($q) ?></span>
          <span style="font-size:1rem;flex-shrink:0;color:var(--ju)">＋</span>
        </button>
        <div style="display:none;padding:0 16px 13px;font-size:.85rem;color:var(--text-soft);line-height:1.72;border-top:1px solid var(--border-lt)"><?= nl2br(e($a)) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <div style="text-align:center;background:var(--bg);border-radius:var(--r-lg);padding:22px;margin-top:8px">
    <p style="font-size:.88rem;color:var(--text-muted);margin-bottom:10px">Still have questions? We're here to help.</p>
    <a href="mailto:<?= getSetting('site_email','hello@shopafricanattire.com') ?>" class="btn btn-ju btn-sm">Email Merchant Support</a>
  </div>
</div>
<script>
function toggleFaq(btn){var a=btn.nextElementSibling,s=btn.querySelector('span:last-child'),o=a.style.display!=='none';a.style.display=o?'none':'block';s.textContent=o?'＋':'－';s.style.color=o?'var(--ju)':'var(--orange)';}
</script>
<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
