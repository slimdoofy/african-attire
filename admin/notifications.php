<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle='Notifications'; $activeNav='notifications';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uids  = $_POST['recipients'] ?? 'all';
    $title = trim($_POST['title']??'');
    $msg   = trim($_POST['message']??'');
    $link  = trim($_POST['link']??'');
    if($title && $msg){
        if($uids==='all'){
            $users=DB::fetchAll("SELECT id FROM users WHERE status='active'");
        } else {
            $users=DB::fetchAll("SELECT id FROM users WHERE role=? AND status='active'",[$uids]);
        }
        foreach($users as $u){
            DB::insert('notifications',['user_id'=>$u['id'],'type'=>'admin_broadcast','title'=>$title,'message'=>$msg,'link'=>$link?:null]);
        }
        flash('Notification sent to '.count($users).' users.','success');
    }
    redirect(BASE_URL.'/admin/notifications.php');
}

include __DIR__ . '/../includes/header_admin.php';
$page  = max(1,(int)($_GET['page']??1)); $per = 20;
$total = DB::count("SELECT COUNT(*) FROM notifications WHERE type='admin_broadcast'");
$recent= DB::fetchAll("SELECT n.*,u.name,u.email FROM notifications n JOIN users u ON u.id=n.user_id WHERE n.type='admin_broadcast' ORDER BY n.created_at DESC LIMIT $per OFFSET ".(($page-1)*$per));
?>
<div class="dash-header"><h1 class="dash-title">🔔 Notifications</h1><p class="dash-sub">Broadcast messages to users</p></div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
  <div class="card">
    <div class="card-title">📢 Send Broadcast Notification</div>
    <form method="POST" style="margin-top:1rem">
      <div class="form-group"><label class="form-label">Recipients</label>
        <select name="recipients" class="form-control">
          <option value="all">All Users (Customers + Merchants)</option>
          <option value="customer">Customers Only</option>
          <option value="merchant">Merchants Only</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required placeholder="e.g. New Collection Dropped!"></div>
      <div class="form-group"><label class="form-label">Message *</label><textarea name="message" class="form-control" rows="4" required placeholder="Message body…"></textarea></div>
      <div class="form-group"><label class="form-label">Link URL (optional)</label><input type="text" name="link" class="form-control" placeholder="/customer/shop.php"></div>
      <button type="submit" class="btn btn-blue btn-full">📢 Send Notification</button>
    </form>
  </div>
  <div class="card">
    <div class="card-title">📋 Recent Broadcasts</div>
    <?php if(empty($recent)):?>
    <div class="empty-state" style="padding:1.5rem"><p>No broadcasts sent yet.</p></div>
    <?php else:?>
    <?php
    $seen=[];
    foreach($recent as $n):
      $key=$n['title'].$n['created_at'];
      if(in_array($key,$seen)) continue; $seen[]=$key;
    ?>
    <div class="card card-sm" style="margin-bottom:.75rem">
      <div class="flex-between mb-1"><span class="fw-600 text-sm"><?=e($n['title'])?></span><span class="text-xs text-muted"><?=timeAgo($n['created_at'])?></span></div>
      <p class="text-sm"><?=e($n['message'])?></p>
    </div>
    <?php endforeach;?>
    <?php endif;?>
    <?= paginate($total, $per, $page) ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
