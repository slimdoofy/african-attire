<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/auth.php';

if (logCheck()) redirect(BASE_URL . '/logistics/');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']    ?? '');
    $pass  =      $_POST['password'] ?? '';

    $user = DB::fetch(
        "SELECT lu.*, lc.company_name FROM logistics_users lu
         JOIN logistics_companies lc ON lc.id=lu.company_id
         WHERE lu.email=? AND lu.status='active' AND lc.status='active'",
        [$email]
    );
    if ($user && password_verify($pass, $user['password_hash'])) {
        logLogin($user);
        DB::update('logistics_users', ['last_login_at' => date('Y-m-d H:i:s')], 'id=?', [$user['id']]);
        if ($user['temp_password']) {
            redirect(BASE_URL . '/logistics/change-password.php');
        }
        redirect(BASE_URL . '/logistics/');
    }
    $err = 'Invalid email or password, or account is inactive.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Logistics Portal Sign In — African Attire</title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body style="background:var(--bg)">
<div class="auth-screen">
  <div class="auth-card" style="max-width:420px">
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="African Attire"
           style="width:64px;height:64px;border-radius:10px;background:#fff;object-fit:contain;padding:4px;box-shadow:var(--sh-sm)">
      <h2 style="margin-top:10px">Logistics Portal</h2>
      <p style="color:var(--text-muted);font-size:.84rem">Sign in to manage your deliveries</p>
    </div>
    <div style="text-align:center;margin-bottom:16px">
      <span style="display:inline-flex;align-items:center;gap:5px;background:linear-gradient(135deg,var(--blue-pale),var(--green-pale));border:1px solid var(--blue-pale2);border-radius:999px;padding:4px 14px;font-size:.72rem;font-weight:700;color:var(--blue);letter-spacing:.05em">
        🚚 LOGISTICS PARTNER PORTAL
      </span>
    </div>
    <?php if ($err): ?>
    <div class="alert alert-danger" style="margin-bottom:14px"><?= e($err) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" required autofocus
               placeholder="you@company.com" value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-ju btn-full btn-lg">Sign In to Logistics Portal</button>
    </form>
    <div style="text-align:center;font-size:.78rem;color:var(--text-muted);margin-top:16px">
      Access is by invitation only · Contact
      <a href="mailto:<?= getSetting('site_email','hello@shopafricanattire.com') ?>"
         style="color:var(--ju)"><?= getSetting('site_email','hello@shopafricanattire.com') ?></a>
      for access.
    </div>
    <div style="text-align:center;margin-top:10px">
      <a href="<?= BASE_URL ?>/logistics/forgot-password.php" style="font-size:.78rem;color:var(--text-muted)">
        Forgot password? Get a reset link
      </a>
    </div>
  </div>
</div>
</body>
</html>
