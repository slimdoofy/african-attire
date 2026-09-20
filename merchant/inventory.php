<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle='Inventory'; $activeNav='inventory';
ob_start();
include __DIR__ . '/../includes/header_merchant.php';
$sid = $_shop['id'];

// Quick stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['qty'] ?? [] as $pid => $qty) {
        DB::update('products',['quantity'=>(int)$qty],'id=? AND shop_id=?',[(int)$pid,$sid]);
    }
    flash('Inventory updated!');
    redirect(BASE_URL.'/merchant/inventory.php');
}

$filter = $_GET['filter'] ?? '';
$where  = "shop_id=? AND status != 'archived'"; $params = [$sid];
if ($filter === 'low')  { $where .= " AND quantity > 0 AND quantity <= 5"; }
if ($filter === 'out')  { $where .= " AND quantity = 0"; }
if ($filter === 'good') { $where .= " AND quantity > 5"; }

$products = DB::fetchAll("
    SELECT p.*, c.name cat_name, c.icon cat_icon
    FROM products p JOIN categories c ON c.id=p.category_id
    WHERE $where ORDER BY p.quantity ASC, p.name ASC", $params);

$lowCount = DB::count("SELECT COUNT(*) FROM products WHERE shop_id=? AND quantity > 0 AND quantity <= 5 AND status != 'archived'",[$sid]);
$outCount = DB::count("SELECT COUNT(*) FROM products WHERE shop_id=? AND quantity = 0 AND status != 'archived'",[$sid]);
?>

<div class="m-page-head">
  <h1 class="m-page-title">📋 Inventory</h1>
  <p class="m-page-sub">Manage stock levels across your products</p>
</div>

<?php if ($lowCount + $outCount > 0): ?>
<div class="alert alert-warning">⚠️ <strong><?= $outCount ?> out of stock</strong> and <strong><?= $lowCount ?> low stock</strong> products need attention.</div>
<?php endif; ?>

<div class="tab-bar">
  <a href="?" class="tab-btn <?= $filter===''?'active':'' ?>">All Products</a>
  <a href="?filter=low" class="tab-btn <?= $filter==='low'?'active':'' ?>">⚠ Low Stock (<?= $lowCount ?>)</a>
  <a href="?filter=out" class="tab-btn <?= $filter==='out'?'active':'' ?>">✗ Out of Stock (<?= $outCount ?>)</a>
  <a href="?filter=good" class="tab-btn <?= $filter==='good'?'active':'' ?>">✓ In Stock</a>
</div>

<?php if (empty($products)): ?>
<div class="empty-state card"><span class="empty-icon">📋</span><p>No products found.</p></div>
<?php else: ?>
<div class="card">
  <form method="POST">
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Product</th><th>Category</th><th>Status</th><th>Current Stock</th><th>Update Stock</th></tr></thead>
        <tbody>
          <?php foreach ($products as $p): ?>
          <tr>
            <td class="fw-600"><?= e($p['name']) ?></td>
            <td class="text-sm text-muted"><?= $p['cat_icon'] ?> <?= e($p['cat_name']) ?></td>
            <td><?= statusBadge($p['status']) ?></td>
            <td>
              <?php if ($p['quantity'] <= 0): ?>
                <span class="badge badge-danger">Out of stock</span>
              <?php elseif ($p['quantity'] <= 5): ?>
                <span class="badge badge-warning">⚠ <?= $p['quantity'] ?> left</span>
              <?php else: ?>
                <span class="badge badge-success"><?= $p['quantity'] ?> in stock</span>
              <?php endif; ?>
            </td>
            <td>
              <input type="number" name="qty[<?= $p['id'] ?>]" value="<?= $p['quantity'] ?>"
                     class="form-control" style="width:90px;padding:.28rem .6rem" min="0" max="9999">
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="padding:1rem;border-top:1px solid var(--border)">
      <button type="submit" class="btn btn-blue">💾 Save All Changes</button>
    </div>
  </form>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer_merchant.php'; ?>
