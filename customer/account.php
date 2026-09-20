<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole('customer', '/customer/login.php');
$user    = Auth::user();
$profile = DB::fetch('SELECT * FROM customer_profiles WHERE user_id=?', [$user['id']]);
$errors  = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST['csrf'] ?? '')) {
    $tab = $_POST['tab'] ?? 'profile';

    if ($tab === 'profile') {
        $name  = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $addr  = trim($_POST['address'] ?? '');
        $city  = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        if (!$name) $errors[] = 'Name is required.';
        if (!$errors) {
            DB::update('users', ['name'=>$name,'phone'=>$phone], 'id=?', [$user['id']]);
            DB::update('customer_profiles', ['address_line1'=>$addr,'city'=>$city,'state'=>$state], 'user_id=?', [$user['id']]);
            // Update session
            $_SESSION['user_name'] = $name;
            $success = 'Profile updated successfully.';
            $user    = Auth::user();
            $profile = DB::fetch('SELECT * FROM customer_profiles WHERE user_id=?', [$user['id']]);
        }
    } elseif ($tab === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $fullUser = DB::fetch('SELECT * FROM users WHERE id=?', [$user['id']]);
        if (!Auth::verifyPassword($current, $fullUser['password_hash'])) { $errors[] = 'Current password is incorrect.'; }
        elseif (strlen($new) < 8) { $errors[] = 'New password must be at least 8 characters.'; }
        elseif ($new !== $confirm) { $errors[] = 'Passwords do not match.'; }
        else {
            DB::update('users', ['password_hash'=>Auth::hashPassword($new)], 'id=?', [$user['id']]);
            $success = 'Password changed successfully.';
        }
    }
}

$pageTitle = 'My Account';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <div class="container">
    <h1>👤 My Account</h1>
    <div class="breadcrumb"><a href="<?= BASE_URL ?>">Home</a> <span>›</span> Account</div>
  </div>
</div>
<div class="container" style="padding:40px 24px 64px;">
  <?php foreach ($errors as $e): ?><div class="flash flash-error">⚠️ <?= e($e) ?></div><?php endforeach; ?>
  <?php if ($success): ?><div class="flash flash-success">✅ <?= e($success) ?></div><?php endif; ?>

  <div class="settings-grid">
    <div class="settings-nav">
      <a href="#profile"  class="settings-nav-link active" onclick="showTab('profile',this)">👤 Profile</a>
      <a href="#password" class="settings-nav-link" onclick="showTab('password',this)">🔒 Password</a>
      <a href="<?= BASE_URL ?>/customer/orders.php" class="settings-nav-link">📦 My Orders</a>
    </div>
    <div>
      <!-- Profile Tab -->
      <div id="tab-profile" class="settings-section">
        <div class="settings-section-title">Personal Information</div>
        <form method="POST">
          <input type="hidden" name="csrf" value="<?= Auth::csrf() ?>">
          <input type="hidden" name="tab"  value="profile">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled style="opacity:.6;cursor:not-allowed;">
              <div class="form-hint">Email cannot be changed.</div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="tel" name="phone" class="form-control" value="<?= e(DB::fetch('SELECT phone FROM users WHERE id=?',[$user['id']])['phone'] ?? '') ?>">
          </div>
          <div class="divider"></div>
          <div class="settings-section-title">Delivery Address</div>
          <div class="form-group">
            <label class="form-label">Street Address</label>
            <input type="text" name="address" class="form-control" value="<?= e($profile['address_line1'] ?? '') ?>">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">City</label>
              <input type="text" name="city" class="form-control" value="<?= e($profile['city'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">State</label>
              <input type="text" name="state" class="form-control" value="<?= e($profile['state'] ?? '') ?>">
            </div>
          </div>
          <button type="submit" class="btn btn-gold">Save Changes</button>
        </form>
      </div>
      <!-- Password Tab -->
      <div id="tab-password" class="settings-section" style="display:none;">
        <div class="settings-section-title">Change Password</div>
        <form method="POST" style="max-width:440px;">
          <input type="hidden" name="csrf" value="<?= Auth::csrf() ?>">
          <input type="hidden" name="tab"  value="password">
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-gold">Update Password</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php $extraScripts = '<script>
function showTab(name, link) {
  document.querySelectorAll("[id^=tab-]").forEach(t => t.style.display="none");
  document.getElementById("tab-"+name).style.display="block";
  document.querySelectorAll(".settings-nav-link").forEach(l => l.classList.remove("active"));
  link.classList.add("active");
  return false;
}
</script>'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
