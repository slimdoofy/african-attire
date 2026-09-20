<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Banner & Slider Management';
$activeNav = 'banners';

// ─── Handle POST ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'add') {
        $img = '';
        if (!empty($_FILES['image']['name'])) {
            $img = uploadFile($_FILES['image'], 'banners') ?? '';
        }
        DB::insert('banners', [
            'title'      => trim($_POST['title']    ?? ''),
            'subtitle'   => trim($_POST['subtitle'] ?? ''),
            'image'      => $img,
            'link'       => trim($_POST['link']     ?? ''),
            'position'   => $_POST['position']      ?? 'hero',
            'active'     => 1,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ]);
        flash('Slider banner added successfully!', 'success');
    }

    if ($act === 'edit') {
        $bid  = (int)($_POST['banner_id'] ?? 0);
        $data = [
            'title'      => trim($_POST['title']    ?? ''),
            'subtitle'   => trim($_POST['subtitle'] ?? ''),
            'link'       => trim($_POST['link']     ?? ''),
            'position'   => $_POST['position']      ?? 'hero',
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
        // Replace image only if new one uploaded
        if (!empty($_FILES['image']['name'])) {
            $img = uploadFile($_FILES['image'], 'banners');
            if ($img) $data['image'] = $img;
        }
        DB::update('banners', $data, 'id=?', [$bid]);
        flash('Banner updated.', 'success');
    }

    if ($act === 'toggle') {
        $bid = (int)($_POST['banner_id'] ?? 0);
        $cur = DB::fetch('SELECT active FROM banners WHERE id=?', [$bid]);
        DB::update('banners', ['active' => $cur['active'] ? 0 : 1], 'id=?', [$bid]);
        flash('Banner ' . ($cur['active'] ? 'deactivated' : 'activated') . '.', 'success');
    }

    if ($act === 'delete') {
        DB::delete('banners', 'id=?', [(int)($_POST['banner_id'] ?? 0)]);
        flash('Banner deleted.', 'success');
    }

    redirect(BASE_URL . '/admin/banners.php');
}

include __DIR__ . '/../includes/header_admin.php';

$heroBanners = DB::fetchAll(
    "SELECT * FROM banners WHERE position='hero' ORDER BY sort_order ASC, id ASC"
);
$otherBanners = DB::fetchAll(
    "SELECT * FROM banners WHERE position!='hero' ORDER BY sort_order ASC, id ASC"
);
?>

<div class="dash-head">
  <div class="flex-between" style="flex-wrap:wrap;gap:10px">
    <div>
      <h1 class="dash-title">🖼 Banner &amp; Slider Management</h1>
      <p class="dash-sub">Manage the homepage hero slider and promotional banners</p>
    </div>
    <button class="btn btn-ju" onclick="openModal('add-modal')">+ Add Slider Banner</button>
  </div>
</div>

<!-- ═══ HERO SLIDER SECTION ══════════════════════════════════ -->
<div class="card" style="margin-bottom:20px;border-top:3px solid var(--ju)">
  <div class="card-head">
    <div class="card-title" style="font-size:.95rem">
      🎞 Homepage Hero Slider
      <span class="badge badge-muted" style="font-size:.7rem;font-weight:500;text-transform:none"><?= count($heroBanners) ?> slide<?= count($heroBanners)!==1?'s':'' ?></span>
    </div>
    <a href="<?= BASE_URL ?>/" target="_blank" class="btn btn-ghost btn-sm">👁 Preview Homepage</a>
  </div>

  <?php if(empty($heroBanners)): ?>
  <div style="text-align:center;padding:32px;background:var(--bg);border-radius:var(--r-md);border:2px dashed var(--border)">
    <div style="font-size:2.5rem;margin-bottom:10px">🎞</div>
    <p style="font-weight:600;color:var(--text);margin-bottom:4px">No hero slider banners yet</p>
    <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:14px">Add banner images to appear in the homepage hero slider. Use landscape images (1280×420px recommended).</p>
    <button class="btn btn-ju btn-sm" onclick="openModal('add-modal')">+ Add First Slide</button>
  </div>
  <?php else: ?>

  <!-- Live slider preview -->
  <div style="border-radius:var(--r-md);overflow:hidden;margin-bottom:16px;position:relative;height:160px;background:#111">
    <div id="previewTrack" style="display:flex;height:100%;transition:transform .5s cubic-bezier(.4,0,.2,1)">
      <?php foreach($heroBanners as $b): ?>
      <div style="flex:0 0 100%;height:100%;position:relative">
        <?php if($b['image']): ?>
          <img src="<?= imgUrl($b['image']) ?>"
               style="width:100%;height:100%;object-fit:cover;display:block">
          <div style="position:absolute;inset:0;background:linear-gradient(90deg,rgba(0,0,0,.55),transparent)"></div>
        <?php else: ?>
          <div style="width:100%;height:100%;background:linear-gradient(135deg,#0D47A1,#1565C0,#2E7D32);display:flex;align-items:center;justify-content:center;font-size:3rem;opacity:.5">🌍</div>
        <?php endif; ?>
        <div style="position:absolute;bottom:10px;left:14px;color:#fff">
          <div style="font-size:.68rem;opacity:.7;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">
            <?= e($b['subtitle']) ?>
          </div>
          <div style="font-size:.92rem;font-weight:700;font-family:var(--ff-head)"><?= e($b['title']) ?></div>
        </div>
        <div style="position:absolute;top:8px;right:8px">
          <span class="badge <?= $b['active']?'badge-success':'badge-danger' ?>"><?= $b['active']?'Active':'Inactive' ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if(count($heroBanners)>1): ?>
    <button onclick="prevSlide()" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.2);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:.9rem">‹</button>
    <button onclick="nextSlide()" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.2);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:.9rem">›</button>
    <?php endif; ?>
    <div style="position:absolute;bottom:7px;right:10px;font-size:.65rem;color:rgba(255,255,255,.55)">Preview</div>
  </div>

  <!-- Slider banner table -->
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Order</th>
          <th>Preview</th>
          <th>Title / Subtitle</th>
          <th>Link</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($heroBanners as $b): ?>
        <tr>
          <td>
            <div style="display:flex;flex-direction:column;gap:4px">
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
                <input type="hidden" name="title" value="<?= e($b['title']) ?>">
                <input type="hidden" name="subtitle" value="<?= e($b['subtitle']) ?>">
                <input type="hidden" name="link" value="<?= e($b['link']) ?>">
                <input type="hidden" name="position" value="<?= e($b['position']) ?>">
                <input type="number" name="sort_order" value="<?= $b['sort_order'] ?>"
                       onchange="this.form.submit()"
                       style="width:52px;padding:4px;border:1px solid var(--border);border-radius:var(--r-xs);font-size:.78rem;text-align:center"
                       title="Set display order (lower = first)">
              </form>
            </div>
          </td>
          <td>
            <?php if($b['image']): ?>
              <img src="<?= imgUrl($b['image']) ?>"
                   style="width:90px;height:50px;object-fit:cover;border-radius:var(--r-sm);display:block">
            <?php else: ?>
              <div style="width:90px;height:50px;background:linear-gradient(135deg,var(--blue-dk),var(--green));border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:1.2rem">🌍</div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600;font-size:.84rem;color:var(--black)"><?= e($b['title']) ?></div>
            <?php if($b['subtitle']): ?>
            <div style="font-size:.74rem;color:var(--text-muted);margin-top:2px"><?= e($b['subtitle']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if($b['link']): ?>
              <a href="<?= e($b['link']) ?>" target="_blank"
                 style="font-size:.76rem;color:var(--blue);max-width:160px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= e($b['link']) ?>
              </a>
            <?php else: ?>
              <span style="font-size:.76rem;color:var(--text-muted)">No link</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge <?= $b['active']?'badge-success':'badge-muted' ?>">
              <?= $b['active'] ? '● Active' : '○ Inactive' ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:5px;align-items:center">
              <!-- Toggle -->
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
                <button type="submit" class="btn btn-ghost btn-xs" title="<?= $b['active']?'Deactivate':'Activate' ?>">
                  <?= $b['active'] ? '⏸ Pause' : '▶ Show' ?>
                </button>
              </form>
              <!-- Edit -->
              <button class="btn btn-blue btn-xs"
                      onclick="openEditModal(<?= htmlspecialchars(json_encode($b)) ?>)">
                ✏ Edit
              </button>
              <!-- Delete -->
              <form method="POST" style="display:inline"
                    onsubmit="return confirm('Delete this slide? This cannot be undone.')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
                <button type="submit" class="btn btn-danger btn-xs">🗑</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ═══ OTHER BANNERS (mid / sidebar) ════════════════════════ -->
<?php if(!empty($otherBanners)): ?>
<div class="card">
  <div class="card-head">
    <div class="card-title">📢 Other Promotional Banners</div>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Preview</th><th>Title</th><th>Position</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach($otherBanners as $b): ?>
        <tr>
          <td><?php if($b['image']): ?><img src="<?= imgUrl($b['image']) ?>" style="width:80px;height:44px;object-fit:cover;border-radius:var(--r-sm)"><?php else: ?><span style="font-size:1.5rem">🖼</span><?php endif; ?></td>
          <td><div style="font-weight:600;font-size:.84rem"><?= e($b['title']) ?></div><div style="font-size:.73rem;color:var(--text-muted)"><?= e($b['subtitle']??'') ?></div></td>
          <td><span class="badge badge-info"><?= ucfirst($b['position']) ?></span></td>
          <td><span class="badge <?= $b['active']?'badge-success':'badge-muted' ?>"><?= $b['active']?'Active':'Inactive' ?></span></td>
          <td>
            <div style="display:flex;gap:5px">
              <form method="POST"><input type="hidden" name="action" value="toggle"><input type="hidden" name="banner_id" value="<?= $b['id'] ?>"><button type="submit" class="btn btn-ghost btn-xs"><?= $b['active']?'Pause':'Show' ?></button></form>
              <form method="POST" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="banner_id" value="<?= $b['id'] ?>"><button class="btn btn-danger btn-xs">🗑</button></form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ══ ADD BANNER MODAL ════════════════════════════════════════ -->
<div class="modal-bg" id="add-modal">
  <div class="modal modal-lg">
    <div class="modal-head">
      <h3 class="modal-title">➕ Add Slider Banner</h3>
      <button class="modal-close" onclick="closeModal('add-modal')">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add">

      <div class="form-group">
        <label class="form-label">Banner Title *
          <span style="font-weight:400;color:var(--text-muted)">(shown as large headline on slide)</span>
        </label>
        <input type="text" name="title" class="form-control" required
               placeholder="e.g. Authentic African Fashion Delivered">
      </div>

      <div class="form-group">
        <label class="form-label">Subtitle / Tag
          <span style="font-weight:400;color:var(--text-muted)">(small pill above headline)</span>
        </label>
        <input type="text" name="subtitle" class="form-control"
               placeholder="e.g. New Collection · Spring 2025">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Position</label>
          <select name="position" class="form-control">
            <option value="hero" selected>🎞 Hero Slider (Homepage)</option>
            <option value="mid">📢 Mid-page Banner</option>
            <option value="sidebar">📌 Sidebar Banner</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Display Order
            <span style="font-weight:400;color:var(--text-muted)">(0 = first)</span>
          </label>
          <input type="number" name="sort_order" class="form-control" value="0" min="0">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Click Link (optional)
          <span style="font-weight:400;color:var(--text-muted)">(where "Shop Now" button goes)</span>
        </label>
        <input type="text" name="link" class="form-control"
               placeholder="/customer/shop.php or /customer/shop.php?category=1">
      </div>

      <div class="form-group">
        <label class="form-label">Banner Image *
          <span style="font-weight:400;color:var(--text-muted)">(recommended: 1280×420px landscape)</span>
        </label>
        <div class="upload-zone" onclick="document.getElementById('add-img').click()" id="add-drop">
          <div style="font-size:2rem;margin-bottom:6px">🖼</div>
          <div style="font-size:.84rem;font-weight:600;color:var(--text)">Click to upload or drag &amp; drop</div>
          <div style="font-size:.75rem;color:var(--text-muted);margin-top:3px">JPEG, PNG or WebP · Max 5MB · 1280×420px recommended</div>
          <input type="file" id="add-img" name="image" accept="image/*"
                 style="display:none" onchange="previewImg(this,'add-prev','add-drop')">
        </div>
        <img id="add-prev" style="display:none;width:100%;height:100px;object-fit:cover;border-radius:var(--r-md);margin-top:8px">
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:8px;border-top:1px solid var(--border)">
        <button type="button" class="btn btn-ghost" onclick="closeModal('add-modal')">Cancel</button>
        <button type="submit" class="btn btn-ju">Add to Slider</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ EDIT BANNER MODAL ═══════════════════════════════════════ -->
<div class="modal-bg" id="edit-modal">
  <div class="modal modal-lg">
    <div class="modal-head">
      <h3 class="modal-title">✏ Edit Banner</h3>
      <button class="modal-close" onclick="closeModal('edit-modal')">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="banner_id" id="edit-id">

      <div class="form-group">
        <label class="form-label">Banner Title *</label>
        <input type="text" name="title" id="edit-title" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Subtitle / Tag</label>
        <input type="text" name="subtitle" id="edit-subtitle" class="form-control">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Position</label>
          <select name="position" id="edit-position" class="form-control">
            <option value="hero">🎞 Hero Slider</option>
            <option value="mid">📢 Mid-page Banner</option>
            <option value="sidebar">📌 Sidebar Banner</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Display Order</label>
          <input type="number" name="sort_order" id="edit-order" class="form-control" min="0">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Click Link</label>
        <input type="text" name="link" id="edit-link" class="form-control">
      </div>
      <div class="form-group">
        <label class="form-label">Replace Image (leave blank to keep existing)</label>
        <div>
          <img id="edit-current-img" style="width:100%;height:90px;object-fit:cover;border-radius:var(--r-md);display:none;margin-bottom:8px">
          <div class="upload-zone" onclick="document.getElementById('edit-img').click()">
            <div style="font-size:1.5rem;margin-bottom:4px">🔄</div>
            <div style="font-size:.8rem;color:var(--text-muted)">Click to replace banner image</div>
            <input type="file" id="edit-img" name="image" accept="image/*"
                   style="display:none" onchange="previewImg(this,'edit-prev',null)">
          </div>
          <img id="edit-prev" style="display:none;width:100%;height:80px;object-fit:cover;border-radius:var(--r-md);margin-top:6px">
        </div>
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:8px;border-top:1px solid var(--border)">
        <button type="button" class="btn btn-ghost" onclick="closeModal('edit-modal')">Cancel</button>
        <button type="submit" class="btn btn-ju">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── Admin preview slider ──────────────────────────────────────
var previewCur = 0;
var previewSlides = document.querySelectorAll('#previewTrack > div');
function prevSlide(){
  previewCur = (previewCur - 1 + previewSlides.length) % previewSlides.length;
  document.getElementById('previewTrack').style.transform = 'translateX(-'+(previewCur*100)+'%)';
}
function nextSlide(){
  previewCur = (previewCur + 1) % previewSlides.length;
  document.getElementById('previewTrack').style.transform = 'translateX(-'+(previewCur*100)+'%)';
}

// ── Image preview ─────────────────────────────────────────────
function previewImg(input, previewId, dropId){
  var file = input.files[0];
  if(!file) return;
  var reader = new FileReader();
  reader.onload = function(e){
    var prev = document.getElementById(previewId);
    prev.src = e.target.result;
    prev.style.display = 'block';
  };
  reader.readAsDataURL(file);
}

// ── Open edit modal ───────────────────────────────────────────
function openEditModal(b){
  document.getElementById('edit-id').value        = b.id;
  document.getElementById('edit-title').value     = b.title;
  document.getElementById('edit-subtitle').value  = b.subtitle || '';
  document.getElementById('edit-link').value      = b.link || '';
  document.getElementById('edit-order').value     = b.sort_order || 0;
  document.getElementById('edit-position').value  = b.position || 'hero';
  var img = document.getElementById('edit-current-img');
  if(b.image){
    img.src = b.image.startsWith('http') ? b.image : BASE_URL + '/assets/uploads/banners/' + b.image;
    img.style.display = 'block';
  } else {
    img.style.display = 'none';
  }
  openModal('edit-modal');
}
</script>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
