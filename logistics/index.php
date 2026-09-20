<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/auth.php';
logRequire();
$la   = logAuth();
$cid  = $la['company_id'];
$pageTitle = 'Dashboard'; $activeNav = 'dash';
include __DIR__ . '/header.php';

$total     = DB::count("SELECT COUNT(*) FROM order_logistics WHERE company_id=?",[$cid]);
$pending   = DB::count("SELECT COUNT(*) FROM order_logistics WHERE company_id=? AND status='assigned'",[$cid]);
$inTransit = DB::count("SELECT COUNT(*) FROM order_logistics WHERE company_id=? AND status IN ('picked_up','in_transit','out_for_delivery')",[$cid]);
$delivered = DB::count("SELECT COUNT(*) FROM order_logistics WHERE company_id=? AND status='delivered'",[$cid]);

$recent = DB::fetchAll(
    "SELECT ol.*, o.order_number, o.created_at order_date,
            COALESCE(u.name,o.guest_name) cust_name, s.shop_name
     FROM order_logistics ol
     JOIN orders o ON o.id=ol.order_id
     JOIN shops s  ON s.id=ol.shop_id
     LEFT JOIN users u ON u.id=o.user_id
     WHERE ol.company_id=?
     ORDER BY ol.assigned_at DESC LIMIT 10", [$cid]
);
?>
<div style="margin-bottom:20px">
  <h1 style="font-family:var(--ff-head);font-size:1.4rem;font-weight:700;color:var(--black);margin-bottom:4px">
    📊 Dashboard
  </h1>
  <p style="font-size:.84rem;color:var(--text-muted)">
    Welcome, <?= e($la['name']) ?> · <?= date('l, F j') ?>
  </p>
</div>

<div class="m-stat-grid" style="margin-bottom:18px">
  <div class="m-stat accent-blue"><div class="m-stat-label">Total Orders</div><div class="m-stat-value"><?= number_format($total) ?></div></div>
  <div class="m-stat accent-gold"><div class="m-stat-label">Awaiting Pickup</div><div class="m-stat-value"><?= number_format($pending) ?></div></div>
  <div class="m-stat accent-orange"><div class="m-stat-label">In Transit</div><div class="m-stat-value"><?= number_format($inTransit) ?></div></div>
  <div class="m-stat accent-green"><div class="m-stat-label">Delivered</div><div class="m-stat-value"><?= number_format($delivered) ?></div></div>
</div>

<?php if ($pending > 0): ?>
<div class="alert alert-info" style="margin-bottom:14px">
  📦 You have <strong><?= $pending ?> order<?= $pending!==1?'s':''?></strong> awaiting pickup.
  <a href="<?= BASE_URL ?>/logistics/orders.php?status=assigned" style="font-weight:700;margin-left:6px">Process now →</a>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-head">
    <div class="card-title">📦 Recent Orders</div>
    <a href="<?= BASE_URL ?>/logistics/orders.php" class="btn btn-ghost btn-sm">All orders →</a>
  </div>
  <?php if (empty($recent)): ?>
  <div class="empty-state" style="padding:32px"><span class="empty-icon">📦</span><p>No orders assigned yet.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Order #</th><th>Merchant</th><th>Customer</th><th>Status</th><th>Assigned</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
        <tr>
          <td><a href="<?= BASE_URL ?>/logistics/order-detail.php?id=<?= $r['id'] ?>" style="font-weight:700;color:var(--blue)"><?= e($r['order_number']) ?></a></td>
          <td style="font-size:.82rem"><?= e($r['shop_name']) ?></td>
          <td style="font-size:.82rem"><?= e($r['cust_name']?:'Guest') ?></td>
          <td><span class="badge badge-info"><?= ucwords(str_replace('_',' ',$r['status'])) ?></span></td>
          <td style="font-size:.76rem;color:var(--text-muted)"><?= date('M j, g:i A',strtotime($r['assigned_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
