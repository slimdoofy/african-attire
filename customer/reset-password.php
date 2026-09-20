<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (Auth::check()) redirect(BASE_URL . '/');

$token  = trim($_GET['token'] ?? '');
$errors = [];
$done   = false;

// Validate token immediately
$reset = null;
if ($token) {
    $reset = DB::fetch(
        "SELECT * FROM password_resets
         WHERE token=? AND portal='customer' AND used=0 AND expires_at > NOW()",
        [$token]
    );
}

if (!$token || !$reset) {
    $errors[] = 'This password reset link is invalid or has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $pass = $_POST['password']         ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    if (strlen($pass) < 8)  $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $conf)    $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $user = DB::fetch('SELECT id FROM users WHERE email=?', [$reset['email']]);
        if ($user) {
            DB::update('users',
                ['password_hash' => Auth::hash($pass)],
                'id=?', [$user['id']]
            );
            DB::update('password_resets', ['used' => 1], 'token=?', [$token]);
            $done = true;
        } else {
            $errors[] = 'Account not found. Please contact support.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset Password — <?= SITE_NAME ?></title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <style>
    #pass-match{font-size:.72rem;margin-top:4px;display:none}
  </style>
</head>
<body>
<div class="auth-screen">
  <div class="auth-card" style="max-width:420px">
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo">
      <h2>Set New Password</h2>
    </div>

    <?php if ($done): ?>
    <div class="alert alert-success" style="margin-bottom:16px">
      <strong>✅ Password updated!</strong><br>
      Your password has been changed. You can now sign in with your new password.
    </div>
    <a href="<?= BASE_URL ?>/customer/login.php" class="btn btn-ju btn-full btn-lg">Sign In →</a>

    <?php elseif (!$reset): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $e) echo e($e); ?></div>
    <a href="<?= BASE_URL ?>/customer/forgot-password.php"
       class="btn btn-ju btn-full" style="margin-top:12px">Request New Link →</a>

    <?php else: ?>
    <?php if ($errors): ?>
    <div class="alert alert-danger" style="margin-bottom:14px">
      <?php foreach ($errors as $e): ?><div>• <?= e($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:16px">
      Resetting password for: <strong><?= e($reset['email']) ?></strong>
    </p>
    <form method="POST">
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="form-group">
        <label class="form-label">New Password *</label>
        <input type="password" name="password" class="form-control" required
               minlength="8" placeholder="Min. 8 characters"
               oninput="checkMatch()">
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password *</label>
        <input type="password" name="confirm_password" id="conf" class="form-control"
               required placeholder="Repeat new password"
               oninput="checkMatch()">
        <div id="pass-match"></div>
      </div>
      <button type="submit" class="btn btn-ju btn-full btn-lg">Set New Password</button>
    </form>
    <?php endif; ?>

    <div style="text-align:center;margin-top:1rem;font-size:.84rem">
      <a href="<?= BASE_URL ?>/customer/login.php" style="color:var(--text-muted)">← Back to Sign In</a>
    </div>
  </div>
</div>
<script>
function checkMatch(){
  var p1=document.querySelector('input[name="password"]').value;
  var p2=document.getElementById('conf').value;
  var el=document.getElementById('pass-match');
  if(!p2){el.style.display='none';return;}
  var ok=p1===p2;
  el.textContent=ok?'✓ Passwords match':'✗ Passwords do not match';
  el.style.color=ok?'var(--green)':'var(--red)';
  el.style.display='block';
}
</script>
</body>
</html>
