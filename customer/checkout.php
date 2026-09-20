<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$isGuest = !Auth::check();

function guestSid(): string {
    Auth::start();
    if (empty($_SESSION['guest_cart_id'])) $_SESSION['guest_cart_id'] = bin2hex(random_bytes(16));
    return $_SESSION['guest_cart_id'];
}
$sid = guestSid();

if ($isGuest) {
    $cart = DB::fetchAll("
        SELECT ci.id cart_row_id, ci.product_id, ci.quantity, ci.size,
               p.name, p.price, p.quantity stock, s.shop_name, s.id shop_id,
               (SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) img
        FROM guest_cart_items ci JOIN products p ON p.id=ci.product_id JOIN shops s ON s.id=p.shop_id
        WHERE ci.session_id=? AND p.status='approved' ORDER BY ci.added_at DESC", [$sid]);
} else {
    $cart = DB::fetchAll("
        SELECT ci.id cart_row_id, p.id product_id, ci.quantity, ci.size,
               p.name, p.price, p.quantity stock, s.shop_name, s.id shop_id,
               (SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) img
        FROM cart_items ci JOIN products p ON p.id=ci.product_id JOIN shops s ON s.id=p.shop_id
        WHERE ci.user_id=? AND p.status='approved' ORDER BY ci.added_at DESC", [Auth::id()]);
    $profile = DB::fetch('SELECT * FROM customer_profiles WHERE user_id=?', [Auth::id()]);
}

if (empty($cart)) { flash('Your cart is empty.','error'); redirect(BASE_URL.'/customer/shop.php'); }
$subtotal = array_sum(array_map(function($i){ return $i['price']*$i['quantity']; }, $cart));

// Unique shop IDs in cart (for delivery fee calculation origin)
$shopIds = array_values(array_unique(array_column($cart, 'shop_id')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $addr      = trim($_POST['address']          ?? '');
    $delivFee  = (float)($_POST['delivery_fee']  ?? 0);
    $gName     = trim($_POST['guest_name']        ?? '');
    $gEmail    = trim($_POST['guest_email']       ?? '');
    $gPhone    = trim($_POST['guest_phone']       ?? '');
    $errs = [];
    if (!$addr) $errs[] = 'Delivery address is required.';
    if ($isGuest) {
        if (!$gName) $errs[] = 'Full name is required.';
        if (!filter_var($gEmail, FILTER_VALIDATE_EMAIL)) $errs[] = 'A valid email address is required.';
    }
    if ($errs) { foreach ($errs as $e) flash($e,'error'); redirect(BASE_URL.'/customer/checkout.php'); }
    Auth::start();
    $discountId   = (int)($_POST['discount_id']     ?? 0);
    $discountCode = trim($_POST['discount_code']     ?? '');
    $discountAmt  = (float)($_POST['discount_amount'] ?? 0);
    $discountAmt  = max(0, min($discountAmt, $subtotal));   // clamp to subtotal
    $total = max(0, $subtotal - $discountAmt) + $delivFee;
    $_SESSION['pending_order'] = [
        'cart'            => $cart,
        'subtotal'        => $subtotal,
        'delivery_fee'    => $delivFee,
        'discount_id'     => $discountId ?: null,
        'discount_code'   => $discountCode ?: null,
        'discount_amount' => $discountAmt > 0 ? $discountAmt : null,
        'total'           => $total,
        'address'      => $addr,
        'is_guest'     => $isGuest,
        'guest_name'   => $gName,
        'guest_email'  => $gEmail,
        'guest_phone'  => $gPhone,
        'user_id'      => $isGuest ? null : Auth::id(),
        'session_id'   => $isGuest ? $sid : null,
        'created_at'   => time(),
    ];
    redirect(BASE_URL.'/customer/pay.php');
}

$pageTitle='Checkout'; $activePage='shop';
include __DIR__.'/../includes/header_customer.php';
?>
<div class="wrap-full" style="padding-top:14px;padding-bottom:28px">
  <div class="breadcrumb">
    <a href="<?=BASE_URL?>/">Home</a><span class="sep">›</span>
    <a href="<?=BASE_URL?>/customer/shop.php">Shop</a><span class="sep">›</span>
    <span class="cur">Checkout</span>
  </div>

  <div class="steps" style="margin-bottom:18px;max-width:480px">
    <div class="step done"><div class="step-circle">✓</div><div class="step-label">Cart</div></div>
    <div class="step curr"><div class="step-circle">2</div><div class="step-label">Details</div></div>
    <div class="step"><div class="step-circle">3</div><div class="step-label">Payment</div></div>
    <div class="step"><div class="step-circle">4</div><div class="step-label">Done</div></div>
  </div>

  <?php if ($isGuest): ?>
  <div class="alert alert-info" style="margin-bottom:14px">
    🛍️ Checking out as a <strong>guest</strong>.
    <a href="<?=BASE_URL?>/customer/login.php" style="font-weight:700;margin-left:6px">Sign in instead →</a>
  </div>
  <?php endif; ?>

  <div class="checkout-layout">
    <div>
      <form method="POST" id="checkout-form">
        <!-- Hidden: delivery fee set by AJAX -->
        <input type="hidden" name="delivery_fee" id="delivery-fee-input" value="0">

        <?php if ($isGuest): ?>
        <div class="checkout-step">
          <div class="checkout-step-head"><div class="step-num">1</div><h3 style="margin:0;font-size:.95rem">Your Details</h3></div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" name="guest_name" class="form-control" required placeholder="Emeka Obi" value="<?=e($_POST['guest_name']??'')?>">
            </div>
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input type="tel" name="guest_phone" class="form-control" placeholder="+234 801 234 5678" value="<?=e($_POST['guest_phone']??'')?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address * <span style="font-weight:400;color:var(--text-muted)">(for receipt &amp; tracking)</span></label>
            <input type="email" name="guest_email" class="form-control" required placeholder="you@example.com" value="<?=e($_POST['guest_email']??'')?>">
          </div>
        </div>
        <?php else: ?>
        <div class="checkout-step">
          <div class="checkout-step-head"><div class="step-num">1</div><h3 style="margin:0;font-size:.95rem">Your Details</h3></div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Name</label><input type="text" class="form-control" value="<?=e(Auth::user()['name'])?>" readonly style="background:var(--bg)"></div>
            <div class="form-group"><label class="form-label">Email</label><input type="text" class="form-control" value="<?=e(Auth::user()['email'])?>" readonly style="background:var(--bg)"></div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Delivery address + fee -->
        <div class="checkout-step">
          <div class="checkout-step-head"><div class="step-num">2</div><h3 style="margin:0;font-size:.95rem">Delivery Address</h3></div>
          <div class="form-group">
            <label class="form-label">Full Delivery Address *</label>
            <textarea name="address" id="delivery-address" class="form-control" rows="3" required
                      placeholder="House/flat number, Street, Estate, Area, City, State"
                      oninput="scheduleDeliveryCalc()"><?=e((!$isGuest&&isset($profile))?($profile['address_line1']??''):'')?></textarea>
          </div>

          <!-- Delivery fee display -->
          <div id="delivery-fee-box"
               style="display:none;background:var(--bg);border:1px solid var(--border-lt);
                      border-radius:var(--r-md);padding:12px 14px;margin-top:10px">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
              <div>
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                            letter-spacing:.07em;color:var(--text-muted);margin-bottom:4px">
                  📍 Estimated Delivery Fee
                </div>
                <div id="fee-amount"
                     style="font-family:var(--ff-head);font-size:1.1rem;font-weight:800;color:var(--navy)">
                  Calculating…
                </div>
                <div id="fee-detail"
                     style="font-size:.72rem;color:var(--text-muted);margin-top:3px"></div>
              </div>
              <div id="fee-spinner"
                   style="font-size:1.2rem;display:none">⏳</div>
            </div>
          </div>

          <div id="fee-calculating"
               style="display:none;margin-top:8px;font-size:.78rem;color:var(--text-muted)">
            ⏳ Calculating delivery fee…
          </div>
          <div id="fee-error"
               style="display:none;margin-top:8px;font-size:.78rem;color:var(--text-muted)">
          </div>
        </div>

        <!-- Payment -->
        <div class="checkout-step">
          <div class="checkout-step-head"><div class="step-num">3</div><h3 style="margin:0;font-size:.95rem">Payment</h3></div>
          <div style="background:var(--blue-pale);border:1px solid var(--blue-pale2);border-radius:var(--r-md);padding:12px 14px;margin-bottom:12px">
            <div style="display:flex;align-items:center;gap:10px">
              <span style="font-size:1.3rem">💳</span>
              <div>
                <div style="font-size:.86rem;font-weight:700;color:var(--black)">Pay securely via Paystack</div>
                <div style="font-size:.76rem;color:var(--text-muted)">Card · Bank Transfer · USSD. <strong style="color:var(--black)">No payment taken until you confirm.</strong></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Discount code section -->
        <div class="checkout-step">
          <div class="checkout-step-head"><div class="step-num" style="background:var(--orange)">🏷</div><h3 style="margin:0;font-size:.95rem">Discount Code <span style="font-weight:400;color:var(--text-muted)">(optional)</span></h3></div>
          <div style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap">
            <input type="text" id="discount-input" class="form-control"
                   placeholder="Enter discount code" style="flex:1;min-width:160px;text-transform:uppercase"
                   oninput="this.value=this.value.toUpperCase()">
            <button type="button" class="btn btn-ghost btn-sm" onclick="applyDiscount()"
                    id="discount-btn" style="flex-shrink:0;padding:10px 16px">Apply</button>
          </div>
          <div id="discount-msg" style="margin-top:6px;font-size:.78rem;display:none"></div>
          <!-- Hidden fields carried to pay.php -->
          <input type="hidden" name="discount_id"     id="discount-id-val"     value="">
          <input type="hidden" name="discount_code"   id="discount-code-val"   value="">
          <input type="hidden" name="discount_amount" id="discount-amount-val" value="0">
        </div>

        <button type="submit" class="btn btn-ju btn-full btn-lg" id="pay-btn" style="font-size:1rem;padding:13px">
          Continue to Payment — <span id="total-display"><?=money($subtotal)?></span> →
        </button>
        <p style="text-align:center;font-size:.74rem;color:var(--text-muted);margin-top:7px">
          🔒 Payments processed securely by Paystack.
        </p>
      </form>
    </div>

    <!-- Order summary sidebar -->
    <div style="position:sticky;top:72px">
      <div class="card">
        <div class="card-head">
          <div class="card-title">🛒 Order Summary</div>
          <span class="badge badge-blue"><?=count($cart)?> item<?=count($cart)>1?'s':''?></span>
        </div>
        <?php foreach ($cart as $item): ?>
        <div style="display:flex;gap:10px;margin-bottom:10px;align-items:center">
          <div style="width:48px;height:52px;border-radius:var(--r-sm);overflow:hidden;background:var(--bg);flex-shrink:0">
            <?php if ($item['img']): ?>
              <img src="<?=imgUrl($item['img'])?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.2rem">👗</div>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:.8rem;font-weight:600;color:var(--black);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?=e($item['name'])?></div>
            <div style="font-size:.72rem;color:var(--text-muted)"><?=e($item['shop_name'])?><?=$item['size']?' · '.$item['size']:''?> · ×<?=$item['quantity']?></div>
          </div>
          <div style="font-size:.86rem;font-weight:700;flex-shrink:0"><?=money($item['price']*$item['quantity'])?></div>
        </div>
        <?php endforeach; ?>
        <div class="divider"></div>
        <div class="flex-between text-sm" style="margin-bottom:5px">
          <span style="color:var(--text-muted)">Subtotal</span>
          <span><?=money($subtotal)?></span>
        </div>
        <div class="flex-between text-sm" style="margin-bottom:5px" id="delivery-row">
          <span style="color:var(--text-muted)">Delivery</span>
          <span id="sidebar-fee" style="color:var(--text-muted);font-style:italic">Enter address</span>
        </div>
        <div id="discount-sidebar-row" style="display:none" class="flex-between text-sm" style="margin-bottom:5px">
          <span style="color:var(--green)">🏷 Discount</span>
          <span id="sidebar-discount" style="color:var(--green);font-weight:600"></span>
        </div>
        <div class="divider"></div>
        <div class="flex-between" style="font-size:1.05rem;font-weight:800">
          <span>Total</span>
          <span style="color:var(--blue)" id="sidebar-total"><?=money($subtotal)?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var SHOP_IDS  = <?= json_encode($shopIds) ?>;
var DISCOUNT_AMT = 0;
var DISCOUNT_ID  = 0;
var DISCOUNT_CODE= '';
var SUBTOTAL  = <?= $subtotal ?>;
var CURR_SYM  = '<?= activeCurrency() === 'usd' ? '$' : '₦' ?>';
var USD_RATE  = <?= usdRate() ?>;
var IS_USD    = <?= activeCurrency() === 'usd' ? 'true' : 'false' ?>;
var calcTimer = null;

function scheduleDeliveryCalc() {
  clearTimeout(calcTimer);
  var addr = document.getElementById('delivery-address').value.trim();
  if (addr.length < 10) {
    // Address too short — show "enter address"
    document.getElementById('sidebar-fee').textContent = 'Enter address';
    document.getElementById('sidebar-fee').style.fontStyle = 'italic';
    document.getElementById('delivery-fee-box').style.display = 'none';
    document.getElementById('delivery-fee-input').value = '0';
    updateTotals(0);
    return;
  }
  document.getElementById('fee-calculating').style.display = 'block';
  document.getElementById('delivery-fee-box').style.display = 'none';
  document.getElementById('fee-error').style.display = 'none';
  calcTimer = setTimeout(function(){ calcDeliveryFee(addr); }, 900);
}

function calcDeliveryFee(addr) {
  fetch(BASE_URL + '/api/delivery-fee.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ address: addr, shop_ids: SHOP_IDS })
  })
  .then(function(r){ return r.json(); })
  .then(function(d) {
    document.getElementById('fee-calculating').style.display = 'none';
    if (!d.ok) {
      document.getElementById('fee-error').textContent = '⚠️ Could not calculate delivery fee.';
      document.getElementById('fee-error').style.display = 'block';
      return;
    }
    var feeNgn = parseFloat(d.fee_ngn) || 0;
    var dispFee = IS_USD
      ? ('$' + (feeNgn / USD_RATE).toFixed(2))
      : ('₦' + parseFloat(feeNgn).toLocaleString('en-NG', {minimumFractionDigits:2, maximumFractionDigits:2}));

    // Update hidden input
    document.getElementById('delivery-fee-input').value = feeNgn.toFixed(2);

    // Show the fee box
    var box = document.getElementById('delivery-fee-box');
    box.style.display = 'block';
    document.getElementById('fee-amount').textContent = dispFee;
    var detail = '';
    if (d.distance_km) {
      detail = d.origin_city + ' → ' + d.dest_city + ' ≈ ' + parseFloat(d.distance_km).toFixed(0) + ' km road est.';
    } else {
      detail = d.breakdown;
    }
    document.getElementById('fee-detail').textContent = detail;

    // Update sidebar
    document.getElementById('sidebar-fee').textContent = dispFee;
    document.getElementById('sidebar-fee').style.color = 'var(--black)';
    document.getElementById('sidebar-fee').style.fontStyle = 'normal';
    document.getElementById('sidebar-fee').style.fontWeight = '600';

    updateTotals(feeNgn);
  })
  .catch(function() {
    document.getElementById('fee-calculating').style.display = 'none';
    document.getElementById('fee-error').textContent = '⚠️ Delivery fee unavailable — will be calculated at confirmation.';
    document.getElementById('fee-error').style.display = 'block';
  });
}

function fmtMoney(ngn) {
  return IS_USD
    ? ('$' + (ngn / USD_RATE).toFixed(2))
    : ('₦' + ngn.toLocaleString('en-NG', {minimumFractionDigits:2, maximumFractionDigits:2}));
}

function updateTotals(feeNgn) {
  var discounted = Math.max(0, SUBTOTAL - DISCOUNT_AMT);
  var total = discounted + feeNgn;
  var dispTotal = fmtMoney(total);
  document.getElementById('total-display').textContent = dispTotal;
  document.getElementById('sidebar-total').textContent = dispTotal;
  document.getElementById('pay-btn').innerHTML =
    'Continue to Payment — ' + dispTotal + ' →';
  // Delivery fee hidden input (recalc ensures correct total)
  document.getElementById('delivery-fee-input').value = feeNgn.toFixed(2);
}

function applyDiscount() {
  var code = document.getElementById('discount-input').value.trim();
  var msg  = document.getElementById('discount-msg');
  if (!code) { showDiscountMsg('Please enter a code.', false); return; }
  var btn  = document.getElementById('discount-btn');
  btn.disabled = true; btn.textContent = 'Checking…';

  var feeVal = parseFloat(document.getElementById('delivery-fee-input').value) || 0;
  fetch(BASE_URL + '/api/discount.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      code: code,
      subtotal: SUBTOTAL,
      shop_ids: SHOP_IDS,
      delivery_fee: feeVal
    })
  })
  .then(function(r){ return r.json(); })
  .then(function(d) {
    btn.disabled = false; btn.textContent = d.ok ? '✓ Applied' : 'Apply';
    if (d.ok) {
      DISCOUNT_AMT  = d.amount_off;
      DISCOUNT_ID   = d.discount_id;
      DISCOUNT_CODE = d.code;
      document.getElementById('discount-id-val').value     = d.discount_id;
      document.getElementById('discount-code-val').value   = d.code;
      document.getElementById('discount-amount-val').value = d.amount_off.toFixed(2);
      // Show discount row in sidebar
      var dRow = document.getElementById('discount-sidebar-row');
      dRow.style.display = 'flex';
      document.getElementById('sidebar-discount').textContent =
        '-' + fmtMoney(d.amount_off);
      updateTotals(feeVal);
      showDiscountMsg('✓ ' + d.message, true);
    } else {
      DISCOUNT_AMT = 0; DISCOUNT_ID = 0; DISCOUNT_CODE = '';
      document.getElementById('discount-id-val').value     = '';
      document.getElementById('discount-code-val').value   = '';
      document.getElementById('discount-amount-val').value = '0';
      document.getElementById('discount-sidebar-row').style.display = 'none';
      updateTotals(feeVal);
      showDiscountMsg('✗ ' + d.message, false);
    }
  })
  .catch(function(){
    btn.disabled = false; btn.textContent = 'Apply';
    showDiscountMsg('✗ Network error. Please try again.', false);
  });
}

function showDiscountMsg(text, ok) {
  var el = document.getElementById('discount-msg');
  el.textContent = text;
  el.style.color = ok ? 'var(--green)' : 'var(--red)';
  el.style.display = 'block';
}

// If address already filled (e.g. member with saved address), calc on load
window.addEventListener('load', function(){
  var addr = document.getElementById('delivery-address').value.trim();
  if (addr.length >= 10) calcDeliveryFee(addr);
});
</script>

<?php include __DIR__.'/../includes/footer_customer.php'; ?>
