<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/phone_countries.php';
logRequire();
$la = logAuth();
if ($la['role'] !== 'admin') { flash('Access restricted to company admins.','error'); redirect(BASE_URL.'/logistics/'); }
$cid = $la['company_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'add_user') {
        $fn   = trim($_POST['first_name'] ?? '');
        $ln   = trim($_POST['last_name']  ?? '');
        $em   = trim($_POST['email']      ?? '');
        $pCode= trim($_POST['phone_code'] ?? '+234');
        $pNum = preg_replace('/\D/', '', trim($_POST['phone_number'] ?? ''));
        $role = $_POST['role'] === 'admin' ? 'admin' : 'staff';

        if (!$fn || !$ln || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
            flash('Please fill in all required fields with a valid email.', 'error');
        } elseif (DB::count('SELECT COUNT(*) FROM logistics_users WHERE email=?', [$em])) {
            flash('This email is already registered.', 'error');
        } else {
            $tmpPwd = ucfirst($fn).'#'.rand(1000,9999);
            $hash   = password_hash($tmpPwd, PASSWORD_BCRYPT, ['cost'=>11]);
            DB::insert('logistics_users', [
                'company_id'   => $cid,
                'first_name'   => $fn, 'last_name' => $ln,
                'email'        => $em,
                'phone'        => $pCode.$pNum,
                'password_hash'=> $hash,
                'role'         => $role,
                'status'       => 'active',
                'temp_password'=> 1,
            ]);
            $co = DB::fetch('SELECT company_name FROM logistics_companies WHERE id=?', [$cid]);
            sendLogisticsWelcomeEmail($em, "$fn $ln", $co['company_name']??'', $tmpPwd, BASE_URL.'/logistics/login.php');
            flash("User $fn $ln added. Welcome email sent to $em.", 'success');
        }
    }
    if ($act === 'toggle_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $u   = DB::fetch('SELECT status FROM logistics_users WHERE id=? AND company_id=?',[$uid,$cid]);
        if ($u) { $new=$u['status']==='active'?'suspended':'active'; DB::update('logistics_users',['status'=>$new],'id=?',[$uid]); flash('User '.$new.'.','success'); }
    }
    redirect(BASE_URL . '/logistics/users.php');
}

$pageTitle = 'Team'; $activeNav = 'users';
include __DIR__ . '/header.php';
$users = DB::fetchAll('SELECT * FROM logistics_users WHERE company_id=? ORDER BY created_at', [$cid]);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <h1 style="font-family:var(--ff-head);font-size:1.3rem;font-weight:700;color:var(--black)">👥 Team Members</h1>
  <button class="btn btn-ju btn-sm" onclick="openModal('add-user-modal')">+ Add Team Member</button>
</div>
<div class="card" style="padding:0">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Last Login</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td style="font-weight:600"><?= e($u['first_name'].' '.$u['last_name']) ?><?= $u['temp_password']?'<span class="badge badge-warning" style="margin-left:5px;font-size:.6rem">Temp PW</span>':''?></td>
          <td style="font-size:.82rem"><?= e($u['email']) ?></td>
          <td style="font-size:.8rem"><?= e($u['phone']?:'—') ?></td>
          <td><?= statusBadge($u['role']) ?></td>
          <td><?= statusBadge($u['status']) ?></td>
          <td style="font-size:.76rem;color:var(--text-muted)"><?= $u['last_login_at']?date('M j, Y',strtotime($u['last_login_at'])):'Never' ?></td>
          <td>
            <?php if ($u['id'] !== (int)$la['id']): ?>
            <form method="POST"><input type="hidden" name="action" value="toggle_user"><input type="hidden" name="user_id" value="<?=$u['id']?>"><button type="submit" class="btn btn-ghost btn-xs"><?=$u['status']==='active'?'Suspend':'Activate'?></button></form>
            <?php else: ?><span style="font-size:.74rem;color:var(--text-muted)">You</span><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-bg" id="add-user-modal">
  <div class="modal">
    <div class="modal-head"><h3 class="modal-title">+ Add Team Member</h3><button class="modal-close" onclick="closeModal('add-user-modal')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add_user">
      <div class="form-row">
        <div class="form-group"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Last Name *</label><input type="text" name="last_name" class="form-control" required></div>
      </div>
      <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <div style="display:flex;gap:0">
          <select name="phone_code" class="form-control" style="width:120px;border-radius:var(--r-sm) 0 0 var(--r-sm);border-right:none;font-size:.8rem;flex-shrink:0">
            <?php foreach($PHONE_CODES as[$c,$n,$f,$i]):?><option value="<?=$c?>"<?=$c==='+234'&&$n==='Nigeria'?' selected':''?>><?=$f?> <?=$c?> <?=$n?></option><?php endforeach;?>
          </select>
          <input type="tel" name="phone_number" class="form-control" placeholder="8012345678" style="border-radius:0 var(--r-sm) var(--r-sm) 0">
        </div>
      </div>
      <div class="form-group"><label class="form-label">Role</label><select name="role" class="form-control"><option value="staff">Staff</option><option value="admin">Admin</option></select></div>
      <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:10px;border-top:1px solid var(--border-lt)">
        <button type="button" class="btn btn-ghost" onclick="closeModal('add-user-modal')">Cancel</button>
        <button type="submit" class="btn btn-ju">Add &amp; Send Welcome Email</button>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
