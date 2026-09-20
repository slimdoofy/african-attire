<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle='Products Moderation'; $activeNav='products';

if(isset($_GET['approve'])){
    DB::update('products',['status'=>'approved'],'id=?',[(int)$_GET['approve']]);
    // Notify merchant
    $p=$_GET['approve'];
    $prod=DB::fetch('SELECT p.*,s.user_id FROM products p JOIN shops s ON s.id=p.shop_id WHERE p.id=?',[$p]);
    if($prod) DB::insert('notifications',['user_id'=>$prod['user_id'],'type'=>'product_approved','title'=>"Product approved! 🎉",'message'=>"Your product \"{$prod['name']}\" is now live on the marketplace.",'link'=>BASE_URL.'/merchant/products.php']);
    flash('Product approved.','success'); redirect(BASE_URL.'/admin/products.php');
}
if(isset($_GET['reject'])){
    DB::update('products',['status'=>'rejected'],'id=?',[(int)$_GET['reject']]);
    $p=$_GET['reject'];
    $prod=DB::fetch('SELECT p.*,s.user_id FROM products p JOIN shops s ON s.id=p.shop_id WHERE p.id=?',[$p]);
    if($prod) DB::insert('notifications',['user_id'=>$prod['user_id'],'type'=>'product_rejected','title'=>"Product not approved",'message'=>"Your product \"{$prod['name']}\" was not approved. Contact support for details.",'link'=>BASE_URL.'/merchant/products.php']);
    flash('Product rejected.'); redirect(BASE_URL.'/admin/products.php');
}
if(isset($_GET['feature'])){
    $pid=(int)$_GET['feature'];
    $curr=DB::fetch('SELECT featured FROM products WHERE id=?',[$pid]);
    DB::update('products',['featured'=>$curr['featured']?0:1],'id=?',[$pid]);
    flash('Featured status toggled.'); redirect(BASE_URL.'/admin/products.php');
}

include __DIR__ . '/../includes/header_admin.php';
$status   = $_GET['status']    ?? '';
$q        = trim($_GET['q']    ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to']   ?? '');
$page     = max(1,(int)($_GET['page'] ?? 1)); $per = 20;
$where = ['1=1']; $params = [];
if($status)  { $where[] = "p.status=?";                          $params[] = $status; }
if($q)       { $where[] = "(p.name LIKE ? OR s.shop_name LIKE ?)"; $params = array_merge($params,["%$q%","%$q%"]); }
if($dateFrom){ $where[] = 'DATE(p.created_at) >= ?';             $params[] = $dateFrom; }
if($dateTo)  { $where[] = 'DATE(p.created_at) <= ?';             $params[] = $dateTo; }
$wStr = implode(' AND ', $where);

// Excel export
if(isset($_GET['export']) && $_GET['export']==='excel'){
    $all = DB::fetchAll("SELECT p.name,p.price,p.cost_price,p.quantity,p.status,p.created_at,s.shop_name,c.name cat FROM products p JOIN shops s ON s.id=p.shop_id JOIN categories c ON c.id=p.category_id WHERE $wStr ORDER BY p.created_at DESC",$params);
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="products-'.date('Y-m-d').'.xls"');
    echo '<table><thead><tr><th>Product</th><th>Category</th><th>Shop</th><th>Price (NGN)</th><th>Cost Price</th><th>Stock</th><th>Status</th><th>Date Added</th></tr></thead><tbody>';
    foreach($all as $r) echo '<tr><td>'.e($r['name']).'</td><td>'.e($r['cat']).'</td><td>'.e($r['shop_name']).'</td><td>'.number_format($r['price'],2).'</td><td>'.number_format($r['cost_price']??0,2).'</td><td>'.$r['quantity'].'</td><td>'.e($r['status']).'</td><td>'.e($r['created_at']).'</td></tr>';
    echo '</tbody></table>'; exit;
}

$total = DB::count("SELECT COUNT(*) FROM products p JOIN shops s ON s.id=p.shop_id WHERE $wStr",$params);
$products=DB::fetchAll("SELECT p.*,s.shop_name,c.name cat_name,c.icon cat_icon,(SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) img FROM products p JOIN shops s ON s.id=p.shop_id JOIN categories c ON c.id=p.category_id WHERE $wStr ORDER BY p.created_at DESC LIMIT $per OFFSET ".(($page-1)*$per),$params);
?>
<div class="dash-header"><h1 class="dash-title">🏷 Products Moderation</h1><p class="dash-sub"><?=$total?> total products</p></div>
<div class="flex-between mb-3" style="flex-wrap:wrap;gap:.75rem">
  <div class="tab-bar" style="border:none;margin-bottom:0">
    <?php foreach([''=> 'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','archived'=>'Archived'] as $v=>$l):?>
    <a href="?status=<?=$v?>" class="tab-btn <?=$status===$v?'active':''?>"><?=$l?></a>
    <?php endforeach;?>
  </div>
  <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
  <form method="GET" style="display:contents">
    <input type="hidden" name="status" value="<?=e($status)?>">
    <input type="text" name="q" class="form-control" placeholder="Search…" value="<?=e($q)?>" style="width:170px">
    <input type="date" name="date_from" class="form-control" style="width:130px" value="<?=e($dateFrom)?>">
    <input type="date" name="date_to"   class="form-control" style="width:130px" value="<?=e($dateTo)?>">
    <button class="btn btn-ghost btn-sm">Filter</button>
    <?php if($q||$dateFrom||$dateTo):?><a href="?status=<?=e($status)?>" class="btn btn-ghost btn-sm">Clear</a><?php endif;?>
  </form>
  <a href="?<?=http_build_query(array_merge($_GET,['export'=>'excel']))?>" class="btn btn-ghost btn-sm">📥 Export Excel</a>
  </div>
</div>
<?php if(empty($products)):?>
<div class="empty-state card"><span class="empty-icon">🏷</span><p>No products found.</p></div>
<?php else:?>
<div class="card"><div class="table-wrap"><table class="data-table">
  <thead><tr><th>Image</th><th>Product</th><th>Shop</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Featured</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($products as $p):?>
    <tr>
      <td><?php if($p['img']):?><img src="<?=imgUrl($p['img'])?>" style="width:40px;height:48px;object-fit:cover;border-radius:6px"><?php else:?><span style="font-size:1.5rem"><?=$p['cat_icon']?></span><?php endif;?></td>
      <td><div class="fw-600 text-sm"><?=e($p['name'])?></div><div class="text-xs text-muted"><?=e(substr($p['description']??'',0,50))?></div></td>
      <td class="text-sm text-muted"><?=e($p['shop_name'])?></td>
      <td><span class="badge badge-muted"><?=$p['cat_icon']?> <?=e($p['cat_name'])?></span></td>
      <td class="text-gold fw-600"><?=money($p['price'])?></td>
      <td><?=$p['quantity']?></td>
      <td><?=statusBadge($p['status'])?></td>
      <?php $fBadge = $p['featured'] ? 'badge-warning' : 'badge-muted'; $fLabel = $p['featured'] ? '⭐ Yes' : 'No'; ?>
      <td><span class="badge <?= $fBadge ?>"><?= $fLabel ?></span></td>
      <td>
        <div style="display:flex;gap:.3rem;flex-wrap:wrap">
          <?php if($p['status']==='pending'):?>
            <a href="?approve=<?=$p['id']?>&status=<?=e($status)?>" class="btn btn-success btn-sm" onclick="return confirm('Approve?')">✓</a>
            <a href="?reject=<?=$p['id']?>&status=<?=e($status)?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject?')">✗</a>
          <?php elseif($p['status']==='approved'):?>
            <a href="?reject=<?=$p['id']?>&status=<?=e($status)?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove?')">Remove</a>
          <?php elseif($p['status']==='rejected'):?>
            <a href="?approve=<?=$p['id']?>&status=<?=e($status)?>" class="btn btn-success btn-sm" onclick="return confirm('Approve?')">Approve</a>
          <?php endif;?>
          <a href="?feature=<?=$p['id']?>&status=<?=e($status)?>" class="btn btn-ghost btn-sm"><?=$p['featured']?'Unfeature':'⭐ Feature'?></a>
        </div>
      </td>
    </tr>
    <?php endforeach;?>
  </tbody>
</table></div></div>
<?=paginate($total,$per,$page,'?status='.urlencode($status).'&q='.urlencode($q))?>
<?php endif;?>
<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
