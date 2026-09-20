<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Merchant Terms of Service'; $activeNav = '';
// Public page — no auth required (linked from signup)
$activePage = '';
include __DIR__ . '/../includes/header_customer.php';
$commRate  = getSetting('platform_commission', '20');
$siteEmail = getSetting('site_email', 'hello@shopafricanattire.com');
$updated   = 'January 1, 2025';
?>

<div style="max-width:820px;margin:0 auto;padding-bottom:40px">

  <div style="text-align:center;margin-bottom:28px;padding:28px 24px;
              background:linear-gradient(135deg,var(--navy),var(--ju-dk));
              border-radius:var(--r-lg)">
    <div style="font-size:2.2rem;margin-bottom:8px">📜</div>
    <h1 style="font-family:var(--ff-head);font-size:1.6rem;color:#fff;margin-bottom:6px">
      Merchant Terms of Service
    </h1>
    <p style="color:rgba(255,255,255,.7);font-size:.84rem">
      Last updated: <?= $updated ?> · www.shopafricanattire.com
    </p>
  </div>

  <div style="background:#fff;border-radius:var(--r-lg);padding:28px 32px;box-shadow:var(--sh-xs);line-height:1.75;color:var(--text)">

    <p style="margin-bottom:20px;font-size:.9rem;color:var(--text-soft)">
      By registering as a merchant and operating a shop on <strong>www.shopafricanattire.com</strong>
      (the "Platform"), you agree to these Merchant Terms of Service. Please read them carefully.
    </p>

    <?php $sections = [
      ['1. Merchant Eligibility &amp; Registration',
       "To sell on African Attire, you must:\n• Be at least 18 years old or represent a legally registered business\n• Provide accurate and truthful information during registration\n• Have a valid Nigerian bank account for payouts\n• Operate within the laws of the Federal Republic of Nigeria\n\nAfrican Attire reserves the right to reject any application without giving reasons."],

      ['2. Approved Products',
       "Merchants may list:\n• Authentic African fashion garments (Ankara, Kente, Agbada, Kaftan, Adire, Boubou, etc.)\n• Traditional and contemporary African clothing and accessories\n• African-inspired jewellery and headwear\n\nYou may NOT list:\n• Counterfeit, copy or unlicensed branded goods\n• Products that violate intellectual property rights\n• Items prohibited by Nigerian or international law\n• Products that are inaccurately described or misrepresented\n\nAll products are reviewed before going live. African Attire may remove any listing at any time."],

      ['3. Platform Commission',
       "African Attire charges a platform commission of <strong>{$commRate}%</strong> on the total sale price of each completed transaction. This is the only fee charged.\n\n• No monthly fees\n• No listing fees\n• No setup costs\n• Commission is deducted automatically from your earnings\n\nThe commission rate may be updated with 30 days' written notice to registered merchants."],

      ['4. Product Listings &amp; Accuracy',
       "You are solely responsible for:\n• The accuracy of all product descriptions, images, sizes, and pricing\n• Keeping stock quantities up to date to avoid overselling\n• Ensuring all images are original or that you hold rights to use them\n• Pricing products correctly (prices stored in NGN internally)\n\nMisleading listings may result in account suspension."],

      ['5. Order Fulfilment',
       "When an order is placed for your products, you agree to:\n• Acknowledge and begin processing orders promptly (within 1 business day)\n• Package items securely and dispatch within the stated timeframe\n• Update order status accurately (Processing → Shipped → Delivered)\n• Notify the platform immediately if you cannot fulfil an order\n\nRepeated failure to fulfil orders will result in account suspension."],

      ['6. Pricing &amp; Multi-Currency',
       "All prices are stored in Nigerian Naira (₦ NGN) internally. Customers may view prices in other display currencies (USD, EUR, GBP) based on the exchange rates set by the platform.\n\nMerchants may enter product prices in any enabled currency — the platform converts to NGN at the current configured rate. The NGN amount is what is charged and what your earnings are based on."],

      ['7. Payouts',
       "Earnings accumulate in your Merchant Hub wallet after successful deliveries. Payouts are made to your registered Nigerian bank account upon request.\n\n• Payout processing time: 2–5 business days\n• Minimum payout may apply (check your dashboard)\n• African Attire reserves the right to withhold payouts pending dispute resolution\n• You are responsible for any applicable taxes on your earnings"],

      ['8. Customer Returns &amp; Refunds',
       "If a customer raises a valid return or refund request, African Attire will investigate. If the return is approved:\n• The refunded amount is deducted from your merchant balance\n• Repeated refund issues may result in account review\n\nTo minimise refunds, always provide accurate descriptions and quality products."],

      ['9. Account Suspension &amp; Termination',
       "African Attire may suspend or terminate your account if you:\n• Violate these Terms\n• Provide false information\n• Sell prohibited items\n• Receive an abnormally high rate of complaints or refunds\n• Attempt to transact with customers outside the platform to avoid commission\n• Engage in any fraudulent activity\n\nUpon termination, any outstanding earnings (minus pending liabilities) will be paid within 30 days."],

      ['10. Intellectual Property',
       "You grant African Attire a non-exclusive, royalty-free licence to display your product images, descriptions, and shop profile on the Platform and in promotional materials. You retain ownership of your content but represent that you have the right to grant this licence."],

      ['11. Privacy',
       "For privacy information specific to merchant accounts, please see our <a href='".BASE_URL."/merchant/privacy.php' style='color:var(--ju);font-weight:600'>Merchant Privacy Policy</a>. Customer personal data shared with you for order fulfilment must be kept confidential and used only for that purpose."],

      ['12. Changes to Terms',
       "African Attire reserves the right to update these Terms. Material changes will be communicated by email with 14 days' notice. Continued use of the Platform after this period constitutes acceptance."],

      ['13. Governing Law',
       "These Terms are governed by the laws of the Federal Republic of Nigeria. Disputes shall be resolved in the courts of Lagos State unless otherwise agreed in writing."],

      ['14. Contact',
       "Merchant support: <a href='mailto:{$siteEmail}' style='color:var(--ju)'>{$siteEmail}</a>\nPlatform: www.shopafricanattire.com"],
    ]; ?>

    <?php foreach ($sections as [$title, $body]): ?>
    <div style="margin-bottom:22px">
      <h2 style="font-family:var(--ff-head);font-size:1rem;font-weight:700;color:var(--navy);
                 margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid var(--ju-pale)">
        <?= $title ?>
      </h2>
      <p style="font-size:.88rem;color:var(--text);white-space:pre-line;line-height:1.75">
        <?= $body ?>
      </p>
    </div>
    <?php endforeach; ?>

  </div>

  <div style="text-align:center;margin-top:16px">
    <a href="<?= BASE_URL ?>/merchant/privacy.php"
       style="color:var(--ju);font-weight:600;font-size:.86rem">
      Merchant Privacy Policy →
    </a>
    &nbsp;·&nbsp;
    <a href="<?= BASE_URL ?>/merchant/signup.php"
       style="color:var(--text-muted);font-size:.86rem">← Back to Sign Up</a>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
