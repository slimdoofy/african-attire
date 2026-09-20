<?php
// api/orders.php
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

function jsonOut(array $d): void { echo json_encode($d); exit; }

if (!Auth::check()) jsonOut(['success'=>false,'error'=>'Not authenticated.']);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$act  = $body['action'] ?? ($_POST['action'] ?? '');

// Merchant: update order status
if ($act === 'update_order' && Auth::role() === 'merchant') {
    $oid    = (int)($body['order_id'] ?? 0);
    $status = $body['status'] ?? '';
    $allowed = ['pending','processing','shipped','delivered','cancelled'];
    if (!in_array($status, $allowed, true)) jsonOut(['success'=>false,'error'=>'Invalid status.']);

    $shop   = DB::fetch('SELECT id FROM shops WHERE user_id=?',[Auth::id()]);
    if (!$shop) jsonOut(['success'=>false,'error'=>'Shop not found.']);

    // Verify this merchant has items in this order
    $hasItems = DB::count('SELECT COUNT(*) FROM order_items WHERE order_id=? AND shop_id=?',[$oid,$shop['id']]);
    if (!$hasItems) jsonOut(['success'=>false,'error'=>'Order not found.']);

    DB::update('orders',['status'=>$status],'id=?',[$oid]);
    DB::update('order_items',['status'=>$status],'order_id=? AND shop_id=?',[$oid,$shop['id']]);
    jsonOut(['success'=>true]);
}

// Merchant: update individual item status
if ($act === 'update_item' && Auth::role() === 'merchant') {
    $itemId = (int)($body['item_id'] ?? 0);
    $status = $body['status'] ?? '';
    $allowed= ['pending','processing','shipped','delivered','cancelled'];
    if (!in_array($status, $allowed, true)) jsonOut(['success'=>false,'error'=>'Invalid status.']);

    $shop = DB::fetch('SELECT id FROM shops WHERE user_id=?',[Auth::id()]);
    if (!$shop) jsonOut(['success'=>false,'error'=>'Shop not found.']);

    DB::update('order_items',['status'=>$status],'id=? AND shop_id=?',[$itemId,$shop['id']]);
    jsonOut(['success'=>true]);
}

// Customer: cancel order
if ($act === 'cancel' && Auth::role() === 'customer') {
    $oid   = (int)($body['order_id'] ?? $_POST['order_id'] ?? 0);
    $order = DB::fetch('SELECT * FROM orders WHERE id=? AND user_id=?',[$oid,Auth::id()]);
    if (!$order) jsonOut(['success'=>false,'error'=>'Order not found.']);
    if ($order['status'] !== 'pending') jsonOut(['success'=>false,'error'=>'Only pending orders can be cancelled.']);

    DB::update('orders',['status'=>'cancelled'],'id=?',[$oid]);
    DB::update('order_items',['status'=>'cancelled'],'order_id=?',[$oid]);

    // Restore stock
    $items = DB::fetchAll('SELECT * FROM order_items WHERE order_id=?',[$oid]);
    foreach ($items as $it) {
        DB::query('UPDATE products SET quantity=quantity+? WHERE id=?',[$it['quantity'],$it['product_id']]);
        // Subtract only cost_price (what was added at purchase), not sale price
        $costToReverse = isset($it['cost_price']) && $it['cost_price'] > 0
            ? (float)$it['cost_price'] * (int)$it['quantity']
            : (float)$it['price'] * (int)$it['quantity']; // legacy fallback
        DB::query('UPDATE shops SET total_revenue=total_revenue-? WHERE id=?',[$costToReverse,$it['shop_id']]);
    }

    if (isset($_POST['order_id'])) {
        flash('Order cancelled successfully.');
        header('Location: '.BASE_URL.'/customer/orders.php');
        exit;
    }
    jsonOut(['success'=>true]);
}

jsonOut(['success'=>false,'error'=>'Invalid action.']);
