<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
$uid = (int)$_SESSION['user_id'];

// Total poin & transaksi
$stmt_poin = $conn->prepare("SELECT COALESCE(SUM(poin),0) AS t FROM riwayat_setor WHERE user_id=?");
$stmt_poin->execute([$uid]);
$total_poin = (int)$stmt_poin->fetch()['t'];

$stmt_trx = $conn->prepare("SELECT COUNT(*) AS t FROM riwayat_setor WHERE user_id=?");
$stmt_trx->execute([$uid]);
$total_trx = (int)$stmt_trx->fetch()['t'];

// Riwayat 10 terakhir
$stmt_r = $conn->prepare("SELECT * FROM riwayat_setor WHERE user_id=? ORDER BY waktu DESC LIMIT 10");
$stmt_r->execute([$uid]);
$riwayat = $stmt_r->fetchAll(PDO::FETCH_ASSOC);

// Grafik bulanan 12 bulan terakhir
$bulan_labels = [];
$bulan_map    = [];
for ($i = 11; $i >= 0; $i--) {
    $key             = date('Y-m', strtotime("-$i months"));
    $bulan_labels[]  = date('M Y', strtotime("-$i months"));
    $bulan_map[$key] = 0;
}
$stmt_g = $conn->prepare("
    SELECT DATE_FORMAT(waktu,'%Y-%m') AS bulan, SUM(poin) AS total
    FROM riwayat_setor
    WHERE user_id = ? AND waktu >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(waktu,'%Y-%m')
");
$stmt_g->execute([$uid]);
foreach ($stmt_g->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (isset($bulan_map[$row['bulan']])) {
        $bulan_map[$row['bulan']] = (int)$row['total'];
    }
}

echo json_encode([
    'total_poin'     => $total_poin,
    'total_trx'      => $total_trx,
    'riwayat'        => $riwayat,
    'grafik_labels'  => $bulan_labels,
    'grafik_data'    => array_values($bulan_map),
]);
