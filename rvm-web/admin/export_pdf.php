<?php
/**
 * Export PDF laporan transaksi RVM
 * ob_start() di baris pertama mencegah "headers already sent"
 * dari whitespace/BOM di file yang di-include
 */
ob_start();

require_once '../auth/cek_session.php';
require_once '../config/db.php';
require_once 'lib/fpdf.php';
cek_login_role('admin');

$tgl_dari    = $_GET['tgl_dari']   ?? date('Y-m-01');
$tgl_sampai  = $_GET['tgl_sampai'] ?? date('Y-m-d');
$filter_jenis = $_GET['jenis']     ?? '';

$where  = "WHERE r.waktu BETWEEN ? AND ?";
$params = [$tgl_dari.' 00:00:00', $tgl_sampai.' 23:59:59'];
if ($filter_jenis && in_array($filter_jenis, ['PET','PP'])) {
    $where .= " AND r.jenis_sampah = ?";
    $params[] = $filter_jenis;
}

$stmt = $conn->prepare("
    SELECT r.waktu, u.nama, r.jenis_sampah, r.jumlah, r.poin
    FROM riwayat_setor r JOIN users u ON r.user_id=u.id
    $where ORDER BY r.waktu ASC
");
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt_sum = $conn->prepare("
    SELECT COUNT(*) AS total_trx,
           COALESCE(SUM(jumlah),0) AS total_unit,
           COALESCE(SUM(poin),0)   AS total_poin,
           COALESCE(SUM(CASE WHEN jenis_sampah='PET' THEN jumlah ELSE 0 END),0) AS pet,
           COALESCE(SUM(CASE WHEN jenis_sampah='PP'  THEN jumlah ELSE 0 END),0) AS pp
    FROM riwayat_setor r JOIN users u ON r.user_id=u.id
    $where
");
$stmt_sum->execute($params);
$sum = $stmt_sum->fetch(PDO::FETCH_ASSOC);

class PDF extends FPDF {
    public $tgl_dari;
    public $tgl_sampai;
    public $filter_jenis;

    function Header() {
        $this->SetFillColor(15, 33, 71);
        $this->Rect(0, 0, 210, 22, 'F');
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(6);
        $this->Cell(0, 10, 'PlasticPoint - Laporan Transaksi', 0, 1, 'C');
        $this->SetY(23);
        $this->SetTextColor(31, 41, 55);
        $this->SetFont('Arial', '', 9);
        $periode = 'Periode: ' . $this->tgl_dari . ' s/d ' . $this->tgl_sampai;
        $jenis   = $this->filter_jenis ? ' | Jenis: ' . $this->filter_jenis : ' | Jenis: Semua';
        $this->Cell(0, 7, $periode . $jenis, 0, 1, 'C');
        $this->Ln(3);
    }

    function Footer() {
        $this->SetY(-14);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(107, 114, 128);
        $this->Cell(0, 5, 'Dicetak: ' . date('d/m/Y H:i'), 0, 0, 'L');
        $this->Cell(0, 5, 'Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
        $this->SetDrawColor(229, 231, 235);
        $this->Line(10, $this->GetY() - 1, 200, $this->GetY() - 1);
    }

    function TabelHeader() {
        $this->SetFillColor(15, 33, 71);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->SetDrawColor(255, 255, 255);
        $this->Cell(10, 8, 'No',        1, 0, 'C', true);
        $this->Cell(38, 8, 'Waktu',     1, 0, 'C', true);
        $this->Cell(45, 8, 'Nama User', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Jenis',     1, 0, 'C', true);
        $this->Cell(22, 8, 'Jumlah',    1, 0, 'C', true);
        $this->Cell(22, 8, 'Poin',      1, 1, 'C', true);
        $this->SetTextColor(31, 41, 55);
        $this->SetDrawColor(229, 231, 235);
    }

    function TabelBaris($no, $waktu, $nama, $jenis, $jumlah, $poin, $alt) {
        $this->SetFont('Arial', '', 8.5);
        $fill = $alt ? [243, 244, 246] : [255, 255, 255];
        $this->SetFillColor($fill[0], $fill[1], $fill[2]);

        $this->Cell(10, 7, $no,     1, 0, 'C', true);
        $this->SetTextColor(31, 41, 55);
        $this->Cell(38, 7, $waktu,  1, 0, 'C', true);
        $this->Cell(45, 7, $nama,   1, 0, 'L', true);
        if ($jenis === 'PET') $this->SetTextColor(22, 163, 74);
        else                  $this->SetTextColor(234, 88, 12);
        $this->Cell(25, 7, $jenis,  1, 0, 'C', true);
        $this->SetTextColor(31, 41, 55);
        $this->Cell(22, 7, $jumlah, 1, 0, 'C', true);
        $this->SetTextColor(22, 163, 74);
        $this->Cell(22, 7, '+'.$poin, 1, 1, 'C', true);
        $this->SetTextColor(31, 41, 55);
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->tgl_dari    = date('d/m/Y', strtotime($tgl_dari));
$pdf->tgl_sampai  = date('d/m/Y', strtotime($tgl_sampai));
$pdf->filter_jenis = $filter_jenis;

$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetMargins(10, 30, 10);
$pdf->SetAutoPageBreak(true, 18);

// Ringkasan
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(243, 244, 246);
$pdf->SetDrawColor(229, 231, 235);
$pdf->Cell(0, 7, 'Ringkasan Data', 1, 1, 'L', true);
$pdf->SetFont('Arial', '', 9);
$pdf->SetFillColor(255, 255, 255);
$w = 45;
$pdf->Cell($w, 6, 'Total Transaksi',   1, 0, 'C', true);
$pdf->Cell($w, 6, 'Total Unit',        1, 0, 'C', true);
$pdf->Cell($w, 6, 'Total Poin',        1, 0, 'C', true);
$pdf->Cell($w, 6, 'PET / PP',          1, 1, 'C', true);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(15, 33, 71);
$pdf->Cell($w, 8, $sum['total_trx'].' kali',  1, 0, 'C', true);
$pdf->Cell($w, 8, $sum['total_unit'].' unit', 1, 0, 'C', true);
$pdf->SetTextColor(22, 163, 74);
$pdf->Cell($w, 8, $sum['total_poin'].' poin', 1, 0, 'C', true);
$pdf->SetTextColor(31, 41, 55);
$pdf->Cell($w, 8, $sum['pet'].' / '.$sum['pp'], 1, 1, 'C', true);
$pdf->Ln(5);

// Tabel transaksi
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, 'Detail Transaksi', 0, 1, 'L');
$pdf->TabelHeader();

if (empty($data)) {
    $pdf->Cell(0, 7, 'Tidak ada data pada periode ini', 1, 1, 'C');
} else {
    foreach ($data as $i => $d) {
        $pdf->TabelBaris(
            $i + 1,
            date('d/m/Y H:i', strtotime($d['waktu'])),
            $d['nama'],
            $d['jenis_sampah'],
            $d['jumlah'],
            $d['poin'],
            $i % 2 === 1
        );
    }
}

// Bersihkan output buffer sebelum kirim PDF
ob_end_clean();

$nama_file = 'Laporan_PlasticPoint_' . $tgl_dari . '_sd_' . $tgl_sampai . '.pdf';
$pdf->Output('D', $nama_file);
