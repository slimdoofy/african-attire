<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Platform Settings';
$activeNav = 'settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note         = trim($_POST['settings']['rate_change_note'] ?? '');
    $admin        = Auth::user();

    // Fetch both existing rates
    $oldUsdRow = DB::fetch("SELECT value FROM settings WHERE `key`='usd_ngn_rate'");
    $oldNgnRow = DB::fetch("SELECT value FROM settings WHERE `key`='ngn_usd_rate'");
    $oldUsdRate = $oldUsdRow ? (float)$oldUsdRow['value'] : 1600.0;
    $oldNgnRate = $oldNgnRow ? (float)$oldNgnRow['value'] : 0.000625;

    // Determine which rate was changed and sync the other
    $newUsdRaw = isset($_POST['settings']['usd_ngn_rate']) ? (float)$_POST['settings']['usd_ngn_rate'] : null;
    $newNgnRaw = isset($_POST['settings']['ngn_usd_rate']) ? (float)$_POST['settings']['ngn_usd_rate'] : null;

    // If USD→NGN changed, derive NGN→USD; if NGN→USD changed, derive USD→NGN
    $usdChanged = $newUsdRaw !== null && $newUsdRaw > 0 && abs($newUsdRaw - $oldUsdRate) > 0.0001;
    $ngnChanged = $newNgnRaw !== null && $newNgnRaw > 0 && abs($newNgnRaw - $oldNgnRate) > 0.000001;
    // Also check EUR/GBP changes (we log them too via the same mechanism)

    if ($usdChanged) {
        $_POST['settings']['usd_ngn_rate'] = $newUsdRaw;
        $_POST['settings']['ngn_usd_rate'] = round(1 / $newUsdRaw, 8);
    } elseif ($ngnChanged) {
        $_POST['settings']['ngn_usd_rate'] = $newNgnRaw;
        $_POST['settings']['usd_ngn_rate'] = round(1 / $newNgnRaw, 2);
    }
    $rateChanged = $usdChanged || $ngnChanged;
    $newRate = $usdChanged ? $newUsdRaw : ($ngnChanged ? round(1/$newNgnRaw,2) : $oldUsdRate);

    foreach ($_POST['settings'] ?? [] as $key => $val) {
        $key = preg_replace('/[^a-z0-9_]/', '', $key);
        if (!$key || $key === 'rate_change_note') continue;
        $isSecret = (strpos($key, '_key') !== false || strpos($key, '_secret') !== false);
        if ($isSecret && trim($val) === '') continue;
        DB::query(
            "INSERT INTO `settings` (`key`, `value`, `label`)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE `value` = ?",
            [$key, $val, $key, $val]
        );
    }

    // Log both rate changes in audit trail
    if ($rateChanged) {
        DB::insert('exchange_rate_log', [
            'admin_id'    => $admin['id'],
            'admin_name'  => $admin['name'],
            'admin_email' => $admin['email'],
            'rate_key'    => 'usd_ngn_rate',
            'old_rate'    => $oldUsdRate,
            'new_rate'    => (float)($_POST['settings']['usd_ngn_rate'] ?? $oldUsdRate),
            'note'        => $note ?: null,
            'changed_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    flash('Settings saved successfully.' . ($rateChanged ? ' Exchange rate updated and logged.' : ''), 'success');
    redirect(BASE_URL . '/admin/settings.php');
}

include __DIR__ . '/../includes/header_admin.php';

// Load all settings into a keyed array for easy access
$rawSettings = DB::fetchAll('SELECT * FROM settings ORDER BY `key`');
$cfg = [];
foreach ($rawSettings as $r) {
    $cfg[$r['key']] = $r['value'] ?? '';
}

function sv(array $cfg, string $key, string $default = ''): string {
    return htmlspecialchars($cfg[$key] ?? $default);
}
?>

<div class="dash-head">
  <h1 class="dash-title">⚙️ Platform Settings</h1>
  <p class="dash-sub">Configure global settings for the African Attire platform</p>
</div>

<form method="POST" style="max-width:860px">

  <!-- ══ GENERAL SETTINGS ══════════════════════════════════════ -->
  <div class="card" style="margin-bottom:16px;border-top:3px solid var(--ju)">
    <div class="card-head">
      <div class="card-title" style="font-size:.95rem">🌐 General Settings</div>
    </div>

    <div class="form-row" style="margin-bottom:14px">
      <div class="form-group">
        <label class="form-label">Site Name</label>
        <input type="text" name="settings[site_name]" class="form-control"
               value="<?= sv($cfg,'site_name','African Attire') ?>"
               placeholder="African Attire">
      </div>
      <div class="form-group">
        <label class="form-label">Site Email</label>
        <input type="email" name="settings[site_email]" class="form-control"
               value="<?= sv($cfg,'site_email','hello@africanattire.com') ?>"
               placeholder="hello@africanattire.com">
      </div>
    </div>

    <div class="form-row" style="margin-bottom:14px">
      <div class="form-group">
        <label class="form-label">Support Phone</label>
        <input type="text" name="settings[support_phone]" class="form-control"
               value="<?= sv($cfg,'support_phone') ?>"
               placeholder="+234 800 000 0000">
      </div>
      <div class="form-group">
        <label class="form-label">Currency Symbol</label>
        <input type="text" name="settings[currency_symbol]" class="form-control"
               value="<?= sv($cfg,'currency_symbol','₦') ?>"
               placeholder="₦">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Platform Commission Rate (%)</label>
      <input type="number" name="settings[platform_commission]" class="form-control"
             value="<?= sv($cfg,'platform_commission','20') ?>"
             min="0" max="100" step="0.1" style="max-width:180px">
      <div class="form-hint">Percentage taken from each sale. e.g. 20 = 20%</div>
    </div>

    <div class="form-group">
      <label class="form-label">Maintenance Mode</label>
      <select name="settings[maintenance_mode]" class="form-control" style="max-width:180px">
        <option value="0" <?= ($cfg['maintenance_mode']??'0')==='0'?'selected':'' ?>>Off — Site is live</option>
        <option value="1" <?= ($cfg['maintenance_mode']??'0')==='1'?'selected':'' ?>>On  — Show maintenance page</option>
      </select>
    </div>
  </div>

  <!-- ══ SENDGRID EMAIL SETTINGS ═══════════════════════════════ -->
  <div class="card" style="margin-bottom:16px;border-top:3px solid var(--blue)">
    <div class="card-head">
      <div class="card-title" style="font-size:.95rem">✉️ Email Settings (SendGrid)</div>
      <a href="https://app.sendgrid.com/settings/api_keys" target="_blank"
         class="btn btn-ghost btn-sm">Get API Key →</a>
    </div>

    <div class="alert alert-info" style="margin-bottom:16px">
      <div>
        <strong>How to set up SendGrid:</strong>
        <ol style="margin-top:6px;padding-left:18px;font-size:.82rem;line-height:1.8;color:var(--text)">
          <li>Create a free account at <a href="https://sendgrid.com" target="_blank" style="color:var(--blue);font-weight:600">sendgrid.com</a></li>
          <li>Go to <strong>Settings → API Keys → Create API Key</strong> (Full Access)</li>
          <li>Paste the key below — it starts with <code style="background:var(--bg);padding:1px 5px;border-radius:3px;font-size:.8rem">SG.</code></li>
          <li>Go to <strong>Settings → Sender Authentication</strong> and verify your sender email</li>
          <li>Enter that verified email and your sender name below</li>
        </ol>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">
        SendGrid API Key
        <span style="font-weight:400;color:var(--text-muted)">(leave blank to keep existing)</span>
      </label>
      <div style="position:relative;max-width:560px">
        <input type="password" name="settings[sendgrid_api_key]"
               id="sg-key-input" class="form-control"
               value="" placeholder="SG.xxxxxxxxxxxxxxxxxxxx"
               autocomplete="new-password">
        <button type="button" onclick="toggleSgKey()"
                style="position:absolute;right:8px;top:50%;transform:translateY(-50%);
                       background:none;border:none;cursor:pointer;color:var(--text-muted);
                       font-size:.8rem;padding:2px 6px">
          Show
        </button>
      </div>
      <?php if (!empty($cfg['sendgrid_api_key'])): ?>
      <div class="form-hint" style="color:var(--green)">
        ✓ API key is set (<?= strlen($cfg['sendgrid_api_key']) ?> chars). Leave blank to keep it unchanged.
      </div>
      <?php else: ?>
      <div class="form-hint" style="color:var(--text-muted)">No API key set — emails will not send until configured.</div>
      <?php endif; ?>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">
          Verified Sender Email *
          <span style="font-weight:400;color:var(--text-muted)">(must be verified in SendGrid)</span>
        </label>
        <input type="email" name="settings[sendgrid_from_email]" class="form-control"
               value="<?= sv($cfg,'sendgrid_from_email') ?>"
               placeholder="noreply@africanattire.com">
        <?php if (!empty($cfg['sendgrid_from_email'])): ?>
        <div class="form-hint" style="color:var(--green)">
          ✓ Current: <?= sv($cfg,'sendgrid_from_email') ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">
          Sender Name
          <span style="font-weight:400;color:var(--text-muted)">(shown in inbox "From")</span>
        </label>
        <input type="text" name="settings[sendgrid_from_name]" class="form-control"
               value="<?= sv($cfg,'sendgrid_from_name','African Attire') ?>"
               placeholder="African Attire">
      </div>
    </div>

    <!-- Test connection button -->
    <div style="display:flex;align-items:center;gap:10px;margin-top:4px;flex-wrap:wrap">
      <button type="button" onclick="testSendGrid()"
              class="btn btn-ghost btn-sm" id="test-sg-btn">
        🧪 Send Test Email
      </button>
      <span id="test-sg-result" style="font-size:.8rem"></span>
    </div>
  </div>

  <!-- ══ PAYSTACK PAYMENT SETTINGS ═════════════════════════════ -->
  <div class="card" style="margin-bottom:16px;border-top:3px solid var(--green)">
    <div class="card-head">
      <div class="card-title" style="font-size:.95rem">💳 Payment Settings (Paystack)</div>
      <a href="https://dashboard.paystack.com/#/settings/developer" target="_blank"
         class="btn btn-ghost btn-sm">Paystack Dashboard →</a>
    </div>

    <div class="alert alert-warning" style="margin-bottom:14px">
      <div style="font-size:.82rem">
        <strong>⚠️ Keep your Secret Key private.</strong>
        Never share it or expose it in client-side code.
        The Public Key is safe to use in the browser (checkout page).
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">
        Paystack Public Key
        <span style="font-weight:400;color:var(--text-muted)">(used on checkout page)</span>
      </label>
      <input type="text" name="settings[paystack_public_key]" class="form-control"
             value="<?= sv($cfg,'paystack_public_key') ?>"
             placeholder="pk_live_xxxx or pk_test_xxxx" style="max-width:480px">
    </div>

    <div class="form-group">
      <label class="form-label">
        Paystack Secret Key
        <span style="font-weight:400;color:var(--text-muted)">(leave blank to keep existing)</span>
      </label>
      <div style="position:relative;max-width:480px">
        <input type="password" name="settings[paystack_secret_key]"
               id="ps-key-input" class="form-control"
               value="" placeholder="sk_live_xxxx or sk_test_xxxx"
               autocomplete="new-password">
        <button type="button" onclick="togglePsKey()"
                style="position:absolute;right:8px;top:50%;transform:translateY(-50%);
                       background:none;border:none;cursor:pointer;color:var(--text-muted);
                       font-size:.8rem;padding:2px 6px">
          Show
        </button>
      </div>
      <?php if (!empty($cfg['paystack_secret_key'])): ?>
      <div class="form-hint" style="color:var(--green)">
        ✓ Secret key is set. Leave blank to keep it unchanged.
      </div>
      <?php endif; ?>
    </div>
  </div>


  <!-- ══ CURRENCY & EXCHANGE RATE ════════════════════════════ -->
  <div class="card" style="margin-bottom:16px;border-top:3px solid var(--gold)">
    <div class="card-head">
      <div class="card-title" style="font-size:.95rem">💱 Currency &amp; Exchange Rates</div>
      <a href="<?= BASE_URL ?>/admin/exchange-log.php" class="btn btn-ghost btn-sm">
        📋 View Audit Log
      </a>
    </div>

    <div class="alert alert-info" style="margin-bottom:16px;font-size:.82rem">
      All product prices are stored in <strong>Nigerian Naira (₦ NGN)</strong>.
      Customers can switch to USD view — prices are converted using the rate below.
      The rate at the time of each order is captured for audit purposes.
    </div>

    <?php
    $usdNgn = (float)($cfg['usd_ngn_rate'] ?? 1600);
    $eurNgn = (float)($cfg['eur_ngn_rate'] ?? 1740);
    $gbpNgn = (float)($cfg['gbp_ngn_rate'] ?? 2030);
    $enabledCurs = enabledCurrencies();
    ?>

    <!-- Enable/disable currencies -->
    <div class="form-row" style="margin-bottom:14px">
      <div class="form-group">
        <label class="form-label">Enable Currencies</label>
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:4px">
          <label style="display:flex;align-items:center;gap:6px;font-size:.86rem;font-weight:600;background:var(--bg);padding:6px 12px;border-radius:var(--r-sm);border:1px solid var(--border-lt)">
            <input type="checkbox" checked disabled> ₦ NGN (always on)
          </label>
          <label style="display:flex;align-items:center;gap:6px;font-size:.86rem;font-weight:600;background:var(--bg);padding:6px 12px;border-radius:var(--r-sm);border:1px solid var(--border-lt)">
            <input type="checkbox" checked disabled> $ USD (always on)
          </label>
          <label style="display:flex;align-items:center;gap:6px;font-size:.86rem;font-weight:600;background:var(--bg);padding:6px 12px;border-radius:var(--r-sm);border:1px solid var(--border-lt);cursor:pointer">
            <input type="checkbox" name="settings[currency_eur_enabled]" value="1"
              <?= ($cfg['currency_eur_enabled']??'1')==='1'?'checked':''?>>
            € EUR — Euro
          </label>
          <label style="display:flex;align-items:center;gap:6px;font-size:.86rem;font-weight:600;background:var(--bg);padding:6px 12px;border-radius:var(--r-sm);border:1px solid var(--border-lt);cursor:pointer">
            <input type="checkbox" name="settings[currency_gbp_enabled]" value="1"
              <?= ($cfg['currency_gbp_enabled']??'1')==='1'?'checked':''?>>
            £ GBP — Pound Sterling
          </label>
        </div>
        <div class="form-hint">Only NGN is stored internally. Other currencies are display-only conversions.</div>
      </div>
    </div>

    <!-- Rate inputs: all 3 foreign currencies -->
    <?php foreach ([
      'usd' => ['$', 'US Dollar',       'usd_ngn_rate', 'ngn_usd_rate', $usdNgn, 1600],
      'eur' => ['€', 'Euro',            'eur_ngn_rate', 'ngn_eur_rate', $eurNgn, 1740],
      'gbp' => ['£', 'Pound Sterling',  'gbp_ngn_rate', 'ngn_gbp_rate', $gbpNgn, 2030],
    ] as $cc => [$sym, $cname, $fwdKey, $invKey, $rate, $def]): ?>
    <div style="background:var(--bg);border-radius:var(--r-md);padding:12px 16px;margin-bottom:10px">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:10px">
        <?= $sym ?> <?= $cname ?> (<?= strtoupper($cc) ?>)
      </div>
      <div class="form-row" style="margin-bottom:0">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" style="font-size:.8rem"><?= $sym ?>1 = how many ₦?</label>
          <div style="display:flex;align-items:center;gap:6px">
            <span style="font-weight:700;color:var(--text-muted)"><?= $sym ?>1 =</span>
            <input type="number" name="settings[<?= $fwdKey ?>]"
                   id="rate-<?= $cc ?>-ngn"
                   class="form-control" style="max-width:130px;font-weight:700"
                   value="<?= number_format($rate,2,'.','') ?>"
                   min="1" step="0.01"
                   oninput="syncRate('<?= $cc ?>','fwd',this.value)">
            <span style="font-weight:700;color:var(--text-muted)">₦</span>
          </div>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" style="font-size:.8rem">₦1 = how many <?= $sym ?>?</label>
          <div style="display:flex;align-items:center;gap:6px">
            <span style="font-weight:700;color:var(--text-muted)">₦1 =</span>
            <input type="number" name="settings[<?= $invKey ?>]"
                   id="rate-ngn-<?= $cc ?>"
                   class="form-control" style="max-width:130px;font-weight:700"
                   value="<?= $rate>0?number_format(1/$rate,8,'.',''):'0.000000' ?>"
                   min="0.000001" step="0.000001"
                   oninput="syncRate('<?= $cc ?>','inv',this.value)">
            <span style="font-weight:700;color:var(--text-muted)"><?= $sym ?></span>
          </div>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" style="font-size:.8rem">Preview</label>
          <div style="font-size:.9rem;font-weight:700;color:var(--navy)" id="prev-<?= $cc ?>">
            ₦50,000 = <?= $sym ?><?= $rate>0?number_format(50000/$rate,2):'?' ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <div class="form-row" style="margin-top:10px">
      <div class="form-group">
        <label class="form-label">Default Display Currency</label>
        <select name="settings[currency_mode]" class="form-control" style="max-width:200px">
          <option value="ngn" <?= ($cfg['currency_mode']??'ngn')==='ngn'?'selected':''?>>₦ NGN — Naira</option>
          <option value="usd" <?= ($cfg['currency_mode']??'ngn')==='usd'?'selected':''?>>$ USD — Dollar</option>
          <option value="eur" <?= ($cfg['currency_mode']??'')==='eur'?'selected':''?>>€ EUR — Euro</option>
          <option value="gbp" <?= ($cfg['currency_mode']??'')==='gbp'?'selected':''?>>£ GBP — Pound</option>
        </select>
        <div class="form-hint">New visitors see prices in this currency by default</div>
      </div>
      <div class="form-group">
        <label class="form-label">Change Note <span style="font-weight:400;color:var(--text-muted)">(optional — logged)</span></label>
        <input type="text" name="settings[rate_change_note]" class="form-control"
               placeholder="e.g. Updated to reflect CBN/ECB rates — Jan 2025">
      </div>
    </div>

<script>
var SYMBOLS = {usd:'$',eur:'€',gbp:'£'};
function syncRate(cc, dir, val) {
  var r = parseFloat(val);
  if (!r || r <= 0) return;
  if (dir === 'fwd') { // cc→NGN changed → update inverse
    var invEl = document.getElementById('rate-ngn-' + cc);
    if (invEl) invEl.value = (1/r).toFixed(8);
    updateCcPreview(cc, r);
  } else { // NGN→cc changed → update forward
    var fwdEl = document.getElementById('rate-' + cc + '-ngn');
    if (fwdEl) fwdEl.value = (1/r).toFixed(2);
    updateCcPreview(cc, 1/r);
  }
}
function updateCcPreview(cc, ngnPerUnit) {
  var el = document.getElementById('prev-' + cc);
  if (!el) return;
  var sym = SYMBOLS[cc] || cc.toUpperCase();
  el.textContent = '₦50,000 = ' + sym + (50000/ngnPerUnit).toFixed(2);
}
// Init previews
<?php foreach(['usd'=>$usdNgn,'eur'=>$eurNgn,'gbp'=>$gbpNgn] as $cc=>$r): if($r>0): ?>
updateCcPreview('<?= $cc ?>', <?= $r ?>);
<?php endif; endforeach; ?>

// Logistics fee sync — keep NGN/USD in sync via exchange rate
var LOG_RATE = <?= (float)($cfg['usd_ngn_rate'] ?? 1600) ?>;
function syncLogFare(from) {
  var ngn = parseFloat(document.getElementById('lf-base-ngn').value);
  var usd = parseFloat(document.getElementById('lf-base-usd').value);
  if (from === 'ngn' && ngn >= 0)  document.getElementById('lf-base-usd').value = (ngn / LOG_RATE).toFixed(2);
  if (from === 'usd' && usd >= 0)  document.getElementById('lf-base-ngn').value = Math.round(usd * LOG_RATE);
}
function syncLogKm(from) {
  var ngn = parseFloat(document.getElementById('lf-km-ngn').value);
  var usd = parseFloat(document.getElementById('lf-km-usd').value);
  if (from === 'ngn' && ngn >= 0)  document.getElementById('lf-km-usd').value = (ngn / LOG_RATE).toFixed(3);
  if (from === 'usd' && usd >= 0)  document.getElementById('lf-km-ngn').value = Math.round(usd * LOG_RATE);
}
function updateFeePreview() {
  var km    = parseFloat(document.getElementById('preview-km').value) || 0;
  var base  = parseFloat(document.getElementById('lf-base-ngn').value) || 0;
  var perKm = parseFloat(document.getElementById('lf-km-ngn').value)   || 0;
  var minF  = parseFloat(document.getElementById('lf-min-ngn').value)  || 0;
  var fee   = Math.max(minF, base + (perKm * km));
  document.getElementById('preview-fee-ngn').textContent = '₦' + fee.toLocaleString('en-NG', {minimumFractionDigits:2});
  document.getElementById('preview-fee-usd').textContent = '$' + (fee / LOG_RATE).toFixed(2);
}
updateFeePreview();
</script>
  </div>


  <!-- ══ LOGISTICS FEE SETTINGS ══════════════════════════════ -->
  <div class="card" style="margin-bottom:16px;border-top:3px solid #00695C">
    <div class="card-head">
      <div class="card-title" style="font-size:.95rem">🚚 Logistics Fee Configuration</div>
      <a href="https://developers.google.com/maps/documentation/distance-matrix"
         target="_blank" class="btn btn-ghost btn-sm">Google Maps API →</a>
    </div>

    <div class="alert alert-info" style="margin-bottom:16px;font-size:.82rem">
      <strong>How fees are calculated:</strong>
      Fee = Base Fare + (Distance in km × Per-km Rate).
      Distance is calculated between the <strong>merchant's pickup city</strong>
      and the <strong>customer's delivery city</strong>.
      Optionally add a Google Maps API key for road distances — otherwise the
      system uses straight-line (Haversine) distance × 1.35 road factor.
    </div>

    <!-- Preview calculator -->
    <div style="background:var(--bg);border-radius:var(--r-md);padding:14px 16px;margin-bottom:16px">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                  letter-spacing:.07em;color:var(--text-muted);margin-bottom:10px">
        Live Fee Preview
      </div>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;font-size:.84rem">
        <span style="color:var(--text-muted)">For a</span>
        <input type="number" id="preview-km" value="100" min="0" step="10"
               style="width:80px;padding:4px 8px;border:1px solid var(--border);border-radius:var(--r-sm);font-size:.84rem"
               oninput="updateFeePreview()">
        <span style="color:var(--text-muted)">km journey:</span>
        <span style="font-family:var(--ff-head);font-size:1rem;font-weight:700;color:var(--navy)"
              id="preview-fee-ngn">calculating…</span>
        <span style="color:var(--text-muted)">/</span>
        <span style="font-family:var(--ff-head);font-size:1rem;font-weight:700;color:var(--green)"
              id="preview-fee-usd">…</span>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Base Fare (NGN) *
          <span style="font-weight:400;color:var(--text-muted)">— charged regardless of distance</span>
        </label>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="font-weight:600;color:var(--text-muted)">₦</span>
          <input type="number" name="settings[logistics_base_fare_ngn]"
                 id="lf-base-ngn" class="form-control"
                 value="<?= sv($cfg,'logistics_base_fare_ngn','2000') ?>"
                 min="0" step="100" style="max-width:150px"
                 oninput="syncLogFare('ngn'); updateFeePreview()">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Base Fare (USD) *</label>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="font-weight:600;color:var(--text-muted)">$</span>
          <input type="number" name="settings[logistics_base_fare_usd]"
                 id="lf-base-usd" class="form-control"
                 value="<?= sv($cfg,'logistics_base_fare_usd','1.25') ?>"
                 min="0" step="0.01" style="max-width:150px"
                 oninput="syncLogFare('usd'); updateFeePreview()">
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Rate per km (NGN)</label>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="font-weight:600;color:var(--text-muted)">₦</span>
          <input type="number" name="settings[logistics_per_km_ngn]"
                 id="lf-km-ngn" class="form-control"
                 value="<?= sv($cfg,'logistics_per_km_ngn','150') ?>"
                 min="0" step="10" style="max-width:150px"
                 oninput="syncLogKm('ngn'); updateFeePreview()">
          <span style="font-size:.78rem;color:var(--text-muted)">/km</span>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Rate per km (USD)</label>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="font-weight:600;color:var(--text-muted)">$</span>
          <input type="number" name="settings[logistics_per_km_usd]"
                 id="lf-km-usd" class="form-control"
                 value="<?= sv($cfg,'logistics_per_km_usd','0.09') ?>"
                 min="0" step="0.001" style="max-width:150px"
                 oninput="syncLogKm('usd'); updateFeePreview()">
          <span style="font-size:.78rem;color:var(--text-muted)">/km</span>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Minimum Logistics Fee (NGN)
        <span style="font-weight:400;color:var(--text-muted)">(fee floor — never charge less than this)</span>
      </label>
      <div style="display:flex;align-items:center;gap:8px">
        <span style="font-weight:600;color:var(--text-muted)">₦</span>
        <input type="number" name="settings[logistics_min_fee_ngn]"
               id="lf-min-ngn" class="form-control"
               value="<?= sv($cfg,'logistics_min_fee_ngn','2500') ?>"
               min="0" step="100" style="max-width:150px"
               oninput="updateFeePreview()">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Google Maps API Key
        <span style="font-weight:400;color:var(--text-muted)">(optional — enables road distance instead of straight-line)</span>
      </label>
      <div style="position:relative;max-width:480px">
        <input type="password" name="settings[google_maps_api_key]"
               id="gm-key-input" class="form-control"
               value="" placeholder="AIza..."
               autocomplete="new-password">
        <button type="button" onclick="document.getElementById('gm-key-input').type=document.getElementById('gm-key-input').type==='password'?'text':'password'"
                style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:.78rem">
          Show
        </button>
      </div>
      <?php if (!empty($cfg['google_maps_api_key'])): ?>
      <div class="form-hint" style="color:var(--green)">✓ API key is set. Leave blank to keep unchanged.</div>
      <?php else: ?>
      <div class="form-hint">Without a key, fees are estimated using city coordinates (Haversine formula). <a href="https://developers.google.com/maps/documentation/distance-matrix/get-api-key" target="_blank" style="color:var(--blue)">Get a free key →</a></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ══ NOTIFICATION EMAILS ══════════════════════════════════ -->
  <div class="card" style="margin-bottom:16px;border-top:3px solid var(--orange)">
    <div class="card-head">
      <div class="card-title" style="font-size:.95rem">🔔 Notification Emails</div>
    </div>
    <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:14px">
      Enter email addresses (comma-separated) to receive notifications for each event.
      Leave blank to disable notifications for that event.
    </p>
    <?php foreach ([
      'notify_merchant_registration' => ['🏪 New Merchant Registration', 'Sent when a merchant submits a registration for review'],
      'notify_product_review'        => ['🛍 Product Submitted for Review', 'Sent when a merchant submits a new product or edit for approval'],
      'notify_new_order'             => ['📦 New Order Placed', 'Sent when a customer places a new order'],
      'notify_new_dispute'           => ['⚖️ New Dispute Raised', 'Sent when a customer or guest raises a dispute'],
    ] as $key => [$label, $hint]): ?>
    <div class="form-group">
      <label class="form-label"><?= $label ?></label>
      <input type="text" name="settings[<?= $key ?>]" class="form-control"
             style="max-width:480px"
             placeholder="admin@example.com, support@example.com"
             value="<?= e($cfg[$key] ?? '') ?>">
      <div class="form-hint"><?= $hint ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ══ SAVE BUTTON ══════════════════════════════════════════ -->
  <div style="display:flex;gap:10px;align-items:center">
    <button type="submit" class="btn btn-ju btn-lg">
      💾 Save All Settings
    </button>
    <span style="font-size:.78rem;color:var(--text-muted)">
      Changes take effect immediately.
    </span>
  </div>

</form>

<script>
function toggleSgKey() {
  var inp = document.getElementById('sg-key-input');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}
function togglePsKey() {
  var inp = document.getElementById('ps-key-input');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}
function testSendGrid() {
  var btn = document.getElementById('test-sg-btn');
  var res = document.getElementById('test-sg-result');
  btn.disabled = true;
  btn.textContent = 'Sending…';
  res.textContent = '';
  res.style.color = '';
  fetch('<?= BASE_URL ?>/admin/test-email.php', {method:'POST'})
    .then(function(r){ return r.json(); })
    .then(function(d){
      res.textContent = d.message || (d.ok ? '✓ Test email sent!' : '✗ Failed');
      res.style.color = d.ok ? 'var(--green)' : 'var(--red)';
    })
    .catch(function(){
      res.textContent = '✗ Request failed. Check your PHP error log.';
      res.style.color = 'var(--red)';
    })
    .finally(function(){
      btn.disabled = false;
      btn.textContent = '🧪 Send Test Email';
    });
}
</script>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
