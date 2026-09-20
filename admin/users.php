<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle='Customers'; $activeNav='users';

if(isset($_GET['suspend'])){
    DB::update('users',['status'=>'suspended'],'id=? AND role="customer"',[(int)$_GET['suspend']]);
    flash('Account suspended.');redirect(BASE_URL.'/admin/users.php');
}
if(isset($_GET['activate'])){
    DB::update('users',['status'=>'active'],'id=? AND role="customer"',[(int)$_GET['activate']]);
    flash('Account activated.');redirect(BASE_URL.'/admin/users.php');
}

include __DIR__ . '/../includes/header_admin.php';
$q        = trim($_GET['q']         ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to']   ?? '');
$page     = max(1,(int)($_GET['page']??1)); $per=25;
$where = "role='customer'"; $params=[];
if($q)       { $where .= " AND (name LIKE ? OR email LIKE ?)"; $params=array_merge($params,["%$q%","%$q%"]); }
if($dateFrom){ $where .= " AND DATE(created_at) >= ?"; $params[]=$dateFrom; }
if($dateTo)  { $where .= " AND DATE(created_at) <= ?"; $params[]=$dateTo; }

// Excel export
if(isset($_GET['export']) && $_GET['export']==='excel'){
    $all=DB::fetchAll("SELECT u.name,u.email,u.phone,u.status,u.created_at,(SELECT COUNT(*) FROM orders WHERE user_id=u.id) order_count,(SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id=u.id) total_spent FROM users u WHERE $where ORDER BY u.created_at DESC",$params);
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="customers-'.date('Y-m-d').'.xls"');
    echo '<table><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent (NGN)</th><th>Status</th><th>Joined</th></tr></thead><tbody>';
    foreach($all as $r) echo '<tr><td>'.e($r['name']).'</td><td>'.e($r['email']).'</td><td>'.e($r['phone']??'').'</td><td>'.$r['order_count'].'</td><td>'.number_format($r['total_spent'],2).'</td><td>'.e($r['status']).'</td><td>'.e($r['created_at']).'</td></tr>';
    echo '</tbody></table>'; exit;
}

$total=DB::count("SELECT COUNT(*) FROM users WHERE $where",$params);
$users=DB::fetchAll("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) order_count, (SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id=u.id) total_spent FROM users u WHERE $where ORDER BY u.created_at DESC LIMIT $per OFFSET ".(($page-1)*$per),$params);
?>
<div class="dash-header"><h1 class="dash-title">👥 Customers</h1><p class="dash-sub"><?=$total?> registered customers</p></div>
<div class="flex-between mb-3">
  <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
  <form method="GET" style="display:contents">
    <input type="text" name="q" class="form-control" placeholder="Name or email…" value="<?=e($q)?>" style="width:200px">
    <input type="date" name="date_from" class="form-control" style="width:130px" value="<?=e($dateFrom)?>" title="Joined from">
    <input type="date" name="date_to"   class="form-control" style="width:130px" value="<?=e($dateTo)?>"   title="Joined to">
    <button class="btn btn-ghost btn-sm">Filter</button>
    <?php if($q||$dateFrom||$dateTo):?><a href="?" class="btn btn-ghost btn-sm">Clear</a><?php endif;?>
  </form>
  <a href="?<?=http_build_query(array_merge($_GET,['export'=>'excel']))?>" class="btn btn-ghost btn-sm">📥 Export Excel</a>
  </div>
</div>
<div class="card"><div class="table-wrap"><table class="data-table">
  <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($users as $u):?>
    <tr>
      <td class="fw-600"><?=e($u['name'])?></td>
      <td class="text-muted text-sm"><?=e($u['email'])?></td>
      <td class="text-muted text-sm"><?=e($u['phone']??'—')?></td>
      <td><?=$u['order_count']?></td>
      <td class="text-gold fw-600"><?=money($u['total_spent'])?></td>
      <td><?=statusBadge($u['status'])?></td>
      <td class="text-xs text-muted"><?=date('M j, Y',strtotime($u['created_at']))?></td>
      <td>
        <?php if($u['status']==='active'):?>
          <a href="?suspend=<?=$u['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Suspend?')">Suspend</a>
        <?php else:?>
          <a href="?activate=<?=$u['id']?>" class="btn btn-success btn-sm" onclick="return confirm('Activate?')">Activate</a>
        <?php endif;?>
      </td>
    </tr>
    <?php endforeach;?>
  </tbody>
</table></div></div>
<?=paginate($total,$per,$page,'?q='.urlencode($q))?>
<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
