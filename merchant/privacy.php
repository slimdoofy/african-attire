<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Merchant Privacy Policy'; $activeNav = '';
// Public page — no auth required (linked from signup)
$activePage = '';
include __DIR__ . '/../includes/header_customer.php';
$siteEmail = getSetting('site_email', 'hello@shopafricanattire.com');
$updated   = 'January 1, 2025';
?>

<div style="max-width:820px;margin:0 auto;padding-bottom:40px">

  <div style="text-align:center;margin-bottom:28px;padding:28px 24px;
              background:linear-gradient(135deg,var(--navy),var(--ju-dk));border-radius:var(--r-lg)">
    <div style="font-size:2.2rem;margin-bottom:8px">🔒</div>
    <h1 style="font-family:var(--ff-head);font-size:1.6rem;color:#fff;margin-bottom:6px">
      Merchant Privacy Policy
    </h1>
    <p style="color:rgba(255,255,255,.7);font-size:.84rem">
      Last updated: <?= $updated ?> · www.shopafricanattire.com
    </p>
  </div>

  <div style="background:#fff;border-radius:var(--r-lg);padding:28px 32px;box-shadow:var(--sh-xs);line-height:1.75;color:var(--text)">

    <p style="margin-bottom:20px;font-size:.9rem;color:var(--text-soft)">
      This Privacy Policy explains how African Attire collects, uses, stores, and protects the personal
      and business information of merchants registered on <strong>www.shopafricanattire.com</strong>.
    </p>

    <?php $sections = [
      ['1. Information We Collect from Merchants',
       "When you register as a merchant, we collect:\n\n• Personal details: name, email address, phone number, country, city\n• Business information: shop name, business email, business phone, business address, country\n• Financial information: bank name, account number, account name (for payouts)\n• Shop content: shop logo, banner, product images, descriptions, and pricing\n• Optional: Agent's ID (referral information)\n\nWe also collect usage data including login activity, order actions, and dashboard interactions for security and analytics."],

      ['2. How We Use Merchant Information',
       "We use your information to:\n• Operate and manage your merchant account and shop\n• Process orders and coordinate with customers\n• Send order notifications and payout confirmations\n• Communicate platform updates, policy changes, and merchant support responses\n• Calculate and remit your earnings (minus platform commission)\n• Verify your identity and prevent fraud\n• Improve the Platform's functionality\n\nWe do not sell or rent your personal information to third parties."],

      ['3. Customer Data You Access',
       "For each order placed from your shop, you will see:\n• Customer name\n• Delivery city and region (NOT full street address)\n\nYou do NOT have access to:\n• Customer email address\n• Customer phone number\n• Full delivery address\n\nThis data is provided solely to help you fulfil orders. You must NOT use it for any other purpose, share it with third parties, or contact customers outside the African Attire platform."],

      ['4. Financial Data',
       "Your bank account details are collected solely for the purpose of making payouts. We do not:\n• Share your bank details with customers\n• Use your account for any charges (we only transfer to you)\n• Store card or payment instrument details on our servers\n\nAll financial processing uses industry-standard encryption."],

      ['5. Data Storage &amp; Security',
       "Your data is stored on secure servers with:\n• HTTPS/TLS encryption in transit\n• Access controls limiting who can view your data\n• Regular security audits\n• Bcrypt password hashing (your password is never stored in plain text)\n\nYour product images and shop assets are stored in our secure file storage system."],

      ['6. Data Sharing',
       "We share your data only with:\n• Payment processors (Paystack) for payout processing — subject to Paystack's privacy policy\n• Email service providers (SendGrid) to deliver notifications\n• Platform administrators who manage the marketplace\n\nWe do not share your personal or business information with customers, logistics partners, or third parties for their own marketing purposes."],

      ['7. Data Retention',
       "We retain your merchant account data for as long as your account is active. If your account is terminated:\n• Publicly visible shop content is removed within 7 days\n• Transaction and financial records are retained for 7 years for legal compliance\n• You may request deletion of personal data (subject to legal retention requirements)\n\nProduct listings are archived (not deleted) to preserve order history integrity."],

      ['8. Your Rights',
       "As a merchant, you have the right to:\n• Access a copy of your personal and business data\n• Correct inaccurate information (via Merchant Hub → Settings)\n• Request deletion of your account (subject to outstanding liabilities)\n• Object to certain processing activities\n• Data portability (receive your data in a readable format)\n\nTo exercise any of these rights, email: <a href='mailto:privacy@shopafricanattire.com' style='color:var(--ju)'>privacy@shopafricanattire.com</a>"],

      ['9. Cookies',
       "The Merchant Hub uses session cookies to keep you logged in during your work session. We do not use advertising or tracking cookies in the merchant portal. Analytics cookies may collect anonymous usage data to improve the platform."],

      ['10. Changes to This Policy',
       "We may update this Privacy Policy from time to time. Changes will be communicated by email and by updating the date at the top of this page. Continued use of the Merchant Hub after changes constitutes acceptance."],

      ['11. Contact',
       "For privacy-related questions:\n📧 privacy@shopafricanattire.com\n🌐 www.shopafricanattire.com\n📮 African Attire Platform, Lagos, Nigeria"],
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
    <a href="<?= BASE_URL ?>/merchant/terms.php"
       style="color:var(--ju);font-weight:600;font-size:.86rem">
      Merchant Terms of Service →
    </a>
    &nbsp;·&nbsp;
    <a href="<?= BASE_URL ?>/merchant/signup.php"
       style="color:var(--text-muted);font-size:.86rem">← Back to Sign Up</a>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
