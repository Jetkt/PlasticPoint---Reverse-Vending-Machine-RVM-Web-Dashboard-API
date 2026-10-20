<?php
/**
 * API Endpoint: Terima data dari alat RVM (ESP32)
 * Method  : POST
 * URL     : http://localhost/rvm-web/api/esp32_input.php
 *
 * Header wajib:
 *   Content-Type : application/json
 *   X-API-KEY    : rvm-secret-2025
 *
 * Body JSON — user_id TIDAK PERLU, diambil otomatis dari sesi aktif:
 * {
 *   "jenis_sampah"    : "PET",
 *   "jumlah"          : 1,
 *   "confidence"      : 0.97,
 *   "status_infrared" : "terdeteksi",
 *   "status_servo"    : "terbuka"
 * }
 *
 * Response sukses:
 * {"status":"sukses","poin":10,"total_poin":120,"user":"Haris","pesan":"..."}
 *
 * Response error:
 * {"status":"error","pesan":"..."}
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/db.php';

// 1. Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'pesan' => 'Hanya menerima POST request']);
    exit;
}

// 2. Validasi API Key
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($api_key !== API_KEY) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'pesan' => 'API Key tidak valid']);
    exit;
}

// 3. Parse body JSON
$body = file_get_contents('php://input');
$data = json_decode($body, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'pesan' => 'Body JSON tidak valid atau kosong']);
    exit;
}

// 4. Ambil user aktif dari sesi_aktif (heartbeat dalam 15 detik terakhir)
$stmt_sesi = $conn->query("
    SELECT s.user_id, u.nama
    FROM sesi_aktif s
    JOIN users u ON s.user_id = u.id
    WHERE s.aktif_at >= NOW() - INTERVAL 15 SECOND
    ORDER BY s.aktif_at DESC
    LIMIT 1
");
$user = $stmt_sesi->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'pesan'  => 'Tidak ada user aktif di depan mesin. Minta user buka dashboard dulu.',
    ]);
    exit;
}

$user_id = (int)$user['user_id'];

// 5. Validasi field wajib
$jenis_sampah = strtoupper(trim($data['jenis_sampah'] ?? ''));
$jumlah       = (int)($data['jumlah'] ?? 1);
$confidence   = isset($data['confidence'])    ? (float)$data['confidence']    : null;
$status_infra = $data['status_infrared']      ?? null;
$status_servo = $data['status_servo']         ?? null;

if (!in_array($jenis_sampah, ['PET', 'PP'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'pesan' => "Jenis sampah tidak valid: $jenis_sampah"]);
    exit;
}
if ($jumlah < 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'pesan' => 'Jumlah minimal 1']);
    exit;
}

// 6. Hitung poin
$poin = $jumlah * 10;

// 7. Simpan transaksi + log sensor
try {
    $conn->beginTransaction();

    $conn->prepare("
        INSERT INTO riwayat_setor (user_id, jenis_sampah, jumlah, poin)
        VALUES (?, ?, ?, ?)
    ")->execute([$user_id, $jenis_sampah, $jumlah, $poin]);

    $conn->prepare("
        INSERT INTO sensor_log (status_infrared, status_servo, hasil_klasifikasi, confidence)
        VALUES (?, ?, ?, ?)
    ")->execute([$status_infra, $status_servo, $jenis_sampah, $confidence]);

    $conn->commit();

    // Total poin user setelah transaksi
    $stmt_total = $conn->prepare("SELECT COALESCE(SUM(poin),0) AS total FROM riwayat_setor WHERE user_id=?");
    $stmt_total->execute([$user_id]);
    $total_poin = (int)$stmt_total->fetch()['total'];

    http_response_code(200);
    echo json_encode([
        'status'     => 'sukses',
        'pesan'      => "Berhasil catat $jumlah unit $jenis_sampah untuk {$user['nama']}",
        'user'       => $user['nama'],
        'user_id'    => $user_id,
        'poin'       => $poin,
        'total_poin' => $total_poin,
        'detail'     => [
            'jenis_sampah' => $jenis_sampah,
            'jumlah'       => $jumlah,
            'confidence'   => $confidence,
        ],
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'pesan' => 'Gagal simpan: ' . $e->getMessage()]);
}
