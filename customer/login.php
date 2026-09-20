<?php
// customer/login.php
require_once __DIR__ . '/../includes/bootstrap.php';
if (Auth::check()) redirect(BASE_URL . '/');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $user  = DB::fetch('SELECT * FROM users WHERE email=? AND status != "suspended"', [$email]);
    if ($user && Auth::verify($pass, $user['password_hash'])) {
        Auth::login($user);
        $next = $_GET['next'] ?? '';
        if ($user['role'] === 'admin')    redirect(BASE_URL . '/admin/');
        if ($user['role'] === 'merchant') redirect(BASE_URL . '/merchant/');
        redirect($next ?: BASE_URL . '/');
    } else {
        $err = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sign In — <?= SITE_NAME ?></title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head><body>
<div class="auth-screen">
  <div class="auth-card">
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo">
      <h2>Welcome Back</h2>
      <p>Sign in to your African Attire account</p>
    </div>
    <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@email.com" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <div style="position:relative;display:flex;align-items:center"><input type="password" name="password" class="form-control" placeholder="••••••••" required> <button type="button" onclick="togglePw(this)" tabindex="-1" title="Show/hide password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);padding:0;line-height:1">👁</button></div>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;font-size:.82rem">
        <label class="form-check"><input type="checkbox" name="remember"> Remember me</label>
        <a href="<?= BASE_URL ?>/customer/forgot-password.php">Forgot password?</a>
      </div>
      <button type="submit" class="btn btn-ju btn-full btn-lg">Sign In</button>
    </form>
    <div class="auth-divider">or</div>
    <div style="text-align:center;font-size:.875rem">
      No account? <a href="<?= BASE_URL ?>/customer/register.php" style="color:var(--ju);font-weight:600">Register free →</a>
    </div>
    <div style="text-align:center;font-size:.78rem;color:var(--cream-3);margin-top:.6rem">
      Want to sell? <a href="<?= BASE_URL ?>/merchant/join.php" style="color:var(--ju);font-weight:600">Open a Merchant Account →</a>
    </div>
    <div style="text-align:center;margin-top:1.25rem">
      <a href="<?= BASE_URL ?>/" style="font-size:.8rem;color:var(--text-muted)">← Back to store</a>
    </div>
  </div>
</div>
<script>
function togglePw(btn) {
  var inp = btn.previousElementSibling;
  var showing = inp.type === 'text';
  inp.type = showing ? 'password' : 'text';
  btn.innerHTML = showing ? '&#128065;' : '&#128584;';
}
</script>
</body></html>
