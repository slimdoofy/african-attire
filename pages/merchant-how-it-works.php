<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'How It Works — Sell on African Attire'; $activePage = '';
$markupRate = (float)getSetting('platform_markup', '5');
include __DIR__ . '/../includes/header_customer.php';
?>
<div style="background:linear-gradient(135deg,#0B1E3D 0%,var(--navy) 35%,var(--ju-dk) 100%);padding:48px 20px 56px">
  <div style="max-width:760px;margin:0 auto;text-align:center">
    <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(232,113,10,.2);border:1px solid rgba(232,113,10,.4);border-radius:999px;padding:5px 16px;font-size:.72rem;font-weight:700;color:var(--orange-lt);text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px">
      🌍 For African Fashion Merchants
    </div>
    <h1 style="font-family:var(--ff-head);font-size:clamp(1.8rem,4vw,2.8rem);color:#fff;line-height:1.08;margin-bottom:14px">
      How Selling on<br><em style="color:var(--orange-lt);font-style:italic">African Attire</em> Works
    </h1>
    <p style="font-size:.95rem;color:rgba(255,255,255,.7);max-width:520px;margin:0 auto 24px;line-height:1.65">
      From registration to your first payout — a complete walkthrough of the merchant experience
    </p>
    <a href="<?= BASE_URL ?>/merchant/join.php" class="btn btn-ju btn-lg" style="box-shadow:0 4px 16px rgba(232,113,10,.4)">
      🏪 Start Selling Free →
    </a>
  </div>
</div>

<div class="wrap-full" style="padding-top:32px;padding-bottom:48px;max-width:900px;margin:0 auto">

  <!-- Steps -->
  <?php $steps = [
    ['1','Apply & Get Approved','orange','🖊',
     'Start at the merchant registration page. Fill in your business name, email, phone, address, and bank details for payouts. Upload your shop logo if you have one.',
     ['Takes less than 5 minutes','No setup fees — completely free','Our team reviews within 24–48 hours','You\'ll receive an approval email']],
    ['2','Set Up Your Shop','navy','🏪',
     'Once approved, log into your Merchant Hub and go to Shop Setup. Add your shop banner, a compelling description, your city, and any social media links.',
     ['Upload a shop banner (recommended: 1200×300px)','Write a description that showcases your craft','Add your specialties and style','A complete profile gets 3× more views']],
    ['3','List Your Products','green','🏷',
     'Go to Merchant Hub → Products → Add Product. Add product name, category, price (in ₦ NGN or $ USD), stock quantity, available sizes, and description. Upload up to 5 product photos.',
     ['Enter prices in NGN or USD — we auto-convert','First photo becomes the main listing image','Products reviewed within 12–24 hours','Add multiple size and colour variants']],
    ['4','Receive Orders','orange','📦',
     'When a customer purchases your product, you receive an instant email notification. Log into Merchant Hub → Orders to view order details, the customer\'s delivery region, and the items purchased.',
     ['Instant email notification on every order','See delivery city/region and order contents','Prepare items promptly — fast merchants get better reviews','Update status to "Processing" when you start']],
    ['5','Ship & Update Status','navy','🚚',
     'Package your items securely and dispatch them. Update the order status to "Shipped" in your Merchant Hub — this automatically sends the customer a shipping notification email.',
     ['Package items with care — first impressions matter','Update status immediately when shipped','Customer receives automatic email notification','Use "Delivered" when confirmed received']],
    ['6','Get Paid','green','💰',
     'Your earnings accumulate in your Merchant Hub wallet after successful deliveries. You receive your exact cost of goods — no deductions from your payout. Request a payout at any time — funds reach your bank account within 2–5 business days.',
     ['You receive 100% of your cost of goods — no deductions','Earnings available immediately after delivery','Payout to any Nigerian bank account','Track all earnings in the Analytics dashboard']],
  ]; ?>

  <?php foreach ($steps as $i => [$num,$title,$accent,$ico,$desc,$bullets]): ?>
  <div style="display:flex;gap:24px;margin-bottom:32px;align-items:flex-start;flex-wrap:wrap">
    <!-- Step number -->
    <div style="flex-shrink:0;width:64px;height:64px;border-radius:50%;
                background:<?= $accent==='orange'?'var(--orange)':($accent==='navy'?'var(--navy)':'var(--green)') ?>;
                display:flex;align-items:center;justify-content:center;
                font-family:var(--ff-head);font-size:1.5rem;font-weight:800;color:#fff;
                box-shadow:0 4px 12px <?= $accent==='orange'?'rgba(232,113,10,.35)':($accent==='navy'?'rgba(15,45,82,.35)':'rgba(46,125,50,.35)') ?>;
                position:relative">
      <?= $ico ?>
      <span style="position:absolute;bottom:-4px;right:-4px;width:22px;height:22px;
                   background:#fff;border-radius:50%;display:flex;align-items:center;
                   justify-content:center;font-size:.7rem;font-weight:800;
                   color:<?= $accent==='orange'?'var(--orange)':($accent==='navy'?'var(--navy)':'var(--green)') ?>;
                   box-shadow:0 1px 4px rgba(0,0,0,.15)">
        <?= $num ?>
      </span>
    </div>
    <!-- Content -->
    <div style="flex:1;min-width:260px">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
        <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;
                     color:<?= $accent==='orange'?'var(--orange)':($accent==='navy'?'var(--navy)':'var(--green)') ?>">
          Step <?= $num ?>
        </span>
      </div>
      <h3 style="font-family:var(--ff-head);font-size:1.15rem;font-weight:700;color:var(--black);margin-bottom:8px">
        <?= e($title) ?>
      </h3>
      <p style="font-size:.88rem;color:var(--text-soft);line-height:1.65;margin-bottom:12px">
        <?= e($desc) ?>
      </p>
      <div style="display:flex;flex-direction:column;gap:6px">
        <?php foreach ($bullets as $b): ?>
        <div style="display:flex;align-items:flex-start;gap:8px;font-size:.82rem;color:var(--text)">
          <span style="color:<?= $accent==='orange'?'var(--orange)':($accent==='navy'?'var(--navy)':'var(--green)') ?>;font-weight:700;flex-shrink:0;margin-top:1px">✓</span>
          <span><?= e($b) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if ($i < count($steps)-1): ?>
    <div style="width:100%;border-left:2px dashed var(--border-lt);height:20px;margin-left:32px"></div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

      <?php endforeach; ?>
    </div>
    <p style="font-size:.82rem;color:var(--text-muted)">Transparent pricing — you receive your full cost of goods on every sale. No hidden fees.</p>
  </div>

  <!-- CTA -->
  <div style="text-align:center;background:linear-gradient(135deg,var(--navy),var(--ju-dk));border-radius:var(--r-lg);padding:32px 24px">
    <h3 style="font-family:var(--ff-head);font-size:1.3rem;color:#fff;margin-bottom:8px">Ready to Start Selling?</h3>
    <p style="font-size:.88rem;color:rgba(255,255,255,.7);margin-bottom:20px">Join verified African fashion merchants on the continent's growing marketplace.</p>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <a href="<?= BASE_URL ?>/merchant/join.php" class="btn btn-ju btn-lg">🏪 Apply Now — Free</a>
      <a href="<?= BASE_URL ?>/pages/merchant-faq.php" style="padding:10px 20px;background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:var(--r-sm);text-decoration:none;font-weight:600;font-size:.88rem">Read Merchant FAQ</a>
    </div>
  </div>

</div>
<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
