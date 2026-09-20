<?php
require_once __DIR__.'/../includes/bootstrap.php';
Auth::requireRole('customer');
$pageTitle='My Wishlist'; $activePage='wishlist';
$products = DB::fetchAll("
    SELECT p.*, s.shop_name, c.name cat_name, c.icon category_icon,
           (SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) primary_image
    FROM wishlists w JOIN products p ON p.id=w.product_id
    JOIN shops s ON s.id=p.shop_id JOIN categories c ON c.id=p.category_id
    WHERE w.user_id=? AND p.status='approved' ORDER BY w.added_at DESC",[Auth::id()]);
include __DIR__.'/../includes/header_customer.php';
?>
<div class="wrap-full" style="padding-top:14px;padding-bottom:28px">

  <div class="ju-panel" style="margin-bottom:14px">
    <div class="ju-panel-head">
      <div class="ju-panel-title">♡ My Wishlist</div>
      <span style="font-size:.78rem;color:var(--text-muted)"><?=count($products)?> saved item<?=count($products)!==1?'s':''?></span>
    </div>
  </div>

  <?php if (empty($products)): ?>
    <div class="empty-state" style="background:#fff;border-radius:var(--r-md);box-shadow:var(--sh-xs);padding:48px 24px">
      <span class="empty-icon">♡</span>
      <p style="font-weight:600;margin-bottom:6px">Your wishlist is empty</p>
      <p style="font-size:.82rem;margin-bottom:14px">Tap the ♡ on any product to save it here.</p>
      <a href="<?=BASE_URL?>/customer/shop.php" class="btn btn-ju btn-sm">Discover Products →</a>
    </div>
  <?php else: ?>
    <div class="shop-grid-6">
      <?php foreach ($products as $p): include __DIR__.'/../includes/product_card.php'; endforeach; ?>
    </div>
  <?php endif; ?>

</div>
<?php include __DIR__.'/../includes/footer_customer.php'; ?>
