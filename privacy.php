<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle  = 'Privacy Policy';
$activePage = '';
include __DIR__ . '/includes/header_customer.php';
$updated = 'January 1, 2025';
?>
<div class="wrap-full" style="padding-top:20px;padding-bottom:40px;max-width:820px;margin:0 auto">

  <div style="text-align:center;margin-bottom:32px;padding:28px;background:linear-gradient(135deg,var(--navy),var(--ju-dk));border-radius:var(--r-lg)">
    <div style="font-size:2.4rem;margin-bottom:8px">🔒</div>
    <h1 style="font-family:var(--ff-head);font-size:1.7rem;color:#fff;margin-bottom:6px">Privacy Policy</h1>
    <p style="color:rgba(255,255,255,.7);font-size:.86rem">
      Last updated: <?= $updated ?> &nbsp;·&nbsp; www.shopafricanattire.com
    </p>
  </div>

  <div style="background:#fff;border-radius:var(--r-lg);padding:28px 32px;box-shadow:var(--sh-xs);line-height:1.75;color:var(--text)">

    <p style="margin-bottom:20px;font-size:.9rem;color:var(--text-soft)">
      African Attire ("we", "us", "our") is committed to protecting your personal information.
      This Privacy Policy explains how we collect, use, store, and protect your data when you use
      <strong>www.shopafricanattire.com</strong>.
    </p>

    <?php
    $sections = [
      ['1. Information We Collect', "We collect the following types of information:\n\n**Account Information:** Name, email address, phone number, and password (stored as an encrypted hash) when you register.\n\n**Order Information:** Delivery address, order history, payment references, and product selections.\n\n**Payment Information:** Payments are processed by Paystack. We do not store card numbers, CVVs, or bank account details. We only receive a transaction reference and status confirmation.\n\n**Device & Usage Data:** IP address, browser type, pages visited, and session duration for security and analytics.\n\n**Communications:** Messages you send via our support channels."],
      ['2. How We Use Your Information', "We use your information to:\n• Process and fulfil your orders\n• Send order confirmation, tracking, and status update emails\n• Communicate with you about your account\n• Improve the Platform's performance and user experience\n• Detect and prevent fraud and abuse\n• Comply with legal obligations\n• Send promotional emails (you can unsubscribe at any time)\n\nWe do not sell, rent, or share your personal information with third parties for their marketing purposes."],
      ['3. Information Shared with Merchants', "When you place an order, we share limited information with the relevant Merchant to enable fulfilment:\n• Your first name\n• Delivery city and region (not the full address)\n• Order contents and quantity\n\nFull contact details (email, phone, street address) are NOT shared with Merchants. All direct communication goes through the African Attire platform. Merchants are contractually bound to use order information solely for fulfilment."],
      ['4. Cookies & Tracking', "We use cookies and similar technologies to:\n• Keep you logged in during your session\n• Remember your currency preference (NGN/USD)\n• Maintain your shopping cart\n• Collect anonymous analytics (page views, time on site)\n\nYou can disable cookies in your browser settings, but some features may not work correctly. We do not use third-party advertising cookies."],
      ['5. Data Storage & Security', "Your data is stored on servers located in secure data centres. We implement industry-standard security measures including:\n• HTTPS/TLS encryption for all data in transit\n• Bcrypt password hashing (your password is never stored in plain text)\n• Regular security audits and access controls\n• Paystack PCI-DSS compliant payment processing\n\nWhile we take all reasonable steps to protect your data, no method of transmission over the internet is 100% secure."],
      ['6. Data Retention', "We retain your personal data for as long as your account is active or as needed to provide services. Order records are retained for 7 years for accounting and legal compliance. You may request deletion of your account and associated data at any time (see Section 8). Some data may be retained in anonymised form for analytics."],
      ['7. Your Rights', "Under applicable privacy laws, you have the right to:\n• **Access:** Request a copy of the personal data we hold about you\n• **Correction:** Ask us to correct inaccurate or incomplete data\n• **Deletion:** Request deletion of your personal data (subject to legal retention requirements)\n• **Portability:** Receive your data in a machine-readable format\n• **Objection:** Object to certain types of data processing\n• **Unsubscribe:** Opt out of marketing emails at any time\n\nTo exercise any of these rights, contact us at privacy@shopafricanattire.com."],
      ['8. Children\'s Privacy', "Our Platform is not directed at children under the age of 13. We do not knowingly collect personal information from children. If you believe a child has provided us with personal information, please contact us and we will promptly delete it."],
      ['9. Third-Party Services', "We use the following third-party services that have their own privacy policies:\n• **Paystack** (payment processing) — paystack.com/privacy\n• **SendGrid** (email delivery) — sendgrid.com/privacy\n\nWe are not responsible for the privacy practices of these third parties."],
      ['10. Changes to This Policy', "We may update this Privacy Policy from time to time. We will notify you of material changes by email or by posting a notice on the Platform. Your continued use of the Platform after changes constitutes acceptance of the updated Policy."],
      ['11. Contact Us', "For privacy-related queries or to exercise your data rights:\n\n📧 privacy@shopafricanattire.com\n🌐 www.shopafricanattire.com\n📮 African Attire Platform, Lagos, Nigeria\n\nWe aim to respond to all requests within 5 business days."],
    ];
    foreach ($sections as $section):
      // Convert **bold** markdown to HTML
      $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', e($section[1]));
    ?>
    <div style="margin-bottom:24px">
      <h2 style="font-family:var(--ff-head);font-size:1rem;font-weight:700;color:var(--navy);margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid var(--ju-pale)">
        <?= e($section[0]) ?>
      </h2>
      <p style="font-size:.88rem;color:var(--text);white-space:pre-line"><?= $text ?></p>
    </div>
    <?php endforeach; ?>

  </div>

  <div style="text-align:center;margin-top:20px">
    <a href="<?= BASE_URL ?>/terms.php" style="color:var(--ju);font-weight:600;font-size:.86rem">View Terms &amp; Conditions →</a>
    &nbsp;·&nbsp;
    <a href="<?= BASE_URL ?>/" style="color:var(--text-muted);font-size:.86rem">← Back to Store</a>
  </div>

</div>
<?php include __DIR__ . '/includes/footer_customer.php'; ?>
