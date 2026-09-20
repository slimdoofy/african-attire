<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/auth.php';
logRequire();
$la  = logAuth(); $cid = $la['company_id'];
$aid = (int)($_GET['id'] ?? 0);

$assignment = DB::fetch(
    "SELECT ol.*, o.order_number, o.created_at order_date,
            o.delivery_address, o.total_amount, o.payment_status,
            COALESCE(u.name,o.guest_name)   cust_name,
            COALESCE(u.email,o.guest_email) cust_email,
            CASE WHEN o.user_id IS NULL THEN 1 ELSE 0 END is_guest,
            o.guest_token, s.shop_name, mu.phone merchant_phone
     FROM order_logistics ol
     JOIN orders o ON o.id=ol.order_id
     JOIN shops s  ON s.id=ol.shop_id
     JOIN users mu ON mu.id=s.user_id
     LEFT JOIN users u ON u.id=o.user_id
     WHERE ol.id=? AND ol.company_id=?",
    [$aid, $cid]
);
if (!$assignment) { flash('Order not found.','error'); redirect(BASE_URL.'/logistics/orders.php'); }

$items = DB::fetchAll(
    'SELECT oi.* FROM order_items oi WHERE oi.order_id=? AND oi.shop_id=?',
    [$assignment['order_id'], $assignment['shop_id']]
);
$logs = DB::fetchAll(
    'SELECT * FROM logistics_status_log WHERE assignment_id=? ORDER BY changed_at DESC',
    [$aid]
);
$pageTitle = 'Order '.$assignment['order_number']; $activeNav = 'orders';
include __DIR__ . '/header.php';
$allStatuses = ['assigned','picked_up','in_transit','out_for_delivery','delivered','failed','returned'];
?>
<div style="margin-bottom:16px">
  <a href="<?= BASE_URL ?>/logistics/orders.php" style="font-size:.8rem;color:var(--text-muted);text-decoration:none">← Back to Orders</a>
  <h1 style="font-family:var(--ff-head);font-size:1.3rem;font-weight:700;color:var(--black);margin-top:6px">
    📦 Order <?= e($assignment['order_number']) ?>
  </h1>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:14px;align-items:start">

  <!-- Left -->
  <div>
    <!-- Items -->
    <div class="card" style="margin-bottom:14px">
      <div class="card-head"><div class="card-title">🛍 Items</div></div>
      <?php foreach ($items as $it): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:9px 0;border-bottom:1px solid var(--border-lt)">
        <div style="flex:1">
          <div style="font-weight:600;font-size:.86rem"><?= e($it['product_name']) ?></div>
          <div style="font-size:.74rem;color:var(--text-muted)"><?= $it['size']?'Size: '.$it['size'].' · ':'' ?>Qty: <?= $it['quantity'] ?></div>
        </div>
        <div style="font-weight:700"><?= money($it['price']*$it['quantity']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Delivery address (logistics needs full address) -->
    <div class="card" style="margin-bottom:14px">
      <div class="card-head">
        <div class="card-title">📍 Full Delivery Address</div>
        <span style="font-size:.7rem;color:var(--green);font-weight:600">🔒 Logistics Only</span>
      </div>
      <p style="font-size:.86rem;color:var(--text-soft);line-height:1.75;white-space:pre-line"><?= e($assignment['delivery_address']) ?></p>
    </div>

    <!-- Update status form -->
    <div class="card" style="margin-bottom:14px;border-top:3px solid var(--navy)">
      <div class="card-head"><div class="card-title">🔄 Update Delivery Status</div></div>
      <form method="POST" action="<?= BASE_URL ?>/logistics/orders.php">
        <input type="hidden" name="assignment_id" value="<?= $aid ?>">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">New Status *</label>
            <select name="status" class="form-control" required>
              <?php foreach ($allStatuses as $st): ?>
              <option value="<?= $st ?>" <?= $assignment['status']===$st?'selected':''?>>
                <?= ucwords(str_replace('_',' ',$st)) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Tracking Number</label>
            <input type="text" name="tracking_number" class="form-control"
                   value="<?= e($assignment['tracking_number']?:'') ?>"
                   placeholder="Optional tracking code">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Note <span style="font-weight:400;color:var(--text-muted)">(optional — visible in audit log)</span></label>
          <input type="text" name="note" class="form-control" placeholder="e.g. Customer not at home — attempted delivery">
        </div>
        <button type="submit" class="btn btn-ju btn-sm">Update Status &amp; Notify Customer</button>
        <p style="font-size:.74rem;color:var(--text-muted);margin-top:6px">Customer receives an automatic email on key status changes.</p>
      </form>
    </div>

    <!-- Status log -->
    <?php if (!empty($logs)): ?>
    <div class="card">
      <div class="card-head"><div class="card-title">📋 Status History</div></div>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach ($logs as $log): ?>
        <div style="display:flex;gap:10px;align-items:flex-start;font-size:.82rem">
          <div style="flex-shrink:0;width:10px;height:10px;border-radius:50%;background:var(--ju);margin-top:5px"></div>
          <div>
            <div style="font-weight:600"><?= ucwords(str_replace('_',' ',$log['old_status'])) ?> → <?= ucwords(str_replace('_',' ',$log['new_status'])) ?></div>
            <div style="color:var(--text-muted);font-size:.76rem"><?= e($log['user_name']) ?> · <?= date('M j, Y g:i A',strtotime($log['changed_at'])) ?></div>
            <?php if($log['note']): ?><div style="color:var(--text-soft);margin-top:2px"><?= e($log['note']) ?></div><?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right sidebar -->
  <div>
    <div class="card" style="margin-bottom:12px">
      <div class="card-title" style="margin-bottom:10px">📋 Assignment Info</div>
      <?php foreach ([
        'Current Status' => '<span class="badge badge-info">'.ucwords(str_replace('_',' ',$assignment['status'])).'</span>',
        'Tracking #'     => e($assignment['tracking_number']?:'Not set'),
        'Assigned'       => date('M j, Y g:i A',strtotime($assignment['assigned_at'])),
        'Merchant'       => e($assignment['shop_name']),
        'Distance'       => $assignment['distance_km']
            ? number_format($assignment['distance_km'],1).' km'
            : '<span style="color:var(--text-muted)">—</span>',
        'Calc. Method'   => $assignment['fee_method']
            ? ucwords(str_replace('_',' ',$assignment['fee_method']))
            : '—',
        'Customer'       => e($assignment['cust_name']?:'Guest'),
        'Order Total'    => moneyNgn((float)$assignment['total_amount']),
        'Logistics Fee'  => $assignment['fee_ngn']
            ? ('<strong style="color:var(--navy)">'.moneyNgn((float)$assignment['fee_ngn']).'</strong>')
            : '<span style="color:var(--text-muted)">Calculating…</span>',
      ] as $k=>$v): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border-lt);font-size:.82rem">
        <span style="color:var(--text-muted)"><?=$k?></span><span><?=$v?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>
<?php include __DIR__ . '/footer.php'; ?>
