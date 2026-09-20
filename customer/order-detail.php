<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole('customer');
$id    = (int)($_GET['id']??0);
$order = DB::fetch('SELECT o.*, u.name cust_name, u.email cust_email FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.id=? AND o.user_id=?', [$id, Auth::id()]);
if (!$order) { flash('Order not found.','error'); redirect(BASE_URL.'/customer/orders.php'); }
$items = DB::fetchAll('SELECT oi.*, s.shop_name FROM order_items oi JOIN shops s ON s.id=oi.shop_id WHERE oi.order_id=?', [$id]);
$pageTitle = 'Order '.$order['order_number'];
$activePage= 'orders';
include __DIR__.'/../includes/header_customer.php';
?>
<div class="wrap-full" style="padding-top:14px;padding-bottom:28px">

  <div class="breadcrumb">
    <a href="<?=BASE_URL?>/">Home</a><span class="sep">›</span>
    <a href="<?=BASE_URL?>/customer/orders.php">My Orders</a><span class="sep">›</span>
    <span class="cur"><?=e($order['order_number'])?></span>
  </div>

  <!-- Status banner -->
  <?php
    $colors = [
      'pending'    =>['bg'=>'#FFF8E1','border'=>'#FFE082','text'=>'#856404','icon'=>'⏳'],
      'processing' =>['bg'=>'#E3F2FD','border'=>'#90CAF9','text'=>'#0D47A1','icon'=>'🔄'],
      'shipped'    =>['bg'=>'#E8F5E9','border'=>'#A5D6A7','text'=>'#1B5E20','icon'=>'🚚'],
      'delivered'  =>['bg'=>'#E8F5E9','border'=>'#A5D6A7','text'=>'#1B5E20','icon'=>'✅'],
      'cancelled'  =>['bg'=>'#FFEBEE','border'=>'#EF9A9A','text'=>'#B71C1C','icon'=>'❌'],
    ];
    $sc = $colors[$order['status']] ?? $colors['pending'];
  ?>
  <div style="background:<?=$sc['bg']?>;border:1px solid <?=$sc['border']?>;border-radius:var(--r-lg);padding:12px 18px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div style="display:flex;align-items:center;gap:10px">
      <span style="font-size:1.6rem"><?=$sc['icon']?></span>
      <div>
        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:<?=$sc['text']?>;font-weight:700">Order Status</div>
        <div style="font-size:1rem;font-weight:800;color:<?=$sc['text']?>"><?=ucfirst($order['status'])?></div>
      </div>
    </div>
    <div style="text-align:right">
      <div style="font-size:.7rem;color:var(--text-muted)">Order Number</div>
      <div style="font-weight:800;color:var(--black)"><?=e($order['order_number'])?></div>
    </div>
  </div>

  <div class="order-detail-grid">

    <!-- Items + address -->
    <div>
      <div class="card" style="margin-bottom:12px">
        <div class="card-head">
          <div class="card-title">🛍 Items Ordered</div>
          <span class="badge badge-muted"><?=count($items)?> item<?=count($items)>1?'s':''?></span>
        </div>
        <?php foreach ($items as $it): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-lt)">
          <div style="width:48px;height:54px;border-radius:var(--r-sm);background:var(--bg);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;overflow:hidden">👗</div>
          <div style="flex:1;min-width:0">
            <div style="font-size:.86rem;font-weight:600;color:var(--black)"><?=e($it['product_name'])?></div>
            <div style="font-size:.74rem;color:var(--text-muted)"><?=e($it['shop_name'])?><?=$it['size']?' · '.$it['size']:''?> · qty <?=$it['quantity']?></div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-weight:700;font-size:.9rem"><?=money($it['price']*$it['quantity'])?></div>
            <?=statusBadge($it['status'])?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <div class="card-title" style="margin-bottom:10px">📍 Delivery Address</div>
        <p style="font-size:.86rem;color:var(--text-soft);white-space:pre-line;line-height:1.65"><?=e($order['delivery_address'])?></p>
      </div>
    </div>

    <!-- Order summary -->
    <div>
      <div class="card" style="margin-bottom:12px">
        <div class="card-title" style="margin-bottom:12px">💰 Order Summary</div>
        <?php $rows = [
          'Order #'        => '<span style="font-weight:700;color:var(--blue)">'.e($order['order_number']).'</span>',
          'Date'           => date('M j, Y \a\t g:i A',strtotime($order['created_at'])),
          'Payment Method' => ucfirst($order['payment_method']),
          'Payment Status' => statusBadge($order['payment_status']),
        ]; foreach ($rows as $k=>$v): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--border-lt);font-size:.84rem">
          <span style="color:var(--text-muted)"><?=$k?></span><span><?=$v?></span>
        </div>
        <?php endforeach; ?>
        <?php if (!empty($order['payment_reference'])): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--border-lt);font-size:.82rem">
          <span style="color:var(--text-muted)">Reference</span>
          <span style="font-size:.78rem;color:var(--text-muted)"><?=e($order['payment_reference'])?></span>
        </div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;font-size:1.05rem;font-weight:800">
          <span>Total</span>
          <span style="color:var(--blue)"><?=money($order['total_amount'])?></span>
        </div>
        <?php if ($order['status']==='pending'): ?>
        <form method="POST" action="<?=BASE_URL?>/api/orders.php" onsubmit="return confirm('Cancel this order?')" style="margin-top:8px">
          <input type="hidden" name="action" value="cancel">
          <input type="hidden" name="order_id" value="<?=$order['id']?>">
          <button type="submit" class="btn btn-danger btn-sm btn-full">Cancel Order</button>
        </form>
        <?php endif; ?>
      </div>

      <a href="<?=BASE_URL?>/customer/orders.php" class="btn btn-ghost btn-sm btn-full">← Back to My Orders</a>
    </div>

  </div>
</div>
<?php include __DIR__.'/../includes/footer_customer.php'; ?>
