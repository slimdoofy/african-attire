<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Analytics'; $activeNav = 'analytics';
include __DIR__ . '/../includes/header_merchant.php';

$sid = $_shop['id'];
$period = (int)($_GET['period'] ?? 6); // months

// Monthly revenue + orders — last N months
$chartData = [];
$maxRev    = 1;
$maxOrd    = 1;
for ($i = $period-1; $i >= 0; $i--) {
    $m   = date('Y-m', strtotime("-{$i} months"));
    $lbl = date("M 'y", strtotime("-{$i} months"));
    $rev = (int)DB::count(
        "SELECT COALESCE(SUM(oi.price*oi.quantity),0)
         FROM order_items oi JOIN orders o ON o.id=oi.order_id
         WHERE oi.shop_id=? AND DATE_FORMAT(o.created_at,'%Y-%m')=?
         AND o.status != 'cancelled'", [$sid, $m]);
    $ord = (int)DB::count(
        "SELECT COUNT(DISTINCT o.id)
         FROM order_items oi JOIN orders o ON o.id=oi.order_id
         WHERE oi.shop_id=? AND DATE_FORMAT(o.created_at,'%Y-%m')=?", [$sid, $m]);
    $chartData[] = ['l'=>$lbl,'rev'=>$rev,'ord'=>$ord];
    if ($rev > $maxRev) $maxRev = $rev;
    if ($ord > $maxOrd) $maxOrd = $ord;
}

// KPIs
$totalRev    = (float)$_shop['total_revenue'];
$totalOrders = (int)DB::count("SELECT COUNT(DISTINCT o.id) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.shop_id=?",[$sid]);
$totalProds  = (int)DB::count("SELECT COUNT(*) FROM products WHERE shop_id=? AND status='approved'",[$sid]);
$avgOrder    = $totalOrders > 0 ? $totalRev / $totalOrders : 0;

// Top products
$topProducts = DB::fetchAll(
    "SELECT p.name, c.name cat_name, c.icon cat_icon,
            SUM(oi.quantity) sold,
            SUM(oi.price*oi.quantity) revenue
     FROM order_items oi
     JOIN products p ON p.id=oi.product_id
     JOIN categories c ON c.id=p.category_id
     WHERE oi.shop_id=? GROUP BY p.id ORDER BY sold DESC LIMIT 10", [$sid]);

// Category breakdown
$catBreakdown = DB::fetchAll(
    "SELECT c.name, c.icon,
            SUM(oi.quantity) sold,
            SUM(oi.price*oi.quantity) revenue
     FROM order_items oi
     JOIN products p ON p.id=oi.product_id
     JOIN categories c ON c.id=p.category_id
     WHERE oi.shop_id=? GROUP BY c.id ORDER BY revenue DESC", [$sid]);

$maxCatRev = max(1, array_reduce($catBreakdown, function($c,$r){ return max($c,(float)$r['revenue']); }, 0));

$hasData = $totalOrders > 0;
?>

<div class="m-page-head">
  <div>
    <div class="m-page-title">📈 Analytics</div>
    <div class="m-page-sub">Your shop performance overview</div>
  </div>
  <div style="display:flex;gap:6px">
    <?php foreach ([3=>'3M',6=>'6M',12=>'12M'] as $v=>$l): ?>
    <a href="?period=<?= $v ?>"
       class="btn btn-sm <?= $period===$v?'btn-ju':'btn-ghost'?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
</div>

<!-- KPI grid -->
<div class="m-stat-grid">
  <div class="m-stat accent-orange">
    <div class="m-stat-label">Total Revenue</div>
    <div class="m-stat-value"><?= money($totalRev) ?></div>
    <div class="m-stat-hint">All-time gross sales</div>
  </div>
  <div class="m-stat accent-blue">
    <div class="m-stat-label">Total Orders</div>
    <div class="m-stat-value"><?= number_format($totalOrders) ?></div>
    <div class="m-stat-hint">Including all statuses</div>
  </div>
  <div class="m-stat accent-green">
    <div class="m-stat-label">Average Order Value</div>
    <div class="m-stat-value"><?= money($avgOrder) ?></div>
    <div class="m-stat-hint">Revenue ÷ orders</div>
  </div>
  <div class="m-stat accent-gold">
    <div class="m-stat-label">Live Products</div>
    <div class="m-stat-value"><?= number_format($totalProds) ?></div>
    <div class="m-stat-hint"><a href="<?= BASE_URL ?>/merchant/products.php" style="color:var(--blue)">Manage →</a></div>
  </div>
</div>

<?php if (!$hasData): ?>
<div class="empty-state card" style="padding:56px 24px">
  <span class="empty-icon">📈</span>
  <p style="font-weight:600;margin-bottom:6px">No sales data yet</p>
  <p style="font-size:.84rem;margin-bottom:16px">Start by adding products and making your first sale.</p>
  <a href="<?= BASE_URL ?>/merchant/products.php" class="btn btn-ju btn-sm">Add Products →</a>
</div>
<?php else: ?>

<!-- Charts row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">

  <!-- Revenue chart -->
  <div class="card">
    <div class="card-head">
      <div class="card-title">💰 Monthly Revenue</div>
      <span style="font-size:.74rem;color:var(--text-muted)">Last <?= $period ?> months</span>
    </div>
    <div style="overflow-x:auto">
      <div style="display:flex;align-items:flex-end;gap:5px;height:130px;min-width:<?= max(240,$period*42) ?>px;padding:4px 0">
        <?php foreach ($chartData as $d):
          $pct = $maxRev > 0 ? round($d['rev']/$maxRev*100) : 0;
          $has = $d['rev'] > 0;
        ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px">
          <?php if ($has): ?>
          <div style="font-size:.55rem;color:var(--text-muted);white-space:nowrap">
            <?= $pct >= 20 ? '₦'.number_format($d['rev']/1000).'k' : '' ?>
          </div>
          <?php endif; ?>
          <div style="width:100%;border-radius:4px 4px 0 0;min-height:3px;
               background:<?=$has?'var(--blue)':'var(--border-lt)'?>;
               height:<?=max(3,$pct)?>%;transition:height .3s"
               title="<?=$d['l']?>: <?=money($d['rev'])?>">
          </div>
          <div style="font-size:.56rem;color:var(--text-muted);white-space:nowrap;text-align:center">
            <?= $d['l'] ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Orders chart -->
  <div class="card">
    <div class="card-head">
      <div class="card-title">📦 Monthly Orders</div>
      <span style="font-size:.74rem;color:var(--text-muted)">Last <?= $period ?> months</span>
    </div>
    <div style="overflow-x:auto">
      <div style="display:flex;align-items:flex-end;gap:5px;height:130px;min-width:<?= max(240,$period*42) ?>px;padding:4px 0">
        <?php foreach ($chartData as $d):
          $pct = $maxOrd > 0 ? round($d['ord']/$maxOrd*100) : 0;
          $has = $d['ord'] > 0;
        ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px">
          <?php if ($has): ?>
          <div style="font-size:.55rem;color:var(--text-muted)"><?= $d['ord'] ?></div>
          <?php endif; ?>
          <div style="width:100%;border-radius:4px 4px 0 0;min-height:3px;
               background:<?=$has?'var(--green)':'var(--border-lt)'?>;
               height:<?=max(3,$pct)?>%;transition:height .3s"
               title="<?=$d['l']?>: <?=$d['ord']?> orders">
          </div>
          <div style="font-size:.56rem;color:var(--text-muted);white-space:nowrap;text-align:center">
            <?= $d['l'] ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<!-- Top products + Category breakdown -->
<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:14px;margin-bottom:14px">

  <!-- Top products table -->
  <div class="card">
    <div class="card-head">
      <div class="card-title">🏆 Top Products by Sales</div>
    </div>
    <?php if (empty($topProducts)): ?>
    <div class="empty-state" style="padding:24px"><p>No sales yet</p></div>
    <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>#</th><th>Product</th><th>Category</th><th>Sold</th><th>Revenue</th></tr>
        </thead>
        <tbody>
          <?php foreach ($topProducts as $i=>$p): ?>
          <tr>
            <td style="font-weight:700;color:var(--text-muted);width:32px"><?= $i+1 ?></td>
            <td style="font-weight:600;font-size:.84rem;max-width:180px">
              <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($p['name']) ?></div>
            </td>
            <td><span class="badge badge-muted"><?= $p['cat_icon'] ?> <?= e($p['cat_name']) ?></span></td>
            <td><span class="badge badge-info"><?= number_format($p['sold']) ?></span></td>
            <td style="font-weight:700;color:var(--blue)"><?= money($p['revenue']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Category breakdown -->
  <div class="card">
    <div class="card-head">
      <div class="card-title">🎨 Revenue by Category</div>
    </div>
    <?php if (empty($catBreakdown)): ?>
    <div class="empty-state" style="padding:24px"><p>No data yet</p></div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:12px">
      <?php foreach ($catBreakdown as $cat):
        $pct = $maxCatRev > 0 ? round((float)$cat['revenue']/$maxCatRev*100) : 0;
      ?>
      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px">
          <span style="font-size:.83rem;font-weight:600;color:var(--black)">
            <?= $cat['icon'] ?> <?= e($cat['name']) ?>
          </span>
          <div style="text-align:right">
            <span style="font-size:.83rem;font-weight:700;color:var(--blue)"><?= money($cat['revenue']) ?></span>
            <span style="font-size:.7rem;color:var(--text-muted);margin-left:6px"><?= number_format($cat['sold']) ?> sold</span>
          </div>
        </div>
        <div style="height:7px;background:var(--border-lt);border-radius:4px;overflow:hidden">
          <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,var(--blue),var(--green));border-radius:4px;transition:width .4s"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<?php endif; // hasData ?>

<?php include __DIR__ . '/../includes/footer_merchant.php'; ?>
