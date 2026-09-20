<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle='Payouts'; $activeNav='payouts';

if(isset($_GET['approve'])){
    DB::update('payouts',['status'=>'approved','processed_at'=>date('Y-m-d H:i:s')],'id=?',[(int)$_GET['approve']]);
    $py=DB::fetch('SELECT p.*,s.user_id,s.shop_name FROM payouts p JOIN shops s ON s.id=p.shop_id WHERE p.id=?',[(int)$_GET['approve']]);
    if($py) DB::insert('notifications',['user_id'=>$py['user_id'],'type'=>'payout_approved','title'=>'Payout approved! 💰','message'=>"Your payout of ".money($py['net_amount'])." has been approved and is being processed.",'link'=>BASE_URL.'/merchant/payouts.php']);
    flash('Payout approved.','success'); redirect(BASE_URL.'/admin/payouts.php');
}
if(isset($_GET['paid'])){
    DB::update('payouts',['status'=>'paid','processed_at'=>date('Y-m-d H:i:s')],'id=?',[(int)$_GET['paid']]);
    flash('Payout marked as paid.','success'); redirect(BASE_URL.'/admin/payouts.php');
}
if(isset($_GET['reject'])){
    DB::update('payouts',['status'=>'rejected'],'id=?',[(int)$_GET['reject']]);
    $py=DB::fetch('SELECT p.*,s.user_id,s.shop_name,s.total_withdrawn FROM payouts p JOIN shops s ON s.id=p.shop_id WHERE p.id=?',[(int)$_GET['reject']]);
    if($py){
        DB::query('UPDATE shops SET total_withdrawn=total_withdrawn-? WHERE id=?',[$py['amount'],$py['shop_id']]);
        DB::insert('notifications',['user_id'=>$py['user_id'],'type'=>'payout_rejected','title'=>'Payout rejected','message'=>"Your payout request of ".money($py['amount'])." was rejected. Contact support.",'link'=>BASE_URL.'/merchant/payouts.php']);
    }
    flash('Payout rejected.','error'); redirect(BASE_URL.'/admin/payouts.php');
}

include __DIR__ . '/../includes/header_admin.php';
$status=$_GET['status']??'';$page=max(1,(int)($_GET['page']??1));$per=20;
$where=['1=1'];$params=[];
if($status){$where[]="py.status=?";$params[]=$status;}
$wStr=implode(' AND ',$where);
$total=DB::count("SELECT COUNT(*) FROM payouts py WHERE $wStr",$params);
$payouts=DB::fetchAll("SELECT py.*,s.shop_name,s.bank_name,s.bank_account,s.bank_account_name FROM payouts py JOIN shops s ON s.id=py.shop_id WHERE $wStr ORDER BY py.requested_at DESC LIMIT $per OFFSET ".(($page-1)*$per),$params);

$pendingTotal = DB::count("SELECT COALESCE(SUM(amount),0) FROM payouts WHERE status='pending'");
$paidTotal    = DB::count("SELECT COALESCE(SUM(net_amount),0) FROM payouts WHERE status='paid'");
?>
<div class="dash-header"><h1 class="dash-title">💰 Payouts</h1><p class="dash-sub">Manage merchant withdrawal requests</p></div>
<div class="stats-row">
  <div class="stat-card"><div class="stat-label">Pending Payouts</div><div class="stat-value" style="color:var(--warning)"><?=money($pendingTotal)?></div></div>
  <div class="stat-card"><div class="stat-label">Total Paid Out</div><div class="stat-value" style="color:var(--success)"><?=money($paidTotal)?></div></div>
</div>
<div class="tab-bar">
  <?php foreach([''=> 'All','pending'=>'Pending','approved'=>'Approved','paid'=>'Paid','rejected'=>'Rejected'] as $v=>$l):?>
  <a href="?status=<?=$v?>" class="tab-btn <?=$status===$v?'active':''?>"><?=$l?></a>
  <?php endforeach;?>
</div>
<?php if(empty($payouts)):?>
<div class="empty-state card"><span class="empty-icon">💳</span><p>No payout requests.</p></div>
<?php else:?>
<div class="card"><div class="table-wrap"><table class="data-table">
  <thead><tr><th>Shop</th><th>Bank</th><th>Amount</th><th>Net Payout</th><th>Status</th><th>Requested</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($payouts as $py):?>
    <tr>
      <td class="fw-600 text-sm"><?=e($py['shop_name'])?></td>
      <td class="text-sm"><div class="fw-600"><?=e($py['bank_account_name'])?></div><div class="text-xs text-muted"><?=e($py['bank_name'])?> · <?=e($py['bank_account'])?></div></td>
      <td class="fw-600"><?=money($py['amount'])?></td>
      <td class="text-gold fw-600"><?=money($py['net_amount'])?></td>
      <td><?=statusBadge($py['status'])?></td>
      <td class="text-xs text-muted"><?=date('M j, Y',strtotime($py['requested_at']))?></td>
      <td>
        <?php if($py['status']==='pending'):?>
          <a href="?approve=<?=$py['id']?>&status=<?=e($status)?>" class="btn btn-success btn-sm" onclick="return confirm('Approve?')">✓ Approve</a>
          <a href="?reject=<?=$py['id']?>&status=<?=e($status)?>"  class="btn btn-danger  btn-sm" onclick="return confirm('Reject?')">✗</a>
        <?php elseif($py['status']==='approved'):?>
          <a href="?paid=<?=$py['id']?>&status=<?=e($status)?>" class="btn btn-gold btn-sm" onclick="return confirm('Mark as paid?')">Mark Paid</a>
        <?php endif;?>
      </td>
    </tr>
    <?php endforeach;?>
  </tbody>
</table></div></div>
<?=paginate($total,$per,$page,'?status='.urlencode($status))?>
<?php endif;?>
<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
