/* ============================================================
   African Attire — main.js
   ============================================================ */
'use strict';

// BASE_URL must be set in a <script> tag before this file loads

/* ─── Toast ──────────────────────────────────────────────────── */
function toast(msg, type = 'default', ms = 3500) {
  let host = document.getElementById('toast-host');
  if (!host) {
    host = document.createElement('div');
    host.id = 'toast-host';
    document.body.appendChild(host);
  }
  const el = document.createElement('div');
  el.className = 'toast' + (type !== 'default' ? ' ' + type : '');
  const icons = { success: '✓', error: '✕', info: 'ℹ' };
  el.innerHTML = (icons[type] ? `<span>${icons[type]}</span>` : '') + `<span>${msg}</span>`;
  host.appendChild(el);
  setTimeout(() => {
    el.style.animation = 'toast-out .3s ease forwards';
    setTimeout(() => el.remove(), 300);
  }, ms);
}

/* ─── Modal ──────────────────────────────────────────────────── */
function openModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}
// Close on backdrop click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-bg')) {
    e.target.classList.remove('open');
    document.body.style.overflow = '';
  }
});

/* ─── Cart Drawer ────────────────────────────────────────────── */
const cartBackdrop = document.getElementById('cart-backdrop');
const cartDrawer   = document.getElementById('cart-drawer');

function openCart() {
  if (!cartDrawer) return;
  cartBackdrop?.classList.add('open');
  cartDrawer.classList.add('open');
  document.body.style.overflow = 'hidden';
  loadCart();
}
function closeCart() {
  cartBackdrop?.classList.remove('open');
  cartDrawer?.classList.remove('open');
  document.body.style.overflow = '';
}

cartBackdrop?.addEventListener('click', closeCart);
document.getElementById('cart-close')?.addEventListener('click', closeCart);

function loadCart() {
  const body   = document.getElementById('cart-body');
  const footer = document.getElementById('cart-footer');
  if (!body) return;
  body.innerHTML = '<p style="text-align:center;padding:2rem;color:var(--cream-3)">Loading…</p>';

  fetch(BASE_URL + '/api/cart.php?action=list')
    .then(r => r.json())
    .then(d => {
      if (!d.items || !d.items.length) {
        body.innerHTML = '<div class="empty-state"><span class="empty-icon">🛍</span><p>Your cart is empty</p></div>';
        if (footer) footer.innerHTML = '';
        return;
      }
      body.innerHTML = d.items.map(item => `
        <div class="cart-item" id="ci-${item.id}">
          <div class="cart-thumb">
            ${item.image ? `<img src="${item.image}" alt="${item.name}">` : item.icon || '👗'}
          </div>
          <div class="cart-info">
            <div class="cart-name">${item.name}</div>
            <div class="cart-meta">${item.shop}${item.size ? ' · ' + item.size : ''}</div>
            <div class="cart-price">${item.line_total_fmt || item.price_fmt}</div>
            <div class="qty-row">
              <button class="qty-btn" onclick="cartQty(${item.id},-1)">−</button>
              <span class="qty-num">${item.qty}</span>
              <button class="qty-btn" onclick="cartQty(${item.id},1)">+</button>
              <button class="qty-btn" onclick="cartRemove(${item.id})" style="color:var(--danger);margin-left:2px" title="Remove">🗑</button>
            </div>
          </div>
        </div>`).join('');

      if (footer) footer.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
          <span style="color:var(--cream-3)">Total</span>
          <span style="font-size:1.2rem;font-weight:700;color:var(--gold-light)">${d.total_fmt || d.symbol + Number(d.total).toLocaleString()}</span>
        </div>
        <a href="${BASE_URL}/customer/checkout.php" class="btn btn-primary btn-full">Proceed to Checkout →</a>
        <div style="text-align:center;margin-top:.6rem">
          <button onclick="closeCart()" class="btn btn-ghost btn-sm">Continue Shopping</button>
        </div>`;

      updateBadge(d.count);
    })
    .catch(() => {
      body.innerHTML = '<div class="empty-state"><span class="empty-icon">⚠️</span><p>Could not load cart</p></div>';
    });
}

function cartQty(id, delta) {
  postCart({ action: 'update', cart_id: id, delta })
    .then(d => { if (d.success) { loadCart(); updateBadge(d.count); } else toast(d.error || 'Error', 'error'); });
}
function cartRemove(id) {
  postCart({ action: 'remove', cart_id: id })
    .then(d => { if (d.success) { document.getElementById('ci-' + id)?.remove(); updateBadge(d.count); } });
}
function addToCart(productId, size = '') {
  postCart({ action: 'add', product_id: productId, size, qty: 1 })
    .then(d => {
      if (d.success) { toast('Added to cart 🛒', 'success'); updateBadge(d.count); }
      else toast(d.error || 'Please log in first', 'error');
    });
}
function postCart(data) {
  return fetch(BASE_URL + '/api/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  }).then(r => r.json());
}
function updateBadge(n) {
  document.querySelectorAll('.cart-badge').forEach(el => {
    el.textContent = n;
    el.style.display = n > 0 ? 'inline-flex' : 'none';
  });
}

/* ─── Wishlist ───────────────────────────────────────────────── */
function toggleWish(productId, btn) {
  fetch(BASE_URL + '/api/wishlist.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ product_id: productId }),
  }).then(r => r.json()).then(d => {
    if (d.success) {
      btn.textContent = d.in_wish ? '❤️' : '🤍';
      toast(d.in_wish ? 'Saved to wishlist' : 'Removed from wishlist');
    } else {
      toast(d.error || 'Please log in', 'error');
    }
  });
}

/* ─── Dropdown ───────────────────────────────────────────────── */
document.addEventListener('click', e => {
  const toggle = e.target.closest('[data-dd]');
  if (toggle) {
    const wrap = toggle.closest('.dd-wrap');
    const wasOpen = wrap?.classList.contains('open');
    document.querySelectorAll('.dd-wrap.open').forEach(w => w.classList.remove('open'));
    if (!wasOpen && wrap) wrap.classList.add('open');
    return;
  }
  document.querySelectorAll('.dd-wrap.open').forEach(w => w.classList.remove('open'));
});

/* ─── Size Selector ──────────────────────────────────────────── */
document.addEventListener('click', e => {
  if (!e.target.classList.contains('size-btn')) return;
  const parent = e.target.closest('.size-grid');
  parent?.querySelectorAll('.size-btn').forEach(b => b.classList.remove('sel'));
  e.target.classList.add('sel');
  const hidden = document.getElementById('sel-size');
  if (hidden) hidden.value = e.target.dataset.size;
});

/* ─── PDP Quantity ───────────────────────────────────────────── */
let pdpQty = 1;
function pdpChangeQty(d, max) {
  pdpQty = Math.max(1, Math.min(max, pdpQty + d));
  const el = document.getElementById('pdp-qty');
  if (el) el.textContent = pdpQty;
}

/* ─── PDP Thumbnail ──────────────────────────────────────────── */
function switchImg(thumb, src) {
  const main = document.getElementById('main-img');
  if (main) main.src = src;
  document.querySelectorAll('.pdp-thumb').forEach(t => t.classList.remove('active'));
  thumb.classList.add('active');
}

/* ─── Table search ───────────────────────────────────────────── */
const tSearch = document.getElementById('tbl-search');
if (tSearch) {
  tSearch.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.sr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

/* ─── Bar Chart ──────────────────────────────────────────────── */
function renderChart(id, data) {
  const c = document.getElementById(id);
  if (!c) return;
  const max = Math.max(...data.map(d => d.v), 1);
  c.innerHTML = data.map(d => `
    <div class="bar-col" title="${d.l}: ₦${Number(d.v).toLocaleString()}">
      <div class="bar" style="height:${Math.round((d.v / max) * 120)}px"></div>
      <div class="bar-lbl">${d.l}</div>
    </div>`).join('');
}

/* ─── Upload preview ─────────────────────────────────────────── */
document.querySelectorAll('[data-preview]').forEach(inp => {
  inp.addEventListener('change', function () {
    const prev = document.getElementById(this.dataset.preview);
    if (!prev || !this.files[0]) return;
    const r = new FileReader();
    r.onload = e => { prev.src = e.target.result; prev.style.display = 'block'; };
    r.readAsDataURL(this.files[0]);
  });
});

/* ─── Range slider display ───────────────────────────────────── */
document.querySelectorAll('input[type=range]').forEach(r => {
  const out = document.getElementById(r.id + '_out');
  if (out) {
    out.textContent = Number(r.value).toLocaleString();
    r.addEventListener('input', () => out.textContent = Number(r.value).toLocaleString());
  }
});

/* ─── Confirm ────────────────────────────────────────────────── */
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
});

/* ─── Order status update (merchant) ────────────────────────── */
function updateItemStatus(itemId, status, el) {
  el.disabled = true;
  fetch(BASE_URL + '/api/orders.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'update_item', item_id: itemId, status }),
  }).then(r => r.json()).then(d => {
    el.disabled = false;
    if (d.success) toast('Status updated', 'success');
    else { toast(d.error || 'Error', 'error'); location.reload(); }
  });
}

/* ─── Auto-dismiss alerts ────────────────────────────────────── */
setTimeout(() => {
  document.querySelectorAll('.alert.auto').forEach(el => {
    el.style.transition = 'opacity .5s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 500);
  });
}, 4000);
