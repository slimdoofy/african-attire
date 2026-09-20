<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Disputes'; $activeNav = 'disputes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $did    = (int)($_POST['dispute_id']  ?? 0);
    $status = trim($_POST['status']       ?? '');
    $res    = trim($_POST['resolution']   ?? '');
    $note   = trim($_POST['admin_note']   ?? '');

    if ($did && $status) {
        // Fetch customer details BEFORE updating so we can email them
        $drow = DB::fetch(
            "SELECT d.*, o.order_number,
                    COALESCE(u.name,  d.guest_name)  cust_name,
                    COALESCE(u.email, d.guest_email) cust_email
             FROM disputes d
             JOIN orders o ON o.id = d.order_id
             LEFT JOIN users u ON u.id = d.user_id
             WHERE d.id = ?",
            [$did]
        );

        $upd = ['status' => $status];
        if ($res)  $upd['resolution']   = $res;
        if ($note) $upd['admin_note']   = $note;
        if (in_array($status, ['resolved','closed'], true)) {
            $upd['resolved_at'] = date('Y-m-d H:i:s');
        }
        DB::update('disputes', $upd, 'id=?', [$did]);

        // If resolved/closed, revert order status from 'disputed'
        if (in_array($status, ['resolved','closed'], true)) {
            $dispute = DB::fetch('SELECT order_id FROM disputes WHERE id=?', [$did]);
            if ($dispute) {
                DB::update('orders', ['status' => $status === 'resolved' ? 'delivered' : 'cancelled'],
                    'id=? AND status="disputed"', [$dispute['order_id']]);
            }
        }

        // Email the customer about the update
        if (!empty($drow['cust_email'])) {
            sendDisputeUpdateEmail(
                $drow['cust_email'],
                $drow['cust_name'] ?: 'Customer',
                $drow['order_number'],
                $status,
                $res,
                $did
            );
        }

        auditLog('dispute_updated', "Dispute #$did status changed to $status", 'dispute', $did, 'admin');
        flash('Dispute updated to: ' . ucfirst(str_replace('_',' ',$status)) . '. Customer notified by email.', 'success');
    }
    redirect(BASE_URL . '/admin/disputes.php' . (isset($_GET['view']) ? '?view='.(int)$_GET['view'] : ''));
}

// Single dispute detail view
$viewId = (int)($_GET['view'] ?? 0);
if ($viewId) {
    include __DIR__ . '/../includes/header_admin.php';
    $d = DB::fetch(
        "SELECT d.*, COALESCE(u.name, d.guest_name) cust_name,
                COALESCE(u.email, d.guest_email) cust_email,
                o.order_number, o.total_amount, o.status order_status,
                o.created_at order_date, s.shop_name
         FROM disputes d
         LEFT JOIN users u ON u.id=d.user_id
         JOIN orders o ON o.id=d.order_id
         LEFT JOIN order_items oi ON oi.order_id=o.id
         LEFT JOIN shops s ON s.id=oi.shop_id
         WHERE d.id=? LIMIT 1", [$viewId]
    );
    if (!$d) { flash('Dispute not found.','error'); redirect(BASE_URL.'/admin/disputes.php'); }

    $statusOptions = ['open'=>'Open','under_review'=>'Under Review','resolved'=>'Resolved','closed'=>'Closed'];
    $badgeMap = ['open'=>'badge-danger','under_review'=>'badge-warning','resolved'=>'badge-success','closed'=>'badge-muted'];
    ?>
    <div class="dash-head">
      <div>
        <a href="<?= BASE_URL ?>/admin/disputes.php" style="font-size:.8rem;color:var(--text-muted);text-decoration:none">← All Disputes</a>
        <h1 class="dash-title" style="margin-top:4px">⚖️ Dispute #<?= $viewId ?></h1>
        <p class="dash-sub">Order <?= e($d['order_number']) ?> · Raised <?= date('M j, Y', strtotime($d['created_at'])) ?></p>
      </div>
      <span class="badge <?= $badgeMap[$d['status']] ?? 'badge-muted' ?>" style="font-size:.8rem;padding:6px 12px">
        <?= $statusOptions[$d['status']] ?? ucfirst($d['status']) ?>
      </span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:14px;align-items:start">
      <div>
        <!-- Dispute details -->
        <div class="card" style="margin-bottom:14px">
          <div class="card-head"><div class="card-title">📋 Dispute Details</div></div>
          <div style="display:flex;flex-direction:column;gap:10px;font-size:.86rem">
            <div><span style="color:var(--text-muted);display:inline-block;width:110px">Customer</span><strong><?= e($d['cust_name']) ?></strong> &lt;<?= e($d['cust_email']) ?>&gt;</div>
            <div><span style="color:var(--text-muted);display:inline-block;width:110px">Order #</span><strong><?= e($d['order_number']) ?></strong> · <?= moneyNgn($d['total_amount']) ?></div>
            <div><span style="color:var(--text-muted);display:inline-block;width:110px">Merchant</span><?= e($d['shop_name'] ?? '—') ?></div>
            <div><span style="color:var(--text-muted);display:inline-block;width:110px">Reason</span>
              <span class="badge badge-danger" style="font-size:.72rem"><?= ucwords(str_replace('_',' ',$d['reason'])) ?></span>
            </div>
          </div>
          <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border-lt)">
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:6px">Customer Description</div>
            <p style="font-size:.86rem;color:var(--text);line-height:1.7;white-space:pre-line"><?= e($d['description'] ?? $d['reason']) ?></p>
          </div>
          <?php if ($d['resolution']): ?>
          <div style="margin-top:12px;padding:10px 14px;background:var(--green-pale);border-radius:var(--r-sm)">
            <div style="font-size:.72rem;font-weight:700;color:var(--green);margin-bottom:4px">✅ RESOLUTION</div>
            <p style="font-size:.86rem;margin:0;line-height:1.7"><?= e($d['resolution']) ?></p>
          </div>
          <?php endif; ?>
          <?php if ($d['admin_note']): ?>
          <div style="margin-top:8px;padding:10px 14px;background:var(--bg);border-radius:var(--r-sm)">
            <div style="font-size:.72rem;font-weight:700;color:var(--text-muted);margin-bottom:4px">🔒 ADMIN NOTE (internal)</div>
            <p style="font-size:.84rem;margin:0"><?= e($d['admin_note']) ?></p>
          </div>
          <?php endif; ?>
        </div>

        <!-- Update form -->
        <?php if ($d['status'] !== 'closed'): ?>
        <div class="card" style="border-top:3px solid var(--navy)">
          <div class="card-head"><div class="card-title">🔄 Update Dispute</div></div>
          <form method="POST">
            <input type="hidden" name="dispute_id" value="<?= $viewId ?>">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Status *</label>
                <select name="status" class="form-control" required>
                  <?php foreach ($statusOptions as $v => $l): ?>
                  <option value="<?= $v ?>" <?= $d['status']===$v?'selected':''?>><?= $l ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Resolution <span style="font-weight:400;color:var(--text-muted)">(shared with customer)</span></label>
              <textarea name="resolution" class="form-control" rows="3"
                        placeholder="Describe the outcome for the customer…"><?= e($d['resolution'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Admin Note <span style="font-weight:400;color:var(--text-muted)">(internal only)</span></label>
              <textarea name="admin_note" class="form-control" rows="2"
                        placeholder="Internal notes not visible to customer…"><?= e($d['admin_note'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:8px">
              <button type="submit" class="btn btn-ju">Update Dispute</button>
              <button type="submit" name="status" value="closed"
                      class="btn btn-danger"
                      onclick="return confirm('Close this dispute permanently?')">
                🔒 Close Dispute
              </button>
            </div>
          </form>
        </div>
        <?php else: ?>
        <div class="alert alert-muted" style="margin-top:4px">
          🔒 This dispute has been closed.
          <?= $d['resolved_at'] ? 'Closed on '.date('M j, Y', strtotime($d['resolved_at'])).'.':''; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Right sidebar -->
      <div>
        <div class="card">
          <div class="card-title" style="margin-bottom:10px">📅 Timeline</div>
          <?php foreach ([
            'Dispute Raised' => $d['created_at'],
            'Last Updated'   => $d['updated_at'] ?? null,
            'Resolved'       => $d['resolved_at'] ?? null,
          ] as $k => $v): if (!$v) continue; ?>
          <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--border-lt);font-size:.82rem">
            <span style="color:var(--text-muted)"><?= $k ?></span>
            <span><?= date('M j, Y', strtotime($v)) ?></span>
          </div>
          <?php endforeach; ?>
          <div style="margin-top:10px">
            <a href="<?= BASE_URL ?>/admin/orders.php?q=<?= urlencode($d['order_number']) ?>"
               class="btn btn-ghost btn-sm btn-full" style="margin-bottom:6px">View Order →</a>
          </div>
        </div>
      </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer_admin.php';
    exit;
}

// ── LIST VIEW ─────────────────────────────────────────────────
include __DIR__ . '/../includes/header_admin.php';
$statusFilter = trim($_GET['status'] ?? '');
$q            = trim($_GET['q']      ?? '');
$dateFrom     = trim($_GET['date_from'] ?? '');
$dateTo       = trim($_GET['date_to']   ?? '');
$page         = max(1,(int)($_GET['page']??1)); $per=20;
$where = ['1=1']; $params = [];
if ($statusFilter) { $where[] = 'd.status=?'; $params[] = $statusFilter; }
if ($q)            { $where[] = '(o.order_number LIKE ? OR d.guest_email LIKE ? OR u.email LIKE ? OR u.name LIKE ? OR d.guest_name LIKE ?)'; $like="%$q%"; $params=array_merge($params,[$like,$like,$like,$like,$like]); }
if ($dateFrom)     { $where[] = 'DATE(d.created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)       { $where[] = 'DATE(d.created_at) <= ?'; $params[] = $dateTo; }
$wStr = implode(' AND ', $where);

// Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $all = DB::fetchAll("SELECT d.*,COALESCE(u.name,d.guest_name,'Guest') cust,COALESCE(u.email,d.guest_email) email,o.order_number FROM disputes d LEFT JOIN users u ON u.id=d.user_id JOIN orders o ON o.id=d.order_id WHERE $wStr ORDER BY d.created_at DESC", $params);
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="disputes-'.date('Y-m-d').'.xls"');
    echo '<table><thead><tr><th>ID</th><th>Order #</th><th>Customer</th><th>Email</th><th>Reason</th><th>Status</th><th>Raised</th><th>Resolved</th></tr></thead><tbody>';
    foreach($all as $r) echo '<tr><td>'.$r['id'].'</td><td>'.e($r['order_number']).'</td><td>'.e($r['cust']).'</td><td>'.e($r['email']).'</td><td>'.e($r['reason']).'</td><td>'.e($r['status']).'</td><td>'.e($r['created_at']).'</td><td>'.e($r['resolved_at']??'').'</td></tr>';
    echo '</tbody></table>'; exit;
}

$total    = (int)DB::count("SELECT COUNT(*) FROM disputes d LEFT JOIN users u ON u.id=d.user_id JOIN orders o ON o.id=d.order_id WHERE $wStr", $params);
$disputes = DB::fetchAll(
    "SELECT d.*, COALESCE(u.name,d.guest_name,'Guest') cust_name,
            COALESCE(u.email,d.guest_email) cust_email,
            o.order_number
     FROM disputes d
     LEFT JOIN users u ON u.id=d.user_id
     JOIN orders o ON o.id=d.order_id
     WHERE $wStr
     ORDER BY FIELD(d.status,'open','under_review','resolved','closed'), d.created_at DESC
     LIMIT $per OFFSET ".(($page-1)*$per), $params
);
$badgeMap = ['open'=>'badge-danger','under_review'=>'badge-warning','resolved'=>'badge-success','closed'=>'badge-muted'];
$openCount = (int)DB::count("SELECT COUNT(*) FROM disputes WHERE status='open'");
?>

<div class="dash-head">
  <div class="flex-between" style="flex-wrap:wrap;gap:10px">
    <div>
      <h1 class="dash-title">⚖️ Disputes</h1>
      <p class="dash-sub"><?= number_format($total) ?> dispute<?= $total!==1?'s':''?>
        <?php if($openCount>0):?> · <strong style="color:var(--red)"><?=$openCount?> open</strong><?php endif;?></p>
    </div>
    <a href="?<?= http_build_query(array_merge($_GET,['export'=>'excel'])) ?>" class="btn btn-ghost btn-sm">📥 Export Excel</a>
  </div>
</div>

<!-- Tabs + Filters -->
<div class="tab-bar" style="margin-bottom:10px">
  <?php foreach ([''=> 'All ('.((int)DB::count("SELECT COUNT(*) FROM disputes")).')','open'=>'Open','under_review'=>'Under Review','resolved'=>'Resolved','closed'=>'Closed'] as $v=>$l):?>
  <a href="?status=<?=$v?>" class="tab-btn <?=$statusFilter===$v?'active':''?>"><?=$l?></a>
  <?php endforeach;?>
</div>
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;align-items:center">
  <form method="GET" style="display:contents">
    <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
    <input type="text"  name="q"         class="form-control" style="width:200px" placeholder="Order #, name, email…" value="<?= e($q) ?>">
    <input type="date"  name="date_from" class="form-control" style="width:130px" value="<?= e($dateFrom) ?>">
    <input type="date"  name="date_to"   class="form-control" style="width:130px" value="<?= e($dateTo) ?>">
    <button class="btn btn-ghost btn-sm">Filter</button>
    <?php if($q||$dateFrom||$dateTo):?><a href="?status=<?=e($statusFilter)?>" class="btn btn-ghost btn-sm">Clear</a><?php endif;?>
  </form>
</div>

<?php if (empty($disputes)): ?>
<div class="empty-state card" style="padding:40px"><span class="empty-icon">⚖️</span><p>No disputes found.</p></div>
<?php else: ?>
<div class="card" style="padding:0">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Order #</th><th>Customer</th><th>Reason</th><th>Status</th><th>Raised</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($disputes as $d): ?>
        <tr style="<?= $d['status']==='open' ? 'background:rgba(198,40,40,.04)' : '' ?>">
          <td style="font-weight:700;color:var(--blue)"><?= e($d['order_number']) ?></td>
          <td>
            <div style="font-weight:600;font-size:.84rem"><?= e($d['cust_name']) ?></div>
            <div style="font-size:.74rem;color:var(--text-muted)"><?= e($d['cust_email']) ?></div>
          </td>
          <td><span class="badge badge-muted" style="font-size:.7rem"><?= ucwords(str_replace('_',' ',$d['reason'])) ?></span></td>
          <td><span class="badge <?= $badgeMap[$d['status']] ?? 'badge-muted' ?>"><?= ucwords(str_replace('_',' ',$d['status'])) ?></span></td>
          <td style="font-size:.76rem;color:var(--text-muted)"><?= date('M j, Y', strtotime($d['created_at'])) ?></td>
          <td>
            <a href="?view=<?= $d['id'] ?>" class="btn btn-blue btn-xs">Review →</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?= paginate($total, $per, $page, '?'.http_build_query(array_diff_key($_GET, ['page'=>'']))) ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
