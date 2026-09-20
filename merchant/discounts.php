<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Discount Codes'; $activeNav = 'discounts';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/../includes/header_merchant.php'; // loads $_shop
    // Actually, we need $_shop before header for POST handling
}
// Load shop first (needed for scope filtering)
Auth::requireRole('merchant');
$_shop = DB::fetch('SELECT * FROM shops WHERE user_id=?', [Auth::id()]);
if (!$_shop) redirect(BASE_URL . '/merchant/register.php');
$sid = $_shop['id'];

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'create' || $act === 'edit') {
        $code     = strtoupper(preg_replace('/[^A-Z0-9_-]/i', '', trim($_POST['code'] ?? '')));
        $desc     = trim($_POST['description'] ?? '');
        $type     = $_POST['type'] === 'flat' ? 'flat' : 'percent';
        $value    = (float)($_POST['value']          ?? 0);
        $minOrder = (float)($_POST['min_order_ngn']  ?? 0);
        $maxUses  = (int)($_POST['max_uses']          ?? 0);
        $from     = trim($_POST['valid_from']         ?? '');
        $until    = trim($_POST['valid_until']        ?? '');
        $status   = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        $errs = [];
        if (!$code)   $errs[] = 'Code is required.';
        if ($value <= 0) $errs[] = 'Value must be greater than 0.';
        if ($type === 'percent' && $value > 100) $errs[] = 'Percent cannot exceed 100.';
        if (!$from || !$until) $errs[] = 'Valid dates are required.';
        if ($from && $until && $from >= $until) $errs[] = 'Valid until must be after valid from.';

        if (empty($errs)) {
            if ($act === 'create') {
                if (DB::count('SELECT COUNT(*) FROM discount_codes WHERE code=?', [$code])) {
                    $errs[] = 'Code already exists (try adding your shop initials).';
                } else {
                    DB::insert('discount_codes', [
                        'code'=>$code,'description'=>$desc,'type'=>$type,'value'=>$value,
                        'min_order_ngn'=>$minOrder,'max_uses'=>$maxUses,
                        'valid_from'=>$from,'valid_until'=>$until,'status'=>$status,
                        'scope'=>'merchant','shop_id'=>$sid,
                        'created_by'=>Auth::id(),'created_by_role'=>'merchant',
                    ]);
                    flash("Code <strong>{$code}</strong> created!", 'success');
                }
            } else {
                $id = (int)($_POST['id'] ?? 0);
                // Only allow editing own codes
                DB::update('discount_codes', [
                    'code'=>$code,'description'=>$desc,'type'=>$type,'value'=>$value,
                    'min_order_ngn'=>$minOrder,'max_uses'=>$maxUses,
                    'valid_from'=>$from,'valid_until'=>$until,'status'=>$status,
                ], 'id=? AND shop_id=?', [$id, $sid]);
                flash("Code <strong>{$code}</strong> updated.", 'success');
            }
        } else {
            foreach ($errs as $e) flash($e, 'error');
        }
    }

    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        DB::query('DELETE FROM discount_codes WHERE id=? AND shop_id=?', [$id, $sid]);
        flash('Code deleted.', 'success');
    }

    redirect(BASE_URL . '/merchant/discounts.php');
}

// ── Load page ─────────────────────────────────────────────────
include __DIR__ . '/../includes/header_merchant.php';

$codes = DB::fetchAll(
    "SELECT dc.*,
            (SELECT COUNT(*) FROM discount_usage du WHERE du.discount_id=dc.id) actual_uses
     FROM discount_codes dc
     WHERE dc.shop_id=? OR dc.scope='platform'
     ORDER BY dc.scope ASC, dc.created_at DESC",
    [$sid]
);
$now = date('Y-m-d H:i:s');
?>

<div class="m-page-head">
  <div>
    <div class="m-page-title">🏷 Discount Codes</div>
    <div class="m-page-sub">Create and manage promo codes for your shop</div>
  </div>
  <button class="btn btn-ju btn-sm" onclick="openModal('mc-modal')">+ New Code</button>
</div>

<?php if (empty($codes)): ?>
<div class="empty-state card" style="padding:40px 24px">
  <span class="empty-icon">🏷</span>
  <p style="font-weight:600;margin-bottom:6px">No discount codes yet</p>
  <p style="font-size:.82rem;margin-bottom:14px">Create codes to offer your customers discounts at checkout.</p>
  <button class="btn btn-ju btn-sm" onclick="openModal('mc-modal')">+ Create First Code</button>
</div>
<?php else: ?>
<div class="card" style="padding:0">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Code</th><th>Type</th><th>Value</th><th>Min Order</th><th>Uses</th><th>Validity</th><th>Scope</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($codes as $dc):
          $isPlatform = $dc['scope'] === 'platform';
          $expired = $dc['valid_until'] < $now;
          $notYet  = $dc['valid_from']  > $now;
          $maxed   = $dc['max_uses'] > 0 && (int)$dc['actual_uses'] >= (int)$dc['max_uses'];
          $eff = ($dc['status']==='inactive'||$expired||$maxed) ? 'inactive' : ($notYet?'scheduled':'active');
        ?>
        <tr>
          <td>
            <div style="font-family:monospace;font-size:.88rem;font-weight:800;
                        color:var(--navy);letter-spacing:.04em"><?= e($dc['code']) ?></div>
            <?php if($dc['description']): ?>
            <div style="font-size:.7rem;color:var(--text-muted)"><?= e($dc['description']) ?></div>
            <?php endif; ?>
          </td>
          <td><span class="badge <?= $dc['type']==='percent'?'badge-info':'badge-warning' ?>"><?= $dc['type']==='percent'?'%':'₦' ?></span></td>
          <td style="font-weight:700"><?= $dc['type']==='percent'?number_format($dc['value'],0).'%':moneyNgn($dc['value']) ?></td>
          <td style="font-size:.8rem"><?= $dc['min_order_ngn']>0?moneyNgn($dc['min_order_ngn']):'—' ?></td>
          <td style="font-size:.86rem"><strong><?= (int)$dc['actual_uses'] ?></strong><?= $dc['max_uses']>0?' / '.$dc['max_uses'].' max':'' ?></td>
          <td style="font-size:.74rem;color:var(--text-muted)">
            <?= date('M j, Y',strtotime($dc['valid_from'])) ?> →<br>
            <span style="color:<?= $expired?'var(--red)':'inherit'?>"><?= date('M j, Y',strtotime($dc['valid_until'])) ?></span>
          </td>
          <td><?php if($isPlatform): ?>
            <span class="badge badge-muted" style="font-size:.62rem">Platform</span>
          <?php else: ?>
            <span class="badge badge-info" style="font-size:.62rem">Your Shop</span>
          <?php endif; ?></td>
          <td><?php if($eff==='active'): ?>
            <span class="badge badge-success" style="font-size:.7rem">● Active</span>
          <?php elseif($eff==='scheduled'): ?>
            <span class="badge badge-info" style="font-size:.7rem">⏰</span>
          <?php else: ?>
            <span class="badge badge-muted" style="font-size:.7rem">○</span>
          <?php endif; ?></td>
          <td>
            <?php if (!$isPlatform): ?>
            <div style="display:flex;gap:4px;flex-wrap:wrap">
              <button class="btn btn-blue btn-xs"
                      onclick='openMerchantEdit(<?= htmlspecialchars(json_encode($dc)) ?>)'>Edit</button>
              <form method="POST" onsubmit="return confirm('Delete?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $dc['id'] ?>">
                <button class="btn btn-danger btn-xs">Del</button>
              </form>
            </div>
            <?php else: ?>
            <span style="font-size:.72rem;color:var(--text-muted)">Admin code</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Usage log for own codes -->
<?php
$totalDiscount = (float)DB::count(
    "SELECT COALESCE(SUM(du.discount_amount),0)
     FROM discount_usage du
     JOIN discount_codes dc ON dc.id=du.discount_id
     WHERE dc.shop_id=?", [$sid]
);
$usages = DB::fetchAll(
    "SELECT du.*, dc.code, o.total_amount, o.status order_status,
            COALESCE(u.name, du.guest_email) customer
     FROM discount_usage du
     JOIN discount_codes dc ON dc.id=du.discount_id
     JOIN orders o ON o.id=du.order_id
     LEFT JOIN users u ON u.id=du.user_id
     WHERE dc.shop_id=?
     ORDER BY du.applied_at DESC LIMIT 20", [$sid]
);
if (!empty($usages)): ?>
<div class="card" style="margin-top:14px">
  <div class="card-head">
    <div class="card-title">📊 Recent Discount Usage</div>
    <span style="font-size:.8rem;color:var(--text-muted)">
      Total given: <strong style="color:var(--green)"><?= moneyNgn($totalDiscount) ?></strong>
    </span>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Order #</th><th>Code</th><th>Customer</th><th>Subtotal</th><th>Discount</th><th>Applied</th></tr></thead>
      <tbody>
        <?php foreach ($usages as $u): ?>
        <tr>
          <td style="font-weight:700;color:var(--blue);font-size:.84rem"><?= e($u['order_number']) ?></td>
          <td><code style="font-size:.8rem;background:var(--bg);padding:2px 6px;border-radius:3px"><?= e($u['code']) ?></code></td>
          <td style="font-size:.82rem"><?= e($u['customer']?:'Guest') ?></td>
          <td style="font-size:.82rem"><?= moneyNgn($u['subtotal_before']) ?></td>
          <td style="font-weight:700;color:var(--green)">-<?= moneyNgn($u['discount_amount']) ?></td>
          <td style="font-size:.76rem;color:var(--text-muted)"><?= date('M j, Y g:i A',strtotime($u['applied_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Modal -->
<div class="modal-bg" id="mc-modal">
  <div class="modal modal-lg">
    <div class="modal-head">
      <h3 class="modal-title" id="mc-modal-title">🏷 New Discount Code</h3>
      <button class="modal-close" onclick="closeModal('mc-modal')">✕</button>
    </div>
    <form method="POST" id="mc-form">
      <input type="hidden" name="action" id="mc-action" value="create">
      <input type="hidden" name="id"     id="mc-id"     value="">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Code *</label>
          <input type="text" name="code" id="mc-code" class="form-control" required
                 placeholder="e.g. SHOP20OFF"
                 oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9_-]/g,'')">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" id="mc-status" class="form-control">
            <option value="active">Active</option>
            <option value="inactive">Inactive (draft)</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Description <span style="font-weight:400;color:var(--text-muted)">(visible to customer)</span></label>
        <input type="text" name="description" id="mc-desc" class="form-control" placeholder="e.g. 15% off orders over ₦10,000">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Type *</label>
          <select name="type" id="mc-type" class="form-control" onchange="updateMcLabel()">
            <option value="percent">Percentage (%)</option>
            <option value="flat">Flat Amount (₦)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" id="mc-value-label">Value *</label>
          <div style="display:flex;align-items:center;gap:6px">
            <span id="mc-pfx" style="font-weight:600;color:var(--text-muted)"></span>
            <input type="number" name="value" id="mc-value" class="form-control" required min="0.01" step="0.01">
            <span id="mc-sfx" style="font-weight:600;color:var(--text-muted)">%</span>
          </div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Min Order (₦) <span style="font-weight:400;color:var(--text-muted)">(0 = none)</span></label>
          <input type="number" name="min_order_ngn" id="mc-minorder" class="form-control" min="0" step="100" value="0">
        </div>
        <div class="form-group">
          <label class="form-label">Max Uses <span style="font-weight:400;color:var(--text-muted)">(0 = unlimited)</span></label>
          <input type="number" name="max_uses" id="mc-maxuses" class="form-control" min="0" value="0">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Valid From *</label>
          <input type="datetime-local" name="valid_from" id="mc-from" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Valid Until *</label>
          <input type="datetime-local" name="valid_until" id="mc-until" class="form-control" required>
        </div>
      </div>
      <div class="alert alert-info" style="font-size:.8rem;margin-top:4px">
        🏷 This code will only apply to purchases from your shop. Platform-wide codes are created by Admin.
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:10px;border-top:1px solid var(--border-lt)">
        <button type="button" class="btn btn-ghost" onclick="closeModal('mc-modal')">Cancel</button>
        <button type="submit" class="btn btn-ju">Save Code</button>
      </div>
    </form>
  </div>
</div>

<script>
function updateMcLabel(){
  var t=document.getElementById('mc-type').value;
  document.getElementById('mc-value-label').textContent=t==='percent'?'Value * (%)':'Value * (₦)';
  document.getElementById('mc-pfx').textContent=t==='flat'?'₦':'';
  document.getElementById('mc-sfx').textContent=t==='percent'?'%':'';
}
function openMerchantEdit(d){
  document.getElementById('mc-modal-title').textContent='✏ Edit: '+d.code;
  document.getElementById('mc-action').value=d.id?'edit':'create';
  document.getElementById('mc-id').value=d.id;
  document.getElementById('mc-code').value=d.code;
  document.getElementById('mc-desc').value=d.description||'';
  document.getElementById('mc-type').value=d.type;
  document.getElementById('mc-value').value=d.value;
  document.getElementById('mc-minorder').value=d.min_order_ngn||0;
  document.getElementById('mc-maxuses').value=d.max_uses||0;
  document.getElementById('mc-status').value=d.status;
  document.getElementById('mc-from').value=d.valid_from?d.valid_from.replace(' ','T').slice(0,16):'';
  document.getElementById('mc-until').value=d.valid_until?d.valid_until.replace(' ','T').slice(0,16):'';
  updateMcLabel(); openModal('mc-modal');
}
(function(){
  var n=new Date(); n.setSeconds(0,0);
  document.getElementById('mc-from').value=n.toISOString().slice(0,16);
  var l=new Date(n); l.setMonth(l.getMonth()+1);
  document.getElementById('mc-until').value=l.toISOString().slice(0,16);
})();
</script>

<?php include __DIR__ . '/../includes/footer_merchant.php'; ?>
