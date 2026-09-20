<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/phone_countries.php';
if (Auth::check()) redirect(BASE_URL . '/');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name']         ?? '');
    $email     = trim($_POST['email']        ?? '');
    $phoneCode = trim($_POST['phone_code']   ?? '+234');
    $phoneNum  = preg_replace('/\D/', '', trim($_POST['phone_number'] ?? ''));
    $phone     = $phoneCode . $phoneNum;
    $country   = trim($_POST['country']      ?? '');
    $pass      = $_POST['password']          ?? '';
    $conf      = $_POST['confirm']           ?? '';

    if (strlen($name) < 2)
        $errors[] = 'Name must be at least 2 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'A valid email address is required.';
    if (!$country)
        $errors[] = 'Please select your country.';
    if (strlen($pass) < 8)
        $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $conf)
        $errors[] = 'Passwords do not match.';
    if (DB::count('SELECT COUNT(*) FROM users WHERE email=?', [$email]))
        $errors[] = 'This email address is already registered.';

    if (empty($errors)) {
        $uid = DB::insert('users', [
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'password_hash' => Auth::hash($pass),
            'role'          => 'customer',
            'status'        => 'active',
        ]);
        DB::insert('customer_profiles', [
            'user_id' => $uid,
            'country' => $country,
        ]);
        DB::insert('notifications', [
            'user_id' => $uid, 'type' => 'welcome',
            'title'   => 'Welcome to African Attire! 🎉',
            'message' => 'Your account is ready. Start exploring authentic African fashion.',
            'link'    => BASE_URL . '/customer/shop.php',
        ]);
        $user = DB::fetch('SELECT * FROM users WHERE id=?', [$uid]);
        Auth::login($user);
        sendWelcomeEmail($email, $name);
        flash('Welcome to African Attire! 🎉 Start shopping.', 'success');
        redirect(BASE_URL . '/');
    }
}

$selCode    = $_POST['phone_code'] ?? '+234';
$selCountry = $_POST['country']    ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Create Account — <?= SITE_NAME ?></title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <style>
    /* Phone input group */
    .phone-group{display:flex;gap:0}
    .phone-code-select{
      width:130px;flex-shrink:0;
      border-radius:var(--r-sm) 0 0 var(--r-sm);
      border-right:none;
      font-size:.82rem;
    }
    .phone-number-input{
      flex:1;
      border-radius:0 var(--r-sm) var(--r-sm) 0;
    }
    /* Email validity indicator */
    #email-check{
      font-size:.72rem;margin-top:4px;display:none;
    }
    #email-check.valid  {color:var(--green)}
    #email-check.invalid{color:var(--red)}
  </style>
</head>
<body>
<div class="auth-screen">
  <div class="auth-card" style="max-width:520px">

    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo">
      <h2>Create Your Account</h2>
      <p>Join Africa's fashion marketplace</p>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?><div>• <?= e($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="reg-form" novalidate>

      <!-- Name -->
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" class="form-control" required
               minlength="2" placeholder="Amaka Osei"
               value="<?= e($_POST['name'] ?? '') ?>">
      </div>

      <!-- Email with live validation -->
      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input type="email" name="email" id="email-input" class="form-control"
               required placeholder="you@email.com"
               value="<?= e($_POST['email'] ?? '') ?>"
               oninput="validateEmail(this)">
        <div id="email-check"></div>
      </div>

      <!-- Phone: country code + number -->
      <div class="form-group">
        <label class="form-label">Phone Number *</label>
        <div class="phone-group">
          <select name="phone_code" class="form-control phone-code-select" required>
            <?php foreach ($PHONE_CODES as [$code, $name, $flag, $iso]): ?>
            <option value="<?= $code ?>"
              <?= $selCode === $code && $name === 'Nigeria' ? 'selected' : ($selCode === $code && $name !== 'Nigeria' ? 'selected' : '') ?>>
              <?= $flag ?> <?= $code ?> <?= $name ?>
            </option>
            <?php endforeach; ?>
          </select>
          <input type="tel" name="phone_number" class="form-control phone-number-input"
                 placeholder="8012345678" required
                 pattern="[0-9]{6,12}"
                 title="Enter digits only — 6 to 12 digits"
                 value="<?= e(preg_replace('/^\+\d+/', '', $_POST['phone_number'] ?? '')) ?>">
        </div>
        <div class="form-hint">Select your country code, then enter the number without the leading zero.</div>
      </div>

      <!-- Country -->
      <div class="form-group">
        <label class="form-label">Country *</label>
        <select name="country" class="form-control" required
                id="country-select">
          <option value="" disabled <?= !$selCountry ? 'selected' : '' ?>>— Select your country —</option>
          <?php foreach ($COUNTRIES as $c): ?>
          <option value="<?= e($c) ?>" <?= $selCountry === $c ? 'selected' : '' ?>>
            <?= e($c) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Passwords -->
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Password *</label>
          <div style="position:relative;display:flex;align-items:center"><input type="password" name="password" id="pass1" class="form-control"
                 required minlength="8" placeholder="Min. 8 characters"
                 oninput="checkPassMatch()"> <button type="button" onclick="togglePw(this)" tabindex="-1" title="Show/hide password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);padding:0;line-height:1">👁</button></div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password *</label>
          <div style="position:relative;display:flex;align-items:center"><input type="password" name="confirm" id="pass2" class="form-control"
                 required placeholder="Repeat password"
                 oninput="checkPassMatch()"> <button type="button" onclick="togglePw(this)" tabindex="-1" title="Show/hide password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);padding:0;line-height:1">👁</button></div>
          <div id="pass-match" style="font-size:.72rem;margin-top:4px;display:none"></div>
        </div>
      </div>

      <!-- Terms -->
      <div class="form-group">
        <label class="form-check">
          <input type="checkbox" required id="terms-chk">
          I agree to the
          <a href="<?= BASE_URL ?>/terms.php" target="_blank" style="color:var(--ju)">Terms &amp; Conditions</a>
          and
          <a href="<?= BASE_URL ?>/privacy.php" target="_blank" style="color:var(--ju)">Privacy Policy</a>
        </label>
      </div>

      <button type="submit" class="btn btn-ju btn-full btn-lg" id="submit-btn">
        Create Account →
      </button>
    </form>

    <div style="text-align:center;font-size:.875rem;margin-top:1.2rem">
      Already have an account?
      <a href="<?= BASE_URL ?>/customer/login.php" style="color:var(--ju);font-weight:600">Sign in →</a>
    </div>
    <div style="text-align:center;font-size:.8rem;color:var(--text-muted);margin-top:.6rem">
      Want to sell?
      <a href="<?= BASE_URL ?>/merchant/join.php" style="color:var(--ju);font-weight:600">Open a Merchant Account →</a>
    </div>
    <div style="text-align:center;margin-top:.75rem">
      <a href="<?= BASE_URL ?>/" style="font-size:.8rem;color:var(--text-muted)">← Back to store</a>
    </div>
  </div>
</div>

<script>
// ── Email live validation ─────────────────────────────────────
function validateEmail(inp) {
  var el  = document.getElementById('email-check');
  var val = inp.value.trim();
  if (!val) { el.style.display='none'; return; }
  var ok  = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val);
  el.textContent  = ok ? '✓ Email looks good' : '✗ Please enter a valid email address';
  el.className    = ok ? 'valid' : 'invalid';
  el.style.display= 'block';
}

// ── Password match indicator ──────────────────────────────────
function checkPassMatch() {
  var p1  = document.getElementById('pass1').value;
  var p2  = document.getElementById('pass2').value;
  var el  = document.getElementById('pass-match');
  if (!p2) { el.style.display='none'; return; }
  var ok  = p1 === p2;
  el.textContent  = ok ? '✓ Passwords match' : '✗ Passwords do not match';
  el.style.color  = ok ? 'var(--green)' : 'var(--red)';
  el.style.display= 'block';
}

// ── Client-side form validation on submit ─────────────────────
document.getElementById('reg-form').addEventListener('submit', function(e) {
  var email = document.getElementById('email-input').value.trim();
  var ok    = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email);
  if (!ok) {
    e.preventDefault();
    document.getElementById('email-input').focus();
    document.getElementById('email-check').textContent  = '✗ Please enter a valid email address';
    document.getElementById('email-check').className    = 'invalid';
    document.getElementById('email-check').style.display= 'block';
    return;
  }
  var p1 = document.getElementById('pass1').value;
  var p2 = document.getElementById('pass2').value;
  if (p1 !== p2) {
    e.preventDefault();
    document.getElementById('pass2').focus();
    var pm = document.getElementById('pass-match');
    pm.textContent='✗ Passwords do not match'; pm.style.color='var(--red)'; pm.style.display='block';
  }
});

// ── Auto-select Nigeria by default ───────────────────────────
(function(){
  var sel = document.getElementById('country-select');
  if (sel && !sel.value) {
    for (var i=0; i<sel.options.length; i++) {
      if (sel.options[i].value === 'Nigeria') { sel.selectedIndex=i; break; }
    }
  }
})();
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
