<?php
// api/cart.php — Cart API (supports both logged-in users and guests)
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

function jsonOut(array $data): void { echo json_encode($data); exit; }

// ─── Helper: guest session ID ─────────────────────────────────
function guestSid(): string {
    Auth::start();
    if (empty($_SESSION['guest_cart_id'])) {
        $_SESSION['guest_cart_id'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['guest_cart_id'];
}

// ─── Cart source: logged-in vs guest ──────────────────────────
$isGuest = !Auth::check();
$uid     = $isGuest ? null : Auth::id();
$sid     = $isGuest ? guestSid() : null;

// ─── Count helper ──────────────────────────────────────────────
function cartCount2(bool $isGuest, ?int $uid, ?string $sid): int {
    if ($isGuest) {
        return (int)DB::count(
            'SELECT COALESCE(SUM(quantity),0) FROM guest_cart_items WHERE session_id=?', [$sid]);
    }
    return (int)DB::count(
        'SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id=?', [$uid]);
}

// ──────────────────────────────────────────────────────────────
// GET: list cart
// ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($isGuest) {
        $items = DB::fetchAll("
            SELECT ci.id, ci.quantity qty, ci.size, p.name, p.price,
                   s.shop_name shop, ci.quantity * p.price line_total,
                   (SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) image,
                   cat.icon icon
            FROM guest_cart_items ci
            JOIN products p    ON p.id  = ci.product_id
            JOIN shops    s    ON s.id  = p.shop_id
            JOIN categories cat ON cat.id = p.category_id
            WHERE ci.session_id=? AND p.status='approved'
            ORDER BY ci.added_at DESC", [$sid]);
    } else {
        $items = DB::fetchAll("
            SELECT ci.id, ci.quantity qty, ci.size, p.name, p.price,
                   s.shop_name shop, ci.quantity * p.price line_total,
                   (SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) image,
                   cat.icon icon
            FROM cart_items ci
            JOIN products p    ON p.id  = ci.product_id
            JOIN shops    s    ON s.id  = p.shop_id
            JOIN categories cat ON cat.id = p.category_id
            WHERE ci.user_id=? AND p.status='approved'
            ORDER BY ci.added_at DESC", [$uid]);
    }

    // Format prices in the active display currency
    $cur    = activeCurrency();
    $symbol = currencySymbol($cur);
    $rate   = currencyRate($cur);

    foreach ($items as &$item) {
        $raw = $item['image'];
        if ($raw) {
            $item['image'] = (strncmp($raw,'http://',7)===0||strncmp($raw,'https://',8)===0)
                ? $raw : UPLOAD_URL.'/'.$raw;
        }
        // Add currency-converted formatted strings for the cart drawer UI
        $unitNgn           = (float)$item['price'];
        $lineNgn           = (float)$item['line_total'];
        $unitDisp          = $cur === 'ngn' ? $unitNgn : $unitNgn / $rate;
        $lineDisp          = $cur === 'ngn' ? $lineNgn : $lineNgn / $rate;
        $item['price_fmt']      = $symbol . number_format($unitDisp, 2);
        $item['line_total_fmt'] = $symbol . number_format($lineDisp, 2);
    }
    unset($item);

    $totalNgn = array_sum(array_column($items, 'line_total'));
    $totalDisp = $cur === 'ngn' ? $totalNgn : $totalNgn / $rate;
    $count = array_sum(array_column($items, 'qty'));
    jsonOut([
        'success'     => true,
        'items'       => $items,
        'total'       => $totalNgn,
        'total_fmt'   => $symbol . number_format($totalDisp, 2),
        'currency'    => $cur,
        'symbol'      => $symbol,
        'count'       => $count,
    ]);
}

// ──────────────────────────────────────────────────────────────
// POST: add / update / remove
// ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $act  = $body['action'] ?? '';

    // ADD ──────────────────────────────────────────────────────
    if ($act === 'add') {
        $pid  = (int)($body['product_id'] ?? 0);
        $size = trim($body['size'] ?? '');
        $qty  = max(1,(int)($body['qty'] ?? 1));

        $prod = DB::fetch("SELECT * FROM products WHERE id=? AND status='approved'", [$pid]);
        if (!$prod) jsonOut(['success'=>false,'error'=>'Product not available.']);
        if ($prod['quantity'] < $qty) jsonOut(['success'=>false,'error'=>'Not enough stock.']);

        if ($isGuest) {
            $existing = DB::fetch(
                "SELECT id,quantity FROM guest_cart_items WHERE session_id=? AND product_id=? AND COALESCE(size,'')=COALESCE(?,'')",
                [$sid, $pid, $size ?: null]);
            if ($existing) {
                $newQty = min($existing['quantity'] + $qty, $prod['quantity']);
                DB::update('guest_cart_items',['quantity'=>$newQty],'id=?',[$existing['id']]);
            } else {
                try {
                    DB::insert('guest_cart_items',['session_id'=>$sid,'product_id'=>$pid,'quantity'=>$qty,'size'=>$size?:null]);
                } catch (Exception $e) {
                    // duplicate key — update
                    DB::query("UPDATE guest_cart_items SET quantity=quantity+? WHERE session_id=? AND product_id=? AND COALESCE(size,'')=COALESCE(?,'') ",
                        [$qty, $sid, $pid, $size ?: null]);
                }
            }
        } else {
            $existing = DB::fetch(
                "SELECT id,quantity FROM cart_items WHERE user_id=? AND product_id=? AND COALESCE(size,'')=COALESCE(?,'')",
                [$uid, $pid, $size ?: null]);
            if ($existing) {
                $newQty = min($existing['quantity'] + $qty, $prod['quantity']);
                DB::update('cart_items',['quantity'=>$newQty],'id=?',[$existing['id']]);
            } else {
                DB::insert('cart_items',['user_id'=>$uid,'product_id'=>$pid,'quantity'=>$qty,'size'=>$size?:null]);
            }
        }

        $count = cartCount2($isGuest, $uid, $sid);
        jsonOut(['success'=>true,'count'=>$count]);
    }

    // UPDATE ───────────────────────────────────────────────────
    if ($act === 'update') {
        $cid   = (int)($body['cart_id'] ?? 0);
        $delta = (int)($body['delta']   ?? 0);
        $table = $isGuest ? 'guest_cart_items' : 'cart_items';
        $where = $isGuest ? 'id=? AND session_id=?' : 'id=? AND user_id=?';
        $wparam= $isGuest ? [$cid, $sid] : [$cid, $uid];

        $item = DB::fetch("SELECT ci.*,p.quantity stock FROM `$table` ci
                           JOIN products p ON p.id=ci.product_id
                           WHERE ci.id=? AND ".($isGuest?'ci.session_id=?':'ci.user_id=?'),
                          $isGuest ? [$cid,$sid] : [$cid,$uid]);
        if (!$item) jsonOut(['success'=>false,'error'=>'Item not found.']);

        $newQty = $item['quantity'] + $delta;
        if ($newQty <= 0) {
            DB::query("DELETE FROM `$table` WHERE $where", $wparam);
        } else {
            $newQty = min($newQty, $item['stock']);
            DB::update($table, ['quantity'=>$newQty], $where, $wparam);
        }
        $count = cartCount2($isGuest, $uid, $sid);
        jsonOut(['success'=>true,'count'=>$count]);
    }

    // REMOVE ───────────────────────────────────────────────────
    if ($act === 'remove') {
        $cid   = (int)($body['cart_id'] ?? 0);
        $table = $isGuest ? 'guest_cart_items' : 'cart_items';
        $where = $isGuest ? 'id=? AND session_id=?' : 'id=? AND user_id=?';
        $wparam= $isGuest ? [$cid, $sid] : [$cid, $uid];
        DB::query("DELETE FROM `$table` WHERE $where", $wparam);
        $count = cartCount2($isGuest, $uid, $sid);
        jsonOut(['success'=>true,'count'=>$count]);
    }
}

jsonOut(['success'=>false,'error'=>'Invalid request.']);
