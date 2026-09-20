<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (Auth::check() && Auth::role() === 'merchant') {
    $shop = DB::fetch('SELECT id FROM shops WHERE user_id=?', [Auth::id()]);
    redirect($shop ? BASE_URL.'/merchant/' : BASE_URL.'/merchant/register.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::check()) {
    DB::update('users', ['role'=>'merchant'], 'id=?', [Auth::id()]);
    $_SESSION['urole'] = 'merchant';
    redirect(BASE_URL.'/merchant/register.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sell on African Attire — Become a Merchant</title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <style>
    .join-hero{
      background:linear-gradient(135deg,#0B1E3D 0%,#0F2D52 30%,#145230 70%,#1B6B3A 100%);
      min-height:100vh;position:relative;overflow:hidden;
      display:flex;flex-direction:column;
    }
    .join-hero::before{
      content:'';position:absolute;inset:0;
      background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
      pointer-events:none;
    }
    .join-nav{
      display:flex;align-items:center;justify-content:space-between;
      padding:16px 32px;position:relative;z-index:1;
    }
    .join-hero-body{
      flex:1;display:flex;align-items:center;
      max-width:1200px;margin:0 auto;width:100%;
      padding:40px 32px 60px;gap:60px;position:relative;z-index:1;
    }
    .join-left{flex:1;max-width:600px}
    .join-right{flex-shrink:0;width:380px}
    .join-tag{
      display:inline-flex;align-items:center;gap:6px;
      background:rgba(246,139,30,.2);border:1px solid rgba(246,139,30,.4);
      border-radius:999px;padding:5px 14px;
      font-size:.72rem;font-weight:700;color:var(--ju-lt);
      text-transform:uppercase;letter-spacing:.08em;margin-bottom:20px;
    }
    .join-h1{
      font-family:var(--ff-head);
      font-size:clamp(2rem,4.5vw,3.2rem);
      font-weight:800;color:#fff;
      line-height:1.05;letter-spacing:-.03em;
      margin-bottom:18px;
    }
    .join-h1 em{color:var(--ju-lt);font-style:italic}
    .join-sub{
      font-size:1rem;color:rgba(255,255,255,.7);
      line-height:1.65;max-width:500px;margin-bottom:28px;
    }
    /* Stats bar */
    .join-stats{
      display:flex;gap:28px;flex-wrap:wrap;margin-bottom:36px;
      padding-bottom:28px;border-bottom:1px solid rgba(255,255,255,.12);
    }
    .join-stat-val{
      font-family:var(--ff-head);font-size:1.6rem;font-weight:800;color:#fff;
      display:block;line-height:1;
    }
    .join-stat-key{font-size:.72rem;color:rgba(255,255,255,.55);margin-top:4px;display:block}
    /* Benefit items */
    .join-benefits{display:flex;flex-direction:column;gap:14px}
    .join-benefit{display:flex;align-items:flex-start;gap:12px}
    .join-benefit-ico{
      width:36px;height:36px;border-radius:8px;flex-shrink:0;
      display:flex;align-items:center;justify-content:center;font-size:1.1rem;
      background:rgba(255,255,255,.1);
    }
    .join-benefit-title{font-weight:700;font-size:.9rem;color:#fff;margin-bottom:2px}
    .join-benefit-desc{font-size:.8rem;color:rgba(255,255,255,.6);line-height:1.5}
    /* Card */
    .join-card{
      background:#fff;border-radius:16px;padding:28px;
      box-shadow:0 20px 60px rgba(0,0,0,.35);
    }
    .join-card-title{
      font-family:var(--ff-head);font-size:1.1rem;font-weight:700;
      color:var(--black);margin-bottom:5px;
    }
    .join-card-sub{font-size:.82rem;color:var(--text-muted);margin-bottom:20px}
    .join-or{
      display:flex;align-items:center;gap:10px;margin:16px 0;
      font-size:.76rem;color:var(--text-muted);
    }
    .join-or::before,.join-or::after{content:'';flex:1;height:1px;background:var(--border-lt)}
    @media(max-width:900px){
      .join-hero-body{flex-direction:column;gap:32px;padding:24px 20px 40px}
      .join-left{max-width:none}
      .join-right{width:100%;max-width:480px;margin:0 auto}
      .join-nav{padding:12px 20px}
    }
    @media(max-width:600px){
      .join-h1{font-size:1.9rem}
      .join-stats{gap:18px}
    }
  </style>
</head>
<body style="margin:0;background:#0B1E3D">

<div class="join-hero">

  <!-- Nav -->
  <nav class="join-nav">
    <a href="<?= BASE_URL ?>/" style="display:flex;align-items:center;gap:10px;text-decoration:none">
      <img src="<?= BASE_URL ?>/assets/images/logo.png"
           style="width:44px;height:44px;border-radius:8px;background:#fff;object-fit:contain;padding:3px;box-shadow:0 2px 8px rgba(0,0,0,.3)">
      <div>
        <div style="font-family:var(--ff-head);font-size:1rem;font-weight:700;color:#fff;line-height:1.1">African Attire</div>
        <div style="font-size:.62rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.08em">Merchant Portal</div>
      </div>
    </a>
    <div style="display:flex;gap:8px;align-items:center">
      <a href="<?= BASE_URL ?>/merchant/login.php"
         style="padding:8px 18px;background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.25);border-radius:6px;font-size:.82rem;font-weight:600;text-decoration:none;transition:.15s"
         onmouseover="this.style.background='rgba(255,255,255,.2)'"
         onmouseout="this.style.background='rgba(255,255,255,.1)'">
        Sign In
      </a>
      <a href="<?= BASE_URL ?>/"
         style="padding:8px 14px;color:rgba(255,255,255,.5);font-size:.8rem;text-decoration:none">
        ← Store
      </a>
    </div>
  </nav>

  <!-- Hero body -->
  <div class="join-hero-body">

    <!-- Left: value prop -->
    <div class="join-left">
      <div class="join-tag">🌍 Africa's #1 Fashion Marketplace</div>
      <h1 class="join-h1">
        Sell Your Fashion<br>to <em>Thousands</em> of<br>Customers Daily
      </h1>
      <p class="join-sub">
        Join verified African fashion merchants selling Ankara, Kente, Agbada,
        Kaftan &amp; more. Get your digital storefront live in minutes.
        We only earn when you earn — no monthly fees, no setup cost.
      </p>

      <!-- Stats -->
      <div class="join-stats">
        <div>
          <span class="join-stat-val"><?= number_format(DB::count("SELECT COUNT(*) FROM shops WHERE status='approved'")) ?>+</span>
          <span class="join-stat-key">Active Merchants</span>
        </div>
        <div>
          <span class="join-stat-val"><?= number_format(DB::count("SELECT COUNT(*) FROM products WHERE status='approved'")) ?>+</span>
          <span class="join-stat-key">Products Listed</span>
        </div>
        <div>
          <span class="join-stat-val">Free</span>
          <span class="join-stat-key">No Monthly Fees</span>
        </div>
        <div>
          <span class="join-stat-val">Free</span>
          <span class="join-stat-key">To Register</span>
        </div>
      </div>

      <!-- Benefits -->
      <div class="join-benefits">
        <?php foreach ([
          ['🏪','Free Digital Storefront',   'Your own branded shop page — no monthly fees, ever'],
          ['🌍','Reach Global Customers',    'Sell to buyers across Africa and the diaspora worldwide'],
          ['📦','Simple Order Management',   'Dashboard to track orders, update status, and notify buyers'],
          ['💰','Direct Bank Payouts',       'Your earnings go straight to your Nigerian bank account'],
          ['📈','Real-time Analytics',       'See your best-selling products, revenue trends and growth'],
          ['🔒','Secure & Trusted Platform', 'All payments handled by Paystack — the most trusted in Africa'],
        ] as $b): ?>
        <div class="join-benefit">
          <div class="join-benefit-ico"><?= $b[0] ?></div>
          <div>
            <div class="join-benefit-title"><?= $b[1] ?></div>
            <div class="join-benefit-desc"><?= $b[2] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Right: action card -->
    <div class="join-right">
      <div class="join-card">

        <?php if (Auth::check()): ?>
        <div class="join-card-title">👋 Welcome back, <?= e(explode(' ', Auth::user()['name'])[0]) ?>!</div>
        <div class="join-card-sub">You're signed in as a customer. One click to upgrade and set up your shop.</div>
        <form method="POST">
          <button type="submit" class="btn btn-ju btn-full btn-lg" style="font-size:.95rem;padding:13px">
            🏪 Set Up My Shop Now →
          </button>
        </form>
        <div style="margin-top:12px;text-align:center;font-size:.76rem;color:var(--text-muted)">
          Or <a href="<?= BASE_URL ?>/customer/logout.php" style="color:var(--text-muted)">sign out</a> to use a different account
        </div>

        <?php else: ?>
        <div class="join-card-title">Start Selling Today</div>
        <div class="join-card-sub">Create your merchant account — takes less than 5 minutes.</div>

        <a href="<?= BASE_URL ?>/merchant/signup.php"
           class="btn btn-ju btn-full btn-xl"
           style="font-size:1rem;font-weight:800;padding:14px;
                  box-shadow:0 4px 16px rgba(246,139,30,.4);margin-bottom:6px">
          🏪 Create Free Merchant Account
        </a>
        <p style="text-align:center;font-size:.74rem;color:var(--text-muted);margin-bottom:16px">
          No credit card · No monthly fees · Live in 24–48 hrs
        </p>

        <div class="join-or">already have an account?</div>

        <a href="<?= BASE_URL ?>/merchant/login.php"
           class="btn btn-ghost btn-full btn-lg"
           style="font-size:.88rem">
          Sign In to Merchant Portal
        </a>
        <?php endif; ?>

        <!-- Trust signals -->
        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border-lt)">
          <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);font-weight:700;text-align:center;margin-bottom:10px">
            Why merchants trust us
          </div>
          <div style="display:flex;flex-direction:column;gap:7px">
            <?php foreach ([
              ['✓','Reviewed &amp; approved within 24–48 hours'],
              ['✓','Paystack-secured payments — industry standard'],
              ['✓','Dedicated merchant support team'],
            ] as $t): ?>
            <div style="display:flex;align-items:center;gap:8px;font-size:.8rem;color:var(--text-soft)">
              <span style="width:18px;height:18px;border-radius:50%;background:var(--green-pale);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;flex-shrink:0"><?= $t[0] ?></span>
              <?= $t[1] ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Social proof -->
      <div style="margin-top:14px;text-align:center;font-size:.78rem;color:rgba(255,255,255,.45)">
        Trusted by merchants across Nigeria, Ghana, Kenya &amp; beyond
      </div>
    </div>

  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
