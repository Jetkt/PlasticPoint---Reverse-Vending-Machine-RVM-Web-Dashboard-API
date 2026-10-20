<?php
/**
 * api/cek_sesi.php — dipolling ESP32 tiap 2-3 detik
 * Mengembalikan user yang sedang aktif di depan mesin.
 * Ada user  : {"ada_user":true,"user_id":2,"nama":"Haris","aktif_sejak":"14:30:05"}
 * Tidak ada : {"ada_user":false}
 */
require_once '../config/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($api_key !== API_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'API Key tidak valid']);
    exit;
}

$stmt = $conn->query("
    SELECT user_id, nama,
           TIME(aktif_at) AS aktif_sejak,
           TIMESTAMPDIFF(SECOND, aktif_at, NOW()) AS detik_lalu
    FROM sesi_aktif
    WHERE aktif_at >= NOW() - INTERVAL 15 SECOND
    ORDER BY aktif_at DESC
    LIMIT 1
");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode([
        'ada_user'    => true,
        'user_id'     => (int)$user['user_id'],
        'nama'        => $user['nama'],
        'aktif_sejak' => $user['aktif_sejak'],
        'detik_lalu'  => (int)$user['detik_lalu'],
    ]);
} else {
    echo json_encode(['ada_user' => false]);
}
