<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::logout();
redirect(BASE_URL . '/customer/login.php');
