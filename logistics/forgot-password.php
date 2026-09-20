<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/auth.php';
$sent = false; $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $user = DB::fetch(
            "SELECT lu.id, lu.first_name, lu.last_name, lu.email
             FROM logistics_users lu
             JOIN logistics_companies lc ON lc.id=lu.company_id
             WHERE lu.email=? AND lu.status='active' AND lc.status='active'",
            [$email]
        );
        if ($user) {
            DB::query("DELETE FROM password_resets WHERE email=? AND portal='logistics'", [$email]);
            $token = bin2hex(random_bytes(32));
            DB::insert('password_resets', [
                'email' => $email, 'token' => $token, 'portal' => 'logistics',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            ]);
            $name = $user['first_name'] . ' ' . $user['last_name'];
            sendPasswordResetEmail($email, $name,
                BASE_URL . '/logistics/reset-password.php?token=' . $token, 'logistics');
        }
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forgot Password — Logistics Portal</title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body>
<div class="auth-screen">
  <div class="auth-card" style="max-width:420px">
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo">
      <h2>Logistics Portal — Forgot Password</h2>
      <p style="color:var(--text-muted);font-size:.84rem">Enter your logistics portal email for a reset link</p>
    </div>
    <?php if ($sent): ?>
    <div class="alert alert-success">
      <strong>✅ Check your inbox!</strong><br>
      If a logistics account exists for <strong><?= e($_POST['email']) ?></strong>, a reset link has been sent (expires in 1 hour).
    </div>
    <?php else: ?>
    <?php if ($errors): ?><div class="alert alert-danger"><?= e($errors[0]) ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group"><label class="form-label">Email Address *</label>
        <input type="email" name="email" class="form-control" required autofocus placeholder="you@logistics.com" value="<?= e($_POST['email']??'') ?>">
      </div>
      <button type="submit" class="btn btn-ju btn-full btn-lg">Send Reset Link</button>
    </form>
    <?php endif; ?>
    <div style="text-align:center;margin-top:1rem;font-size:.84rem">
      <a href="<?= BASE_URL ?>/logistics/login.php" style="color:var(--text-muted)">← Back to Logistics Login</a>
    </div>
  </div>
</div>
</body>
</html>
