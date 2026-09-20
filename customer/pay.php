<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::start();

if (empty($_SESSION['pending_order'])) {
    flash('No pending order found. Please start checkout again.', 'error');
    redirect(BASE_URL . '/customer/checkout.php');
}

$pending = $_SESSION['pending_order'];
$subtotal    = (float)($pending['subtotal']        ?? $pending['total']);
$delivFee    = (float)($pending['delivery_fee']    ?? 0);
$discountAmt = (float)($pending['discount_amount'] ?? 0);
$total       = (float)$pending['total'];

if (time() - ($pending['created_at'] ?? 0) > 1800) {
    unset($_SESSION['pending_order']);
    flash('Your checkout session expired. Please try again.', 'error');
    redirect(BASE_URL . '/customer/checkout.php');
}

$psPublicKey = getSetting('paystack_public_key', 'pk_test_xxxxxxxxxxxxxxxx');

if ($pending['is_guest']) {
    $payerEmail = $pending['guest_email'];
    $payerName  = $pending['guest_name'];
} else {
    $user       = DB::fetch('SELECT name, email FROM users WHERE id=?', [$pending['user_id']]);
    $payerEmail = $user ? $user['email'] : '';
    $payerName  = $user ? $user['name']  : '';
}

$amountKobo = (int)round($total * 100);
$ref = 'AA-' . strtoupper(bin2hex(random_bytes(8)));
$_SESSION['pending_order']['payment_ref'] = $ref;

$pageTitle  = 'Secure Payment';
$activePage = 'shop';
include __DIR__ . '/../includes/header_customer.php';
?>
<div class="wrap-full" style="padding-top:14px;padding-bottom:32px">
  <div class="breadcrumb">
    <a href="<?= BASE_URL ?>/">Home</a><span class="sep">›</span>
    <a href="<?= BASE_URL ?>/customer/checkout.php">Checkout</a><span class="sep">›</span>
    <span class="cur">Payment</span>
  </div>

  <div class="steps" style="margin-bottom:20px">
    <div class="step done"><div class="step-circle">✓</div><div class="step-label">Cart</div></div>
    <div class="step done"><div class="step-circle">✓</div><div class="step-label">Details</div></div>
    <div class="step curr"><div class="step-circle">3</div><div class="step-label">Payment</div></div>
    <div class="step"><div class="step-circle">4</div><div class="step-label">Done</div></div>
  </div>

  <div style="max-width:520px;margin:0 auto">
    <div class="card" style="border-top:3px solid var(--blue)">
      <div style="text-align:center;padding:10px 0 18px">
        <div style="font-size:2.4rem;margin-bottom:8px">🔒</div>
        <h2 style="font-family:var(--ff-head);font-size:1.25rem;color:var(--black);margin-bottom:4px">
          Complete Your Payment
        </h2>
        <p style="font-size:.84rem;color:var(--text-muted)">
          You'll be charged <strong style="color:var(--black)"><?= money($total) ?></strong>
          via Paystack. Secure &amp; encrypted.
        </p>
      </div>

      <!-- Order summary — all line items -->
      <div style="background:var(--bg);border-radius:var(--r-md);padding:12px 14px;margin-bottom:16px">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                    letter-spacing:.07em;color:var(--text-muted);margin-bottom:8px">
          Order Summary
        </div>

        <!-- Cart items -->
        <?php foreach ($pending['cart'] as $item): ?>
        <div style="display:flex;justify-content:space-between;font-size:.83rem;
                    padding:3px 0;border-bottom:1px solid var(--border-lt)">
          <span style="color:var(--text)"><?= e($item['name']) ?> × <?= $item['quantity'] ?></span>
          <span style="font-weight:600"><?= money($item['price'] * $item['quantity']) ?></span>
        </div>
        <?php endforeach; ?>

        <!-- Subtotal -->
        <div style="display:flex;justify-content:space-between;font-size:.84rem;
                    padding:6px 0;border-bottom:1px solid var(--border-lt);margin-top:4px">
          <span style="color:var(--text-muted)">Subtotal</span>
          <span><?= money($subtotal) ?></span>
        </div>

        <!-- Delivery fee -->
        <?php if ($delivFee > 0): ?>
        <div style="display:flex;justify-content:space-between;font-size:.84rem;
                    padding:5px 0;border-bottom:1px solid var(--border-lt)">
          <span style="color:var(--text-muted)">🚚 Delivery Fee</span>
          <span style="color:var(--navy);font-weight:600"><?= money($delivFee) ?></span>
        </div>
        <?php else: ?>
        <div style="display:flex;justify-content:space-between;font-size:.84rem;
                    padding:5px 0;border-bottom:1px solid var(--border-lt)">
          <span style="color:var(--text-muted)">🚚 Delivery</span>
          <span style="color:var(--green);font-weight:600">FREE</span>
        </div>
        <?php endif; ?>

        <!-- Discount -->
        <?php if ($discountAmt > 0): ?>
        <div style="display:flex;justify-content:space-between;font-size:.84rem;
                    padding:5px 0;border-bottom:1px solid var(--border-lt)">
          <span style="color:var(--green)">
            🏷 Discount
            <?php if ($pending['discount_code']): ?>
            <span style="font-size:.72rem;background:var(--green-pale);color:var(--green);
                         padding:1px 6px;border-radius:3px;margin-left:4px;font-weight:700">
              <?= e($pending['discount_code']) ?>
            </span>
            <?php endif; ?>
          </span>
          <span style="color:var(--green);font-weight:700">-<?= money($discountAmt) ?></span>
        </div>
        <?php endif; ?>

        <!-- Grand total -->
        <div style="display:flex;justify-content:space-between;margin-top:8px;
                    font-size:.98rem;font-weight:800;color:var(--black)">
          <span>Total</span>
          <span style="color:var(--blue)"><?= money($total) ?></span>
        </div>
      </div>

      <button id="pay-btn" class="btn btn-ju btn-full btn-xl"
              style="font-size:1rem;font-weight:800;
                     box-shadow:0 4px 14px rgba(27,107,58,.35)">
        💳 Pay <?= money($total) ?> Securely
      </button>

      <a href="<?= BASE_URL ?>/customer/checkout.php"
         style="display:block;text-align:center;font-size:.78rem;
                color:var(--text-muted);margin-top:12px;text-decoration:none">
        ← Go back and edit details
      </a>
    </div>

    <div style="display:flex;align-items:center;justify-content:center;
                gap:16px;margin-top:14px;flex-wrap:wrap">
      <div style="display:flex;align-items:center;gap:5px;font-size:.74rem;color:var(--text-muted)">🔒 <span>256-bit SSL</span></div>
      <div style="display:flex;align-items:center;gap:5px;font-size:.74rem;color:var(--text-muted)">✅ <span>Paystack Verified</span></div>
      <div style="display:flex;align-items:center;gap:5px;font-size:.74rem;color:var(--text-muted)">🛡️ <span>Buyer Protection</span></div>
    </div>
  </div>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
document.getElementById('pay-btn').addEventListener('click', function() {
  this.disabled = true;
  this.textContent = 'Opening Paystack…';
  var self = this;
  var handler = PaystackPop.setup({
    key:      '<?= htmlspecialchars($psPublicKey) ?>',
    email:    '<?= htmlspecialchars($payerEmail) ?>',
    amount:   <?= $amountKobo ?>,
    currency: 'NGN',
    ref:      '<?= htmlspecialchars($ref) ?>',
    metadata: {
      custom_fields: [
        {display_name:'Customer Name',variable_name:'customer_name',value:'<?= htmlspecialchars($payerName) ?>'},
        {display_name:'Discount Code',variable_name:'discount_code',value:'<?= htmlspecialchars($pending['discount_code'] ?? '') ?>'}
      ]
    },
    onClose: function() {
      self.disabled = false;
      self.innerHTML = '💳 Pay <?= money($total) ?> Securely';
    },
    callback: function(response) {
      window.location.href = '<?= BASE_URL ?>/customer/pay-callback.php?reference='
                           + encodeURIComponent(response.reference);
    }
  });
  handler.openIframe();
});
</script>
<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
