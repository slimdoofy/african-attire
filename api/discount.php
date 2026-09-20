<?php
/**
 * api/discount.php — validate and apply a discount code
 * POST: {code, subtotal, shop_ids[]}
 * Returns: {ok, discount_id, type, value, amount_off, new_total, message}
 */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$code     = strtoupper(trim($input['code']     ?? ''));
$subtotal = (float)($input['subtotal']         ?? 0);
$shopIds  = array_map('intval', $input['shop_ids'] ?? []);
$delivFee = (float)($input['delivery_fee']     ?? 0);

if (!$code) { echo json_encode(['ok'=>false,'message'=>'Please enter a discount code.']); exit; }
if ($subtotal <= 0) { echo json_encode(['ok'=>false,'message'=>'Cart is empty.']); exit; }

$now = date('Y-m-d H:i:s');
$dc  = DB::fetch(
    "SELECT * FROM discount_codes
     WHERE code=? AND status='active'
     AND valid_from <= ? AND valid_until >= ?",
    [$code, $now, $now]
);

if (!$dc) {
    echo json_encode(['ok'=>false,'message'=>'This discount code is invalid or has expired.']);
    exit;
}

// Check scope — merchant code must match a shop in cart
if ($dc['scope'] === 'merchant' && $dc['shop_id']) {
    if (!in_array((int)$dc['shop_id'], $shopIds, true)) {
        echo json_encode(['ok'=>false,'message'=>'This code is only valid for a specific merchant not in your cart.']);
        exit;
    }
}

// Check usage limit
if ($dc['max_uses'] > 0 && $dc['uses_count'] >= $dc['max_uses']) {
    echo json_encode(['ok'=>false,'message'=>'This discount code has reached its maximum number of uses.']);
    exit;
}

// Check minimum order
if ($subtotal < (float)$dc['min_order_ngn']) {
    echo json_encode([
        'ok'      => false,
        'message' => 'Minimum order of ' . money($dc['min_order_ngn'], 'ngn') . ' required for this code.',
    ]);
    exit;
}

// Calculate discount amount
$amountOff = $dc['type'] === 'percent'
    ? round($subtotal * (float)$dc['value'] / 100, 2)
    : min((float)$dc['value'], $subtotal); // flat — can't exceed subtotal

$newSubtotal = max(0, $subtotal - $amountOff);
$newTotal    = $newSubtotal + $delivFee;

echo json_encode([
    'ok'          => true,
    'discount_id' => (int)$dc['id'],
    'code'        => $dc['code'],
    'type'        => $dc['type'],
    'value'       => (float)$dc['value'],
    'amount_off'  => $amountOff,
    'new_subtotal'=> $newSubtotal,
    'new_total'   => $newTotal,
    'description' => $dc['description'] ?: ($dc['type']==='percent'
        ? number_format($dc['value'],0).'% off'
        : '₦'.number_format($dc['value'],0).' off'),
    'message'     => 'Discount applied! ' . ($dc['type']==='percent'
        ? number_format($dc['value'],0).'% off'
        : '₦'.number_format($amountOff,0).' off'),
]);
