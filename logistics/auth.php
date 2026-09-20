<?php
/**
 * logistics/auth.php — Session auth for logistics portal users
 * Separate from the main platform Auth class.
 */

function logAuth(): array {
    Auth::start();
    return [
        'id'         => $_SESSION['log_uid']     ?? null,
        'company_id' => $_SESSION['log_cid']     ?? null,
        'name'       => $_SESSION['log_name']    ?? null,
        'email'      => $_SESSION['log_email']   ?? null,
        'role'       => $_SESSION['log_role']    ?? null,
        'temp_pw'    => $_SESSION['log_temp_pw'] ?? false,
    ];
}

function logCheck(): bool {
    Auth::start();
    return !empty($_SESSION['log_uid']);
}

function logRequire(): void {
    if (!logCheck()) {
        header('Location: ' . BASE_URL . '/logistics/login.php');
        exit;
    }
    // Force password change for temp passwords
    if (!empty($_SESSION['log_temp_pw'])) {
        $page = basename($_SERVER['PHP_SELF']);
        if ($page !== 'change-password.php' && $page !== 'login.php') {
            header('Location: ' . BASE_URL . '/logistics/change-password.php');
            exit;
        }
    }
}

function logLogin(array $user): void {
    Auth::start();
    $_SESSION['log_uid']     = $user['id'];
    $_SESSION['log_cid']     = $user['company_id'];
    $_SESSION['log_name']    = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['log_email']   = $user['email'];
    $_SESSION['log_role']    = $user['role'];
    $_SESSION['log_temp_pw'] = (bool)$user['temp_password'];
}

function logLogout(): void {
    Auth::start();
    foreach (['log_uid','log_cid','log_name','log_email','log_role','log_temp_pw'] as $k) {
        unset($_SESSION[$k]);
    }
}
