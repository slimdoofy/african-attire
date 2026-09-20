<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/auth.php';
logLogout();
redirect(BASE_URL . '/logistics/login.php');
