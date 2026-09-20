<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/auth.php';
if (!logCheck()) redirect(BASE_URL . '/logistics/login.php');
$la = logAuth();
$isForced = !empty($_SESSION['log_temp_pw']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curr = $_POST['current_password'] ?? '';
    $new  = $_POST['new_password']     ?? '';
    $conf = $_POST['confirm_password'] ?? '';
    $user = DB::fetch('SELECT * FROM logistics_users WHERE id=?', [$la['id']]);
    if (!$user || !password_verify($curr, $user['password_hash'])) $errors[] = 'Current password is incorrect.';
    if (strlen($new) < 8)  $errors[] = 'New password must be at least 8 characters.';
    if ($new !== $conf)    $errors[] = 'Passwords do not match.';
    if (empty($errors)) {
        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost'=>11]);
        DB::update('logistics_users', ['password_hash'=>$hash,'temp_password'=>0], 'id=?', [$la['id']]);
        $_SESSION['log_temp_pw'] = false;
        flash('Password changed successfully.', 'success');
        redirect(BASE_URL . '/logistics/');
    }
}
$pageTitle = 'Change Password'; $activeNav = 'pw';
include __DIR__ . '/header.php';
?>
<div style="max-width:480px;margin:0 auto">
  <h2 style="font-family:var(--ff-head);font-size:1.2rem;margin-bottom:16px">
    🔑 <?= $isForced ? 'Set Your Password — Required Before Continuing' : 'Change Password' ?>
  </h2>
  <?php if ($isForced): ?>
  <div class="alert alert-warning" style="margin-bottom:16px">
    This is a temporary password. You must set a new password to continue.
  </div>
  <?php endif; ?>
  <?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $e) echo "<div>• ".e($e)."</div>"; ?></div>
  <?php endif; ?>
  <div class="card">
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Current / Temporary Password *</label>
        <input type="password" name="current_password" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">New Password *</label>
        <input type="password" name="new_password" class="form-control" required minlength="8" placeholder="Min. 8 characters">
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password *</label>
        <input type="password" name="confirm_password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-ju btn-full">Set New Password</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
