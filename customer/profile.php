<?php
require_once __DIR__.'/../includes/bootstrap.php';
Auth::requireRole('customer');
$profile = DB::fetch('SELECT * FROM customer_profiles WHERE user_id=?',[Auth::id()]);
$user    = DB::fetch('SELECT * FROM users WHERE id=?',[Auth::id()]);
$orderCount = DB::count('SELECT COUNT(*) FROM orders WHERE user_id=?',[Auth::id()]);
$totalSpent = (float)DB::count('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id=? AND payment_status="paid"',[Auth::id()]);
$wishCount  = DB::count('SELECT COUNT(*) FROM wishlists WHERE user_id=?',[Auth::id()]);

if ($_SERVER['REQUEST_METHOD']==='POST') {
    DB::update('users',['name'=>trim($_POST['name']??''),'phone'=>trim($_POST['phone']??'')],'id=?',[Auth::id()]);
    DB::update('customer_profiles',['address_line1'=>trim($_POST['address']??''),'city'=>trim($_POST['city']??''),'state'=>trim($_POST['state']??''),'country'=>trim($_POST['country']??'Nigeria')],'user_id=?',[Auth::id()]);
    $_SESSION['uname'] = trim($_POST['name']??'');
    flash('Profile updated!','success');
    redirect(BASE_URL.'/customer/profile.php');
}
$pageTitle='My Profile'; $activePage='profile';
include __DIR__.'/../includes/header_customer.php';
?>
<div class="wrap-full" style="padding-top:14px;padding-bottom:28px">

  <!-- Profile header -->
  <div class="ju-panel" style="margin-bottom:14px">
    <div class="ju-panel-head">
      <div class="ju-panel-title">👤 My Profile</div>
    </div>
    <div style="padding:20px 16px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--ju),var(--blue));display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:#fff;font-weight:700;flex-shrink:0">
        <?=strtoupper(substr($user['name'],0,1))?>
      </div>
      <div style="flex:1">
        <div style="font-family:var(--ff-head);font-size:1.15rem;font-weight:700;color:var(--black)"><?=e($user['name'])?></div>
        <div style="font-size:.8rem;color:var(--text-muted)"><?=e($user['email'])?> · Member since <?=date('M Y',strtotime($user['created_at']))?></div>
      </div>
      <!-- Quick stats -->
      <div style="display:flex;gap:16px;flex-wrap:wrap">
        <?php foreach ([['Orders',$orderCount],['Spent',money($totalSpent)],['Wishlist',$wishCount]] as $s): ?>
        <div style="text-align:center;background:var(--bg);border-radius:var(--r-md);padding:10px 16px;min-width:80px">
          <div style="font-family:var(--ff-head);font-size:1.1rem;font-weight:800;color:var(--blue)"><?=$s[1]?></div>
          <div style="font-size:.65rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em"><?=$s[0]?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr;gap:14px;max-width:760px">

    <!-- Personal info form -->
    <div class="card">
      <div class="card-head"><div class="card-title">📝 Personal Information</div></div>
      <form method="POST">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" value="<?=e($user['name'])?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="tel" name="phone" class="form-control" value="<?=e($user['phone']??'')?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email <span style="color:var(--text-muted);font-weight:400">(cannot change)</span></label>
          <input type="email" class="form-control" value="<?=e($user['email'])?>" readonly style="background:var(--bg);cursor:not-allowed">
        </div>
        <div class="divider"></div>
        <div class="card-title" style="margin-bottom:12px;font-size:.88rem">📍 Delivery Address</div>
        <div class="form-group">
          <label class="form-label">Street Address</label>
          <input type="text" name="address" class="form-control" value="<?=e($profile['address_line1']??'')?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">City</label>
            <input type="text" name="city" class="form-control" value="<?=e($profile['city']??'')?>">
          </div>
          <div class="form-group">
            <label class="form-label">State</label>
            <input type="text" name="state" class="form-control" value="<?=e($profile['state']??'')?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Country</label>
          <input type="text" name="country" class="form-control" value="<?=e($profile['country']??'Nigeria')?>">
        </div>
        <button type="submit" class="btn btn-ju">💾 Save Changes</button>
      </form>
    </div>

    <!-- Quick links -->
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php foreach ([
        ['📦','My Orders','View and track your orders',BASE_URL.'/customer/orders.php','btn-ju'],
        ['♡','My Wishlist','Saved items',BASE_URL.'/customer/wishlist.php','btn-ghost'],
        ['🔔','Notifications','Updates and alerts',BASE_URL.'/customer/notifications.php','btn-ghost'],
        ['📦','Track an Order','Track any order',BASE_URL.'/customer/track-order.php','btn-ghost'],
      ] as $lnk): ?>
      <div class="card" style="padding:12px 14px">
        <div style="display:flex;align-items:center;gap:10px">
          <span style="font-size:1.3rem;flex-shrink:0"><?=$lnk[0]?></span>
          <div style="flex:1">
            <div style="font-weight:700;font-size:.88rem;color:var(--black)"><?=$lnk[1]?></div>
            <div style="font-size:.75rem;color:var(--text-muted)"><?=$lnk[2]?></div>
          </div>
          <a href="<?=$lnk[3]?>" class="btn <?=$lnk[4]?> btn-sm">→</a>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="card" style="padding:12px 14px;border-color:var(--red-pale)">
        <div style="display:flex;align-items:center;gap:10px">
          <span style="font-size:1.3rem">🚪</span>
          <div style="flex:1">
            <div style="font-weight:700;font-size:.88rem;color:var(--black)">Sign Out</div>
            <div style="font-size:.75rem;color:var(--text-muted)">Log out of your account</div>
          </div>
          <a href="<?=BASE_URL?>/customer/logout.php" class="btn btn-danger btn-sm">Sign Out</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__.'/../includes/footer_customer.php'; ?>
