<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Already logged in as admin → go to dashboard
if (Auth::check() && Auth::role() === 'admin') {
    redirect(BASE_URL . '/admin/');
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']    ?? '');
    $pass  =      $_POST['password'] ?? '';

    // Check super admin (users table, role=admin)
    $user = DB::fetch(
        "SELECT id, name, email, password_hash, role, status, NULL AS temp_password,
                NULL AS modules, 'super' AS admin_type
         FROM users WHERE email=? AND role='admin' AND status != 'suspended'",
        [$email]
    );

    // Check sub admin users (admin_users table)
    if (!$user) {
        $auRow = DB::fetch(
            "SELECT id, name, email, password_hash, 'admin' AS role, status,
                    temp_password, modules, 'sub' AS admin_type
             FROM admin_users WHERE email=? AND status='active'",
            [$email]
        );
        if ($auRow) $user = $auRow;
    }

    if ($user && Auth::verify($pass, $user['password_hash'])) {
        // Log last login for sub-admins
        if ($user['admin_type'] === 'sub') {
            DB::update('admin_users', ['last_login_at' => date('Y-m-d H:i:s')], 'id=?', [$user['id']]);
            // Store modules and temp_password flag in session
            Auth::start();
            $_SESSION['admin_modules']  = json_decode($user['modules'] ?? '[]', true) ?: [];
            $_SESSION['admin_temp_pw']  = !empty($user['temp_password']);
            $_SESSION['admin_type']     = 'sub';
            $_SESSION['admin_user_id']  = $user['id'];
        } else {
            Auth::start();
            $_SESSION['admin_modules']  = ['*']; // full access
            $_SESSION['admin_temp_pw']  = false;
            $_SESSION['admin_type']     = 'super';
        }
        Auth::login($user);
        auditLog('admin_login', 'Admin logged in (' . $user['admin_type'] . ')', 'user', $user['id'], 'admin');

        // Force temp password change
        if (!empty($_SESSION['admin_temp_pw'])) {
            redirect(BASE_URL . '/admin/change-password.php');
        }

        $next = $_GET['next'] ?? '';
        redirect($next ?: BASE_URL . '/admin/');
    } else {
        $err = 'Invalid email or password.';
        auditLog('admin_login_failed', "Failed login attempt for: $email", '', 0, 'admin');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login — <?= SITE_NAME ?></title>
  <link rel="icon"       href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body style="background:var(--bg)">
<div class="auth-screen">
  <div class="auth-card" style="max-width:420px">

    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="African Attire"
           style="width:70px;height:70px;border-radius:10px;background:#fff;
                  object-fit:contain;padding:4px;box-shadow:var(--sh-sm)">
      <h2 style="margin-top:10px;margin-bottom:4px">Admin Portal</h2>
      <p style="font-size:.82rem;color:var(--text-muted)">African Attire — Restricted Access</p>
    </div>

    <div style="text-align:center;margin-bottom:16px">
      <span style="display:inline-flex;align-items:center;gap:5px;background:var(--navy-pale);
                   border:1px solid var(--navy-pale2);border-radius:999px;padding:4px 14px;
                   font-size:.72rem;font-weight:700;color:var(--navy);letter-spacing:.05em">
        🔐 ADMIN PORTAL — AUTHORISED PERSONNEL ONLY
      </span>
    </div>

    <?php if ($err): ?>
    <div class="alert alert-danger" style="margin-bottom:14px"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">Admin Email</label>
        <input type="email" name="email" class="form-control" required autofocus
               placeholder="admin@shopafricanattire.com"
               value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <div style="position:relative;display:flex;align-items:center"><input type="password" name="password" class="form-control" required placeholder="••••••••"> <button type="button" onclick="togglePw(this)" tabindex="-1" title="Show/hide password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);padding:0;line-height:1">👁</button></div>
      </div>
      <button type="submit" class="btn btn-ju btn-full btn-lg">Sign In to Admin Portal</button>
    </form>

    <div style="text-align:center;font-size:.76rem;color:var(--text-muted);margin-top:16px;line-height:1.6">
      This portal is restricted to authorised administrators only.<br>
      Unauthorised access attempts are logged.
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
