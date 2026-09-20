<?php
/**
 * fix-uploads.php — Upload directory permission check & repair tool
 * Access: http://yourdomain.com/fix-uploads.php
 * DELETE this file after use in production.
 */
define('BASE_PATH', __DIR__);
$uploadBase = BASE_PATH . '/assets/uploads';
$dirs = ['banners','general','products','shops'];

$results = [];
foreach ($dirs as $d) {
    $path    = $uploadBase . '/' . $d;
    $exists  = is_dir($path);
    $writable= $exists && is_writable($path);
    $fixed   = false;
    $error   = '';

    if (!$exists) {
        if (mkdir($path, 0755, true)) {
            $exists = true; $fixed = true;
        } else {
            $error = 'Could not create directory.';
        }
    }

    if ($exists && !$writable) {
        if (@chmod($path, 0755)) {
            $writable = is_writable($path);
            $fixed    = true;
        }
        if (!$writable) {
            $error = 'chmod failed — may need server-level fix (cPanel / FTP).';
        }
    }

    // Try writing a test file
    $testFile  = $path . '/.write_test';
    $canWrite  = false;
    if ($writable) {
        $canWrite = (file_put_contents($testFile, 'test') !== false);
        if ($canWrite) @unlink($testFile);
    }

    $results[$d] = compact('path','exists','writable','canWrite','fixed','error');
}

// Also check PHP upload settings
$phpSettings = [
    'file_uploads'       => ini_get('file_uploads'),
    'upload_max_filesize'=> ini_get('upload_max_filesize'),
    'post_max_size'      => ini_get('post_max_size'),
    'max_file_uploads'   => ini_get('max_file_uploads'),
    'upload_tmp_dir'     => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
];
$tmpWritable = is_writable($phpSettings['upload_tmp_dir']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload Directory Fix — African Attire</title>
  <style>
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;max-width:720px;margin:40px auto;padding:0 20px;background:#f5f5f5;color:#333}
    h1{font-size:1.4rem;margin-bottom:4px}
    .card{background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:14px;box-shadow:0 1px 4px rgba(0,0,0,.1)}
    .ok  {color:#1B5E20;font-weight:700}
    .err {color:#B71C1C;font-weight:700}
    .warn{color:#E65100;font-weight:700}
    table{width:100%;border-collapse:collapse;font-size:.88rem}
    th{text-align:left;padding:7px 10px;background:#f0f0f0;border-bottom:2px solid #ddd}
    td{padding:7px 10px;border-bottom:1px solid #eee;vertical-align:middle}
    code{background:#f0f0f0;padding:2px 6px;border-radius:4px;font-size:.82rem}
    .tag{display:inline-block;padding:2px 8px;border-radius:999px;font-size:.72rem;font-weight:700}
    .tag-ok {background:#E8F5E9;color:#1B5E20}
    .tag-err{background:#FFEBEE;color:#B71C1C}
    .tag-fix{background:#FFF8E1;color:#E65100}
  </style>
</head>
<body>
<h1>🔧 Upload Directory Diagnostics</h1>
<p style="color:#666;font-size:.86rem;margin-bottom:20px">
  Checks and attempts to fix write permissions on the upload subdirectories.
  <strong style="color:#B71C1C">Delete this file after use.</strong>
</p>

<div class="card">
  <h3 style="margin:0 0 10px">📂 Upload Directories</h3>
  <table>
    <thead>
      <tr><th>Directory</th><th>Exists</th><th>Writable</th><th>Write Test</th><th>Notes</th></tr>
    </thead>
    <tbody>
      <?php foreach ($results as $d => $r): ?>
      <tr>
        <td><code>assets/uploads/<?= $d ?>/</code></td>
        <td><?= $r['exists'] ? '<span class="tag tag-ok">✓ Yes</span>' : '<span class="tag tag-err">✗ No</span>' ?></td>
        <td><?= $r['writable'] ? '<span class="tag tag-ok">✓ Yes</span>' : '<span class="tag tag-err">✗ No</span>' ?></td>
        <td><?= $r['canWrite'] ? '<span class="tag tag-ok">✓ Pass</span>' : '<span class="tag tag-err">✗ Fail</span>' ?></td>
        <td style="font-size:.78rem">
          <?= $r['fixed'] ? '<span class="warn">⚡ Fixed automatically</span> ' : '' ?>
          <?= $r['error'] ? '<span class="err">⚠ '.$r['error'].'</span>' : '' ?>
          <?= !$r['error'] && $r['canWrite'] ? '<span class="ok">All good</span>' : '' ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <h3 style="margin:0 0 10px">⚙️ PHP Upload Settings</h3>
  <table>
    <tbody>
      <?php foreach ($phpSettings as $k => $v): ?>
      <tr><td style="width:50%"><code><?= $k ?></code></td><td><?= htmlspecialchars($v) ?></td></tr>
      <?php endforeach; ?>
      <tr>
        <td><code>upload_tmp_dir writable</code></td>
        <td><?= $tmpWritable ? '<span class="ok">✓ Yes</span>' : '<span class="err">✗ No — uploads will fail</span>' ?></td>
      </tr>
    </tbody>
  </table>
</div>

<?php $allOk = array_reduce($results, function($c,$r){ return $c && $r['canWrite']; }, true); ?>
<?php if ($allOk): ?>
<div style="background:#E8F5E9;border:1px solid #A5D6A7;border-radius:8px;padding:14px 16px;color:#1B5E20;font-weight:700">
  ✅ All upload directories are writable. File uploads should work correctly.
</div>
<?php else: ?>
<div style="background:#FFEBEE;border:1px solid #EF9A9A;border-radius:8px;padding:14px 16px;color:#B71C1C">
  <strong>⚠️ Some directories are not writable.</strong>
  <p style="margin:8px 0 0;font-size:.86rem;color:#333">
    If <code>chmod</code> failed, the directories may be owned by root or a different system user.
    Fix via <strong>cPanel → File Manager</strong> or FTP:
    set permissions to <strong>755</strong> on each directory.
    <br><br>
    Alternatively, ask your hosting provider to make
    <code><?= htmlspecialchars($uploadBase) ?></code> and its subdirectories writable by the web server.
  </p>
</div>
<?php endif; ?>

<p style="margin-top:16px;font-size:.78rem;color:#999;text-align:center">
  ⚠️ Delete <code>fix-uploads.php</code> from your server after resolving the issue.
</p>
</body>
</html>
