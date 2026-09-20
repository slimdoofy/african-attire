<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Orders';
$activeNav = 'orders';

// ─── Update order status ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $oid = (int)$_POST['order_id'];
    $st  = trim($_POST['status']         ?? '');
    $pay = trim($_POST['payment_status'] ?? '');

    // Fetch full order BEFORE updating (need old status + customer info)
    $order = DB::fetch(
        "SELECT o.*,
                COALESCE(u.name,  o.guest_name)  AS cust_name,
                COALESCE(u.email, o.guest_email) AS cust_email,
                CASE WHEN o.user_id IS NULL THEN 1 ELSE 0 END AS is_guest
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         WHERE o.id = ?",
        [$oid]
    );

    $statusChanged = $order && $st && $order['status'] !== $st;

    if ($st)  DB::update('orders', ['status'         => $st],  'id=?', [$oid]);
    if ($pay) DB::update('orders', ['payment_status' => $pay], 'id=?', [$oid]);

    // Send customer email when status changes to a meaningful value
    $notifyStatuses = ['processing', 'shipped', 'delivered', 'cancelled'];
    if ($statusChanged && in_array($st, $notifyStatuses, true) && !empty($order['cust_email'])) {
        $items = DB::fetchAll(
            'SELECT oi.*, s.shop_name
             FROM order_items oi
             JOIN shops s ON s.id = oi.shop_id
             WHERE oi.order_id = ?',
            [$oid]
        );

        // Build tracking URL for guests
        $trackUrl = ($order['is_guest'] && !empty($order['guest_token']))
            ? BASE_URL . '/customer/track-order.php?order='
              . urlencode($order['order_number'])
              . '&token=' . urlencode($order['guest_token'])
            : null;

        // Use the first shop name as the "seller" identifier, or site name
        $shopName = !empty($items) ? $items[0]['shop_name'] : SITE_NAME;

        sendOrderStatusEmail($order, $items, $st, $shopName, $trackUrl);
        flash('Order updated to "' . ucfirst($st) . '" — customer notified by email.', 'success');
    } else {
        flash('Order updated successfully.', 'success');
    }

    redirect(BASE_URL . '/admin/orders.php' .
             '?status=' . urlencode($_POST['filter_status'] ?? '') .
             '&q='      . urlencode($_POST['filter_q']      ?? ''));
}

include __DIR__ . '/../includes/header_admin.php';

$status   = trim($_GET['status']    ?? '');
$q        = trim($_GET['q']         ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to']   ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per      = 25;

// ─── Build WHERE ──────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($status) {
    $where[]  = 'o.status = ?';
    $params[] = $status;
}

if ($q) {
    $where[]  = '(
        o.order_number LIKE ?
        OR u.name        LIKE ?
        OR u.email       LIKE ?
        OR o.guest_name  LIKE ?
        OR o.guest_email LIKE ?
    )';
    $like     = "%$q%";
    $params   = array_merge($params, [$like, $like, $like, $like, $like]);
}

if ($dateFrom) { $where[] = 'DATE(o.created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = 'DATE(o.created_at) <= ?'; $params[] = $dateTo;   }

$wStr = implode(' AND ', $where);

// ─── Excel export ─────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $allOrders = DB::fetchAll(
        "SELECT o.order_number,
                COALESCE(u.name,  o.guest_name,  'Guest') cust,
                COALESCE(u.email, o.guest_email)           email,
                o.total_amount, o.delivery_fee, o.discount_amount,
                o.discount_code, o.payment_status, o.status, o.created_at
         FROM orders o LEFT JOIN users u ON u.id=o.user_id
         WHERE $wStr ORDER BY o.created_at DESC", $params
    );
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="orders-'.date('Y-m-d').'.xls"');
    echo '<table><thead><tr>
        <th>Order #</th><th>Customer</th><th>Email</th>
        <th>Total (NGN)</th><th>Delivery Fee</th><th>Discount</th><th>Discount Code</th>
        <th>Payment</th><th>Status</th><th>Date</th>
    </tr></thead><tbody>';
    foreach ($allOrders as $o) {
        echo '<tr>'
           . '<td>'.htmlspecialchars($o['order_number']).'</td>'
           . '<td>'.htmlspecialchars($o['cust']).'</td>'
           . '<td>'.htmlspecialchars($o['email'] ?? '').'</td>'
           . '<td>'.number_format($o['total_amount'],2).'</td>'
           . '<td>'.number_format($o['delivery_fee']??0,2).'</td>'
           . '<td>'.number_format($o['discount_amount']??0,2).'</td>'
           . '<td>'.htmlspecialchars($o['discount_code']??'').'</td>'
           . '<td>'.htmlspecialchars($o['payment_status']).'</td>'
           . '<td>'.htmlspecialchars($o['status']).'</td>'
           . '<td>'.htmlspecialchars($o['created_at']).'</td>'
           . '</tr>';
    }
    echo '</tbody></table>';
    exit;
}

// COUNT
$total = (int)DB::count(
    "SELECT COUNT(*)
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     WHERE $wStr",
    $params
);

// FETCH — most recent first; guest fields as fallback for name/email columns
$orders = DB::fetchAll(
    "SELECT
        o.*,
        COALESCE(u.name,  o.guest_name)  AS cust_name,
        COALESCE(u.email, o.guest_email) AS cust_email,
        CASE WHEN o.user_id IS NULL THEN 1 ELSE 0 END AS is_guest,
        (SELECT GROUP_CONCAT(DISTINCT s.shop_name ORDER BY s.shop_name SEPARATOR ', ')
         FROM order_items oi
         JOIN shops s ON s.id = oi.shop_id
         WHERE oi.order_id = o.id) AS merchant_names
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     WHERE $wStr
     ORDER BY o.created_at DESC
     LIMIT $per OFFSET " . (($page - 1) * $per),
    $params
);
?>

<div class="dash-head">
  <div class="flex-between" style="flex-wrap:wrap;gap:10px">
    <div>
      <h1 class="dash-title">📦 All Orders</h1>
      <p class="dash-sub">
        <?= number_format($total) ?> order<?= $total !== 1 ? 's' : '' ?>
        <?= $status ? ' · filtered: <strong>' . ucfirst(e($status)) . '</strong>' : '' ?>
        <?= $q      ? ' · search: "<strong>' . e($q) . '</strong>"' : '' ?>
      </p>
    </div>
  </div>
</div>

<!-- Tabs + Search -->
<div style="display:flex;align-items:center;justify-content:space-between;
            flex-wrap:wrap;gap:10px;margin-bottom:14px">
  <div class="tab-bar" style="border:none;margin-bottom:0">
    <?php foreach ([
        ''           => 'All',
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'shipped'    => 'Shipped',
        'delivered'  => 'Delivered',
        'cancelled'  => 'Cancelled',
        'disputed'   => 'Disputed',
    ] as $v => $l):
        $cnt = (int)DB::count(
            "SELECT COUNT(*) FROM orders o
             LEFT JOIN users u ON u.id=o.user_id
             WHERE " . ($v ? "o.status=?" : "1=1"),
            $v ? [$v] : []
        );
    ?>
    <a href="?status=<?= urlencode($v) ?>&q=<?= urlencode($q) ?>"
       class="tab-btn <?= $status === $v ? 'active' : '' ?>">
      <?= $l ?>
      <?php if ($cnt > 0): ?>
        <span style="font-size:.65rem;background:<?= $v==='pending'?'var(--ju)':'var(--border)' ?>;
                     color:<?= $v==='pending'?'#fff':'var(--text-muted)' ?>;
                     padding:1px 5px;border-radius:999px;margin-left:3px">
          <?= $cnt ?>
        </span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
  <form method="GET" style="display:contents">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <input type="text" name="q" class="form-control"
           placeholder="Order #, name or email…"
           value="<?= e($q) ?>" style="width:200px">
    <input type="date" name="date_from" class="form-control" style="width:130px"
           value="<?= e($dateFrom) ?>" title="From date">
    <input type="date" name="date_to" class="form-control" style="width:130px"
           value="<?= e($dateTo) ?>" title="To date">
    <button class="btn btn-ghost btn-sm">Search</button>
    <?php if ($q || $dateFrom || $dateTo): ?>
    <a href="?status=<?= urlencode($status) ?>" class="btn btn-ghost btn-sm">✕ Clear</a>
    <?php endif; ?>
  </form>
  <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'excel'])) ?>"
     class="btn btn-ghost btn-sm">📥 Export Excel</a>
  </div>
</div>

<!-- Orders table -->
<?php if (empty($orders)): ?>
  <div class="empty-state card">
    <span class="empty-icon">📦</span>
    <p style="font-weight:600;margin-bottom:4px">No orders found</p>
    <?php if ($status || $q): ?>
    <p style="font-size:.82rem">Try clearing the filters above.</p>
    <a href="?" class="btn btn-ju btn-sm" style="margin-top:10px">Clear Filters</a>
    <?php endif; ?>
  </div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Merchant</th>
          <th>Type</th>
          <th>Amount</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Date &amp; Time</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr id="row-<?= $o['id'] ?>">

          <!-- Order number -->
          <td>
            <span style="font-weight:700;color:var(--blue);font-size:.84rem">
              <?= e($o['order_number']) ?>
            </span>
          </td>

          <!-- Customer (registered or guest) -->
          <td>
            <div style="font-weight:600;font-size:.84rem;color:var(--black)">
              <?= e($o['cust_name'] ?: '—') ?>
            </div>
            <div style="font-size:.72rem;color:var(--text-muted)">
              <?= e($o['cust_email'] ?: '—') ?>
            </div>
            <?php if (!empty($o['guest_phone'])): ?>
            <div style="font-size:.7rem;color:var(--text-muted)">
              📞 <?= e($o['guest_phone']) ?>
            </div>
            <?php endif; ?>
          </td>

          <!-- Merchant / Shop name -->
          <td style="font-size:.82rem;color:var(--text);max-width:140px">
            <?php if (!empty($o['merchant_names'])): ?>
              <div style="font-weight:600"><?= e($o['merchant_names']) ?></div>
            <?php else: ?>
              <span style="color:var(--text-muted)">—</span>
            <?php endif; ?>
          </td>

          <!-- Guest / Member badge -->
          <td>
            <?php if ($o['is_guest']): ?>
            <span class="badge badge-muted" style="font-size:.65rem">👤 Guest</span>
            <?php else: ?>
            <span class="badge badge-info" style="font-size:.65rem">✓ Member</span>
            <?php endif; ?>
          </td>

          <!-- Amount -->
          <td style="font-weight:700;color:var(--black)">
            <?= money($o['total_amount']) ?>
          </td>

          <!-- Payment status -->
          <td><?= statusBadge($o['payment_status']) ?></td>

          <!-- Order status -->
          <td><?= statusBadge($o['status']) ?></td>

          <!-- Date — most recent first, show time too -->
          <td style="font-size:.8rem;color:var(--text-muted);white-space:nowrap">
            <?= date('M j, Y', strtotime($o['created_at'])) ?>
            <div style="font-size:.7rem"><?= date('g:i A', strtotime($o['created_at'])) ?></div>
          </td>

          <!-- Actions -->
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <button class="btn btn-blue btn-xs"
                      onclick="showOrderEdit(<?= htmlspecialchars(json_encode($o)) ?>)">
                ✏ Edit
              </button>
              <?php if ($o['is_guest'] && $o['guest_token']): ?>
              <a href="<?= BASE_URL ?>/customer/track-order.php?order=<?= urlencode($o['order_number']) ?>&token=<?= urlencode($o['guest_token']) ?>"
                 target="_blank" class="btn btn-ghost btn-xs">Track</a>
              <?php else: ?>
              <a href="<?= BASE_URL ?>/customer/order-detail.php?id=<?= $o['id'] ?>"
                 target="_blank" class="btn btn-ghost btn-xs">View</a>
              <?php endif; ?>
            </div>
          </td>

        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?= paginate($total, $per, $page,
    '?status=' . urlencode($status) . '&q=' . urlencode($q)) ?>

<?php endif; ?>

<!-- Edit Order Modal -->
<div class="modal-bg" id="edit-order-modal">
  <div class="modal">
    <div class="modal-head">
      <h3 class="modal-title">✏ Update Order</h3>
      <button class="modal-close" onclick="closeModal('edit-order-modal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="order_id"      id="edit-oid">
      <input type="hidden" name="filter_status" value="<?= e($status) ?>">
      <input type="hidden" name="filter_q"      value="<?= e($q) ?>">

      <!-- Order summary in modal -->
      <div style="background:var(--bg);border-radius:var(--r-sm);padding:10px 12px;
                  margin-bottom:14px;font-size:.82rem">
        <div style="display:flex;justify-content:space-between;margin-bottom:3px">
          <span style="color:var(--text-muted)">Order</span>
          <strong id="edit-order-num"></strong>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:3px">
          <span style="color:var(--text-muted)">Customer</span>
          <span id="edit-cust-name"></span>
        </div>
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text-muted)">Amount</span>
          <strong id="edit-amount" style="color:var(--blue)"></strong>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Order Status</label>
          <select name="status" id="edit-status" class="form-control">
            <?php foreach (['pending','processing','shipped','delivered','cancelled','disputed'] as $s): ?>
            <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Payment Status</label>
          <select name="payment_status" id="edit-pay" class="form-control">
            <?php foreach (['pending','paid','failed','refunded'] as $s): ?>
            <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;
                  border-top:1px solid var(--border-lt);padding-top:12px">
        <button type="button" class="btn btn-ghost"
                onclick="closeModal('edit-order-modal')">Cancel</button>
        <button type="submit" class="btn btn-ju">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function showOrderEdit(o) {
  document.getElementById('edit-oid').value        = o.id;
  document.getElementById('edit-status').value     = o.status;
  document.getElementById('edit-pay').value        = o.payment_status;
  document.getElementById('edit-order-num').textContent  = o.order_number;
  document.getElementById('edit-cust-name').textContent  = o.cust_name || '(Guest)';
  document.getElementById('edit-amount').textContent = o.total_amount ?
    '₦' + parseFloat(o.total_amount).toLocaleString('en-NG', {minimumFractionDigits:2}) : '—';
  var rateRow = document.getElementById('edit-rate-row');
  var usdRow  = document.getElementById('edit-usd-row');
  if (o.usd_rate && parseFloat(o.usd_rate) > 0) {
    document.getElementById('edit-rate').textContent = '$1 = ₦' + parseFloat(o.usd_rate).toLocaleString('en-NG', {minimumFractionDigits:2});
    document.getElementById('edit-usd-amount').textContent = o.usd_amount ? '$' + parseFloat(o.usd_amount).toFixed(2) : '—';
    rateRow.style.display = 'flex';
    usdRow.style.display  = 'flex';
  } else {
    rateRow.style.display = 'none';
    usdRow.style.display  = 'none';
  }
  openModal('edit-order-modal');
}
</script>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
