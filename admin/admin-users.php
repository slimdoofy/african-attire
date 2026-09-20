<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Admin Users'; $activeNav = 'admin-users';

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    // Create admin user
    if ($act === 'create') {
        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $modules = $_POST['modules']      ?? [];

        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Name and valid email are required.', 'error');
        } elseif (DB::count('SELECT COUNT(*) FROM admin_users WHERE email=?', [$email])) {
            flash('An admin user with this email already exists.', 'error');
        } else {
            $tmpPwd = ucfirst(substr($name, 0, 3)) . rand(1000, 9999) . '!';
            $hash   = Auth::hash($tmpPwd);
            $uid    = DB::insert('admin_users', [
                'name'          => $name,
                'email'         => $email,
                'password_hash' => $hash,
                'modules'       => json_encode(array_values($modules)),
                'status'        => 'active',
                'temp_password' => 1,
                'created_by'    => Auth::id(),
            ]);
            // Send welcome email
            $loginUrl = BASE_URL . '/admin/';
            $content = "<h2>Welcome to the African Attire Admin Portal</h2>
              <p>Hi <strong>" . htmlspecialchars($name) . "</strong>,</p>
              <p>An admin account has been created for you. Use the credentials below to log in:</p>
              <div style='background:#f8f8f8;padding:14px;border-radius:8px;margin:16px 0'>
                <div><strong>Portal URL:</strong> <a href='{$loginUrl}'>{$loginUrl}</a></div>
                <div><strong>Email:</strong> " . htmlspecialchars($email) . "</div>
                <div><strong>Temporary Password:</strong> <code style='font-size:1.1rem'>{$tmpPwd}</code></div>
              </div>
              <p style='color:#e65100'><strong>⚠️ You will be prompted to change your password on first login.</strong></p>";
            $html = mailTemplate($content, 'Your African Attire Admin Access');
            sendMail($email, $name, '🔐 Your Admin Portal Access — African Attire', $html);
            auditLog('admin_user_created', "Created admin user $email", 'admin_user', $uid, 'admin');
            flash("Admin user <strong>" . htmlspecialchars($name) . "</strong> created. Welcome email sent to $email.", 'success');
        }
        redirect(BASE_URL . '/admin/admin-users.php');
    }

    // Edit modules / status
    if ($act === 'edit') {
        $uid     = (int)($_POST['user_id'] ?? 0);
        $modules = $_POST['modules'] ?? [];
        $status  = $_POST['status'] === 'suspended' ? 'suspended' : 'active';
        DB::update('admin_users', [
            'modules' => json_encode(array_values($modules)),
            'status'  => $status,
        ], 'id=?', [$uid]);
        flash('Admin user updated.', 'success');
        redirect(BASE_URL . '/admin/admin-users.php');
    }

    // Reset password
    if ($act === 'reset_password') {
        $uid  = (int)($_POST['user_id'] ?? 0);
        $user = DB::fetch('SELECT * FROM admin_users WHERE id=?', [$uid]);
        if ($user) {
            $tmpPwd = 'Reset' . rand(1000, 9999) . '!';
            DB::update('admin_users', [
                'password_hash' => Auth::hash($tmpPwd),
                'temp_password' => 1,
            ], 'id=?', [$uid]);
            $content = "<h2>Admin Password Reset</h2>
              <p>Hi <strong>" . htmlspecialchars($user['name']) . "</strong>,</p>
              <p>Your admin password has been reset. Use the temporary password below:</p>
              <div style='background:#f8f8f8;padding:14px;border-radius:8px;margin:16px 0'>
                <div><strong>Temporary Password:</strong> <code style='font-size:1.1rem'>{$tmpPwd}</code></div>
              </div>
              <p style='color:#e65100'>Change it immediately after logging in.</p>";
            $html = mailTemplate($content, 'Admin Password Reset');
            sendMail($user['email'], $user['name'], '🔑 Admin Password Reset — African Attire', $html);
            auditLog('admin_password_reset', "Password reset for admin user {$user['email']}", 'admin_user', $uid, 'admin');
            flash('Password reset. New temporary password emailed to ' . $user['email'] . '.', 'success');
        }
        redirect(BASE_URL . '/admin/admin-users.php');
    }

    // Toggle status (revoke/restore)
    if ($act === 'toggle_status') {
        $uid  = (int)($_POST['user_id'] ?? 0);
        $user = DB::fetch('SELECT status,email FROM admin_users WHERE id=?', [$uid]);
        if ($user) {
            $new = $user['status'] === 'active' ? 'suspended' : 'active';
            DB::update('admin_users', ['status' => $new], 'id=?', [$uid]);
            auditLog('admin_user_'.($new==='suspended'?'suspended':'activated'), "{$user['email']} access $new", 'admin_user', $uid, 'admin');
            flash('User access ' . ($new === 'active' ? 'restored' : 'revoked') . '.', 'success');
        }
        redirect(BASE_URL . '/admin/admin-users.php');
    }
}

include __DIR__ . '/../includes/header_admin.php';

$adminUsers = DB::fetchAll(
    'SELECT * FROM admin_users ORDER BY created_at DESC'
);

$allModules = [
    'dashboard'   => '📊 Dashboard',
    'orders'      => '📦 Orders',
    'merchants'   => '🏪 Merchants',
    'products'    => '🛍 Products',
    'categories'  => '🗂 Categories',
    'customers'   => '👥 Customers',
    'logistics'   => '🚚 Logistics',
    'payouts'     => '💰 Payouts',
    'discounts'   => '🏷 Discounts',
    'reports'     => '📈 Reports',
    'banners'     => '🖼 Banners',
    'settings'    => '⚙️ Settings',
    'audit_log'   => '📋 Audit Log',
    'markup'      => '📊 Markup',
];
?>

<div class="dash-head">
  <div class="flex-between" style="flex-wrap:wrap;gap:10px">
    <div>
      <h1 class="dash-title">🔐 Admin Users</h1>
      <p class="dash-sub"><?= count($adminUsers) ?> admin user<?= count($adminUsers)!==1?'s':''?> · Invite-only access with module permissions</p>
    </div>
    <button class="btn btn-ju" onclick="openModal('create-admin-modal')">+ Invite Admin User</button>
  </div>
</div>

<?php if (empty($adminUsers)): ?>
<div class="empty-state card" style="padding:48px 24px">
  <span class="empty-icon">🔐</span>
  <p style="font-weight:600;margin-bottom:8px">No additional admin users yet</p>
  <button class="btn btn-ju btn-sm" onclick="openModal('create-admin-modal')">+ Invite First Admin</button>
</div>
<?php else: ?>
<div class="card" style="padding:0">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Name</th><th>Email</th><th>Modules</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($adminUsers as $au):
          $mods = json_decode($au['modules'] ?? '[]', true) ?: [];
        ?>
        <tr>
          <td style="font-weight:700"><?= e($au['name']) ?><?= $au['temp_password']?'<span class="badge badge-warning" style="margin-left:5px;font-size:.6rem">Temp PW</span>':''?></td>
          <td style="font-size:.82rem"><?= e($au['email']) ?></td>
          <td>
            <div style="display:flex;flex-wrap:wrap;gap:3px">
              <?php foreach ($mods as $m): ?>
              <span class="badge badge-muted" style="font-size:.62rem">
                <?= $allModules[$m] ?? $m ?>
              </span>
              <?php endforeach; ?>
              <?php if (empty($mods)): ?><span style="font-size:.76rem;color:var(--text-muted)">No modules</span><?php endif; ?>
            </div>
          </td>
          <td><?= statusBadge($au['status']) ?></td>
          <td style="font-size:.76rem;color:var(--text-muted)">
            <?= $au['last_login_at'] ? date('M j, Y g:i A', strtotime($au['last_login_at'])) : 'Never' ?>
          </td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <button class="btn btn-blue btn-xs"
                      onclick='openEditModal(<?= htmlspecialchars(json_encode([
                        "id"=>$au["id"],"name"=>$au["name"],"email"=>$au["email"],
                        "modules"=>$mods,"status"=>$au["status"]
                      ])) ?>)'>
                Edit
              </button>
              <form method="POST">
                <input type="hidden" name="action"  value="reset_password">
                <input type="hidden" name="user_id" value="<?= $au['id'] ?>">
                <button class="btn btn-ghost btn-xs"
                        onclick="return confirm('Reset password for <?= e(addslashes($au['name'])) ?>?')">
                  🔑 Reset PW
                </button>
              </form>
              <form method="POST">
                <input type="hidden" name="action"  value="toggle_status">
                <input type="hidden" name="user_id" value="<?= $au['id'] ?>">
                <button class="btn <?= $au['status']==='active'?'btn-danger':'btn-ghost' ?> btn-xs"
                        onclick="return confirm('<?= $au['status']==='active'?'Revoke':'Restore' ?> access for <?= e(addslashes($au['name'])) ?>?')">
                  <?= $au['status'] === 'active' ? '🚫 Revoke' : '✅ Restore' ?>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ── Create Modal ── -->
<div class="modal-bg" id="create-admin-modal">
  <div class="modal modal-lg">
    <div class="modal-head">
      <h3 class="modal-title">+ Invite Admin User</h3>
      <button class="modal-close" onclick="closeModal('create-admin-modal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Email Address *</label><input type="email" name="email" class="form-control" required></div>
      </div>
      <div class="form-group">
        <label class="form-label">Module Access *</label>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:6px;background:var(--bg);border-radius:var(--r-md);padding:12px">
          <?php foreach ($allModules as $key => $label): ?>
          <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;cursor:pointer;padding:4px 0">
            <input type="checkbox" name="modules[]" value="<?= $key ?>" style="width:14px;height:14px">
            <?= $label ?>
          </label>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" style="margin-top:6px" onclick="toggleAllModules(true)">Select All</button>
        <button type="button" class="btn btn-ghost btn-sm" style="margin-left:4px" onclick="toggleAllModules(false)">Clear All</button>
      </div>
      <div class="alert alert-info" style="font-size:.8rem">
        A temporary password will be generated and emailed to the user.
        They must change it on first login.
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:10px;border-top:1px solid var(--border-lt)">
        <button type="button" class="btn btn-ghost" onclick="closeModal('create-admin-modal')">Cancel</button>
        <button type="submit" class="btn btn-ju">Create &amp; Send Invite</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Edit Modal ── -->
<div class="modal-bg" id="edit-admin-modal">
  <div class="modal modal-lg">
    <div class="modal-head">
      <h3 class="modal-title" id="edit-admin-title">Edit Admin User</h3>
      <button class="modal-close" onclick="closeModal('edit-admin-modal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action"  value="edit">
      <input type="hidden" name="user_id" id="edit-admin-id">
      <div style="margin-bottom:12px;font-size:.86rem;color:var(--text-muted)" id="edit-admin-email"></div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" id="edit-admin-status" class="form-control" style="max-width:160px">
          <option value="active">Active</option>
          <option value="suspended">Suspended</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Module Access</label>
        <div id="edit-modules-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:6px;background:var(--bg);border-radius:var(--r-md);padding:12px">
          <?php foreach ($allModules as $key => $label): ?>
          <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;cursor:pointer;padding:4px 0">
            <input type="checkbox" name="modules[]" value="<?= $key ?>" class="edit-module-cb" style="width:14px;height:14px">
            <?= $label ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:10px;border-top:1px solid var(--border-lt)">
        <button type="button" class="btn btn-ghost" onclick="closeModal('edit-admin-modal')">Cancel</button>
        <button type="submit" class="btn btn-ju">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleAllModules(on) {
  document.querySelectorAll('#create-admin-modal input[type=checkbox]').forEach(function(cb){ cb.checked = on; });
}
function openEditModal(u) {
  document.getElementById('edit-admin-title').textContent = 'Edit: ' + u.name;
  document.getElementById('edit-admin-id').value          = u.id;
  document.getElementById('edit-admin-email').textContent = '📧 ' + u.email;
  document.getElementById('edit-admin-status').value      = u.status;
  document.querySelectorAll('.edit-module-cb').forEach(function(cb){
    cb.checked = u.modules && u.modules.indexOf(cb.value) > -1;
  });
  openModal('edit-admin-modal');
}
</script>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
