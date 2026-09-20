<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (Auth::check()) redirect(BASE_URL . '/');

$sent   = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $user = DB::fetch(
            "SELECT id, name, email FROM users WHERE email=? AND status != 'suspended'",
            [$email]
        );

        if ($user) {
            // Invalidate any existing unused tokens for this email
            DB::query(
                "DELETE FROM password_resets WHERE email=? AND portal='customer'",
                [$email]
            );

            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            DB::insert('password_resets', [
                'email'      => $email,
                'token'      => $token,
                'portal'     => 'customer',
                'expires_at' => $expires,
            ]);

            $resetUrl = BASE_URL . '/customer/reset-password.php?token=' . $token;
            sendPasswordResetEmail($email, $user['name'], $resetUrl, 'customer');
        }
        // Always show the same message — prevents email enumeration
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forgot Password — <?= SITE_NAME ?></title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body>
<div class="auth-screen">
  <div class="auth-card" style="max-width:420px">

    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo">
      <h2>Forgot Password?</h2>
      <p>Enter your email and we'll send you a reset link</p>
    </div>

    <?php if ($sent): ?>
    <div class="alert alert-success" style="margin-bottom:16px">
      <strong>✅ Check your inbox!</strong><br>
      If an account exists for <strong><?= e($_POST['email']) ?></strong>, a password
      reset link has been sent. It expires in <strong>1 hour</strong>.
      <div style="margin-top:6px;font-size:.8rem;color:var(--text-muted)">
        Didn't receive it? Check your spam folder or
        <a href="<?= BASE_URL ?>/customer/forgot-password.php" style="color:var(--ju)">try again</a>.
      </div>
    </div>
    <?php else: ?>
    <?php if ($errors): ?>
    <div class="alert alert-danger" style="margin-bottom:14px">
      <?php foreach ($errors as $e): ?><div>• <?= e($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input type="email" name="email" class="form-control" required autofocus
               placeholder="you@email.com"
               value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <button type="submit" class="btn btn-ju btn-full btn-lg">Send Reset Link</button>
    </form>
    <?php endif; ?>

    <div style="text-align:center;margin-top:1.2rem;font-size:.85rem">
      <a href="<?= BASE_URL ?>/customer/login.php" style="color:var(--text-muted)">← Back to Sign In</a>
    </div>
  </div>
</div>
</body>
</html>
