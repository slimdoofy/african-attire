<?php
// ob_start() MUST be first — buffers output so redirect() works even after HTML starts.
ob_start();

// ── ALL CHECKS BEFORE ANY OUTPUT ──────────────────────────────
Auth::requireRole('merchant');

$_shop    = DB::fetch('SELECT * FROM shops WHERE user_id=?', [Auth::id()]);
$_user    = Auth::user();
$pageTitle = $pageTitle ?? 'Merchant Hub';
$activeNav = $activeNav ?? '';

// No shop yet — redirect to register (redirect() calls exit, no output yet)
if (!$_shop) {
    redirect(BASE_URL . '/merchant/register.php');
}

// Pending/rejected gate — show holding page BEFORE any main page output
if ($_shop['status'] === 'pending' || $_shop['status'] === 'rejected') {
    // Output the full holding page here, then exit
    // (no output has happened yet so headers are clean)
    $isPending = ($_shop['status'] === 'pending');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Application Under Review — African Attire</title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body style="background:var(--bg);min-height:100vh;display:flex;flex-direction:column">

<header style="background:linear-gradient(135deg,#0D47A1,#1565C0,#1B5E20);padding:14px 20px">
  <div style="max-width:900px;margin:0 auto;display:flex;align-items:center;justify-content:space-between">
    <a href="<?= BASE_URL ?>/" style="display:flex;align-items:center;gap:10px;text-decoration:none">
      <img src="<?= BASE_URL ?>/assets/images/logo.png"
           style="width:40px;height:40px;border-radius:6px;background:#fff;object-fit:contain;padding:2px">
      <div>
        <div style="font-family:var(--ff-head);font-size:.95rem;font-weight:700;color:#fff">African Attire</div>
        <div style="font-size:.62rem;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.08em">Merchant Portal</div>
      </div>
    </a>
    <a href="<?= BASE_URL ?>/customer/logout.php"
       style="padding:6px 14px;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.25);border-radius:6px;font-size:.8rem;font-weight:600;text-decoration:none">
      Sign Out
    </a>
  </div>
</header>

<div style="flex:1;display:flex;align-items:center;justify-content:center;padding:32px 16px">
  <div style="max-width:560px;width:100%;text-align:center">

    <?php if ($isPending): ?>
    <div style="width:80px;height:80px;border-radius:50%;background:#FFF8E1;border:3px solid #FFE082;display:flex;align-items:center;justify-content:center;font-size:2.5rem;margin:0 auto 20px">⏳</div>
    <h1 style="font-family:var(--ff-head);font-size:1.5rem;color:var(--black);margin-bottom:10px">Application Under Review</h1>
    <p style="font-size:.9rem;color:var(--text-muted);line-height:1.65;margin-bottom:24px">
      Thank you, <strong><?= e($_user['name']) ?></strong>! Your shop
      <strong>"<?= e($_shop['shop_name']) ?>"</strong> is being reviewed. We approve applications within <strong>24–48 hours</strong>.
    </p>
    <div style="background:#FFF8E1;border:1px solid #FFE082;border-radius:var(--r-lg);padding:18px 22px;margin-bottom:24px;text-align:left">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#856404;margin-bottom:12px">What happens next?</div>
      <div style="display:flex;flex-direction:column;gap:10px;font-size:.86rem;color:#555">
        <div style="display:flex;gap:10px"><span style="color:var(--ju);font-weight:700">📧</span><span>You'll receive an email when your application is reviewed</span></div>
        <div style="display:flex;gap:10px"><span style="color:var(--green);font-weight:700">✅</span><span>Once approved, log in to start adding products immediately</span></div>
        <div style="display:flex;gap:10px"><span style="color:var(--blue);font-weight:700">🌍</span><span>Your shop goes live on the African Attire marketplace</span></div>
      </div>
    </div>
    <?php else: ?>
    <div style="width:80px;height:80px;border-radius:50%;background:var(--red-pale);border:3px solid #FFCDD2;display:flex;align-items:center;justify-content:center;font-size:2.5rem;margin:0 auto 20px">❌</div>
    <h1 style="font-family:var(--ff-head);font-size:1.5rem;color:var(--black);margin-bottom:10px">Application Not Approved</h1>
    <p style="font-size:.9rem;color:var(--text-muted);line-height:1.65;margin-bottom:24px">
      Unfortunately your shop <strong>"<?= e($_shop['shop_name']) ?>"</strong> was not approved. Contact support for details or to reapply.
    </p>
    <?php endif; ?>

    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <a href="<?= BASE_URL ?>/" class="btn btn-ghost btn-lg">← Back to Store</a>
      <a href="mailto:<?= getSetting('site_email','hello@africanattire.com') ?>" class="btn btn-ju btn-lg">Contact Support</a>
    </div>
    <div style="margin-top:16px;font-size:.76rem;color:var(--text-muted)">
      Email: <a href="mailto:<?= getSetting('site_email','hello@africanattire.com') ?>" style="color:var(--ju)"><?= getSetting('site_email','hello@africanattire.com') ?></a>
    </div>

  </div>
</div>
</body>
</html>
<?php
    exit; // Stop everything — no more output
}

// ── APPROVED: now compute data and output dashboard header ─────
$_pct = 0;
if ($_shop['shop_name'])    $_pct += 20;
if ($_shop['description'])  $_pct += 20;
if ($_shop['logo'])         $_pct += 20;
if ($_shop['banner'])       $_pct += 20;
if ($_shop['bank_account']) $_pct += 20;

$_pendingOrders = (int)DB::count(
    "SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi
     JOIN orders o ON o.id=oi.order_id
     WHERE oi.shop_id=? AND o.status='pending'",
    [$_shop['id']]
);

// ── NOW output HTML for the dashboard ─────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle) ?> — Merchant Hub · African Attire</title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <style>
    .merchant-header{
      background:linear-gradient(135deg,#0D47A1 0%,#1565C0 55%,#1B5E20 100%);
      position:sticky;top:0;z-index:600;
      box-shadow:0 2px 12px rgba(0,0,0,.22);
    }
    .merchant-header-top{
      display:flex;align-items:center;gap:14px;
      max-width:1400px;margin:0 auto;padding:10px 20px;
    }
    .mh-logo{display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0}
    .mh-logo-img{width:52px;height:52px;border-radius:8px;background:#fff;object-fit:contain;padding:3px;box-shadow:0 2px 8px rgba(0,0,0,.2);flex-shrink:0}
    .mh-logo-title{font-family:var(--ff-head);font-size:1rem;font-weight:700;color:#fff;display:block}
    .mh-logo-sub{font-size:.62rem;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.1em;display:block;margin-top:1px}
    .mh-divider{width:1px;height:38px;background:rgba(255,255,255,.2);flex-shrink:0}
    .mh-shop-badge{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);border-radius:8px;padding:6px 12px;flex-shrink:0}
    .mh-shop-label{font-size:.62rem;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.08em;display:block}
    .mh-shop-name{font-size:.88rem;font-weight:700;color:#fff;display:block;margin-top:1px}
    .mh-pill-approved{display:inline-flex;align-items:center;gap:4px;padding:4px 11px;background:var(--green);color:#fff;border-radius:999px;font-size:.67rem;font-weight:700;letter-spacing:.03em;text-transform:uppercase}
    .mh-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}
    .mh-btn{display:flex;align-items:center;gap:5px;padding:7px 13px;border-radius:6px;font-size:.78rem;font-weight:600;text-decoration:none;transition:opacity .15s;white-space:nowrap;border:none;cursor:pointer;font-family:inherit}
    .mh-btn-ghost{background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.22)}
    .mh-btn-ghost:hover{background:rgba(255,255,255,.2);color:#fff}
    .mh-btn-danger{background:#C62828;color:#fff}
    .mh-btn-danger:hover{background:#B71C1C;color:#fff}
    .merchant-nav-bar{background:rgba(0,0,0,.18);border-top:1px solid rgba(255,255,255,.07)}
    .merchant-nav-inner{display:flex;overflow-x:auto;scrollbar-width:none;max-width:1400px;margin:0 auto;padding:0 20px}
    .merchant-nav-inner::-webkit-scrollbar{display:none}
    .merchant-nav-link{display:flex;align-items:center;gap:5px;padding:8px 14px;font-size:.77rem;font-weight:600;color:rgba(255,255,255,.6);text-decoration:none;white-space:nowrap;flex-shrink:0;border-bottom:2px solid transparent;transition:color .15s,border-color .15s}
    .merchant-nav-link:hover{color:#fff;border-bottom-color:rgba(255,255,255,.4)}
    .merchant-nav-link.active{color:#fff;border-bottom-color:var(--ju)}
    .mnl-badge{background:var(--ju);color:#fff;font-size:.6rem;font-weight:800;padding:1px 5px;border-radius:999px;margin-left:3px}
    /* Merchant page cards */
    .m-page-head{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px}
    .m-page-title{font-family:var(--ff-head);font-size:1.4rem;font-weight:700;color:var(--black);margin-bottom:3px}
    .m-page-sub{font-size:.82rem;color:var(--text-muted)}
    .m-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:18px}
    .m-stat{background:#fff;border:1px solid var(--border-lt);border-radius:var(--r-lg);padding:14px 16px;box-shadow:var(--sh-xs)}
    .m-stat.accent-orange{border-left:4px solid var(--ju)}
    .m-stat.accent-blue{border-left:4px solid var(--blue)}
    .m-stat.accent-green{border-left:4px solid var(--green)}
    .m-stat.accent-gold{border-left:4px solid #F9A825}
    .m-stat-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:5px}
    .m-stat-value{font-family:var(--ff-head);font-size:1.5rem;font-weight:800;color:var(--black);line-height:1}
    .m-stat-hint{font-size:.72rem;color:var(--text-muted);margin-top:4px}
    @media(max-width:600px){
      .merchant-header-top{padding:8px 12px;gap:8px}
      .mh-logo-img{width:38px;height:38px}
      .mh-divider,.mh-shop-badge{display:none}
    }
  </style>
  <script>const BASE_URL='<?= BASE_URL ?>';</script>
</head>
<body style="background:var(--bg)">

<header class="merchant-header">
  <div class="merchant-header-top">
    <a href="<?= BASE_URL ?>/merchant/" class="mh-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" class="mh-logo-img" alt="African Attire">
      <div>
        <span class="mh-logo-title">African Attire</span>
        <span class="mh-logo-sub">Merchant Hub</span>
      </div>
    </a>
    <div class="mh-divider"></div>
    <div class="mh-shop-badge">
      <span class="mh-shop-label">Your Shop</span>
      <span class="mh-shop-name"><?= e($_shop['shop_name']) ?></span>
    </div>
    <span class="mh-pill-approved">✓ Approved</span>
    <div style="flex:1"></div>
    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
      <span style="font-size:.7rem;color:rgba(255,255,255,.5)">Profile <?= $_pct ?>%</span>
      <div style="width:60px;height:4px;background:rgba(255,255,255,.15);border-radius:2px;overflow:hidden">
        <div style="height:100%;width:<?= $_pct ?>%;background:var(--ju)"></div>
      </div>
    </div>
    <div class="mh-actions">
      <a href="<?= BASE_URL ?>/" target="_blank" class="mh-btn mh-btn-ghost">🌐 View Store</a>
      <a href="<?= BASE_URL ?>/customer/logout.php" class="mh-btn mh-btn-danger">Sign Out</a>
    </div>
  </div>
  <div class="merchant-nav-bar">
    <div class="merchant-nav-inner">
      <a href="<?= BASE_URL ?>/merchant/"              class="merchant-nav-link <?= $activeNav==='overview'  ?'active':''?>">📊 Dashboard</a>
      <a href="<?= BASE_URL ?>/merchant/orders.php"    class="merchant-nav-link <?= $activeNav==='orders'    ?'active':''?>">
        📦 Orders<?php if($_pendingOrders>0):?><span class="mnl-badge"><?=$_pendingOrders?></span><?php endif;?>
      </a>
      <a href="<?= BASE_URL ?>/merchant/products.php"  class="merchant-nav-link <?= $activeNav==='products'  ?'active':''?>">🏷 Products</a>
      <a href="<?= BASE_URL ?>/merchant/inventory.php" class="merchant-nav-link <?= $activeNav==='inventory' ?'active':''?>">📋 Inventory</a>
      <a href="<?= BASE_URL ?>/merchant/analytics.php" class="merchant-nav-link <?= $activeNav==='analytics' ?'active':''?>">📈 Analytics</a>
      <a href="<?= BASE_URL ?>/merchant/payouts.php"   class="merchant-nav-link <?= $activeNav==='payouts'   ?'active':''?>">💰 Payouts</a>
      <a href="<?= BASE_URL ?>/merchant/shop-setup.php" class="merchant-nav-link <?= $activeNav==='shop-setup'?'active':''?>">🏪 Shop Setup</a>
      <a href="<?= BASE_URL ?>/merchant/discounts.php" class="merchant-nav-link <?= $activeNav==='discounts'?'active':''?>">🏷 Discounts</a>
      <a href="<?= BASE_URL ?>/merchant/settings.php"  class="merchant-nav-link <?= $activeNav==='settings'  ?'active':''?>">⚙️ Settings</a>
    </div>
  </div>
</header>

<div id="toast-host"></div>
<?php foreach(getFlashes() as $f): ?>
<div style="max-width:1400px;margin:.5rem auto;padding:0 20px">
  <div class="alert alert-<?= $f['type']==='error'?'danger':e($f['type']) ?>"><?= e($f['msg']) ?></div>
</div>
<?php endforeach; ?>

<div class="dash-layout">
<aside class="dash-sidebar">
  <div class="sidebar-card">
    <div style="background:linear-gradient(135deg,#0D47A1,#1B5E20);padding:14px">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
        <div class="shop-avatar" style="width:46px;height:46px;border:2px solid rgba(255,255,255,.25);flex-shrink:0">
          <?php if($_shop['logo']): ?><img src="<?= imgUrl($_shop['logo']) ?>" alt=""><?php else: ?><span style="color:#fff;font-size:1.3rem">🏪</span><?php endif; ?>
        </div>
        <div>
          <div style="font-weight:700;font-size:.86rem;color:#fff"><?= e($_shop['shop_name']) ?></div>
          <div style="font-size:.7rem;color:rgba(255,255,255,.5)">📍 <?= e($_shop['city']??'Nigeria') ?></div>
        </div>
      </div>
      <div style="background:rgba(255,255,255,.08);border-radius:4px;padding:8px 10px">
        <div style="display:flex;justify-content:space-between;margin-bottom:5px">
          <span style="font-size:.65rem;color:rgba(255,255,255,.5)">Profile completion</span>
          <span style="font-size:.68rem;font-weight:700;color:var(--ju-lt)"><?= $_pct ?>%</span>
        </div>
        <div style="height:5px;background:rgba(255,255,255,.12);border-radius:3px;overflow:hidden">
          <div style="height:100%;width:<?= $_pct ?>%;background:var(--ju)"></div>
        </div>
        <?php if($_pct<100): ?>
        <a href="<?= BASE_URL ?>/merchant/shop-setup.php" style="display:block;font-size:.66rem;color:rgba(255,255,255,.45);margin-top:5px;text-decoration:none">Complete profile →</a>
        <?php endif; ?>
      </div>
    </div>
    <div style="padding:.4rem 0">
      <div class="sidebar-section-label">Overview</div>
      <a href="<?= BASE_URL ?>/merchant/"              class="sidebar-link <?=$activeNav==='overview'  ?'active':''?>"><span class="sidebar-icon">📊</span> Dashboard</a>
      <a href="<?= BASE_URL ?>/merchant/orders.php"    class="sidebar-link <?=$activeNav==='orders'    ?'active':''?>">
        <span class="sidebar-icon">📦</span> Orders
        <?php if($_pendingOrders>0):?><span style="margin-left:auto;background:var(--ju);color:#fff;font-size:.6rem;padding:1px 5px;border-radius:999px;font-weight:800"><?=$_pendingOrders?></span><?php endif;?>
      </a>
      <a href="<?= BASE_URL ?>/merchant/products.php"  class="sidebar-link <?=$activeNav==='products'  ?'active':''?>"><span class="sidebar-icon">🏷</span> Products</a>
      <a href="<?= BASE_URL ?>/merchant/inventory.php" class="sidebar-link <?=$activeNav==='inventory' ?'active':''?>"><span class="sidebar-icon">📋</span> Inventory</a>
      <a href="<?= BASE_URL ?>/merchant/analytics.php" class="sidebar-link <?=$activeNav==='analytics' ?'active':''?>"><span class="sidebar-icon">📈</span> Analytics</a>
      <a href="<?= BASE_URL ?>/merchant/payouts.php"   class="sidebar-link <?=$activeNav==='payouts'   ?'active':''?>"><span class="sidebar-icon">💰</span> Payouts</a>
      <div class="sidebar-section-label">Settings</div>
      <a href="<?= BASE_URL ?>/merchant/shop-setup.php" class="sidebar-link <?=$activeNav==='shop-setup'?'active':''?>"><span class="sidebar-icon">🏪</span> Shop Setup</a>
      <a href="<?= BASE_URL ?>/merchant/discounts.php"  class="sidebar-link <?=$activeNav==='discounts'?'active':''?>"><span class="sidebar-icon">🏷</span> Discounts</a>
      <a href="<?= BASE_URL ?>/merchant/settings.php"   class="sidebar-link <?=$activeNav==='settings'  ?'active':''?>"><span class="sidebar-icon">⚙️</span> Settings</a>
      <div class="sidebar-section-label">Account</div>
      <a href="<?= BASE_URL ?>/" target="_blank" class="sidebar-link"><span class="sidebar-icon">🌐</span> View Store</a>
      <a href="<?= BASE_URL ?>/customer/logout.php" class="sidebar-link" style="color:var(--red)"><span class="sidebar-icon">🚪</span> Sign Out</a>
    </div>
  </div>
</aside>
<div class="dash-main">
