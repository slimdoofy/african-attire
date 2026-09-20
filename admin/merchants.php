<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle='Merchants'; $activeNav='merchants';

// Quick approve/reject from URL
if (isset($_GET['approve'])) {
    $sid = (int)$_GET['approve'];
    DB::update('shops',['status'=>'approved'],'id=?',[$sid]);
    $shop = DB::fetch('SELECT s.*,u.name merchant_name,u.email FROM shops s JOIN users u ON u.id=s.user_id WHERE s.id=?',[$sid]);
    if ($shop) {
        // In-app notification
        DB::insert('notifications',['user_id'=>$shop['user_id'],'type'=>'shop_approved',
            'title'=>'Your shop is approved! 🎉',
            'message'=>"Congratulations! Your shop \"{$shop['shop_name']}\" is now live. Start adding products!",
            'link'=>BASE_URL.'/merchant/products.php']);
        // Email notification
        sendMerchantApprovalEmail($shop['email'], $shop['merchant_name'], $shop['shop_name']);
    }
    flash('Shop approved — merchant has been notified by email.','success');
    redirect(BASE_URL.'/admin/merchants.php');
}
if (isset($_GET['reject'])) {
    $sid = (int)$_GET['reject'];
    DB::update('shops',['status'=>'rejected'],'id=?',[$sid]);
    $shop = DB::fetch('SELECT s.*,u.email FROM shops s JOIN users u ON u.id=s.user_id WHERE s.id=?',[$sid]);
    if ($shop) {
        DB::insert('notifications',['user_id'=>$shop['user_id'],'type'=>'shop_rejected',
            'title'=>'Shop application update',
            'message'=>"Unfortunately your shop \"{$shop['shop_name']}\" was not approved. Contact support for details.",
            'link'=>BASE_URL.'/merchant/register.php']);
    }
    flash('Shop rejected.','error');
    redirect(BASE_URL.'/admin/merchants.php');
}
if (isset($_GET['suspend'])) {
    DB::update('shops',['status'=>'suspended'],'id=?',[(int)$_GET['suspend']]);
    flash('Shop suspended.');
    redirect(BASE_URL.'/admin/merchants.php');
}

// ── Admin: update merchant email ──────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update_email') {
    $uid      = (int)($_POST['user_id']   ?? 0);
    $newEmail = strtolower(trim($_POST['new_email'] ?? ''));
    if ($uid && filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $taken = (int)DB::count('SELECT COUNT(*) FROM users WHERE email=? AND id!=?', [$newEmail, $uid]);
        if ($taken) {
            flash('That email is already used by another account.', 'error');
        } else {
            DB::update('users', ['email' => $newEmail], 'id=?', [$uid]);
            auditLog('merchant_email_changed', "Merchant #$uid email → $newEmail", 'user', $uid, 'admin');
            flash('Merchant email updated to <strong>'.e($newEmail).'</strong>.', 'success');
        }
    } else {
        flash('Please enter a valid email address.', 'error');
    }
    redirect(BASE_URL.'/admin/merchants.php');
}

include __DIR__ . '/../includes/header_admin.php';

$status = $_GET['status'] ?? '';
$q      = trim($_GET['q'] ?? '');
$page   = max(1,(int)($_GET['page']??1)); $per = 20;

$where = ['1=1']; $params = [];
if ($status) { $where[] = "s.status=?"; $params[] = $status; }
if ($q)      { $where[] = "(s.shop_name LIKE ? OR u.name LIKE ? OR u.email LIKE ?)"; $params=array_merge($params,["%$q%","%$q%","%$q%"]); }
$wStr  = implode(' AND ',$where);
$total = DB::count("SELECT COUNT(*) FROM shops s JOIN users u ON u.id=s.user_id WHERE $wStr",$params);
$shops = DB::fetchAll("
    SELECT s.*, u.name owner, u.email,
           (SELECT COUNT(*) FROM products WHERE shop_id=s.id AND status='approved') prod_count,
           (SELECT COUNT(DISTINCT o.id) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.shop_id=s.id) order_count
    FROM shops s JOIN users u ON u.id=s.user_id
    WHERE $wStr ORDER BY s.created_at DESC
    LIMIT $per OFFSET ".(($page-1)*$per), $params);
?>

<div class="dash-header">
  <h1 class="dash-title">🏪 Merchants</h1>
  <p class="dash-sub"><?= $total ?> merchants on platform</p>
</div>

<div class="flex-between mb-3" style="flex-wrap:wrap;gap:.75rem">
  <div class="tab-bar" style="border:none;margin-bottom:0">
    <?php foreach ([''=> 'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','suspended'=>'Suspended'] as $v=>$l): ?>
    <a href="?status=<?= $v ?>" class="tab-btn <?= $status===$v?'active':''?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <form method="GET" style="display:flex;gap:.5rem">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <input type="text" name="q" class="form-control" placeholder="Search…" value="<?= e($q) ?>" style="width:200px">
    <button class="btn btn-ghost btn-sm">Search</button>
  </form>
</div>

<?php if(empty($shops)): ?>
<div class="empty-state card"><span class="empty-icon">🏪</span><p>No merchants found.</p></div>
<?php else: ?>
<div class="card"><div class="table-wrap"><table class="data-table">
  <thead><tr><th>Shop</th><th>Owner</th><th>Status</th><th>Products</th><th>Orders</th><th>Revenue</th><th>Commission</th><th>Joined</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($shops as $s): ?>
    <tr>
      <td>
        <div class="flex-center gap-2">
          <?php if($s['logo']): ?><img src="<?= imgUrl($s['logo']) ?>" style="width:34px;height:34px;border-radius:50%;object-fit:cover"><?php else: ?><span>🏪</span><?php endif; ?>
          <div>
            <div class="fw-600 text-sm"><?= e($s['shop_name']) ?></div>
            <div class="text-xs text-muted">📍 <?= e($s['city']??'Nigeria') ?></div>
          </div>
        </div>
      </td>
      <td><div class="text-sm"><?= e($s['owner']) ?></div><div class="text-xs text-muted"><?= e($s['email']) ?></div></td>
      <td><?= statusBadge($s['status']) ?></td>
      <td><?= $s['prod_count'] ?></td>
      <td><?= $s['order_count'] ?></td>
      <td class="text-gold fw-600"><?= money($s['total_revenue']) ?></td>
      <td><?php
        $shopMarkup = isset($s['markup_rate']) && $s['markup_rate'] !== null
            ? (float)$s['markup_rate']
            : (float)getSetting('platform_markup', 5);
      ?><?= $shopMarkup ?>%</td>
      <td class="text-xs text-muted"><?= date('M j, Y',strtotime($s['created_at'])) ?></td>
      <td>
        <div style="display:flex;gap:.3rem;flex-wrap:wrap">
          <?php if($s['status']==='pending'): ?>
            <a href="?approve=<?= $s['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Approve?')">✓ Approve</a>
            <a href="?reject=<?= $s['id'] ?>"  class="btn btn-danger  btn-sm" onclick="return confirm('Reject?')">✗ Reject</a>
          <?php elseif($s['status']==='approved'): ?>
            <a href="?suspend=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Suspend this shop?')">Suspend</a>
          <?php elseif($s['status']==='suspended'): ?>
            <a href="?approve=<?= $s['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Reinstate?')">Reinstate</a>
          <?php endif; ?>
          <button class="btn btn-ghost btn-sm"
                  onclick="openEmailModal(<?= $s['user_id'] ?>, '<?= e(addslashes($s['email'])) ?>', '<?= e(addslashes($s['owner'])) ?>')">
            ✉ Email
          </button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table></div></div>
<?= paginate($total, $per, $page, '?status='.urlencode($status).'&q='.urlencode($q)) ?>
<?php endif; ?>

<!-- ── Change Merchant Email Modal ── -->
<div class="modal-bg" id="email-modal">
  <div class="modal" style="max-width:440px;background:#fff;border-radius:var(--r-lg);padding:24px;width:90%">
    <div class="modal-head">
      <h3 class="modal-title">✉ Change Merchant Email</h3>
      <button class="modal-close" onclick="closeEmailModal()" style="background:none;border:none;cursor:pointer;font-size:1.2rem">✕</button>
    </div>
    <div style="padding:4px 0 10px;font-size:.84rem;color:var(--text-muted)" id="email-modal-name"></div>
    <form method="POST">
      <input type="hidden" name="action"  value="update_email">
      <input type="hidden" name="user_id" id="email-modal-uid">
      <div class="form-group">
        <label class="form-label">Current Email</label>
        <input type="text" id="email-modal-current" class="form-control"
               readonly style="background:var(--bg);color:var(--text-muted)">
      </div>
      <div class="form-group">
        <label class="form-label">New Email Address *</label>
        <input type="email" name="new_email" id="email-modal-new" class="form-control"
               required placeholder="new@email.com">
        <div class="form-hint">This will update the merchant's login email immediately.</div>
      </div>
      <div class="alert alert-warning" style="font-size:.8rem;margin-top:4px">
        ⚠️ The merchant will need to use the new email address to log in going forward.
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button type="button" class="btn btn-ghost" onclick="closeEmailModal()">Cancel</button>
        <button type="submit" class="btn btn-ju"
                onclick="return confirm('Change this merchant\'s email address?')">
          Update Email
        </button>
      </div>
    </form>
  </div>
</div>
<script>
function openEmailModal(uid, email, name) {
  document.getElementById('email-modal-uid').value     = uid;
  document.getElementById('email-modal-current').value = email;
  document.getElementById('email-modal-new').value     = '';
  document.getElementById('email-modal-name').textContent = name;
  document.getElementById('email-modal').classList.add('open');
}
function closeEmailModal() {
  document.getElementById('email-modal').classList.remove('open');
}
document.addEventListener('click', function(e){
  if (e.target === document.getElementById('email-modal')) closeEmailModal();
});
</script>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
