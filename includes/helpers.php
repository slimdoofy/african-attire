<?php
// includes/helpers.php — Utility Functions

// ─── String Helpers ──────────────────────────────────────────
function slug(string $s): string {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ─── Multi-Currency helpers ──────────────────────────────────

function supportedCurrencies(): array {
    return [
        'ngn' => ['symbol'=>'₦','name'=>'Nigerian Naira',  'rate_key'=>null,           'enabled_key'=>null,                  'default_rate'=>1.0,    'always_on'=>true],
        'usd' => ['symbol'=>'$',      'name'=>'US Dollar',       'rate_key'=>'usd_ngn_rate', 'enabled_key'=>null,                  'default_rate'=>1600.0, 'always_on'=>true],
        'eur' => ['symbol'=>'€','name'=>'Euro',            'rate_key'=>'eur_ngn_rate', 'enabled_key'=>'currency_eur_enabled','default_rate'=>1740.0, 'always_on'=>false],
        'gbp' => ['symbol'=>'£','name'=>'Pound Sterling',  'rate_key'=>'gbp_ngn_rate', 'enabled_key'=>'currency_gbp_enabled','default_rate'=>2030.0, 'always_on'=>false],
    ];
}

function enabledCurrencies(): array {
    $all = supportedCurrencies();
    $out = [];
    foreach ($all as $code => $cfg) {
        if ($cfg['always_on']) { $out[$code] = $cfg; continue; }
        if ((int)getSetting($cfg['enabled_key'], '1') === 1) $out[$code] = $cfg;
    }
    return $out;
}

function currencyRate(string $code): float {
    $code = strtolower($code);
    if ($code === 'ngn') return 1.0;
    $all = supportedCurrencies();
    if (!isset($all[$code])) return 1.0;
    $key     = $all[$code]['rate_key'];
    $default = $all[$code]['default_rate'];
    $rate    = (float)(getSetting($key, (string)$default) ?: $default);
    return $rate > 0 ? $rate : $default;
}

function activeCurrency(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $c = strtolower($_SESSION['currency'] ?? 'ngn');
    $enabled = enabledCurrencies();
    return isset($enabled[$c]) ? $c : 'ngn';
}

function usdRate(): float { return currencyRate('usd'); }

function currencySymbol(string $code): string {
    $map = ['ngn'=>'₦','usd'=>'$','eur'=>'€','gbp'=>'£'];
    return html_entity_decode($map[strtolower($code)] ?? '₦', ENT_QUOTES, 'UTF-8');
}

function money(float $amount, string $forceCurrency = ''): string {
    $cur  = strtolower($forceCurrency ?: activeCurrency());
    if ($cur === 'ngn') return '₦' . number_format($amount, 2);
    $rate = currencyRate($cur);
    $sym  = currencySymbol($cur);
    return $sym . number_format($amount / $rate, 2);
}

function moneyNgn(float $amount): string { return '₦' . number_format($amount, 2); }
function moneyUsd(float $amount): string { return '$' . number_format($amount / currencyRate('usd'), 2); }

function toNgn(float $amount, string $currency): float {
    $currency = strtolower($currency);
    if ($currency === 'ngn') return round($amount, 2);
    return round($amount * currencyRate($currency), 2);
}

// ─── Redirect ────────────────────────────────────────────────
function redirect(string $url): void {
    // Discard any buffered output so the Location header can be sent cleanly
    if (ob_get_level() > 0) ob_end_clean();
    header('Location: ' . $url);
    exit;
}

// ─── Flash Messages ──────────────────────────────────────────
function flash(string $msg, string $type = 'success'): void {
    Auth::start();
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function getFlashes(): array {
    Auth::start();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ─── File Upload ─────────────────────────────────────────────
function uploadFile(array $file, string $subdir = 'general'): ?string {
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'] ?? '', $allowed, true)) return null;
    if (($file['size'] ?? 0) > 5 * 1024 * 1024)         return null; // 5 MB
    if (($file['error'] ?? 1) !== UPLOAD_ERR_OK)          return null;

    $dir = UPLOAD_PATH . '/' . $subdir;

    // Create directory if it doesn't exist
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            error_log("uploadFile: failed to create directory: $dir");
            return null;
        }
    }

    // Ensure the directory is writable — try chmod if not
    if (!is_writable($dir)) {
        @chmod($dir, 0755);
        if (!is_writable($dir)) {
            error_log("uploadFile: directory not writable: $dir");
            return null;
        }
    }

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    // Normalise extension from MIME type to be safe
    $mimeToExt = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    if (isset($mimeToExt[$file['type']])) $ext = $mimeToExt[$file['type']];

    $name = uniqid('img_', true) . '.' . $ext;
    $dest = $dir . '/' . $name;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        @chmod($dest, 0644); // ensure file is readable
        return $subdir . '/' . $name;
    }

    error_log("uploadFile: move_uploaded_file failed. tmp={$file['tmp_name']} dest=$dest");
    return null;
}

// ─── Image URL ───────────────────────────────────────────────
function imgUrl(?string $path, string $fallback = ''): string {
    if (!$path) return $fallback ?: BASE_URL . '/assets/images/placeholder.svg';
    if (strncmp($path, 'http://', 7) === 0 || strncmp($path, 'https://', 8) === 0) return $path;
    return UPLOAD_URL . '/' . ltrim($path, '/');
}

// ─── Time ────────────────────────────────────────────────────
function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

// ─── Status Badge ────────────────────────────────────────────
function statusBadge(string $status): string {
    $map = [
        'pending'      => 'badge-warning',
        'approved'     => 'badge-success',
        'active'       => 'badge-success',
        'paid'         => 'badge-success',
        'delivered'    => 'badge-success',
        'resolved'     => 'badge-success',
        'processing'   => 'badge-info',
        'shipped'      => 'badge-info',
        'under_review' => 'badge-info',
        'rejected'     => 'badge-danger',
        'suspended'    => 'badge-danger',
        'cancelled'    => 'badge-danger',
        'failed'       => 'badge-danger',
        'refunded'     => 'badge-danger',
        'open'         => 'badge-danger',
        'disputed'     => 'badge-danger',
        'archived'     => 'badge-muted',
        'closed'       => 'badge-muted',
    ];
    $cls = $map[strtolower($status)] ?? 'badge-muted';
    return '<span class="badge ' . $cls . '">' . e(ucfirst($status)) . '</span>';
}

// ─── Settings ────────────────────────────────────────────────
function getSetting(string $key, string $default = ''): string {
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        $row = DB::fetch('SELECT `value` FROM settings WHERE `key` = ?', [$key]);
        $cache[$key] = $row['value'] ?? $default;
    }
    return $cache[$key] ?? $default;
}

// ─── Categories ──────────────────────────────────────────────
function getCategories(): array {
    static $cats = null;
    if ($cats === null) $cats = DB::fetchAll('SELECT * FROM categories ORDER BY sort_order');
    return $cats;
}

// ─── Cart count ──────────────────────────────────────────────
function cartCount(): int {
    if (!Auth::check()) return 0;
    return (int)DB::count(
        'SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id = ?',
        [Auth::id()]
    );
}

// ─── Notif count ─────────────────────────────────────────────
function notifCount(): int {
    if (!Auth::check()) return 0;
    return DB::count(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL',
        [Auth::id()]
    );
}

// ─── Order Number ────────────────────────────────────────────
function genOrderNumber(): string {
    return 'AA' . strtoupper(substr(uniqid(), -6)) . date('ymd');
}

// ─── Pagination ──────────────────────────────────────────────
function paginate(int $total, int $perPage, int $page, string $baseUrl = ''): string {
    if ($baseUrl === '') {
        // Build base URL from current request, stripping existing page param
        $qs = $_GET;
        unset($qs['page']);
        $baseUrl = '?' . http_build_query($qs);
        if ($baseUrl === '?') $baseUrl = '?';
    }
    $pages = (int)ceil($total / $perPage);
    if ($pages <= 1) return '';

    $sep = (strpos($baseUrl, '?') !== false) ? '&' : '?';
    $html = '<nav class="pagination-wrap"><ul class="pagination">';

    if ($page > 1) {
        $html .= '<li><a href="' . $baseUrl . $sep . 'page=' . ($page - 1) . '" class="page-link">‹</a></li>';
    }
    for ($i = 1; $i <= $pages; $i++) {
        $active = $i === $page ? ' active' : '';
        $html  .= '<li><a href="' . $baseUrl . $sep . 'page=' . $i . '" class="page-link' . $active . '">' . $i . '</a></li>';
    }
    if ($page < $pages) {
        $html .= '<li><a href="' . $baseUrl . $sep . 'page=' . ($page + 1) . '" class="page-link">›</a></li>';
    }

    return $html . '</ul></nav>';
}

// ─── Audit Logging ────────────────────────────────────────────

/**
 * Write an entry to the audit_log table.
 * Call this from any significant action (login, order status change, etc.)
 */
function auditLog(
    string $action,
    string $description = '',
    string $entityType  = '',
    int    $entityId    = 0,
    string $portal      = 'admin'
): void {
    $uid   = null; $email = null;
    if (Auth::check()) {
        $uid   = Auth::id();
        $u     = Auth::user();
        $email = $u['email'] ?? null;
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    try {
        DB::insert('audit_log', [
            'user_id'     => $uid,
            'user_email'  => $email,
            'portal'      => $portal,
            'action'      => $action,
            'entity_type' => $entityType ?: null,
            'entity_id'   => $entityId   ?: null,
            'description' => $description ?: null,
            'ip_address'  => $ip,
            'user_agent'  => $ua,
        ]);
    } catch (Exception $e) {
        // Silently fail — audit log must never break the main flow
    }
}
