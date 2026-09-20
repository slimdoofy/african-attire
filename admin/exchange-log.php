<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Exchange Rate Audit Log';
$activeNav = 'settings';
include __DIR__ . '/../includes/header_admin.php';

$page  = max(1,(int)($_GET['page']??1)); $per = 30;
$total = DB::count('SELECT COUNT(*) FROM exchange_rate_log');
$logs  = DB::fetchAll(
    'SELECT * FROM exchange_rate_log ORDER BY changed_at DESC LIMIT '.$per.' OFFSET '.(($page-1)*$per)
);
$currentRate = (float)getSetting('usd_ngn_rate','1600');
?>

<div class="dash-head">
  <div class="flex-between" style="flex-wrap:wrap;gap:10px">
    <div>
      <h1 class="dash-title">📋 Exchange Rate Audit Log</h1>
      <p class="dash-sub">
        Current rate: <strong>$1 = ₦<?= number_format($currentRate,2) ?></strong>
        &nbsp;·&nbsp; <?= number_format($total) ?> change<?= $total!==1?'s':''?> recorded
      </p>
    </div>
    <a href="<?= BASE_URL ?>/admin/settings.php" class="btn btn-ghost btn-sm">
      ⚙️ Back to Settings
    </a>
  </div>
</div>

<!-- Summary card -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:18px">
  <div class="stat-card ab">
    <div class="stat-label">Current Rate</div>
    <div class="stat-value">₦<?= number_format($currentRate,2) ?></div>
    <div style="font-size:.72rem;color:var(--text-muted)">per 1 USD</div>
  </div>
  <div class="stat-card ag">
    <div class="stat-label">Total Changes</div>
    <div class="stat-value"><?= number_format($total) ?></div>
    <div style="font-size:.72rem;color:var(--text-muted)">since tracking began</div>
  </div>
  <?php if ($total > 0):
    $last = $logs[0];
    $diff = $last['new_rate'] - $last['old_rate'];
  ?>
  <div class="stat-card <?= $diff >= 0 ? 'ab' : 'ag' ?>">
    <div class="stat-label">Last Change</div>
    <div class="stat-value" style="font-size:1.1rem"><?= $diff >= 0 ? '+' : '' ?><?= number_format($diff,2) ?> ₦</div>
    <div style="font-size:.72rem;color:var(--text-muted)"><?= date('M j, Y', strtotime($last['changed_at'])) ?></div>
  </div>
  <?php endif; ?>
</div>

<?php if (empty($logs)): ?>
<div class="card" style="text-align:center;padding:48px">
  <div style="font-size:2.5rem;margin-bottom:12px">📋</div>
  <p style="font-weight:600;color:var(--text)">No rate changes logged yet</p>
  <p style="font-size:.84rem;color:var(--text-muted);margin-top:6px">
    When an admin updates the USD/NGN rate in Settings, the change is recorded here.
  </p>
</div>
<?php else: ?>
<div class="card" style="padding:0">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Date &amp; Time</th>
          <th>Changed By</th>
          <th>Previous Rate</th>
          <th>New Rate</th>
          <th>Change</th>
          <th>Note</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $i => $log):
          $diff = (float)$log['new_rate'] - (float)$log['old_rate'];
          $pct  = $log['old_rate'] > 0 ? ($diff / $log['old_rate'] * 100) : 0;
          $up   = $diff >= 0;
        ?>
        <tr>
          <td style="font-size:.76rem;color:var(--text-muted)"><?= (($page-1)*$per)+$i+1 ?></td>
          <td style="white-space:nowrap">
            <div style="font-size:.84rem;font-weight:600"><?= date('M j, Y', strtotime($log['changed_at'])) ?></div>
            <div style="font-size:.72rem;color:var(--text-muted)"><?= date('g:i A', strtotime($log['changed_at'])) ?> WAT</div>
          </td>
          <td>
            <div style="font-weight:600;font-size:.84rem"><?= e($log['admin_name']) ?></div>
            <div style="font-size:.72rem;color:var(--text-muted)"><?= e($log['admin_email']) ?></div>
          </td>
          <td style="font-size:.9rem">
            <span style="color:var(--text-muted)">₦<?= number_format($log['old_rate'],2) ?></span>
          </td>
          <td style="font-size:.9rem;font-weight:700">
            ₦<?= number_format($log['new_rate'],2) ?>
          </td>
          <td>
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:.82rem;font-weight:700;
                         color:<?= $up ? 'var(--red)' : 'var(--green)' ?>">
              <?= $up ? '▲' : '▼' ?>
              <?= $up ? '+' : '' ?><?= number_format($diff,2) ?>
              <span style="font-weight:400;font-size:.72rem;color:var(--text-muted)">
                (<?= $up ? '+' : '' ?><?= number_format($pct,1) ?>%)
              </span>
            </span>
          </td>
          <td style="font-size:.8rem;color:var(--text-soft);max-width:200px">
            <?= $log['note'] ? e($log['note']) : '<span style="color:var(--text-muted);font-style:italic">No note</span>' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?= paginate($total, $per, $page) ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
