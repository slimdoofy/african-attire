<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (Auth::check()) {
    auditLog('admin_logout', 'Admin logged out', '', 0, 'admin');
}
Auth::logout();
redirect(BASE_URL . '/admin/login.php');
