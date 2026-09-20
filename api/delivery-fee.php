<?php
/**
 * api/delivery-fee.php
 * AJAX endpoint: POST {address, shop_id?} → returns calculated delivery fee
 */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

$input   = json_decode(file_get_contents('php://input'), true) ?: [];
$address = trim($input['address'] ?? $_POST['address'] ?? '');
$shopIds = $input['shop_ids'] ?? []; // array of shop IDs in cart

if (!$address) {
    echo json_encode(['ok'=>false,'message'=>'Address required']);
    exit;
}

// Get unique pickup cities from shops in the cart
$pickupCities = [];
if (!empty($shopIds)) {
    $placeholders = implode(',', array_fill(0, count($shopIds), '?'));
    $shops = DB::fetchAll(
        "SELECT id, COALESCE(pickup_city, city) AS origin_city
         FROM shops WHERE id IN ($placeholders)",
        array_map('intval', $shopIds)
    );
    foreach ($shops as $sh) {
        if ($sh['origin_city']) $pickupCities[] = $sh['origin_city'];
    }
}

// Use most common / first pickup city, or Lagos as default
$originCity = !empty($pickupCities) ? $pickupCities[0] : 'Lagos';

$result = calculateLogisticsFee($originCity, $address);

echo json_encode([
    'ok'          => true,
    'fee_ngn'     => $result['fee_ngn'],
    'fee_usd'     => $result['fee_usd'],
    'distance_km' => $result['distance_km'],
    'method'      => $result['method'],
    'origin_city' => $result['origin_city'],
    'dest_city'   => $result['dest_city'],
    'breakdown'   => $result['breakdown'],
    'fee_ngn_fmt' => '₦' . number_format($result['fee_ngn'], 2),
    'fee_usd_fmt' => '$' . number_format($result['fee_usd'], 2),
]);
