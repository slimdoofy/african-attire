<?php
// customer/track-order.php — Public guest order tracking (no login needed)
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle  = 'Track Your Order';
$activePage = 'track';

$order = null;
$items = [];
$error = '';
$searched = false;

// Pre-fill from URL params (redirect from checkout)
$preOrder = $_GET['order'] ?? '';
$preToken = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($preOrder && $preToken)) {
    $searched = true;
    $oNum   = trim($_POST['order_number'] ?? $preOrder);
    $oEmail = trim($_POST['email']        ?? '');
    $oToken = trim($_POST['token']        ?? $preToken);

    if (!$oNum) {
        $error = 'Please enter your order number.';
    } else {
        // Guests: match by order number + (token OR email)
        // Logged-in users can also use this page with just order number
        if ($oToken) {
            $order = DB::fetch(
                "SELECT * FROM orders WHERE order_number=? AND guest_token=?",
                [$oNum, $oToken]);
        }
        if (!$order && $oEmail) {
            $order = DB::fetch(
                "SELECT * FROM orders WHERE order_number=? AND (guest_email=? OR user_id IN (SELECT id FROM users WHERE email=?))",
                [$oNum, $oEmail, $oEmail]);
        }
        // Logged-in users can view their own orders
        if (!$order && Auth::check()) {
            $order = DB::fetch(
                "SELECT * FROM orders WHERE order_number=? AND user_id=?",
                [$oNum, Auth::id()]);
        }
        if (!$order) {
            $error = 'Order not found. Please check your order number and email address.';
        } else {
            $items = DB::fetchAll(
                "SELECT oi.*, s.shop_name FROM order_items oi JOIN shops s ON s.id=oi.shop_id WHERE oi.order_id=?",
                [$order['id']]);
        }
    }
}

include __DIR__ . '/../includes/header_customer.php';

// Status to steps mapping
function statusToStep(string $status): int {
    switch ($status) {
        case 'pending':    return 1;
        case 'processing': return 2;
        case 'shipped':    return 3;
        case 'delivered':  return 4;
        default:           return 1;
    }
}
$statusColors = [
    'pending'    => ['bg'=>'#FFF8E1','border'=>'#FFE082','text'=>'#856404','icon'=>'⏳'],
    'processing' => ['bg'=>'#E3F2FD','border'=>'#90CAF9','text'=>'#0D47A1','icon'=>'🔄'],
    'shipped'    => ['bg'=>'#E8F5E9','border'=>'#A5D6A7','text'=>'#1B5E20','icon'=>'🚚'],
    'delivered'  => ['bg'=>'#E8F5E9','border'=>'#A5D6A7','text'=>'#1B5E20','icon'=>'✅'],
    'cancelled'  => ['bg'=>'#FFEBEE','border'=>'#EF9A9A','text'=>'#B71C1C','icon'=>'❌'],
];
?>

<div class="wrap-full" style="padding-top:14px;padding-bottom:28px">

  <div class="breadcrumb">
    <a href="<?= BASE_URL ?>/">Home</a><span class="sep">›</span><span class="cur">Track Order</span>
  </div>

  <!-- Page header -->
  <div style="margin-bottom:16px">
    <div class="ju-panel-head">
      <div class="ju-panel-title"><span class="st-icon">📦</span> Track Your Order</div>
    </div>
    <div style="background:#fff;border:1px solid var(--border-lt);border-radius:0 0 var(--r-md) var(--r-md);padding:14px">
      <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:14px">
        Enter your order number and email address to track your order status in real time.
      </p>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
        <div style="flex:1;min-width:200px">
          <label class="form-label">Order Number</label>
          <input type="text" name="order_number" class="form-control"
                 placeholder="e.g. AABC4F2505001"
                 value="<?= htmlspecialchars($_POST['order_number'] ?? $preOrder) ?>">
        </div>
        <div style="flex:1;min-width:200px">
          <label class="form-label">Email Address <span style="color:var(--text-muted);font-weight:400">(used at checkout)</span></label>
          <?php
          $loggedInEmail = Auth::check() ? (Auth::user()['email'] ?? '') : '';
          $emailValue    = $loggedInEmail ?: htmlspecialchars($_POST['email'] ?? '');
          ?>
          <input type="email" name="email" class="form-control"
                 placeholder="you@example.com"
                 value="<?= htmlspecialchars($emailValue) ?>"
                 <?= $loggedInEmail ? 'readonly style="background:var(--bg);color:var(--text-muted);cursor:not-allowed"' : '' ?>>
          <?php if ($loggedInEmail): ?>
          <div class="form-hint">Using your registered email address.</div>
          <?php endif; ?>
        </div>
        <?php if ($preToken): ?>
          <input type="hidden" name="token" value="<?= htmlspecialchars($preToken) ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-ju btn-lg">Track Order →</button>
      </form>
    </div>
  </div>

  <?php if ($order): ?>
  <?php
    $sc    = $statusColors[$order['status']] ?? $statusColors['pending'];
    $step  = statusToStep($order['status']);
    $name  = $order['guest_name'] ?? ($order['user_id']
              ? DB::fetch('SELECT name FROM users WHERE id=?',[$order['user_id']])['name'] ?? ''
              : '');
  ?>

  <!-- Status banner -->
  <div style="background:<?= $sc['bg'] ?>;border:1px solid <?= $sc['border'] ?>;border-radius:var(--r-lg);padding:14px 18px;margin-bottom:14px;display:flex;align-items:center;gap:12px">
    <div style="font-size:2rem"><?= $sc['icon'] ?></div>
    <div>
      <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;color:<?= $sc['text'] ?>;font-weight:700;margin-bottom:2px">Order Status</div>
      <div style="font-size:1.1rem;font-weight:800;font-family:var(--ff-head);color:<?= $sc['text'] ?>"><?= ucfirst($order['status']) ?></div>
    </div>
    <div style="margin-left:auto;text-align:right">
      <div style="font-size:.72rem;color:var(--text-muted)">Order Number</div>
      <div style="font-size:.95rem;font-weight:800;color:var(--black)"><?= htmlspecialchars($order['order_number']) ?></div>
    </div>
  </div>

  <!-- Progress track -->
  <?php if ($order['status'] !== 'cancelled'): ?>
  <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:18px 20px;margin-bottom:14px">
    <div style="font-size:.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px">Delivery Progress</div>
    <div style="display:flex;align-items:center;justify-content:space-between;position:relative">
      <!-- Progress line -->
      <div style="position:absolute;top:18px;left:10%;right:10%;height:3px;background:var(--bg);z-index:0">
        <div style="height:100%;background:var(--ju);width:<?= ($step-1)/3*100 ?>%;transition:width .4s"></div>
      </div>
      <?php foreach ([1=>'Order Placed',2=>'Processing',3=>'Shipped',4=>'Delivered'] as $i=>$lbl): ?>
      <div style="display:flex;flex-direction:column;align-items:center;gap:6px;z-index:1">
        <div style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;
             background:<?= $i<=$step?'var(--blue)':'#fff' ?>;color:<?= $i<=$step?'#fff':'var(--grey-3)' ?>;
             border:2px solid <?= $i<=$step?'var(--blue)':'var(--grey-2)' ?>">
          <?= $i < $step ? '✓' : ($i === $step ? '●' : $i) ?>
        </div>
        <div style="font-size:.68rem;font-weight:600;color:<?= $i<=$step?'var(--ju)':'var(--text-muted)' ?>;text-align:center;max-width:68px;line-height:1.2"><?= $lbl ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="order-detail-grid">

    <!-- Order items -->
    <div class="card">
      <div class="card-head">
        <div class="card-title">🛍️ Items Ordered</div>
        <span class="badge badge-muted"><?= count($items) ?> item<?= count($items)>1?'s':'' ?></span>
      </div>
      <?php foreach ($items as $it): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border-lt)">
        <div style="width:42px;height:48px;border-radius:var(--r-sm);background:var(--bg);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.2rem">👗</div>
        <div style="flex:1;min-width:0">
          <div style="font-size:.84rem;font-weight:600;color:var(--black)"><?= htmlspecialchars($it['product_name']) ?></div>
          <div style="font-size:.72rem;color:var(--text-muted)"><?= htmlspecialchars($it['shop_name']) ?><?= $it['size']?' · '.$it['size']:'' ?> · qty <?= $it['quantity'] ?></div>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <div style="font-size:.88rem;font-weight:700"><?= money($it['price']*$it['quantity']) ?></div>
          <?= statusBadge($it['status']) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Order info -->
    <div>
      <div class="card" style="margin-bottom:10px">
        <div class="card-title" style="margin-bottom:12px">📋 Order Details</div>
        <?php
        // Calculate subtotal from items
        $itemSubtotal = array_sum(array_map(function($i){ return $i['price']*$i['quantity']; }, $items));
        $delivFee     = isset($order['delivery_fee'])    ? (float)$order['delivery_fee']    : null;
        $discountAmt  = isset($order['discount_amount']) ? (float)$order['discount_amount'] : null;
        $discountCode = $order['discount_code'] ?? null;
        $rows = [
          'Customer'       => $name,
          'Order Date'     => date('M j, Y', strtotime($order['created_at'])),
          'Payment Method' => ucfirst($order['payment_method']),
          'Payment Status' => statusBadge($order['payment_status']),
        ];
        foreach ($rows as $k=>$v): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border-lt);font-size:.82rem">
          <span style="color:var(--text-muted)"><?= $k ?></span>
          <span><?= $v ?></span>
        </div>
        <?php endforeach; ?>

        <!-- Financial breakdown -->
        <div style="margin-top:4px">
          <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--border-lt);font-size:.82rem">
            <span style="color:var(--text-muted)">Subtotal</span>
            <span><?= money($itemSubtotal) ?></span>
          </div>
          <?php if ($delivFee !== null && $delivFee > 0): ?>
          <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--border-lt);font-size:.82rem">
            <span style="color:var(--text-muted)">🚚 Delivery Fee</span>
            <span style="color:var(--navy);font-weight:600"><?= money($delivFee) ?></span>
          </div>
          <?php endif; ?>
          <?php if ($discountAmt !== null && $discountAmt > 0): ?>
          <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--border-lt);font-size:.82rem">
            <span style="color:var(--green)">
              🏷 Discount<?= $discountCode ? ' <span style="font-size:.7rem;background:var(--green-pale);padding:1px 6px;border-radius:3px;font-weight:700">'.e($discountCode).'</span>' : '' ?>
            </span>
            <span style="color:var(--green);font-weight:700">-<?= money($discountAmt) ?></span>
          </div>
          <?php endif; ?>
          <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:.9rem;font-weight:800">
            <span>Total</span>
            <span style="color:var(--blue)"><?= money($order['total_amount']) ?></span>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-title" style="margin-bottom:10px">📍 Delivery Address</div>
        <p style="font-size:.82rem;color:var(--text-soft);white-space:pre-line;line-height:1.6"><?= htmlspecialchars($order['delivery_address']) ?></p>
      </div>
    </div>
  </div>

  <!-- Help -->
  <div style="background:var(--blue-pale);border:1px solid var(--blue-pale2);border-radius:var(--r-md);padding:12px 16px;margin-top:14px;font-size:.82rem;color:var(--blue)">
    💬 <strong>Need help?</strong> Contact us at <a href="mailto:<?= getSetting('site_email','hello@africanattire.com') ?>" style="font-weight:700"><?= getSetting('site_email','hello@africanattire.com') ?></a> with your order number.
  </div>

  <?php elseif ($searched && !$error): ?>
    <!-- handled above -->
  <?php endif; ?>

  <?php if (isset($order) && !in_array($order['status'], ['cancelled','disputed'])): ?>
  <div style="text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid var(--border-lt)">
    <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:8px">Problem with this order?</p>
    <a href="<?= BASE_URL ?>/customer/raise-dispute.php?order_id=<?= $order['id'] ?>"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border:1.5px solid #C62828;
              color:#C62828;border-radius:var(--r-sm);font-size:.84rem;font-weight:600;text-decoration:none">
      ⚖️ Raise a Dispute
    </a>
  </div>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
