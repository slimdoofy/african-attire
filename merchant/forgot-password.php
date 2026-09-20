<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$sent = false; $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $user = DB::fetch(
            "SELECT id, name, email FROM users WHERE email=? AND role='merchant' AND status != 'suspended'",
            [$email]
        );
        if ($user) {
            DB::query("DELETE FROM password_resets WHERE email=? AND portal='merchant'", [$email]);
            $token = bin2hex(random_bytes(32));
            DB::insert('password_resets', [
                'email' => $email, 'token' => $token, 'portal' => 'merchant',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            ]);
            sendPasswordResetEmail($email, $user['name'],
                BASE_URL . '/merchant/reset-password.php?token=' . $token, 'merchant');
        }
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forgot Password — Merchant Portal</title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body>
<div class="auth-screen">
  <div class="auth-card" style="max-width:420px">
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo">
      <h2>Merchant — Forgot Password</h2>
      <p style="color:var(--text-muted);font-size:.84rem">Enter your merchant email for a reset link</p>
    </div>
    <?php if ($sent): ?>
    <div class="alert alert-success">
      <strong>✅ Check your inbox!</strong><br>
      If a merchant account exists for <strong><?= e($_POST['email']) ?></strong>, a reset link has been sent (expires in 1 hour). Check spam if not received.
    </div>
    <?php else: ?>
    <?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo e($e); ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group"><label class="form-label">Merchant Email *</label>
        <input type="email" name="email" class="form-control" required autofocus placeholder="you@yourbusiness.com" value="<?= e($_POST['email']??'') ?>">
      </div>
      <button type="submit" class="btn btn-ju btn-full btn-lg">Send Reset Link</button>
    </form>
    <?php endif; ?>
    <div style="text-align:center;margin-top:1rem;font-size:.84rem">
      <a href="<?= BASE_URL ?>/merchant/login.php" style="color:var(--text-muted)">← Back to Merchant Login</a>
    </div>
  </div>
</div>
</body>
</html>
