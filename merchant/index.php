<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Dashboard'; $activeNav = 'overview';
include __DIR__ . '/../includes/header_merchant.php';

$sid = $_shop['id'];

// Available balance = pure cost of goods earned on paid orders minus already withdrawn
// Never touches commission, markup, or delivery — only what the merchant is owed
$totalCostEarned = (float)DB::count(
    "SELECT COALESCE(SUM(oi.cost_price * oi.quantity), 0)
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE oi.shop_id = ?
       AND o.payment_status = 'paid'
       AND oi.cost_price IS NOT NULL
       AND oi.cost_price > 0",
    [$sid]
);
$withdrawn = (float)$_shop['total_withdrawn'];
$available = max(0, $totalCostEarned - $withdrawn);

$ordersCount = DB::count('SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi WHERE oi.shop_id=?',[$sid]);
$prodCount   = DB::count('SELECT COUNT(*) FROM products WHERE shop_id=? AND status="approved"',[$sid]);
$pendOrd     = DB::count('SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.shop_id=? AND o.status="pending"',[$sid]);

$chartData = [];
$maxChart  = 1;
for ($i=6;$i>=0;$i--) {
    $m   = date('Y-m', strtotime("-$i months"));
    $lbl = date('M', strtotime("-$i months"));
    $v   = (int)DB::count("SELECT COALESCE(SUM(oi.price*oi.quantity),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.shop_id=? AND DATE_FORMAT(o.created_at,'%Y-%m')=? AND o.payment_status='paid'",[$sid,$m]);
    $chartData[] = ['l'=>$lbl,'v'=>$v];
    if ($v > $maxChart) $maxChart = $v;
}

$bestSellers  = DB::fetchAll("SELECT p.name, SUM(oi.quantity) sold, SUM(oi.price*oi.quantity) revenue FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.shop_id=? GROUP BY p.id ORDER BY sold DESC LIMIT 5",[$sid]);
$recentOrders = DB::fetchAll("SELECT o.order_number, o.created_at, o.status, o.total_amount, COALESCE(u.name,o.guest_name) cust_name FROM orders o JOIN order_items oi ON oi.order_id=o.id LEFT JOIN users u ON u.id=o.user_id WHERE oi.shop_id=? GROUP BY o.id ORDER BY o.created_at DESC LIMIT 8",[$sid]);
$lowStock     = DB::fetchAll("SELECT id,name,quantity FROM products WHERE shop_id=? AND quantity<=5 AND quantity>0 AND status='approved' ORDER BY quantity ASC LIMIT 5",[$sid]);
?>

<!-- Page header -->
<div class="m-page-head">
  <div>
    <div class="m-page-title">📊 Dashboard</div>
    <div class="m-page-sub">Welcome back, <?= e(Auth::user()['name']) ?> · <?= date('l, F j') ?></div>
  </div>
  <a href="<?= BASE_URL ?>/merchant/products.php?action=add" class="btn btn-ju btn-sm">+ Add Product</a>
</div>

<!-- KPI Stats -->
<div class="m-stat-grid">
  <div class="m-stat accent-orange">
    <div class="m-stat-label">Cost of Goods Earned</div>
    <div class="m-stat-value"><?= money($totalCostEarned) ?></div>
    <div class="m-stat-hint">Your cost price × qty on paid orders</div>
  </div>
  <div class="m-stat accent-blue">
    <div class="m-stat-label">Total Withdrawn</div>
    <div class="m-stat-value"><?= money($withdrawn) ?></div>
    <div class="m-stat-hint">Paid to your bank account</div>
  </div>
  <div class="m-stat accent-green">
    <div class="m-stat-label">Available Balance</div>
    <div class="m-stat-value" style="color:var(--green)"><?= money($available) ?></div>
    <div class="m-stat-hint"><a href="<?= BASE_URL ?>/merchant/payouts.php" style="color:var(--green)">Request payout →</a></div>
  </div>
  <div class="m-stat accent-gold">
    <div class="m-stat-label">Total Orders</div>
    <div class="m-stat-value"><?= number_format($ordersCount) ?></div>
    <div class="m-stat-hint"><?= $pendOrd ?> pending action<?= $pendOrd!==1?'s':''?></div>
  </div>
  <div class="m-stat">
    <div class="m-stat-label">Live Products</div>
    <div class="m-stat-value"><?= number_format($prodCount) ?></div>
    <div class="m-stat-hint"><a href="<?= BASE_URL ?>/merchant/products.php" style="color:var(--blue)">Manage →</a></div>
  </div>
</div>

<!-- Profile incomplete nudge -->
<?php if ($_pct < 100): ?>
<div class="alert alert-warning" style="margin-bottom:14px">
  📝 Your shop profile is <?= $_pct ?>% complete.
  <a href="<?= BASE_URL ?>/merchant/shop-setup.php" style="font-weight:700;color:var(--ju-dk);margin-left:6px">
    Complete it to attract more buyers →
  </a>
</div>
<?php endif; ?>

<!-- Pending orders alert -->
<?php if ($pendOrd > 0): ?>
<div class="alert alert-info" style="margin-bottom:14px">
  📦 You have <strong><?= $pendOrd ?> pending order<?= $pendOrd!==1?'s':''?></strong> waiting to be processed.
  <a href="<?= BASE_URL ?>/merchant/orders.php?status=pending" style="font-weight:700;margin-left:6px">View now →</a>
</div>
<?php endif; ?>

<!-- Chart + Best Sellers -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
  <div class="card">
    <div class="card-head">
      <div class="card-title">📈 Monthly Revenue</div>
      <span style="font-size:.74rem;color:var(--text-muted)">Last 7 months</span>
    </div>
    <?php if (array_sum(array_column($chartData,'v')) === 0): ?>
    <div class="empty-state" style="padding:24px"><span class="empty-icon" style="font-size:1.8rem">📈</span><p>No revenue data yet</p></div>
    <?php else: ?>
    <div style="display:flex;align-items:flex-end;gap:4px;height:120px;padding:4px 0">
      <?php foreach ($chartData as $d):
        $pct = $maxChart > 0 ? round($d['v']/$maxChart*100) : 0;
        $hasVal = $d['v'] > 0;
      ?>
      <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px">
        <div style="width:100%;border-radius:4px 4px 0 0;background:<?=$hasVal?'var(--blue)':'var(--border-lt)'?>;height:<?=max(3,$pct)?>%;min-height:3px" title="<?=$d['l']?>: <?=money($d['v'])?>"></div>
        <div style="font-size:.58rem;color:var(--text-muted)"><?=$d['l']?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-head">
      <div class="card-title">🏆 Best Sellers</div>
      <a href="<?= BASE_URL ?>/merchant/analytics.php" class="btn btn-ghost btn-sm">Full report →</a>
    </div>
    <?php if (empty($bestSellers)): ?>
    <div class="empty-state" style="padding:24px"><span class="empty-icon" style="font-size:1.8rem">🏷</span><p>No sales yet</p></div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:8px">
      <?php foreach ($bestSellers as $i=>$bs): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid var(--border-lt)">
        <div style="width:22px;height:22px;border-radius:50%;background:var(--ju-pale);color:var(--ju-dk);font-size:.7rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0"><?= $i+1 ?></div>
        <div style="flex:1;min-width:0;font-size:.83rem;font-weight:500;color:var(--black);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($bs['name']) ?></div>
        <div style="text-align:right;flex-shrink:0">
          <div style="font-size:.8rem;font-weight:700;color:var(--blue)"><?= money($bs['revenue']) ?></div>
          <div style="font-size:.68rem;color:var(--text-muted)"><?= $bs['sold'] ?> sold</div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Recent Orders -->
<div class="card" style="margin-bottom:14px">
  <div class="card-head">
    <div class="card-title">📦 Recent Orders</div>
    <a href="<?= BASE_URL ?>/merchant/orders.php" class="btn btn-ghost btn-sm">All orders →</a>
  </div>
  <?php if (empty($recentOrders)): ?>
  <div class="empty-state" style="padding:32px"><span class="empty-icon">📦</span><p>No orders yet. Start by adding products!</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Order #</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($recentOrders as $o): ?>
        <tr>
          <td style="font-weight:700;color:var(--blue);font-size:.84rem"><?= e($o['order_number']) ?></td>
          <td style="font-size:.84rem"><?= e($o['cust_name'] ?: 'Guest') ?></td>
          <td style="font-weight:600"><?= money($o['total_amount']) ?></td>
          <td><?= statusBadge($o['status']) ?></td>
          <td style="font-size:.78rem;color:var(--text-muted)"><?= date('M j, g:i A', strtotime($o['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Low Stock -->
<?php if (!empty($lowStock)): ?>
<div class="card">
  <div class="card-head">
    <div class="card-title">⚠️ Low Stock Alerts</div>
    <a href="<?= BASE_URL ?>/merchant/inventory.php" class="btn btn-ghost btn-sm">Manage inventory →</a>
  </div>
  <div style="display:flex;flex-direction:column;gap:6px">
    <?php foreach ($lowStock as $ls): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:var(--red-pale);border:1px solid #FFCDD2;border-radius:var(--r-sm)">
      <span style="font-size:.84rem;color:var(--black);font-weight:500"><?= e($ls['name']) ?></span>
      <strong style="color:var(--red);font-size:.86rem"><?= $ls['quantity'] ?> left</strong>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer_merchant.php'; ?>
