<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole('admin', '/admin/login.php');

// Only sub-admins with temp passwords need this
if (empty($_SESSION['admin_temp_pw'])) {
    redirect(BASE_URL . '/admin/');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curr = $_POST['current_password'] ?? '';
    $new  = $_POST['new_password']     ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    $uid  = $_SESSION['admin_user_id'] ?? 0;
    $user = DB::fetch('SELECT * FROM admin_users WHERE id=?', [$uid]);

    if (!$user || !Auth::verify($curr, $user['password_hash'])) {
        $errors[] = 'Current password is incorrect.';
    }
    if (strlen($new) < 8)  $errors[] = 'New password must be at least 8 characters.';
    if ($new !== $conf)    $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        DB::update('admin_users', [
            'password_hash' => Auth::hash($new),
            'temp_password' => 0,
        ], 'id=?', [$uid]);
        $_SESSION['admin_temp_pw'] = false;
        auditLog('admin_password_changed', 'Sub-admin changed temporary password', 'admin_user', $uid, 'admin');
        flash('Password changed successfully.', 'success');
        redirect(BASE_URL . '/admin/');
    }
}

$pageTitle = 'Set New Password'; $activeNav = '';
include __DIR__ . '/../includes/header_admin.php';
?>
<div style="max-width:480px;margin:0 auto">
  <div class="alert alert-warning" style="margin-bottom:18px">
    <strong>⚠️ Action Required:</strong> You must set a new password before continuing.
    Your current password is temporary and must be changed now.
  </div>
  <?php if ($errors): ?>
  <div class="alert alert-danger" style="margin-bottom:14px">
    <?php foreach ($errors as $e): ?><div>• <?= e($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="card">
    <div class="card-head"><div class="card-title">🔑 Set Your New Password</div></div>
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
      <button type="submit" class="btn btn-ju btn-full">Set New Password &amp; Continue</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
