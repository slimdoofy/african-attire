<?php
/**
 * pay-callback.php — Paystack payment verification + order creation
 *
 * Paystack redirects here after payment with ?reference=REF
 * We verify the payment server-side (secret key → Paystack API).
 * Only on successful verification do we create the order.
 *
 * This page is safe to refresh — a double-spend check prevents
 * the same reference creating two orders.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

Auth::start();

$ref = trim($_GET['reference'] ?? '');

if (!$ref) {
    flash('Invalid payment reference.', 'error');
    redirect(BASE_URL . '/customer/checkout.php');
}

// ─── Retrieve pending order from session ────────────────────
if (empty($_SESSION['pending_order'])) {
    // If session expired but order already exists (e.g. page refresh), find it
    $existing = DB::fetch('SELECT * FROM orders WHERE payment_reference=?', [$ref]);
    if ($existing && $existing['payment_status'] === 'paid') {
        // Already processed — just show the confirmation
        if ($existing['guest_token']) {
            redirect(BASE_URL . '/customer/track-order.php?order=' .
                     urlencode($existing['order_number']) . '&token=' .
                     urlencode($existing['guest_token']));
        }
        redirect(BASE_URL . '/customer/orders.php');
    }
    flash('Your session expired. If you were charged, please contact support with reference: ' . htmlspecialchars($ref), 'error');
    redirect(BASE_URL . '/customer/checkout.php');
}

$pending = $_SESSION['pending_order'];

// ─── Double-spend guard: check if ref already used ──────────
$alreadyUsed = DB::fetch('SELECT id FROM orders WHERE payment_reference=?', [$ref]);
if ($alreadyUsed) {
    unset($_SESSION['pending_order']);
    flash('This payment has already been processed.', 'error');
    redirect(BASE_URL . '/customer/orders.php');
}

// ─── Verify with Paystack API ────────────────────────────────
$secretKey = getSetting('paystack_secret_key', 'sk_test_xxxxxxxxxxxxxxxx');

$ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($ref));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $secretKey,
        'Cache-Control: no-cache',
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 30,
]);
$raw      = curl_exec($ch);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    // Network error — don't create order, let them try again or contact support
    flash('Payment verification failed (network error). Please contact support with reference: ' . htmlspecialchars($ref), 'error');
    redirect(BASE_URL . '/customer/pay.php');
}

$res    = json_decode($raw, true);
$status = $res['data']['status']  ?? '';
$paidKobo = (int)($res['data']['amount'] ?? 0);
$expectedKobo = (int)round($pending['total'] * 100);

// ─── Payment must be "success" and amount must match ─────────
if ($status !== 'success') {
    flash('Payment was not successful (status: ' . htmlspecialchars($status) . '). No charge was made. Please try again.', 'error');
    redirect(BASE_URL . '/customer/pay.php');
}

if ($paidKobo < $expectedKobo) {
    // Amount mismatch — flag but still create order (Paystack already charged)
    // In production, log this for manual review
    $amountOk = false;
} else {
    $amountOk = true;
}

// ─── Payment verified — NOW create the order ─────────────────
$isGuest    = (bool)$pending['is_guest'];
$guestToken = $isGuest ? bin2hex(random_bytes(20)) : null;
$orderNo    = genOrderNumber();

// Capture exchange rate at time of order for admin audit
$rateAtOrder = usdRate();
$usdAmtAtOrder = $pending['total'] / $rateAtOrder;

$orderData = [
    'order_number'      => $orderNo,
    'user_id'           => $isGuest ? null : (int)$pending['user_id'],
    'total_amount'      => $pending['total'],
    'usd_rate'          => $rateAtOrder,
    'usd_amount'        => round($usdAmtAtOrder, 2),
    'delivery_fee'      => !empty($pending['delivery_fee'])    ? (float)$pending['delivery_fee']    : null,
    'discount_code'          => !empty($pending['discount_code'])   ? $pending['discount_code']          : null,
    'discount_amount'        => !empty($pending['discount_amount']) ? (float)$pending['discount_amount'] : null,
    'merchant_amount'        => null, // set after order items inserted
    'platform_markup_amount' => null,
    'delivery_address'  => $pending['address'],
    'payment_method'    => 'paystack',
    'payment_reference' => $ref,
    'payment_status'    => 'paid',
    'status'            => 'processing',
];
if ($isGuest) {
    $orderData['guest_name']  = $pending['guest_name'];
    $orderData['guest_email'] = $pending['guest_email'];
    $orderData['guest_phone'] = $pending['guest_phone'];
    $orderData['guest_token'] = $guestToken;
}
if (!$amountOk) {
    $orderData['notes'] = 'AMOUNT_MISMATCH: expected ' . $expectedKobo . ' kobo, got ' . $paidKobo;
}

$oid = DB::insert('orders', $orderData);

// ─── Record discount usage ───────────────────────────────────
if (!empty($pending['discount_id']) && !empty($pending['discount_amount'])) {
    $discId  = (int)$pending['discount_id'];
    $discAmt = (float)$pending['discount_amount'];
    $subtotl = (float)($pending['subtotal'] ?? $pending['total']);
    DB::insert('discount_usage', [
        'discount_id'     => $discId,
        'order_id'        => $oid,
        'order_number'    => $orderNo,
        'user_id'         => $isGuest ? null : (int)$pending['user_id'],
        'guest_email'     => $isGuest ? ($pending['guest_email'] ?? null) : null,
        'discount_amount' => $discAmt,
        'subtotal_before' => $subtotl,
    ]);
    DB::query(
        'UPDATE discount_codes SET uses_count = uses_count + 1 WHERE id = ?',
        [$discId]
    );
}

// ─── Insert order items + update stock/revenue ───────────────
$totalMerchantAmt = 0;
$totalMarkupAmt   = 0;
foreach ($pending['cart'] as $item) {
    $pid = (int)($item['product_id'] ?? 0);
    if (!$pid) continue;

    // Get the shop's actual markup rate first
    $shopMrk = DB::fetch('SELECT markup_rate FROM shops WHERE id=?', [$item['shop_id']]);
    $mrkRate = ($shopMrk && $shopMrk['markup_rate'] !== null)
        ? (float)$shopMrk['markup_rate']
        : (float)getSetting('platform_markup', 5);

    // Fetch the stored cost_price — this is what the merchant entered
    $prod   = DB::fetch('SELECT cost_price FROM products WHERE id=?', [$pid]);
    $costPx = ($prod && $prod['cost_price'] > 0)
        ? (float)$prod['cost_price']
        // Fallback: reverse the actual markup rate from the sale price
        // price = cost × (1 + markup/100)  →  cost = price / (1 + markup/100)
        : round((float)$item['price'] / (1 + $mrkRate / 100), 2);

    DB::insert('order_items', [
        'order_id'     => $oid,
        'product_id'   => $pid,
        'shop_id'      => (int)$item['shop_id'],
        'product_name' => $item['name'],
        'price'        => $item['price'],
        'cost_price'   => $costPx,
        'markup_rate'  => $mrkRate,
        'quantity'     => (int)$item['quantity'],
        'size'         => $item['size'] ?? null,
        'status'       => 'processing',
    ]);

    $qty = (int)$item['quantity'];
    $totalMerchantAmt += $costPx * $qty;
    $totalMarkupAmt   += ((float)$item['price'] - $costPx) * $qty;

    DB::query(
        'UPDATE products SET quantity = quantity - ?, sales_count = sales_count + ? WHERE id = ?',
        [$qty, $qty, $pid]
    );
    // total_revenue = sum of cost_price (what merchant earns)
    DB::query(
        'UPDATE shops SET total_revenue = total_revenue + ? WHERE id = ?',
        [$costPx * $qty, (int)$item['shop_id']]
    );
}

// Update order with merchant payout amount and platform markup earned
DB::update('orders', [
    'merchant_amount'        => round($totalMerchantAmt, 2),
    'platform_markup_amount' => round($totalMarkupAmt, 2),
], 'id=?', [$oid]);

// ─── Clear cart ───────────────────────────────────────────────
if ($isGuest) {
    $gsid = $pending['session_id'] ?? '';
    if ($gsid) DB::query('DELETE FROM guest_cart_items WHERE session_id=?', [$gsid]);
} else {
    DB::query('DELETE FROM cart_items WHERE user_id=?', [(int)$pending['user_id']]);
    // In-app notification for logged-in users
    DB::insert('notifications', [
        'user_id' => (int)$pending['user_id'],
        'type'    => 'order_placed',
        'title'   => "Order {$orderNo} confirmed! 🎉",
        'message' => "Payment received. Your order is now being processed. Total: " . money($pending['total']),
        'link'    => BASE_URL . '/customer/orders.php',
    ]);
}

// ─── Send order confirmation email ────────────────────────────
// Fetch the saved order and its items for the email
$savedOrder = DB::fetch('SELECT * FROM orders WHERE id=?', [$oid]);
$savedItems = DB::fetchAll(
    'SELECT oi.*, s.shop_name
     FROM order_items oi
     JOIN shops s ON s.id = oi.shop_id
     WHERE oi.order_id = ?',
    [$oid]
);

if ($savedOrder && !empty($savedItems)) {
    if ($isGuest) {
        $toEmail  = $pending['guest_email'];
        $toName   = $pending['guest_name'];
        $trackUrl = BASE_URL . '/customer/track-order.php?order='
                  . urlencode($orderNo) . '&token=' . urlencode($guestToken);
    } else {
        $user     = DB::fetch('SELECT name, email FROM users WHERE id=?', [(int)$pending['user_id']]);
        $toEmail  = $user['email'] ?? '';
        $toName   = $user['name']  ?? '';
        $trackUrl = null; // members use orders.php
    }

    if ($toEmail) {
        sendOrderConfirmationEmail($savedOrder, $savedItems, $toEmail, $toName, $trackUrl);
    }

    // ── Notify each merchant whose products are in this order ──────
    // Group items by shop_id so each merchant gets one email
    $itemsByShop = [];
    foreach ($savedItems as $si) {
        $itemsByShop[$si['shop_id']][] = $si;
    }
    foreach ($itemsByShop as $shopId => $shopItems) {
        $shop = DB::fetch(
            'SELECT s.shop_name, u.email, u.name
             FROM shops s JOIN users u ON u.id = s.user_id
             WHERE s.id = ?',
            [$shopId]
        );
        if ($shop && !empty($shop['email'])) {
            $custName = $isGuest
                ? ($pending['guest_name'] ?? 'Guest Customer')
                : ($user['name'] ?? 'Customer');
            sendMerchantOrderEmail(
                $savedOrder,
                $shopItems,
                $shop['email'],
                $shop['name'],
                $custName
            );
        }
    }
    // Note: email failures are non-blocking — order is already confirmed.
}

// ─── Clear session pending order ──────────────────────────────
unset($_SESSION['pending_order']);

// ─── Redirect to confirmation ─────────────────────────────────
if ($isGuest) {
    Auth::start();
    $_SESSION['last_guest_order'] = [
        'number' => $orderNo,
        'token'  => $guestToken,
        'email'  => $pending['guest_email'],
    ];
    flash("✅ Payment confirmed! Order {$orderNo} placed. Check your email for tracking details.", 'success');
    redirect(BASE_URL . '/customer/track-order.php?order=' . urlencode($orderNo) . '&token=' . urlencode($guestToken));
} else {
    flash("✅ Payment confirmed! Order {$orderNo} is now being processed.", 'success');
    redirect(BASE_URL . '/customer/orders.php');
}
