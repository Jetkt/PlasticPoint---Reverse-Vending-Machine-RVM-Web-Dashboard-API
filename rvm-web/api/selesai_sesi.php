<?php
/**
 * api/selesai_sesi.php
 * Dipanggil tombol "Selesai Menyetor" di dashboard user.
 * Menghapus sesi aktif user dari tabel sesi_aktif.
 */
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'pesan' => 'Belum login']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM sesi_aktif WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);

echo json_encode(['status' => 'ok', 'pesan' => 'Sesi selesai']);
