<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole('customer');
$pageTitle  = 'My Orders';
$activePage = 'orders';

$page   = max(1,(int)($_GET['page']??1)); $per = 10;
$status = trim($_GET['status']??'');
$where  = ['o.user_id=?']; $params = [Auth::id()];
if ($status) { $where[] = 'o.status=?'; $params[] = $status; }
$wStr   = implode(' AND ',$where);
$total  = DB::count("SELECT COUNT(*) FROM orders o WHERE $wStr", $params);
$orders = DB::fetchAll(
    "SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) item_count
     FROM orders o WHERE $wStr ORDER BY o.created_at DESC
     LIMIT $per OFFSET ".(($page-1)*$per), $params);

include __DIR__.'/../includes/header_customer.php';
?>
<div class="wrap-full" style="padding-top:14px;padding-bottom:28px">

  <!-- Page header -->
  <div class="ju-panel" style="margin-bottom:14px">
    <div class="ju-panel-head">
      <div class="ju-panel-title">📦 My Orders</div>
      <span style="font-size:.78rem;color:var(--text-muted)"><?=number_format($total)?> order<?=$total!==1?'s':''?></span>
    </div>
    <!-- Status filter tabs -->
    <div style="display:flex;gap:0;overflow-x:auto;border-bottom:1px solid var(--border-lt);padding:0 14px">
      <?php foreach ([''=> 'All','pending'=>'Pending','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $v=>$l): ?>
      <a href="?status=<?=urlencode($v)?>" style="display:flex;align-items:center;padding:9px 14px;font-size:.8rem;font-weight:600;white-space:nowrap;border-bottom:2px solid <?=$status===$v?'var(--ju)':'transparent'?>;color:<?=$status===$v?'var(--ju)':'var(--text-muted)'?>;text-decoration:none;transition:color .15s">
        <?=$l?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if (empty($orders)): ?>
    <div class="empty-state" style="background:#fff;border-radius:var(--r-md);box-shadow:var(--sh-xs);padding:48px 24px">
      <span class="empty-icon">📦</span>
      <p style="font-weight:600;margin-bottom:6px">No orders yet</p>
      <p style="font-size:.82rem;margin-bottom:14px">Your order history will appear here once you place an order.</p>
      <a href="<?=BASE_URL?>/customer/shop.php" class="btn btn-ju btn-sm">Start Shopping →</a>
    </div>
  <?php else: ?>

  <!-- Orders list -->
  <div style="display:flex;flex-direction:column;gap:10px">
    <?php foreach ($orders as $o): ?>
    <div class="card" style="padding:0;overflow:hidden">
      <!-- Order header bar -->
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;
                  padding:10px 16px;background:var(--bg);border-bottom:1px solid var(--border-lt)">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
          <div>
            <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em">Order Number</div>
            <div style="font-weight:700;color:var(--blue);font-size:.88rem"><?=e($o['order_number'])?></div>
          </div>
          <div>
            <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em">Date</div>
            <div style="font-size:.82rem;font-weight:500"><?=date('M j, Y',strtotime($o['created_at']))?></div>
          </div>
          <div>
            <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em">Items</div>
            <div style="font-size:.82rem;font-weight:500"><?=$o['item_count']?> item<?=$o['item_count']>1?'s':''?></div>
          </div>
          <div>
            <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em">Total</div>
            <div style="font-size:.88rem;font-weight:800;color:var(--black)"><?=money($o['total_amount'])?></div>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
          <?=statusBadge($o['status'])?>
          <?=statusBadge($o['payment_status'])?>
          <a href="<?=BASE_URL?>/customer/order-detail.php?id=<?=$o['id']?>" class="btn btn-ghost btn-sm">View Details →</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?=paginate($total,$per,$page,'?status='.urlencode($status))?>
  <?php endif; ?>

</div>
<?php include __DIR__.'/../includes/footer_customer.php'; ?>
