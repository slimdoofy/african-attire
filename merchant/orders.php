<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// ── ALL POST HANDLING BEFORE ANY OUTPUT ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    Auth::requireRole('merchant');
    $_shop = DB::fetch('SELECT * FROM shops WHERE user_id=?', [Auth::id()]);
    if (!$_shop) redirect(BASE_URL . '/merchant/register.php');

    $sid    = $_shop['id'];
    $oid    = (int)$_POST['order_id'];
    $newSt  = trim($_POST['status'] ?? '');
    $filter = trim($_POST['filter_status'] ?? '');
    $goDetail = !empty($_POST['redirect_to']) && $_POST['redirect_to'] === 'detail';

    if ($newSt) {
        // Get old status + customer info BEFORE updating
        $order = DB::fetch(
            "SELECT o.*,
                    COALESCE(u.name,  o.guest_name)  AS cust_name,
                    COALESCE(u.email, o.guest_email) AS cust_email,
                    COALESCE(u.phone, o.guest_phone) AS cust_phone,
                    CASE WHEN o.user_id IS NULL THEN 1 ELSE 0 END AS is_guest
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             WHERE o.id = ?
               AND EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id=o.id AND oi.shop_id=?)",
            [$oid, $sid]
        );

        if ($order && $order['status'] !== $newSt) {
            DB::update('orders', ['status' => $newSt], 'id=?', [$oid]);

            // Only send email for meaningful customer-facing statuses
            $notifyStatuses = ['processing','shipped','delivered','cancelled'];
            if (in_array($newSt, $notifyStatuses, true) && !empty($order['cust_email'])) {
                $items    = DB::fetchAll(
                    'SELECT oi.* FROM order_items oi WHERE oi.order_id=? AND oi.shop_id=?',
                    [$oid, $sid]
                );
                $trackUrl = ($order['is_guest'] && !empty($order['guest_token']))
                    ? BASE_URL . '/customer/track-order.php?order='
                      . urlencode($order['order_number'])
                      . '&token=' . urlencode($order['guest_token'])
                    : null;

                sendOrderStatusEmail(
                    $order,
                    $items,
                    $newSt,
                    $_shop['shop_name'],
                    $trackUrl
                );
            }

            flash('Order status updated to "' . ucfirst($newSt) . '". Customer notified.', 'success');
        } elseif (!$order) {
            flash('Order not found or access denied.', 'error');
        } else {
            flash('Status unchanged.', 'info');
        }
    }

    if ($goDetail) {
        redirect(BASE_URL . '/merchant/order-detail.php?id=' . $oid);
    }
    redirect(BASE_URL . '/merchant/orders.php?status=' . urlencode($filter));
}

// ── LOAD PAGE ─────────────────────────────────────────────────
$pageTitle = 'Orders';
$activeNav = 'orders';
include __DIR__ . '/../includes/header_merchant.php';

$sid    = $_shop['id'];
$status = trim($_GET['status'] ?? '');
$q      = trim($_GET['q']      ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 20;

// WHERE — LEFT JOIN so guest orders are included
$where  = ['oi.shop_id=?']; $params = [$sid];
if ($status) { $where[] = 'o.status=?'; $params[] = $status; }
if ($q) {
    $where[] = '(o.order_number LIKE ? OR COALESCE(u.name,o.guest_name) LIKE ? OR COALESCE(u.email,o.guest_email) LIKE ?)';
    $params  = array_merge($params, ["%$q%", "%$q%", "%$q%"]);
}
$wStr = implode(' AND ', $where);

$total = (int)DB::count(
    "SELECT COUNT(DISTINCT o.id)
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     LEFT JOIN users u ON u.id = o.user_id
     WHERE $wStr", $params);

$orders = DB::fetchAll(
    "SELECT o.id, o.order_number, o.status, o.payment_status, o.created_at,
            COALESCE(u.name,  o.guest_name)  AS cust_name,
            COALESCE(u.email, o.guest_email) AS cust_email,
            CASE WHEN o.user_id IS NULL THEN 1 ELSE 0 END AS is_guest,
            SUM(oi.price * oi.quantity) AS shop_revenue,
            COUNT(oi.id)               AS item_count
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     LEFT JOIN users u ON u.id = o.user_id
     WHERE $wStr
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT $per OFFSET " . (($page-1)*$per), $params);
?>

<div class="m-page-head">
  <div>
    <div class="m-page-title">📦 Orders</div>
    <div class="m-page-sub">
      <?= number_format($total) ?> order<?= $total!==1?'s':''?>
      <?= $status ? ' · <strong>'.ucfirst(e($status)).'</strong>' : '' ?>
    </div>
  </div>
  <form method="GET" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <?php if($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <input type="text" name="q" class="form-control" placeholder="Order #, name or email…"
           value="<?= e($q) ?>" style="width:220px">
    <button class="btn btn-ju btn-sm">Search</button>
    <?php if($q): ?><a href="?status=<?= urlencode($status) ?>" class="btn btn-ghost btn-sm">✕</a><?php endif; ?>
  </form>
</div>

<!-- Status tabs -->
<div class="tab-bar" style="margin-bottom:14px">
  <?php foreach ([
    ''           => 'All',
    'pending'    => 'Pending',
    'processing' => 'Processing',
    'shipped'    => 'Shipped',
    'delivered'  => 'Delivered',
    'cancelled'  => 'Cancelled',
  ] as $v => $l): ?>
  <a href="?status=<?= urlencode($v) ?><?= $q?'&q='.urlencode($q):'' ?>"
     class="tab-btn <?= $status===$v?'active':'' ?>">
    <?= $l ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if (empty($orders)): ?>
<div class="empty-state card" style="padding:48px 24px">
  <span class="empty-icon">📦</span>
  <p style="font-weight:600;margin-bottom:6px">No orders found</p>
  <p style="font-size:.82rem">Orders appear here when customers purchase your products.</p>
  <?php if ($status||$q): ?>
  <a href="?" class="btn btn-ju btn-sm" style="margin-top:12px">Clear filters</a>
  <?php endif; ?>
</div>

<?php else: ?>

<div class="card" style="padding:0">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Type</th>
          <th>Items</th>
          <th>Revenue</th>
          <th>Payment</th>
          <th>Update Status</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>

          <!-- Order number -->
          <td>
            <a href="<?= BASE_URL ?>/merchant/order-detail.php?id=<?= $o['id'] ?>"
               style="font-weight:700;color:var(--blue);font-size:.84rem;text-decoration:none">
              <?= e($o['order_number']) ?>
            </a>
          </td>

          <!-- Customer -->
          <td>
            <div style="font-weight:600;font-size:.84rem;color:var(--black)"><?= e($o['cust_name'] ?: '—') ?></div>
            <div style="font-size:.72rem;color:var(--text-muted)">🔒 Email hidden</div>
          </td>

          <!-- Guest/Member -->
          <td>
            <?php if ($o['is_guest']): ?>
            <span class="badge badge-muted" style="font-size:.64rem">👤 Guest</span>
            <?php else: ?>
            <span class="badge badge-info" style="font-size:.64rem">✓ Member</span>
            <?php endif; ?>
          </td>

          <td style="font-size:.82rem;color:var(--text-muted)">
            <?= $o['item_count'] ?> item<?= $o['item_count']>1?'s':''?>
          </td>
          <td style="font-weight:700;color:var(--blue)"><?= money($o['shop_revenue']) ?></td>
          <td><?= statusBadge($o['payment_status']) ?></td>

          <!-- Status dropdown — POST before header means no headers warning -->
          <td>
            <form method="POST" style="display:flex;align-items:center;gap:5px">
              <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
              <input type="hidden" name="filter_status" value="<?= e($status) ?>">
              <select name="status" class="sort-select"
                      style="font-size:.76rem;padding:4px 8px"
                      onchange="this.form.submit()">
                <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $o['status']===$s?'selected':''?>>
                  <?= ucfirst($s) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>

          <td style="font-size:.78rem;color:var(--text-muted);white-space:nowrap">
            <?= date('M j, Y', strtotime($o['created_at'])) ?>
            <div style="font-size:.7rem"><?= date('g:i A', strtotime($o['created_at'])) ?></div>
          </td>

          <!-- View — routes to merchant's own order detail page -->
          <td>
            <a href="<?= BASE_URL ?>/merchant/order-detail.php?id=<?= $o['id'] ?>"
               class="btn btn-blue btn-xs">
              View →
            </a>
          </td>

        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?= paginate($total, $per, $page, '?status='.urlencode($status).'&q='.urlencode($q)) ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer_merchant.php'; ?>
