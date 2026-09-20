<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle  = 'Terms & Conditions';
$activePage = '';
include __DIR__ . '/includes/header_customer.php';
$updated = 'January 1, 2025';
?>
<div class="wrap-full" style="padding-top:20px;padding-bottom:40px;max-width:820px;margin:0 auto">

  <!-- Header -->
  <div style="text-align:center;margin-bottom:32px;padding:28px;background:linear-gradient(135deg,var(--navy),var(--ju-dk));border-radius:var(--r-lg)">
    <div style="font-size:2.4rem;margin-bottom:8px">📜</div>
    <h1 style="font-family:var(--ff-head);font-size:1.7rem;color:#fff;margin-bottom:6px">Terms &amp; Conditions</h1>
    <p style="color:rgba(255,255,255,.7);font-size:.86rem">
      Last updated: <?= $updated ?> &nbsp;·&nbsp; www.shopafricanattire.com
    </p>
  </div>

  <div style="background:#fff;border-radius:var(--r-lg);padding:28px 32px;box-shadow:var(--sh-xs);line-height:1.75;color:var(--text)">

    <p style="margin-bottom:20px;font-size:.9rem;color:var(--text-soft)">
      Please read these Terms &amp; Conditions carefully before using the African Attire marketplace
      at <strong>www.shopafricanattire.com</strong>. By accessing or purchasing from our platform,
      you agree to be bound by these terms.
    </p>

    <?php
    $sections = [
      ['1. Acceptance of Terms', 'By creating an account, browsing the platform, or placing an order on www.shopafricanattire.com (the "Platform"), you confirm that you have read, understood, and agreed to these Terms & Conditions and our Privacy Policy. If you do not agree, please do not use the Platform.'],
      ['2. About the Platform', 'African Attire is a B2B2C marketplace connecting customers with independent African fashion designers and merchants ("Merchants"). African Attire acts as an intermediary platform and is not the seller of the products listed. Each Merchant is responsible for the accuracy of their product listings, fulfilment, and customer service for their own products.'],
      ['3. User Accounts', "You must provide accurate and complete information when creating an account. You are responsible for maintaining the confidentiality of your account credentials. You must notify us immediately at hello@shopafricanattire.com if you suspect unauthorised access to your account. Accounts may not be transferred to another person. We reserve the right to suspend or terminate accounts that violate these Terms."],
      ['4. Merchant Accounts', "Merchants must apply and be approved before listing products. Merchants agree to: (a) provide accurate product descriptions, pricing, and stock levels; (b) fulfil orders promptly; (c) communicate professionally with customers; (d) comply with all applicable Nigerian and international laws. African Attire applies a platform markup on each product as configured at the time of listing. Merchants are responsible for their own tax obligations."],
      ['5. Orders & Payments', "All prices on the Platform are displayed in Nigerian Naira (₦ NGN) by default. A USD display option is available for convenience — the actual charge is always processed in NGN at the exchange rate displayed at time of checkout. Payments are processed securely by Paystack. African Attire does not store card details. Orders are confirmed only after successful payment verification via our payment gateway. African Attire reserves the right to cancel orders in cases of suspected fraud, pricing errors, or product unavailability."],
      ['6. Multicurrency Display', "Product prices are stored and settled in Nigerian Naira (₦ NGN). The USD display is a convenience feature based on an exchange rate set by the platform administrator. The actual USD amount charged depends on Paystack's live conversion rate at the time of transaction. African Attire is not liable for exchange rate fluctuations."],
      ['7. Shipping & Delivery', "Delivery is handled by individual Merchants. Estimated delivery times are provided by Merchants and are not guaranteed by African Attire. African Attire is not liable for delays caused by third-party logistics providers. Customers should contact the relevant Merchant directly for shipping queries via the platform's order system."],
      ['8. Returns & Refunds', "Our return policy allows customers to request a return within 7 days of confirmed delivery for items that are: (a) significantly different from the product description; (b) damaged or defective on arrival; (c) incorrect items sent. Refund decisions are made by African Attire after reviewing evidence provided by both parties. Refunds are processed via the original payment method within 5-10 business days. Custom-made or personalised items are non-refundable unless defective."],
      ['9. Intellectual Property', "All content on the Platform — including logos, design, text, graphics, and software — is owned by or licensed to African Attire and is protected by applicable intellectual property laws. Merchants retain ownership of their product images and descriptions but grant African Attire a non-exclusive licence to display this content on the Platform. You may not copy, reproduce, or distribute Platform content without written permission."],
      ['10. Prohibited Conduct', "You agree not to: (a) post false, misleading, or fraudulent product listings; (b) interfere with the Platform's security or functionality; (c) use the Platform for unlawful purposes; (d) harass other users or Merchants; (e) circumvent the platform's payment system by transacting directly with Merchants to avoid the platform markup; (f) create multiple accounts to abuse promotions."],
      ['11. Limitation of Liability', "To the fullest extent permitted by law, African Attire shall not be liable for: indirect, incidental, special, or consequential damages arising from your use of the Platform; losses resulting from Merchant actions or product quality; losses due to payment processing errors outside our control. Our total liability shall not exceed the amount paid for the specific transaction giving rise to the claim."],
      ['12. Changes to Terms', "We reserve the right to update these Terms at any time. Material changes will be notified via email or a prominent notice on the Platform. Continued use of the Platform after changes constitutes acceptance of the updated Terms."],
      ['13. Governing Law', "These Terms are governed by the laws of the Federal Republic of Nigeria. Any disputes shall be resolved by the courts of Lagos State, Nigeria, unless otherwise agreed."],
      ['14. Contact', "For questions about these Terms, contact us at:\n📧 hello@shopafricanattire.com\n🌐 www.shopafricanattire.com\n📞 Available through the platform contact form"],
    ];
    foreach ($sections as $section): ?>
    <div style="margin-bottom:24px">
      <h2 style="font-family:var(--ff-head);font-size:1rem;font-weight:700;color:var(--navy);margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid var(--ju-pale)">
        <?= e($section[0]) ?>
      </h2>
      <p style="font-size:.88rem;color:var(--text);white-space:pre-line"><?= e($section[1]) ?></p>
    </div>
    <?php endforeach; ?>

  </div>

  <div style="text-align:center;margin-top:20px">
    <a href="<?= BASE_URL ?>/privacy.php" style="color:var(--ju);font-weight:600;font-size:.86rem">View Privacy Policy →</a>
    &nbsp;·&nbsp;
    <a href="<?= BASE_URL ?>/" style="color:var(--text-muted);font-size:.86rem">← Back to Store</a>
  </div>

</div>
<?php include __DIR__ . '/includes/footer_customer.php'; ?>
