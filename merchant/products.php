<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// ── ALL POST HANDLING BEFORE ANY OUTPUT ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // requireRole here so we get $_shop before the header include
    Auth::requireRole('merchant');
    $_shop = DB::fetch('SELECT * FROM shops WHERE user_id=?', [Auth::id()]);
    if (!$_shop) redirect(BASE_URL . '/merchant/register.php');

    $sid = $_shop['id'];
    $act = $_POST['action'] ?? '';

    if ($act === 'add') {
        $name      = trim($_POST['name']            ?? '');
        $priceCur  = $_POST['price_currency']      ?? 'ngn';
        $costNgn   = toNgn((float)($_POST['price'] ?? 0), $priceCur); // merchant's cost of goods
        $qty       = (int)($_POST['quantity']       ?? 0);
        $catId     = (int)($_POST['category_id']   ?? 0);
        $sizes     = trim($_POST['sizes']          ?? '');
        $desc      = trim($_POST['description']    ?? '');
        $origCur   = $_POST['orig_currency']       ?? 'ngn';
        $orig      = (float)($_POST['original_price'] ?? 0);
        if ($orig > 0) $orig = toNgn($orig, $origCur);
        $gender    = $_POST['gender']              ?? 'unisex';
        // Apply markup to get the customer-facing price
        $shopMarkup = DB::fetch('SELECT markup_rate FROM shops WHERE id=?', [$sid]);
        $markupRate = ($shopMarkup && $shopMarkup['markup_rate'] !== null)
            ? (float)$shopMarkup['markup_rate']
            : (float)getSetting('platform_markup', 5);
        $price = round($costNgn * (1 + $markupRate / 100), 2);

        if ($name && $price > 0 && $catId) {
            $pid = DB::insert('products', [
                'shop_id'        => $sid,
                'category_id'    => $catId,
                'name'           => $name,
                'slug'           => slug($name) . '-' . uniqid(),
                'description'    => $desc,
                'price'          => $price,
                'original_price' => $orig ?: null,
                'sizes'          => $sizes,
                'gender'         => $gender,
                'quantity'       => $qty,
                'status'         => 'pending',
            ]);
            if (!empty($_FILES['images']['name'][0])) {
                $isPrimary = 1;
                foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
                    if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $file = [
                        'tmp_name' => $tmp,
                        'name'     => $_FILES['images']['name'][$i],
                        'type'     => $_FILES['images']['type'][$i],
                        'size'     => $_FILES['images']['size'][$i],
                        'error'    => $_FILES['images']['error'][$i],
                    ];
                    $path = uploadFile($file, 'products');
                    if ($path) {
                        DB::insert('product_images', [
                            'product_id' => $pid,
                            'image_path' => $path,
                            'is_primary' => $isPrimary,
                            'sort_order' => $i,
                        ]);
                        $isPrimary = 0;
                    }
                }
            }
            flash('Product submitted for admin approval!', 'success');
            sendNotification('notify_product_review',
                '🛍 New Product for Review — ' . htmlspecialchars($name),
                '<h2>Product Submitted for Review</h2><p>A merchant has submitted a new product for approval.</p><div style="background:#f8f8f8;padding:12px;border-radius:8px;margin:12px 0"><div><strong>Product:</strong> '.htmlspecialchars($name).'</div><div><strong>Shop:</strong> '.htmlspecialchars($_shop['shop_name']).'</div><div><strong>Price:</strong> ₦'.number_format($price,2).'</div></div><a href="'.BASE_URL.'/admin/products.php?status=pending" style="display:inline-block;padding:10px 20px;background:#1B6B3A;color:#fff;border-radius:6px;font-weight:700;text-decoration:none">Review Products →</a>'
            );
        } else {
            flash('Please fill in all required fields.', 'error');
        }
    }

    if ($act === 'edit') {
        $pid     = (int)($_POST['product_id']      ?? 0);
        $priceCur= $_POST['price_currency']       ?? 'ngn';
        $costNgn = toNgn((float)($_POST['price']  ?? 0), $priceCur);
        $qty     = (int)($_POST['quantity']       ?? 0);
        $origCur = $_POST['orig_currency']        ?? 'ngn';
        $origRaw = (float)($_POST['original_price'] ?? 0);
        $orig    = $origRaw > 0 ? toNgn($origRaw, $origCur) : 0;
        // Recalculate customer price with markup
        $shopMarkup2 = DB::fetch('SELECT markup_rate FROM shops WHERE id=?', [$sid]);
        $markupRate2 = ($shopMarkup2 && $shopMarkup2['markup_rate'] !== null)
            ? (float)$shopMarkup2['markup_rate']
            : (float)getSetting('platform_markup', 5);
        $price = round($costNgn * (1 + $markupRate2 / 100), 2);
        $sizes= trim($_POST['sizes']              ?? '');
        $desc = trim($_POST['description']        ?? '');
        $name = trim($_POST['name']               ?? '');
        $catId= (int)($_POST['category_id']       ?? 0);
        $gender=     $_POST['gender']             ?? 'unisex';

        DB::update('products', [
            'name'           => $name,
            'cost_price'     => $costNgn,
            'price'          => $price,
            'quantity'       => $qty,
            'original_price' => $orig ?: null,
            'sizes'          => $sizes,
            'description'    => $desc,
            'category_id'    => $catId,
            'gender'         => $gender,
            'status'         => 'pending',
        ], 'id=? AND shop_id=?', [$pid, $sid]);

        // Upload new images if provided
        if (!empty($_FILES['images']['name'][0])) {
            $replaceAll = !empty($_POST['replace_images']);
            if ($replaceAll) {
                DB::query('DELETE FROM product_images WHERE product_id=?', [$pid]);
            }
            // Find current max sort_order
            $maxSort = (int)DB::count('SELECT COALESCE(MAX(sort_order),0) FROM product_images WHERE product_id=?', [$pid]);
            $isPrimary = $replaceAll ? 1 : 0; // if replacing, first new image is primary
            foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $file = [
                    'tmp_name' => $tmp,
                    'name'     => $_FILES['images']['name'][$i],
                    'type'     => $_FILES['images']['type'][$i],
                    'size'     => $_FILES['images']['size'][$i],
                    'error'    => $_FILES['images']['error'][$i],
                ];
                $path = uploadFile($file, 'products');
                if ($path) {
                    DB::insert('product_images', [
                        'product_id' => $pid,
                        'image_path' => $path,
                        'is_primary' => $isPrimary,
                        'sort_order' => $maxSort + $i + 1,
                    ]);
                    $isPrimary = 0;
                }
            }
        }

        // Delete specific images if requested
        $deleteImgs = $_POST['delete_images'] ?? [];
        if (!empty($deleteImgs)) {
            foreach ((array)$deleteImgs as $imgId) {
                DB::query('DELETE FROM product_images WHERE id=? AND product_id=?', [(int)$imgId, $pid]);
            }
        }

        flash('Product updated and re-submitted for review.', 'success');
    }

    if ($act === 'archive') {
        $pid = (int)($_POST['product_id'] ?? 0);
        DB::update('products', ['status'=>'archived'], 'id=? AND shop_id=?', [$pid, $sid]);
        flash('Product archived.');
    }

    redirect(BASE_URL . '/merchant/products.php' . ($_GET['status'] ? '?status='.urlencode($_GET['status']) : ''));
}

// ── NOW LOAD THE PAGE ─────────────────────────────────────────
$pageTitle = 'Products'; $activeNav = 'products';
include __DIR__ . '/../includes/header_merchant.php';

$sid      = $_shop['id'];
$cats     = getCategories();
$status   = trim($_GET['status'] ?? '');

$where  = 'shop_id=?'; $params = [$sid];
if ($status) { $where .= ' AND status=?'; $params[] = $status; }
$products = DB::fetchAll(
    "SELECT p.*, c.name cat_name, c.icon cat_icon,
            (SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) primary_image
     FROM products p JOIN categories c ON c.id=p.category_id
     WHERE $where ORDER BY p.created_at DESC", $params);
?>

<div class="m-page-head">
  <div>
    <div class="m-page-title">🏷 Products</div>
    <div class="m-page-sub"><?= count($products) ?> product<?= count($products)!==1?'s':''?></div>
  </div>
  <button class="btn btn-ju" onclick="openModal('add-modal')">+ Add Product</button>
</div>

<!-- Status tabs -->
<div class="tab-bar" style="margin-bottom:14px">
  <?php foreach ([''=> 'All', 'approved'=>'Live', 'pending'=>'Pending Review', 'rejected'=>'Rejected', 'archived'=>'Archived'] as $v=>$l): ?>
  <a href="?status=<?= urlencode($v) ?>" class="tab-btn <?= $status===$v?'active':''?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<?php if (empty($products)): ?>
<div class="empty-state card" style="padding:48px 24px">
  <span class="empty-icon">🏷</span>
  <p style="font-weight:600;margin-bottom:6px">No products yet</p>
  <p style="font-size:.82rem;margin-bottom:14px">Add your first product to start selling.</p>
  <button class="btn btn-ju btn-sm" onclick="openModal('add-modal')">+ Add First Product</button>
</div>
<?php else: ?>
<div class="card" style="padding:0">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Image</th><th>Product</th><th>Category</th>
          <th>Price</th><th>Stock</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td>
            <?php if ($p['primary_image']): ?>
              <img src="<?= imgUrl($p['primary_image']) ?>"
                   style="width:48px;height:54px;object-fit:cover;border-radius:6px;display:block">
            <?php else: ?>
              <div style="width:48px;height:54px;background:var(--bg);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.5rem"><?= $p['cat_icon'] ?></div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600;font-size:.86rem;color:var(--black)"><?= e($p['name']) ?></div>
            <div style="font-size:.72rem;color:var(--text-muted)"><?= e(substr($p['description']??'',0,55)) ?><?= strlen($p['description']??'')>55?'…':''?></div>
          </td>
          <td><span class="badge badge-muted"><?= $p['cat_icon'] ?> <?= e($p['cat_name']) ?></span></td>
          <td style="font-weight:700;color:var(--blue)"><?= money($p['price']) ?></td>
          <td>
            <?php if ($p['quantity']<=0): ?>
              <span class="badge badge-danger">Out of stock</span>
            <?php elseif ($p['quantity']<=5): ?>
              <span class="badge badge-warning">⚠ <?= $p['quantity'] ?></span>
            <?php else: ?>
              <span class="badge badge-success"><?= $p['quantity'] ?></span>
            <?php endif; ?>
          </td>
          <td><?= statusBadge($p['status']) ?></td>
          <td>
            <div style="display:flex;gap:5px">
              <button class="btn btn-blue btn-xs"
                      onclick='openEditModal(<?= htmlspecialchars(json_encode($p)) ?>)'>
                ✏ Edit
              </button>
              <?php if ($p['status'] !== 'archived'): ?>
              <form method="POST" onsubmit="return confirm('Archive this product?')">
                <input type="hidden" name="action" value="archive">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn btn-danger btn-xs">Archive</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ══ ADD PRODUCT MODAL ══════════════════════════════════════ -->
<div class="modal-bg" id="add-modal">
  <div class="modal" style="max-width:620px">
    <div class="modal-head">
      <h3 class="modal-title">+ Add New Product</h3>
      <button class="modal-close" onclick="closeModal('add-modal')">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Product Name *</label>
          <input type="text" name="name" class="form-control" required
                 placeholder="e.g. Royal Ankara Boubou">
        </div>
        <div class="form-group">
          <label class="form-label">Category *</label>
          <select name="category_id" class="form-control" required>
            <option value="">Select category</option>
            <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>"><?= $c['icon'] ?> <?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <!-- Currency-aware price inputs -->
      <?php $rate = usdRate(); ?>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">
            Selling Price *
            <span style="font-weight:400;color:var(--text-muted)">— enter in any currency</span>
          </label>
          <div style="display:flex;gap:0;align-items:stretch">
            <select name="price_currency" id="add-price-cur"
                    class="form-control"
                    style="width:85px;border-radius:var(--r-sm) 0 0 var(--r-sm);border-right:none;flex-shrink:0"
                    onchange="updateAddPreview()">
              <?php foreach (enabledCurrencies() as $_pcc => $_pcfg): ?>
              <option value="<?= $_pcc ?>"><?= htmlspecialchars($_pcfg['symbol']) ?> <?= strtoupper($_pcc) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="number" name="price" id="add-price-val"
                   class="form-control" min="0" step="0.01" required
                   placeholder="15000"
                   style="border-radius:0 var(--r-sm) var(--r-sm) 0"
                   oninput="updateAddPreview()">
          </div>
          <div class="form-hint" id="add-price-hint">
            Prices stored in ₦ NGN. Select $ to enter in USD — auto-converts at current rate.
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">
            Compare-at / Original Price
            <span style="font-weight:400;color:var(--text-muted)">(shows strikethrough)</span>
          </label>
          <div style="display:flex;gap:0;align-items:stretch">
            <select name="orig_currency" id="add-orig-cur"
                    class="form-control"
                    style="width:85px;border-radius:var(--r-sm) 0 0 var(--r-sm);border-right:none;flex-shrink:0"
                    onchange="updateAddPreview()">
              <?php foreach (enabledCurrencies() as $_pcc => $_pcfg): ?>
              <option value="<?= $_pcc ?>"><?= htmlspecialchars($_pcfg['symbol']) ?> <?= strtoupper($_pcc) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="number" name="original_price" id="add-orig-val"
                   class="form-control" min="0" step="0.01"
                   placeholder="20000"
                   style="border-radius:0 var(--r-sm) var(--r-sm) 0"
                   oninput="updateAddPreview()">
          </div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Stock Quantity *</label>
          <input type="number" name="quantity" class="form-control" min="0" required placeholder="10">
        </div>
        <div class="form-group">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-control">
            <option value="unisex">Unisex</option>
            <option value="female">Female</option>
            <option value="male">Male</option>
            <option value="kids">Kids</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Available Sizes <span style="font-weight:400;color:var(--text-muted)">(comma-separated)</span></label>
        <input type="text" name="sizes" class="form-control" placeholder="S, M, L, XL, XXL">
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"
                  placeholder="Material, style, fit, care instructions…"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">
          Product Images
          <span style="font-weight:400;color:var(--text-muted)">(first image = main photo, up to 5)</span>
        </label>
        <div class="upload-zone" onclick="document.getElementById('add-imgs').click()" style="padding:16px;cursor:pointer">
          <div style="font-size:1.6rem;margin-bottom:5px">📷</div>
          <div style="font-size:.82rem;color:var(--text-muted)">Click to upload product photos</div>
          <input type="file" id="add-imgs" name="images[]" multiple accept="image/*"
                 style="display:none" onchange="previewImgs(this,'add-prev')">
        </div>
        <div id="add-prev" style="display:flex;gap:7px;flex-wrap:wrap;margin-top:8px"></div>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;border-top:1px solid var(--border-lt);padding-top:12px">
        <button type="button" class="btn btn-ghost" onclick="closeModal('add-modal')">Cancel</button>
        <button type="submit" class="btn btn-ju">Submit for Review</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ EDIT PRODUCT MODAL ═════════════════════════════════════ -->
<div class="modal-bg" id="edit-modal">
  <div class="modal" style="max-width:640px;max-height:92vh;overflow-y:auto">
    <div class="modal-head">
      <h3 class="modal-title">✏ Edit Product</h3>
      <button class="modal-close" onclick="closeModal('edit-modal')">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data" id="edit-form">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="product_id" id="edit-id">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Product Name *</label>
          <input type="text" name="name" id="edit-name" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <select name="category_id" id="edit-cat" class="form-control">
            <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>"><?= $c['icon'] ?> <?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php $rate = usdRate(); ?>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Your Cost of Goods * <span style="font-weight:400;color:var(--text-muted)">(excl. markup — what you pay)</span></label>
          <div style="display:flex;gap:0;align-items:stretch">
            <select name="price_currency" id="edit-price-cur"
                    class="form-control"
                    style="width:85px;border-radius:var(--r-sm) 0 0 var(--r-sm);border-right:none;flex-shrink:0"
                    onchange="updateEditPreview()">
              <?php foreach (enabledCurrencies() as $_pcc => $_pcfg): ?>
              <option value="<?= $_pcc ?>"><?= htmlspecialchars($_pcfg['symbol']) ?> <?= strtoupper($_pcc) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="number" name="price" id="edit-price"
                   class="form-control" min="0" step="0.01" required
                   style="border-radius:0 var(--r-sm) var(--r-sm) 0"
                   oninput="updateEditPreview()">
          </div>
          <div class="form-hint" id="edit-price-hint"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Compare-at / Original Price</label>
          <div style="display:flex;gap:0;align-items:stretch">
            <select name="orig_currency" id="edit-orig-cur"
                    class="form-control"
                    style="width:85px;border-radius:var(--r-sm) 0 0 var(--r-sm);border-right:none;flex-shrink:0"
                    onchange="updateEditPreview()">
              <?php foreach (enabledCurrencies() as $_pcc => $_pcfg): ?>
              <option value="<?= $_pcc ?>"><?= htmlspecialchars($_pcfg['symbol']) ?> <?= strtoupper($_pcc) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="number" name="original_price" id="edit-orig"
                   class="form-control" min="0" step="0.01"
                   style="border-radius:0 var(--r-sm) var(--r-sm) 0"
                   oninput="updateEditPreview()">
          </div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Stock Quantity *</label>
          <input type="number" name="quantity" id="edit-qty" class="form-control" min="0" required>
        </div>
        <div class="form-group">
          <label class="form-label">Gender</label>
          <select name="gender" id="edit-gender" class="form-control">
            <option value="unisex">Unisex</option>
            <option value="female">Female</option>
            <option value="male">Male</option>
            <option value="kids">Kids</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Sizes</label>
        <input type="text" name="sizes" id="edit-sizes" class="form-control">
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" id="edit-desc" class="form-control" rows="3"></textarea>
      </div>

      <!-- Current images -->
      <div class="form-group">
        <label class="form-label">Current Images</label>
        <div id="edit-current-imgs" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">
          <span style="font-size:.78rem;color:var(--text-muted);font-style:italic">Loading images…</span>
        </div>
      </div>

      <!-- Upload new images -->
      <div class="form-group">
        <label class="form-label">
          Add / Replace Images
          <span style="font-weight:400;color:var(--text-muted)">(upload to add more photos)</span>
        </label>
        <div class="upload-zone" onclick="document.getElementById('edit-imgs').click()"
             style="padding:14px;cursor:pointer">
          <div style="font-size:1.4rem;margin-bottom:4px">📷</div>
          <div style="font-size:.8rem;color:var(--text-muted)">Click to upload new product photos</div>
          <input type="file" id="edit-imgs" name="images[]" multiple accept="image/*"
                 style="display:none" onchange="previewImgs(this,'edit-new-prev')">
        </div>
        <div id="edit-new-prev" style="display:flex;gap:7px;flex-wrap:wrap;margin-top:8px"></div>

        <div class="form-check" style="margin-top:10px">
          <input type="checkbox" name="replace_images" id="replace-chk" value="1">
          <label for="replace-chk" style="font-size:.82rem;color:var(--text)">
            Replace all existing images with new uploads
          </label>
        </div>
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;border-top:1px solid var(--border-lt);padding-top:12px">
        <button type="button" class="btn btn-ghost" onclick="closeModal('edit-modal')">Cancel</button>
        <button type="submit" class="btn btn-ju">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── Exchange rate for conversion hints ───────────────────────
// Multi-currency rates (NGN per 1 unit of each currency)
var PROD_RATES = {
  ngn: 1,
  usd: <?= currencyRate('usd') ?>,
  eur: <?= currencyRate('eur') ?>,
  gbp: <?= currencyRate('gbp') ?>
};
var PROD_SYMS = {ngn:'₦',usd:'$',eur:'€',gbp:'£'};

function updateAddPreview() {
  var cur = document.getElementById('add-price-cur').value;
  var val = parseFloat(document.getElementById('add-price-val').value);
  var hint = document.getElementById('add-price-hint');
  if (!val || isNaN(val)) { hint.textContent = 'Stored as ₦ NGN.'; return; }
  var rate = PROD_RATES[cur] || 1;
  var sym  = PROD_SYMS[cur] || cur.toUpperCase();
  if (cur === 'ngn') {
    hint.textContent = '₦' + val.toLocaleString('en-NG',{maximumFractionDigits:2}) + ' stored as-is';
  } else {
    var ngn = val * rate;
    hint.textContent = sym + val.toFixed(2) + ' = ₦' + ngn.toLocaleString('en-NG',{maximumFractionDigits:2}) + ' (rate: ₦' + rate.toLocaleString() + '/' + sym + '1)';
  }
}

function updateEditPreview() {
  var cur = document.getElementById('edit-price-cur').value;
  var val = parseFloat(document.getElementById('edit-price').value);
  var hint = document.getElementById('edit-price-hint');
  if (!hint || !val || isNaN(val)) { if(hint) hint.textContent=''; return; }
  var rate = PROD_RATES[cur] || 1;
  var sym  = PROD_SYMS[cur] || cur.toUpperCase();
  if (cur === 'ngn') {
    hint.textContent = '₦' + val.toLocaleString('en-NG',{maximumFractionDigits:2}) + ' stored as-is';
  } else {
    hint.textContent = sym + val.toFixed(2) + ' → ₦' + (val*rate).toLocaleString('en-NG',{maximumFractionDigits:2});
  }
}

// ── Open edit modal with product data ─────────────────────────
function openEditModal(p) {
  document.getElementById('edit-id').value     = p.id;
  document.getElementById('edit-name').value   = p.name || '';
  // Prices are stored in NGN — show in NGN by default
  document.getElementById('edit-price-cur').value = 'ngn';
  document.getElementById('edit-orig-cur').value  = 'ngn';
  document.getElementById('edit-price').value  = p.price || '';
  document.getElementById('edit-orig').value   = p.original_price || '';
  document.getElementById('edit-qty').value    = p.quantity || '';
  document.getElementById('edit-sizes').value  = p.sizes || '';
  document.getElementById('edit-desc').value   = p.description || '';
  document.getElementById('edit-cat').value    = p.category_id || '';
  document.getElementById('edit-gender').value = p.gender || 'unisex';

  // Clear new image previews
  document.getElementById('edit-new-prev').innerHTML = '';
  document.getElementById('edit-imgs').value = '';
  document.getElementById('replace-chk').checked = false;

  // Load existing product images via API
  var imgsDiv = document.getElementById('edit-current-imgs');
  imgsDiv.innerHTML = '<span style="font-size:.76rem;color:var(--text-muted)">Loading…</span>';
  fetch(BASE_URL + '/api/product-images.php?product_id=' + p.id)
    .then(function(r){ return r.json(); })
    .then(function(data) {
      if (!data.images || data.images.length === 0) {
        imgsDiv.innerHTML = '<span style="font-size:.76rem;color:var(--text-muted);font-style:italic">No images uploaded yet.</span>';
        return;
      }
      imgsDiv.innerHTML = '';
      data.images.forEach(function(img) {
        var wrap = document.createElement('div');
        wrap.style.cssText = 'position:relative;display:inline-block';
        wrap.innerHTML =
          '<img src="' + img.url + '" style="width:72px;height:82px;object-fit:cover;border-radius:6px;border:2px solid ' +
          (img.is_primary ? 'var(--ju)' : 'var(--border-lt)') + '">' +
          (img.is_primary ? '<span style="position:absolute;top:2px;left:2px;background:var(--ju);color:#fff;font-size:.58rem;font-weight:700;padding:1px 5px;border-radius:3px">MAIN</span>' : '') +
          '<label title="Delete this image" style="position:absolute;top:2px;right:2px;background:rgba(0,0,0,.55);color:#fff;font-size:.65rem;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer">' +
          '<input type="checkbox" name="delete_images[]" value="' + img.id + '" form="edit-form" style="display:none" onchange="this.parentElement.style.background=this.checked?\'var(--red)\':\' rgba(0,0,0,.55)\'">' +
          '✕</label>';
        imgsDiv.appendChild(wrap);
      });
      imgsDiv.insertAdjacentHTML('beforeend', '<div style="font-size:.7rem;color:var(--text-muted);align-self:flex-end">Tick ✕ to delete an image</div>');
    })
    .catch(function() {
      imgsDiv.innerHTML = '<span style="font-size:.76rem;color:var(--text-muted)">Could not load images.</span>';
    });

  openModal('edit-modal');
}

// ── Image preview ─────────────────────────────────────────────
function previewImgs(input, targetId) {
  var div = document.getElementById(targetId);
  div.innerHTML = '';
  Array.from(input.files).slice(0, 5).forEach(function(f, i) {
    var r = new FileReader();
    r.onload = function(e) {
      div.innerHTML +=
        '<div style="position:relative">' +
        '<img src="' + e.target.result + '" style="width:72px;height:82px;object-fit:cover;border-radius:6px;border:2px solid ' + (i===0?'var(--ju)':'var(--border-lt)') + '">' +
        '<span style="position:absolute;top:2px;left:2px;background:' + (i===0?'var(--ju)':'var(--text-muted)') + ';color:#fff;font-size:.58rem;font-weight:700;padding:1px 5px;border-radius:3px">' +
        (i===0?'MAIN':i+1) + '</span>' +
        '</div>';
    };
    r.readAsDataURL(f);
  });
}
</script>

<?php include __DIR__ . '/../includes/footer_merchant.php'; ?>
