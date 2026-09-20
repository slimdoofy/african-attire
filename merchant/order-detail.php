<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Order Details';
$activeNav = 'orders';
include __DIR__ . '/../includes/header_merchant.php';

$sid = $_shop['id'];
$oid = (int)($_GET['id'] ?? 0);

// Fetch the order — must contain at least one item belonging to this shop
$order = DB::fetch(
    "SELECT o.*,
            COALESCE(u.name,  o.guest_name)  AS cust_name,
            COALESCE(u.email, o.guest_email) AS cust_email,
            COALESCE(u.phone, o.guest_phone) AS cust_phone,
            CASE WHEN o.user_id IS NULL THEN 1 ELSE 0 END AS is_guest
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     WHERE o.id = ?
       AND EXISTS (
           SELECT 1 FROM order_items oi
           WHERE oi.order_id = o.id AND oi.shop_id = ?
       )",
    [$oid, $sid]
);

if (!$order) {
    flash('Order not found.', 'error');
    redirect(BASE_URL . '/merchant/orders.php');
}

// Only THIS shop's items
$items = DB::fetchAll(
    'SELECT oi.* FROM order_items oi
     WHERE oi.order_id = ? AND oi.shop_id = ?
     ORDER BY oi.id',
    [$oid, $sid]
);

$shopRevenue = array_sum(array_map(function($i) { return $i['price'] * $i['quantity']; }, $items));

$statusColors = [
    'pending'    => ['bg'=>'#FFF8E1','border'=>'#FFE082','text'=>'#856404','icon'=>'⏳'],
    'processing' => ['bg'=>'#E3F2FD','border'=>'#90CAF9','text'=>'#0D47A1','icon'=>'🔄'],
    'shipped'    => ['bg'=>'#E8F5E9','border'=>'#A5D6A7','text'=>'#1B5E20','icon'=>'🚚'],
    'delivered'  => ['bg'=>'#E8F5E9','border'=>'#A5D6A7','text'=>'#1B5E20','icon'=>'✅'],
    'cancelled'  => ['bg'=>'#FFEBEE','border'=>'#EF9A9A','text'=>'#B71C1C','icon'=>'❌'],
];
$sc = $statusColors[$order['status']] ?? $statusColors['pending'];
?>

<div class="m-page-head">
  <div>
    <a href="<?= BASE_URL ?>/merchant/orders.php"
       style="font-size:.8rem;color:var(--text-muted);text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px">
      ← Back to Orders
    </a>
    <div class="m-page-title">📦 Order <?= e($order['order_number']) ?></div>
    <div class="m-page-sub">Placed <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?></div>
  </div>
  <!-- Quick status update -->
  <form method="POST" action="<?= BASE_URL ?>/merchant/orders.php"
        style="display:flex;align-items:center;gap:8px">
    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
    <input type="hidden" name="redirect_to" value="detail">
    <label style="font-size:.8rem;color:var(--text-muted);font-weight:600">Update Status:</label>
    <select name="status" class="form-control" style="width:auto;font-size:.84rem">
      <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
      <option value="<?= $s ?>" <?= $order['status']===$s?'selected':''?>>
        <?= ucfirst($s) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-ju btn-sm">Save</button>
  </form>
</div>

<!-- Status banner -->
<div style="background:<?= $sc['bg'] ?>;border:1px solid <?= $sc['border'] ?>;
            border-radius:var(--r-lg);padding:12px 18px;margin-bottom:16px;
            display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
  <div style="display:flex;align-items:center;gap:10px">
    <span style="font-size:1.8rem"><?= $sc['icon'] ?></span>
    <div>
      <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:<?= $sc['text'] ?>;font-weight:700">Order Status</div>
      <div style="font-size:1rem;font-weight:800;color:<?= $sc['text'] ?>"><?= ucfirst($order['status']) ?></div>
    </div>
  </div>
  <div style="display:flex;gap:20px;flex-wrap:wrap">
    <div style="text-align:right">
      <div style="font-size:.68rem;color:var(--text-muted)">Your Revenue</div>
      <div style="font-weight:800;font-size:1rem;color:var(--blue)"><?= money($shopRevenue) ?></div>
    </div>
    <div style="text-align:right">
      <div style="font-size:.68rem;color:var(--text-muted)">Payment</div>
      <div style="font-weight:600;margin-top:2px"><?= statusBadge($order['payment_status']) ?></div>
    </div>
  </div>
</div>

<div class="order-detail-grid">

  <!-- Items + Address -->
  <div>

    <!-- Items this merchant sold -->
    <div class="card" style="margin-bottom:14px">
      <div class="card-head">
        <div class="card-title">🛍 Your Items in This Order</div>
        <span class="badge badge-muted"><?= count($items) ?> item<?= count($items)>1?'s':''?></span>
      </div>
      <?php foreach ($items as $it): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-lt)">
        <div style="width:52px;height:58px;border-radius:var(--r-sm);background:var(--bg);
                    flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.4rem">
          👗
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:.88rem;font-weight:600;color:var(--black)"><?= e($it['product_name']) ?></div>
          <div style="font-size:.76rem;color:var(--text-muted)">
            <?= $it['size'] ? 'Size: '.$it['size'].' · ' : '' ?>Qty: <?= $it['quantity'] ?>
          </div>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <div style="font-size:.9rem;font-weight:700;color:var(--black)"><?= money($it['price'] * $it['quantity']) ?></div>
          <div style="font-size:.72rem;color:var(--text-muted)"><?= money($it['price']) ?> each</div>
        </div>
      </div>
      <?php endforeach; ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;font-size:.9rem;font-weight:800">
        <span>Your Subtotal</span>
        <span style="color:var(--blue)"><?= money($shopRevenue) ?></span>
      </div>
    </div>

    <!-- Assign to logistics -->
    <?php
    $activeLogistics  = DB::fetchAll(
        "SELECT id, company_name FROM logistics_companies WHERE status='active' ORDER BY company_name"
    );
    $existingAssign = DB::fetch(
        'SELECT ol.*, lc.company_name FROM order_logistics ol
         JOIN logistics_companies lc ON lc.id=ol.company_id
         WHERE ol.order_id=? AND ol.shop_id=?',
        [$oid, $sid]
    );
    ?>
    <div class="card" style="margin-bottom:14px;border-top:3px solid var(--navy)">
      <div class="card-head">
        <div class="card-title">🚚 Logistics Assignment</div>
        <?php if($existingAssign): ?>
        <span class="badge badge-info"><?= ucwords(str_replace('_',' ',$existingAssign['status'])) ?></span>
        <?php endif; ?>
      </div>
      <?php if ($existingAssign): ?>
        <div style="font-size:.86rem;color:var(--text);display:flex;flex-direction:column;gap:8px">
          <div style="font-weight:600">📦 Assigned to: <?= e($existingAssign['company_name']) ?></div>
          <?php if($existingAssign['tracking_number']): ?>
          <div style="color:var(--text-muted)">Tracking #: <?= e($existingAssign['tracking_number']) ?></div>
          <?php endif; ?>
          <?php if($existingAssign['distance_km']): ?>
          <div style="background:var(--bg);border-radius:var(--r-sm);padding:8px 10px">
            <div style="font-size:.7rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">Logistics Fee Breakdown</div>
            <div style="display:flex;gap:16px;flex-wrap:wrap">
              <div><span style="color:var(--text-muted)">Distance:</span> <strong><?= number_format($existingAssign['distance_km'],1) ?> km</strong></div>
              <div><span style="color:var(--text-muted)">Fee:</span> <strong style="color:var(--navy)"><?= moneyNgn((float)$existingAssign['fee_ngn']) ?></strong> / <strong style="color:var(--green)"><?= moneyUsd((float)$existingAssign['fee_ngn']) ?></strong></div>
              <div><span style="color:var(--text-muted)">Method:</span> <span class="badge badge-muted" style="font-size:.64rem"><?= ucwords(str_replace('_',' ',$existingAssign['fee_method'])) ?></span></div>
            </div>
          </div>
          <?php elseif($existingAssign['fee_ngn']): ?>
          <div><span style="color:var(--text-muted)">Fee:</span> <strong style="color:var(--navy)"><?= moneyNgn((float)$existingAssign['fee_ngn']) ?></strong> (base fare)</div>
          <?php endif; ?>
          <?php if($existingAssign['notes']): ?>
          <div style="color:var(--text-muted)"><?= e($existingAssign['notes']) ?></div>
          <?php endif; ?>
        </div>
      <?php elseif (empty($activeLogistics)): ?>
        <p style="font-size:.82rem;color:var(--text-muted)">No active logistics partners available. Contact your platform admin.</p>
      <?php else: ?>
        <form method="POST" action="<?= BASE_URL ?>/merchant/assign-logistics.php">
          <input type="hidden" name="order_id" value="<?= $oid ?>">
          <div class="form-group">
            <label class="form-label">Select Logistics Partner</label>
            <select name="company_id" class="form-control" required>
              <option value="">— Choose logistics company —</option>
              <?php foreach ($activeLogistics as $lg): ?>
              <option value="<?= $lg['id'] ?>"><?= e($lg['company_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Tracking Number <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
            <input type="text" name="tracking_number" class="form-control" placeholder="e.g. AA-2025-001234">
          </div>
          <div class="form-group">
            <label class="form-label">Notes <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
            <input type="text" name="notes" class="form-control" placeholder="Any special delivery instructions">
          </div>
          <button type="submit" class="btn btn-navy btn-sm btn-full">Assign to Logistics →</button>
        </form>
      <?php endif; ?>
    </div>

    <!-- Delivery: city & country only — full address hidden from merchants -->
    <div class="card">
      <div class="card-head">
        <div class="card-title">📍 Delivery Destination</div>
        <span style="font-size:.7rem;color:var(--text-muted)">🔒 Full address hidden</span>
      </div>
      <?php
        // Parse city and country from the delivery address.
        // Addresses are free-text; we take the last non-empty line as country/state
        // and the second-to-last as the city.
        $rawLines = array_values(array_filter(
            array_map('trim', preg_split('/
?
|,/', $order['delivery_address'] ?? ''))
        ));
        $total    = count($rawLines);
        $city     = $total >= 2 ? $rawLines[$total - 2] : ($total === 1 ? $rawLines[0] : '');
        $country  = $total >= 1 ? $rawLines[$total - 1] : '';
      ?>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php if ($city): ?>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="font-size:.76rem;font-weight:600;color:var(--text-muted);width:52px;flex-shrink:0">City</span>
          <span style="font-size:.9rem;font-weight:600;color:var(--black)"><?= e($city) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($country): ?>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="font-size:.76rem;font-weight:600;color:var(--text-muted);width:52px;flex-shrink:0">Region</span>
          <span style="font-size:.9rem;font-weight:600;color:var(--black)"><?= e($country) ?></span>
        </div>
        <?php endif; ?>
      </div>
      <div style="margin-top:10px;font-size:.75rem;color:var(--text-muted);
                  background:var(--bg);border-radius:var(--r-sm);padding:7px 10px;line-height:1.5">
        💡 Full delivery address is handled by the platform. Update your order status as you process and ship.
      </div>
    </div>

  </div>

  <!-- Customer info + Order meta -->
  <div>

    <div class="card" style="margin-bottom:12px">
      <div class="card-head">
        <div class="card-title">👤 Customer</div>
        <span style="font-size:.7rem;color:var(--text-muted)">🔒 PII protected</span>
      </div>
      <div class="alert alert-info" style="font-size:.76rem;margin-bottom:10px;padding:7px 10px">
        Customer contact details are protected. Use your Merchant Hub to communicate via orders.
      </div>
      <?php
        // Parse only city and country — never show the full address in merchant view
        $rawLinesC = array_values(array_filter(
            array_map('trim', preg_split('/
?
|,/', $order['delivery_address'] ?? ''))
        ));
        $totalC   = count($rawLinesC);
        $cityOnly = $totalC >= 2 ? $rawLinesC[$totalC - 2] : ($totalC === 1 ? $rawLinesC[0] : '');
        $countryOnly = $totalC >= 1 ? $rawLinesC[$totalC - 1] : '';
        $locationDisplay = implode(', ', array_filter([$cityOnly, $countryOnly]));
        $rows = [
          'Name'     => e($order['cust_name'] ?: '—'),
          'City'     => e($cityOnly ?: '—'),
          'Region'   => e($countryOnly ?: '—'),
          'Type'     => $order['is_guest']
              ? '<span class="badge badge-muted">👤 Guest</span>'
              : '<span class="badge badge-info">✓ Member</span>',
        ];
      ?>
      <?php foreach ($rows as $k=>$v): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--border-lt);font-size:.83rem">
        <span style="color:var(--text-muted);font-weight:500"><?= $k ?></span>
        <span><?= $v ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <div class="card-title" style="margin-bottom:12px">📋 Order Info</div>
      <?php $meta = [
        'Order #'    => '<strong style="color:var(--blue)">'.e($order['order_number']).'</strong>',
        'Date'       => date('M j, Y', strtotime($order['created_at'])),
        'Time'       => date('g:i A', strtotime($order['created_at'])),
        'Payment'    => ucfirst($order['payment_method'] ?? 'Paystack'),
        'Ref'        => '<span style="font-size:.74rem;color:var(--text-muted)">'.e($order['payment_reference'] ?? '—').'</span>',
      ]; ?>
      <?php foreach ($meta as $k=>$v): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border-lt);font-size:.82rem">
        <span style="color:var(--text-muted)"><?= $k ?></span>
        <span><?= $v ?></span>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer_merchant.php'; ?>
