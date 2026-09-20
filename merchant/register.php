<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/phone_countries.php';
Auth::requireRole('merchant');

// Already has a shop — go to dashboard
$existing = DB::fetch('SELECT id FROM shops WHERE user_id=?', [Auth::id()]);
if ($existing) redirect(BASE_URL . '/merchant/');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Business info fields ───────────────────────────────────
    $shopName    = trim($_POST['shop_name']          ?? '');
    $bizEmail    = trim($_POST['business_email']     ?? '');
    $bizPhoneCode = trim($_POST['biz_phone_code']   ?? '+234');
    $bizPhoneNum  = preg_replace('/\D/', '', trim($_POST['biz_phone_number'] ?? ''));
    $bizPhone     = $bizPhoneCode . $bizPhoneNum;
    $bizAddress  = trim($_POST['business_address']   ?? '');
    $city        = trim($_POST['city']               ?? '');
    $country     = trim($_POST['country']            ?? 'Nigeria');
    $desc        = trim($_POST['description']        ?? '');

    // ── Bank details ───────────────────────────────────────────
    $bank   = trim($_POST['bank_name']         ?? '');
    $acct   = trim($_POST['bank_account']      ?? '');
    $aname  = trim($_POST['bank_account_name'] ?? '');

    // ── Validation ─────────────────────────────────────────────
    if (strlen($shopName) < 2)
        $errors[] = 'Business name is required (min 2 characters).';
    if (!filter_var($bizEmail, FILTER_VALIDATE_EMAIL))
        $errors[] = 'A valid business email address is required.';
    if (strlen($bizPhoneNum) < 6)
        $errors[] = 'Business phone number is required.';
    if (strlen($bizAddress) < 5)
        $errors[] = 'Business address is required.';
    if (!$country)
        $errors[] = 'Country is required.';
    if (strlen($desc) < 10)
        $errors[] = 'Shop description is required (min 10 characters).';
    if (!$bank || !$acct || !$aname)
        $errors[] = 'All bank details are required for payouts.';
    if (DB::count('SELECT COUNT(*) FROM shops WHERE shop_name=?', [$shopName]))
        $errors[] = 'That business name is already taken. Please choose another.';

    if (empty($errors)) {
        $logo = '';
        if (!empty($_FILES['logo']['name'])) {
            $logo = uploadFile($_FILES['logo'], 'shops') ?? '';
        }

        $sid = DB::insert('shops', [
            'user_id'           => Auth::id(),
            'shop_name'         => $shopName,
            'slug'              => slug($shopName) . '-' . uniqid(),
            'description'       => $desc,
            'city'              => $city,
            'country'           => $country,
            'logo'              => $logo,
            'business_email'    => $bizEmail,
            'business_phone'    => $bizPhone,
            'business_address'  => $bizAddress,
            'bank_name'         => $bank,
            'bank_account'      => $acct,
            'bank_account_name' => $aname,
            'agent_id'          => trim($_POST['agent_id'] ?? '') ?: null,
            'status'            => 'pending',
        ]);

        DB::insert('notifications', [
            'user_id' => Auth::id(),
            'type'    => 'shop_submitted',
            'title'   => 'Shop application submitted! 🎉',
            'message' => "Your shop \"{$shopName}\" is under review. We'll notify you when approved.",
            'link'    => BASE_URL . '/merchant/',
        ]);

        // Send merchant onboarding email (non-blocking)
        $merchantUser = DB::fetch('SELECT email, name FROM users WHERE id=?', [Auth::id()]);
        if ($merchantUser) {
            sendMerchantWelcomeEmail($merchantUser['email'], $merchantUser['name'], $shopName);
        }

        flash('Application submitted! We\'ll review and approve within 24–48 hours.', 'success');
        redirect(BASE_URL . '/merchant/');
    }
}

$countries = [
    'Nigeria','Ghana','Kenya','South Africa','Ethiopia','Tanzania','Uganda',
    'Senegal','Cameroon','Côte d\'Ivoire','Mali','Burkina Faso','Niger',
    'Rwanda','Zambia','Zimbabwe','Mozambique','Angola','Benin','Togo',
    'Sierra Leone','Liberia','Gambia','Guinea','Democratic Republic of Congo',
    'United Kingdom','United States','Canada','France','Germany',
    'Netherlands','Italy','Spain','Belgium','Sweden','Norway','Other',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Merchant Registration — <?= SITE_NAME ?></title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body style="background:var(--bg)">

<!-- Simple header -->
<nav class="ju-header" style="position:relative">
  <div class="ju-header-inner">
    <a href="<?= BASE_URL ?>/" class="ju-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo"
           style="background:#fff;border-radius:4px;object-fit:contain">
      <span class="ju-logo-text">African Attire</span>
    </a>
    <div style="margin-left:auto">
      <a href="<?= BASE_URL ?>/" class="btn btn-ghost btn-sm"
         style="color:#fff;border-color:rgba(255,255,255,.4)">← Back to Store</a>
    </div>
  </div>
</nav>

<div style="max-width:660px;margin:0 auto;padding:24px 16px 48px">

  <!-- Page heading -->
  <div style="text-align:center;margin-bottom:24px">
    <div style="font-size:3rem;margin-bottom:8px">🏪</div>
    <h1 style="font-family:var(--ff-head);font-size:1.55rem;color:var(--black);margin-bottom:6px">
      Become a Merchant
    </h1>
    <p style="color:var(--text-muted);font-size:.88rem;max-width:400px;margin:0 auto">
      Set up your digital storefront and start selling authentic African fashion globally.
    </p>
  </div>

  <!-- Steps -->
  <div class="steps" style="margin-bottom:24px">
    <div class="step curr"><div class="step-circle">1</div><div class="step-label">Business Info</div></div>
    <div class="step"><div class="step-circle">2</div><div class="step-label">Bank Details</div></div>
    <div class="step"><div class="step-circle">3</div><div class="step-label">Review</div></div>
    <div class="step"><div class="step-circle">4</div><div class="step-label">Go Live!</div></div>
  </div>

  <!-- Errors -->
  <?php if (!empty($errors)): ?>
  <div class="alert alert-danger" style="margin-bottom:16px">
    <?php foreach ($errors as $e): ?>
    <div>• <?= e($e) ?></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">

    <!-- ── SECTION 1: Business Details ────────────────────────── -->
    <div class="card" style="margin-bottom:14px;border-top:3px solid var(--ju)">
      <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;
                  color:var(--ju);margin-bottom:16px">
        🏪 Business Details
      </div>

      <div class="form-group">
        <label class="form-label">Business / Brand Name *</label>
        <input type="text" name="shop_name" class="form-control" required
               placeholder="e.g. Ade's Ankara Studio"
               value="<?= e($_POST['shop_name'] ?? '') ?>">
        <div class="form-hint">This is your public shop name visible to customers.</div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Business Email Address *</label>
          <input type="email" name="business_email" id="biz-email" class="form-control" required
                 placeholder="orders@yourbrand.com"
                 value="<?= e($_POST['business_email'] ?? '') ?>"
                 oninput="validateBizEmail(this)">
          <div id="biz-email-check" style="font-size:.72rem;margin-top:4px;display:none"></div>
          <div class="form-hint">Used for order notifications.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Business Phone Number *</label>
          <div class="phone-group">
            <select name="biz_phone_code" class="form-control phone-code-select" required>
              <?php foreach ($PHONE_CODES as [$code,$cname,$flag,$iso]): ?>
              <option value="<?= $code ?>"
                <?= (($_POST['biz_phone_code'] ?? '+234')===$code && $cname==='Nigeria'?'selected':(($_POST['biz_phone_code'] ?? '')===$code?'selected':'')) ?>>
                <?= $flag ?> <?= $code ?> <?= $cname ?>
              </option>
              <?php endforeach; ?>
            </select>
            <input type="tel" name="biz_phone_number" class="form-control phone-number-input"
                   placeholder="8012345678" required pattern="[0-9]{6,12}"
                   title="Digits only, 6–12 digits"
                   value="<?= e(preg_replace('/^\+\d+/', '', $_POST['biz_phone_number'] ?? '')) ?>">
          </div>
          <div class="form-hint">Country code + number without leading zero</div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Business Address *</label>
        <textarea name="business_address" class="form-control" rows="2" required
                  placeholder="Street address, Area, City"><?= e($_POST['business_address'] ?? '') ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">City *</label>
          <input type="text" name="city" class="form-control" required
                 placeholder="Lagos"
                 value="<?= e($_POST['city'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Country *</label>
          <select name="country" class="form-control" required id="merch-country">
            <option value="" disabled <?= !isset($_POST['country']) ? 'selected' : '' ?>>— Select your country —</option>
            <?php foreach ($COUNTRIES as $c): ?>
            <option value="<?= e($c) ?>" <?= (($_POST['country'] ?? 'Nigeria') === $c) ? 'selected' : '' ?>>
              <?= e($c) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">
          Shop Description *
          <span style="font-weight:400;color:var(--text-muted)">(min 10 characters)</span>
        </label>
        <textarea name="description" class="form-control" rows="4" required
                  placeholder="Tell buyers about your craft, specialties, experience, and what makes your fashion unique…"><?= e($_POST['description'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Shop Logo <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
        <div class="upload-zone" onclick="document.getElementById('logo-inp').click()"
             style="padding:16px;cursor:pointer">
          <div style="font-size:1.8rem;margin-bottom:5px">🖼</div>
          <div style="font-size:.82rem;color:var(--text-muted)">
            Click to upload logo · JPG or PNG · max 5MB
          </div>
          <input type="file" id="logo-inp" name="logo" accept="image/*"
                 style="display:none" onchange="previewLogo(this)">
        </div>
        <img id="logo-prev"
             style="display:none;width:80px;height:80px;object-fit:cover;
                    border-radius:50%;margin-top:10px;border:3px solid var(--ju)">
      </div>
    </div>

    <!-- ── SECTION 2: Bank / Payout Details ───────────────────── -->
    <div class="card" style="margin-bottom:14px;border-top:3px solid var(--blue)">
      <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;
                  color:var(--blue);margin-bottom:16px">
        🏦 Bank / Payout Details
      </div>
      <div class="alert alert-info" style="margin-bottom:16px;font-size:.82rem">
        Your earnings are paid out to this account after the platform commission of
        <strong><?= getSetting('platform_commission', '20') ?>%</strong> is deducted.
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Bank Name *</label>
          <select name="bank_name" class="form-control" required>
            <option value="">Select Bank</option>
            <?php foreach ([
              'Access Bank','Ecobank','FCMB','Fidelity Bank','First Bank','GTBank',
              'Jaiz Bank','Keystone Bank','Kuda Bank','Opay','Palmpay','Polaris Bank',
              'Stanbic IBTC','Sterling Bank','UBA','Union Bank','Unity Bank',
              'VFD Microfinance Bank','Wema Bank','Zenith Bank',
            ] as $b): ?>
            <option value="<?= e($b) ?>"
              <?= (($_POST['bank_name'] ?? '') === $b) ? 'selected' : '' ?>>
              <?= e($b) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Account Number *</label>
          <input type="text" name="bank_account" class="form-control"
                 placeholder="0123456789" maxlength="10" required
                 pattern="[0-9]{10}" title="Enter a 10-digit account number"
                 value="<?= e($_POST['bank_account'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Account Name *</label>
        <input type="text" name="bank_account_name" class="form-control"
               placeholder="Name exactly as on your bank statement" required
               value="<?= e($_POST['bank_account_name'] ?? '') ?>">
      </div>
    </div>

    <!-- ── AGENT ID ────────────────────────────────────────────── -->
    <div class="card" style="margin-bottom:14px">
      <div class="card-title" style="margin-bottom:14px">🔗 Referral / Agent Information</div>
      <div class="form-group">
        <label class="form-label">
          Agent's ID
          <span style="font-weight:400;color:var(--text-muted)">(optional — enter if you were referred by an agent)</span>
        </label>
        <input type="text" name="agent_id" class="form-control"
               style="max-width:260px"
               placeholder="e.g. AGT-001234"
               value="<?= e($_POST['agent_id'] ?? '') ?>">
        <div class="form-hint">
          If a sales agent referred you to African Attire, enter their Agent ID here.
          Leave blank if not applicable.
        </div>
      </div>
    </div>

    <!-- ── TERMS & SUBMIT ──────────────────────────────────────── -->
    <div class="card" style="margin-bottom:14px">
      <div class="form-check" style="margin-bottom:14px">
        <input type="checkbox" required id="terms-chk">
        <label for="terms-chk" style="font-size:.84rem;color:var(--text)">
          I agree to the <a href="<?= BASE_URL ?>/merchant/terms.php" target="_blank" style="color:var(--ju);font-weight:600">Merchant Terms of Service</a>
          and understand that a
          <strong><?= getSetting('platform_commission', '20') ?>% platform commission</strong>
          applies to each sale.
        </label>
      </div>

      <div style="display:flex;gap:10px">
        <a href="<?= BASE_URL ?>/" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-ju btn-lg" style="flex:1;font-size:.95rem">
          Submit Application →
        </button>
      </div>
      <p style="text-align:center;font-size:.76rem;color:var(--text-muted);margin-top:10px">
        Applications are reviewed within 24–48 hours. You'll receive a notification when approved.
      </p>
    </div>

  </form>
</div>

<script>
function previewLogo(input) {
  var file = input.files[0];
  if (!file) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    var img = document.getElementById('logo-prev');
    img.src = e.target.result;
    img.style.display = 'block';
  };
  reader.readAsDataURL(file);
}
</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<style>
.phone-group{display:flex;gap:0}
.phone-code-select{width:130px;flex-shrink:0;border-radius:var(--r-sm) 0 0 var(--r-sm);border-right:none;font-size:.82rem}
.phone-number-input{flex:1;border-radius:0 var(--r-sm) var(--r-sm) 0}
</style>
<script>
function validateBizEmail(inp) {
  var el=document.getElementById('biz-email-check');
  var ok=/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(inp.value.trim());
  if (!inp.value.trim()) { el.style.display='none'; return; }
  el.textContent=ok?'✓ Email looks good':'✗ Enter a valid email address';
  el.style.color=ok?'var(--green)':'var(--red)';
  el.style.display='block';
}
(function(){
  var sel=document.getElementById('merch-country');
  if (sel && !sel.value) {
    for (var i=0;i<sel.options.length;i++){
      if(sel.options[i].value==='Nigeria'){sel.selectedIndex=i;break;}
    }
  }
})();
</script>
</body>
</html>
