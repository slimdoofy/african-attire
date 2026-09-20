<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// If not logged in, redirect to the admin login page
if (!Auth::check() || Auth::role() !== 'admin') {
    redirect(BASE_URL . '/admin/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
}

$pageTitle='Dashboard'; $activeNav='overview';
include __DIR__ . '/../includes/header_admin.php';

// Platform-wide stats
$totalUsers     = DB::count("SELECT COUNT(*) FROM users WHERE role='customer'");
$totalMerchants = DB::count("SELECT COUNT(*) FROM users WHERE role='merchant'");
$pendingShops   = DB::count("SELECT COUNT(*) FROM shops WHERE status='pending'");
$pendingProds   = DB::count("SELECT COUNT(*) FROM products WHERE status='pending'");
$totalOrders    = DB::count("SELECT COUNT(*) FROM orders");
$totalRevenue   = DB::count("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status='paid'");
$gmv            = DB::count("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status NOT IN ('cancelled')");
$commRate       = (float)getSetting('platform_commission',20);
$commRevenue    = $gmv * $commRate / 100;

// Revenue chart last 7 months
$chartData = [];
for($i=6;$i>=0;$i--){
    $m   = date('Y-m',strtotime("-$i months"));
    $lbl = date("M",strtotime("-$i months"));
    $v   = DB::count("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')=? AND status!='cancelled'",[$m]);
    $chartData[] = ['l'=>$lbl,'v'=>(int)$v];
}

// Recent shops pending
$pendShops = DB::fetchAll("SELECT s.*,u.name owner,u.email FROM shops s JOIN users u ON u.id=s.user_id WHERE s.status='pending' ORDER BY s.created_at DESC LIMIT 5");

// Recent orders
$recentOrders = DB::fetchAll("SELECT o.*,u.name cust_name FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 8");
?>

<div class="dash-header">
  <h1 class="dash-title">📊 Platform Dashboard</h1>
  <p class="dash-sub">Overview — <?= date('F j, Y') ?></p>
</div>

<!-- Alerts -->
<?php if ($pendingShops > 0): ?>
<div class="admin-warning">🏪 <strong><?= $pendingShops ?> merchant application<?= $pendingShops>1?'s':'' ?></strong> awaiting review. <a href="<?= BASE_URL ?>/admin/merchants.php?status=pending" style="color:inherit;text-decoration:underline">Review now →</a></div>
<?php endif; ?>
<?php if ($pendingProds > 0): ?>
<div class="admin-warning">🏷 <strong><?= $pendingProds ?> product<?= $pendingProds>1?'s':'' ?></strong> pending moderation. <a href="<?= BASE_URL ?>/admin/products.php?status=pending" style="color:inherit;text-decoration:underline">Moderate now →</a></div>
<?php endif; ?>

<div class="stats-row" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
  <div class="stat-card kpi-card"><div class="stat-label">GMV (All Time)</div><div class="stat-value"><?= money($gmv) ?></div><div class="stat-hint">Gross Merchandise Value</div></div>
  <div class="stat-card kpi-card"><div class="stat-label">Commission Earned</div><div class="stat-value" style="color:var(--success)"><?= money($commRevenue) ?></div><div class="stat-hint"><?= $commRate ?>% of GMV</div></div>
  <div class="stat-card"><div class="stat-label">Total Orders</div><div class="stat-value"><?= $totalOrders ?></div></div>
  <div class="stat-card"><div class="stat-label">Customers</div><div class="stat-value"><?= $totalUsers ?></div></div>
  <div class="stat-card"><div class="stat-label">Active Merchants</div><div class="stat-value"><?= $totalMerchants ?></div><div class="stat-hint"><?= $pendingShops ?> pending</div></div>
  <div class="stat-card"><div class="stat-label">Pending Products</div><div class="stat-value" style="color:var(--warning)"><?= $pendingProds ?></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
  <div class="card">
    <div class="card-head"><div class="card-title">📈 Monthly GMV</div><span class="text-xs text-muted">Last 7 months</span></div>
    <div style="height:140px;display:flex;align-items:flex-end"><div class="bar-chart w-full" id="gmv-chart"></div></div>
  </div>
  <div class="card">
    <div class="card-head"><div class="card-title">🏪 Pending Merchant Applications</div><a href="<?= BASE_URL ?>/admin/merchants.php?status=pending" class="btn btn-ghost btn-sm">All →</a></div>
    <?php if(empty($pendShops)): ?>
      <div class="empty-state" style="padding:1rem"><p>No pending applications</p></div>
    <?php else: ?>
    <?php foreach($pendShops as $s): ?>
    <div class="flex-between" style="margin-bottom:.75rem;align-items:center">
      <div>
        <div class="text-sm fw-600"><?= e($s['shop_name']) ?></div>
        <div class="text-xs text-muted"><?= e($s['owner']) ?> · <?= timeAgo($s['created_at']) ?></div>
      </div>
      <div style="display:flex;gap:.4rem">
        <a href="<?= BASE_URL ?>/admin/merchants.php?approve=<?= $s['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Approve this shop?')">✓</a>
        <a href="<?= BASE_URL ?>/admin/merchants.php?reject=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject this shop?')">✗</a>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-head"><div class="card-title">📦 Recent Orders</div><a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-ghost btn-sm">All orders →</a></div>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th>Order #</th><th>Customer</th><th>Amount</th><th>Payment</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
      <?php foreach ($recentOrders as $o): ?>
      <tr>
        <td class="text-gold fw-600"><?= e($o['order_number']) ?></td>
        <td><?= e($o['cust_name']) ?></td>
        <td class="fw-600"><?= money($o['total_amount']) ?></td>
        <td><?= statusBadge($o['payment_status']) ?></td>
        <td><?= statusBadge($o['status']) ?></td>
        <td class="text-sm text-muted"><?= timeAgo($o['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>renderChart('gmv-chart',<?= json_encode($chartData) ?>));</script>
<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
