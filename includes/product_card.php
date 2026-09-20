<?php
// Jumia-style product card
$_inWish = Auth::check()
    ? (bool)DB::count('SELECT COUNT(*) FROM wishlists WHERE user_id=? AND product_id=?',[Auth::id(),$p['id']])
    : false;
$_disc   = ($p['original_price'] && $p['original_price'] > $p['price'])
    ? round((1 - $p['price'] / $p['original_price']) * 100) : 0;
$_isNew  = strtotime($p['created_at']) > strtotime('-21 days');
$_img    = !empty($p['primary_image']) ? imgUrl($p['primary_image']) : null;
?>
<div class="product-card" onclick="window.location='<?= BASE_URL ?>/customer/product.php?id=<?= $p['id'] ?>'">

  <div class="p-img">
    <?php if($_img): ?>
      <img src="<?= $_img ?>" alt="<?= e($p['name']) ?>" loading="lazy">
    <?php else: ?>
      <div class="p-img-ph"><?= $p['category_icon'] ?? '👗' ?></div>
    <?php endif; ?>
    <div class="p-badges">
      <?php if($_disc>0): ?><span class="p-badge pb-sale">-<?= $_disc ?>%</span><?php endif; ?>
      <?php if($_isNew && $_disc===0): ?><span class="p-badge pb-new">NEW</span><?php endif; ?>
      <?php if(!empty($p['featured'])): ?><span class="p-badge pb-feat">TOP</span><?php endif; ?>
    </div>
    <button class="p-wish" onclick="event.stopPropagation();toggleWish(<?= $p['id'] ?>,this)" title="Wishlist">
      <?= $_inWish ? '❤️' : '🤍' ?>
    </button>
  </div>

  <div class="p-body">
    <div class="p-shop"><?= e($p['shop_name']) ?></div>
    <div class="p-name"><?= e($p['name']) ?></div>
    <div class="p-price-row">
      <span class="p-price"><?= money($p['price']) ?></span>
      <?php if($_disc>0): ?>
        <span class="p-orig"><?= money($p['original_price']) ?></span>
        <span class="p-disc">-<?= $_disc ?>%</span>
      <?php endif; ?>
    </div>
    <?php if($p['quantity']<=0): ?>
      <div class="p-stock-out">Out of stock</div>
    <?php elseif($p['quantity']<=5): ?>
      <div class="p-stock-low">Only <?= $p['quantity'] ?> left!</div>
    <?php endif; ?>
  </div>

  <div class="p-actions">
    <button class="p-add-btn"
      onclick="event.stopPropagation();addToCart(<?= $p['id'] ?>)"
      <?= $p['quantity']<=0?'disabled':'' ?>>
      <?= $p['quantity']<=0 ? '✗ Out of Stock' : '🛒 Add to Cart' ?>
    </button>
    <a href="<?= BASE_URL ?>/customer/product.php?id=<?= $p['id'] ?>" class="p-view"
       onclick="event.stopPropagation()">View Details</a>
  </div>

</div>
