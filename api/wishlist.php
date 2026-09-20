<?php
// api/wishlist.php
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

if (!Auth::check()) { echo json_encode(['success'=>false,'error'=>'Please log in.']); exit; }

$uid  = Auth::id();
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$pid  = (int)($body['product_id'] ?? 0);

if (!$pid) { echo json_encode(['success'=>false,'error'=>'Invalid product.']); exit; }

$exists = DB::count('SELECT COUNT(*) FROM wishlists WHERE user_id=? AND product_id=?',[$uid,$pid]);
if ($exists) {
    DB::delete('wishlists','user_id=? AND product_id=?',[$uid,$pid]);
    echo json_encode(['success'=>true,'in_wish'=>false]);
} else {
    DB::insert('wishlists',['user_id'=>$uid,'product_id'=>$pid]);
    echo json_encode(['success'=>true,'in_wish'=>true]);
}
