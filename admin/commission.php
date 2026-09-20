<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Markup Management';
$activeNav = 'commission';

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'global_markup') {
        $rate = max(0, min(100, (float)($_POST['markup_rate'] ?? 5)));
        DB::query("INSERT INTO settings (`key`,`value`,`label`)
                   VALUES ('platform_markup',?,?)
                   ON DUPLICATE KEY UPDATE value=?",
            [$rate, 'Platform Markup Rate (%)', $rate]);
        // Re-apply the markup to all products that use the global rate
        DB::query("UPDATE products p
                   JOIN shops s ON s.id = p.shop_id
                   SET p.price = ROUND(p.cost_price * (1 + ? / 100), 2)
                   WHERE s.markup_rate IS NULL
                   AND p.cost_price IS NOT NULL AND p.cost_price > 0",
            [$rate]);
        flash('Global markup updated to ' . $rate . '% and applied to all products.', 'success');
    }

    if ($act === 'shop_markup') {
        $sid  = (int)($_POST['shop_id'] ?? 0);
        $rate = trim($_POST['markup_rate'] ?? '');
        if ($rate === '' || $rate === 'global') {
            // Remove per-shop override → revert to global
            DB::update('shops', ['markup_rate' => null], 'id=?', [$sid]);
            $globalRate = (float)getSetting('platform_markup', 5);
            DB::query("UPDATE products SET price = ROUND(cost_price * (1 + ? / 100), 2)
                       WHERE shop_id=? AND cost_price IS NOT NULL",
                [$globalRate, $sid]);
            flash('Shop markup reset to global rate.', 'success');
        } else {
            $rate = max(0, min(100, (float)$rate));
            DB::update('shops', ['markup_rate' => $rate], 'id=?', [$sid]);
            DB::query("UPDATE products SET price = ROUND(cost_price * (1 + ? / 100), 2)
                       WHERE shop_id=? AND cost_price IS NOT NULL",
                [$rate, $sid]);
            flash('Custom markup of ' . $rate . '% set for this shop.', 'success');
        }
    }

    redirect(BASE_URL . '/admin/commission.php');
}

include __DIR__ . '/../includes/header_admin.php';

$globalMarkup = (float)getSetting('platform_markup', 5);
$shops = DB::fetchAll(
    "SELECT s.id, s.shop_name, s.markup_rate, s.total_revenue,
            COUNT(p.id) prod_count,
            COALESCE(SUM(p.cost_price), 0) total_cost,
            COALESCE(SUM(p.price), 0)      total_listed
     FROM shops s
     LEFT JOIN products p ON p.shop_id = s.id AND p.status = 'approved'
     WHERE s.status = 'approved'
     GROUP BY s.id
     ORDER BY s.total_revenue DESC"
);

// Platform markup = sum of (sale_price - cost_price) × qty on paid orders (excludes delivery)
$platformMarkupEarned = (float)DB::count(
    "SELECT COALESCE(SUM((oi.price - oi.cost_price) * oi.quantity), 0)
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.payment_status = 'paid'
       AND oi.cost_price IS NOT NULL
       AND oi.cost_price > 0
       AND oi.price >= oi.cost_price"
);

// Merchant cost received = pure cost_price × qty only (no delivery, no markup)
$merchantCostEarned = (float)DB::count(
    "SELECT COALESCE(SUM(oi.cost_price * oi.quantity), 0)
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.payment_status = 'paid'
       AND oi.cost_price IS NOT NULL
       AND oi.cost_price > 0"
);
?>

<div class="dash-head">
  <h1 class="dash-title">📊 Markup Management</h1>
  <p class="dash-sub">
    Pricing model: Merchant enters <strong>cost of goods</strong> →
    Platform applies <strong>markup %</strong> → Customer sees <strong>final price</strong> →
    Merchant receives <strong>cost of goods only</strong>
  </p>
</div>

<!-- How it works -->
<div class="alert alert-info" style="margin-bottom:18px">
  <strong>💡 How the Markup Model Works</strong><br>
  <span style="font-size:.86rem">
    Example: Merchant sets cost = ₦10,000 · Global markup = <?= $globalMarkup ?>% →
    Customer price = ₦<?= number_format(10000 * (1 + $globalMarkup/100), 2) ?> →
    Merchant receives ₦10,000 · Platform keeps ₦<?= number_format(10000 * $globalMarkup/100, 2) ?>
  </span>
</div>

<!-- KPI Stats -->
<div class="m-stat-grid" style="margin-bottom:18px">
  <div class="m-stat accent-green">
    <div class="m-stat-label">Global Markup Rate</div>
    <div class="m-stat-value"><?= $globalMarkup ?>%</div>
    <div class="m-stat-hint">Applied where no shop override set</div>
  </div>
  <div class="m-stat accent-orange">
    <div class="m-stat-label">Platform Markup Earned</div>
    <div class="m-stat-value"><?= moneyNgn($platformMarkupEarned) ?></div>
    <div class="m-stat-hint">Sale price minus cost price on paid orders (excl. delivery)</div>
  </div>
  <div class="m-stat accent-blue">
    <div class="m-stat-label">Merchant Cost of Goods</div>
    <div class="m-stat-value"><?= moneyNgn($merchantCostEarned) ?></div>
    <div class="m-stat-hint">Pure cost_price × qty on paid orders (excl. delivery & markup)</div>
  </div>
  <div class="m-stat">
    <div class="m-stat-label">Shops on Global Rate</div>
    <div class="m-stat-value">
      <?= count(array_filter($shops, function($s){ return $s['markup_rate'] === null; })) ?>
    </div>
    <div class="m-stat-hint">Of <?= count($shops) ?> approved shops</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px">

  <!-- Set Global Markup -->
  <div class="card">
    <div class="card-head">
      <div class="card-title">🌐 Global Markup Rate</div>
      <span class="badge badge-success" style="font-size:.72rem">Applied platform-wide</span>
    </div>
    <div style="background:var(--bg);border-radius:var(--r-md);padding:12px 16px;margin-bottom:14px">
      <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;
                  color:var(--text-muted);margin-bottom:8px">Live Preview</div>
      <div style="display:flex;flex-direction:column;gap:5px;font-size:.84rem" id="markup-preview">
        <?php foreach ([10000, 25000, 50000, 100000] as $cost): ?>
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text-muted)">Cost ₦<?= number_format($cost) ?></span>
          <span>→ Customer pays <strong>₦<?= number_format($cost*(1+$globalMarkup/100),2) ?></strong>
            · Platform earns <span style="color:var(--green)">₦<?= number_format($cost*$globalMarkup/100,2) ?></span>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="global_markup">
      <div class="form-group">
        <label class="form-label">
          Global Markup % *
          <span style="font-weight:400;color:var(--text-muted)">(applies to all shops without override)</span>
        </label>
        <div style="display:flex;align-items:center;gap:8px">
          <input type="number" name="markup_rate" id="markup-input"
                 class="form-control" style="max-width:120px;font-size:1.1rem;font-weight:700"
                 value="<?= $globalMarkup ?>" min="0" max="100" step="0.5"
                 oninput="updatePreview(this.value)">
          <span style="font-size:1.1rem;font-weight:700;color:var(--text-muted)">%</span>
        </div>
        <div class="form-hint">
          ⚠️ Saving this will recalculate all product prices for shops on the global rate.
        </div>
      </div>
      <button type="submit" class="btn btn-ju btn-full"
              onclick="return confirm('Update global markup to ' + document.getElementById('markup-input').value + '%? This recalculates product prices for all shops on the global rate.')">
        Apply Global Markup
      </button>
    </form>
  </div>

  <!-- Markup explained -->
  <div class="card">
    <div class="card-head">
      <div class="card-title">📋 Markup Model Summary</div>
    </div>
    <div style="display:flex;flex-direction:column;gap:12px;font-size:.86rem">
      <div style="background:var(--ju-pale);border-left:3px solid var(--ju);border-radius:var(--r-sm);padding:10px 14px">
        <div style="font-weight:700;margin-bottom:4px">1. Merchant enters cost of goods</div>
        <div style="color:var(--text-muted)">e.g. ₦10,000 — what the product costs them</div>
      </div>
      <div style="background:var(--navy-pale);border-left:3px solid var(--navy);border-radius:var(--r-sm);padding:10px 14px">
        <div style="font-weight:700;margin-bottom:4px">2. Platform applies markup</div>
        <div style="color:var(--text-muted)">₦10,000 × (1 + 5%) = ₦10,500 shown to customer</div>
      </div>
      <div style="background:var(--orange-pale);border-left:3px solid var(--orange);border-radius:var(--r-sm);padding:10px 14px">
        <div style="font-weight:700;margin-bottom:4px">3. Customer pays marked-up price</div>
        <div style="color:var(--text-muted)">₦10,500 collected via Paystack</div>
      </div>
      <div style="background:var(--green-pale);border-left:3px solid var(--green);border-radius:var(--r-sm);padding:10px 14px">
        <div style="font-weight:700;margin-bottom:4px">4. Payout split</div>
        <div style="color:var(--text-muted)">
          Merchant receives: ₦10,000 (cost of goods)<br>
          Platform keeps: ₦500 (markup = ₦500)<br>
          Logistics receives: their delivery fee only
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Per-shop overrides table -->
<div class="card" style="padding:0">
  <div class="card-head" style="padding:14px 16px">
    <div class="card-title">🏪 Per-Shop Markup Overrides</div>
    <span style="font-size:.78rem;color:var(--text-muted)">
      Leave blank to use global rate (<?= $globalMarkup ?>%)
    </span>
  </div>
  <?php if (empty($shops)): ?>
  <div class="empty-state" style="padding:32px"><p>No approved shops yet.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Shop</th>
          <th>Products</th>
          <th>Markup Rate</th>
          <th>Example: Cost ₦10k</th>
          <th>Revenue (All Time)</th>
          <th>Set Override</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($shops as $sh):
          $rate = $sh['markup_rate'] !== null ? (float)$sh['markup_rate'] : $globalMarkup;
          $isOverride = $sh['markup_rate'] !== null;
        ?>
        <tr>
          <td style="font-weight:700"><?= e($sh['shop_name']) ?></td>
          <td style="color:var(--text-muted)"><?= $sh['prod_count'] ?> products</td>
          <td>
            <?php if ($isOverride): ?>
            <span class="badge badge-warning"><?= $sh['markup_rate'] ?>% (custom)</span>
            <?php else: ?>
            <span class="badge badge-muted"><?= $globalMarkup ?>% (global)</span>
            <?php endif; ?>
          </td>
          <td style="font-size:.82rem">
            Customer pays <strong>₦<?= number_format(10000 * (1 + $rate/100), 2) ?></strong>
            · Platform gets <span style="color:var(--green)">₦<?= number_format(10000 * $rate/100, 2) ?></span>
          </td>
          <td style="font-weight:600;color:var(--blue)"><?= moneyNgn($sh['total_revenue']) ?></td>
          <td>
            <form method="POST" style="display:flex;gap:5px;align-items:center">
              <input type="hidden" name="action"  value="shop_markup">
              <input type="hidden" name="shop_id" value="<?= $sh['id'] ?>">
              <input type="number" name="markup_rate"
                     placeholder="<?= $globalMarkup ?>"
                     value="<?= $isOverride ? $sh['markup_rate'] : '' ?>"
                     class="form-control" style="width:80px;padding:4px 8px;font-size:.82rem"
                     min="0" max="100" step="0.5">
              <button type="submit" class="btn btn-ghost btn-sm">Set</button>
              <?php if ($isOverride): ?>
              <button type="submit" name="markup_rate" value=""
                      class="btn btn-danger btn-sm" title="Reset to global">✕</button>
              <?php endif; ?>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
var COSTS = [10000, 25000, 50000, 100000];
function updatePreview(rate) {
  rate = parseFloat(rate) || 0;
  var rows = document.getElementById('markup-preview').querySelectorAll('div');
  rows.forEach(function(row, i) {
    var cost = COSTS[i];
    var cust = cost * (1 + rate/100);
    var earn = cost * rate/100;
    row.innerHTML =
      '<span style="color:var(--text-muted)">Cost ₦' + cost.toLocaleString() + '</span>' +
      '<span>→ Customer pays <strong>₦' + cust.toLocaleString('en-NG',{minimumFractionDigits:2}) + '</strong>' +
      ' · Platform earns <span style="color:var(--green)">₦' + earn.toLocaleString('en-NG',{minimumFractionDigits:2}) + '</span></span>';
  });
}
</script>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
