<?php
$pageTitle  = $pageTitle  ?? SITE_NAME;
$activePage = $activePage ?? '';
$_cart   = Auth::check() ? cartCount()  : 0;
$_notifs = Auth::check() ? notifCount() : 0;
$_cats   = getCategories();
$_curAct = activeCurrency();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle) ?> — <?= SITE_NAME ?></title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <style>
    /* ── Currency switcher ─────────────────────────────────── */
    .cur-switch{
      display:flex;align-items:center;gap:0;
      background:rgba(0,0,0,.25);
      border:2px solid rgba(255,255,255,.35);
      border-radius:999px;overflow:hidden;
      flex-shrink:0;
    }
    .cur-btn{
      padding:6px 14px;border:none;cursor:pointer;
      font-size:.78rem;font-weight:800;letter-spacing:.04em;
      font-family:var(--ff);transition:.18s;line-height:1;
      background:transparent;color:rgba(255,255,255,.6);
    }
    .cur-btn.active{
      background:#fff;
      color:var(--ju-dk);
      border-radius:999px;
      box-shadow:0 2px 8px rgba(0,0,0,.2);
    }
    .cur-btn:hover:not(.active){color:#fff;background:rgba(255,255,255,.15)}

  </style>
  <script>const BASE_URL='<?= BASE_URL ?>';</script>
</head>
<body>

<!-- Cart Drawer -->
<div id="cart-backdrop" class="cart-backdrop"></div>
<div id="cart-drawer" class="cart-drawer">
  <div class="cart-head">
    <h3>My Cart <?php if($_cart>0):?><span style="font-size:.72rem;opacity:.75;font-family:var(--ff)">(<?=$_cart?> items)</span><?php endif;?></h3>
    <button id="cart-close" class="cart-close-btn">✕</button>
  </div>
  <div id="cart-body" class="cart-body"><div class="empty-state"><span class="empty-icon">🛒</span><p>Your cart is empty</p></div></div>
  <div id="cart-footer" class="cart-foot"></div>
</div>

<!-- Top strip (desktop) -->
<div class="ju-topstrip">
  <div class="ju-topstrip-inner">
    <a href="<?= BASE_URL ?>/customer/track-order.php">📦 Track Order</a>
    <a href="<?= BASE_URL ?>/merchant/join.php">🏪 Sell on African Attire</a>
    <a href="#">❓ Help</a>

  </div>
</div>

<!-- ─── MAIN HEADER ─── -->
<header class="ju-header">
  <div class="ju-header-inner">

    <!-- Logo -->
    <a href="<?= BASE_URL ?>/" class="ju-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="African Attire"
           style="background:#fff;border-radius:4px;object-fit:contain">
      <span class="ju-logo-text">African Attire</span>
    </a>

    <!-- Search -->
    <form class="ju-search" action="<?= BASE_URL ?>/customer/shop.php" method="GET">
      <input type="text" name="q"
             placeholder="Search African fashion — Ankara, Agbada, Kente…"
             value="<?= e($_GET['q'] ?? '') ?>">
      <button type="submit">🔍</button>
    </form>

    <!-- Actions -->
    <div class="ju-actions">

      <!-- ══ CURRENCY SWITCHER — dynamic, all enabled currencies ══ -->
      <?php $_enabledCurs = enabledCurrencies(); ?>
      <div class="cur-switch" title="Switch display currency">
        <?php foreach ($_enabledCurs as $_cCode => $_cCfg): ?>
        <button class="cur-btn <?= $_curAct===$_cCode?'active':'' ?>"
                id="btn-<?= $_cCode ?>"
                onclick="switchCurrency('<?= $_cCode ?>')">
          <?= htmlspecialchars($_cCfg['symbol']) ?> <?= strtoupper($_cCode) ?>
        </button>
        <?php endforeach; ?>
      </div>

      <?php if(Auth::check()): ?>
        <a href="<?= BASE_URL ?>/customer/wishlist.php" class="ju-btn">
          <span class="jbi">♡</span><span class="ju-btn-lbl">Wishlist</span>
        </a>
        <a href="<?= BASE_URL ?>/customer/notifications.php" class="ju-btn">
          <span class="jbi">🔔</span><span class="ju-btn-lbl">Alerts</span>
          <?php if($_notifs>0):?><span class="ju-badge"><?= min($_notifs,9) ?></span><?php endif;?>
        </a>
        <button class="ju-btn" onclick="openCart()">
          <span class="jbi">🛒</span><span class="ju-btn-lbl">Cart</span>
          <span class="ju-badge cart-badge" <?= $_cart>0?'':'style="display:none"' ?>><?= $_cart ?></span>
        </button>
        <div class="dd-wrap">
          <button class="ju-btn" data-dd>
            <span class="jbi">👤</span>
            <span class="ju-btn-lbl"><?= e(explode(' ', Auth::user()['name'])[0]) ?> ▾</span>
          </button>
          <div class="dd-menu">
            <div style="padding:5px 10px 2px;font-size:.65rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.07em">My Account</div>
            <a href="<?= BASE_URL ?>/customer/profile.php"       class="dd-item">👤 Profile</a>
            <a href="<?= BASE_URL ?>/customer/my-disputes.php" class="dd-item <?= $activePage==='disputes'?'active':'' ?>">⚖️ My Disputes</a>
          <a href="<?= BASE_URL ?>/customer/orders.php"        class="dd-item">📦 My Orders</a>
            <a href="<?= BASE_URL ?>/customer/wishlist.php"      class="dd-item">♡ Wishlist</a>
            <a href="<?= BASE_URL ?>/customer/notifications.php" class="dd-item">🔔 Notifications</a>
            <?php if(Auth::role()==='merchant'):?>
            <hr class="dd-sep">
            <a href="<?= BASE_URL ?>/merchant/" class="dd-item">🏪 Merchant Hub</a>
            <?php endif;?>
            <hr class="dd-sep">
            <a href="<?= BASE_URL ?>/customer/logout.php" class="dd-item danger">🚪 Sign Out</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/customer/login.php" class="ju-btn">
          <span class="jbi">👤</span><span class="ju-btn-lbl">Sign In</span>
        </a>
        <a href="<?= BASE_URL ?>/customer/register.php"
           class="btn btn-white"
           style="border-color:rgba(255,255,255,.6);color:#fff;
                  background:rgba(255,255,255,.12);font-size:.78rem;padding:6px 12px">
          Join Free
        </a>
        <button class="ju-btn" onclick="openCart()">
          <span class="jbi">🛒</span><span class="ju-btn-lbl">Cart</span>
          <span class="ju-badge cart-badge" style="display:none">0</span>
        </button>
      <?php endif; ?>
    </div>

  </div>
</header>

<!-- ─── CATEGORY NAV BAR ─── -->
<nav class="ju-catbar">
  <div class="ju-catbar-inner">
    <a href="<?= BASE_URL ?>/customer/shop.php"
       class="ju-cat <?= $activePage==='shop'?'active':''?>">
      <span class="jc-ico">🏬</span> All Products
    </a>
    <a href="<?= BASE_URL ?>/customer/merchants.php"
       class="ju-cat <?= $activePage==='merchants'?'active':''?>">
      <span class="jc-ico">✨</span> Designers
    </a>
    <a href="<?= BASE_URL ?>/customer/track-order.php"
       class="ju-cat <?= $activePage==='track'?'active':''?>">
      <span class="jc-ico">📦</span> Track Order
    </a>
    <?php foreach($_cats as $c): ?>
    <a href="<?= BASE_URL ?>/customer/shop.php?category=<?= $c['id'] ?>"
       class="ju-cat">
      <span class="jc-ico"><?= $c['icon'] ?></span> <?= e($c['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
</nav>

<div id="toast-host"></div>
<?php foreach(getFlashes() as $f): ?>
<div class="wrap" style="margin-top:8px">
  <div class="alert alert-<?= $f['type']==='error'?'danger':e($f['type']) ?>"><?= e($f['msg']) ?></div>
</div>
<?php endforeach; ?>

<script>
// ── Currency switcher ─────────────────────────────────────────
window.switchCurrency = function(c) {
  // Optimistic UI: mark all inactive, set selected active
  document.querySelectorAll('.cur-btn').forEach(function(btn) {
    btn.classList.remove('active');
  });
  var active = document.getElementById('btn-' + c);
  if (active) active.classList.add('active');

  fetch(BASE_URL + '/api/currency.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({currency: c})
  })
  .then(function(r){ return r.json(); })
  .then(function(d){ if (d.ok) location.reload(); });
};
</script>
