<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle  = 'African Attire — Authentic African Fashion';
$activePage = 'home';
$cats       = getCategories();

// Hero slider banners (position='hero', active=1, ordered)
$sliderBanners = DB::fetchAll(
    "SELECT * FROM banners WHERE position='hero' AND active=1 ORDER BY sort_order ASC, id ASC"
);

$featured = DB::fetchAll("
    SELECT p.*, s.shop_name, c.name cat_name, c.icon category_icon,
           (SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) primary_image
    FROM products p JOIN shops s ON s.id=p.shop_id JOIN categories c ON c.id=p.category_id
    WHERE p.status='approved' AND p.featured=1 ORDER BY p.sales_count DESC LIMIT 6");

$newArrivals = DB::fetchAll("
    SELECT p.*, s.shop_name, c.name cat_name, c.icon category_icon,
           (SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) primary_image
    FROM products p JOIN shops s ON s.id=p.shop_id JOIN categories c ON c.id=p.category_id
    WHERE p.status='approved' ORDER BY p.created_at DESC LIMIT 6");

$topShops = DB::fetchAll("
    SELECT s.*, (SELECT COUNT(*) FROM products WHERE shop_id=s.id AND status='approved') prod_count
    FROM shops s WHERE s.status='approved' ORDER BY s.total_revenue DESC LIMIT 6");

include __DIR__ . '/includes/header_customer.php';
?>

<div class="wrap-full" style="padding-top:12px;padding-bottom:16px">

<!-- ══════════════════════════════════════════════════════
     HERO LAYOUT: category sidebar + hero slider
     ══════════════════════════════════════════════════════ -->
<div class="ju-hero-wrap" style="margin-bottom:12px">

  <!-- Left: category sidebar (desktop) -->
  <div class="ju-sidebar-cats">
    <a href="<?= BASE_URL ?>/customer/shop.php" class="ju-sidebar-cat-item">
      <span class="sc-ico">🏬</span> All Products <span class="sc-arrow">›</span>
    </a>
    <?php foreach(array_slice($cats, 0, 11) as $c): ?>
    <a href="<?= BASE_URL ?>/customer/shop.php?category=<?= $c['id'] ?>" class="ju-sidebar-cat-item">
      <span class="sc-ico"><?= $c['icon'] ?></span> <?= e($c['name']) ?> <span class="sc-arrow">›</span>
    </a>
    <?php endforeach; ?>
    <a href="<?= BASE_URL ?>/customer/merchants.php" class="ju-sidebar-cat-item">
      <span class="sc-ico">✨</span> Designers <span class="sc-arrow">›</span>
    </a>
    <a href="<?= BASE_URL ?>/customer/track-order.php" class="ju-sidebar-cat-item">
      <span class="sc-ico">📦</span> Track Order <span class="sc-arrow">›</span>
    </a>
  </div>

  <!-- Right column: slider on top, perks bar directly beneath -->
  <div class="hero-right-col">

  <!-- ── HERO SLIDER ── -->
  <div class="hero-slider" id="heroSlider">

    <?php
    $slides    = $sliderBanners;
    $slideCount= count($slides);
    $hasSlides = $slideCount > 0;
    ?>

    <?php if (!$hasSlides): ?>
      <!-- Default slide when no banners uploaded -->
      <div class="hs-slide active">
        <div class="hs-slide-gradient"></div>
        <div class="hs-slide-content">
          <div class="hs-slide-tag">🌍 Africa's #1 Fashion Marketplace</div>
          <div class="hs-slide-title">Authentic African<br><em>Fashion</em> Delivered</div>
          <div class="hs-slide-sub">Shop verified Ankara, Kente, Agbada &amp; Adire from Nigeria's finest designers.</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px">
            <a href="<?= BASE_URL ?>/customer/shop.php" class="hs-slide-cta">Shop Now →</a>
            <a href="<?= BASE_URL ?>/customer/merchants.php" class="hs-slide-cta-ghost">Meet Designers</a>
          </div>
        </div>
      </div>

    <?php else: ?>
      <?php foreach($slides as $i => $slide): ?>
      <div class="hs-slide <?= $i === 0 ? 'active' : '' ?>">
        <?php if($slide['image']): ?>
          <img class="hs-slide-img" src="<?= imgUrl($slide['image']) ?>" alt="<?= e($slide['title']) ?>">
          <div class="hs-slide-overlay"></div>
        <?php else: ?>
          <div class="hs-slide-gradient"></div>
        <?php endif; ?>
        <?php if($slide['title'] || $slide['subtitle'] || $slide['link']): ?>
        <div class="hs-slide-content">
          <?php if($slide['subtitle']): ?>
          <div class="hs-slide-tag">🌍 <?= e($slide['subtitle']) ?></div>
          <?php endif; ?>
          <?php if($slide['title']): ?>
          <div class="hs-slide-title"><?= e($slide['title']) ?></div>
          <?php endif; ?>
          <?php if($slide['link']): ?>
          <a href="<?= e($slide['link']) ?>" class="hs-slide-cta" style="margin-top:14px">Shop Now →</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <!-- Dots — only shown when >1 slide -->
    <?php if($slideCount > 1): ?>
    <div class="hs-dots" id="hsDots">
      <?php for($i = 0; $i < $slideCount; $i++): ?>
      <button class="hs-dot <?= $i===0?'active':''?>" onclick="hsGoTo(<?=$i?>)" aria-label="Slide <?=$i+1?>"></button>
      <?php endfor; ?>
    </div>
    <!-- Arrows -->
    <button class="hs-arrow hs-arrow-prev" onclick="hsMove(-1)" aria-label="Previous">&#8249;</button>
    <button class="hs-arrow hs-arrow-next" onclick="hsMove(1)"  aria-label="Next">&#8250;</button>
    <?php endif; ?>

  </div><!-- /hero-slider -->

<?php if($slideCount > 1): ?>
<script>
(function(){
  var slides = document.querySelectorAll('#heroSlider .hs-slide');
  var dots   = document.querySelectorAll('#hsDots .hs-dot');
  var total  = slides.length;
  var cur    = 0;
  var timer  = null;

  function show(n) {
    slides[cur].classList.remove('active');
    dots[cur] && dots[cur].classList.remove('active');
    cur = (n + total) % total;
    slides[cur].classList.add('active');
    dots[cur] && dots[cur].classList.add('active');
  }

  function startAuto() { timer = setInterval(function(){ show(cur + 1); }, 5000); }
  function stopAuto()  { clearInterval(timer); }

  window.hsMove  = function(d){ stopAuto(); show(cur + d); startAuto(); };
  window.hsGoTo  = function(n){ stopAuto(); show(n);       startAuto(); };

  document.getElementById('heroSlider').addEventListener('mouseenter', stopAuto);
  document.getElementById('heroSlider').addEventListener('mouseleave', startAuto);

  var tx = 0;
  document.getElementById('heroSlider').addEventListener('touchstart', function(e){ tx = e.changedTouches[0].clientX; }, {passive:true});
  document.getElementById('heroSlider').addEventListener('touchend',   function(e){
    var dx = e.changedTouches[0].clientX - tx;
    if (Math.abs(dx) > 40) { stopAuto(); show(cur + (dx < 0 ? 1 : -1)); startAuto(); }
  }, {passive:true});

  startAuto();
})();
</script>
<?php endif; ?>

  </div><!-- /hero-right-col -->

</div><!-- /ju-hero-wrap -->


<!-- ── PROMO MINI-BANNERS ── -->
<div class="ju-promo-grid" style="margin-bottom:12px">
  <a href="<?= BASE_URL ?>/customer/shop.php?category=3" class="ju-promo-card" style="background:linear-gradient(135deg,#4A0066,#7B1FA2)">
    <div class="jp-ico">👗</div><div class="jp-tag">Event Wear</div>
    <div class="jp-title">Aso Ebi &amp; Bridal</div><div class="jp-cta">Shop Now ›</div>
  </a>
  <a href="<?= BASE_URL ?>/customer/shop.php?category=2" class="ju-promo-card" style="background:linear-gradient(135deg,var(--ju-dk),var(--ju))">
    <div class="jp-ico">🏆</div><div class="jp-tag">Men's Wear</div>
    <div class="jp-title">Royal Agbada Sets</div><div class="jp-cta">Shop Now ›</div>
  </a>
  <a href="<?= BASE_URL ?>/customer/shop.php?category=8" class="ju-promo-card" style="background:linear-gradient(135deg,var(--navy),var(--navy-md))">
    <div class="jp-ico">🎨</div><div class="jp-tag">Handcrafted</div>
    <div class="jp-title">Ankara &amp; Adire</div><div class="jp-cta">Shop Now ›</div>
  </a>
  <a href="<?= BASE_URL ?>/customer/shop.php?sort=new" class="ju-promo-card" style="background:linear-gradient(135deg,#3D1500,var(--orange-dk))">
    <div class="jp-ico">✨</div><div class="jp-tag">New Arrivals</div>
    <div class="jp-title">Fresh This Week</div><div class="jp-cta">Shop Now ›</div>
  </a>
</div>

<!-- ── FEATURED PRODUCTS ── -->
<?php if (!empty($featured)): ?>
<div class="ju-panel" style="border-top:3px solid var(--ju);margin-bottom:12px">
  <div class="ju-panel-head">
    <div class="ju-panel-title"><span class="pt-ico">⭐</span> Featured Products</div>
    <a href="<?= BASE_URL ?>/customer/shop.php?sort=featured" class="ju-panel-see">See All →</a>
  </div>
  <div class="ju-panel-body-tight">
    <div class="ju-product-grid">
      <?php foreach($featured as $p): include __DIR__.'/includes/product_card.php'; endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ── NEW ARRIVALS ── -->
<?php if (!empty($newArrivals)): ?>
<div class="ju-panel" style="border-top:3px solid var(--navy);margin-bottom:12px">
  <div class="ju-panel-head">
    <div class="ju-panel-title"><span class="pt-ico">🆕</span> New Arrivals</div>
    <a href="<?= BASE_URL ?>/customer/shop.php?sort=new" class="ju-panel-see">See All →</a>
  </div>
  <div class="ju-panel-body-tight">
    <div class="ju-product-grid">
      <?php foreach($newArrivals as $p): include __DIR__.'/includes/product_card.php'; endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ── TOP DESIGNERS ── -->
<?php if (!empty($topShops)): ?>
<div class="ju-panel dark" style="margin-bottom:12px">
  <div class="ju-panel-head">
    <div class="ju-panel-title"><span class="pt-ico">✨</span> Top Designers</div>
    <a href="<?= BASE_URL ?>/customer/merchants.php" class="ju-panel-see">All Designers →</a>
  </div>
  <div class="ju-panel-body-tight">
    <div class="mc-grid">
      <?php foreach($topShops as $m): include __DIR__.'/includes/merchant_card.php'; endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>



</div><!-- /wrap-full -->

<?php include __DIR__ . '/includes/footer_customer.php'; ?>
