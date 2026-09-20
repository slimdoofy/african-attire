<?php
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$cur   = strtolower(trim($input['currency'] ?? $_POST['currency'] ?? 'ngn'));

$enabled = enabledCurrencies();
if (!isset($enabled[$cur])) {
    echo json_encode(['ok'=>false,'message'=>'Currency not available']);
    exit;
}

Auth::start();
$_SESSION['currency'] = $cur;

echo json_encode([
    'ok'       => true,
    'currency' => $cur,
    'symbol'   => currencySymbol($cur),
    'rate'     => $cur === 'ngn' ? 1.0 : currencyRate($cur),
]);
