<?php
/**
 * api/data_user.php
 * Dipanggil dashboard user setiap 15 detik via AJAX.
 * Return: poin terbaru + riwayat 10 transaksi terakhir.
 * Tujuan: poin otomatis update di layar begitu ESP32 kirim data baru.
 */
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$uid = (int)$_SESSION['user_id'];

$stmt_poin = $conn->prepare("SELECT COALESCE(SUM(poin),0) AS t FROM riwayat_setor WHERE user_id=?");
$stmt_poin->execute([$uid]);
$total_poin = (int)$stmt_poin->fetch()['t'];

$stmt_trx = $conn->prepare("SELECT COUNT(*) AS t FROM riwayat_setor WHERE user_id=?");
$stmt_trx->execute([$uid]);
$total_trx = (int)$stmt_trx->fetch()['t'];

$stmt_rw = $conn->prepare("SELECT * FROM riwayat_setor WHERE user_id=? ORDER BY waktu DESC LIMIT 10");
$stmt_rw->execute([$uid]);
$riwayat = $stmt_rw->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'total_poin' => $total_poin,
    'total_trx'  => $total_trx,
    'riwayat'    => $riwayat,
]);
