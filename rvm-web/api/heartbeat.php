<?php
/**
 * api/heartbeat.php
 * Dipanggil otomatis oleh dashboard user setiap 10 detik.
 * Selama halaman terbuka di HP → user dianggap ada di depan mesin.
 * Kalau heartbeat berhenti 15 detik → sesi dianggap habis.
 */
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'pesan' => 'Belum login']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$nama    = $_SESSION['nama'];

// Upsert: insert pertama kali, update aktif_at berikutnya
$stmt = $conn->prepare("
    INSERT INTO sesi_aktif (user_id, nama, aktif_at)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE nama = ?, aktif_at = NOW()
");
$stmt->execute([$user_id, $nama, $nama]);

// Bersihkan sesi user lain yang sudah tidak aktif >15 detik
$conn->exec("DELETE FROM sesi_aktif WHERE aktif_at < NOW() - INTERVAL 15 SECOND");

echo json_encode(['status' => 'ok', 'user_id' => $user_id, 'nama' => $nama]);
