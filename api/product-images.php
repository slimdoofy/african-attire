<?php
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

$pid  = (int)($_GET['product_id'] ?? 0);
if (!$pid) { echo json_encode(['images'=>[]]); exit; }

// Only the shop owner or admin can fetch images
if (!Auth::check()) { echo json_encode(['error'=>'Unauthorised']); exit; }

$images = DB::fetchAll(
    'SELECT id, image_path, is_primary, sort_order
     FROM product_images WHERE product_id=? ORDER BY sort_order ASC, is_primary DESC',
    [$pid]
);

$out = [];
foreach ($images as $img) {
    $raw = $img['image_path'];
    $url = (strncmp($raw,'http',4)===0) ? $raw : UPLOAD_URL.'/products/'.$raw;
    $out[] = [
        'id'         => $img['id'],
        'url'        => $url,
        'is_primary' => (bool)$img['is_primary'],
    ];
}
echo json_encode(['images' => $out]);
