<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Returns & Refunds Policy'; $activePage = '';
include __DIR__ . '/../includes/header_customer.php';
?>
<div class="wrap-full" style="padding-top:20px;padding-bottom:48px;max-width:820px;margin:0 auto">
  <div style="text-align:center;margin-bottom:32px;padding:28px;background:linear-gradient(135deg,var(--navy),var(--ju-dk));border-radius:var(--r-lg)">
    <div style="font-size:2.4rem;margin-bottom:8px">↩️</div>
    <h1 style="font-family:var(--ff-head);font-size:1.7rem;color:#fff;margin-bottom:6px">Returns &amp; Refunds</h1>
    <p style="color:rgba(255,255,255,.7);font-size:.86rem">Last updated: January 1, 2025 · www.shopafricanattire.com</p>
  </div>

  <!-- Quick summary -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:20px">
    <?php foreach([['7 Days','Return window from delivery','var(--ju)'],['5–10 Days','Refund processing time','var(--blue)'],['Free','No return shipping fees on defective items','var(--green)']] as [$v,$k,$c]): ?>
    <div style="background:#fff;border-radius:var(--r-md);padding:16px;text-align:center;border:1px solid var(--border-lt);box-shadow:var(--sh-xs)">
      <div style="font-family:var(--ff-head);font-size:1.5rem;font-weight:800;color:<?= $c ?>;margin-bottom:4px"><?= $v ?></div>
      <div style="font-size:.76rem;color:var(--text-muted)"><?= $k ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="background:#fff;border-radius:var(--r-lg);padding:28px 32px;box-shadow:var(--sh-xs)">
  <?php $sections = [
    ['✅ What Can Be Returned',
     "You may request a return within 7 days of confirmed delivery for items that are:\n\n• Significantly different from the product description or photos\n• Damaged or defective on arrival\n• The wrong item sent (incorrect size, colour, or product)\n• Missing components included in the listing\n\nItems must be returned in their original condition, unworn, and with all original packaging and tags attached."],
    ['❌ What Cannot Be Returned',
     "The following items are not eligible for return:\n\n• Custom-made or personalised/monogrammed items (unless defective)\n• Items that have been worn, washed, or altered\n• Items returned more than 7 days after delivery confirmation\n• Items without original tags or packaging\n• Sale or clearance items marked as final sale\n• Perishable or hygienic accessories\n\nIf you are unsure whether your item qualifies, contact us before initiating a return."],
    ['📋 How to Request a Return',
     "To start a return:\n\n1. Email hello@shopafricanattire.com within 7 days of delivery\n2. Include your order number, the item(s) you wish to return, and a clear reason\n3. Attach photos showing the issue (required for damaged/defective/incorrect items)\n4. Our team will review and respond within 2 business days\n5. If approved, you will receive return instructions by email\n\nDo not ship items back without first receiving a return approval from our team — unapproved returns cannot be processed."],
    ['💰 Refund Process',
     "Once your return is received and inspected, we will notify you by email of the refund decision.\n\nApproved refunds are processed within 5–10 business days back to your original payment method:\n\n• Paystack card payments: 5–7 business days\n• Bank transfer payments: 7–10 business days\n\nRefund amounts will be the original product price minus any non-refundable delivery fees, unless the return is due to our error or a defective item."],
    ['🔄 Exchanges',
     "We do not currently offer direct product exchanges. If you would like a different size or colour, please:\n\n1. Return the original item following the standard return process\n2. Place a new order for the correct item once the return is approved\n\nContact us at hello@shopafricanattire.com and we will do our best to assist."],
    ['📞 Contact Support',
     "For all returns and refund queries:\n\n📧 hello@shopafricanattire.com\n⏰ Response within 2 business days\n📞 Support hours: Mon–Fri 8am–6pm WAT\n\nPlease always include your order number in any communications."],
  ]; foreach ($sections as [$title,$body]): ?>
  <div style="margin-bottom:22px">
    <h2 style="font-family:var(--ff-head);font-size:1rem;font-weight:700;color:var(--navy);margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid var(--ju-pale)"><?= e($title) ?></h2>
    <p style="font-size:.88rem;color:var(--text);white-space:pre-line;line-height:1.75"><?= e($body) ?></p>
  </div>
  <?php endforeach; ?>
  </div>
  <div style="text-align:center;margin-top:18px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>/pages/shipping-policy.php" style="color:var(--ju);font-weight:600;font-size:.86rem">Shipping Policy →</a>
    <span style="color:var(--border)">·</span>
    <a href="<?= BASE_URL ?>/pages/customer-faq.php" style="color:var(--text-muted);font-size:.86rem">Customer FAQ</a>
    <span style="color:var(--border)">·</span>
    <a href="<?= BASE_URL ?>/" style="color:var(--text-muted);font-size:.86rem">← Back to Store</a>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
