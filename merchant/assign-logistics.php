<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/logistics_fee.php';
Auth::requireRole('merchant');
$_shop = DB::fetch('SELECT * FROM shops WHERE user_id=?', [Auth::id()]);
if (!$_shop) redirect(BASE_URL . '/merchant/register.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL . '/merchant/orders.php');

$oid     = (int)($_POST['order_id']      ?? 0);
$cid     = (int)($_POST['company_id']    ?? 0);
$trackNo = trim($_POST['tracking_number'] ?? '');
$notes   = trim($_POST['notes']           ?? '');
$sid     = $_shop['id'];

// Verify the order belongs to this merchant
$order = DB::fetch(
    "SELECT o.* FROM orders o
     JOIN order_items oi ON oi.order_id=o.id
     WHERE o.id=? AND oi.shop_id=? LIMIT 1",
    [$oid, $sid]
);
if (!$order) { flash('Order not found.', 'error'); redirect(BASE_URL . '/merchant/orders.php'); }

$company = DB::fetch('SELECT * FROM logistics_companies WHERE id=? AND status="active"', [$cid]);
if (!$company) { flash('Logistics partner not found or inactive.', 'error'); redirect(BASE_URL . '/merchant/order-detail.php?id='.$oid); }

// Check not already assigned
$existing = DB::fetch('SELECT id FROM order_logistics WHERE order_id=? AND shop_id=?', [$oid, $sid]);
if ($existing) { flash('This order is already assigned to a logistics partner.', 'error'); redirect(BASE_URL . '/merchant/order-detail.php?id='.$oid); }

// Calculate logistics fee
$merchantCity = $_shop['pickup_city'] ?: ($_shop['city'] ?: '');
$feeResult = calculateLogisticsFee($merchantCity, $order['delivery_address']);

DB::insert('order_logistics', [
    'order_id'        => $oid,
    'shop_id'         => $sid,
    'company_id'      => $cid,
    'assigned_by'     => Auth::id(),
    'tracking_number' => $trackNo ?: null,
    'notes'           => $notes ?: null,
    'status'          => 'assigned',
    'distance_km'     => $feeResult['distance_km'],
    'fee_ngn'         => $feeResult['fee_ngn'],
    'fee_usd'         => $feeResult['fee_usd'],
    'fee_method'      => $feeResult['method'],
]);

// Update order status to processing if still pending
if ($order['status'] === 'pending') {
    DB::update('orders', ['status' => 'processing'], 'id=?', [$oid]);
}

// Email the logistics company
$items = DB::fetchAll(
    'SELECT oi.* FROM order_items oi WHERE oi.order_id=? AND oi.shop_id=?',
    [$oid, $sid]
);
// Get primary logistics user email
$logUser = DB::fetch(
    "SELECT email, CONCAT(first_name,' ',last_name) name
     FROM logistics_users WHERE company_id=? AND role='admin' LIMIT 1",
    [$cid]
);
$loginUrl = BASE_URL . '/logistics/login.php';

if ($logUser) {
    sendLogisticsOrderAssignedEmail(
        $order, $items,
        $logUser['email'], $logUser['name'],
        $_shop['shop_name'],
        $loginUrl
    );
}

flash("Order assigned to {$company['company_name']}. Logistics fee: ₦".number_format($feeResult['fee_ngn'],2)." ({$feeResult['method']}). Partner notified by email.", 'success');
redirect(BASE_URL . '/merchant/order-detail.php?id=' . $oid);
