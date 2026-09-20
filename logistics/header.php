<?php
$_la   = logAuth();
$_lco  = DB::fetch('SELECT * FROM logistics_companies WHERE id=?', [$_la['company_id']]);
$pageTitle = $pageTitle ?? 'Logistics Portal';
$activeNav = $activeNav ?? '';
$_pendingCount = (int)DB::count(
    "SELECT COUNT(*) FROM order_logistics WHERE company_id=? AND status='assigned'",
    [$_la['company_id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle) ?> — African Attire Logistics</title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <style>
    .log-header{
      background:linear-gradient(135deg,#0D47A1 0%,#1565C0 60%,#0F4C35 100%);
      position:sticky;top:0;z-index:600;
      box-shadow:0 2px 12px rgba(0,0,0,.25);
    }
    .log-header-inner{
      display:flex;align-items:center;gap:14px;
      max-width:1400px;margin:0 auto;padding:10px 20px;
    }
    .log-logo{display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0}
    .log-logo img{width:44px;height:44px;border-radius:7px;background:#fff;object-fit:contain;padding:3px}
    .log-logo-title{font-family:var(--ff-head);font-size:.95rem;font-weight:700;color:#fff;display:block}
    .log-logo-sub{font-size:.6rem;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.1em;display:block}
    .log-company-badge{
      background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);
      border-radius:8px;padding:5px 12px;flex-shrink:0;
    }
    .log-company-badge span:first-child{font-size:.62rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.07em;display:block}
    .log-company-badge strong{font-size:.86rem;font-weight:700;color:#fff;display:block}
    .log-nav-bar{background:rgba(0,0,0,.2);border-top:1px solid rgba(255,255,255,.08)}
    .log-nav-inner{display:flex;overflow-x:auto;scrollbar-width:none;max-width:1400px;margin:0 auto;padding:0 20px}
    .log-nav-inner::-webkit-scrollbar{display:none}
    .log-nav-link{display:flex;align-items:center;gap:5px;padding:8px 14px;font-size:.77rem;font-weight:600;color:rgba(255,255,255,.6);text-decoration:none;white-space:nowrap;flex-shrink:0;border-bottom:2px solid transparent;transition:.15s}
    .log-nav-link:hover{color:#fff;border-bottom-color:rgba(255,255,255,.4)}
    .log-nav-link.active{color:#fff;border-bottom-color:var(--orange)}
    .log-badge{background:var(--orange);color:#fff;font-size:.6rem;font-weight:800;padding:1px 5px;border-radius:999px;margin-left:3px}
    @media(max-width:600px){.log-company-badge,.log-divider{display:none}.log-header-inner{gap:8px;padding:8px 12px}}
  </style>
  <script>const BASE_URL='<?= BASE_URL ?>';</script>
</head>
<body style="background:var(--bg)">

<header class="log-header">
  <div class="log-header-inner">
    <a href="<?= BASE_URL ?>/logistics/" class="log-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="African Attire">
      <div>
        <span class="log-logo-title">African Attire</span>
        <span class="log-logo-sub">Logistics Portal</span>
      </div>
    </a>
    <div style="width:1px;height:36px;background:rgba(255,255,255,.2);flex-shrink:0"></div>
    <div class="log-company-badge">
      <span>Company</span>
      <strong><?= e($_lco ? $_lco['company_name'] : '—') ?></strong>
    </div>
    <div style="flex:1"></div>
    <div style="display:flex;align-items:center;gap:8px">
      <span style="font-size:.78rem;color:rgba(255,255,255,.6)">
        👤 <?= e($_la['name']) ?>
        <?php if ($_la['role']==='admin'): ?>
        <span style="background:var(--orange);color:#fff;font-size:.6rem;font-weight:700;padding:1px 6px;border-radius:999px;margin-left:4px">ADMIN</span>
        <?php endif; ?>
      </span>
      <a href="<?= BASE_URL ?>/logistics/logout.php"
         style="padding:6px 12px;background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:6px;font-size:.76rem;font-weight:600;text-decoration:none">
        Sign Out
      </a>
    </div>
  </div>
  <div class="log-nav-bar">
    <div class="log-nav-inner">
      <a href="<?= BASE_URL ?>/logistics/"
         class="log-nav-link <?= $activeNav==='dash'?'active':''?>">📊 Dashboard</a>
      <a href="<?= BASE_URL ?>/logistics/orders.php"
         class="log-nav-link <?= $activeNav==='orders'?'active':''?>">
        📦 Orders
        <?php if($_pendingCount>0):?><span class="log-badge"><?=$_pendingCount?></span><?php endif;?>
      </a>
      <a href="<?= BASE_URL ?>/logistics/payments.php"
         class="log-nav-link <?= $activeNav==='payments'?'active':''?>">
        💳 Payments
      </a>
      <?php if($_la['role']==='admin'):?>
      <a href="<?= BASE_URL ?>/logistics/users.php"
         class="log-nav-link <?= $activeNav==='users'?'active':''?>">👥 Team</a>
      <?php endif;?>
      <a href="<?= BASE_URL ?>/logistics/change-password.php"
         class="log-nav-link <?= $activeNav==='pw'?'active':''?>">🔑 Password</a>
      <?php if($_la['role']==='admin'):?>
      <a href="<?= BASE_URL ?>/logistics/bank-account.php"
         class="log-nav-link <?= $activeNav==='bank'?'active':''?>">🏦 Bank Account</a>
      <?php endif;?>
    </div>
  </div>
</header>

<div id="toast-host"></div>
<?php foreach(getFlashes() as $f): ?>
<div style="max-width:1400px;margin:.5rem auto;padding:0 20px">
  <div class="alert alert-<?= $f['type']==='error'?'danger':e($f['type']) ?>"><?= e($f['msg']) ?></div>
</div>
<?php endforeach; ?>

<div style="max-width:1400px;margin:0 auto;padding:16px 20px 40px">
