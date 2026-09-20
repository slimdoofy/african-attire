<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$cid     = (int)($_GET['id'] ?? 0);
$company = DB::fetch('SELECT * FROM logistics_companies WHERE id=?', [$cid]);
if (!$company) { flash('Company not found.','error'); redirect(BASE_URL.'/admin/logistics.php'); }
$pageTitle = e($company['company_name']); $activeNav = 'logistics';
include __DIR__ . '/../includes/header_admin.php';

$users  = DB::fetchAll('SELECT * FROM logistics_users WHERE company_id=? ORDER BY created_at', [$cid]);
$orders = DB::fetchAll(
    "SELECT ol.*, o.order_number, o.created_at order_date,
            COALESCE(u.name,o.guest_name) cust_name,
            s.shop_name
     FROM order_logistics ol
     JOIN orders o ON o.id=ol.order_id
     JOIN shops s  ON s.id=ol.shop_id
     LEFT JOIN users u ON u.id=o.user_id
     WHERE ol.company_id=?
     ORDER BY ol.assigned_at DESC", [$cid]);
?>
<div class="dash-head">
  <div>
    <a href="<?= BASE_URL ?>/admin/logistics.php" style="font-size:.8rem;color:var(--text-muted);text-decoration:none">← Logistics Partners</a>
    <h1 class="dash-title" style="margin-top:4px">🚚 <?= e($company['company_name']) ?></h1>
    <p class="dash-sub"><?= e($company['city']) ?>, <?= e($company['country']) ?> · <?= e($company['email']) ?></p>
  </div>
  <span class="badge <?= $company['status']==='active'?'badge-success':'badge-danger' ?>" style="font-size:.8rem;padding:6px 12px">
    <?= ucfirst($company['status']) ?>
  </span>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
  <!-- Contact info -->
  <div class="card">
    <div class="card-title" style="margin-bottom:12px">👤 Contact Details</div>
    <?php foreach (['Name'=>e($company['contact_first'].' '.$company['contact_last']),'Email'=>e($company['email']),'Phone'=>e($company['phone']),'City'=>e($company['city']),'Country'=>e($company['country'])] as $k=>$v): ?>
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-lt);font-size:.84rem">
      <span style="color:var(--text-muted)"><?=$k?></span><span><?=$v?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <!-- Stats -->
  <div class="card">
    <div class="card-title" style="margin-bottom:12px">📊 Statistics</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <?php $statuses = DB::fetchAll("SELECT status, COUNT(*) cnt FROM order_logistics WHERE company_id=? GROUP BY status",[$cid]);
      $stMap=[]; foreach($statuses as $ss) $stMap[$ss['status']]=$ss['cnt'];
      foreach(['assigned'=>['📩','Assigned'],'in_transit'=>['🚛','In Transit'],'delivered'=>['✅','Delivered'],'failed'=>['❌','Failed']] as $s=>[$ico,$lbl]): ?>
      <div style="background:var(--bg);border-radius:var(--r-md);padding:10px;text-align:center">
        <div style="font-size:1.2rem"><?=$ico?></div>
        <div style="font-family:var(--ff-head);font-size:1.1rem;font-weight:700"><?=$stMap[$s]??0?></div>
        <div style="font-size:.7rem;color:var(--text-muted)"><?=$lbl?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Portal users -->
<div class="card" style="margin-bottom:14px">
  <div class="card-head"><div class="card-title">👥 Portal Users</div></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td style="font-weight:600"><?= e($u['first_name'].' '.$u['last_name']) ?><?= $u['temp_password']?'<span class="badge badge-warning" style="margin-left:6px;font-size:.62rem">Temp PW</span>':'' ?></td>
          <td style="font-size:.82rem"><?= e($u['email']) ?></td>
          <td><?= statusBadge($u['role']) ?></td>
          <td><?= statusBadge($u['status']) ?></td>
          <td style="font-size:.78rem;color:var(--text-muted)"><?= $u['last_login_at']?date('M j, Y g:i A',strtotime($u['last_login_at'])):'Never' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Assigned orders -->
<div class="card">
  <div class="card-head"><div class="card-title">📦 Orders Assigned to This Partner</div><span class="badge badge-muted"><?= count($orders) ?></span></div>
  <?php if(empty($orders)): ?>
  <div class="empty-state" style="padding:24px"><p>No orders assigned yet.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Order #</th><th>Customer</th><th>Merchant</th><th>Tracking #</th><th>Status</th><th>Assigned</th></tr></thead>
      <tbody>
        <?php foreach ($orders as $a): ?>
        <tr>
          <td style="font-weight:700;color:var(--blue)"><?= e($a['order_number']) ?></td>
          <td style="font-size:.82rem"><?= e($a['cust_name']?:'Guest') ?></td>
          <td style="font-size:.82rem"><?= e($a['shop_name']) ?></td>
          <td style="font-size:.78rem"><?= e($a['tracking_number']?:'—') ?></td>
          <td><span class="badge badge-info"><?= ucwords(str_replace('_',' ',$a['status'])) ?></span></td>
          <td style="font-size:.76rem;color:var(--text-muted)"><?= date('M j, Y',strtotime($a['assigned_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
