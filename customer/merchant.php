<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id   = (int)($_GET['id'] ?? 0);
$shop = DB::fetch(
    "SELECT s.*, u.name owner_name
     FROM shops s
     JOIN users u ON u.id = s.user_id
     WHERE s.id = ? AND s.status = 'approved'",
    [$id]
);
if (!$shop) {
    flash('Shop not found.', 'error');
    redirect(BASE_URL . '/customer/merchants.php');
}

$products = DB::fetchAll("
    SELECT p.*, s.shop_name, c.name cat_name, c.icon category_icon,
           (SELECT image_path FROM product_images
            WHERE product_id = p.id AND is_primary = 1 LIMIT 1) primary_image
    FROM products p
    JOIN shops s ON s.id = p.shop_id
    JOIN categories c ON c.id = p.category_id
    WHERE p.shop_id = ? AND p.status = 'approved'
    ORDER BY p.sales_count DESC",
    [$id]
);

$totalSales  = $shop['total_revenue'] ?? 0;
$productCnt  = count($products);
$pageTitle   = e($shop['shop_name']);
$activePage  = 'merchants';

include __DIR__ . '/../includes/header_customer.php';
?>

<div class="wrap-full" style="padding-top:12px;padding-bottom:28px">

  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="<?= BASE_URL ?>/">Home</a><span class="sep">›</span>
    <a href="<?= BASE_URL ?>/customer/merchants.php">Designers</a><span class="sep">›</span>
    <span class="cur"><?= e($shop['shop_name']) ?></span>
  </div>

  <!-- ═══ SHOP HERO ═══════════════════════════════════════════ -->
  <div style="background:#fff;border-radius:var(--r-lg);overflow:hidden;
              box-shadow:var(--sh);margin-bottom:16px;border:1px solid var(--border-lt)">

    <!-- ── BANNER IMAGE: full-width, 220px tall ── -->
    <div style="
      width:100%;height:220px;position:relative;overflow:hidden;
      background:linear-gradient(135deg, var(--navy) 0%, var(--ju-dk) 55%, var(--ju) 100%);
      flex-shrink:0;
    ">
      <?php if (!empty($shop['banner'])): ?>
        <img src="<?= imgUrl($shop['banner']) ?>"
             alt="<?= e($shop['shop_name']) ?> banner"
             style="width:100%;height:100%;object-fit:cover;object-position:center;display:block">
        <div style="position:absolute;inset:0;
                    background:linear-gradient(to bottom, rgba(0,0,0,.05) 30%, rgba(0,0,0,.55));
                    pointer-events:none"></div>
      <?php else: ?>
        <!-- Decorative fallback -->
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                    font-size:5rem;opacity:.25">🌍</div>
      <?php endif; ?>

      <!-- Verified badge on banner -->
      <div style="position:absolute;top:12px;right:12px;
                  background:var(--green);color:#fff;
                  font-size:.68rem;font-weight:700;padding:5px 12px;
                  border-radius:999px;display:flex;align-items:center;gap:4px;
                  box-shadow:0 2px 8px rgba(0,0,0,.25);letter-spacing:.02em">
        ✓ Verified Merchant
      </div>
    </div>

    <!-- ── SHOP INFO ROW (avatar + name + stats) ── -->
    <div style="padding:0 20px 20px">

      <!-- Avatar: overlaps banner by 30px, always square -->
      <div style="margin-top:-38px;margin-bottom:12px;display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap">

        <div style="
          width:76px;height:76px;border-radius:12px;
          border:4px solid #fff;overflow:hidden;flex-shrink:0;
          background:var(--blue-pale);
          box-shadow:0 4px 16px rgba(0,0,0,.18);
          display:flex;align-items:center;justify-content:center;
          position:relative;z-index:2;
        ">
          <?php if (!empty($shop['logo'])): ?>
            <img src="<?= imgUrl($shop['logo']) ?>"
                 alt="<?= e($shop['shop_name']) ?> logo"
                 style="width:100%;height:100%;object-fit:cover;display:block">
          <?php else: ?>
            <span style="font-size:2.2rem;line-height:1">🏪</span>
          <?php endif; ?>
        </div>

        <!-- Name + location beside avatar -->
        <div style="padding-top:20px;flex:1;min-width:160px">
          <h1 style="font-family:var(--ff-head);font-size:clamp(1.1rem,2.5vw,1.55rem);
                     color:var(--black);margin-bottom:3px;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <?= e($shop['shop_name']) ?>
            <span style="background:var(--navy);color:#fff;font-size:.64rem;font-weight:700;
                         padding:3px 9px;border-radius:999px;font-family:var(--ff));
                         letter-spacing:.02em;white-space:nowrap">✓ Verified</span>
          </h1>
          <div style="font-size:.78rem;color:var(--text-muted);display:flex;align-items:center;gap:5px">
            <span>📍</span>
            <span><?= e($shop['city'] ?? 'Nigeria') ?><?= ($shop['country'] && $shop['country'] !== 'Nigeria') ? ', ' . e($shop['country']) : '' ?></span>
          </div>
        </div>
      </div>

      <!-- Description -->
      <?php if (!empty($shop['description'])): ?>
      <p style="font-size:.84rem;color:var(--text-soft);line-height:1.65;
                max-width:680px;margin-bottom:16px">
        <?= e(substr($shop['description'], 0, 300)) ?><?= strlen($shop['description']) > 300 ? '…' : '' ?>
      </p>
      <?php endif; ?>

      <!-- Stats + CTA row -->
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">

        <!-- Stat boxes -->
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <div style="background:var(--blue-pale);border:1px solid var(--blue-pale2);
                      border-radius:var(--r-md);padding:10px 18px;text-align:center;min-width:90px">
            <div style="font-family:var(--ff-head);font-size:1.3rem;font-weight:800;
                        color:var(--blue);line-height:1"><?= $productCnt ?></div>
            <div style="font-size:.65rem;color:var(--text-muted);text-transform:uppercase;
                        letter-spacing:.05em;margin-top:3px">Products</div>
          </div>
          <div style="background:var(--green-pale);border:1px solid var(--green-pale2);
                      border-radius:var(--r-md);padding:10px 18px;text-align:center;min-width:90px">
            <div style="font-family:var(--ff-head);font-size:1.1rem;font-weight:800;
                        color:var(--green);line-height:1"><?= money($totalSales) ?></div>
            <div style="font-size:.65rem;color:var(--text-muted);text-transform:uppercase;
                        letter-spacing:.05em;margin-top:3px">Total Sales</div>
          </div>
        </div>

        <!-- Spacer -->
        <div style="flex:1"></div>

        <!-- Share / follow actions -->
        <div style="display:flex;gap:8px">
          <a href="<?= BASE_URL ?>/customer/shop.php?shop=<?= $id ?>"
             class="btn btn-ghost btn-sm">Browse All Products</a>
          <a href="<?= BASE_URL ?>/customer/merchants.php"
             class="btn btn-ju btn-sm">← All Designers</a>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ PRODUCTS SECTION ══════════════════════════════════════ -->
  <?php if (empty($products)): ?>
    <div class="empty-state" style="background:#fff;border-radius:var(--r-lg);
         padding:56px 24px;box-shadow:var(--sh-xs)">
      <span class="empty-icon">🏷</span>
      <p style="font-weight:600;margin-bottom:6px">No products listed yet</p>
      <p style="font-size:.82rem">This designer hasn't added products yet. Check back soon.</p>
    </div>

  <?php else: ?>

    <!-- Section header -->
    <div style="background:#fff;border:1px solid var(--border-lt);
                border-radius:var(--r-md) var(--r-md) 0 0;
                padding:12px 16px;display:flex;align-items:center;
                justify-content:space-between;
                border-bottom:2px solid var(--blue)">
      <div style="font-family:var(--ff-head);font-size:.95rem;font-weight:700;
                  color:var(--black);display:flex;align-items:center;gap:6px">
        🛍 Products by <?= e($shop['shop_name']) ?>
      </div>
      <span style="font-size:.76rem;color:var(--text-muted);font-weight:500">
        <?= $productCnt ?> item<?= $productCnt !== 1 ? 's' : '' ?>
      </span>
    </div>

    <!-- 5-column product grid -->
    <div style="background:#fff;border:1px solid var(--border-lt);border-top:none;
                border-radius:0 0 var(--r-md) var(--r-md);padding:14px;
                box-shadow:var(--sh-xs)">
      <div class="merchant-products-grid">
        <?php foreach ($products as $p): ?>
          <?php include __DIR__ . '/../includes/product_card.php'; ?>
        <?php endforeach; ?>
      </div>
    </div>

  <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
