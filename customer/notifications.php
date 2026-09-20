<?php
require_once __DIR__.'/../includes/bootstrap.php';
Auth::requireRole('customer');
DB::query('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL',[Auth::id()]);
$page   = max(1,(int)($_GET['page']??1)); $per = 20;
$total  = DB::count('SELECT COUNT(*) FROM notifications WHERE user_id=?',[Auth::id()]);
$notifs = DB::fetchAll('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT '.$per.' OFFSET '.(($page-1)*$per),[Auth::id()]);
$pageTitle='Notifications'; $activePage='';
include __DIR__.'/../includes/header_customer.php';
?>
<div class="wrap-full" style="padding-top:14px;padding-bottom:28px">

  <div class="ju-panel" style="margin-bottom:14px">
    <div class="ju-panel-head">
      <div class="ju-panel-title">🔔 Notifications</div>
      <span style="font-size:.78rem;color:var(--text-muted)"><?=number_format($total)?> total</span>
    </div>
  </div>

  <?php if (empty($notifs)): ?>
    <div class="empty-state" style="background:#fff;border-radius:var(--r-md);box-shadow:var(--sh-xs);padding:48px 24px">
      <span class="empty-icon">🔔</span>
      <p style="font-weight:600;margin-bottom:6px">No notifications yet</p>
      <p style="font-size:.82rem">You'll receive updates about orders, promotions and more here.</p>
    </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:8px;max-width:680px">
    <?php foreach ($notifs as $n): ?>
    <div class="card" style="padding:12px 14px;<?= !$n['read_at']?'border-left:3px solid var(--ju)':''?>">
      <div style="display:flex;align-items:flex-start;gap:10px">
        <div style="width:36px;height:36px;border-radius:50%;background:<?= !$n['read_at']?'var(--ju-pale)':'var(--bg)'?>;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0">
          🔔
        </div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:3px">
            <div style="font-weight:700;font-size:.86rem;color:var(--black)"><?=e($n['title'])?></div>
            <div style="font-size:.7rem;color:var(--text-muted);white-space:nowrap;flex-shrink:0"><?=timeAgo($n['created_at'])?></div>
          </div>
          <div style="font-size:.82rem;color:var(--text-soft);line-height:1.5"><?=e($n['message'])?></div>
          <?php if ($n['link']): ?>
          <a href="<?=e($n['link'])?>" class="btn btn-ghost btn-xs" style="margin-top:7px">View →</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?=paginate($total,$per,$page)?>
  <?php endif; ?>

</div>
<?php include __DIR__.'/../includes/footer_customer.php'; ?>
