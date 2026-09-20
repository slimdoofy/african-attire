<?php
// includes/auth.php — Session & Authentication
// Each portal (customer, merchant, admin) uses its OWN session key.
// A merchant session cannot satisfy a customer auth check, and vice versa.

class Auth {

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => 86400 * 30,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function login(array $user): void {
        self::start();
        $_SESSION['uid']   = (int)$user['id'];
        $_SESSION['uname'] = $user['name'];
        $_SESSION['uemail']= $user['email'];
        $_SESSION['urole'] = $user['role'];
        session_regenerate_id(true);
    }

    public static function logout(): void {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool {
        self::start();
        return !empty($_SESSION['uid']);
    }

    /** Check only — does NOT enforce role mismatch */
    public static function user(): ?array {
        self::start();
        if (empty($_SESSION['uid'])) return null;
        return [
            'id'    => $_SESSION['uid'],
            'name'  => $_SESSION['uname'],
            'email' => $_SESSION['uemail'],
            'role'  => $_SESSION['urole'],
        ];
    }

    public static function id(): ?int {
        self::start();
        return $_SESSION['uid'] ?? null;
    }

    public static function role(): ?string {
        self::start();
        return $_SESSION['urole'] ?? null;
    }

    /**
     * Strict portal-aware auth.
     * 'customer' portal: only 'customer' role passes.
     * 'merchant' portal: only 'merchant' role passes.
     * 'admin'    portal: only 'admin' role passes.
     * Cross-portal sessions are silently cleared and redirected to the
     * correct portal login — no bleed-through.
     */
    public static function requireRole(string $role, string $redirect = ''): void {
        $loginMap = [
            'merchant' => '/merchant/login.php',
            'admin'    => '/admin/login.php',
            'customer' => '/customer/login.php',
        ];
        $loginPage = $redirect ?: ($loginMap[$role] ?? '/customer/login.php');

        self::start();

        // Not logged in at all
        if (empty($_SESSION['uid'])) {
            header('Location: ' . BASE_URL . $loginPage . '?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
            exit;
        }

        $currentRole = $_SESSION['urole'] ?? '';

        // Wrong portal — clear their session and redirect to the right login
        if ($currentRole !== $role) {
            // Preserve logistics session keys so logistics portal stays independent
            $logKeys = ['log_uid','log_cid','log_name','log_email','log_role','log_temp_pw'];
            $saved = [];
            foreach ($logKeys as $k) {
                if (isset($_SESSION[$k])) $saved[$k] = $_SESSION[$k];
            }
            self::logout();
            // Restart and restore logistics keys if any
            if (!empty($saved)) {
                self::start();
                foreach ($saved as $k => $v) $_SESSION[$k] = $v;
            }
            header('Location: ' . BASE_URL . $loginPage);
            exit;
        }
    }

    public static function hash(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 11]);
    }

    public static function verify(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    public static function csrf(): string {
        self::start();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function checkCsrf(string $token): bool {
        self::start();
        return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }
}
