<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/phone_countries.php';
$pageTitle = 'Logistics Partners';
$activeNav = 'logistics';

// ── POST: create logistics company ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'create_company') {
        $co      = trim($_POST['company_name']   ?? '');
        $first   = trim($_POST['contact_first']  ?? '');
        $last    = trim($_POST['contact_last']    ?? '');
        $email   = trim($_POST['email']           ?? '');
        $pCode   = trim($_POST['phone_code']      ?? '+234');
        $pNum    = preg_replace('/\D/', '', trim($_POST['phone_number'] ?? ''));
        $phone   = $pCode . $pNum;
        $country = trim($_POST['country']         ?? '');
        $city    = trim($_POST['city']            ?? '');

        $errs = [];
        if (!$co)     $errs[] = 'Company name is required.';
        if (!$first)  $errs[] = 'Contact first name is required.';
        if (!$last)   $errs[] = 'Contact last name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = 'Valid email is required.';
        if (strlen($pNum) < 6) $errs[] = 'Phone number is required.';
        if (!$country) $errs[] = 'Country is required.';
        if (!$city)    $errs[] = 'City is required.';
        if (DB::count('SELECT COUNT(*) FROM logistics_companies WHERE email=?', [$email]))
            $errs[] = 'A logistics company with this email already exists.';

        if (empty($errs)) {
            $cid = DB::insert('logistics_companies', [
                'company_name'  => $co,
                'contact_first' => $first,
                'contact_last'  => $last,
                'email'         => $email,
                'phone'         => $phone,
                'country'       => $country,
                'city'          => $city,
                'created_by'    => Auth::id(),
            ]);

            // Generate temp password and create primary logistics user
            $tmpPwd  = ucfirst(substr($co, 0, 3)) . rand(1000, 9999) . '!';
            $hash    = password_hash($tmpPwd, PASSWORD_BCRYPT, ['cost' => 11]);
            DB::insert('logistics_users', [
                'company_id'   => $cid,
                'first_name'   => $first,
                'last_name'    => $last,
                'email'        => $email,
                'phone'        => $phone,
                'password_hash'=> $hash,
                'role'         => 'admin',
                'status'       => 'active',
                'temp_password'=> 1,
            ]);

            // Send welcome email
            $loginUrl = BASE_URL . '/logistics/login.php';
            sendLogisticsWelcomeEmail($email, "$first $last", $co, $tmpPwd, $loginUrl);

            flash("Logistics partner \"$co\" created. Welcome email sent to $email.", 'success');
        } else {
            foreach ($errs as $e) flash($e, 'error');
        }
        redirect(BASE_URL . '/admin/logistics.php');
    }

    if ($act === 'toggle_status') {
        $cid = (int)($_POST['company_id'] ?? 0);
        $co  = DB::fetch('SELECT * FROM logistics_companies WHERE id=?', [$cid]);
        if ($co) {
            $new = $co['status'] === 'active' ? 'suspended' : 'active';
            DB::update('logistics_companies', ['status' => $new], 'id=?', [$cid]);
            flash("Company " . ($new === 'active' ? 'activated' : 'suspended') . ".", 'success');
        }
        redirect(BASE_URL . '/admin/logistics.php');
    }
}

include __DIR__ . '/../includes/header_admin.php';

$companies = DB::fetchAll(
    "SELECT lc.*,
            u.name admin_name,
            (SELECT COUNT(*) FROM logistics_users lu WHERE lu.company_id=lc.id) user_count,
            (SELECT COUNT(*) FROM order_logistics ol WHERE ol.company_id=lc.id) order_count
     FROM logistics_companies lc
     JOIN users u ON u.id=lc.created_by
     ORDER BY lc.created_at DESC"
);

// Admin view: all orders assigned to any logistics partner
$assignedOrders = DB::fetchAll(
    "SELECT ol.*, o.order_number, o.status order_status, o.created_at order_date,
            COALESCE(u.name, o.guest_name)  cust_name,
            COALESCE(u.email,o.guest_email) cust_email,
            lc.company_name,
            s.shop_name,
            mu.name merchant_name
     FROM order_logistics ol
     JOIN orders o           ON o.id  = ol.order_id
     JOIN logistics_companies lc ON lc.id = ol.company_id
     JOIN shops s             ON s.id  = ol.shop_id
     JOIN users mu            ON mu.id = s.user_id
     LEFT JOIN users u        ON u.id  = o.user_id
     ORDER BY ol.assigned_at DESC
     LIMIT 50"
);
?>

<div class="dash-head">
  <div class="flex-between" style="flex-wrap:wrap;gap:10px">
    <div>
      <h1 class="dash-title">🚚 Logistics Partners</h1>
      <p class="dash-sub">Invite-only logistics management · <?= count($companies) ?> partner<?= count($companies)!==1?'s':''?> registered</p>
    </div>
    <button class="btn btn-ju" onclick="openModal('add-company-modal')">+ Add Logistics Partner</button>
  </div>
</div>

<!-- Partners grid -->
<?php if (empty($companies)): ?>
<div class="empty-state card" style="padding:48px 24px">
  <span class="empty-icon">🚚</span>
  <p style="font-weight:600;margin-bottom:6px">No logistics partners yet</p>
  <p style="font-size:.84rem;margin-bottom:16px">Add your first logistics company to enable delivery assignment for merchants.</p>
  <button class="btn btn-ju btn-sm" onclick="openModal('add-company-modal')">+ Add First Partner</button>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;margin-bottom:20px">
  <?php foreach ($companies as $co): ?>
  <div class="card" style="padding:0;overflow:hidden;border-top:3px solid <?= $co['status']==='active'?'var(--green)':'var(--text-muted)' ?>">
    <div style="padding:14px 16px">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:10px">
        <div>
          <div style="font-family:var(--ff-head);font-size:.96rem;font-weight:700;color:var(--black)"><?= e($co['company_name']) ?></div>
          <div style="font-size:.76rem;color:var(--text-muted)">📍 <?= e($co['city']) ?>, <?= e($co['country']) ?></div>
        </div>
        <span class="badge <?= $co['status']==='active'?'badge-success':'badge-muted' ?>">
          <?= $co['status'] === 'active' ? '● Active' : '○ Suspended' ?>
        </span>
      </div>
      <div style="display:flex;flex-direction:column;gap:5px;margin-bottom:12px;font-size:.82rem;color:var(--text)">
        <div>👤 <?= e($co['contact_first'].' '.$co['contact_last']) ?></div>
        <div>✉️ <?= e($co['email']) ?></div>
        <div>📞 <?= e($co['phone']) ?></div>
      </div>
      <div style="display:flex;gap:12px;padding:8px 0;border-top:1px solid var(--border-lt);margin-bottom:10px">
        <div style="text-align:center;flex:1">
          <div style="font-family:var(--ff-head);font-size:1.1rem;font-weight:700;color:var(--blue)"><?= $co['user_count'] ?></div>
          <div style="font-size:.68rem;color:var(--text-muted)">Portal Users</div>
        </div>
        <div style="text-align:center;flex:1">
          <div style="font-family:var(--ff-head);font-size:1.1rem;font-weight:700;color:var(--ju)"><?= $co['order_count'] ?></div>
          <div style="font-size:.68rem;color:var(--text-muted)">Orders Assigned</div>
        </div>
        <div style="text-align:center;flex:1">
          <div style="font-size:.72rem;color:var(--text-muted)">Added by</div>
          <div style="font-size:.76rem;font-weight:600"><?= e($co['admin_name']) ?></div>
        </div>
      </div>
      <div style="display:flex;gap:6px">
        <a href="<?= BASE_URL ?>/admin/logistics-detail.php?id=<?= $co['id'] ?>"
           class="btn btn-blue btn-sm btn-full">View Details →</a>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="toggle_status">
          <input type="hidden" name="company_id" value="<?= $co['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm"
                  onclick="return confirm('<?= $co['status']==='active'?'Suspend':'Activate' ?> this company?')">
            <?= $co['status']==='active'?'⏸':'▶' ?>
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Assigned orders table -->
<div class="card">
  <div class="card-head">
    <div class="card-title">📦 All Orders Assigned to Logistics Partners</div>
    <span class="badge badge-muted"><?= count($assignedOrders) ?> shown</span>
  </div>
  <?php if (empty($assignedOrders)): ?>
  <div class="empty-state" style="padding:28px"><span class="empty-icon" style="font-size:2rem">📦</span><p>No orders assigned yet.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Order #</th><th>Customer</th><th>Merchant</th><th>Logistics Partner</th><th>Tracking #</th><th>Logistics Status</th><th>Assigned</th></tr>
      </thead>
      <tbody>
        <?php foreach ($assignedOrders as $a): ?>
        <tr>
          <td style="font-weight:700;color:var(--blue);font-size:.84rem"><?= e($a['order_number']) ?></td>
          <td style="font-size:.82rem"><?= e($a['cust_name'] ?: 'Guest') ?></td>
          <td style="font-size:.82rem"><?= e($a['shop_name']) ?></td>
          <td>
            <div style="font-weight:600;font-size:.82rem"><?= e($a['company_name']) ?></div>
          </td>
          <td style="font-size:.78rem;color:var(--text-muted)"><?= e($a['tracking_number'] ?: '—') ?></td>
          <td><?php
            $badgeMap = [
              'assigned'=>'badge-info','picked_up'=>'badge-warning',
              'in_transit'=>'badge-warning','out_for_delivery'=>'badge-warning',
              'delivered'=>'badge-success','failed'=>'badge-danger','returned'=>'badge-danger'
            ];
            $bc = $badgeMap[$a['status']] ?? 'badge-muted';
            echo '<span class="badge '.$bc.'">'.ucwords(str_replace('_',' ',$a['status'])).'</span>';
          ?></td>
          <td style="font-size:.76rem;color:var(--text-muted)"><?= date('M j, Y', strtotime($a['assigned_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Add Company Modal -->
<div class="modal-bg" id="add-company-modal">
  <div class="modal modal-lg">
    <div class="modal-head">
      <h3 class="modal-title">🚚 Add Logistics Partner</h3>
      <button class="modal-close" onclick="closeModal('add-company-modal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create_company">

      <div class="form-group">
        <label class="form-label">Logistics Company Name *</label>
        <input type="text" name="company_name" class="form-control" required
               placeholder="e.g. Swift Delivery Nigeria Ltd">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Contact First Name *</label>
          <input type="text" name="contact_first" class="form-control" required placeholder="Chidi">
        </div>
        <div class="form-group">
          <label class="form-label">Contact Last Name *</label>
          <input type="text" name="contact_last" class="form-control" required placeholder="Okonkwo">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Contact Email Address *
          <span style="font-weight:400;color:var(--text-muted)">(receives login credentials)</span>
        </label>
        <input type="email" name="email" class="form-control" required
               placeholder="contact@swiftdelivery.ng"
               id="logistics-email" oninput="validateLogEmail(this)">
        <div id="logistics-email-check" style="font-size:.72rem;margin-top:4px;display:none"></div>
      </div>

      <div class="form-group">
        <label class="form-label">Phone Number *</label>
        <div style="display:flex;gap:0">
          <select name="phone_code" class="form-control" style="width:130px;flex-shrink:0;border-radius:var(--r-sm) 0 0 var(--r-sm);border-right:none;font-size:.82rem">
            <?php foreach ($PHONE_CODES as [$code,$cname,$flag,$iso]): ?>
            <option value="<?= $code ?>" <?= $code==='+234'&&$cname==='Nigeria'?'selected':'' ?>>
              <?= $flag ?> <?= $code ?> <?= $cname ?>
            </option>
            <?php endforeach; ?>
          </select>
          <input type="tel" name="phone_number" class="form-control" required
                 placeholder="8012345678" pattern="[0-9]{6,12}"
                 style="border-radius:0 var(--r-sm) var(--r-sm) 0">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Country *</label>
          <select name="country" class="form-control" required>
            <option value="" disabled selected>— Select country —</option>
            <?php foreach ($COUNTRIES as $c): ?>
            <option value="<?= e($c) ?>" <?= $c==='Nigeria'?'selected':'' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">City *</label>
          <input type="text" name="city" class="form-control" required placeholder="Lagos">
        </div>
      </div>

      <div class="alert alert-info" style="font-size:.8rem">
        <strong>Note:</strong> A temporary password will be generated and emailed to the contact.
        They must change it on first login. They can then add additional portal users.
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:10px;border-top:1px solid var(--border-lt)">
        <button type="button" class="btn btn-ghost" onclick="closeModal('add-company-modal')">Cancel</button>
        <button type="submit" class="btn btn-ju">Create &amp; Send Invite</button>
      </div>
    </form>
  </div>
</div>

<script>
function validateLogEmail(inp){
  var el=document.getElementById('logistics-email-check');
  var ok=/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(inp.value.trim());
  if(!inp.value.trim()){el.style.display='none';return;}
  el.textContent=ok?'✓ Valid email':'✗ Enter a valid email';
  el.style.color=ok?'var(--green)':'var(--red)';
  el.style.display='block';
}
</script>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
