<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle  = 'Shop African Fashion';
$activePage = 'shop';

// ─── Query params ───────────────────────────────────────────
$q      = trim($_GET['q']        ?? '');
$catId  = (int)($_GET['category'] ?? 0);
$sort   = $_GET['sort']           ?? 'popular';
$gender = $_GET['gender']         ?? '';
$pmin   = (float)($_GET['pmin']   ?? 0);
$pmax   = (float)($_GET['pmax']   ?? 0);
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 24; // 4 rows of 6

// ─── Build query ────────────────────────────────────────────
$where  = ["p.status='approved'"]; $params = [];
if ($q)       { $where[] = "(p.name LIKE ? OR p.description LIKE ? OR s.shop_name LIKE ?)"; $params = array_merge($params,["%$q%","%$q%","%$q%"]); }
if ($catId)   { $where[] = "p.category_id=?";   $params[] = $catId; }
if ($gender)  { $where[] = "p.gender=?";         $params[] = $gender; }
if ($pmin > 0){ $where[] = "p.price>=?";         $params[] = $pmin; }
if ($pmax > 0){ $where[] = "p.price<=?";         $params[] = $pmax; }

$wStr  = implode(' AND ', $where);
$oMap  = ['popular'=>'p.sales_count DESC','newest'=>'p.created_at DESC','price_asc'=>'p.price ASC','price_desc'=>'p.price DESC'];
$order = $oMap[$sort] ?? 'p.sales_count DESC';

$total      = DB::count("SELECT COUNT(*) FROM products p JOIN shops s ON s.id=p.shop_id WHERE $wStr", $params);
$products   = DB::fetchAll("
    SELECT p.*, s.shop_name, c.name cat_name, c.icon category_icon,
           (SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) primary_image
    FROM products p
    JOIN shops s ON s.id=p.shop_id
    JOIN categories c ON c.id=p.category_id
    WHERE $wStr ORDER BY $order
    LIMIT $per OFFSET " . (($page-1)*$per), $params);

$categories = getCategories();
$currentCat = $catId ? DB::fetch('SELECT * FROM categories WHERE id=?', [$catId]) : null;

include __DIR__ . '/../includes/header_customer.php';
?>

<div class="wrap-full" style="padding-top:10px;padding-bottom:24px">

  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="<?= BASE_URL ?>/">Home</a><span class="sep">›</span>
    <?php if($currentCat): ?>
      <a href="<?= BASE_URL ?>/customer/shop.php">Shop</a><span class="sep">›</span>
      <span class="cur"><?= e($currentCat['name']) ?></span>
    <?php elseif($q): ?>
      <a href="<?= BASE_URL ?>/customer/shop.php">Shop</a><span class="sep">›</span>
      <span class="cur">Search: "<?= e($q) ?>"</span>
    <?php else: ?>
      <span class="cur">All Products</span>
    <?php endif; ?>
  </div>

  <!-- ─── MOBILE FILTER TOGGLE (hidden on desktop) ─── -->
  <div class="shop-mobile-bar">
    <button class="shop-filter-toggle" onclick="toggleMobileFilters()" id="filter-toggle-btn">
      <span>⚙️ Filters<?php if($catId||$gender||$pmin||$pmax): ?> <span class="filter-dot"></span><?php endif;?></span>
      <span id="filter-toggle-arrow">▼</span>
    </button>
    <div style="display:flex;align-items:center;gap:8px">
      <span style="font-size:.75rem;color:var(--text-muted)"><?= number_format($total) ?> items</span>
      <select class="sort-select" id="mobile-sort-sel"
              onchange="document.getElementById('sort-hidden').value=this.value;document.getElementById('filter-form').submit()">
        <option value="popular"    <?= $sort==='popular'   ?'selected':''?>>Best Selling</option>
        <option value="newest"     <?= $sort==='newest'    ?'selected':''?>>Newest</option>
        <option value="price_asc"  <?= $sort==='price_asc' ?'selected':''?>>Price ↑</option>
        <option value="price_desc" <?= $sort==='price_desc'?'selected':''?>>Price ↓</option>
      </select>
    </div>
  </div>

  <!-- ─── SHOP LAYOUT: sidebar + grid ─── -->
  <div class="shop-layout">

    <!-- ═══ FILTER SIDEBAR ═══════════════════════════════════ -->
    <aside class="shop-sidebar" id="shop-sidebar">
      <form method="GET" id="filter-form">
        <?php if($q): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
        <input type="hidden" name="sort" value="<?= e($sort) ?>" id="sort-hidden">

        <div class="filter-panel">
          <!-- Header -->
          <div class="filter-panel-head">
            🔧 Filters
            <?php if($catId||$gender||$pmin||$pmax): ?>
              <a href="<?= BASE_URL ?>/customer/shop.php<?= $q?'?q='.urlencode($q):''?>"
                 style="margin-left:auto;font-size:.68rem;color:rgba(255,255,255,.8);font-weight:400">
                Clear all
              </a>
            <?php endif; ?>
          </div>

          <!-- Category filter -->
          <div class="filter-section">
            <div class="filter-section-title">Category</div>
            <div class="filter-opt">
              <label>
                <input type="radio" name="category" value=""
                       <?= !$catId?'checked':''?> onchange="this.form.submit()">
                All Products
              </label>
              <span class="filter-cnt"><?= number_format($total) ?></span>
            </div>
            <?php foreach($categories as $c):
              $cnt = DB::count("SELECT COUNT(*) FROM products WHERE category_id=? AND status='approved'", [$c['id']]);
            ?>
            <div class="filter-opt">
              <label>
                <input type="radio" name="category" value="<?= $c['id'] ?>"
                       <?= $catId==$c['id']?'checked':''?> onchange="this.form.submit()">
                <?= $c['icon'] ?> <?= e($c['name']) ?>
              </label>
              <span class="filter-cnt"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Gender filter -->
          <div class="filter-section">
            <div class="filter-section-title">For</div>
            <?php foreach([''=> 'Everyone','female'=>'Women','male'=>'Men','unisex'=>'Unisex','kids'=>'Kids'] as $v=>$l): ?>
            <div class="filter-opt">
              <label>
                <input type="radio" name="gender" value="<?= $v ?>"
                       <?= $gender===$v?'checked':''?> onchange="this.form.submit()">
                <?= $l ?>
              </label>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Price filter -->
          <div class="filter-section">
            <div class="filter-section-title">Price Range</div>
            <div style="display:flex;gap:6px;align-items:center;margin-bottom:8px">
              <input type="number" name="pmin" class="form-control"
                     placeholder="Min ₦" value="<?= $pmin?:'' ?>" min="0"
                     style="padding:6px 8px;font-size:.76rem">
              <span style="color:var(--text-muted);font-size:.8rem;flex-shrink:0">–</span>
              <input type="number" name="pmax" class="form-control"
                     placeholder="Max ₦" value="<?= $pmax?:'' ?>" min="0"
                     style="padding:6px 8px;font-size:.76rem">
            </div>
            <?php foreach([
              [0,15000,'Under ₦15k'],
              [15000,30000,'₦15k–₦30k'],
              [30000,75000,'₦30k–₦75k'],
              [75000,150000,'₦75k–₦150k'],
              [150000,0,'Over ₦150k'],
            ] as [$mn,$mx,$lbl]): ?>
            <div class="filter-opt">
              <label style="cursor:pointer">
                <input type="radio" name="prange" value="<?= $mn ?>_<?= $mx ?>"
                  <?= ($pmin==$mn&&$pmax==$mx)?'checked':'' ?>
                  onchange="document.querySelector('[name=pmin]').value='<?= $mn ?>';
                            document.querySelector('[name=pmax]').value='<?= $mx ?>';
                            this.form.submit()">
                <?= $lbl ?>
              </label>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Apply -->
          <div class="filter-section" style="border-bottom:none">
            <button type="submit" class="btn btn-ju btn-sm btn-full">Apply Filters</button>
          </div>
        </div><!-- /filter-panel -->
      </form>
    </aside>

    <!-- ═══ PRODUCT AREA ═════════════════════════════════════ -->
    <div class="shop-products-col">

      <!-- Active filter tags -->
      <?php if($catId || $gender || $pmin || $pmax || $q): ?>
      <div class="filter-tags" style="margin-bottom:10px">
        <?php if($q): ?>
          <span class="filter-tag">🔍 "<?= e($q) ?>" <a href="<?= BASE_URL ?>/customer/shop.php" style="color:inherit">×</a></span>
        <?php endif; ?>
        <?php if($currentCat): ?>
          <span class="filter-tag"><?= $currentCat['icon'] ?> <?= e($currentCat['name']) ?> <a href="?<?= http_build_query(array_merge($_GET,['category'=>'']))?>" style="color:inherit">×</a></span>
        <?php endif; ?>
        <?php if($gender): ?>
          <span class="filter-tag"><?= ucfirst($gender) ?> <a href="?<?= http_build_query(array_merge($_GET,['gender'=>'']))?>" style="color:inherit">×</a></span>
        <?php endif; ?>
        <?php if($pmin||$pmax): ?>
          <span class="filter-tag">₦<?= number_format($pmin) ?>–<?= $pmax?'₦'.number_format($pmax):'∞' ?> <a href="?<?= http_build_query(array_merge($_GET,['pmin'=>'','pmax'=>'']))?>" style="color:inherit">×</a></span>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Sort bar (desktop only — mobile uses top bar) -->
      <div class="sort-bar shop-sort-desktop">
        <div class="sort-bar-left">
          Showing <strong><?= number_format($total) ?></strong> product<?= $total!==1?'s':''?>
          <?= $currentCat ? ' in <strong>'.e($currentCat['name']).'</strong>' : '' ?>
          <?= $q ? ' for "<strong>'.e($q).'</strong>"' : '' ?>
        </div>
        <div style="display:flex;align-items:center;gap:7px">
          <span style="font-size:.78rem;color:var(--text-muted)">Sort:</span>
          <select class="sort-select"
                  onchange="document.getElementById('sort-hidden').value=this.value;document.getElementById('filter-form').submit()">
            <option value="popular"    <?= $sort==='popular'   ?'selected':''?>>Best Selling</option>
            <option value="newest"     <?= $sort==='newest'    ?'selected':''?>>Newest First</option>
            <option value="price_asc"  <?= $sort==='price_asc' ?'selected':''?>>Price: Low → High</option>
            <option value="price_desc" <?= $sort==='price_desc'?'selected':''?>>Price: High → Low</option>
          </select>
        </div>
      </div>

      <!-- ═══ PRODUCT GRID — 6 columns, looped ═══════════════ -->
      <?php if(empty($products)): ?>
        <div class="empty-state" style="background:#fff;border-radius:var(--r-md);padding:48px 24px;box-shadow:var(--sh-xs)">
          <span class="empty-icon">🔍</span>
          <p style="font-size:.9rem;font-weight:600;color:var(--text);margin-bottom:6px">No products found<?= $q?' for "'.e($q).'"':''?></p>
          <p style="font-size:.8rem;margin-bottom:14px">Try a different search or clear your filters.</p>
          <a href="<?= BASE_URL ?>/customer/shop.php" class="btn btn-ju btn-sm">Browse Everything</a>
        </div>
      <?php else: ?>

        <!-- 6-column product grid -->
        <div class="shop-grid-6">
          <?php foreach($products as $p): ?>
            <?php include __DIR__ . '/../includes/product_card.php'; ?>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?= paginate($total, $per, $page, '?' . http_build_query(array_diff_key($_GET, ['page'=>'']))) ?>

      <?php endif; ?>
    </div><!-- /shop-products-col -->

  </div><!-- /shop-layout -->
</div>

<script>
// Mobile filter toggle
function toggleMobileFilters() {
  var sidebar = document.getElementById('shop-sidebar');
  var arrow   = document.getElementById('filter-toggle-arrow');
  var open    = sidebar.classList.toggle('open');
  arrow.textContent = open ? '▲' : '▼';
}
</script>

<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
