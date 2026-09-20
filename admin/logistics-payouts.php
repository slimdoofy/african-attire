<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Logistics Payouts'; $activeNav = 'logistics';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $cid = (int)($_POST['company_id'] ?? 0);
    if ($act === 'create_payout' && $cid) {
        // Calculate total unpaid logistics fees for this company
        $unpaidFees = (float)DB::count(
            "SELECT COALESCE(SUM(ol.fee_ngn), 0)
             FROM order_logistics ol
             JOIN orders o ON o.id = ol.order_id
             WHERE ol.company_id = ?
             AND ol.status = 'delivered'
             AND ol.id NOT IN (SELECT logistics_company_id FROM payouts WHERE payout_type='logistics' AND status != 'rejected')",
            [$cid]
        );
        if ($unpaidFees > 0) {
            // Check bank account is on file before creating payout
            $co = DB::fetch('SELECT * FROM logistics_companies WHERE id=?', [$cid]);
            if (empty($co['bank_account']) || empty($co['bank_name'])) {
                // Send notification email to the logistics company admin
                $logUser = DB::fetch(
                    "SELECT email, CONCAT(first_name,' ',last_name) name
                     FROM logistics_users WHERE company_id=? AND role='admin' LIMIT 1",
                    [$cid]
                );
                if ($logUser) {
                    $loginUrl = BASE_URL . '/logistics/bank-account.php';
                    $content  = "<h2>Bank Account Details Required</h2>
                        <p>Hi <strong>" . htmlspecialchars($logUser['name']) . "</strong>,</p>
                        <p>African Attire is ready to process a logistics fee payout of
                        <strong>" . moneyNgn($unpaidFees) . "</strong> for
                        <strong>" . htmlspecialchars($co['company_name'] ?? '') . "</strong>.</p>
                        <p>However, <strong>no bank account details are on file</strong> for your company.
                        Please log in to the Logistics Portal and add your bank account details so we can process your payout.</p>
                        <div style='text-align:center;margin:20px 0'>
                          <a href='{$loginUrl}'
                             style='display:inline-block;padding:12px 28px;background:#1B6B3A;color:#fff;
                                    border-radius:6px;font-weight:700;text-decoration:none'>
                            Add Bank Account →
                          </a>
                        </div>
                        <p style='font-size:.82rem;color:#999'>
                          Portal: <a href='" . BASE_URL . "/logistics/'>" . BASE_URL . "/logistics/</a>
                        </p>";
                    $html = mailTemplate($content, 'Bank Account Required for Payout');
                    sendMail($logUser['email'], $logUser['name'],
                        '💳 Bank Account Required — African Attire Payout', $html);
                }
                flash(
                    'Cannot create payout: <strong>' . htmlspecialchars($co['company_name'] ?? '') .
                    '</strong> has no bank account on file. A notification email has been sent to their admin to add their bank details.',
                    'error'
                );
            } else {
                try {
                    DB::insert('payouts', [
                        'shop_id'              => null,   // NULL after migration 022
                        'payout_type'          => 'logistics',
                        'logistics_company_id' => $cid,
                        'logistics_fee_amount' => $unpaidFees,
                        'amount'               => $unpaidFees,
                        'commission_deducted'  => 0,
                        'net_amount'           => $unpaidFees,
                        'status'               => 'pending',
                    ]);
                } catch (PDOException $e) {
                    // shop_id column not yet nullable (migration 022 not run)
                    // Use a placeholder shop_id from any approved shop
                    $anyShop = DB::fetch('SELECT id FROM shops WHERE status="approved" LIMIT 1');
                    DB::insert('payouts', [
                        'shop_id'              => $anyShop ? $anyShop['id'] : 1,
                        'payout_type'          => 'logistics',
                        'logistics_company_id' => $cid,
                        'logistics_fee_amount' => $unpaidFees,
                        'amount'               => $unpaidFees,
                        'commission_deducted'  => 0,
                        'net_amount'           => $unpaidFees,
                        'status'               => 'pending',
                    ]);
                }
                flash('Logistics payout of ' . moneyNgn($unpaidFees) . ' created for ' . htmlspecialchars($co['company_name'] ?? '') . '.', 'success');
            }
        } else {
            flash('No outstanding delivered logistics fees for this company.', 'error');
        }
    }
    if ($act === 'approve') {
        DB::update('payouts', ['status'=>'approved','processed_at'=>date('Y-m-d H:i:s')], 'id=?', [(int)$_POST['payout_id']]);
        flash('Logistics payout approved.', 'success');
    }
    if ($act === 'mark_paid') {
        DB::update('payouts', ['status'=>'paid','processed_at'=>date('Y-m-d H:i:s')], 'id=?', [(int)$_POST['payout_id']]);
        flash('Logistics payout marked as paid.', 'success');
    }
    redirect(BASE_URL . '/admin/logistics-payouts.php');
}

include __DIR__ . '/../includes/header_admin.php';

$companies = DB::fetchAll(
    "SELECT lc.*,
            COALESCE(SUM(CASE WHEN ol.status='delivered' THEN ol.fee_ngn ELSE 0 END), 0) total_fees_earned,
            COALESCE(SUM(CASE WHEN ol.status='delivered' THEN ol.fee_ngn ELSE 0 END), 0) -
            COALESCE((SELECT SUM(py.net_amount) FROM payouts py
                      WHERE py.logistics_company_id=lc.id
                      AND py.payout_type='logistics' AND py.status != 'rejected'), 0) outstanding
     FROM logistics_companies lc
     LEFT JOIN order_logistics ol ON ol.company_id = lc.id
     WHERE lc.status = 'active'
     GROUP BY lc.id
     ORDER BY lc.company_name"
);

$payouts = DB::fetchAll(
    "SELECT py.*, lc.company_name
     FROM payouts py
     JOIN logistics_companies lc ON lc.id = py.logistics_company_id
     WHERE py.payout_type = 'logistics'
     ORDER BY py.requested_at DESC
     LIMIT 50"
);
?>

<div class="dash-head">
  <h1 class="dash-title">🚚 Logistics Payouts</h1>
  <p class="dash-sub">Logistics companies receive their delivery fees only — no markup included</p>
</div>

<div class="alert alert-info" style="margin-bottom:16px">
  <strong>Logistics Payout Rule:</strong> Each logistics company receives exactly the
  <strong>delivery fee</strong> charged on orders they delivered. No markup, no commission — just their fee.
</div>

<!-- Company outstanding fees -->
<div class="card" style="margin-bottom:16px">
  <div class="card-head">
    <div class="card-title">🏢 Outstanding Fees by Company</div>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Company</th><th>Total Fees Earned</th><th>Outstanding (unpaid)</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($companies as $co): ?>
        <tr>
          <td style="font-weight:700"><?= e($co['company_name']) ?></td>
          <td><?= moneyNgn($co['total_fees_earned']) ?></td>
          <td style="font-weight:700;color:<?= $co['outstanding']>0?'var(--green)':'var(--text-muted)' ?>">
            <?= moneyNgn($co['outstanding']) ?>
          </td>
          <td>
            <?php if ((float)$co['outstanding'] > 0): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action"     value="create_payout">
              <input type="hidden" name="company_id" value="<?= $co['id'] ?>">
              <button class="btn btn-ju btn-sm"
                      onclick="return confirm('Create payout of <?= moneyNgn($co['outstanding']) ?> for <?= e($co['company_name']) ?>?')">
                Create Payout
              </button>
            </form>
            <?php else: ?>
            <span style="font-size:.78rem;color:var(--text-muted)">Fully paid</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Payout history -->
<div class="card" style="padding:0">
  <div class="card-head" style="padding:14px 16px">
    <div class="card-title">📃 Logistics Payout History</div>
  </div>
  <?php if (empty($payouts)): ?>
  <div class="empty-state" style="padding:32px"><p>No logistics payouts yet.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Company</th><th>Delivery Fees</th><th>Net Payout</th><th>Status</th><th>Requested</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($payouts as $py): ?>
        <tr>
          <td style="font-weight:700"><?= e($py['company_name']) ?></td>
          <td><?= moneyNgn($py['logistics_fee_amount']) ?></td>
          <td style="font-weight:700;color:var(--green)"><?= moneyNgn($py['net_amount']) ?></td>
          <td><?= statusBadge($py['status']) ?></td>
          <td style="font-size:.78rem;color:var(--text-muted)"><?= date('M j, Y', strtotime($py['requested_at'])) ?></td>
          <td>
            <div style="display:flex;gap:5px">
              <?php if ($py['status'] === 'pending'): ?>
              <form method="POST"><input type="hidden" name="action" value="approve"><input type="hidden" name="payout_id" value="<?= $py['id'] ?>"><button class="btn btn-ju btn-xs">Approve</button></form>
              <?php elseif ($py['status'] === 'approved'): ?>
              <form method="POST"><input type="hidden" name="action" value="mark_paid"><input type="hidden" name="payout_id" value="<?= $py['id'] ?>"><button class="btn btn-green btn-xs">Mark Paid</button></form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
