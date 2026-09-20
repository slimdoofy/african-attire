<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Reports & Analytics';
$activeNav = 'reports';

// Helper defined first — used in chart labels below
function money_short($v) {
    if ($v >= 1000000) return '₦' . round($v / 1000000, 1) . 'M';
    if ($v >= 1000)    return '₦' . round($v / 1000, 0) . 'k';
    return '₦' . number_format($v);
}

// Excel export (before header output)
if(isset($_GET['export']) && $_GET['export']==='excel'){
    require_once __DIR__ . '/../includes/bootstrap.php'; // already included but harmless
    $custFrom2 = trim($_GET['date_from'] ?? '');
    $custTo2   = trim($_GET['date_to']   ?? '');
    $per2      = (int)($_GET['period'] ?? 30);
    if($custFrom2 && $custTo2){ $dw='created_at >= ? AND created_at <= ?'; $dp=[$custFrom2.' 00:00:00',$custTo2.' 23:59:59']; }
    else { $dw='created_at >= ?'; $dp=[date('Y-m-d',strtotime("-{$per2} days")).' 00:00:00']; }
    $allOrders = DB::fetchAll("SELECT o.order_number,COALESCE(u.name,o.guest_name,'Guest') cust,o.total_amount,o.merchant_amount,o.platform_markup_amount,o.delivery_fee,o.discount_amount,o.status,o.created_at FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.status!='cancelled' AND o.$dw ORDER BY o.created_at DESC",$dp);
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="revenue-report-'.date('Y-m-d').'.xls"');
    echo '<table><thead><tr><th>Order #</th><th>Customer</th><th>Total (NGN)</th><th>Merchant Amount</th><th>Platform Markup</th><th>Delivery Fee</th><th>Discount</th><th>Status</th><th>Date</th></tr></thead><tbody>';
    foreach($allOrders as $r) echo '<tr><td>'.e($r['order_number']).'</td><td>'.e($r['cust']).'</td><td>'.number_format($r['total_amount'],2).'</td><td>'.number_format($r['merchant_amount']??0,2).'</td><td>'.number_format($r['platform_markup_amount']??0,2).'</td><td>'.number_format($r['delivery_fee']??0,2).'</td><td>'.number_format($r['discount_amount']??0,2).'</td><td>'.e($r['status']).'</td><td>'.e($r['created_at']).'</td></tr>';
    echo '</tbody></table>'; exit;
}

include __DIR__ . '/../includes/header_admin.php';

$period   = (int)($_GET['period'] ?? 30);
$custFrom = trim($_GET['date_from'] ?? '');
$custTo   = trim($_GET['date_to']   ?? '');

// Use custom date range if provided, else use period
if ($custFrom && $custTo) {
    $dateFrom = $custFrom . ' 00:00:00';
    $dateTo   = $custTo   . ' 23:59:59';
    $dateWhere= 'created_at >= ? AND created_at <= ?';
    $dateParams= [$dateFrom, $dateTo];
} else {
    $dateFrom = date('Y-m-d', strtotime("-{$period} days")) . ' 00:00:00';
    $dateTo   = date('Y-m-d') . ' 23:59:59';
    $dateWhere = 'created_at >= ?';
    $dateParams= [$dateFrom];
}

// Excel export of revenue summary
if(isset($_GET['export']) && $_GET['export']==='excel'){
    $allOrders = DB::fetchAll("SELECT o.order_number,COALESCE(u.name,o.guest_name,'Guest') cust,o.total_amount,o.merchant_amount,o.platform_markup_amount,o.delivery_fee,o.discount_amount,o.status,o.created_at FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.status!='cancelled' AND o.$dateWhere ORDER BY o.created_at DESC", $dateParams);
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="revenue-report-'.date('Y-m-d').'.xls"');
    echo '<table><thead><tr><th>Order #</th><th>Customer</th><th>Total (NGN)</th><th>Merchant Amount</th><th>Platform Markup</th><th>Delivery Fee</th><th>Discount</th><th>Status</th><th>Date</th></tr></thead><tbody>';
    foreach($allOrders as $r) echo '<tr><td>'.e($r['order_number']).'</td><td>'.e($r['cust']).'</td><td>'.number_format($r['total_amount'],2).'</td><td>'.number_format($r['merchant_amount']??0,2).'</td><td>'.number_format($r['platform_markup_amount']??0,2).'</td><td>'.number_format($r['delivery_fee']??0,2).'</td><td>'.number_format($r['discount_amount']??0,2).'</td><td>'.e($r['status']).'</td><td>'.e($r['created_at']).'</td></tr>';
    echo '</tbody></table>'; exit;
}

// ── KPIs ──────────────────────────────────────────────────────
$gmv        = (float)DB::count("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status!='cancelled' AND $dateWhere", $dateParams);
$ordersCount= (int)DB::count("SELECT COUNT(*) FROM orders WHERE $dateWhere", $dateParams);
$newUsers   = (int)DB::count("SELECT COUNT(*) FROM users WHERE role='customer' AND $dateWhere", $dateParams);
$newShops   = (int)DB::count("SELECT COUNT(*) FROM shops WHERE status='approved' AND $dateWhere", $dateParams);
$markupRate = (float)getSetting('platform_markup', 5);
$commission = $gmv * $markupRate / 100;
$avgOrder   = $ordersCount > 0 ? round($gmv / $ordersCount, 2) : 0;

// All-time totals
$totalGMV   = (float)DB::count("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status!='cancelled'");
$totalOrders= (int)DB::count("SELECT COUNT(*) FROM orders");
$totalUsers = (int)DB::count("SELECT COUNT(*) FROM users WHERE role='customer'");
$totalShops = (int)DB::count("SELECT COUNT(*) FROM shops WHERE status='approved'");

// ── Daily revenue for chart ────────────────────────────────────
$chartData = [];
$maxVal    = 1;
for ($i = $period - 1; $i >= 0; $i--) {
    $d   = date('Y-m-d', strtotime("-{$i} days"));
    $v   = (int)DB::count("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)=? AND status!='cancelled'", [$d]);
    $lbl = date('d M', strtotime("-{$i} days"));
    $chartData[] = ['l' => $lbl, 'v' => $v];
    if ($v > $maxVal) $maxVal = $v;
}

// ── Top merchants ──────────────────────────────────────────────
$topMerchants = DB::fetchAll("
    SELECT s.shop_name, s.total_revenue, s.commission_rate,
           COUNT(DISTINCT oi.order_id) order_count
    FROM shops s
    LEFT JOIN order_items oi ON oi.shop_id = s.id
    WHERE s.status = 'approved'
    GROUP BY s.id
    ORDER BY s.total_revenue DESC
    LIMIT 10");

// ── Top categories ─────────────────────────────────────────────
$topCats = DB::fetchAll("
    SELECT c.name, c.icon,
           COALESCE(SUM(oi.quantity), 0) sold,
           COALESCE(SUM(oi.price * oi.quantity), 0) revenue
    FROM categories c
    LEFT JOIN products p  ON p.category_id = c.id
    LEFT JOIN order_items oi ON oi.product_id = p.id
    GROUP BY c.id
    ORDER BY revenue DESC");

$maxCatRev = max(1, array_reduce($topCats, function($carry, $cat) {
    return max($carry, (float)$cat['revenue']);
}, 0));

// ── Recent orders summary ──────────────────────────────────────
$statusBreakdown = DB::fetchAll("
    SELECT status, COUNT(*) cnt, COALESCE(SUM(total_amount),0) total
    FROM orders GROUP BY status ORDER BY cnt DESC");
?>

<div class="dash-head">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div>
      <h1 class="dash-title">📈 Reports &amp; Analytics</h1>
      <p class="dash-sub">Platform performance overview</p>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
      <?php foreach ([7=>'7 days', 30=>'30 days', 90=>'90 days', 365=>'1 Year'] as $v => $l): ?>
      <a href="?period=<?= $v ?>"
         class="btn btn-sm <?= (!$custFrom && $period == $v) ? 'btn-blue' : 'btn-ghost' ?>">
        <?= $l ?>
      </a>
      <?php endforeach; ?>
      <form method="GET" style="display:flex;gap:5px;align-items:center">
        <input type="date" name="date_from" class="form-control" style="width:128px" value="<?= e($custFrom) ?>" title="From">
        <input type="date" name="date_to"   class="form-control" style="width:128px" value="<?= e($custTo) ?>"   title="To">
        <button class="btn btn-ghost btn-sm">Apply</button>
        <?php if($custFrom):?><a href="?period=<?=$period?>" class="btn btn-ghost btn-sm">Clear</a><?php endif;?>
      </form>
      <a href="?<?= http_build_query(array_merge($_GET,['export'=>'excel'])) ?>"
         class="btn btn-ghost btn-sm">📥 Export Excel</a>
    </div>
  </div>
</div>

<!-- ── ALL-TIME TOTALS ── -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px;margin-bottom:16px">
  <?php $alltime = [
    ['All-time GMV',      money($totalGMV),    'ab', '₦'],
    ['Total Orders',      number_format($totalOrders), 'ab', '📦'],
    ['Total Customers',   number_format($totalUsers),  'ag', '👥'],
    ['Active Merchants',  number_format($totalShops),  'ab', '🏪'],
  ]; foreach ($alltime as $s): ?>
  <div class="stat-card <?= $s[2] ?>">
    <div class="stat-label"><?= $s[0] ?></div>
    <div class="stat-value" style="font-size:1.25rem"><?= $s[1] ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── PERIOD KPIs ── -->
<div style="background:#fff;border:1px solid var(--border-lt);border-radius:var(--r-md);padding:12px 16px;margin-bottom:14px">
  <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:12px">
    Last <?= $period ?> days
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(145px,1fr));gap:10px">
    <?php $kpis = [
      ['GMV',          money($gmv),              'color:var(--black)'],
      ['Commission',   money($commission),        'color:var(--green)'],
      ['Orders',       number_format($ordersCount), ''],
      ['Avg Order',    money($avgOrder),           ''],
      ['New Customers',number_format($newUsers),  ''],
      ['New Shops',    number_format($newShops),  ''],
    ]; foreach ($kpis as $k): ?>
    <div style="background:var(--bg);border-radius:var(--r-sm);padding:10px 12px">
      <div style="font-size:.68rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px"><?= $k[0] ?></div>
      <div style="font-size:1.15rem;font-weight:800;<?= $k[2] ?>"><?= $k[1] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── DAILY REVENUE CHART ── -->
<div class="card" style="margin-bottom:14px">
  <div class="card-head">
    <div class="card-title">📊 Daily Revenue — Last <?= $period ?> days</div>
    <span style="font-size:.76rem;color:var(--text-muted)">Each bar = one day</span>
  </div>
  <?php if ($ordersCount === 0): ?>
    <div class="empty-state" style="padding:24px"><span class="empty-icon" style="font-size:1.5rem">📊</span><p>No order data in this period.</p></div>
  <?php else: ?>
  <!-- Pure CSS/HTML bar chart — no JS needed, no distortion -->
  <div style="overflow-x:auto">
    <div style="display:flex;align-items:flex-end;gap:3px;height:160px;padding:0 4px;min-width:<?= max(400, $period * 18) ?>px">
      <?php foreach ($chartData as $day):
        $pct = $maxVal > 0 ? round($day['v'] / $maxVal * 100) : 0;
        $hasVal = $day['v'] > 0;
      ?>
      <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;min-width:14px">
        <?php if ($hasVal): ?>
        <div style="font-size:.55rem;color:var(--text-muted);writing-mode:horizontal-tb;white-space:nowrap">
          <?= $pct >= 15 ? money_short($day['v']) : '' ?>
        </div>
        <?php endif; ?>
        <div style="width:100%;border-radius:3px 3px 0 0;
                    background:<?= $hasVal ? 'var(--blue)' : 'var(--border-lt)' ?>;
                    height:<?= max(2, $pct) ?>%;
                    transition:height .3s;cursor:default"
             title="<?= $day['l'] ?>: <?= money($day['v']) ?>">
        </div>
        <div style="font-size:.52rem;color:var(--text-muted);white-space:nowrap;
                    writing-mode:vertical-rl;text-orientation:mixed;transform:rotate(180deg);
                    max-height:36px;overflow:hidden">
          <?= $day['l'] ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ── ORDER STATUS BREAKDOWN ── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">

  <div class="card">
    <div class="card-head">
      <div class="card-title">📦 Order Status Breakdown</div>
    </div>
    <?php if (empty($statusBreakdown)): ?>
      <p style="color:var(--text-muted);font-size:.84rem">No orders yet.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Status</th><th>Count</th><th>Total Value</th></tr></thead>
        <tbody>
          <?php foreach ($statusBreakdown as $row): ?>
          <tr>
            <td><?= statusBadge($row['status']) ?></td>
            <td style="font-weight:700"><?= number_format($row['cnt']) ?></td>
            <td style="font-weight:600;color:var(--blue)"><?= money($row['total']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── CATEGORY REVENUE ── -->
  <div class="card">
    <div class="card-head">
      <div class="card-title">🎨 Revenue by Category</div>
    </div>
    <?php if (empty($topCats)): ?>
      <p style="color:var(--text-muted);font-size:.84rem">No sales data yet.</p>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php foreach ($topCats as $cat):
        $pct = $maxCatRev > 0 ? round((float)$cat['revenue'] / $maxCatRev * 100) : 0;
      ?>
      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
          <span style="font-size:.82rem;font-weight:600;color:var(--text)">
            <?= $cat['icon'] ?> <?= e($cat['name']) ?>
          </span>
          <div style="text-align:right">
            <span style="font-size:.82rem;font-weight:700;color:var(--blue)"><?= money($cat['revenue']) ?></span>
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

<!-- ── TOP MERCHANTS ── -->
<div class="card">
  <div class="card-head">
    <div class="card-title">🏆 Top Merchants by Revenue</div>
    <a href="<?= BASE_URL ?>/admin/merchants.php" class="btn btn-ghost btn-sm">View All →</a>
  </div>
  <?php if (empty($topMerchants)): ?>
    <p style="color:var(--text-muted);font-size:.84rem">No merchant data yet.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Shop Name</th>
          <th>Total Revenue</th>
          <th>Commission Rate</th>
          <th>Est. Commission</th>
          <th>Orders</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($topMerchants as $i => $m): ?>
        <tr>
          <td style="font-weight:700;color:var(--text-muted)"><?= $i + 1 ?></td>
          <td style="font-weight:600;font-size:.86rem"><?= e($m['shop_name']) ?></td>
          <td style="font-weight:700;color:var(--blue)"><?= money($m['total_revenue']) ?></td>
          <td><?= $m['commission_rate'] ?>%</td>
          <td style="color:var(--green);font-weight:600"><?= money($m['total_revenue'] * $m['commission_rate'] / 100) ?></td>
          <td><?= number_format($m['order_count']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
