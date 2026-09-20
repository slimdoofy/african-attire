<?php // footer_customer.php
$_fcats = getCategories();
?>
<div class="mobile-nav-spacer"></div>

<footer class="site-footer">

  <!-- ── TOP BAR ── -->
  <div class="footer-top-bar">
    <div class="footer-top-inner">
      <div>
        <div class="footer-top-text">🌍 Join Africa's Fashion Community</div>
        <div class="footer-top-sub">Browse verified designers · Shop authentic fashion · Sell your creations</div>
      </div>
      <div class="footer-top-cta">
        <a href="<?= BASE_URL ?>/customer/shop.php" class="ftc-primary">Shop Now →</a>
        <a href="<?= BASE_URL ?>/merchant/join.php" class="ftc-outline">Start Selling</a>
      </div>
    </div>
  </div>

  <!-- ── ROW 1: Brand · Shop A · Shop B · My Account · Sell With Us ── -->
  <div class="footer-main">

    <!-- Brand column -->
    <div class="footer-brand-col">
      <div class="footer-logo-row">
        <img src="<?= BASE_URL ?>/assets/images/logo.png" class="footer-logo-img"
             alt="African Attire" style="background:#fff;border-radius:4px;object-fit:contain">
        <div>
          <div class="footer-brand-name">African Attire</div>
          <div class="footer-brand-tag">Africa's #1 Fashion Marketplace</div>
        </div>
      </div>
      <p class="footer-brand-desc">
        Connecting Africa's finest fashion designers with customers across the continent and diaspora.
        Authentic. Verified. Delivered.
      </p>
      <div class="footer-trust-badges">
        <div class="footer-trust-item">
          <div class="footer-trust-ico green">✓</div>
          <span>All merchants verified before listing</span>
        </div>
        <div class="footer-trust-item">
          <div class="footer-trust-ico blue">🔒</div>
          <span>Payments secured by Paystack</span>
        </div>
        <div class="footer-trust-item">
          <div class="footer-trust-ico orange">🚚</div>
          <span>Nationwide delivery · Easy returns</span>
        </div>
      </div>
    </div>

    <!-- Shop column A (first half of categories) -->
    <?php
    $_catTotal   = count($_fcats);
    $_catHalf    = (int)ceil($_catTotal / 2);
    $_catsFirst  = array_slice($_fcats, 0, $_catHalf);
    $_catsSecond = array_slice($_fcats, $_catHalf);
    ?>
    <div class="footer-col">
      <div class="footer-col-head"><span class="fch-ico">🛍</span> Shop</div>
      <div class="footer-col-links">
        <a href="<?= BASE_URL ?>/customer/shop.php" class="footer-col-link">
          <span class="fl-ico">›</span> All Products
        </a>
        <?php foreach($_catsFirst as $c): ?>
        <a href="<?= BASE_URL ?>/customer/shop.php?category=<?= $c['id'] ?>" class="footer-col-link">
          <span class="fl-ico"><?= $c['icon'] ?></span> <?= e($c['name']) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Shop column B (second half) -->
    <div class="footer-col">
      <div class="footer-col-head"><span class="fch-ico">🛍</span> More Categories</div>
      <div class="footer-col-links">
        <?php foreach($_catsSecond as $c): ?>
        <a href="<?= BASE_URL ?>/customer/shop.php?category=<?= $c['id'] ?>" class="footer-col-link">
          <span class="fl-ico"><?= $c['icon'] ?></span> <?= e($c['name']) ?>
        </a>
        <?php endforeach; ?>
        <a href="<?= BASE_URL ?>/customer/shop.php" class="footer-col-link"
           style="color:var(--ju-lt);font-weight:600">
          <span class="fl-ico">→</span> Browse All
        </a>
      </div>
    </div>

    <!-- My Account column -->
    <div class="footer-col">
      <div class="footer-col-head"><span class="fch-ico">👤</span> My Account</div>
      <div class="footer-col-links">
        <a href="<?= BASE_URL ?>/customer/register.php"  class="footer-col-link"><span class="fl-ico">›</span> Create Account</a>
        <a href="<?= BASE_URL ?>/customer/login.php"     class="footer-col-link"><span class="fl-ico">›</span> Sign In</a>
        <a href="<?= BASE_URL ?>/customer/orders.php"    class="footer-col-link"><span class="fl-ico">›</span> My Orders</a>
        <a href="<?= BASE_URL ?>/customer/track-order.php" class="footer-col-link"><span class="fl-ico">📦</span> Track Order</a>
        <a href="<?= BASE_URL ?>/customer/wishlist.php"  class="footer-col-link"><span class="fl-ico">›</span> My Wishlist</a>
        <a href="<?= BASE_URL ?>/customer/profile.php"   class="footer-col-link"><span class="fl-ico">›</span> My Profile</a>
      </div>
    </div>

    <!-- Sell With Us column -->
    <div class="footer-col">
      <div class="footer-col-head"><span class="fch-ico">🏪</span> Sell With Us</div>
      <div class="footer-col-links">
        <a href="<?= BASE_URL ?>/merchant/join.php"                    class="footer-col-link"><span class="fl-ico">›</span> Become a Merchant</a>
        <a href="<?= BASE_URL ?>/customer/merchants.php"               class="footer-col-link"><span class="fl-ico">›</span> Browse Designers</a>
        <a href="<?= BASE_URL ?>/pages/merchant-how-it-works.php"      class="footer-col-link"><span class="fl-ico">›</span> How It Works</a>
        <a href="<?= BASE_URL ?>/pages/merchant-faq.php"               class="footer-col-link"><span class="fl-ico">›</span> Merchant FAQs</a>
        <a href="<?= BASE_URL ?>/merchant/join.php"                    class="footer-col-link"><span class="fl-ico">›</span> Start Selling Free</a>
      </div>
    </div>

  </div><!-- /footer-main ROW 1 -->

  <!-- ── ROW 2: Follow Us · Help & Support · Contact Us ── -->
  <div class="footer-main footer-main-row2"
       style="border-top:1px solid rgba(255,255,255,.07);padding-top:24px;margin-top:0">

    <!-- Follow Us / Socials -->
    <div class="footer-col">
      <div class="footer-col-head"><span class="fch-ico">📱</span> Follow Us</div>
      <div class="footer-col-links">
        <a href="https://www.instagram.com/officialafricanattire?igsh=MWtjdHV3dG5hY2Zibw=="
           target="_blank" rel="noopener noreferrer"
           class="footer-col-link" style="display:flex;align-items:center;gap:8px">
          <span style="font-size:1.1rem">📸</span>
          <span>
            <span style="font-weight:700;color:rgba(255,255,255,.8)">Instagram</span><br>
            <!--<span style="font-size:.72rem;color:rgba(255,255,255,.45)">@africanattire.ng</span>-->
          </span>
        </a>
        <a href="https://www.tiktok.com/@officialafricanattire?_r=1&_t=ZS-97mltEJQ7w0"
           target="_blank" rel="noopener noreferrer"
           class="footer-col-link" style="display:flex;align-items:center;gap:8px">
          <span style="font-size:1.1rem">🎵</span>
          <span>
            <span style="font-weight:700;color:rgba(255,255,255,.8)">TikTok</span><br>
            <!--<span style="font-size:.72rem;color:rgba(255,255,255,.45)">@africanattire.ng</span>-->
          </span>
        </a>
      </div>
    </div>

    <!-- Help & Support -->
    <div class="footer-col">
      <div class="footer-col-head"><span class="fch-ico">💬</span> Help &amp; Support</div>
      <div class="footer-col-links">
        <a href="<?= BASE_URL ?>/pages/customer-faq.php"       class="footer-col-link"><span class="fl-ico">›</span> Customer FAQ</a>
        <a href="<?= BASE_URL ?>/pages/shipping-policy.php"    class="footer-col-link"><span class="fl-ico">›</span> Shipping Policy</a>
        <a href="<?= BASE_URL ?>/pages/returns.php"            class="footer-col-link"><span class="fl-ico">›</span> Returns &amp; Refunds</a>
        <a href="<?= BASE_URL ?>/terms.php"                    class="footer-col-link"><span class="fl-ico">›</span> Terms &amp; Conditions</a>
        <a href="<?= BASE_URL ?>/privacy.php"                  class="footer-col-link"><span class="fl-ico">›</span> Privacy Policy</a>
      </div>
    </div>

    <!-- Contact Us -->
    <div class="footer-col">
      <div class="footer-col-head"><span class="fch-ico">📞</span> Contact Us</div>
      <div class="footer-col-links">
        <a href="tel:+2348128124305" class="footer-col-link" style="display:flex;align-items:center;gap:6px">
          <span class="fl-ico">📞</span>
          <span>
            <span style="font-weight:600;color:rgba(255,255,255,.8);">+234 812 812 4305</span>
          </span>
        </a>
        <a href="tel:+2348138896333" class="footer-col-link" style="display:flex;align-items:center;gap:6px">
          <span class="fl-ico">📞</span>
          <span>
            <span style="font-weight:600;color:rgba(255,255,255,.8);">+234 813 889 6333</span>
          </span>
        </a>
        <a href="mailto:<?= getSetting('site_email','hello@shopafricanattire.com') ?>"
           class="footer-col-link" style="display:flex;align-items:center;gap:6px">
          <span class="fl-ico">✉</span>
          <span style="color:var(--ju-lt)"><?= getSetting('site_email','hello@shopafricanattire.com') ?></span>
        </a>
        <div class="footer-col-link" style="cursor:default;pointer-events:none">
          <span class="fl-ico">🕐</span>
          <span style="color:rgba(255,255,255,.45);font-size:.76rem;line-height:1.7">
            Mon–Fri: 8am – 6pm WAT<br>
            Saturday: 9am – 2pm WAT
          </span>
        </div>
      </div>
    </div>

    <!-- Spacer columns to align with row 1's 5 columns -->
    <div class="footer-col" style="visibility:hidden"></div>
    <div class="footer-col" style="visibility:hidden"></div>

  </div><!-- /footer-main ROW 2 -->

  <!-- ── BOTTOM BAR ── -->
  <div class="footer-bottom-bar">
    <div class="footer-bottom-inner">
      <div class="footer-copyright">
        © <?= date('Y') ?> African Attire (www.shopafricanattire.com). All rights reserved.
      </div>
      <div class="footer-legal-links">
        <a href="<?= BASE_URL ?>/privacy.php">Privacy Policy</a>
        <a href="<?= BASE_URL ?>/terms.php">Terms &amp; Conditions</a>
        <a href="<?= BASE_URL ?>/pages/shipping-policy.php">Shipping</a>
        <a href="<?= BASE_URL ?>/pages/returns.php">Returns</a>
      </div>
      <div class="footer-payment-icons">
        <div class="footer-payment-icon">Paystack</div>
        <div class="footer-payment-icon">Visa</div>
        <div class="footer-payment-icon">Mastercard</div>
        <div class="footer-payment-icon">Verve</div>
      </div>
    </div>
  </div>

</footer>

<!-- Mobile bottom navigation -->
<nav class="mobile-nav">
  <a href="<?= BASE_URL ?>/"
     class="mobile-nav-item <?= ($activePage===''||$activePage==='home')?'active':'' ?>">
    <span class="mn-ico">🏠</span> Home
  </a>
  <a href="<?= BASE_URL ?>/customer/shop.php"
     class="mobile-nav-item <?= $activePage==='shop'?'active':'' ?>">
    <span class="mn-ico">🛍</span> Shop
  </a>
  <a href="<?= BASE_URL ?>/customer/merchants.php"
     class="mobile-nav-item <?= $activePage==='merchants'?'active':'' ?>">
    <span class="mn-ico">✨</span> Designers
  </a>
  <?php if(Auth::check()): ?>
  <button class="mobile-nav-item" onclick="openCart()">
    <span class="mn-ico">🛒</span> Cart
    <?php if($_cart>0):?>
      <span class="mn-dot cart-badge"><?= $_cart ?></span>
    <?php endif;?>
  </button>
  <a href="<?= BASE_URL ?>/customer/profile.php" class="mobile-nav-item">
    <span class="mn-ico">👤</span> Account
  </a>
  <?php else: ?>
  <button class="mobile-nav-item" onclick="openCart()">
    <span class="mn-ico">🛒</span> Cart
  </button>
  <a href="<?= BASE_URL ?>/customer/login.php" class="mobile-nav-item">
    <span class="mn-ico">👤</span> Sign In
  </a>
  <?php endif; ?>
</nav>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
