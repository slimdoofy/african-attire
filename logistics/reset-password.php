<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/auth.php';
$token = trim($_GET['token'] ?? ''); $errors = []; $done = false;
$reset = $token ? DB::fetch("SELECT * FROM password_resets WHERE token=? AND portal='logistics' AND used=0 AND expires_at > NOW()", [$token]) : null;
if (!$reset && !$done) $errors[] = 'This link is invalid or has expired.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $pass = $_POST['password'] ?? ''; $conf = $_POST['confirm_password'] ?? '';
    if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $conf)   $errors[] = 'Passwords do not match.';
    if (empty($errors)) {
        $user = DB::fetch("SELECT id FROM logistics_users WHERE email=? AND status='active'", [$reset['email']]);
        if ($user) {
            DB::update('logistics_users', ['password_hash'=>Auth::hash($pass),'temp_password'=>0], 'id=?', [$user['id']]);
            DB::update('password_resets', ['used'=>1], 'token=?', [$token]);
            $done = true;
        } else $errors[] = 'Account not found.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset Password — Logistics Portal</title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body>
<div class="auth-screen">
  <div class="auth-card" style="max-width:420px">
    <div class="auth-logo"><img src="<?= BASE_URL ?>/assets/images/logo.png" alt=""><h2>Set New Logistics Password</h2></div>
    <?php if ($done): ?>
      <div class="alert alert-success"><strong>✅ Password updated!</strong></div>
      <a href="<?= BASE_URL ?>/logistics/login.php" class="btn btn-ju btn-full" style="margin-top:12px">Sign In to Logistics Portal →</a>
    <?php elseif (!$reset): ?>
      <div class="alert alert-danger"><?= e($errors[0]??'Invalid link.') ?></div>
      <a href="<?= BASE_URL ?>/logistics/forgot-password.php" class="btn btn-ju btn-full" style="margin-top:12px">Request New Link</a>
    <?php else: ?>
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo "<div>• ".e($e)."</div>"; ?></div><?php endif; ?>
      <form method="POST">
        <div class="form-group"><label class="form-label">New Password *</label><input type="password" name="password" class="form-control" required minlength="8"></div>
        <div class="form-group"><label class="form-label">Confirm Password *</label><input type="password" name="confirm_password" class="form-control" required></div>
        <button type="submit" class="btn btn-ju btn-full btn-lg">Set New Password</button>
      </form>
    <?php endif; ?>
    <div style="text-align:center;margin-top:1rem;font-size:.82rem"><a href="<?= BASE_URL ?>/logistics/login.php" style="color:var(--text-muted)">← Back to login</a></div>
  </div>
</div>
</body>
</html>
