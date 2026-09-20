<?php
/**
 * migrate.php — African Attire Database Migration Runner
 *
 * Run in your browser: http://localhost/african-attire/migrate.php
 *
 * HOW TO ADD A NEW MIGRATION:
 *   1. Create a file in the migrations/ folder:
 *      e.g.  migrations/006_my_change.php
 *   2. Return an array with: id, title, description, steps[]
 *      Each step has: label, sql, and optionally safe=>true
 *      (safe=true means errors on that step are expected and ignored)
 *   3. Visit migrate.php in your browser and click Run Pending
 *
 * migrations/ files are run in alphabetical filename order.
 * Each migration is tracked in the `migrations` table and
 * runs exactly ONCE — re-running migrate.php is always safe.
 */

// ─── Bootstrap (config + DB only — no session/auth needed) ───
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// ─── Create tracking table if it doesn't exist ───────────────
DB::query("
    CREATE TABLE IF NOT EXISTS `migrations` (
        `id`         VARCHAR(10)  NOT NULL,
        `title`      VARCHAR(200) NOT NULL,
        `batch`      INT(11)      NOT NULL DEFAULT 1,
        `ran_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `status`     ENUM('ok','partial','failed') NOT NULL DEFAULT 'ok',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ─── Load migration files ─────────────────────────────────────
$migrationFiles = glob(__DIR__ . '/migrations/*.php');
sort($migrationFiles); // ensures alphabetical = chronological order

$allMigrations  = [];
foreach ($migrationFiles as $file) {
    $def = require $file;
    if (isset($def['id'])) {
        $allMigrations[$def['id']] = $def;
    }
}

// ─── Which migrations have already run? ───────────────────────
$ran = DB::fetchAll("SELECT id, status, ran_at FROM migrations ORDER BY id");
$ranIds = array_column($ran, 'id');

// ─── Pending = defined but not yet in tracking table ─────────
$pending = array_filter($allMigrations, function($m) use ($ranIds) { return !in_array($m['id'], $ranIds); });

// ─── Handle: run a specific migration ─────────────────────────
$results  = [];   // ['step_id' => ['ok'=>bool,'msg'=>string,'safe'=>bool]]
$runId    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';

    // Special: re-run admin reset even if already ran
    if ($action === 'reset_admin') {
        $mid = '003';
        $migration = $allMigrations[$mid] ?? null;
        if ($migration) {
            // Remove from tracking so it can run again
            DB::query("DELETE FROM migrations WHERE id=?", [$mid]);
            // Force POST to run it
            $_POST['action'] = 'run';
            $_POST['mid']    = $mid;
            $action = 'run';
        }
    }
    $runId   = $_POST['migrate'] ?? null;   // single migration id, or 'all'

    if ($action === 'run') {
        $toRun = ($runId === 'all')
            ? array_values($pending)
            : (isset($allMigrations[$runId]) ? [$allMigrations[$runId]] : []);

        $batch = (int)(DB::count("SELECT COALESCE(MAX(batch),0) FROM migrations") + 1);

        foreach ($toRun as $migration) {
            $mid        = $migration['id'];
            $stepOk     = 0;
            $stepFail   = 0;
            $stepSafeErr= 0;

            foreach ($migration['steps'] as $stepId => $step) {
                $safe = !empty($step['safe']);
                try {
                    if (isset($step['php']) && is_callable($step['php'])) {
                        // PHP callable step (e.g. mkdir operations)
                        $msg = call_user_func($step['php']);
                        $results[$mid][$stepId] = ['ok'=>true, 'msg'=>$step['label'] . ($msg ? ': '.$msg : ''), 'safe'=>$safe];
                        $stepOk++;
                    } elseif (!empty($step['sql'])) {
                        DB::query($step['sql']);
                        $results[$mid][$stepId] = ['ok'=>true,  'msg'=>$step['label'], 'safe'=>$safe];
                        $stepOk++;
                    } else {
                        // No sql or php — skip silently
                        $results[$mid][$stepId] = ['ok'=>'safe', 'msg'=>$step['label'].' — skipped (no sql/php)', 'safe'=>true];
                        $stepSafeErr++;
                    }
                } catch (Exception $e) {
                    $msg = $step['label'] . ' — ' . $e->getMessage();
                    if ($safe) {
                        $results[$mid][$stepId] = ['ok'=>'safe', 'msg'=>$msg, 'safe'=>true];
                        $stepSafeErr++;
                    } else {
                        $results[$mid][$stepId] = ['ok'=>false, 'msg'=>$msg, 'safe'=>false];
                        $stepFail++;
                    }
                }
            }

            // Determine overall status
            $status = $stepFail > 0 ? 'partial' : 'ok';

            // Record in tracking table (REPLACE so re-runs update the row)
            DB::query(
                "INSERT INTO `migrations` (`id`,`title`,`batch`,`ran_at`,`status`)
                 VALUES (?,?,?,NOW(),?)
                 ON DUPLICATE KEY UPDATE `ran_at`=NOW(), `status`=?, `batch`=?",
                [$mid, $migration['title'], $batch, $status, $status, $batch]
            );
        }

        // Refresh ran list
        $ran    = DB::fetchAll("SELECT id, status, ran_at FROM migrations ORDER BY id");
        $ranIds = array_column($ran, 'id');
        $pending = array_filter($allMigrations, function($m) use ($ranIds) { return !in_array($m['id'], $ranIds); });
    }

    if ($action === 'rollback') {
        $mid = $_POST['migration_id'] ?? '';
        if ($mid) {
            DB::query("DELETE FROM `migrations` WHERE id=?", [$mid]);
            // Refresh
            $ran    = DB::fetchAll("SELECT id, status, ran_at FROM migrations ORDER BY id");
            $ranIds = array_column($ran, 'id');
            $pending = array_filter($allMigrations, function($m) use ($ranIds) { return !in_array($m['id'], $ranIds); });
        }
    }
}

// ─── Build ran map for quick lookup ──────────────────────────
$ranMap = [];
foreach ($ran as $r) { $ranMap[$r['id']] = $r; }

$pendingCount = count($pending);
$ranCount     = count($ran);
$totalCount   = count($allMigrations);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Database Migrations — African Attire</title>
  <style>
    /* ── Design tokens ── */
    :root{
      --bg:#0B1120;--surface:#111827;--surface2:#1F2937;
      --border:#1F2937;--border2:#374151;
      --text:#F3F4F6;--text-soft:#9CA3AF;--text-muted:#6B7280;
      --blue:#2563EB;--blue-lt:#3B82F6;--blue-pale:#1E3A5F;
      --green:#16A34A;--green-lt:#22C55E;--green-pale:#14532D;
      --orange:#D97706;--orange-pale:#451A03;
      --red:#DC2626;--red-pale:#450A0A;
      --radius:10px;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:system-ui,-apple-system,sans-serif;background:var(--bg);
         color:var(--text);line-height:1.6;font-size:14px;min-height:100vh}
    a{color:var(--blue-lt);text-decoration:none}

    /* ── Layout ── */
    .page{max-width:900px;margin:0 auto;padding:32px 20px 64px}
    .header{display:flex;align-items:flex-start;justify-content:space-between;
            flex-wrap:wrap;gap:16px;margin-bottom:28px}
    .header-left h1{font-size:1.35rem;color:#fff;display:flex;align-items:center;gap:8px}
    .header-left p{font-size:.82rem;color:var(--text-muted);margin-top:4px}

    /* ── Cards ── */
    .card{background:var(--surface);border:1px solid var(--border2);
          border-radius:var(--radius);padding:20px;margin-bottom:14px}
    .card-head{display:flex;align-items:center;justify-content:space-between;
               margin-bottom:16px;flex-wrap:wrap;gap:8px}
    .card-title{font-size:.82rem;text-transform:uppercase;letter-spacing:.08em;
                color:var(--text-muted);display:flex;align-items:center;gap:6px}

    /* ── Stats bar ── */
    .stats{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px}
    .stat{background:var(--surface);border:1px solid var(--border2);
          border-radius:8px;padding:14px 20px;flex:1;min-width:140px}
    .stat-val{font-size:1.8rem;font-weight:700;line-height:1;margin-bottom:4px}
    .stat-key{font-size:.73rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em}
    .stat.blue  .stat-val{color:var(--blue-lt)}
    .stat.green .stat-val{color:var(--green-lt)}
    .stat.orange.stat-val,.stat.orange .stat-val{color:#FCD34D}

    /* ── Badges ── */
    .badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;
           border-radius:999px;font-size:.67rem;font-weight:700;letter-spacing:.03em;
           text-transform:uppercase}
    .b-ran    {background:var(--green-pale);  color:var(--green-lt)}
    .b-pending{background:#1C1917;            color:#FCD34D;border:1px solid #44403C}
    .b-failed {background:var(--red-pale);    color:#F87171}
    .b-partial{background:var(--orange-pale); color:#FCD34D}

    /* ── Migration row ── */
    .mig{background:var(--surface2);border:1px solid var(--border2);
         border-radius:8px;padding:14px 16px;margin-bottom:8px;
         display:flex;align-items:flex-start;gap:14px}
    .mig-num{font-size:.7rem;font-weight:800;color:var(--text-muted);
             background:var(--border2);border-radius:4px;padding:2px 7px;
             letter-spacing:.04em;flex-shrink:0;margin-top:2px}
    .mig-body{flex:1;min-width:0}
    .mig-title{font-weight:600;color:var(--text);font-size:.9rem;margin-bottom:2px}
    .mig-desc{font-size:.78rem;color:var(--text-muted);margin-bottom:6px}
    .mig-meta{font-size:.71rem;color:var(--text-muted)}
    .mig-actions{display:flex;gap:6px;flex-shrink:0;align-items:flex-start;flex-wrap:wrap}

    /* ── Step results ── */
    .step-results{margin-top:10px;background:var(--bg);border-radius:6px;
                  padding:10px 12px;border:1px solid var(--border)}
    .step-item{display:flex;align-items:flex-start;gap:7px;padding:4px 0;
               font-size:.78rem;border-bottom:1px solid var(--border)}
    .step-item:last-child{border-bottom:none}
    .step-ico{flex-shrink:0;margin-top:1px}
    .step-ok   {color:var(--green-lt)}
    .step-safe {color:#FCD34D}
    .step-fail {color:#F87171}

    /* ── Buttons ── */
    .btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;
         border-radius:6px;font-size:.8rem;font-weight:600;border:none;
         cursor:pointer;transition:opacity .15s,transform .1s;white-space:nowrap;
         font-family:inherit}
    .btn:hover{opacity:.88}.btn:active{transform:scale(.97)}
    .btn-green {background:var(--green);   color:#fff}
    .btn-blue  {background:var(--blue);    color:#fff}
    .btn-ghost {background:var(--border2); color:var(--text-soft)}
    .btn-danger{background:var(--red);     color:#fff}
    .btn-orange{background:var(--orange);  color:#fff}
    .btn-sm    {padding:5px 10px;font-size:.74rem}
    .btn:disabled{opacity:.4;cursor:not-allowed}

    /* ── Notice boxes ── */
    .notice{border-radius:8px;padding:12px 16px;margin-bottom:20px;
            font-size:.84rem;display:flex;align-items:flex-start;gap:10px}
    .notice-warn{background:var(--orange-pale);border:1px solid #78350F;color:#FDE68A}
    .notice-info{background:var(--blue-pale);  border:1px solid #1D4ED8;color:#BFDBFE}
    .notice-ok  {background:var(--green-pale); border:1px solid #166534;color:#BBF7D0}
    .notice-ico {font-size:1.1rem;flex-shrink:0;margin-top:1px}

    /* ── How to add section ── */
    .howto{background:var(--surface);border:1px solid var(--border2);
           border-radius:var(--radius);padding:20px;margin-top:24px}
    .howto h3{font-size:.88rem;color:#fff;margin-bottom:14px;
              display:flex;align-items:center;gap:6px}
    .howto ol{padding-left:18px;display:flex;flex-direction:column;gap:8px}
    .howto li{font-size:.82rem;color:var(--text-soft);line-height:1.5}
    .howto code{background:var(--bg);border:1px solid var(--border2);
                border-radius:4px;padding:1px 6px;font-size:.8rem;
                color:#93C5FD;font-family:'Courier New',monospace}
    .howto pre{background:var(--bg);border:1px solid var(--border2);
               border-radius:8px;padding:14px 16px;font-size:.79rem;
               overflow-x:auto;color:#93C5FD;margin-top:10px;line-height:1.8;
               font-family:'Courier New',monospace}

    /* ── Separator ── */
    .sep{border:none;border-top:1px solid var(--border2);margin:20px 0}

    @media(max-width:600px){
      .header{flex-direction:column}
      .stats{flex-direction:column}
      .mig{flex-direction:column;gap:8px}
      .mig-actions{flex-direction:row}
    }
  </style>
</head>
<body>
<div class="page">

  <!-- ── HEADER ──────────────────────────────────────────────── -->
  <div class="header">
    <div class="header-left">
      <h1>🗄️ Database Migrations</h1>
      <p>
        <strong style="color:var(--text-soft)"><?= DB_NAME ?></strong>
        &nbsp;·&nbsp; <?= date('Y-m-d H:i:s') ?>
        &nbsp;·&nbsp; <?= $totalCount ?> migration<?= $totalCount !== 1?'s':'' ?> defined
      </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a href="<?= BASE_URL ?>/" class="btn btn-ghost" style="font-size:.78rem">← Back to Site</a>
      <a href="<?= BASE_URL ?>/admin/" class="btn btn-ghost" style="font-size:.78rem">Admin Panel</a>
    </div>
  </div>

  <!-- ── STATS ────────────────────────────────────────────────── -->
  <div class="stats">
    <div class="stat blue">
      <div class="stat-val"><?= $totalCount ?></div>
      <div class="stat-key">Total Migrations</div>
    </div>
    <div class="stat green">
      <div class="stat-val"><?= $ranCount ?></div>
      <div class="stat-key">Already Applied</div>
    </div>
    <div class="stat orange">
      <div class="stat-val"><?= $pendingCount ?></div>
      <div class="stat-key">Pending</div>
    </div>
  </div>

  <!-- ── NOTICES ──────────────────────────────────────────────── -->
  <?php if (!empty($results)): ?>
  <div class="notice notice-ok">
    <span class="notice-ico">✅</span>
    <div>
      <strong>Migration complete.</strong>
      Results are shown below inside each migration row.
    </div>
  </div>
  <?php endif; ?>

  <div class="notice notice-warn">
    <span class="notice-ico">⚠️</span>
    <div>
      <strong>Security:</strong>
      Restrict or remove <code>migrate.php</code> in production, or protect it behind
      a server-level password. This file can alter your database schema.
    </div>
  </div>

  <!-- ── RUN ALL PENDING ──────────────────────────────────────── -->
  <?php if ($pendingCount > 0): ?>
  <div class="card" style="border-color:var(--blue);border-left:3px solid var(--blue)">
    <div class="card-head">
      <div class="card-title">⚡ <?= $pendingCount ?> pending migration<?= $pendingCount!==1?'s':''?></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <form method="POST">
          <input type="hidden" name="action"  value="run">
          <input type="hidden" name="migrate" value="all">
          <button type="submit" class="btn btn-green">
            ▶ Run All Pending (<?= $pendingCount ?>)
          </button>
        </form>
        <form method="POST"
              onsubmit="return confirm('Reset admin account to admin@africanattire.com / Admin@1234?')">
          <input type="hidden" name="action" value="reset_admin">
          <button type="submit" class="btn"
                  style="background:#dc3545;color:#fff;border-color:#dc3545">
            🔑 Reset Admin Password
          </button>
        </form>
      </div>
    </div>
    <p style="font-size:.82rem;color:var(--text-muted)">
      These migrations have not been applied yet. Run them all at once, or
      run them individually below. Each migration is tracked and will only run once.
    </p>
  </div>
  <?php else: ?>
  <div class="notice notice-ok" style="margin-bottom:20px">
    <span class="notice-ico">🎉</span>
    <div><strong>All migrations applied.</strong> Your database is up to date.</div>
  </div>
  <?php endif; ?>

  <!-- ── ADMIN RESET (always available) ───────────────────────── -->
  <div class="card" style="border-left:4px solid #dc3545;margin-bottom:14px;padding:14px 16px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
      <div>
        <div style="font-weight:700;margin-bottom:3px">🔑 Reset Admin Password</div>
        <div style="font-size:.82rem;color:var(--text-muted)">
          Resets admin account to <code>admin@africanattire.com</code> / <code>Admin@1234</code>.
          Use if you cannot log in to the admin portal.
        </div>
      </div>
      <form method="POST"
            onsubmit="return confirm('Reset admin account password to Admin@1234?')">
        <input type="hidden" name="action" value="reset_admin">
        <button type="submit" class="btn"
                style="background:#dc3545;color:#fff;border-color:#dc3545;white-space:nowrap">
          🔑 Reset Admin Password
        </button>
      </form>
    </div>
  </div>

  <!-- ── PENDING MIGRATIONS ───────────────────────────────────── -->
  <?php if ($pendingCount > 0): ?>
  <div class="card-title" style="margin-bottom:10px;padding:4px 0">
    ⏳ Pending Migrations
  </div>
  <?php foreach ($pending as $mig): ?>
  <div class="mig">
    <div class="mig-num"><?= htmlspecialchars($mig['id']) ?></div>
    <div class="mig-body">
      <div class="mig-title"><?= htmlspecialchars($mig['title']) ?></div>
      <div class="mig-desc"><?= htmlspecialchars($mig['description'] ?? '') ?></div>
      <div class="mig-meta">
        <?= count($mig['steps']) ?> step<?= count($mig['steps'])!==1?'s':'' ?>
        &nbsp;·&nbsp;
        <?php
        $safeCount = count(array_filter($mig['steps'], function($s) { return !empty($s['safe']); }));
        if ($safeCount > 0): ?>
          <?= $safeCount ?> step<?= $safeCount!==1?'s':'' ?> are non-critical (safe to fail)
        <?php endif; ?>
      </div>
      <!-- Step results (shown after run) -->
      <?php if (isset($results[$mig['id']])): ?>
      <div class="step-results">
        <?php foreach ($results[$mig['id']] as $sid => $r): ?>
        <div class="step-item">
          <span class="step-ico">
            <?php if ($r['ok'] === true):  ?>✅
            <?php elseif ($r['ok'] === 'safe'): ?>⚠️
            <?php else: ?>❌
            <?php endif; ?>
          </span>
          <span class="<?= $r['ok']===true?'step-ok':($r['ok']==='safe'?'step-safe':'step-fail') ?>">
            <strong><?= htmlspecialchars($sid) ?>:</strong>
            <?= htmlspecialchars($r['msg']) ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="mig-actions">
      <span class="badge b-pending">⏳ Pending</span>
      <form method="POST">
        <input type="hidden" name="action"  value="run">
        <input type="hidden" name="migrate" value="<?= htmlspecialchars($mig['id']) ?>">
        <button type="submit" class="btn btn-blue btn-sm">▶ Run</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
  <hr class="sep">
  <?php endif; ?>

  <!-- ── APPLIED MIGRATIONS ───────────────────────────────────── -->
  <?php if ($ranCount > 0): ?>
  <div class="card-title" style="margin-bottom:10px;padding:4px 0">
    ✅ Applied Migrations (<?= $ranCount ?>)
  </div>
  <?php foreach ($allMigrations as $mig):
    if (!in_array($mig['id'], $ranIds)) continue;
    $record = $ranMap[$mig['id']];
  ?>
  <div class="mig" style="opacity:.75">
    <div class="mig-num"><?= htmlspecialchars($mig['id']) ?></div>
    <div class="mig-body">
      <div class="mig-title"><?= htmlspecialchars($mig['title']) ?></div>
      <div class="mig-desc"><?= htmlspecialchars($mig['description'] ?? '') ?></div>
      <div class="mig-meta">
        Applied: <?= htmlspecialchars($record['ran_at']) ?>
        &nbsp;·&nbsp; <?= count($mig['steps']) ?> steps
      </div>
    </div>
    <div class="mig-actions">
      <?php if ($record['status'] === 'partial'): ?>
        <span class="badge b-partial">⚠ Partial</span>
      <?php elseif ($record['status'] === 'failed'): ?>
        <span class="badge b-failed">✗ Failed</span>
      <?php else: ?>
        <span class="badge b-ran">✓ Applied</span>
      <?php endif; ?>
      <!-- Re-run option (useful if partial/failed) -->
      <form method="POST" onsubmit="return confirm('Re-run migration <?= htmlspecialchars($mig['id']) ?>?\nThis will mark it as pending and run again.')">
        <input type="hidden" name="action"       value="rollback">
        <input type="hidden" name="migration_id" value="<?= htmlspecialchars($mig['id']) ?>">
        <button type="submit" class="btn btn-ghost btn-sm" title="Mark as pending and re-run">↩ Re-run</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
  <hr class="sep">
  <?php endif; ?>

  <!-- ── HOW TO ADD A MIGRATION ───────────────────────────────── -->
  <div class="howto">
    <h3>📖 How to Add a New Migration</h3>
    <ol>
      <li>
        Create a new file in <code>migrations/</code> — name it with the next
        number prefix, e.g. <code>006_my_change.php</code>
      </li>
      <li>
        Return an array with <code>id</code>, <code>title</code>,
        <code>description</code>, and <code>steps[]</code>.
        Each step needs a <code>label</code> and <code>sql</code>.
        Add <code>'safe' => true</code> for steps that are allowed to fail
        (e.g. adding an index that might already exist).
      </li>
      <li>
        Visit <code>/migrate.php</code> in your browser and click
        <strong>Run All Pending</strong>.
      </li>
      <li>
        The migration runs exactly <strong>once</strong> and is recorded in the
        <code>migrations</code> table. Re-visiting migrate.php is always safe.
      </li>
    </ol>

    <pre>
&lt;?php
// migrations/006_example.php
return [
    'id'          =&gt; '006',
    'title'       =&gt; 'Short description of the change',
    'description' =&gt; 'Longer explanation shown in the dashboard.',
    'steps'       =&gt; [

        '006a' =&gt; [
            'label' =&gt; 'Add column to products',
            'sql'   =&gt; "ALTER TABLE `products`
                        ADD COLUMN `colour` VARCHAR(50) NULL AFTER `sizes`",
            'safe'  =&gt; true,  // won't fail if column already exists
        ],

        '006b' =&gt; [
            'label' =&gt; 'Backfill default colour value',
            'sql'   =&gt; "UPDATE `products` SET `colour` = 'Multicolour'
                        WHERE `colour` IS NULL",
        ],

    ],
];
    </pre>
  </div>

</div><!-- /page -->
</body>
</html>
