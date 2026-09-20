<?php
Auth::requireRole('admin', '/admin/login.php');
$pageTitle  = $pageTitle  ?? 'Admin';
$activeNav  = $activeNav  ?? '';

// ── Module access control ─────────────────────────────────────
// Super admins ($_SESSION['admin_type'] === 'super') have full access.
// Sub-admins are restricted to their assigned modules array.
// canAccess() is called in nav links AND at the top of each page.
function canAccess(string $module): bool {
    if (!isset($_SESSION['admin_type'])) return true; // not yet set → allow (login page etc.)
    if ($_SESSION['admin_type'] === 'super')  return true;
    $mods = $_SESSION['admin_modules'] ?? [];
    return in_array('*', $mods, true) || in_array($module, $mods, true);
}

// If the current page's activeNav module is not allowed, block access
$_pageModule = $activeNav ?: 'dashboard';
if (!empty($_SESSION['admin_type']) && $_SESSION['admin_type'] === 'sub') {
    $moduleMap = [
        'overview'         => 'dashboard',
        'users'            => 'customers',
        'products'         => 'products',
        'categories'       => 'categories',
        'orders'           => 'orders',
        'agents'           => 'merchants',
        'merchants'        => 'merchants',
        'payouts'          => 'payouts',
        'reports'          => 'reports',
        'banners'          => 'banners',
        'settings'         => 'settings',
        'logistics'        => 'logistics',
        'logistics-payouts'=> 'logistics',
        'commission'       => 'markup',
        'discounts'        => 'discounts',
        'audit-log'        => 'audit_log',
        'admin-users'      => 'audit_log', // only super admins see this really
        'disputes'         => 'orders',
        'notifications'    => 'dashboard',
        'exchange-log'     => 'settings',
    ];
    $requiredModule = $moduleMap[$activeNav] ?? $activeNav;
    if ($requiredModule && !canAccess($requiredModule)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><title>Access Denied</title>
              <link rel="stylesheet" href="'.BASE_URL.'/assets/css/main.css"></head><body>
              <div style="max-width:520px;margin:80px auto;text-align:center;padding:40px">
              <div style="font-size:3rem;margin-bottom:16px">🔒</div>
              <h2 style="font-family:var(--ff-head);color:var(--navy);margin-bottom:8px">Access Denied</h2>
              <p style="color:var(--text-muted);margin-bottom:20px">
                You do not have permission to access this module.<br>
                Contact your administrator to request access.
              </p>
              <a href="'.BASE_URL.'/admin/" class="btn btn-ju">← Back to Dashboard</a>
              </div></body></html>';
        exit;
    }
}

// Quick stats for the header bar
$pendingOrders    = DB::count("SELECT COUNT(*) FROM orders WHERE status='pending'");
$pendingMerchants = DB::count("SELECT COUNT(*) FROM shops WHERE status='pending'");
$pendingPayouts   = DB::count("SELECT COUNT(*) FROM payouts WHERE status='pending'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle) ?> — African Attire Admin</title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <style>
    /* ── Admin header override ───────────────────────────────── */
    .admin-header{
      background: linear-gradient(135deg, #0D47A1 0%, #1565C0 60%, #1B5E20 100%);
      position:sticky;top:0;z-index:600;
      box-shadow:0 2px 12px rgba(0,0,0,.25);
    }
    .admin-header-top{
      display:flex;align-items:center;gap:16px;
      max-width:1400px;margin:0 auto;padding:10px 20px;
    }
    /* Logo block */
    .ah-logo{
      display:flex;align-items:center;gap:12px;
      text-decoration:none;flex-shrink:0;
    }
    .ah-logo-img{
      width:80px;height:80px;
      object-fit:contain;
      border-radius:10px;
      background:#fff;
      padding:4px;
      box-shadow:0 3px 10px rgba(0,0,0,.2);
      flex-shrink:0;
    }
    .ah-logo-text{}
    .ah-logo-name{
      font-family:'Sora','Plus Jakarta Sans',system-ui,sans-serif;
      font-size:1.15rem;font-weight:800;color:#fff;
      display:block;line-height:1.1;letter-spacing:-.01em;
    }
    .ah-logo-sub{
      font-size:.7rem;font-weight:600;
      color:rgba(255,255,255,.6);
      text-transform:uppercase;letter-spacing:.1em;
      display:block;margin-top:2px;
    }
    /* Divider */
    .ah-divider{
      width:1px;height:48px;
      background:rgba(255,255,255,.2);
      flex-shrink:0;
    }
    /* Admin badge */
    .ah-badge{
      background:rgba(255,255,255,.12);
      border:1px solid rgba(255,255,255,.2);
      border-radius:8px;padding:6px 14px;
    }
    .ah-badge-label{
      font-size:.65rem;font-weight:700;
      text-transform:uppercase;letter-spacing:.1em;
      color:rgba(255,255,255,.55);display:block;
    }
    .ah-badge-value{
      font-size:.88rem;font-weight:700;color:#fff;
      display:flex;align-items:center;gap:5px;
    }
    /* Quick-action alerts */
    .ah-alerts{
      display:flex;gap:8px;flex-wrap:wrap;
      margin-left:auto;
    }
    .ah-alert-chip{
      display:flex;align-items:center;gap:5px;
      padding:5px 11px;border-radius:999px;
      font-size:.74rem;font-weight:700;
      text-decoration:none;transition:opacity .15s;
      white-space:nowrap;
    }
    .ah-alert-chip:hover{opacity:.82}
    .ah-alert-chip.orange{background:var(--ju);    color:#fff}
    .ah-alert-chip.blue  {background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3)}
    .ah-alert-chip.green {background:#2E7D32;      color:#fff}
    .ah-alert-chip .ah-dot{
      width:18px;height:18px;border-radius:50%;
      background:rgba(0,0,0,.2);
      display:flex;align-items:center;justify-content:center;
      font-size:.64rem;font-weight:800;
    }
    /* Right actions */
    .ah-actions{
      display:flex;align-items:center;gap:8px;
      flex-shrink:0;
    }
    .ah-btn{
      display:flex;align-items:center;gap:5px;
      padding:7px 13px;border-radius:6px;
      font-size:.78rem;font-weight:600;
      text-decoration:none;transition:var(--t);
      white-space:nowrap;border:none;cursor:pointer;
      font-family:inherit;
    }
    .ah-btn-ghost{
      background:rgba(255,255,255,.1);color:#fff;
      border:1px solid rgba(255,255,255,.22);
    }
    .ah-btn-ghost:hover{background:rgba(255,255,255,.2);color:#fff}
    .ah-btn-danger{background:#C62828;color:#fff}
    .ah-btn-danger:hover{background:#B71C1C;color:#fff}
    /* Bottom nav bar */
    .admin-nav-bar{
      background:rgba(0,0,0,.2);
      border-top:1px solid rgba(255,255,255,.08);
    }
    .admin-nav-inner{
      display:flex;overflow-x:auto;scrollbar-width:none;
      max-width:1400px;margin:0 auto;padding:0 20px;
    }
    .admin-nav-inner::-webkit-scrollbar{display:none}
    .admin-nav-link{
      display:flex;align-items:center;gap:5px;
      padding:8px 14px;
      font-size:.77rem;font-weight:600;
      color:rgba(255,255,255,.65);
      text-decoration:none;white-space:nowrap;
      border-bottom:2px solid transparent;
      transition:color .15s,border-color .15s;
      flex-shrink:0;
    }
    .admin-nav-link:hover{color:#fff;border-bottom-color:rgba(255,255,255,.4)}
    .admin-nav-link.active{color:#fff;border-bottom-color:var(--ju)}
    .admin-nav-link .anl-ico{font-size:.88rem}
    /* Mobile collapse helpers */
    @media(max-width:700px){
      .ah-divider,.ah-badge,.ah-alerts{display:none}
      .admin-header-top{padding:8px 12px;gap:10px}
      .ah-logo-img{width:52px;height:52px}
      .ah-logo-name{font-size:.95rem}
    }
    @media(max-width:480px){
      .ah-logo-img{width:44px;height:44px;border-radius:8px}
    }
  </style>
  <script>const BASE_URL='<?= BASE_URL ?>';</script>
</head>
<body style="background:var(--bg)">

<!-- ══ ADMIN HEADER ═══════════════════════════════════════════ -->
<header class="admin-header">

  <!-- Top row: logo · divider · badge · alerts · actions -->
  <div class="admin-header-top">

    <!-- Logo + name -->
    <a href="<?= BASE_URL ?>/admin/" class="ah-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png"
           class="ah-logo-img" alt="African Attire">
      <div class="ah-logo-text">
        <span class="ah-logo-name">African Attire</span>
        <span class="ah-logo-sub">Admin Console · v<?= VERSION ?></span>
      </div>
    </a>

    <div class="ah-divider"></div>

    <!-- Admin badge -->
    <div class="ah-badge">
      <span class="ah-badge-label">Signed in as</span>
      <span class="ah-badge-value">
        <span style="font-size:.85rem">👤</span>
        <?= e(Auth::user()['name']) ?>
        <span style="font-size:.65rem;background:var(--ju);color:#fff;padding:1px 6px;border-radius:999px;font-weight:700;letter-spacing:.04em">ADMIN</span>
      </span>
    </div>

    <!-- Quick-action alert chips -->
    <div class="ah-alerts">
      <?php if ($pendingOrders > 0): ?>
      <a href="<?= BASE_URL ?>/admin/orders.php?status=pending" class="ah-alert-chip orange">
        <span class="ah-dot"><?= min($pendingOrders, 99) ?></span>
        Pending Orders
      </a>
      <?php endif; ?>

      <?php if ($pendingMerchants > 0): ?>
      <a href="<?= BASE_URL ?>/admin/merchants.php?status=pending" class="ah-alert-chip green">
        <span class="ah-dot"><?= min($pendingMerchants, 99) ?></span>
        Pending Merchants
      </a>
      <?php endif; ?>

      <?php if ($pendingPayouts > 0): ?>
      <a href="<?= BASE_URL ?>/admin/payouts.php?status=pending" class="ah-alert-chip blue">
        <span class="ah-dot"><?= min($pendingPayouts, 99) ?></span>
        Pending Payouts
      </a>
      <?php endif; ?>

      <?php if (!$pendingOrders && !$pendingMerchants && !$pendingPayouts): ?>
      <span style="font-size:.76rem;color:rgba(255,255,255,.45);padding:5px 0">
        ✓ No pending actions
      </span>
      <?php endif; ?>
    </div>

    <!-- Right actions -->
    <div class="ah-actions">
      <a href="<?= BASE_URL ?>/" target="_blank" class="ah-btn ah-btn-ghost">
        🌐 View Store
      </a>
      <a href="<?= BASE_URL ?>/customer/logout.php" class="ah-btn ah-btn-danger">
        Sign Out
      </a>
    </div>

  </div>

  <!-- Bottom nav row: quick links to all admin sections -->
  <div class="admin-nav-bar">
    <div class="admin-nav-inner">
      <a href="<?= BASE_URL ?>/admin/"
         class="admin-nav-link <?= $activeNav==='overview'  ?'active':'' ?>">
        <span class="anl-ico">📊</span> Dashboard
      </a>
      <?php if(canAccess('merchants')): ?>
      <a href="<?= BASE_URL ?>/admin/agents.php"
         class="admin-nav-link <?= $activeNav==='agents'?'active':'' ?>">
        <span class="anl-ico">🔗</span> Agents
      </a>
      <a href="<?= BASE_URL ?>/admin/merchants.php"
         class="admin-nav-link <?= $activeNav==='merchants'  ?'active':'' ?>">
        <span class="anl-ico">🏪</span> Merchants
        <?php if($pendingMerchants>0):?>
          <span style="background:var(--green-lt);color:#fff;font-size:.6rem;font-weight:800;padding:1px 5px;border-radius:999px;margin-left:2px"><?=$pendingMerchants?></span>
        <?php endif;?>
      </a>
      <?php endif; ?>
      <?php if(canAccess('customers')): ?>
      <a href="<?= BASE_URL ?>/admin/users.php"
         class="admin-nav-link <?= $activeNav==='users'      ?'active':'' ?>">
        <span class="anl-ico">👥</span> Customers
      </a>
      <?php endif; ?>
      <?php if(canAccess('products')): ?>
      <a href="<?= BASE_URL ?>/admin/products.php"
         class="admin-nav-link <?= $activeNav==='products'   ?'active':'' ?>">
        <span class="anl-ico">🏷</span> Products
      </a>
      <?php endif; ?>
      <?php if(canAccess('categories')): ?>
      <a href="<?= BASE_URL ?>/admin/categories.php"
         class="admin-nav-link <?= $activeNav==='categories' ?'active':'' ?>">
        <span class="anl-ico">🗂</span> Categories
      </a>
      <?php endif; ?>
      <?php if(canAccess('orders')): ?>
      <a href="<?= BASE_URL ?>/admin/orders.php"
         class="admin-nav-link <?= $activeNav==='orders'     ?'active':'' ?>">
        <span class="anl-ico">📦</span> Orders
        <?php if($pendingOrders>0):?>
          <span style="background:var(--ju);color:#fff;font-size:.6rem;font-weight:800;padding:1px 5px;border-radius:999px;margin-left:2px"><?=$pendingOrders?></span>
        <?php endif;?>
      </a>
      <?php endif; ?>
      <?php if(canAccess('orders')): ?>
      <a href="<?= BASE_URL ?>/admin/disputes.php"
         class="admin-nav-link <?= $activeNav==='disputes'   ?'active':'' ?>">
        <span class="anl-ico">⚖️</span> Disputes
      </a>
      <?php endif; ?>
      <?php if(canAccess('payouts')): ?>
      <a href="<?= BASE_URL ?>/admin/payouts.php"
         class="admin-nav-link <?= $activeNav==='payouts'    ?'active':'' ?>">
        <span class="anl-ico">💰</span> Payouts
        <?php if($pendingPayouts>0):?>
          <span style="background:#fff;color:var(--blue);font-size:.6rem;font-weight:800;padding:1px 5px;border-radius:999px;margin-left:2px"><?=$pendingPayouts?></span>
        <?php endif;?>
      </a>
      <?php endif; ?>
      <?php if(canAccess('reports')): ?>
      <a href="<?= BASE_URL ?>/admin/reports.php"
         class="admin-nav-link <?= $activeNav==='reports'    ?'active':'' ?>">
        <span class="anl-ico">📈</span> Reports
      </a>
      <?php endif; ?>
      <?php if(canAccess('banners')): ?>
      <a href="<?= BASE_URL ?>/admin/banners.php"
         class="admin-nav-link <?= $activeNav==='banners'    ?'active':'' ?>">
        <span class="anl-ico">🖼</span> Banners
      </a>
      <?php endif; ?>
      <?php if(canAccess('settings')): ?>
      <a href="<?= BASE_URL ?>/admin/settings.php"
         class="admin-nav-link <?= $activeNav==='settings'   ?'active':'' ?>">
        <span class="anl-ico">⚙️</span> Settings
      </a>
      <?php endif; ?>
      <?php if(canAccess('logistics')): ?>
      <a href="<?= BASE_URL ?>/admin/logistics.php"
         class="admin-nav-link <?= $activeNav==='logistics'?'active':'' ?>">
        <span class="anl-ico">🚚</span> Logistics
      </a>
      <?php endif; ?>
      <?php if(canAccess('markup')): ?>
      <a href="<?= BASE_URL ?>/admin/commission.php"
         class="admin-nav-link <?= $activeNav==='commission'?'active':'' ?>">
        <span class="anl-ico">📊</span> Markup
      </a>
      <?php endif; ?>
      <?php if(canAccess('discounts')): ?>
      <a href="<?= BASE_URL ?>/admin/discounts.php"
         class="admin-nav-link <?= $activeNav==='discounts'?'active':'' ?>">
        <span class="anl-ico">🏷</span> Discounts
      </a>
      <?php endif; ?>
      <?php if(canAccess('audit_log')): ?>
      <a href="<?= BASE_URL ?>/admin/audit-log.php"
         class="admin-nav-link <?= $activeNav==='audit-log'?'active':'' ?>">
        <span class="anl-ico">📋</span> Audit Log
      </a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/admin/notifications.php"
         class="admin-nav-link <?= $activeNav==='notifications'?'active':'' ?>">
        <span class="anl-ico">🔔</span> Notifications
      </a>
    </div>
  </div>

</header>

<div id="toast-host"></div>
<?php foreach(getFlashes() as $f): ?>
<div style="max-width:1400px;margin:.6rem auto;padding:0 20px">
  <div class="alert alert-<?= $f['type']==='error'?'danger':e($f['type']) ?>">
    <?= e($f['msg']) ?>
  </div>
</div>
<?php endforeach; ?>

<!-- ── DASH LAYOUT ── -->
<div class="dash-layout">
<aside class="dash-sidebar">
  <div class="sidebar-card">
    <div style="background:linear-gradient(135deg,#0D47A1,#1B5E20);padding:14px">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
        <img src="<?= BASE_URL ?>/assets/images/logo.png"
             style="width:36px;height:36px;border-radius:6px;background:#fff;object-fit:contain;padding:2px;flex-shrink:0">
        <div>
          <div style="font-weight:700;font-size:.84rem;color:#fff">
            <?= e(Auth::user()['name']) ?>
          </div>
          <div style="font-size:.68rem;color:rgba(255,255,255,.55)">
            Platform Administrator
          </div>
        </div>
      </div>
      <?php if ($pendingOrders || $pendingMerchants || $pendingPayouts): ?>
      <div style="background:rgba(255,255,255,.1);border-radius:6px;padding:7px 10px;font-size:.72rem;color:rgba(255,255,255,.75)">
        <?php if($pendingOrders):    ?><div>📦 <?= $pendingOrders ?> pending order<?= $pendingOrders!==1?'s':'' ?></div><?php endif; ?>
        <?php if($pendingMerchants):?><div>🏪 <?= $pendingMerchants ?> merchant<?= $pendingMerchants!==1?'s':'' ?> awaiting approval</div><?php endif; ?>
        <?php if($pendingPayouts):  ?><div>💰 <?= $pendingPayouts ?> payout<?= $pendingPayouts!==1?'s':'' ?> pending</div><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <div style="padding:.4rem 0">
      <div class="sidebar-section-label">Overview</div>
      <a href="<?=BASE_URL?>/admin/"             class="sidebar-link <?=$activeNav==='overview'     ?'active':''?>"><span class="sidebar-icon">📊</span> Dashboard</a>
      <?php if(canAccess('reports')): ?>
      <a href="<?=BASE_URL?>/admin/reports.php"  class="sidebar-link <?=$activeNav==='reports'      ?'active':''?>"><span class="sidebar-icon">📈</span> Reports</a>
      <?php endif; ?>

      <div class="sidebar-section-label">Management</div>
      <?php if(canAccess('merchants')): ?>
      <a href="<?=BASE_URL?>/admin/agents.php"        class="sidebar-link <?=$activeNav==='agents'        ?'active':''?>"><span class="sidebar-icon">🔗</span> Agents</a>
      <a href="<?=BASE_URL?>/admin/merchants.php"    class="sidebar-link <?=$activeNav==='merchants'    ?'active':''?>"><span class="sidebar-icon">🏪</span> Merchants</a>
      <?php endif; ?>
      <?php if(canAccess('customers')): ?>
      <a href="<?=BASE_URL?>/admin/users.php"         class="sidebar-link <?=$activeNav==='users'         ?'active':''?>"><span class="sidebar-icon">👥</span> Customers</a>
      <?php endif; ?>
      <?php if(canAccess('products')): ?>
      <a href="<?=BASE_URL?>/admin/products.php"      class="sidebar-link <?=$activeNav==='products'      ?'active':''?>"><span class="sidebar-icon">🏷</span> Products</a>
      <?php endif; ?>
      <?php if(canAccess('categories')): ?>
      <a href="<?=BASE_URL?>/admin/categories.php"   class="sidebar-link <?=$activeNav==='categories'   ?'active':''?>"><span class="sidebar-icon">🗂</span> Categories</a>
      <?php endif; ?>
      <?php if(canAccess('orders')): ?>
      <a href="<?=BASE_URL?>/admin/orders.php"        class="sidebar-link <?=$activeNav==='orders'        ?'active':''?>"><span class="sidebar-icon">📦</span> Orders</a>
      <a href="<?=BASE_URL?>/admin/disputes.php"      class="sidebar-link <?=$activeNav==='disputes'      ?'active':''?>"><span class="sidebar-icon">⚖️</span> Disputes</a>
      <?php endif; ?>

      <div class="sidebar-section-label">Finance</div>
      <?php if(canAccess('payouts')): ?>
      <a href="<?=BASE_URL?>/admin/payouts.php"    class="sidebar-link <?=$activeNav==='payouts'    ?'active':''?>"><span class="sidebar-icon">💰</span> Payouts</a>
      <?php endif; ?>
      <?php if(canAccess('markup')): ?>
      <a href="<?=BASE_URL?>/admin/commission.php" class="sidebar-link <?=$activeNav==='commission'  ?'active':''?>"><span class="sidebar-icon">📊</span> Markup</a>
      <?php endif; ?>
      <?php if(canAccess('discounts')): ?>
      <a href="<?=BASE_URL?>/admin/discounts.php"  class="sidebar-link <?=$activeNav==='discounts'   ?'active':''?>"><span class="sidebar-icon">🏷</span> Discounts</a>
      <?php endif; ?>

      <div class="sidebar-section-label">Content</div>
      <?php if(canAccess('logistics')): ?>
      <a href="<?=BASE_URL?>/admin/logistics.php"      class="sidebar-link <?=$activeNav==='logistics'      ?'active':''?>"><span class="sidebar-icon">🚚</span> Logistics</a>
      <a href="<?=BASE_URL?>/admin/logistics-payouts.php" class="sidebar-link <?=$activeNav==='logistics-payouts'?'active':''?>"><span class="sidebar-icon">💸</span> Logistics Payouts</a>
      <?php endif; ?>
      <?php if(canAccess('banners')): ?>
      <a href="<?=BASE_URL?>/admin/banners.php"        class="sidebar-link <?=$activeNav==='banners'        ?'active':''?>"><span class="sidebar-icon">🖼</span> Banners</a>
      <?php endif; ?>
      <?php if(canAccess('settings')): ?>
      <a href="<?=BASE_URL?>/admin/settings.php"       class="sidebar-link <?=$activeNav==='settings'       ?'active':''?>"><span class="sidebar-icon">⚙️</span> Settings</a>
      <?php endif; ?>

      <div class="sidebar-section-label">Security</div>
      <?php if(canAccess('audit_log')): ?>
      <a href="<?=BASE_URL?>/admin/audit-log.php"    class="sidebar-link <?=$activeNav==='audit-log'    ?'active':''?>"><span class="sidebar-icon">📋</span> Audit Log</a>
      <a href="<?=BASE_URL?>/admin/admin-users.php"  class="sidebar-link <?=$activeNav==='admin-users'  ?'active':''?>"><span class="sidebar-icon">🔐</span> Admin Users</a>
      <?php endif; ?>
      <a href="<?=BASE_URL?>/admin/notifications.php"  class="sidebar-link <?=$activeNav==='notifications'  ?'active':''?>"><span class="sidebar-icon">🔔</span> Notifications</a>
    </div>
  </div>
</aside>
<div class="dash-main">
