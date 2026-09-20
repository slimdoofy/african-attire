<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Audit Log'; $activeNav = 'audit-log';
include __DIR__ . '/../includes/header_admin.php';

// ── Filters ──────────────────────────────────────────────────
$portal    = trim($_GET['portal']    ?? '');
$action    = trim($_GET['action']    ?? '');
$search    = trim($_GET['q']         ?? '');
$dateFrom  = trim($_GET['date_from'] ?? '');
$dateTo    = trim($_GET['date_to']   ?? '');
$page      = max(1,(int)($_GET['page'] ?? 1)); $per = 50;

$where = ['1=1']; $params = [];
if ($portal) { $where[] = 'portal=?';       $params[] = $portal; }
if ($action) { $where[] = 'action LIKE ?';  $params[] = '%'.$action.'%'; }
if ($search) { $where[] = '(user_email LIKE ? OR description LIKE ?)'; $params[] = '%'.$search.'%'; $params[] = '%'.$search.'%'; }
if ($dateFrom) { $where[] = 'DATE(created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = 'DATE(created_at) <= ?'; $params[] = $dateTo;   }
$wStr = implode(' AND ', $where);

$total   = (int)DB::count("SELECT COUNT(*) FROM audit_log WHERE $wStr", $params);
$logs    = DB::fetchAll("SELECT * FROM audit_log WHERE $wStr ORDER BY created_at DESC LIMIT $per OFFSET ".(($page-1)*$per), $params);
$portals = DB::fetchAll("SELECT DISTINCT portal FROM audit_log ORDER BY portal");
$actions = DB::fetchAll("SELECT DISTINCT action FROM audit_log ORDER BY action LIMIT 50");

// Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $all = DB::fetchAll("SELECT * FROM audit_log WHERE $wStr ORDER BY created_at DESC", $params);
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="audit-log-'.date('Y-m-d').'.xls"');
    echo '<table><thead><tr><th>ID</th><th>Date/Time</th><th>Portal</th><th>User</th><th>Action</th><th>Entity</th><th>Description</th><th>IP Address</th></tr></thead><tbody>';
    foreach ($all as $l) {
        echo '<tr>';
        echo '<td>'.htmlspecialchars($l['id']).'</td>';
        echo '<td>'.htmlspecialchars($l['created_at']).'</td>';
        echo '<td>'.htmlspecialchars($l['portal']).'</td>';
        echo '<td>'.htmlspecialchars($l['user_email'] ?? 'Guest').'</td>';
        echo '<td>'.htmlspecialchars($l['action']).'</td>';
        echo '<td>'.htmlspecialchars(($l['entity_type'] ? $l['entity_type'].'#'.$l['entity_id'] : '—')).'</td>';
        echo '<td>'.htmlspecialchars($l['description'] ?? '').'</td>';
        echo '<td>'.htmlspecialchars($l['ip_address'] ?? '').'</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    exit;
}
?>

<div class="dash-head">
  <div class="flex-between" style="flex-wrap:wrap;gap:10px">
    <div>
      <h1 class="dash-title">📋 Audit Log</h1>
      <p class="dash-sub"><?= number_format($total) ?> events · All platform activity across all portals</p>
    </div>
    <a href="?<?= http_build_query(array_merge($_GET,['export'=>'excel'])) ?>"
       class="btn btn-ghost btn-sm">📥 Export Excel</a>
  </div>
</div>

<!-- Filters -->
<div class="card" style="padding:14px 16px;margin-bottom:14px">
  <form method="GET" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end">
    <div class="form-group" style="margin-bottom:0;flex:1;min-width:160px">
      <label class="form-label" style="font-size:.72rem">Search</label>
      <input type="text" name="q" class="form-control" placeholder="Email or description…" value="<?= e($search) ?>">
    </div>
    <div class="form-group" style="margin-bottom:0;width:130px">
      <label class="form-label" style="font-size:.72rem">Portal</label>
      <select name="portal" class="form-control">
        <option value="">All Portals</option>
        <?php foreach(['customer','merchant','admin','logistics'] as $pt): ?>
        <option value="<?=$pt?>" <?= $portal===$pt?'selected':''?>><?= ucfirst($pt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin-bottom:0;width:160px">
      <label class="form-label" style="font-size:.72rem">Action</label>
      <input type="text" name="action" class="form-control" placeholder="e.g. login" value="<?= e($action) ?>">
    </div>
    <div class="form-group" style="margin-bottom:0;width:130px">
      <label class="form-label" style="font-size:.72rem">From</label>
      <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
    </div>
    <div class="form-group" style="margin-bottom:0;width:130px">
      <label class="form-label" style="font-size:.72rem">To</label>
      <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>">
    </div>
    <button type="submit" class="btn btn-ju btn-sm" style="flex-shrink:0">Filter</button>
    <a href="?" class="btn btn-ghost btn-sm" style="flex-shrink:0">Reset</a>
  </form>
</div>

<div class="card" style="padding:0">
  <?php if (empty($logs)): ?>
  <div class="empty-state" style="padding:40px"><span class="empty-icon">📋</span><p>No log entries found.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th style="width:150px">Date / Time</th><th>Portal</th><th>User</th><th>Action</th><th>Entity</th><th>Description</th><th>IP</th></tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td style="font-size:.76rem;color:var(--text-muted);white-space:nowrap">
            <?= date('M j, Y', strtotime($l['created_at'])) ?><br>
            <span style="font-size:.7rem"><?= date('g:i:s A', strtotime($l['created_at'])) ?></span>
          </td>
          <td>
            <?php $portalColors = ['admin'=>'badge-danger','merchant'=>'badge-info','customer'=>'badge-success','logistics'=>'badge-warning']; ?>
            <span class="badge <?= $portalColors[$l['portal']] ?? 'badge-muted' ?>" style="font-size:.66rem">
              <?= ucfirst($l['portal']) ?>
            </span>
          </td>
          <td style="font-size:.8rem"><?= e($l['user_email'] ?? 'Guest') ?></td>
          <td>
            <span style="font-size:.78rem;font-weight:700;background:var(--bg);padding:2px 7px;border-radius:4px;font-family:monospace">
              <?= e($l['action']) ?>
            </span>
          </td>
          <td style="font-size:.76rem;color:var(--text-muted)">
            <?= $l['entity_type'] ? e($l['entity_type'].'#'.$l['entity_id']) : '—' ?>
          </td>
          <td style="font-size:.8rem;max-width:280px"><?= e($l['description'] ?? '') ?></td>
          <td style="font-size:.72rem;color:var(--text-muted)"><?= e($l['ip_address'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= paginate($total, $per, $page, '?'.http_build_query(array_diff_key($_GET, ['page'=>'']))) ?>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
