<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle='Settings'; $activeNav='settings';
ob_start();
include __DIR__ . '/../includes/header_merchant.php';
$sid  = $_shop['id'];
$user = DB::fetch('SELECT * FROM users WHERE id=?',[Auth::id()]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'bank') {
        DB::update('shops',[
            'bank_name'         =>trim($_POST['bank_name']??''),
            'bank_account'      =>trim($_POST['bank_account']??''),
            'bank_account_name' =>trim($_POST['bank_account_name']??''),
        ],'id=?',[$sid]);
        flash('Bank details updated!');
    }
    if ($act === 'password') {
        $cur  = $_POST['current'] ?? '';
        $new  = $_POST['new_pass'] ?? '';
        $conf = $_POST['confirm']  ?? '';
        if (!Auth::verify($cur, $user['password_hash'])) {
            flash('Current password is incorrect.','error');
        } elseif (strlen($new) < 8) {
            flash('New password must be at least 8 characters.','error');
        } elseif ($new !== $conf) {
            flash('Passwords do not match.','error');
        } else {
            DB::update('users',['password_hash'=>Auth::hash($new)],'id=?',[Auth::id()]);
            flash('Password changed successfully!');
        }
    }
    if ($act === 'profile') {
        $name = trim($_POST['name']??'');
        $phone= trim($_POST['phone']??'');
        DB::update('users',['name'=>$name,'phone'=>$phone],'id=?',[Auth::id()]);
        $_SESSION['uname'] = $name;
        flash('Profile updated!');
    }
    redirect(BASE_URL.'/merchant/settings.php');
}
?>

<div class="m-page-head">
  <h1 class="m-page-title">⚙️ Settings</h1>
  <p class="m-page-sub">Manage account and payment settings</p>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
  <!-- Profile -->
  <div class="card">
    <div class="card-title">👤 Account Profile</div>
    <form method="POST" style="margin-top:1rem">
      <input type="hidden" name="action" value="profile">
      <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required></div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-control" value="<?= e($user['email']) ?>" readonly></div>
      <div class="form-group"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?= e($user['phone']??'') ?>"></div>
      <button type="submit" class="btn btn-blue btn-full">Save Profile</button>
    </form>
  </div>

  <!-- Bank -->
  <div class="card">
    <div class="card-title">🏦 Bank Details</div>
    <form method="POST" style="margin-top:1rem">
      <input type="hidden" name="action" value="bank">
      <div class="form-group">
        <label class="form-label">Bank Name</label>
        <select name="bank_name" class="form-control">
          <?php foreach(['Access Bank','Ecobank','FCMB','Fidelity Bank','First Bank','GTBank','Kuda Bank','Opay','Polaris Bank','Stanbic IBTC','Sterling Bank','UBA','Union Bank','Wema Bank','Zenith Bank'] as $b): ?>
          <option value="<?= $b ?>" <?= ($_shop['bank_name']??'')===$b?'selected':''?>><?= $b ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Account Number</label><input type="text" name="bank_account" class="form-control" maxlength="10" value="<?= e($_shop['bank_account']??'') ?>"></div>
      <div class="form-group"><label class="form-label">Account Name</label><input type="text" name="bank_account_name" class="form-control" value="<?= e($_shop['bank_account_name']??'') ?>"></div>
      <button type="submit" class="btn btn-blue btn-full">Update Bank Details</button>
    </form>
  </div>

  <!-- Password -->
  <div class="card">
    <div class="card-title">🔒 Change Password</div>
    <form method="POST" style="margin-top:1rem">
      <input type="hidden" name="action" value="password">
      <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current" class="form-control" required></div>
      <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_pass" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" name="confirm" class="form-control" required></div>
      <button type="submit" class="btn btn-blue btn-full">Change Password</button>
    </form>
  </div>

  <!-- Commission Info -->
  <div class="card">
    <div class="card-title">💹 Commission Agreement</div>
    <div style="margin-top:1rem">
      <div class="m-stat" style="margin-bottom:1rem">
        <div class="stat-label">Your Commission Rate</div>
        <div class="stat-value"><?= $_shop['commission_rate'] ?>%</div>
        <div class="stat-hint">Applied to every sale</div>
      </div>
      <p class="text-sm text-muted">Commission is automatically deducted from your revenue before payout. Contact support to discuss custom rates for high-volume merchants.</p>
      <a href="mailto:<?= getSetting('site_email','hello@africanattire.com') ?>" class="btn btn-ghost btn-sm mt-2">Contact Support</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer_merchant.php'; ?>
