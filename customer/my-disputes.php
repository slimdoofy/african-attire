<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::start();

$isGuest   = !Auth::check();
$done      = false;
$disputes  = [];
$gEmail    = '';

// ── Guest lookup via email ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gEmail = strtolower(trim($_POST['guest_email'] ?? ''));
}

// ── Fetch disputes ────────────────────────────────────────────
if (!$isGuest) {
    $disputes = DB::fetchAll(
        "SELECT d.*, o.order_number, o.total_amount, o.status order_status
         FROM disputes d
         JOIN orders o ON o.id = d.order_id
         WHERE d.user_id = ?
         ORDER BY d.created_at DESC",
        [Auth::id()]
    );
} elseif ($gEmail) {
    $disputes = DB::fetchAll(
        "SELECT d.*, o.order_number, o.total_amount, o.status order_status
         FROM disputes d
         JOIN orders o ON o.id = d.order_id
         WHERE d.guest_email = ?
         ORDER BY d.created_at DESC",
        [$gEmail]
    );
    $done = true; // show results
}

$statusLabels = [
    'open'         => ['label'=>'Open',         'badge'=>'badge-danger',  'icon'=>'🔴'],
    'under_review' => ['label'=>'Under Review',  'badge'=>'badge-warning', 'icon'=>'🔍'],
    'resolved'     => ['label'=>'Resolved',      'badge'=>'badge-success', 'icon'=>'✅'],
    'closed'       => ['label'=>'Closed',        'badge'=>'badge-muted',   'icon'=>'🔒'],
];

$pageTitle  = 'My Disputes';
$activePage = 'orders';
include __DIR__ . '/../includes/header_customer.php';
?>

<div class="wrap" style="max-width:760px;padding-top:24px;padding-bottom:40px">

  <div class="breadcrumb" style="margin-bottom:16px">
    <a href="<?= BASE_URL ?>/">Home</a><span class="sep">›</span>
    <?php if (!$isGuest): ?>
    <a href="<?= BASE_URL ?>/customer/orders.php">My Orders</a><span class="sep">›</span>
    <?php endif; ?>
    <span class="cur">My Disputes</span>
  </div>

  <div style="margin-bottom:20px">
    <h1 style="font-family:var(--ff-head);font-size:1.3rem;font-weight:700;color:var(--black);margin-bottom:4px">
      ⚖️ My Disputes
    </h1>
    <p style="font-size:.84rem;color:var(--text-muted)">
      Track the status of any disputes you have raised with us.
    </p>
  </div>

  <!-- Guest email lookup form -->
  <?php if ($isGuest && !$done): ?>
  <div class="card" style="margin-bottom:20px">
    <div class="card-head">
      <div class="card-title">🔍 Find Your Disputes</div>
    </div>
    <p style="font-size:.84rem;color:var(--text-muted);margin-bottom:14px">
      Enter the email address you used when raising the dispute to view your cases.
    </p>
    <form method="POST" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
      <div class="form-group" style="flex:1;min-width:220px;margin-bottom:0">
        <label class="form-label">Email Address *</label>
        <input type="email" name="guest_email" class="form-control" required
               placeholder="you@example.com"
               value="<?= e($_POST['guest_email'] ?? '') ?>">
      </div>
      <button type="submit" class="btn btn-ju">Find My Disputes →</button>
    </form>
    <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border-lt);font-size:.8rem;color:var(--text-muted)">
      Have an account?
      <a href="<?= BASE_URL ?>/customer/login.php" style="color:var(--ju);font-weight:600">Sign in</a>
      to see all your disputes in one place.
    </div>
  </div>
  <?php endif; ?>

  <!-- Results -->
  <?php if (!$isGuest || $done): ?>

  <?php if ($isGuest && $done && empty($disputes)): ?>
  <div class="alert alert-info">
    No disputes found for <strong><?= e($gEmail) ?></strong>.
    Check the email address or
    <a href="<?= BASE_URL ?>/customer/raise-dispute.php" style="color:var(--ju);font-weight:600">raise a new dispute</a>.
  </div>

  <?php elseif (empty($disputes)): ?>
  <div class="empty-state card" style="padding:40px 24px;text-align:center">
    <span class="empty-icon" style="font-size:2.5rem">⚖️</span>
    <p style="font-weight:600;margin-bottom:6px">No disputes raised</p>
    <p style="font-size:.84rem;color:var(--text-muted);margin-bottom:14px">
      You have not raised any disputes yet. If you have an issue with an order, let us know.
    </p>
    <a href="<?= BASE_URL ?>/customer/raise-dispute.php" class="btn btn-ju btn-sm">
      + Raise a Dispute
    </a>
  </div>

  <?php else: ?>

  <div style="display:flex;flex-direction:column;gap:12px">
    <?php foreach ($disputes as $d):
      $st = $statusLabels[$d['status']] ?? ['label'=>ucfirst($d['status']),'badge'=>'badge-muted','icon'=>'❓'];
    ?>
    <div class="card" style="padding:0;overflow:hidden;
         border-left:4px solid <?= $d['status']==='open'?'#C62828':($d['status']==='resolved'?'#1B5E20':($d['status']==='under_review'?'#E65100':'#999')) ?>">

      <!-- Header row -->
      <div style="display:flex;align-items:flex-start;justify-content:space-between;
                  padding:14px 16px;flex-wrap:wrap;gap:8px;border-bottom:1px solid var(--border-lt)">
        <div>
          <div style="font-family:var(--ff-head);font-size:.95rem;font-weight:700;color:var(--black)">
            <?= $st['icon'] ?> Order <?= e($d['order_number']) ?>
          </div>
          <div style="font-size:.76rem;color:var(--text-muted);margin-top:2px">
            Raised <?= date('F j, Y', strtotime($d['created_at'])) ?>
            <?= $d['resolved_at'] ? ' · Resolved ' . date('F j, Y', strtotime($d['resolved_at'])) : '' ?>
          </div>
        </div>
        <span class="badge <?= $st['badge'] ?>" style="font-size:.75rem;padding:5px 10px">
          <?= $st['label'] ?>
        </span>
      </div>

      <!-- Body -->
      <div style="padding:14px 16px">

        <!-- Reason + description -->
        <div style="margin-bottom:12px">
          <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:4px">
            Reason
          </div>
          <div style="font-size:.86rem;font-weight:600;color:var(--black)">
            <?= ucwords(str_replace('_', ' ', $d['reason'])) ?>
          </div>
          <?php if ($d['description']): ?>
          <div style="font-size:.82rem;color:var(--text-soft);margin-top:4px;line-height:1.65">
            <?= e($d['description']) ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Resolution (if any) -->
        <?php if ($d['resolution']): ?>
        <div style="background:var(--green-pale);border:1px solid var(--green-pale2);
                    border-radius:var(--r-sm);padding:10px 14px;margin-bottom:10px">
          <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;
                      letter-spacing:.07em;color:var(--green);margin-bottom:5px">
            ✅ Resolution from African Attire
          </div>
          <p style="font-size:.86rem;color:var(--black);margin:0;line-height:1.65">
            <?= e($d['resolution']) ?>
          </p>
        </div>
        <?php endif; ?>

        <!-- Status timeline -->
        <div style="display:flex;gap:20px;flex-wrap:wrap;font-size:.76rem;color:var(--text-muted);
                    padding-top:10px;border-top:1px solid var(--border-lt)">
          <?php if ($d['status'] === 'open' || $d['status'] === 'under_review'): ?>
          <div>
            🕐 <strong>Response time:</strong> 2–3 business days
          </div>
          <?php endif; ?>
          <div>
            📧 <strong>Notifications:</strong>
            Sent to your registered email on every update
          </div>
          <?php if ($d['status'] === 'open'): ?>
          <div style="color:var(--red)">
            🔴 Awaiting admin review
          </div>
          <?php elseif ($d['status'] === 'under_review'): ?>
          <div style="color:var(--orange)">
            🔍 Our team is investigating
          </div>
          <?php elseif ($d['status'] === 'resolved'): ?>
          <div style="color:var(--green)">
            ✅ This dispute has been resolved
          </div>
          <?php elseif ($d['status'] === 'closed'): ?>
          <div style="color:var(--text-muted)">
            🔒 This dispute has been closed
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="text-align:center;margin-top:20px">
    <a href="<?= BASE_URL ?>/customer/raise-dispute.php"
       style="font-size:.84rem;color:var(--ju);font-weight:600;text-decoration:none">
      + Raise a New Dispute
    </a>
  </div>

  <?php endif; ?>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
