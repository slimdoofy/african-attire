<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle='Shop Setup'; $activeNav='shop-setup';
ob_start();
include __DIR__ . '/../includes/header_merchant.php';
$sid = $_shop['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'shop_name'    => trim($_POST['shop_name']   ?? ''),
        'description'  => trim($_POST['description'] ?? ''),
        'city'         => trim($_POST['city']         ?? ''),
        'pickup_city'  => trim($_POST['pickup_city']  ?? ''),
    ];
    if (!empty($_FILES['logo']['name'])) {
        $p = uploadFile($_FILES['logo'],'shops');
        if ($p) $data['logo'] = $p;
    }
    if (!empty($_FILES['banner']['name'])) {
        $p = uploadFile($_FILES['banner'],'banners');
        if ($p) $data['banner'] = $p;
    }
    DB::update('shops', $data, 'id=?', [$sid]);
    $_shop = DB::fetch('SELECT * FROM shops WHERE id=?', [$sid]);
    flash('Shop profile updated!');
    redirect(BASE_URL.'/merchant/shop-setup.php');
}
?>

<div class="m-page-head">
  <h1 class="m-page-title">🏪 Shop Setup</h1>
  <p class="m-page-sub">Customize how your shop appears to customers</p>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem">
  <div class="card">
    <form method="POST" enctype="multipart/form-data">
      <div class="card-title mb-3">Shop Information</div>
      <div class="form-group">
        <label class="form-label">Shop Name</label>
        <input type="text" name="shop_name" class="form-control" value="<?= e($_shop['shop_name']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">City / Location</label>
        <input type="text" name="city" class="form-control" value="<?= e($_shop['city'] ?? '') ?>" placeholder="e.g. Lagos, Abuja, Accra">
      </div>
      <div class="form-group">
        <label class="form-label">Pickup / Collection City
          <span style="font-weight:400;color:var(--text-muted)">(used for logistics fee calculation)</span>
        </label>
        <input type="text" name="pickup_city" class="form-control"
               value="<?= e($_shop['pickup_city'] ?? '') ?>"
               placeholder="City where orders are collected by logistics partner">
        <div class="form-hint">
          Enter the city where your products are picked up for delivery. If left blank, your shop city above is used.
          This must match a city in our <a href="<?= BASE_URL ?>/admin/logistics.php" style="color:var(--blue)">supported cities list</a>.
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Shop Description</label>
        <textarea name="description" class="form-control" rows="5" placeholder="Tell customers about your brand, specialties, story…"><?= e($_shop['description'] ?? '') ?></textarea>
      </div>
      <hr class="divider">
      <div class="card-title mb-3">Branding</div>
      <div class="form-group">
        <label class="form-label">Shop Logo <span class="text-xs text-muted">(Recommended: 200×200px)</span></label>
        <?php if ($_shop['logo']): ?>
          <img src="<?= imgUrl($_shop['logo']) ?>" style="width:70px;height:70px;border-radius:50%;object-fit:cover;margin-bottom:.5rem;border:2px solid var(--gold-light)">
        <?php endif; ?>
        <div class="upload-zone" onclick="document.getElementById('logo-inp').click()">
          <div class="icon">🖼</div><div class="text-sm text-muted">Click to upload logo (JPG/PNG, max 5MB)</div>
          <input type="file" id="logo-inp" name="logo" accept="image/*" style="display:none" data-preview="logo-prev">
        </div>
        <img id="logo-prev" style="display:none;width:70px;height:70px;border-radius:50%;object-fit:cover;margin-top:.5rem">
      </div>
      <div class="form-group">
        <label class="form-label">Shop Banner <span class="text-xs text-muted">(Recommended: 1200×300px)</span></label>
        <?php if ($_shop['banner']): ?>
          <img src="<?= imgUrl($_shop['banner']) ?>" style="width:100%;height:80px;object-fit:cover;border-radius:8px;margin-bottom:.5rem">
        <?php endif; ?>
        <div class="upload-zone" onclick="document.getElementById('banner-inp').click()">
          <div class="icon">🏞</div><div class="text-sm text-muted">Click to upload banner image</div>
          <input type="file" id="banner-inp" name="banner" accept="image/*" style="display:none" data-preview="banner-prev">
        </div>
        <img id="banner-prev" style="display:none;width:100%;height:80px;object-fit:cover;border-radius:8px;margin-top:.5rem">
      </div>
      <div style="display:flex;gap:1rem">
        <a href="<?= BASE_URL ?>/customer/merchant.php?id=<?= $sid ?>" target="_blank" class="btn btn-ghost">Preview Shop →</a>
        <button type="submit" class="btn btn-blue" style="flex:1">💾 Save Shop Profile</button>
      </div>
    </form>
  </div>

  <!-- Preview -->
  <div>
    <div class="card card-sm">
      <div class="card-title mb-2">Profile Completeness</div>
      <?php
      $checks = [
        'Shop name'   => !empty($_shop['shop_name']),
        'Description' => !empty($_shop['description']),
        'Logo'        => !empty($_shop['logo']),
        'Banner'      => !empty($_shop['banner']),
        'Bank details'=> !empty($_shop['bank_account']),
      ];
      $done = count(array_filter($checks));
      $pct  = round($done / count($checks) * 100);
      ?>
      <div class="progress" style="margin-bottom:.5rem"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
      <div class="text-sm text-muted mb-2"><?= $pct ?>% complete</div>
      <?php foreach ($checks as $label => $ok): ?>
      <div class="flex-center gap-2 text-sm" style="margin-bottom:.4rem">
        <span><?= $ok ? '✅' : '⬜' ?></span> <span style="color:<?= $ok?'var(--success)':'var(--cream-3)'?>"><?= $label ?></span>
      </div>
      <?php endforeach; ?>
      <div class="mt-2">
        <a href="<?= BASE_URL ?>/merchant/settings.php" class="btn btn-ghost btn-sm btn-full">Update Bank Details →</a>
      </div>
    </div>
    <div class="card card-sm mt-2">
      <div class="card-title mb-1">Shop Status</div>
      <?= statusBadge($_shop['status']) ?>
      <?php if ($_shop['status'] === 'pending'): ?>
      <p class="text-sm text-muted mt-1">Your shop is under review. Products will go live after approval.</p>
      <?php elseif ($_shop['status'] === 'approved'): ?>
      <p class="text-sm text-muted mt-1">Your shop is live! Customers can discover your products.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer_merchant.php'; ?>
