<?php
/**
 * Endpoint AJAX: Ambil data sensor terbaru
 * Dipanggil setiap 5 detik oleh halaman monitoring
 * Return: JSON { status, alat_online, sensor_terbaru, log_terbaru, stat }
 */
session_start();
require_once '../config/db.php';

// Hanya bisa diakses oleh admin yang sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// ===== Status alat (online jika ada data sensor dalam 10 menit terakhir) =====
$last = $conn->query("SELECT * FROM sensor_log ORDER BY timestamp DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$alat_online  = false;
$menit_lalu   = null;

if ($last) {
    $selisih_detik = time() - strtotime($last['timestamp']);
    $alat_online   = $selisih_detik <= 600; // 10 menit
    $menit_lalu    = round($selisih_detik / 60, 1);
}

// ===== Log 20 sensor terbaru =====
$log = $conn->query("
    SELECT * FROM sensor_log
    ORDER BY timestamp DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// ===== Statistik hari ini =====
$stat = $conn->query("
    SELECT
        COUNT(*) AS total_log,
        COALESCE(SUM(CASE WHEN hasil_klasifikasi='PET' THEN 1 ELSE 0 END), 0) AS total_pet,
        COALESCE(SUM(CASE WHEN hasil_klasifikasi='PP'  THEN 1 ELSE 0 END), 0) AS total_pp,
        COALESCE(ROUND(AVG(confidence) * 100, 1), 0) AS rata_confidence
    FROM sensor_log
    WHERE DATE(timestamp) = CURDATE()
")->fetch(PDO::FETCH_ASSOC);

// ===== Data untuk grafik confidence (50 data terakhir, urutan lama ke baru) =====
$grafik_raw = $conn->query("
    SELECT timestamp, hasil_klasifikasi, confidence
    FROM sensor_log
    WHERE confidence IS NOT NULL
    ORDER BY timestamp DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);
$grafik = array_reverse($grafik_raw);

// ===== User yang sedang aktif di mesin (heartbeat dalam 15 detik terakhir) =====
$stmt_sesi = $conn->query("
    SELECT user_id, nama, aktif_at,
           TIMESTAMPDIFF(SECOND, aktif_at, NOW()) AS detik_lalu
    FROM sesi_aktif
    WHERE aktif_at >= NOW() - INTERVAL 15 SECOND
    ORDER BY aktif_at DESC LIMIT 1
");
$user_aktif = $stmt_sesi->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'alat_online'    => $alat_online,
    'menit_lalu'     => $menit_lalu,
    'sensor_terbaru' => $last,
    'log'            => $log,
    'stat'           => $stat,
    'grafik'         => $grafik,
    'user_aktif'     => $user_aktif ?: null,
    'waktu_server'   => date('H:i:s'),
]);
