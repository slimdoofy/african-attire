<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole('customer');
// Upgrade customer to merchant role
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    DB::update('users',['role'=>'merchant'],'id=?',[Auth::id()]);
    $_SESSION['urole'] = 'merchant';
    flash('Account upgraded to Merchant! Please complete your shop profile.','success');
    redirect(BASE_URL.'/merchant/join.php');
}
$pageTitle='Become a Merchant'; $activePage='';
include __DIR__ . '/../includes/header_customer.php';
?>
<div class="container-sm" style="padding:4rem 1.5rem;text-align:center">
  <div style="font-size:4rem;margin-bottom:1.5rem">🏪</div>
  <h1 style="font-family:var(--ff-head);margin-bottom:.75rem">Become a Merchant on African Attire</h1>
  <p style="max-width:540px;margin:0 auto 2rem">Join our growing community of African fashion merchants. Set up your digital storefront, list your products, and reach customers globally.</p>
  <div class="card" style="max-width:440px;margin:0 auto;text-align:left">
    <div style="display:flex;flex-direction:column;gap:.75rem;margin-bottom:1.5rem">
      <div class="flex-center gap-2">✅ <span>Free digital storefront</span></div>
      <div class="flex-center gap-2">✅ <span>Reach global African fashion lovers</span></div>
      <div class="flex-center gap-2">✅ <span>Simple <?=getSetting('platform_commission','20')?>% commission model</span></div>
      <div class="flex-center gap-2">✅ <span>Easy payout management</span></div>
      <div class="flex-center gap-2">✅ <span>Real-time analytics dashboard</span></div>
    </div>
    <form method="POST">
      <button type="submit" class="btn btn-blue btn-full btn-lg">Start My Shop →</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer_customer.php'; ?>
