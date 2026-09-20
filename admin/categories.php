<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Categories';
$activeNav = 'categories';

// ── POST handlers (before any output) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    // ── Create ──────────────────────────────────────────────
    if ($act === 'create') {
        $name  = trim($_POST['name']       ?? '');
        $icon  = trim($_POST['icon']       ?? '👗');
        $order = (int)($_POST['sort_order'] ?? 0);

        if (!$name) {
            flash('Category name is required.', 'error');
        } elseif (DB::count('SELECT COUNT(*) FROM categories WHERE name=?', [$name])) {
            flash('A category with this name already exists.', 'error');
        } else {
            $base = $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $n = 1;
            while (DB::count('SELECT COUNT(*) FROM categories WHERE slug=?', [$slug])) {
                $slug = $base . '-' . $n++;
            }
            DB::insert('categories', [
                'name'       => $name,
                'slug'       => $slug,
                'icon'       => $icon ?: '👗',
                'sort_order' => $order,
            ]);
            flash("Category <strong>" . htmlspecialchars($name) . "</strong> created.", 'success');
        }
        redirect(BASE_URL . '/admin/categories.php');
    }

    // ── Edit ────────────────────────────────────────────────
    if ($act === 'edit') {
        $id    = (int)($_POST['id']         ?? 0);
        $name  = trim($_POST['name']        ?? '');
        $icon  = trim($_POST['icon']        ?? '👗');
        $order = (int)($_POST['sort_order'] ?? 0);

        if (!$id || !$name) {
            flash('ID and name are required.', 'error');
        } elseif (DB::count('SELECT COUNT(*) FROM categories WHERE name=? AND id != ?', [$name, $id])) {
            flash('Another category already has this name.', 'error');
        } else {
            // Regenerate slug from name only if name changed
            $existing = DB::fetch('SELECT * FROM categories WHERE id=?', [$id]);
            if ($existing && $existing['name'] !== $name) {
                $base = $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
                $n = 1;
                while (DB::count('SELECT COUNT(*) FROM categories WHERE slug=? AND id != ?', [$slug, $id])) {
                    $slug = $base . '-' . $n++;
                }
            } else {
                $slug = $existing['slug'] ?? strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            }
            DB::update('categories', [
                'name'       => $name,
                'slug'       => $slug,
                'icon'       => $icon ?: '👗',
                'sort_order' => $order,
            ], 'id=?', [$id]);
            flash("Category <strong>" . htmlspecialchars($name) . "</strong> updated.", 'success');
        }
        redirect(BASE_URL . '/admin/categories.php');
    }

    // ── Reorder (drag-drop AJAX) ─────────────────────────────
    if ($act === 'reorder') {
        header('Content-Type: application/json');
        $ids = $_POST['ids'] ?? [];
        foreach ($ids as $pos => $cid) {
            DB::update('categories', ['sort_order' => (int)$pos + 1], 'id=?', [(int)$cid]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    // ── Delete ───────────────────────────────────────────────
    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $prodCount = (int)DB::count(
            'SELECT COUNT(*) FROM products WHERE category_id=?', [$id]
        );
        if ($prodCount > 0) {
            flash("Cannot delete: this category has <strong>{$prodCount}</strong> product(s) assigned to it. Reassign or delete those products first.", 'error');
        } else {
            $cat = DB::fetch('SELECT name FROM categories WHERE id=?', [$id]);
            DB::query('DELETE FROM categories WHERE id=?', [$id]);
            flash("Category <strong>" . htmlspecialchars($cat['name'] ?? '') . "</strong> deleted.", 'success');
        }
        redirect(BASE_URL . '/admin/categories.php');
    }
}

include __DIR__ . '/../includes/header_admin.php';

$categories = DB::fetchAll(
    "SELECT c.*,
            COUNT(p.id) prod_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id
     ORDER BY c.sort_order ASC, c.name ASC"
);

// Common emoji suggestions for icon picker
$emojiSuggestions = [
    // ── Garments & Fabrics ──────────────────────────────────
    '👗', // General dress / Iro & Buba / Blouse
    '👘', // Flowing robe — Agbada / Babban Riga
    '🥻', // Wrap dress — Kaftan / Native Wrappers
    '🩱', // Body garment — Damask
    '👚', // Casual top — Blouse
    '👙', // Layered top
    '🧕', // Headscarf — Hijab
    '🧣', // Wrap fabric — Aso Oke / Akwete / Inyanga
    '🧥', // Outer garment — Lace / Damask occasion wear
    '👔', // Formal wear
    '👕', // Casual top

    // ── Headwear & Caps ─────────────────────────────────────
    '🎩', // Classic hat — Cultural Caps / Hats
    '👒', // Brimmed hat — Hats
    '🧢', // Cap — Cultural Caps
    '👑', // Crown — Cultural Caps / traditional headgear
    '⛑️', // Structured cap

    // ── Footwear ────────────────────────────────────────────
    '👡', // Heeled sandal — Slippers / footwear
    '👠', // Heel — dress footwear
    '👟', // Flat shoe / Slippers
    '🩴', // Flip-flop — Slippers

    // ── Bags & Accessories ──────────────────────────────────
    '👜', // Handbag — Handmade bags
    '👝', // Clutch — Handmade bags
    '🎒', // Backpack — bags
    '💼', // Structured bag
    '👛', // Coin/small bag

    // ── Jewellery & Adornments ──────────────────────────────
    '💍', // Ring
    '📿', // Beads — very West African
    '💎', // Gemstone
    '🏵️', // Rosette — decorative

    // ── Textiles & Fabric Patterns ──────────────────────────
    '🎨', // Art/pattern — Ankara / Adire / Akwete
    '🌀', // Spiral pattern — Adire / tie-dye
    '🎭', // Masks — cultural / Inyanga
    '🌺', // Flower — floral fabrics / Lace
    '🌸', // Blossom — delicate Lace
    '🧵', // Thread — woven fabrics / Aso Oke / Kente
    '🪡', // Spool — handwoven Akwete / Inyanga

    // ── Misc / Decorative ───────────────────────────────────
    '✨', '🌟', '💫', '🌈', '🔥', '🌙', '☀️',
    '🦚', '🦋', '🌻', '🎀', '🏺',
];
?>

<!-- Page header -->
<div class="dash-head">
  <div class="flex-between" style="flex-wrap:wrap;gap:10px;align-items:flex-start">
    <div>
      <h1 class="dash-title">🗂 Categories</h1>
      <p class="dash-sub">
        <?= count($categories) ?> categor<?= count($categories)===1?'y':'ies'?> ·
        Drag rows to reorder · Changes reflect immediately in the shop
      </p>
    </div>
    <button class="btn btn-ju" onclick="openModal('cat-create-modal')">
      + New Category
    </button>
  </div>
</div>

<?php if (empty($categories)): ?>
<div class="empty-state card" style="padding:48px 24px">
  <span class="empty-icon">🗂</span>
  <p style="font-weight:600;margin-bottom:8px">No categories yet</p>
  <button class="btn btn-ju btn-sm" onclick="openModal('cat-create-modal')">
    + Create First Category
  </button>
</div>
<?php else: ?>

<div class="card" style="padding:0">

  <!-- Column headers -->
  <div style="display:grid;grid-template-columns:36px 60px 1fr 80px 80px 100px 180px;
              gap:0;padding:10px 16px;background:var(--bg);
              border-bottom:1px solid var(--border-lt);
              font-size:.7rem;font-weight:700;text-transform:uppercase;
              letter-spacing:.07em;color:var(--text-muted)">
    <div></div>
    <div>Icon</div>
    <div>Name</div>
    <div style="text-align:center">Order</div>
    <div style="text-align:center">Products</div>
    <div>Slug</div>
    <div>Actions</div>
  </div>

  <!-- Sortable rows -->
  <div id="cat-list">
    <?php foreach ($categories as $cat): ?>
    <div class="cat-row" data-id="<?= $cat['id'] ?>"
         style="display:grid;grid-template-columns:36px 60px 1fr 80px 80px 100px 180px;
                gap:0;padding:10px 16px;border-bottom:1px solid var(--border-lt);
                align-items:center;transition:background .15s;background:#fff">

      <!-- Drag handle -->
      <div class="drag-handle"
           style="cursor:grab;color:var(--text-muted);font-size:1rem;
                  display:flex;align-items:center;justify-content:center;
                  user-select:none">
        ⠿
      </div>

      <!-- Icon -->
      <div style="font-size:1.6rem;text-align:center">
        <?= htmlspecialchars($cat['icon'] ?: '👗') ?>
      </div>

      <!-- Name -->
      <div>
        <div style="font-weight:700;font-size:.9rem;color:var(--black)">
          <?= e($cat['name']) ?>
        </div>
      </div>

      <!-- Sort order -->
      <div style="text-align:center;font-size:.82rem;
                  color:var(--text-muted);font-variant-numeric:tabular-nums">
        <?= $cat['sort_order'] ?>
      </div>

      <!-- Product count -->
      <div style="text-align:center">
        <?php if ($cat['prod_count'] > 0): ?>
        <a href="<?= BASE_URL ?>/admin/products.php?category=<?= $cat['id'] ?>"
           class="badge badge-blue"
           style="text-decoration:none;font-size:.72rem">
          <?= $cat['prod_count'] ?> product<?= $cat['prod_count']>1?'s':''?>
        </a>
        <?php else: ?>
        <span class="badge badge-muted" style="font-size:.7rem">0</span>
        <?php endif; ?>
      </div>

      <!-- Slug -->
      <div style="font-family:monospace;font-size:.74rem;color:var(--text-muted)">
        <?= e($cat['slug']) ?>
      </div>

      <!-- Actions -->
      <div style="display:flex;gap:6px;align-items:center">
        <button class="btn btn-blue btn-xs"
                onclick='openEditModal(<?= htmlspecialchars(json_encode([
                    "id"         => $cat["id"],
                    "name"       => $cat["name"],
                    "icon"       => $cat["icon"],
                    "sort_order" => $cat["sort_order"],
                ])) ?>)'>
          ✏ Edit
        </button>

        <a href="<?= BASE_URL ?>/customer/shop.php?category=<?= $cat['id'] ?>"
           target="_blank" class="btn btn-ghost btn-xs" title="View in shop">
          👁
        </a>

        <?php if ($cat['prod_count'] == 0): ?>
        <form method="POST"
              onsubmit="return confirm('Delete category \'<?= e(addslashes($cat['name'])) ?>\'? This cannot be undone.')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id"     value="<?= $cat['id'] ?>">
          <button type="submit" class="btn btn-danger btn-xs">🗑</button>
        </form>
        <?php else: ?>
        <button class="btn btn-danger btn-xs" disabled
                title="Cannot delete — has <?= $cat['prod_count'] ?> product(s)">
          🗑
        </button>
        <?php endif; ?>
      </div>

    </div>
    <?php endforeach; ?>
  </div>

  <div style="padding:10px 16px;background:var(--bg);font-size:.74rem;
              color:var(--text-muted);border-top:1px solid var(--border-lt)">
    💡 Drag rows by the ⠿ handle to reorder. Order is saved automatically.
  </div>
</div>

<?php endif; ?>

<!-- ══ CREATE MODAL ══════════════════════════════════════════ -->
<div class="modal-bg" id="cat-create-modal">
  <div class="modal" style="max-width:520px">
    <div class="modal-head">
      <h3 class="modal-title">+ New Category</h3>
      <button class="modal-close" onclick="closeModal('cat-create-modal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">

      <div class="form-row">
        <div class="form-group" style="flex:1">
          <label class="form-label">Category Name *</label>
          <input type="text" name="name" class="form-control" required
                 placeholder="e.g. Boubou, Kente, Accessories"
                 autofocus>
        </div>
        <div class="form-group" style="flex:0 0 90px">
          <label class="form-label">Sort Order</label>
          <input type="number" name="sort_order" class="form-control"
                 min="0" value="<?= count($categories) + 1 ?>"
                 style="text-align:center">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Emoji Icon</label>
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
          <input type="text" name="icon" id="create-icon-input" class="form-control"
                 value="👗" maxlength="8" style="width:80px;font-size:1.4rem;text-align:center">
          <span style="font-size:.78rem;color:var(--text-muted)">
            Type any emoji, or click one below:
          </span>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
          <?php foreach ($emojiSuggestions as $em): ?>
          <button type="button"
                  onclick="document.getElementById('create-icon-input').value='<?= $em ?>'"
                  style="width:34px;height:34px;border:1px solid var(--border);border-radius:var(--r-sm);
                         background:#fff;font-size:1.2rem;cursor:pointer;display:flex;
                         align-items:center;justify-content:center;transition:.12s"
                  onmouseover="this.style.borderColor='var(--ju)';this.style.background='var(--ju-pale)'"
                  onmouseout="this.style.borderColor='var(--border)';this.style.background='#fff'">
            <?= $em ?>
          </button>
          <?php endforeach; ?>
        </div>
        <div class="form-hint">The icon appears in the shop category bar and product listings.</div>
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;
                  padding-top:12px;border-top:1px solid var(--border-lt)">
        <button type="button" class="btn btn-ghost"
                onclick="closeModal('cat-create-modal')">Cancel</button>
        <button type="submit" class="btn btn-ju">Create Category</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ EDIT MODAL ════════════════════════════════════════════ -->
<div class="modal-bg" id="cat-edit-modal">
  <div class="modal" style="max-width:520px">
    <div class="modal-head">
      <h3 class="modal-title">✏ Edit Category</h3>
      <button class="modal-close" onclick="closeModal('cat-edit-modal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id"     id="edit-cat-id">

      <div class="form-row">
        <div class="form-group" style="flex:1">
          <label class="form-label">Category Name *</label>
          <input type="text" name="name" id="edit-cat-name" class="form-control" required>
        </div>
        <div class="form-group" style="flex:0 0 90px">
          <label class="form-label">Sort Order</label>
          <input type="number" name="sort_order" id="edit-cat-order" class="form-control"
                 min="0" style="text-align:center">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Emoji Icon</label>
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
          <input type="text" name="icon" id="edit-icon-input" class="form-control"
                 maxlength="8" style="width:80px;font-size:1.4rem;text-align:center">
          <span style="font-size:.78rem;color:var(--text-muted)">
            Type any emoji, or click one below:
          </span>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
          <?php foreach ($emojiSuggestions as $em): ?>
          <button type="button"
                  onclick="document.getElementById('edit-icon-input').value='<?= $em ?>'"
                  style="width:34px;height:34px;border:1px solid var(--border);border-radius:var(--r-sm);
                         background:#fff;font-size:1.2rem;cursor:pointer;display:flex;
                         align-items:center;justify-content:center;transition:.12s"
                  onmouseover="this.style.borderColor='var(--ju)';this.style.background='var(--ju-pale)'"
                  onmouseout="this.style.borderColor='var(--border)';this.style.background='#fff'">
            <?= $em ?>
          </button>
          <?php endforeach; ?>
        </div>
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;
                  padding-top:12px;border-top:1px solid var(--border-lt)">
        <button type="button" class="btn btn-ghost"
                onclick="closeModal('cat-edit-modal')">Cancel</button>
        <button type="submit" class="btn btn-ju">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── Open edit modal ───────────────────────────────────────────
function openEditModal(cat) {
  document.getElementById('edit-cat-id').value    = cat.id;
  document.getElementById('edit-cat-name').value  = cat.name;
  document.getElementById('edit-cat-order').value = cat.sort_order;
  document.getElementById('edit-icon-input').value= cat.icon || '👗';
  openModal('cat-edit-modal');
}

// ── Drag-to-reorder ───────────────────────────────────────────
(function() {
  var list     = document.getElementById('cat-list');
  if (!list) return;
  var dragging = null;

  list.querySelectorAll('.cat-row').forEach(function(row) {
    row.addEventListener('dragstart', function(e) {
      dragging = row;
      row.style.opacity = '0.4';
      e.dataTransfer.effectAllowed = 'move';
    });
    row.addEventListener('dragend', function() {
      row.style.opacity = '1';
      dragging = null;
      saveOrder();
    });
    row.addEventListener('dragover', function(e) {
      e.preventDefault();
      if (dragging && dragging !== row) {
        var rect   = row.getBoundingClientRect();
        var midY   = rect.top + rect.height / 2;
        var parent = row.parentNode;
        if (e.clientY < midY) {
          parent.insertBefore(dragging, row);
        } else {
          parent.insertBefore(dragging, row.nextSibling);
        }
        list.querySelectorAll('.cat-row').forEach(function(r, i) {
          r.style.background = '#fff';
        });
        row.style.background = 'var(--ju-pale)';
      }
    });
    row.addEventListener('dragleave', function() {
      row.style.background = '#fff';
    });
    row.setAttribute('draggable', 'true');
  });

  function saveOrder() {
    var ids = Array.from(list.querySelectorAll('.cat-row'))
                   .map(function(r) { return r.dataset.id; });
    var fd  = new FormData();
    fd.append('action', 'reorder');
    ids.forEach(function(id) { fd.append('ids[]', id); });
    fetch(BASE_URL + '/admin/categories.php', { method:'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.ok) {
          // Brief green flash to confirm save
          list.style.outline = '2px solid var(--green)';
          setTimeout(function(){ list.style.outline = 'none'; }, 700);
        }
      });
  }

  // Make drag handle the draggable trigger
  list.querySelectorAll('.drag-handle').forEach(function(h) {
    h.addEventListener('mousedown', function() {
      h.closest('.cat-row').setAttribute('draggable','true');
    });
    h.addEventListener('mouseup', function() {
      h.closest('.cat-row').setAttribute('draggable','false');
    });
  });
})();
</script>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
