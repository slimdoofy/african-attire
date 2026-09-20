<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle  = 'African Fashion Designers';
$activePage = 'merchants';

$q    = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 12;

$where  = ["s.status='approved'"]; $params = [];
if ($q) {
    $where[] = "(s.shop_name LIKE ? OR s.description LIKE ? OR s.city LIKE ?)";
    $params  = ["%$q%", "%$q%", "%$q%"];
}
$wStr  = implode(' AND ', $where);
$total = DB::count("SELECT COUNT(*) FROM shops s WHERE $wStr", $params);
$shops = DB::fetchAll("
    SELECT s.*,
           (SELECT COUNT(*) FROM products WHERE shop_id=s.id AND status='approved') prod_count
    FROM shops s
    WHERE $wStr
    ORDER BY s.total_revenue DESC
    LIMIT $per OFFSET " . (($page-1)*$per), $params);

$totalProducts = DB::count("SELECT COUNT(*) FROM products WHERE status='approved'");

include __DIR__ . '/../includes/header_customer.php';
?>

<div class="wrap-full" style="padding-top:12px;padding-bottom:28px">

  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="<?= BASE_URL ?>/">Home</a>
    <span class="sep">›</span>
    <span class="cur">Fashion Designers</span>
  </div>

  <!-- ═══ HERO SECTION ══════════════════════════════════════════ -->
  <div class="merchants-hero">
    <div class="merchants-hero-inner">
      <h1>🌍 African Fashion Designers</h1>
      <p>Discover and shop from verified African fashion merchants — Ankara, Kente, Agbada, Kaftan, Adire and more. Every merchant is reviewed and approved by our team.</p>

      <!-- Stats row -->
      <div class="merchants-hero-stats">
        <div class="mh-stat">
          <div class="mh-stat-val"><?= number_format($total) ?></div>
          <div class="mh-stat-key">Verified Merchants</div>
        </div>
        <div class="mh-stat">
          <div class="mh-stat-val"><?= number_format($totalProducts) ?>+</div>
          <div class="mh-stat-key">Products Listed</div>
        </div>
        <div class="mh-stat">
          <div class="mh-stat-val">9</div>
          <div class="mh-stat-key">African Countries</div>
        </div>
      </div>

      <!-- Search inside hero -->
      <form method="GET" class="merchants-hero-search">
        <input type="text" name="q"
               placeholder="Search by shop name, city or style…"
               value="<?= e($q) ?>">
        <button type="submit">🔍 Search</button>
        <?php if($q): ?>
          <a href="?" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);padding:10px 14px;font-size:.82rem;font-weight:600;border-radius:var(--r-sm)">
            Clear
          </a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- ═══ TRUST BADGES STRIP ════════════════════════════════════ -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px">
    <div style="display:flex;align-items:center;gap:7px;background:#fff;border:1px solid var(--green-pale2);border-radius:var(--r-md);padding:8px 14px;flex:1;min-width:170px;box-shadow:var(--sh-xs)">
      <div style="width:28px;height:28px;border-radius:50%;background:var(--green);display:flex;align-items:center;justify-content:center;font-size:.75rem;color:#fff;flex-shrink:0">✓</div>
      <div>
        <div style="font-size:.78rem;font-weight:700;color:var(--green)">All Merchants Verified</div>
        <div style="font-size:.7rem;color:var(--text-muted)">Reviewed by our team before going live</div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:7px;background:#fff;border:1px solid var(--blue-pale2);border-radius:var(--r-md);padding:8px 14px;flex:1;min-width:170px;box-shadow:var(--sh-xs)">
      <div style="width:28px;height:28px;border-radius:50%;background:var(--blue);display:flex;align-items:center;justify-content:center;font-size:.75rem;color:#fff;flex-shrink:0">🔒</div>
      <div>
        <div style="font-size:.78rem;font-weight:700;color:var(--blue)">Secure Payments</div>
        <div style="font-size:.7rem;color:var(--text-muted)">Every transaction protected by Paystack</div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:7px;background:#fff;border:1px solid var(--ju-pale2);border-radius:var(--r-md);padding:8px 14px;flex:1;min-width:170px;box-shadow:var(--sh-xs)">
      <div style="width:28px;height:28px;border-radius:50%;background:var(--ju);display:flex;align-items:center;justify-content:center;font-size:.75rem;color:#fff;flex-shrink:0">🚚</div>
      <div>
        <div style="font-size:.78rem;font-weight:700;color:var(--ju-dk)">Nationwide Delivery</div>
        <div style="font-size:.7rem;color:var(--text-muted)">Ships to all states in Nigeria</div>
      </div>
    </div>
  </div>

  <!-- ═══ RESULTS HEADER ════════════════════════════════════════ -->
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:14px">
    <div style="font-size:.86rem;color:var(--text-muted)">
      <?php if($q): ?>
        Showing <strong style="color:var(--black)"><?= number_format($total) ?></strong>
        result<?= $total!==1?'s':''?> for "<strong style="color:var(--blue)"><?= e($q) ?></strong>"
      <?php else: ?>
        Showing all <strong style="color:var(--black)"><?= number_format($total) ?></strong>
        verified designer<?= $total!==1?'s':''?>
      <?php endif; ?>
    </div>
    <div style="display:flex;align-items:center;gap:6px">
      <span style="font-size:.72rem;color:var(--text-muted)">Sort:</span>
      <select class="sort-select" onchange="window.location='?sort='+this.value+'<?= $q?'&q='.urlencode($q):''?>'">
        <option value="revenue" selected>Top Sellers</option>
        <option value="products">Most Products</option>
      </select>
    </div>
  </div>

  <!-- ═══ MERCHANT CARDS GRID ═══════════════════════════════════ -->
  <?php if(empty($shops)): ?>
    <div class="empty-state" style="background:#fff;border-radius:var(--r-lg);padding:56px 24px;box-shadow:var(--sh-xs)">
      <span class="empty-icon">🏪</span>
      <p style="font-size:.9rem;font-weight:600;color:var(--text);margin-bottom:6px">
        No designers found<?= $q?' for "'.e($q).'"':''?>
      </p>
      <p style="font-size:.8rem;margin-bottom:16px">Try a different search term or city name.</p>
      <a href="?" class="btn btn-blue btn-sm">Browse All Designers</a>
    </div>

  <?php else: ?>

    <div class="merchant-grid">
      <?php foreach($shops as $m): ?>

      <a href="<?= BASE_URL ?>/customer/merchant.php?id=<?= $m['id'] ?>"
         style="text-decoration:none;display:flex"><!-- flex so card fills height -->
        <div class="merchant-card">

          <!-- ── BANNER ── -->
          <div class="mc-banner">
            <?php if($m['banner']): ?>
              <img src="<?= imgUrl($m['banner']) ?>" alt="<?= e($m['shop_name']) ?>">
            <?php else: ?>
              <div class="mc-banner-fallback">🌍</div>
            <?php endif; ?>
            <div class="mc-banner-overlay"></div>
            <div class="mc-verified-badge">✓ Verified</div>
          </div>

          <!-- ── AVATAR ── -->
          <div class="mc-avatar-wrap">
            <div class="mc-avatar">
              <?php if($m['logo']): ?>
                <img src="<?= imgUrl($m['logo']) ?>" alt="<?= e($m['shop_name']) ?>">
              <?php else: ?>
                <span>🏪</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- ── BODY ── -->
          <div class="mc-body">
            <div class="mc-name"><?= e($m['shop_name']) ?></div>
            <div class="mc-location">
              <span>📍</span>
              <span><?= e($m['city'] ?? 'Nigeria') ?><?= ($m['country'] && $m['country'] !== 'Nigeria') ? ', '.e($m['country']) : '' ?></span>
            </div>

            <?php if($m['description']): ?>
              <div class="mc-desc"><?= e($m['description']) ?></div>
            <?php else: ?>
              <div class="mc-desc" style="color:var(--text-muted);font-style:italic">African fashion designer on African Attire</div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="mc-stats">
              <div class="mc-stat">
                <span class="mc-stat-val"><?= $m['prod_count'] ?></span>
                <span class="mc-stat-key">Products</span>
              </div>
              <div class="mc-stat">
                <span class="mc-stat-val" style="font-size:<?= strlen(money($m['total_revenue']))>8?'.72rem':'.9rem' ?>"><?= money($m['total_revenue']) ?></span>
                <span class="mc-stat-key">Total Sales</span>
              </div>
            </div>

            <!-- CTA -->
            <div class="mc-cta">Visit Shop →</div>
          </div>

        </div>
      </a>

      <?php endforeach; ?>
    </div>

    <?= paginate($total, $per, $page, '?q='.urlencode($q)) ?>

  <?php endif; ?>

  <!-- ═══ BECOME A MERCHANT CTA ══════════════════════════════ -->
  <div style="margin-top:24px;border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh)">
    <div style="background:linear-gradient(120deg,var(--navy) 0%,var(--ju-dk) 50%,var(--ju) 100%);padding:28px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;position:relative;overflow:hidden">
      <div style="position:absolute;right:-30px;top:-30px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.05)"></div>
      <div style="position:relative;z-index:1">
        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--ju-lt);margin-bottom:5px">🏪 For Designers &amp; Tailors</div>
        <div style="font-family:var(--ff-head);font-size:clamp(1rem,2.5vw,1.4rem);color:#fff;margin-bottom:6px">
          Sell Your Fashion on African Attire
        </div>
        <p style="font-size:.8rem;color:rgba(255,255,255,.65);max-width:440px">
          Join <?= number_format($total) ?>+ verified merchants. Get your digital storefront, reach customers across Africa and the diaspora. Free to register.
        </p>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;position:relative;z-index:1">
        <a href="<?= BASE_URL ?>/merchant/join.php"
           class="btn btn-ju btn-lg"
           style="box-shadow:0 4px 14px rgba(246,139,30,.4)">
          Start Selling Free →
        </a>
        <a href="<?= BASE_URL ?>/customer/merchants.php"
           class="btn"
           style="background:rgba(255,255,255,.12);color:#fff;border:1.5px solid rgba(255,255,255,.35);padding:12px 20px;font-size:.88rem;font-weight:600">
          Learn More
        </a>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
