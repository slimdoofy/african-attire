<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Payouts'; $activeNav = 'payouts';
include __DIR__ . '/../includes/header_merchant.php';

$sid     = $_shop['id'];
$minPay  = (float)getSetting('min_payout', 5000);

// Compute pure cost of goods earned from order_items directly
// — avoids stale total_revenue which may include delivery fee or markup
$totalCostEarned = (float)DB::count(
    "SELECT COALESCE(SUM(oi.cost_price * oi.quantity), 0)
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE oi.shop_id = ?
       AND o.payment_status = 'paid'
       AND oi.cost_price IS NOT NULL
       AND oi.cost_price > 0",
    [$sid]
);
$withdrawn = (float)$_shop['total_withdrawn'];
$available = max(0, $totalCostEarned - $withdrawn);

// What the markup rate is for this shop (for display only)
$markupRate = ($_shop['markup_rate'] !== null)
    ? (float)$_shop['markup_rate']
    : (float)getSetting('platform_markup', 5);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request') {
    $amount = (float)($_POST['amount'] ?? 0);
    if ($amount < $minPay) {
        flash('Minimum payout is ' . money($minPay) . '.', 'error');
    } elseif ($amount > $available) {
        flash('Amount exceeds available balance of ' . money($available) . '.', 'error');
    } else {
        DB::insert('payouts', [
            'shop_id'             => $sid,
            'payout_type'         => 'merchant',
            'amount'              => $amount,
            'commission_deducted' => 0,   // No commission — merchant gets full cost
            'net_amount'          => $amount,
            'status'              => 'pending',
        ]);
        DB::query('UPDATE shops SET total_withdrawn=total_withdrawn+? WHERE id=?', [$amount, $sid]);
        flash('Payout request of ' . money($amount) . ' submitted. Processing within 2–3 business days.', 'success');
    }
    redirect(BASE_URL . '/merchant/payouts.php');
}

$payouts = DB::fetchAll(
    'SELECT * FROM payouts WHERE shop_id=? AND payout_type="merchant" ORDER BY requested_at DESC',
    [$sid]
);
?>

<div class="m-page-head">
  <div>
    <div class="m-page-title">💰 Payouts</div>
    <div class="m-page-sub">You receive your <strong>cost of goods</strong> — the platform earns the markup.</div>
  </div>
</div>

<!-- Info box -->
<div class="alert alert-info" style="margin-bottom:16px">
  <strong>💡 How your earnings work (Markup Model):</strong><br>
  <span style="font-size:.84rem">
    When you set a product cost, the platform adds a <?= $markupRate ?>% markup for the customer price.
    <strong>Your payout = your cost of goods only.</strong>
    Example: you set ₦10,000 cost → customer pays ₦<?= number_format(10000*(1+$markupRate/100),2) ?> → you receive ₦10,000.
  </span>
</div>

<!-- Balance cards -->
<div class="m-stat-grid" style="margin-bottom:18px">
  <div class="m-stat accent-green">
    <div class="m-stat-label">Total Cost of Goods Earned</div>
    <div class="m-stat-value"><?= money($totalCostEarned) ?></div>
    <div class="m-stat-hint">Sum of your cost prices on paid orders</div>
  </div>
  <div class="m-stat accent-orange">
    <div class="m-stat-label">Total Withdrawn</div>
    <div class="m-stat-value"><?= money($withdrawn) ?></div>
    <div class="m-stat-hint">Paid to your bank account</div>
  </div>
  <div class="m-stat" style="border-left:4px solid var(--green)">
    <div class="m-stat-label">Available to Withdraw</div>
    <div class="m-stat-value" style="color:var(--green)"><?= money($available) ?></div>
    <div class="m-stat-hint">Ready to request payout</div>
  </div>
  <div class="m-stat">
    <div class="m-stat-label">Your Markup Rate</div>
    <div class="m-stat-value"><?= $markupRate ?>%</div>
    <div class="m-stat-hint">Applied on top of your cost prices</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
  <div class="card">
    <div class="card-head">
      <div class="card-title">📤 Request Payout</div>
    </div>
    <?php if ($available < $minPay): ?>
    <div class="alert alert-warning">
      Minimum payout is <?= money($minPay) ?>. Your available balance is <?= money($available) ?>.
    </div>
    <?php else: ?>
    <form method="POST">
      <input type="hidden" name="action" value="request">
      <div class="form-group">
        <label class="form-label">Amount to Withdraw (₦)</label>
        <input type="number" name="amount" class="form-control"
               min="<?= $minPay ?>" max="<?= floor($available) ?>" step="100"
               required placeholder="<?= number_format($minPay) ?>">
        <div class="form-hint">
          Min: <?= money($minPay) ?> · Max: <?= money($available) ?>
        </div>
      </div>
      <button type="submit" class="btn btn-ju btn-full">Request Payout</button>
    </form>
    <?php endif; ?>
    <div style="margin-top:14px;background:var(--bg);border-radius:var(--r-sm);padding:10px 12px;font-size:.78rem;color:var(--text-muted)">
      💳 <strong>Bank account:</strong> <?= e($_shop['bank_name'] ?? '—') ?> · <?= e($_shop['bank_account'] ?? '—') ?>
      <a href="<?= BASE_URL ?>/merchant/shop-setup.php" style="color:var(--blue);margin-left:6px">Edit →</a>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><div class="card-title">📋 Payout Policy</div></div>
    <div style="display:flex;flex-direction:column;gap:8px;font-size:.84rem;color:var(--text-soft)">
      <div>✅ <strong>You receive:</strong> Your exact cost of goods (no deductions)</div>
      <div>📊 <strong>Platform earns:</strong> The <?= $markupRate ?>% markup — not from your payout</div>
      <div>⏱ <strong>Processing time:</strong> 2–5 business days after approval</div>
      <div>💳 <strong>Payment method:</strong> Direct bank transfer (NGN)</div>
      <div>🔔 <strong>Notification:</strong> Email when approved and when paid</div>
      <div>💰 <strong>Minimum:</strong> <?= money($minPay) ?> per payout request</div>
    </div>
  </div>
</div>

<!-- Payout history -->
<div class="card" style="padding:0">
  <div class="card-head" style="padding:14px 16px">
    <div class="card-title">📃 Payout History</div>
  </div>
  <?php if (empty($payouts)): ?>
  <div class="empty-state" style="padding:32px">
    <span class="empty-icon">💳</span>
    <p>No payout requests yet.</p>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Date</th><th>Amount Requested</th><th>You Receive</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($payouts as $py): ?>
        <tr>
          <td style="font-size:.8rem;color:var(--text-muted)"><?= date('M j, Y', strtotime($py['requested_at'])) ?></td>
          <td style="font-weight:700"><?= moneyNgn($py['amount']) ?></td>
          <td style="font-weight:700;color:var(--green)"><?= moneyNgn($py['net_amount']) ?></td>
          <td><?= statusBadge($py['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_merchant.php'; ?>
