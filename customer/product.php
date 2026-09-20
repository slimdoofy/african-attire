<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$id = (int)($_GET['id'] ?? 0);
$p  = DB::fetch("
    SELECT p.*, s.id shop_id, s.shop_name, s.logo shop_logo, s.city shop_city, s.description shop_desc,
           c.name cat_name, c.icon category_icon
    FROM products p JOIN shops s ON s.id=p.shop_id JOIN categories c ON c.id=p.category_id
    WHERE p.id=? AND p.status='approved'",[$id]);
if (!$p) { flash('Product not found.','error'); redirect(BASE_URL.'/customer/shop.php'); }

$images      = DB::fetchAll('SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order',[$id]);
$sizes       = $p['sizes'] ? array_map('trim', explode(',', $p['sizes'])) : [];
$related     = DB::fetchAll("
    SELECT p.*, s.shop_name, c.name cat_name, c.icon category_icon,
           (SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) primary_image
    FROM products p JOIN shops s ON s.id=p.shop_id JOIN categories c ON c.id=p.category_id
    WHERE p.category_id=? AND p.id!=? AND p.status='approved' ORDER BY p.sales_count DESC LIMIT 6",
    [$p['category_id'],$id]);
$inWish      = Auth::check() ? (bool)DB::count('SELECT COUNT(*) FROM wishlists WHERE user_id=? AND product_id=?',[Auth::id(),$id]) : false;
$disc        = ($p['original_price'] && $p['original_price'] > $p['price']) ? round((1-$p['price']/$p['original_price'])*100) : 0;
$shopProdCnt = DB::count("SELECT COUNT(*) FROM products WHERE shop_id=? AND status='approved'",[$p['shop_id']]);

$pageTitle  = $p['name']; $activePage = 'shop';
include __DIR__.'/../includes/header_customer.php';
?>

<div class="wrap" style="padding-top:1.5rem;padding-bottom:3.5rem">

  <div class="breadcrumb">
    <a href="<?= BASE_URL ?>/">Home</a><span class="sep">›</span>
    <a href="<?= BASE_URL ?>/customer/shop.php">Shop</a><span class="sep">›</span>
    <a href="<?= BASE_URL ?>/customer/shop.php?category=<?= $p['category_id'] ?>"><?= e($p['cat_name']) ?></a><span class="sep">›</span>
    <span class="cur"><?= e($p['name']) ?></span>
  </div>

  <div class="pdp-layout">

    <!-- ─── GALLERY ────────────────────────────────────────────── -->
    <div class="pdp-gallery">
      <div class="pdp-main-img">
        <?php if (!empty($images)): ?>
          <img id="main-img" src="<?= imgUrl($images[0]['image_path']) ?>" alt="<?= e($p['name']) ?>">
        <?php else: ?>
          <div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:9rem;opacity:.15"><?= $p['category_icon'] ?></div>
        <?php endif; ?>
      </div>

      <?php if (count($images) > 1): ?>
      <div class="pdp-thumbs">
        <?php foreach ($images as $idx => $img): ?>
        <div class="pdp-thumb <?= $idx===0?'active':''?>" onclick="switchImg(this,'<?= imgUrl($img['image_path']) ?>')">
          <img src="<?= imgUrl($img['image_path']) ?>" alt="">
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Trust grid -->
      <div class="pdp-trust">
        <div class="pdp-trust-item"><span>🔒</span><div><strong>Secure checkout</strong>Paystack protected</div></div>
        <div class="pdp-trust-item"><span>🚚</span><div><strong>Fast delivery</strong>3–7 days nationwide</div></div>
        <div class="pdp-trust-item"><span>↩️</span><div><strong>Easy returns</strong>7-day policy</div></div>
        <div class="pdp-trust-item"><span>✅</span><div><strong>Verified seller</strong>Quality guaranteed</div></div>
      </div>
    </div>

    <!-- ─── INFO PANE ───────────────────────────────────────────── -->
    <div>
      <a href="<?= BASE_URL ?>/customer/merchant.php?id=<?= $p['shop_id'] ?>" class="pdp-shop-link"><?= e($p['shop_name']) ?></a>
      &nbsp;<span style="color:var(--g300);font-size:.75rem">·</span>&nbsp;
      <span style="font-size:.75rem;color:var(--g400)"><?= e($p['cat_name']) ?></span>

      <h1 class="pdp-title"><?= e($p['name']) ?></h1>

      <!-- Price -->
      <div class="pdp-price-box">
        <span class="pdp-price"><?= money($p['price']) ?></span>
        <?php if ($disc > 0): ?>
          <span class="pdp-orig"><?= money($p['original_price']) ?></span>
          <span class="pdp-disc-badge">Save <?= $disc ?>%</span>
        <?php endif; ?>
      </div>

      <!-- Badges -->
      <div class="pdp-badges">
        <?php if ($p['quantity'] > 10): ?>
          <span class="badge badge-success">✓ In Stock</span>
        <?php elseif ($p['quantity'] > 0): ?>
          <span class="badge badge-warning">⚡ Only <?= $p['quantity'] ?> left</span>
        <?php else: ?>
          <span class="badge badge-danger">✗ Out of Stock</span>
        <?php endif; ?>
        <span class="badge badge-muted"><?= ucfirst($p['gender']) ?></span>
        <?php if ($disc > 0): ?><span class="badge badge-danger">🔥 On Sale</span><?php endif; ?>
      </div>

      <!-- Sizes -->
      <?php if (!empty($sizes)): ?>
      <div style="margin-bottom:1.25rem">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem">
          <span style="font-size:.83rem;font-weight:700;color:var(--g700)">Select Size</span>
                  </div>
        <div class="size-grid">
          <?php foreach ($sizes as $s): ?>
          <button class="size-btn" data-size="<?= e($s) ?>"><?= e($s) ?></button>
          <?php endforeach; ?>
        </div>
        <input type="hidden" id="sel-size" value="">
      </div>
      <?php endif; ?>

      <!-- Qty -->
      <div style="margin-bottom:1.25rem">
        <div style="font-size:.83rem;font-weight:700;color:var(--g700);margin-bottom:.5rem">Quantity</div>
        <div class="qty-ctrl">
          <button onclick="pdpChangeQty(-1,<?= $p['quantity'] ?>)">−</button>
          <span id="pdp-qty">1</span>
          <button onclick="pdpChangeQty(1,<?= $p['quantity'] ?>)">+</button>
        </div>
      </div>

      <!-- CTAs -->
      <div class="pdp-ctas">
        <button class="btn btn-ju btn-lg" style="flex:2"
          onclick="addToCart(<?= $id ?>, document.getElementById('sel-size')?.value||'')"
          <?= $p['quantity']<=0?'disabled':''?>>
          🛒 <?= $p['quantity']<=0 ? 'Out of Stock' : 'Add to Cart' ?>
        </button>
        <button class="btn btn-outline btn-lg" style="flex:1" onclick="toggleWish(<?= $id ?>,this)">
          <?= $inWish ? '❤️ Saved' : '♡ Save' ?>
        </button>
      </div>

      <!-- Feature grid -->
      <div class="pdp-feats">
        <div class="pdp-feat"><span class="pdp-feat-ico">🚚</span><span>3–7 days nationwide delivery</span></div>
        <div class="pdp-feat"><span class="pdp-feat-ico">↩️</span><span>7-day hassle-free returns</span></div>
        <div class="pdp-feat"><span class="pdp-feat-ico">✅</span><span>Verified merchant</span></div>
        <div class="pdp-feat"><span class="pdp-feat-ico">🔒</span><span>Secure Paystack checkout</span></div>
      </div>

      <!-- Tabs -->
      <div class="pdp-tabs">
        <div class="pdp-tab active" onclick="switchTab(this,'t-desc')">Description</div>
        <div class="pdp-tab" onclick="switchTab(this,'t-seller')">About Seller</div>
        <div class="pdp-tab" onclick="switchTab(this,'t-ship')">Shipping &amp; Returns</div>
      </div>

      <div id="t-desc" class="pdp-tab-content active">
        <?php if ($p['description']): ?>
          <p style="font-size:.88rem;line-height:1.8;white-space:pre-line;color:var(--g700)"><?= e($p['description']) ?></p>
        <?php else: ?>
          <p class="text-muted">No description provided.</p>
        <?php endif; ?>
      </div>

      <div id="t-seller" class="pdp-tab-content">
        <div style="display:flex;gap:.85rem;align-items:flex-start;padding:1rem;background:var(--g50);border-radius:var(--r-lg);border:1px solid var(--border)">
          <div class="shop-avatar" style="width:50px;height:50px;flex-shrink:0">
            <?php if ($p['shop_logo']): ?><img src="<?= imgUrl($p['shop_logo']) ?>" alt=""><?php else: ?>🏪<?php endif; ?>
          </div>
          <div style="flex:1">
            <div style="font-weight:700;color:var(--black);font-size:.9rem"><?= e($p['shop_name']) ?> <span style="color:var(--blue);font-size:.78rem">✓ Verified</span></div>
            <div style="font-size:.75rem;color:var(--g500);margin:.15rem 0">📍 <?= e($p['shop_city']??'Nigeria') ?> · <?= $shopProdCnt ?> products</div>
            <?php if ($p['shop_desc']): ?><p style="font-size:.8rem;margin-top:.4rem;color:var(--g600)"><?= e(substr($p['shop_desc'],0,190)) ?><?= strlen($p['shop_desc'])>190?'…':''?></p><?php endif; ?>
            <a href="<?= BASE_URL ?>/customer/merchant.php?id=<?= $p['shop_id'] ?>" class="btn btn-outline btn-sm" style="margin-top:.65rem">Visit Shop →</a>
          </div>
        </div>
      </div>

      <div id="t-ship" class="pdp-tab-content">
        <div style="font-size:.86rem;line-height:1.85;color:var(--g700)">
          <p><strong>🚚 Delivery:</strong> Standard delivery 3–7 business days nationwide. Lagos same/next-day available on select items.</p>
          <p style="margin-top:.65rem"><strong>↩️ Returns:</strong> Items can be returned within 7 days of delivery if unworn and in original condition with packaging intact. Contact the merchant to initiate.</p>
          <p style="margin-top:.65rem"><strong>✂️ Custom Orders:</strong> Many merchants accept bespoke orders. Message the shop for enquiries about custom sizing or fabric options.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Related products -->
  <?php if (!empty($related)): ?>
  <div style="margin-top:3.5rem;padding-top:3rem;border-top:1px solid var(--border)">
    <div class="sec-head">
      <div>
        <div class="sec-label">More Like This</div>
        <h2 class="sec-title-plain">You May Also <em>Like</em></h2>
      </div>
      <a href="<?= BASE_URL ?>/customer/shop.php?category=<?= $p['category_id'] ?>" class="btn btn-ghost btn-sm">More <?= e($p['cat_name']) ?> →</a>
    </div>
    <div class="related-grid-5">
      <?php foreach ($related as $p): include __DIR__.'/../includes/product_card.php'; endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
function switchTab(el, id) {
  document.querySelectorAll('.pdp-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.pdp-tab-content').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  document.getElementById(id)?.classList.add('active');
}
</script>
<?php include __DIR__.'/../includes/footer_customer.php'; ?>
