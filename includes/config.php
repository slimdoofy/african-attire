<?php
// ============================================================
// includes/config.php — African Attire Platform Configuration
// ============================================================

// ─── Database ───────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'african_attire');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ─── Paths ───────────────────────────────────────────────────
define('BASE_URL',    'http://local.africanattire');  // No trailing slash
define('BASE_PATH',   dirname(__DIR__));                   // /path/to/african-attire
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads');

// ── Ensure upload subdirectories exist and are writable ─────
(function() {
    $dirs = ['banners','general','products','shops'];
    foreach ($dirs as $d) {
        $path = UPLOAD_PATH . '/' . $d;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        // Always attempt to ensure correct permissions
        @chmod($path, 0755);
    }
})();
define('UPLOAD_URL',  BASE_URL  . '/assets/uploads');

// ─── App ─────────────────────────────────────────────────────
define('SESSION_NAME', 'aa_sess');
define('SITE_NAME',    'African Attire');
define('VERSION',      '1.0.0');

// ─── Error Handling (disable in production) ───────────────────
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ─── Timezone ────────────────────────────────────────────────
date_default_timezone_set('Africa/Lagos');
