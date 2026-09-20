<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/auth.php';
logRequire();

$la  = logAuth();
$cid = $la['company_id'];
$co  = DB::fetch('SELECT * FROM logistics_companies WHERE id=?', [$cid]);

// Only company admin can edit bank details
if ($la['role'] !== 'admin') {
    flash('Only company admins can manage bank account details.', 'error');
    redirect(BASE_URL . '/logistics/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bankName   = trim($_POST['bank_name']         ?? '');
    $bankAcct   = trim($_POST['bank_account']       ?? '');
    $acctName   = trim($_POST['bank_account_name']  ?? '');
    $sortCode   = trim($_POST['bank_sort_code']     ?? '');

    $errors = [];
    if (!$bankName) $errors[] = 'Bank name is required.';
    if (!$bankAcct) $errors[] = 'Account number is required.';
    if (!$acctName) $errors[] = 'Account name is required.';
    if (!preg_match('/^\d{10,}$/', $bankAcct))
        $errors[] = 'Account number must be at least 10 digits.';

    if (empty($errors)) {
        DB::update('logistics_companies', [
            'bank_name'         => $bankName,
            'bank_account'      => $bankAcct,
            'bank_account_name' => $acctName,
            'bank_sort_code'    => $sortCode ?: null,
        ], 'id=?', [$cid]);

        flash('Bank account details saved successfully.', 'success');
        redirect(BASE_URL . '/logistics/bank-account.php');
    }
}

// Reload after possible POST errors
$co = DB::fetch('SELECT * FROM logistics_companies WHERE id=?', [$cid]);

$pageTitle = 'Bank Account'; $activeNav = 'bank';
include __DIR__ . '/header.php';

$nigerianBanks = [
    'Access Bank', 'Citibank Nigeria', 'Ecobank Nigeria', 'Fidelity Bank',
    'First Bank of Nigeria', 'First City Monument Bank (FCMB)', 'Globus Bank',
    'Guaranty Trust Bank (GTBank)', 'Heritage Bank', 'Jaiz Bank',
    'Keystone Bank', 'Kuda Bank', 'Moniepoint MFB', 'OPay',
    'Palmpay', 'Polaris Bank', 'Providus Bank', 'Stanbic IBTC Bank',
    'Standard Chartered Bank', 'Sterling Bank', 'SunTrust Bank',
    'Titan Trust Bank', 'UBA (United Bank for Africa)', 'Union Bank',
    'Unity Bank', 'VFD Microfinance Bank', 'Wema Bank', 'Zenith Bank',
];
?>

<div style="max-width:580px">

  <div style="margin-bottom:20px">
    <h1 style="font-family:var(--ff-head);font-size:1.3rem;font-weight:700;color:var(--black);margin-bottom:4px">
      🏦 Bank Account Details
    </h1>
    <p style="font-size:.84rem;color:var(--text-muted)">
      Payout for delivery fees will be sent to this bank account.
      Only company admins can update these details.
    </p>
  </div>

  <!-- Current details -->
  <?php if ($co['bank_account']): ?>
  <div style="background:var(--green-pale);border:1px solid var(--green-pale2);
              border-radius:var(--r-md);padding:14px 16px;margin-bottom:18px">
    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                letter-spacing:.07em;color:var(--green);margin-bottom:8px">
      ✓ Current Bank Account
    </div>
    <div style="display:flex;flex-direction:column;gap:6px;font-size:.86rem">
      <?php foreach ([
        'Bank'           => $co['bank_name'],
        'Account Number' => $co['bank_account'],
        'Account Name'   => $co['bank_account_name'],
        'Sort Code'      => $co['bank_sort_code'] ?: '—',
      ] as $k => $v): ?>
      <div style="display:flex;gap:12px">
        <span style="color:var(--text-muted);width:110px;flex-shrink:0"><?= $k ?></span>
        <span style="font-weight:600;color:var(--black)"><?= e($v) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="alert alert-warning" style="margin-bottom:18px">
    ⚠️ No bank account on file. Add your details below to receive logistics fee payouts.
  </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-danger" style="margin-bottom:14px">
    <?php foreach ($errors as $e): ?><div>• <?= e($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-head">
      <div class="card-title">
        <?= $co['bank_account'] ? '✏ Update Bank Account' : '+ Add Bank Account' ?>
      </div>
    </div>
    <form method="POST">

      <div class="form-group">
        <label class="form-label">Bank Name *</label>
        <select name="bank_name" class="form-control" required id="bank-select"
                onchange="document.getElementById('bank-other').style.display=this.value==='Other'?'block':'none'">
          <option value="">— Select your bank —</option>
          <?php foreach ($nigerianBanks as $bank): ?>
          <option value="<?= e($bank) ?>"
            <?= ($co['bank_name'] === $bank) ? 'selected' : '' ?>>
            <?= e($bank) ?>
          </option>
          <?php endforeach; ?>
          <option value="Other" <?= (!in_array($co['bank_name'] ?? '', $nigerianBanks) && $co['bank_name']) ? 'selected' : '' ?>>
            Other
          </option>
        </select>
        <input type="text" id="bank-other" class="form-control" style="margin-top:6px;display:none"
               placeholder="Enter bank name"
               oninput="document.getElementById('bank-select').value=''">
      </div>

      <div class="form-group">
        <label class="form-label">Account Number *</label>
        <input type="text" name="bank_account" class="form-control"
               maxlength="10" pattern="\d{10,}"
               placeholder="10-digit account number"
               value="<?= e($co['bank_account'] ?? '') ?>"
               oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)"
               required>
        <div class="form-hint">Enter your 10-digit NUBAN account number</div>
      </div>

      <div class="form-group">
        <label class="form-label">Account Name *
          <span style="font-weight:400;color:var(--text-muted)">(as it appears on your bank statement)</span>
        </label>
        <input type="text" name="bank_account_name" class="form-control"
               placeholder="e.g. Swift Delivery Nigeria Ltd"
               value="<?= e($co['bank_account_name'] ?? '') ?>"
               required>
      </div>

      <div class="form-group">
        <label class="form-label">Sort Code / Bank Code
          <span style="font-weight:400;color:var(--text-muted)">(optional)</span>
        </label>
        <input type="text" name="bank_sort_code" class="form-control"
               placeholder="e.g. 044 (Access Bank)"
               value="<?= e($co['bank_sort_code'] ?? '') ?>">
      </div>

      <div class="alert alert-info" style="font-size:.8rem;margin-top:4px">
        🔒 Your bank details are stored securely and only used by African Attire
        to process your logistics fee payouts. They are not shared with merchants or customers.
      </div>

      <div style="display:flex;gap:8px;margin-top:14px">
        <button type="submit" class="btn btn-ju btn-lg" style="flex:1">
          💾 Save Bank Account
        </button>
        <a href="<?= BASE_URL ?>/logistics/" class="btn btn-ghost btn-lg">Cancel</a>
      </div>
    </form>
  </div>

</div>

<script>
// Handle "Other" bank selection
(function(){
  var sel   = document.getElementById('bank-select');
  var other = document.getElementById('bank-other');
  if (sel.value === 'Other' || (sel.value === '' && other.value)) {
    other.style.display = 'block';
  }
  // On submit — copy other input into the select value if needed
  document.querySelector('form').addEventListener('submit', function(){
    if (sel.value === '' || sel.value === 'Other') {
      var otherVal = other.value.trim();
      if (otherVal) {
        var opt = document.createElement('option');
        opt.value = otherVal; opt.selected = true;
        sel.appendChild(opt);
      }
    }
  });
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
