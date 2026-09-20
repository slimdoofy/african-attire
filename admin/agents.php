<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Agents'; $activeNav = 'agents';

// ── Filters ───────────────────────────────────────────────────
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to']   ?? '');
$q        = trim($_GET['q']         ?? '');
$agentId  = trim($_GET['agent']     ?? '');

// ── Shared WHERE for agent list ───────────────────────────────
$where  = ["s.agent_id IS NOT NULL AND s.agent_id != ''"];
$params = [];
if ($q)        { $where[] = 's.agent_id LIKE ?';      $params[] = "%$q%"; }
if ($dateFrom) { $where[] = 'DATE(s.created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = 'DATE(s.created_at) <= ?'; $params[] = $dateTo; }
$wStr = implode(' AND ', $where);

// ── Shared WHERE for agent detail ─────────────────────────────
$dWhere  = ['s.agent_id = ?'];
$dParams = [$agentId];
if ($dateFrom) { $dWhere[] = 'DATE(s.created_at) >= ?'; $dParams[] = $dateFrom; }
if ($dateTo)   { $dWhere[] = 'DATE(s.created_at) <= ?'; $dParams[] = $dateTo; }
$dStr = implode(' AND ', $dWhere);

// ── CSV exports — MUST run before any HTML output ─────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    if ($agentId) {
        // Detail CSV — merchants for one agent
        $rows = DB::fetchAll(
            "SELECT s.shop_name, s.status, s.city, s.country,
                    u.name owner_name, u.email owner_email, u.phone,
                    s.created_at
             FROM shops s JOIN users u ON u.id = s.user_id
             WHERE $dStr ORDER BY s.created_at DESC",
            $dParams
        );
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="agent-' . preg_replace('/[^a-z0-9]/i','_',$agentId) . '-merchants-' . date('Y-m-d') . '.csv"');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['Shop Name','Owner','Email','Phone','City','Country','Status','Registered']);
        foreach ($rows as $r) {
            fputcsv($f, [$r['shop_name'],$r['owner_name'],$r['owner_email'],$r['phone'],$r['city'],$r['country'],$r['status'],$r['created_at']]);
        }
        fclose($f); exit;

    } else {
        // Summary CSV — all agents
        $rows = DB::fetchAll(
            "SELECT s.agent_id,
                    COUNT(s.id)          merchant_count,
                    MIN(s.created_at)    first_registered,
                    MAX(s.created_at)    last_registered
             FROM shops s
             WHERE $wStr
             GROUP BY s.agent_id
             ORDER BY s.agent_id ASC",
            $params
        );
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="agents-' . date('Y-m-d') . '.csv"');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['Agent ID','Merchants Onboarded','First Registration','Last Registration']);
        foreach ($rows as $r) {
            fputcsv($f, [$r['agent_id'],$r['merchant_count'],$r['first_registered'],$r['last_registered']]);
        }
        fclose($f); exit;
    }
}

// ── All HTML output below ─────────────────────────────────────
include __DIR__ . '/../includes/header_admin.php';

// Agent list data
$agents = DB::fetchAll(
    "SELECT s.agent_id,
            COUNT(s.id)              merchant_count,
            MIN(s.created_at)        first_registered,
            MAX(s.created_at)        last_registered,
            SUM(CASE WHEN s.status='approved' THEN 1 ELSE 0 END) approved_count,
            SUM(CASE WHEN s.status='pending'  THEN 1 ELSE 0 END) pending_count
     FROM shops s
     WHERE $wStr
     GROUP BY s.agent_id
     ORDER BY merchant_count DESC, s.agent_id ASC",
    $params
);
$totalAgents    = count($agents);
$totalMerchants = array_sum(array_column($agents, 'merchant_count'));
?>

<div class="dash-head">
  <div class="flex-between" style="flex-wrap:wrap;gap:10px">
    <div>
      <h1 class="dash-title">🔗 Agents</h1>
      <p class="dash-sub">
        <?= $totalAgents ?> agent<?= $totalAgents !== 1 ? 's' : '' ?> ·
        <?= $totalMerchants ?> merchant<?= $totalMerchants !== 1 ? 's' : '' ?> onboarded
      </p>
    </div>
    <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>"
       class="btn btn-ghost btn-sm">📥 Export CSV</a>
  </div>
</div>

<!-- Filters -->
<div class="card" style="padding:12px 16px;margin-bottom:14px">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
    <div class="form-group" style="margin-bottom:0;flex:1;min-width:160px">
      <label class="form-label" style="font-size:.72rem">Search Agent ID</label>
      <input type="text" name="q" class="form-control" placeholder="AGT-001…" value="<?= e($q) ?>">
    </div>
    <div class="form-group" style="margin-bottom:0">
      <label class="form-label" style="font-size:.72rem">From</label>
      <input type="date" name="date_from" class="form-control" style="width:130px" value="<?= e($dateFrom) ?>">
    </div>
    <div class="form-group" style="margin-bottom:0">
      <label class="form-label" style="font-size:.72rem">To</label>
      <input type="date" name="date_to" class="form-control" style="width:130px" value="<?= e($dateTo) ?>">
    </div>
    <button class="btn btn-ju btn-sm">Filter</button>
    <?php if ($q || $dateFrom || $dateTo): ?>
    <a href="?" class="btn btn-ghost btn-sm">Reset</a>
    <?php endif; ?>
  </form>
</div>

<?php if (empty($agents)): ?>
<div class="empty-state card" style="padding:48px 24px;text-align:center">
  <span class="empty-icon">🔗</span>
  <p>No agent IDs found. Merchants enter an Agent ID during registration.</p>
</div>
<?php else: ?>
<div class="card" style="padding:0">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Agent ID</th>
          <th>Merchants Onboarded</th>
          <th>Approved</th>
          <th>Pending</th>
          <th>First Registration</th>
          <th>Last Registration</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($agents as $a): ?>
        <tr>
          <td>
            <span style="font-family:monospace;font-weight:700;font-size:.9rem;
                         background:var(--bg);padding:3px 8px;border-radius:4px">
              <?= e($a['agent_id']) ?>
            </span>
          </td>
          <td><span style="font-size:1.05rem;font-weight:700;color:var(--navy)"><?= $a['merchant_count'] ?></span></td>
          <td><?= $a['approved_count'] > 0 ? '<span class="badge badge-success">'.$a['approved_count'].' approved</span>' : '<span style="color:var(--text-muted);font-size:.8rem">—</span>' ?></td>
          <td><?= $a['pending_count']  > 0 ? '<span class="badge badge-warning">'.$a['pending_count'].' pending</span>'   : '<span style="color:var(--text-muted);font-size:.8rem">—</span>' ?></td>
          <td style="font-size:.8rem;color:var(--text-muted)">
            <?= date('M j, Y', strtotime($a['first_registered'])) ?><br>
            <span style="font-size:.72rem"><?= date('g:i A', strtotime($a['first_registered'])) ?></span>
          </td>
          <td style="font-size:.8rem;color:var(--text-muted)">
            <?= date('M j, Y', strtotime($a['last_registered'])) ?><br>
            <span style="font-size:.72rem"><?= date('g:i A', strtotime($a['last_registered'])) ?></span>
          </td>
          <td>
            <a href="?agent=<?= urlencode($a['agent_id']) ?>"
               class="btn btn-blue btn-xs">View Merchants →</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($agentId):
    $merchants = DB::fetchAll(
        "SELECT s.*, u.name owner_name, u.email owner_email, u.phone
         FROM shops s JOIN users u ON u.id = s.user_id
         WHERE $dStr ORDER BY s.created_at DESC",
        $dParams
    );
?>
<div class="card" style="margin-top:20px;padding:0;border-top:3px solid var(--navy)">
  <div class="card-head" style="padding:14px 16px;flex-wrap:wrap;gap:10px">
    <div>
      <div class="card-title">
        🔗 Agent <span style="font-family:monospace;background:var(--bg);padding:2px 8px;
                               border-radius:4px;font-size:.88rem"><?= e($agentId) ?></span>
        — Merchants
      </div>
      <div style="font-size:.78rem;color:var(--text-muted);margin-top:3px">
        <?= count($merchants) ?> merchant<?= count($merchants) !== 1 ? 's' : '' ?> onboarded
      </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <form method="GET" style="display:flex;gap:6px;align-items:center">
        <input type="hidden" name="agent" value="<?= e($agentId) ?>">
        <input type="date" name="date_from" class="form-control" style="width:130px;font-size:.8rem" value="<?= e($dateFrom) ?>" title="From">
        <input type="date" name="date_to"   class="form-control" style="width:130px;font-size:.8rem" value="<?= e($dateTo) ?>"   title="To">
        <button class="btn btn-ghost btn-sm">Filter</button>
        <?php if ($dateFrom||$dateTo): ?><a href="?agent=<?= urlencode($agentId) ?>" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
      </form>
      <a href="?agent=<?= urlencode($agentId) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&export=csv"
         class="btn btn-ghost btn-sm">📥 Export CSV</a>
    </div>
  </div>

  <?php if (empty($merchants)): ?>
  <div class="empty-state" style="padding:32px"><p>No merchants found for this agent in the selected range.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Shop Name</th><th>Owner</th><th>Contact</th><th>Location</th><th>Status</th><th>Registered</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($merchants as $m): ?>
        <tr>
          <td style="font-weight:700"><?= e($m['shop_name']) ?></td>
          <td style="font-size:.84rem"><?= e($m['owner_name']) ?></td>
          <td style="font-size:.78rem;color:var(--text-muted)"><?= e($m['owner_email']) ?><br><?= e($m['phone'] ?? '') ?></td>
          <td style="font-size:.8rem"><?= e(implode(', ', array_filter([$m['city'],$m['country']]))) ?></td>
          <td><?= statusBadge($m['status']) ?></td>
          <td style="font-size:.78rem;color:var(--text-muted);white-space:nowrap">
            <?= date('M j, Y', strtotime($m['created_at'])) ?><br>
            <span style="font-size:.7rem"><?= date('g:i A', strtotime($m['created_at'])) ?></span>
          </td>
          <td><a href="<?= BASE_URL ?>/admin/merchants.php?id=<?= $m['id'] ?>" class="btn btn-ghost btn-xs">View →</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
