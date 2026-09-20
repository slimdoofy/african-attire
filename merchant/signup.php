<?php
/**
 * merchant/signup.php — New merchant account creation
 *
 * URL: /merchant/signup.php
 *
 * Creates a NEW user account with role=merchant, then redirects
 * to merchant/register.php to complete shop profile.
 * Distinct from customer registration at /customer/register.php.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/phone_countries.php';

// Already logged in
if (Auth::check()) {
    $role = Auth::role();
    if ($role === 'merchant') redirect(BASE_URL . '/merchant/');
    if ($role === 'admin')    redirect(BASE_URL . '/admin/');
    // Logged in as customer — send to join page to upgrade
    redirect(BASE_URL . '/merchant/join.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']     ?? '');
    $email = trim($_POST['email']    ?? '');
    $phoneCode = trim($_POST['phone_code']   ?? '+234');
    $phoneNum  = preg_replace('/\D/', '', trim($_POST['phone_number'] ?? ''));
    $phone     = $phoneCode . $phoneNum;
    $pass  =      $_POST['password'] ?? '';
    $conf  =      $_POST['confirm']  ?? '';

    if (strlen($name) < 2)
        $errors[] = 'Full name is required (min 2 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'A valid email address is required.';
    if (strlen($pass) < 8)
        $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $conf)
        $errors[] = 'Passwords do not match.';
    if (DB::count('SELECT COUNT(*) FROM users WHERE email=?', [$email]))
        $errors[] = 'An account with this email already exists. '
                  . '<a href="' . BASE_URL . '/merchant/login.php" '
                  . 'style="color:var(--ju);font-weight:600">Sign in →</a>';

    if (empty($errors)) {
        $uid = DB::insert('users', [
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'password_hash' => Auth::hash($pass),
            'role'          => 'merchant',
            'status'        => 'active',
            'email_verified'=> 0,
        ]);

        DB::insert('notifications', [
            'user_id' => $uid,
            'type'    => 'welcome',
            'title'   => 'Welcome to African Attire Merchant Portal! 🎉',
            'message' => 'Your merchant account is ready. Complete your shop profile to go live.',
            'link'    => BASE_URL . '/merchant/register.php',
        ]);

        // Send welcome email (non-blocking)
        sendWelcomeEmail($email, $name);

        // Log them in and send straight to shop registration
        $user = DB::fetch('SELECT * FROM users WHERE id=?', [$uid]);
        Auth::login($user);

        flash('Account created! Now set up your shop profile.', 'success');
        // Notify admins of new merchant signup
        sendNotification('notify_merchant_registration',
            '🏪 New Merchant Registration — ' . htmlspecialchars($name),
            '<h2>New Merchant Registration</h2><p>A new merchant has signed up and set up their shop for review.</p><div style="background:#f8f8f8;padding:12px;border-radius:8px;margin:12px 0"><div><strong>Name:</strong> '.htmlspecialchars($name).'</div><div><strong>Email:</strong> '.htmlspecialchars($email).'</div></div><a href="'.BASE_URL.'/admin/merchants.php" style="display:inline-block;padding:10px 20px;background:#1B6B3A;color:#fff;border-radius:6px;font-weight:700;text-decoration:none">Review Application →</a>',
            true
        );
        redirect(BASE_URL . '/merchant/register.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Create Merchant Account — <?= SITE_NAME ?></title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body style="background:var(--bg)">

<div class="auth-screen">
  <div class="auth-card" style="max-width:460px">

    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="African Attire"
           style="width:64px;height:64px;object-fit:contain;background:#fff;
                  border-radius:10px;padding:4px;box-shadow:var(--sh-sm)">
      <h2 style="margin-top:10px">Create Merchant Account</h2>
      <p style="color:var(--text-muted);font-size:.84rem">
        Step 1 of 2 — Your personal account details
      </p>
    </div>

    <!-- Progress indicator -->
    <div class="steps" style="margin-bottom:20px">
      <div class="step curr"><div class="step-circle">1</div><div class="step-label">Account</div></div>
      <div class="step"><div class="step-circle">2</div><div class="step-label">Shop Info</div></div>
      <div class="step"><div class="step-circle">3</div><div class="step-label">Bank</div></div>
      <div class="step"><div class="step-circle">4</div><div class="step-label">Live!</div></div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="margin-bottom:14px">
      <?php foreach ($errors as $e): ?>
      <div>• <?= $e /* may contain safe HTML link */ ?></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" required
                 placeholder="Amaka Osei"
                 value="<?= e($_POST['name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <div class="phone-group">
            <select name="phone_code" class="form-control phone-code-select">
              <?php foreach ($PHONE_CODES as [$code,$cname,$flag,$iso]): ?>
              <option value="<?= $code ?>"
                <?= (($_POST['phone_code'] ?? '+234')===$code && $cname==='Nigeria'?'selected':'') ?>>
                <?= $flag ?> <?= $code ?> <?= $cname ?>
              </option>
              <?php endforeach; ?>
            </select>
            <input type="tel" name="phone_number" class="form-control phone-number-input"
                   placeholder="8012345678" pattern="[0-9]{6,12}"
                   value="<?= e($_POST['phone_number'] ?? '') ?>">
          </div>
          <div class="form-hint">Country code + number without leading zero</div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input type="email" name="email" id="su-email" class="form-control" required
               placeholder="you@yourbusiness.com"
               value="<?= e($_POST['email'] ?? '') ?>"
               oninput="validateSuEmail(this)">
        <div id="su-email-check" style="font-size:.72rem;margin-top:4px;display:none"></div>
        <div class="form-hint">This will be your login email.</div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Password *</label>
          <div style="position:relative;display:flex;align-items:center"><input type="password" name="password" class="form-control"
                 placeholder="Min 8 characters" required> <button type="button" onclick="togglePw(this)" tabindex="-1" title="Show/hide password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);padding:0;line-height:1">👁</button></div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password *</label>
          <div style="position:relative;display:flex;align-items:center"><input type="password" name="confirm" class="form-control"
                 placeholder="Repeat password" required> <button type="button" onclick="togglePw(this)" tabindex="-1" title="Show/hide password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);padding:0;line-height:1">👁</button></div>
        </div>
      </div>

      <div class="form-check" style="margin-bottom:16px">
        <input type="checkbox" id="terms" required>
        <label for="terms" style="font-size:.82rem">
          I agree to the <a href="<?= BASE_URL ?>/merchant/terms.php" target="_blank" style="color:var(--ju)">Terms of Service</a>
          and <a href="<?= BASE_URL ?>/merchant/privacy.php" target="_blank" style="color:var(--ju)">Privacy Policy</a>
        </label>
      </div>

      <button type="submit" class="btn btn-ju btn-full btn-lg">
        Create Account &amp; Continue →
      </button>
    </form>

    <div style="text-align:center;font-size:.82rem;margin-top:14px">
      Already have a merchant account?
      <a href="<?= BASE_URL ?>/merchant/login.php"
         style="color:var(--ju);font-weight:600">Sign in →</a>
    </div>

    <div style="text-align:center;margin-top:8px;font-size:.78rem;color:var(--text-muted)">
      <a href="<?= BASE_URL ?>/merchant/join.php" style="color:var(--text-muted)">
        ← Back
      </a>
      &nbsp;·&nbsp;
      <a href="<?= BASE_URL ?>/" style="color:var(--text-muted)">Back to Store</a>
    </div>

  </div>
</div>

<style>
.phone-group{display:flex;gap:0}
.phone-code-select{width:130px;flex-shrink:0;border-radius:var(--r-sm) 0 0 var(--r-sm);border-right:none;font-size:.82rem}
.phone-number-input{flex:1;border-radius:0 var(--r-sm) var(--r-sm) 0}
</style>
<script>
function validateSuEmail(inp){
  var el=document.getElementById('su-email-check');
  var ok=/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(inp.value.trim());
  if(!inp.value.trim()){el.style.display='none';return;}
  el.textContent=ok?'✓ Email looks good':'✗ Enter a valid email address';
  el.style.color=ok?'var(--green)':'var(--red)';
  el.style.display='block';
}
</script>
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
