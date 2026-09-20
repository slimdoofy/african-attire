<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Discount Codes'; $activeNav = 'discounts';

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'create' || $act === 'edit') {
        $code     = strtoupper(preg_replace('/[^A-Z0-9_-]/i', '', trim($_POST['code'] ?? '')));
        $desc     = trim($_POST['description'] ?? '');
        $type     = $_POST['type'] === 'flat' ? 'flat' : 'percent';
        $value    = (float)($_POST['value']         ?? 0);
        $minOrder = (float)($_POST['min_order_ngn'] ?? 0);
        $maxUses  = (int)($_POST['max_uses']         ?? 0);
        $from     = trim($_POST['valid_from']        ?? '');
        $until    = trim($_POST['valid_until']       ?? '');
        $status   = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
        $scope    = 'platform'; // admin always creates platform-wide

        $errs = [];
        if (!$code)   $errs[] = 'Code is required.';
        if ($value <= 0) $errs[] = 'Value must be greater than 0.';
        if ($type === 'percent' && $value > 100) $errs[] = 'Percent value cannot exceed 100%.';
        if (!$from || !$until) $errs[] = 'Valid from and until dates are required.';
        if ($from && $until && $from >= $until) $errs[] = 'Valid until must be after valid from.';

        if (empty($errs)) {
            if ($act === 'create') {
                if (DB::count('SELECT COUNT(*) FROM discount_codes WHERE code=?', [$code])) {
                    $errs[] = 'This code already exists.';
                } else {
                    DB::insert('discount_codes', compact(
                        'code','description','type','value','min_order_ngn',
                        'max_uses','valid_from','valid_until','scope','status'
                    ) + ['created_by'=>Auth::id(),'created_by_role'=>'admin',
                         'description'=>$desc,'valid_from'=>$from,'valid_until'=>$until,
                         'min_order_ngn'=>$minOrder]);
                    flash("Discount code <strong>{$code}</strong> created.", 'success');
                }
            } else {
                $id = (int)($_POST['id'] ?? 0);
                DB::update('discount_codes', [
                    'code'=>$code,'description'=>$desc,'type'=>$type,'value'=>$value,
                    'min_order_ngn'=>$minOrder,'max_uses'=>$maxUses,
                    'valid_from'=>$from,'valid_until'=>$until,'status'=>$status,
                ], 'id=?', [$id]);
                flash("Discount code <strong>{$code}</strong> updated.", 'success');
            }
        } else {
            foreach ($errs as $e) flash($e, 'error');
        }
    }

    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        DB::query('DELETE FROM discount_codes WHERE id=? AND created_by_role="admin"', [$id]);
        flash('Code deleted.', 'success');
    }

    redirect(BASE_URL . '/admin/discounts.php');
}

include __DIR__ . '/../includes/header_admin.php';

$codes = DB::fetchAll(
    "SELECT dc.*,
            (SELECT COUNT(*) FROM discount_usage du WHERE du.discount_id=dc.id) actual_uses
     FROM discount_codes dc
     ORDER BY dc.created_at DESC"
);
$now  = date('Y-m-d H:i:s');
?>

<div class="dash-head">
  <div class="flex-between" style="flex-wrap:wrap;gap:10px">
    <div>
      <h1 class="dash-title">🏷 Discount Codes</h1>
      <p class="dash-sub"><?= count($codes) ?> code<?= count($codes)!==1?'s':''?> · Platform-wide and merchant-specific</p>
    </div>
    <button class="btn btn-ju" onclick="openModal('dc-modal')">+ New Discount Code</button>
  </div>
</div>

<?php if (empty($codes)): ?>
<div class="empty-state card" style="padding:48px 24px">
  <span class="empty-icon">🏷</span>
  <p style="font-weight:600;margin-bottom:8px">No discount codes yet</p>
  <button class="btn btn-ju btn-sm" onclick="openModal('dc-modal')">+ Create First Code</button>
</div>
<?php else: ?>
<div class="card" style="padding:0">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Code</th><th>Type</th><th>Value</th><th>Min Order</th>
          <th>Uses</th><th>Validity</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($codes as $dc):
          $expired  = $dc['valid_until'] < $now;
          $notYet   = $dc['valid_from']  > $now;
          $maxed    = $dc['max_uses'] > 0 && (int)$dc['actual_uses'] >= (int)$dc['max_uses'];
          $effStatus= ($dc['status']==='inactive'||$expired||$maxed) ? 'inactive' : ($notYet ? 'scheduled' : 'active');
        ?>
        <tr>
          <td>
            <div style="font-family:monospace;font-size:.9rem;font-weight:800;
                        color:var(--navy);letter-spacing:.04em"><?= e($dc['code']) ?></div>
            <?php if($dc['description']): ?>
            <div style="font-size:.72rem;color:var(--text-muted)"><?= e($dc['description']) ?></div>
            <?php endif; ?>
            <div style="font-size:.7rem;color:var(--text-muted)">
              Scope: <span class="badge badge-muted" style="font-size:.6rem"><?= $dc['scope'] ?></span>
            </div>
          </td>
          <td>
            <span class="badge <?= $dc['type']==='percent'?'badge-info':'badge-warning' ?>">
              <?= $dc['type'] === 'percent' ? '%' : '₦' ?> <?= ucfirst($dc['type']) ?>
            </span>
          </td>
          <td style="font-weight:700;color:var(--black)">
            <?= $dc['type']==='percent' ? number_format($dc['value'],0).'%' : moneyNgn($dc['value']) ?>
          </td>
          <td style="font-size:.8rem;color:var(--text-muted)">
            <?= $dc['min_order_ngn'] > 0 ? moneyNgn($dc['min_order_ngn']) : '<span style="color:var(--text-muted)">None</span>' ?>
          </td>
          <td>
            <div style="font-size:.86rem">
              <strong><?= (int)$dc['actual_uses'] ?></strong>
              <?= $dc['max_uses']>0 ? ' / '.$dc['max_uses'] : ' / ∞' ?>
            </div>
            <?php if($dc['max_uses']>0): ?>
            <div style="height:4px;background:var(--border-lt);border-radius:2px;margin-top:3px;width:60px">
              <div style="height:100%;background:var(--ju);border-radius:2px;
                          width:<?= min(100,round($dc['actual_uses']/$dc['max_uses']*100)) ?>%"></div>
            </div>
            <?php endif; ?>
          </td>
          <td style="font-size:.76rem">
            <div style="color:var(--text-muted)">From: <?= date('M j, Y',strtotime($dc['valid_from'])) ?></div>
            <div style="color:<?= $expired?'var(--red)':'var(--text-muted)'?>">
              Until: <?= date('M j, Y',strtotime($dc['valid_until'])) ?>
              <?= $expired ? ' <span class="badge badge-danger" style="font-size:.6rem">Expired</span>' : '' ?>
            </div>
          </td>
          <td>
            <?php if ($effStatus==='active'): ?>
              <span class="badge badge-success">● Active</span>
            <?php elseif ($effStatus==='scheduled'): ?>
              <span class="badge badge-info">⏰ Scheduled</span>
            <?php else: ?>
              <span class="badge badge-muted">○ Inactive</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <button class="btn btn-blue btn-xs"
                      onclick='openEditModal(<?= htmlspecialchars(json_encode($dc)) ?>)'>Edit</button>
              <a href="<?= BASE_URL ?>/admin/discounts.php?usage=<?= $dc['id'] ?>"
                 class="btn btn-ghost btn-xs">Usage</a>
              <form method="POST" onsubmit="return confirm('Delete this code?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $dc['id'] ?>">
                <button class="btn btn-danger btn-xs">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php
// ── Usage log for a specific code ────────────────────────────
$viewId = (int)($_GET['usage'] ?? 0);
if ($viewId > 0) {
    $viewCode = DB::fetch('SELECT * FROM discount_codes WHERE id=?', [$viewId]);
    if ($viewCode) {
        $usages = DB::fetchAll(
            "SELECT du.*, o.total_amount, o.status order_status,
                    COALESCE(u.name, du.guest_email) customer
             FROM discount_usage du
             JOIN orders o ON o.id=du.order_id
             LEFT JOIN users u ON u.id=du.user_id
             WHERE du.discount_id=?
             ORDER BY du.applied_at DESC", [$viewId]);
?>
<div class="card" style="margin-top:16px;border-top:3px solid var(--orange)">
  <div class="card-head">
    <div class="card-title">📊 Usage Log: <code><?= e($viewCode['code']) ?></code></div>
    <a href="<?= BASE_URL ?>/admin/discounts.php" class="btn btn-ghost btn-sm">← Back</a>
  </div>
  <?php if (empty($usages)): ?>
  <div class="empty-state" style="padding:24px"><p>No usage recorded yet.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Order #</th><th>Customer</th><th>Subtotal Before</th><th>Discount Given</th><th>Order Total</th><th>Status</th><th>Applied</th></tr></thead>
      <tbody>
        <?php foreach ($usages as $u): ?>
        <tr>
          <td style="font-weight:700;color:var(--blue)"><?= e($u['order_number']) ?></td>
          <td style="font-size:.82rem"><?= e($u['customer']?:'Guest') ?></td>
          <td><?= moneyNgn($u['subtotal_before']) ?></td>
          <td style="font-weight:700;color:var(--green)">-<?= moneyNgn($u['discount_amount']) ?></td>
          <td style="font-weight:700"><?= moneyNgn($u['total_amount']) ?></td>
          <td><?= statusBadge($u['order_status']) ?></td>
          <td style="font-size:.76rem;color:var(--text-muted)"><?= date('M j, Y g:i A',strtotime($u['applied_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php }}?>

<!-- Create/Edit Modal -->
<div class="modal-bg" id="dc-modal">
  <div class="modal modal-lg">
    <div class="modal-head">
      <h3 class="modal-title" id="dc-modal-title">🏷 New Discount Code</h3>
      <button class="modal-close" onclick="closeModal('dc-modal')">✕</button>
    </div>
    <form method="POST" id="dc-form">
      <input type="hidden" name="action" id="dc-action" value="create">
      <input type="hidden" name="id"     id="dc-id"     value="">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Discount Code * <span style="font-weight:400;color:var(--text-muted)">(letters &amp; numbers only)</span></label>
          <input type="text" name="code" id="dc-code" class="form-control" required
                 placeholder="e.g. SAVE20, WELCOME10"
                 oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9_-]/g,'')">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" id="dc-status" class="form-control">
            <option value="active">Active</option>
            <option value="inactive">Inactive (draft)</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Description <span style="font-weight:400;color:var(--text-muted)">(shown to customer)</span></label>
        <input type="text" name="description" id="dc-desc" class="form-control"
               placeholder="e.g. 20% off your first order">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Discount Type *</label>
          <select name="type" id="dc-type" class="form-control" onchange="updateValueLabel()">
            <option value="percent">Percentage (%)</option>
            <option value="flat">Flat Amount (₦)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" id="dc-value-label">Discount Value * (%)</label>
          <div style="display:flex;align-items:center;gap:8px">
            <span id="dc-value-prefix" style="font-weight:600;color:var(--text-muted)"></span>
            <input type="number" name="value" id="dc-value" class="form-control"
                   required min="0.01" step="0.01" placeholder="20">
            <span id="dc-value-suffix" style="font-weight:600;color:var(--text-muted)">%</span>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Min. Order Amount (₦) <span style="font-weight:400;color:var(--text-muted)">(0 = no minimum)</span></label>
          <div style="display:flex;align-items:center;gap:6px">
            <span style="font-weight:600;color:var(--text-muted)">₦</span>
            <input type="number" name="min_order_ngn" id="dc-minorder" class="form-control" min="0" step="100" value="0">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Max Uses <span style="font-weight:400;color:var(--text-muted)">(0 = unlimited)</span></label>
          <input type="number" name="max_uses" id="dc-maxuses" class="form-control" min="0" value="0">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Valid From *</label>
          <input type="datetime-local" name="valid_from" id="dc-from" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Valid Until *</label>
          <input type="datetime-local" name="valid_until" id="dc-until" class="form-control" required>
        </div>
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:10px;border-top:1px solid var(--border-lt)">
        <button type="button" class="btn btn-ghost" onclick="closeModal('dc-modal')">Cancel</button>
        <button type="submit" class="btn btn-ju">Save Code</button>
      </div>
    </form>
  </div>
</div>

<script>
function updateValueLabel() {
  var t = document.getElementById('dc-type').value;
  document.getElementById('dc-value-label').textContent = t==='percent' ? 'Discount Value * (%)' : 'Discount Value * (₦)';
  document.getElementById('dc-value-prefix').textContent = t==='flat' ? '₦' : '';
  document.getElementById('dc-value-suffix').textContent = t==='percent' ? '%' : '';
}
function openEditModal(d) {
  document.getElementById('dc-modal-title').textContent = '✏ Edit: ' + d.code;
  document.getElementById('dc-action').value  = 'edit';
  document.getElementById('dc-id').value      = d.id;
  document.getElementById('dc-code').value    = d.code;
  document.getElementById('dc-desc').value    = d.description || '';
  document.getElementById('dc-type').value    = d.type;
  document.getElementById('dc-value').value   = d.value;
  document.getElementById('dc-minorder').value= d.min_order_ngn || 0;
  document.getElementById('dc-maxuses').value = d.max_uses || 0;
  document.getElementById('dc-status').value  = d.status;
  document.getElementById('dc-from').value    = d.valid_from  ? d.valid_from.replace(' ','T').slice(0,16)  : '';
  document.getElementById('dc-until').value   = d.valid_until ? d.valid_until.replace(' ','T').slice(0,16) : '';
  updateValueLabel();
  openModal('dc-modal');
}
// Set default valid_from to now
(function(){
  var now = new Date(); now.setSeconds(0,0);
  var s = now.toISOString().slice(0,16);
  document.getElementById('dc-from').value = s;
  var later = new Date(now); later.setMonth(later.getMonth()+1);
  document.getElementById('dc-until').value = later.toISOString().slice(0,16);
})();
</script>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
