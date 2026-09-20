<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/auth.php';
logRequire();

$la  = logAuth();
$cid = $la['company_id'];
$co  = DB::fetch('SELECT * FROM logistics_companies WHERE id=?', [$cid]);

$pageTitle = 'Payments'; $activeNav = 'payments';
include __DIR__ . '/header.php';

// ── Delivery fees earned per delivered order ──────────────────
$statusFilter = trim($_GET['status'] ?? '');
$dateFrom     = trim($_GET['date_from'] ?? '');
$dateTo       = trim($_GET['date_to']   ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$per          = 25;

$where  = ['ol.company_id = ?'];
$params = [$cid];
if ($statusFilter) { $where[] = 'ol.status = ?';              $params[] = $statusFilter; }
if ($dateFrom)     { $where[] = 'DATE(ol.assigned_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)       { $where[] = 'DATE(ol.assigned_at) <= ?'; $params[] = $dateTo; }
$wStr = implode(' AND ', $where);

// KPI Totals
$totalEarned   = (float)DB::count(
    "SELECT COALESCE(SUM(ol.fee_ngn), 0) FROM order_logistics ol WHERE ol.company_id=? AND ol.status='delivered'",
    [$cid]
);
$pendingCount  = (int)DB::count(
    "SELECT COUNT(*) FROM order_logistics ol WHERE ol.company_id=? AND ol.status NOT IN ('delivered','returned','failed')",
    [$cid]
);
$deliveredCount= (int)DB::count(
    "SELECT COUNT(*) FROM order_logistics ol WHERE ol.company_id=? AND ol.status='delivered'",
    [$cid]
);

// Payout records (paid out amounts from admin)
$payouts = DB::fetchAll(
    "SELECT * FROM payouts
     WHERE logistics_company_id=? AND payout_type='logistics'
     ORDER BY requested_at DESC",
    [$cid]
);
$totalPaidOut = array_sum(array_column(
    array_filter($payouts, function($p){ return $p['status'] === 'paid'; }),
    'net_amount'
));
$totalOutstanding = $totalEarned - $totalPaidOut;

// Deliveries list
$total    = (int)DB::count(
    "SELECT COUNT(*) FROM order_logistics ol WHERE $wStr", $params
);
$deliveries = DB::fetchAll(
    "SELECT ol.*,
            o.order_number,
            o.delivery_address,
            o.created_at order_date,
            COALESCE(u.name,  o.guest_name)  cust_name,
            COALESCE(u.email, o.guest_email) cust_email,
            s.shop_name
     FROM order_logistics ol
     JOIN orders o  ON o.id  = ol.order_id
     JOIN shops s   ON s.id  = ol.shop_id
     LEFT JOIN users u ON u.id = o.user_id
     WHERE $wStr
     ORDER BY ol.assigned_at DESC
     LIMIT $per OFFSET " . (($page - 1) * $per),
    $params
);

// Pull the city from the delivery address (second-to-last segment)
function extractDestCity(string $addr): string {
    $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n|,/', $addr))));
    $n = count($lines);
    if ($n === 0) return '—';
    if ($n === 1) return $lines[0];
    return $lines[$n - 2];
}

$statusBadgeMap = [
    'assigned'         => 'badge-info',
    'picked_up'        => 'badge-warning',
    'in_transit'       => 'badge-warning',
    'out_for_delivery' => 'badge-warning',
    'delivered'        => 'badge-success',
    'failed'           => 'badge-danger',
    'returned'         => 'badge-danger',
];
?>

<div style="margin-bottom:20px">
  <h1 style="font-family:var(--ff-head);font-size:1.4rem;font-weight:700;color:var(--black);margin-bottom:3px">
    💳 Payments & Earnings
  </h1>
  <p style="font-size:.84rem;color:var(--text-muted)">
    Delivery fee earnings and payout history for <?= e($co['company_name'] ?? '') ?>
  </p>
</div>

<!-- KPI Cards -->
<div class="m-stat-grid" style="margin-bottom:20px">
  <div class="m-stat accent-green">
    <div class="m-stat-label">Total Fees Earned</div>
    <div class="m-stat-value"><?= moneyNgn($totalEarned) ?></div>
    <div class="m-stat-hint">From <?= $deliveredCount ?> delivered order<?= $deliveredCount!==1?'s':''?></div>
  </div>
  <div class="m-stat accent-blue">
    <div class="m-stat-label">Total Paid Out</div>
    <div class="m-stat-value"><?= moneyNgn($totalPaidOut) ?></div>
    <div class="m-stat-hint">Transferred to your bank account</div>
  </div>
  <div class="m-stat" style="border-left:4px solid var(--orange)">
    <div class="m-stat-label">Outstanding Balance</div>
    <div class="m-stat-value" style="color:var(--orange)"><?= moneyNgn($totalOutstanding) ?></div>
    <div class="m-stat-hint">Pending payment from platform</div>
  </div>
  <div class="m-stat">
    <div class="m-stat-label">Active Deliveries</div>
    <div class="m-stat-value"><?= $pendingCount ?></div>
    <div class="m-stat-hint">Orders in progress</div>
  </div>
</div>

<!-- Bank account notice -->
<?php if (empty($co['bank_account'])): ?>
<div class="alert alert-warning" style="margin-bottom:16px">
  ⚠️ <strong>No bank account on file.</strong>
  Payouts cannot be processed until you add your bank details.
  <a href="<?= BASE_URL ?>/logistics/bank-account.php"
     style="font-weight:700;margin-left:8px;color:var(--navy)">Add Bank Account →</a>
</div>
<?php else: ?>
<div style="background:var(--green-pale);border:1px solid var(--green-pale2);border-radius:var(--r-md);
            padding:10px 14px;margin-bottom:16px;font-size:.82rem;display:flex;gap:16px;flex-wrap:wrap">
  <div>💳 <strong><?= e($co['bank_name']) ?></strong> · <?= e($co['bank_account']) ?></div>
  <div>Account Name: <strong><?= e($co['bank_account_name']) ?></strong></div>
  <a href="<?= BASE_URL ?>/logistics/bank-account.php" style="color:var(--blue);font-weight:600">Edit →</a>
</div>
<?php endif; ?>

<!-- Payout history -->
<?php if (!empty($payouts)): ?>
<div class="card" style="margin-bottom:16px">
  <div class="card-head">
    <div class="card-title">📤 Payout History</div>
    <span style="font-size:.78rem;color:var(--text-muted)">Payments from African Attire to your account</span>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Delivery Fees Covered</th>
          <th>Amount Paid</th>
          <th>Status</th>
          <th>Processed</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payouts as $py):
          $pyBadge = [
              'pending'  => 'badge-warning',
              'approved' => 'badge-info',
              'paid'     => 'badge-success',
              'rejected' => 'badge-danger',
          ][$py['status']] ?? 'badge-muted';
        ?>
        <tr>
          <td style="font-size:.8rem;color:var(--text-muted);white-space:nowrap">
            <?= date('M j, Y', strtotime($py['requested_at'])) ?>
          </td>
          <td style="font-weight:700;color:var(--navy)"><?= moneyNgn($py['logistics_fee_amount'] ?? $py['amount']) ?></td>
          <td style="font-weight:700;color:var(--green)"><?= moneyNgn($py['net_amount']) ?></td>
          <td><span class="badge <?= $pyBadge ?>"><?= ucfirst($py['status']) ?></span></td>
          <td style="font-size:.78rem;color:var(--text-muted)">
            <?= $py['processed_at'] ? date('M j, Y', strtotime($py['processed_at'])) : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Delivery earnings breakdown -->
<div class="card" style="padding:0">
  <div class="card-head" style="padding:14px 16px">
    <div class="card-title">📦 Delivery Earnings Breakdown</div>
    <span style="font-size:.78rem;color:var(--text-muted)"><?= number_format($total) ?> deliveries</span>
  </div>

  <!-- Filters -->
  <div style="padding:10px 16px;border-bottom:1px solid var(--border-lt);display:flex;gap:6px;flex-wrap:wrap;align-items:center">
    <form method="GET" style="display:contents">
      <select name="status" class="sort-select" style="font-size:.78rem" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['assigned','picked_up','in_transit','out_for_delivery','delivered','failed','returned'] as $st): ?>
        <option value="<?= $st ?>" <?= $statusFilter===$st?'selected':''?>>
          <?= ucwords(str_replace('_',' ',$st)) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <input type="date" name="date_from" class="form-control" style="width:130px;font-size:.8rem"
             value="<?= e($dateFrom) ?>" title="From">
      <input type="date" name="date_to"   class="form-control" style="width:130px;font-size:.8rem"
             value="<?= e($dateTo) ?>"   title="To">
      <button class="btn btn-ghost btn-sm">Filter</button>
      <?php if ($statusFilter || $dateFrom || $dateTo): ?>
      <a href="?" class="btn btn-ghost btn-sm">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <?php if (empty($deliveries)): ?>
  <div class="empty-state" style="padding:40px">
    <span class="empty-icon">📦</span>
    <p>No deliveries found for the selected filters.</p>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Delivery Destination</th>
          <th>Merchant</th>
          <th>Distance</th>
          <th>Fee Earned</th>
          <th>Delivery Status</th>
          <th>Assigned</th>
          <th>Tracking #</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($deliveries as $d):
          $destCity = extractDestCity($d['delivery_address'] ?? '');
          $feeEarned = (float)($d['fee_ngn'] ?? 0);
          $badge = $statusBadgeMap[$d['status']] ?? 'badge-muted';
        ?>
        <tr>
          <!-- Order number + link -->
          <td>
            <a href="<?= BASE_URL ?>/logistics/order-detail.php?id=<?= $d['id'] ?>"
               style="font-weight:700;color:var(--blue);text-decoration:none;font-size:.84rem">
              <?= e($d['order_number']) ?>
            </a>
            <div style="font-size:.7rem;color:var(--text-muted)">
              Order date: <?= date('M j, Y', strtotime($d['order_date'])) ?>
            </div>
          </td>

          <!-- Destination -->
          <td>
            <div style="font-weight:600;font-size:.84rem">📍 <?= e($destCity) ?></div>
            <div style="font-size:.72rem;color:var(--text-muted)">
              <?= e($d['cust_name'] ?: 'Guest') ?>
            </div>
          </td>

          <!-- Merchant -->
          <td style="font-size:.82rem"><?= e($d['shop_name']) ?></td>

          <!-- Distance -->
          <td style="font-size:.82rem;color:var(--text-muted)">
            <?= $d['distance_km'] ? number_format($d['distance_km'], 1) . ' km' : '—' ?>
          </td>

          <!-- Fee earned -->
          <td>
            <?php if ($feeEarned > 0): ?>
            <span style="font-family:var(--ff-head);font-size:.9rem;font-weight:700;
                         color:<?= $d['status']==='delivered'?'var(--green)':'var(--navy)' ?>">
              <?= moneyNgn($feeEarned) ?>
            </span>
            <?php if ($d['status'] !== 'delivered'): ?>
            <div style="font-size:.68rem;color:var(--text-muted)">Pending delivery</div>
            <?php endif; ?>
            <?php else: ?>
            <span style="color:var(--text-muted);font-size:.8rem">Calculating…</span>
            <?php endif; ?>
          </td>

          <!-- Status -->
          <td>
            <span class="badge <?= $badge ?>">
              <?= ucwords(str_replace('_', ' ', $d['status'])) ?>
            </span>
          </td>

          <!-- Assigned date -->
          <td style="font-size:.76rem;color:var(--text-muted);white-space:nowrap">
            <?= date('M j, Y', strtotime($d['assigned_at'])) ?><br>
            <span style="font-size:.7rem"><?= date('g:i A', strtotime($d['assigned_at'])) ?></span>
          </td>

          <!-- Tracking number -->
          <td style="font-family:monospace;font-size:.78rem;color:var(--text-muted)">
            <?= e($d['tracking_number'] ?: '—') ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= paginate($total, $per, $page, '?' . http_build_query(array_diff_key($_GET, ['page'=>'']))) ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
