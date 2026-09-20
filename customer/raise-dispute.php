<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::start();

$isGuest  = !Auth::check();
$orderId  = (int)($_GET['order_id'] ?? 0);
$orderNum = trim($_GET['order'] ?? '');
$done     = false;
$errors   = [];

// Resolve the order — members by order_id, guests by order_number+email
$order = null;
if ($orderId && !$isGuest) {
    $order = DB::fetch(
        "SELECT o.*, COALESCE(u.name,o.guest_name) cust_name,
                COALESCE(u.email,o.guest_email) cust_email
         FROM orders o LEFT JOIN users u ON u.id=o.user_id
         WHERE o.id=? AND o.user_id=?",
        [$orderId, Auth::id()]
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason  = trim($_POST['reason']      ?? '');
    $desc    = trim($_POST['description'] ?? '');
    $gName   = trim($_POST['guest_name']  ?? '');
    $gEmail  = trim($_POST['guest_email'] ?? '');
    $gOrder  = trim($_POST['order_number']?? '');
    $gToken  = trim($_POST['guest_token'] ?? '');

    // Resolve order for guests via order_number + email
    if ($isGuest && !$order) {
        $order = DB::fetch(
            "SELECT * FROM orders WHERE order_number=? AND guest_email=?",
            [$gOrder, $gEmail]
        );
        if (!$order && $gToken) {
            $order = DB::fetch(
                "SELECT * FROM orders WHERE order_number=? AND guest_token=?",
                [$gOrder, $gToken]
            );
        }
    } elseif (!$isGuest && !$order) {
        $oNum = trim($_POST['order_number'] ?? '');
        $order = DB::fetch(
            "SELECT * FROM orders WHERE order_number=? AND user_id=?",
            [$oNum, Auth::id()]
        );
    }

    if (!$reason)  $errors[] = 'Please select a dispute reason.';
    if (!$desc)    $errors[] = 'Please describe the issue.';
    if ($isGuest) {
        if (!$gName)  $errors[] = 'Your name is required.';
        if (!filter_var($gEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
        if (!$gOrder) $errors[] = 'Order number is required.';
    }
    if (!$order)  $errors[] = 'Order not found. Please check your order number' . ($isGuest ? ' and email address.' : '.');

    // Check not already disputed
    if ($order && DB::count('SELECT COUNT(*) FROM disputes WHERE order_id=? AND status NOT IN ("closed")', [$order['id']])) {
        $errors[] = 'A dispute is already open for this order.';
    }

    if (empty($errors)) {
        $did = DB::insert('disputes', [
            'order_id'    => $order['id'],
            'user_id'     => $isGuest ? null : Auth::id(),
            'guest_name'  => $isGuest ? $gName  : null,
            'guest_email' => $isGuest ? $gEmail : null,
            'order_number'=> $order['order_number'],
            'reason'      => $reason,
            'description' => $desc,
            'status'      => 'open',
        ]);

        // Update order status to disputed
        DB::update('orders', ['status' => 'disputed'], 'id=?', [$order['id']]);

        // Notify admins
        $custName  = $isGuest ? $gName  : (Auth::user()['name'] ?? '');
        $custEmail = $isGuest ? $gEmail : (Auth::user()['email'] ?? '');
        sendDisputeNotification([
            'order_number' => $order['order_number'],
            'cust_name'    => $custName,
            'cust_email'   => $custEmail,
            'reason'       => $reason,
            'description'  => $desc,
        ]);

        auditLog('dispute_raised', "Dispute raised for order {$order['order_number']}", 'dispute', $did, 'customer');
        $done = true;
    }
}

$reasons = [
    'item_not_received'   => 'Item not received',
    'item_not_as_described' => 'Item not as described',
    'wrong_item'          => 'Wrong item delivered',
    'damaged_item'        => 'Item arrived damaged',
    'quality_issue'       => 'Quality does not match listing',
    'refund_not_received' => 'Refund not received',
    'other'               => 'Other',
];

$pageTitle  = 'Raise a Dispute';
$activePage = 'orders';
include __DIR__ . '/../includes/header_customer.php';
?>

<div class="wrap" style="max-width:600px;padding-top:24px;padding-bottom:40px">

  <div class="breadcrumb" style="margin-bottom:16px">
    <a href="<?= BASE_URL ?>/">Home</a><span class="sep">›</span>
    <?php if(!$isGuest):?><a href="<?= BASE_URL ?>/customer/orders.php">My Orders</a><span class="sep">›</span><?php endif;?>
    <span class="cur">Raise a Dispute</span>
  </div>

  <?php if ($done): ?>
  <div style="text-align:center;padding:40px 20px">
    <div style="font-size:3rem;margin-bottom:12px">✅</div>
    <h2 style="font-family:var(--ff-head);font-size:1.3rem;color:var(--black);margin-bottom:8px">Dispute Submitted</h2>
    <p style="color:var(--text-muted);font-size:.9rem;margin-bottom:20px">
      Your dispute has been logged. Our support team will review your case and
      contact you within <strong>2–3 business days</strong>.
    </p>
    <a href="<?= BASE_URL ?>/customer/track-order.php" class="btn btn-ju">Track Your Order →</a>
    <?php if(!$isGuest):?>
    <a href="<?= BASE_URL ?>/customer/orders.php" class="btn btn-ghost" style="margin-left:8px">My Orders</a>
    <?php endif;?>
  </div>

  <?php else: ?>

  <div style="margin-bottom:20px">
    <h1 style="font-family:var(--ff-head);font-size:1.3rem;font-weight:700;color:var(--black);margin-bottom:4px">
      ⚖️ Raise a Dispute
    </h1>
    <p style="font-size:.84rem;color:var(--text-muted)">
      If you have an issue with your order, let us know. Our team will investigate and resolve it.
    </p>
  </div>

  <?php if ($errors): ?>
  <div class="alert alert-danger" style="margin-bottom:16px">
    <?php foreach ($errors as $e): ?><div>• <?= e($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <form method="POST">

      <!-- Order identification -->
      <?php if ($isGuest): ?>
      <div style="background:var(--bg);border-radius:var(--r-md);padding:12px 14px;margin-bottom:16px">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:10px">Your Details</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="guest_name" class="form-control" required placeholder="Your name" value="<?= e($_POST['guest_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <input type="email" name="guest_email" class="form-control" required placeholder="Email used when ordering" value="<?= e($_POST['guest_email'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Order Number *</label>
          <input type="text" name="order_number" class="form-control" required placeholder="e.g. AA-2025-001234" value="<?= e($_POST['order_number'] ?? $orderNum) ?>">
          <div class="form-hint">Found in your order confirmation email.</div>
        </div>
      </div>
      <?php elseif ($order): ?>
      <div style="background:var(--ju-pale);border:1px solid var(--ju-pale2);border-radius:var(--r-md);padding:10px 14px;margin-bottom:16px;font-size:.86rem">
        <strong>Order:</strong> <?= e($order['order_number']) ?>
        &nbsp;·&nbsp; <strong>Total:</strong> <?= moneyNgn($order['total_amount']) ?>
      </div>
      <?php else: ?>
      <div class="form-group">
        <label class="form-label">Order Number *</label>
        <input type="text" name="order_number" class="form-control" required placeholder="e.g. AA-2025-001234" value="<?= e($_POST['order_number'] ?? $orderNum) ?>">
      </div>
      <?php endif; ?>

      <!-- Reason -->
      <div class="form-group">
        <label class="form-label">Reason for Dispute *</label>
        <select name="reason" class="form-control" required>
          <option value="">— Select a reason —</option>
          <?php foreach ($reasons as $val => $label): ?>
          <option value="<?= $val ?>" <?= (($_POST['reason'] ?? '') === $val) ? 'selected' : '' ?>>
            <?= $label ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Description -->
      <div class="form-group">
        <label class="form-label">Describe the Issue *</label>
        <textarea name="description" class="form-control" rows="5" required
                  placeholder="Please describe what happened in as much detail as possible. Include dates, item descriptions, and what resolution you are seeking."><?= e($_POST['description'] ?? '') ?></textarea>
      </div>

      <div class="alert alert-info" style="font-size:.8rem;margin-top:4px">
        📋 Our support team will review your dispute within 2–3 business days.
        You will be contacted at your registered email address.
      </div>

      <button type="submit" class="btn btn-danger btn-full btn-lg" style="background:#C62828;border-color:#C62828;color:#fff">
        Submit Dispute
      </button>

    </form>
  </div>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
