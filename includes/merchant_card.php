<?php
/**
 * includes/merchant_card.php
 * Reusable merchant/shop card for homepage Top Designers grid.
 * Expects $m array with shops columns + prod_count.
 */
$_mc_earnings  = (float)($m['total_revenue']   ?? 0);
$_mc_comm      = (float)($m['commission_rate']  ?? getSetting('platform_commission','20'));
$_mc_prodCount = (int)($m['prod_count']         ?? 0);
$_mc_logo      = !empty($m['logo'])   ? imgUrl($m['logo'])   : null;
$_mc_banner    = !empty($m['banner']) ? imgUrl($m['banner']) : null;
?>
<div class="shop-card">

  <!-- Banner -->
  <div class="shop-banner">
    <?php if ($_mc_banner): ?>
      <img src="<?= $_mc_banner ?>" alt="<?= e($m['shop_name']) ?>">
    <?php endif; ?>
    <div class="shop-banner-overlay"></div>
    <!-- Verified badge -->
    <div class="mc-verified-badge">✓ Verified</div>
  </div>

  <!-- Avatar (logo overlapping banner) -->
  <div class="mc-avatar-wrap">
    <div class="mc-avatar">
      <?php if ($_mc_logo): ?>
        <img src="<?= $_mc_logo ?>" alt="<?= e($m['shop_name']) ?>">
      <?php else: ?>
        🏪
      <?php endif; ?>
    </div>
  </div>

  <!-- Body -->
  <div class="mc-body">
    <div class="mc-name"><?= e($m['shop_name']) ?></div>
    <?php if (!empty($m['city'])): ?>
    <div class="mc-location">📍 <?= e($m['city']) ?></div>
    <?php endif; ?>
    <?php if (!empty($m['description'])): ?>
    <div class="mc-desc"><?= e($m['description']) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="mc-stats">
      <div class="mc-stat">
        <span class="mc-stat-val"><?= number_format($_mc_prodCount) ?></span>
        <span class="mc-stat-key">Products</span>
      </div>
      <div class="mc-stat">
        <span class="mc-stat-val">✓</span>
        <span class="mc-stat-key">Verified</span>
      </div>
    </div>

    <a href="<?= BASE_URL ?>/customer/merchant.php?id=<?= $m['id'] ?>"
       class="mc-cta">
      Visit Shop →
    </a>
  </div>

</div>
