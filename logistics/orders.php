<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/auth.php';
logRequire();
$la  = logAuth();
$cid = $la['company_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])) {
    $aid    = (int)$_POST['assignment_id'];
    $newSt  = trim($_POST['status'] ?? '');
    $note   = trim($_POST['note']   ?? '');
    $trackN = trim($_POST['tracking_number'] ?? '');

    $assignment = DB::fetch(
        "SELECT ol.*, o.order_number, o.guest_token,
                COALESCE(u.name,o.guest_name)   cust_name,
                COALESCE(u.email,o.guest_email) cust_email,
                o.delivery_address,
                CASE WHEN o.user_id IS NULL THEN 1 ELSE 0 END is_guest,
                s.shop_name
         FROM order_logistics ol
         JOIN orders o ON o.id=ol.order_id
         JOIN shops s  ON s.id=ol.shop_id
         LEFT JOIN users u ON u.id=o.user_id
         WHERE ol.id=? AND ol.company_id=?",
        [$aid, $cid]
    );

    $validStatuses = ['assigned','picked_up','in_transit','out_for_delivery','delivered','failed','returned'];

    if ($assignment && in_array($newSt, $validStatuses, true) && $newSt !== $assignment['status']) {
        // Log old status
        DB::insert('logistics_status_log', [
            'assignment_id' => $aid,
            'changed_by'    => $la['id'],
            'user_name'     => $la['name'],
            'old_status'    => $assignment['status'],
            'new_status'    => $newSt,
            'note'          => $note ?: null,
        ]);

        $updateData = ['status' => $newSt];
        if ($trackN) $updateData['tracking_number'] = $trackN;
        if ($note)   $updateData['notes']           = $note;
        DB::update('order_logistics', $updateData, 'id=?', [$aid]);

        // Sync orders table status
        $orderStatusMap = [
            'assigned'         => 'processing',
            'picked_up'        => 'processing',
            'in_transit'       => 'shipped',
            'out_for_delivery' => 'shipped',
            'delivered'        => 'delivered',
            'failed'           => 'processing',
            'returned'         => 'cancelled',
        ];
        if (isset($orderStatusMap[$newSt])) {
            DB::update('orders', ['status' => $orderStatusMap[$newSt]], 'id=?', [$assignment['order_id']]);
        }

        // Email customer on key status changes
        $notifyCustomer = ['picked_up','in_transit','out_for_delivery','delivered','failed'];
        if (in_array($newSt, $notifyCustomer, true) && !empty($assignment['cust_email'])) {
            $orderRow = DB::fetch('SELECT * FROM orders WHERE id=?', [$assignment['order_id']]);
            $orderRow['cust_name']  = $assignment['cust_name'];
            $orderRow['cust_email'] = $assignment['cust_email'];
            $items = DB::fetchAll(
                'SELECT oi.* FROM order_items oi WHERE oi.order_id=? AND oi.shop_id=?',
                [$assignment['order_id'], $assignment['shop_id']]
            );
            $trackUrl = ($assignment['is_guest'] && !empty($orderRow['guest_token']))
                ? BASE_URL . '/customer/track-order.php?order='
                  . urlencode($assignment['order_number'])
                  . '&token=' . urlencode($orderRow['guest_token'])
                : null;
            sendOrderStatusEmail(
                $orderRow, $items,
                $orderStatusMap[$newSt] ?? $newSt,
                $assignment['shop_name'],
                $trackUrl
            );
        }

        flash('Status updated to "' . ucwords(str_replace('_',' ',$newSt)) . '". Customer notified.', 'success');
    }
    redirect(BASE_URL . '/logistics/orders.php?status=' . urlencode($_POST['filter_status'] ?? ''));
}

$pageTitle = 'Orders'; $activeNav = 'orders';
include __DIR__ . '/header.php';

$statusFilter = trim($_GET['status'] ?? '');
$page  = max(1,(int)($_GET['page']??1)); $per = 25;
$where = ['ol.company_id=?']; $params = [$cid];
if ($statusFilter) { $where[] = 'ol.status=?'; $params[] = $statusFilter; }
$wStr = implode(' AND ', $where);

$total   = (int)DB::count("SELECT COUNT(*) FROM order_logistics ol WHERE $wStr", $params);
$orders  = DB::fetchAll(
    "SELECT ol.*, o.order_number, o.created_at order_date,
            COALESCE(u.name,o.guest_name) cust_name,
            s.shop_name
     FROM order_logistics ol
     JOIN orders o ON o.id=ol.order_id
     JOIN shops s  ON s.id=ol.shop_id
     LEFT JOIN users u ON u.id=o.user_id
     WHERE $wStr
     ORDER BY ol.assigned_at DESC
     LIMIT $per OFFSET " . (($page-1)*$per), $params
);
$allStatuses = ['assigned','picked_up','in_transit','out_for_delivery','delivered','failed','returned'];
?>
<div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px">
  <div>
    <h1 style="font-family:var(--ff-head);font-size:1.4rem;font-weight:700;color:var(--black);margin-bottom:3px">📦 Orders</h1>
    <p style="font-size:.82rem;color:var(--text-muted)"><?= number_format($total) ?> order<?= $total!==1?'s':''?></p>
  </div>
</div>

<div class="tab-bar" style="margin-bottom:14px">
  <a href="?" class="tab-btn <?= !$statusFilter?'active':'' ?>">All</a>
  <?php foreach ($allStatuses as $st): ?>
  <a href="?status=<?= urlencode($st) ?>" class="tab-btn <?= $statusFilter===$st?'active':'' ?>">
    <?= ucwords(str_replace('_',' ',$st)) ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if (empty($orders)): ?>
<div class="empty-state card" style="padding:40px"><span class="empty-icon">📦</span><p>No orders found.</p></div>
<?php else: ?>
<div class="card" style="padding:0">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Order #</th><th>Merchant</th><th>Customer</th><th>Tracking #</th><th>Update Status</th><th>Assigned</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td><a href="<?= BASE_URL ?>/logistics/order-detail.php?id=<?= $o['id'] ?>" style="font-weight:700;color:var(--blue);text-decoration:none"><?= e($o['order_number']) ?></a></td>
          <td style="font-size:.82rem"><?= e($o['shop_name']) ?></td>
          <td style="font-size:.82rem"><?= e($o['cust_name']?:'Guest') ?></td>
          <td style="font-size:.78rem;color:var(--text-muted)"><?= e($o['tracking_number']?:'—') ?></td>
          <td>
            <form method="POST" style="display:flex;align-items:center;gap:5px;flex-wrap:wrap">
              <input type="hidden" name="assignment_id" value="<?= $o['id'] ?>">
              <input type="hidden" name="filter_status" value="<?= e($statusFilter) ?>">
              <select name="status" class="sort-select" style="font-size:.76rem;padding:4px 8px"
                      onchange="this.form.submit()">
                <?php foreach ($allStatuses as $st): ?>
                <option value="<?= $st ?>" <?= $o['status']===$st?'selected':''?>>
                  <?= ucwords(str_replace('_',' ',$st)) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
          <td style="font-size:.76rem;color:var(--text-muted);white-space:nowrap">
            <?= date('M j, Y',strtotime($o['assigned_at'])) ?>
            <div style="font-size:.7rem"><?= date('g:i A',strtotime($o['assigned_at'])) ?></div>
          </td>
          <td>
            <a href="<?= BASE_URL ?>/logistics/order-detail.php?id=<?= $o['id'] ?>" class="btn btn-blue btn-xs">View →</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?= paginate($total, $per, $page, '?status='.urlencode($statusFilter)) ?>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
