<?php
/**
 * merchant/login.php — Dedicated merchant login page
 *
 * URL: /merchant/login.php
 *
 * Distinct from customer login. Only accepts merchant and admin roles.
 * Redirects approved merchants to their dashboard.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

// Already logged in as merchant
if (Auth::check() && Auth::role() === 'merchant') {
    redirect(BASE_URL . '/merchant/');
}
if (Auth::check() && Auth::role() === 'admin') {
    redirect(BASE_URL . '/admin/');
}

$err  = '';
$next = trim($_GET['next'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']    ?? '');
    $pass  =      $_POST['password'] ?? '';

    $user = DB::fetch(
        'SELECT * FROM users WHERE email=? AND status != "suspended"',
        [$email]
    );

    if ($user && Auth::verify($pass, $user['password_hash'])) {
        if (!in_array($user['role'], ['merchant', 'admin'])) {
            // Valid customer account — tell them to use the right portal
            $err = 'This is the Merchant Portal. '
                 . '<a href="' . BASE_URL . '/customer/login.php" style="color:var(--ju);font-weight:600">'
                 . 'Sign in to your customer account here →</a>';
        } else {
            Auth::login($user);
            if ($user['role'] === 'admin') redirect(BASE_URL . '/admin/');
            // Check if merchant has a shop
            $shop = DB::fetch('SELECT id, status FROM shops WHERE user_id=?', [$user['id']]);
            if (!$shop) {
                redirect(BASE_URL . '/merchant/register.php');
            }
            redirect($next ?: BASE_URL . '/merchant/');
        }
    } else {
        $err = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Merchant Sign In — <?= SITE_NAME ?></title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body style="background:var(--bg)">

<div class="auth-screen">
  <div class="auth-card" style="max-width:420px">

    <!-- Merchant-branded logo block -->
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="African Attire"
           style="width:64px;height:64px;object-fit:contain;background:#fff;
                  border-radius:10px;padding:4px;box-shadow:var(--sh-sm)">
      <h2 style="margin-top:10px">Merchant Portal</h2>
      <p style="color:var(--text-muted);font-size:.84rem">
        Sign in to manage your shop on African Attire
      </p>
    </div>

    <!-- Merchant portal badge -->
    <div style="text-align:center;margin-bottom:16px">
      <span style="display:inline-flex;align-items:center;gap:5px;
                   background:linear-gradient(135deg,var(--blue-pale),var(--green-pale));
                   border:1px solid var(--blue-pale2);border-radius:999px;
                   padding:4px 14px;font-size:.72rem;font-weight:700;
                   color:var(--blue);letter-spacing:.05em">
        🏪 MERCHANT PORTAL
      </span>
    </div>

    <?php if ($err): ?>
    <div class="alert alert-danger" style="margin-bottom:14px">
      <?= $err /* already escaped or is safe HTML */ ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <?php if ($next): ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" required
               placeholder="you@yourbusiness.com"
               value="<?= e($_POST['email'] ?? '') ?>" autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <div style="position:relative;display:flex;align-items:center"><input type="password" name="password" class="form-control"
               placeholder="••••••••" required> <button type="button" onclick="togglePw(this)" tabindex="-1" title="Show/hide password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);padding:0;line-height:1">👁</button></div>
      </div>
      <div style="text-align:right;margin-bottom:14px;font-size:.8rem">
        <a href="<?= BASE_URL ?>/merchant/forgot-password.php"
           style="color:var(--text-muted)">Forgot password?</a>
      </div>

      <button type="submit" class="btn btn-ju btn-full btn-lg">
        Sign In to Merchant Portal
      </button>
    </form>

    <div class="auth-divider">or</div>

    <div style="text-align:center;font-size:.86rem;margin-bottom:10px">
      New merchant?
      <a href="<?= BASE_URL ?>/merchant/join.php"
         style="color:var(--ju);font-weight:700">Apply to sell →</a>
    </div>

    <div style="text-align:center;font-size:.78rem;color:var(--text-muted)">
      Customer account?
      <a href="<?= BASE_URL ?>/customer/login.php"
         style="color:var(--text-muted);text-decoration:underline">Sign in here</a>
    </div>

    <div style="text-align:center;margin-top:14px">
      <a href="<?= BASE_URL ?>/"
         style="font-size:.78rem;color:var(--text-muted)">← Back to store</a>
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
</body>
</html>
