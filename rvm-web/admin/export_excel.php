<?php
/**
 * Export Excel laporan transaksi RVM
 * Semua XML dibangun sebagai string PHP dulu,
 * baru dikirim setelah header — menghindari masalah
 * whitespace sebelum deklarasi XML
 */
ob_start();

require_once '../auth/cek_session.php';
require_once '../config/db.php';
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

// ===== Bangun isi XML sebagai string PHP =====
$periode_label = date('d/m/Y', strtotime($tgl_dari)) . ' s/d ' . date('d/m/Y', strtotime($tgl_sampai));
$jenis_label   = $filter_jenis ?: 'Semua';
$generated     = date('d/m/Y H:i');

// Baris data
$baris_xml = '';
if (empty($data)) {
    $baris_xml .= '<Row><Cell ss:MergeAcross="5" ss:StyleID="baris_ganjil">
      <Data ss:Type="String">Tidak ada data pada periode ini</Data>
    </Cell></Row>' . "\n";
} else {
    foreach ($data as $i => $d) {
        $alt         = ($i % 2 === 1) ? 'baris_genap' : 'baris_ganjil';
        $jenis_style = $d['jenis_sampah'] === 'PET' ? 'pet' : 'pp';
        $waktu_fmt   = date('d/m/Y H:i', strtotime($d['waktu']));
        $nama_esc    = htmlspecialchars($d['nama'], ENT_XML1);
        $baris_xml .= "<Row ss:Height=\"18\">
      <Cell ss:StyleID=\"center\"><Data ss:Type=\"Number\">" . ($i+1) . "</Data></Cell>
      <Cell ss:StyleID=\"{$alt}\"><Data ss:Type=\"String\">{$waktu_fmt}</Data></Cell>
      <Cell ss:StyleID=\"{$alt}\"><Data ss:Type=\"String\">{$nama_esc}</Data></Cell>
      <Cell ss:StyleID=\"{$jenis_style}\"><Data ss:Type=\"String\">{$d['jenis_sampah']}</Data></Cell>
      <Cell ss:StyleID=\"center\"><Data ss:Type=\"Number\">{$d['jumlah']}</Data></Cell>
      <Cell ss:StyleID=\"poin_style\"><Data ss:Type=\"Number\">{$d['poin']}</Data></Cell>
    </Row>\n";
    }
}

$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:x="urn:schemas-microsoft-com:office:excel">
<Styles>
  <Style ss:ID="judul"><Font ss:Bold="1" ss:Size="14" ss:Color="#0F2147"/><Alignment ss:Horizontal="Center"/></Style>
  <Style ss:ID="subjudul"><Font ss:Bold="1" ss:Size="10" ss:Color="#6B7280"/><Alignment ss:Horizontal="Center"/></Style>
  <Style ss:ID="header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0F2147" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFFFFF"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFFFFF"/></Borders></Style>
  <Style ss:ID="rekap_label"><Font ss:Bold="1" ss:Color="#374151"/><Interior ss:Color="#F3F4F6" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/><Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D1D5DB"/></Borders></Style>
  <Style ss:ID="rekap_val"><Font ss:Bold="1" ss:Size="12" ss:Color="#0F2147"/><Alignment ss:Horizontal="Center"/><Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D1D5DB"/></Borders></Style>
  <Style ss:ID="baris_genap"><Interior ss:Color="#F9FAFB" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/></Borders></Style>
  <Style ss:ID="baris_ganjil"><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/></Borders></Style>
  <Style ss:ID="pet"><Font ss:Color="#16A34A" ss:Bold="1"/><Interior ss:Color="#F9FAFB" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/></Borders></Style>
  <Style ss:ID="pp"><Font ss:Color="#EA580C" ss:Bold="1"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/></Borders></Style>
  <Style ss:ID="poin_style"><Font ss:Color="#16A34A" ss:Bold="1"/><Alignment ss:Horizontal="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/></Borders></Style>
  <Style ss:ID="center"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/></Borders></Style>
</Styles>
<Worksheet ss:Name="Laporan PlasticPoint">
  <Table ss:DefaultRowHeight="18">
    <Column ss:Width="35"/><Column ss:Width="130"/><Column ss:Width="110"/>
    <Column ss:Width="70"/><Column ss:Width="70"/><Column ss:Width="70"/>
    <Row ss:Height="28"><Cell ss:MergeAcross="5" ss:StyleID="judul"><Data ss:Type="String">PLASTICPOINT - LAPORAN TRANSAKSI</Data></Cell></Row>
    <Row ss:Height="18"><Cell ss:MergeAcross="5" ss:StyleID="subjudul"><Data ss:Type="String">Periode: {$periode_label} | Jenis: {$jenis_label}</Data></Cell></Row>
    <Row ss:Height="12"/>
    <Row ss:Height="20">
      <Cell ss:StyleID="rekap_label"><Data ss:Type="String">Total Transaksi</Data></Cell>
      <Cell ss:StyleID="rekap_label"><Data ss:Type="String">Total Unit Sampah</Data></Cell>
      <Cell ss:StyleID="rekap_label"><Data ss:Type="String">Total Poin</Data></Cell>
      <Cell ss:StyleID="rekap_label"><Data ss:Type="String">Sampah PET</Data></Cell>
      <Cell ss:StyleID="rekap_label"><Data ss:Type="String">Sampah PP</Data></Cell>
      <Cell ss:StyleID="rekap_label"><Data ss:Type="String">Digenerate</Data></Cell>
    </Row>
    <Row ss:Height="24">
      <Cell ss:StyleID="rekap_val"><Data ss:Type="Number">{$sum['total_trx']}</Data></Cell>
      <Cell ss:StyleID="rekap_val"><Data ss:Type="Number">{$sum['total_unit']}</Data></Cell>
      <Cell ss:StyleID="rekap_val"><Data ss:Type="Number">{$sum['total_poin']}</Data></Cell>
      <Cell ss:StyleID="rekap_val"><Data ss:Type="Number">{$sum['pet']}</Data></Cell>
      <Cell ss:StyleID="rekap_val"><Data ss:Type="Number">{$sum['pp']}</Data></Cell>
      <Cell ss:StyleID="rekap_val"><Data ss:Type="String">{$generated}</Data></Cell>
    </Row>
    <Row ss:Height="12"/>
    <Row ss:Height="22">
      <Cell ss:StyleID="header"><Data ss:Type="String">No</Data></Cell>
      <Cell ss:StyleID="header"><Data ss:Type="String">Waktu Transaksi</Data></Cell>
      <Cell ss:StyleID="header"><Data ss:Type="String">Nama Pengguna</Data></Cell>
      <Cell ss:StyleID="header"><Data ss:Type="String">Jenis Plastik</Data></Cell>
      <Cell ss:StyleID="header"><Data ss:Type="String">Jumlah (Unit)</Data></Cell>
      <Cell ss:StyleID="header"><Data ss:Type="String">Poin</Data></Cell>
    </Row>
    {$baris_xml}
  </Table>
</Worksheet>
</Workbook>
XML;

// ===== Bersihkan buffer, kirim header, lalu output XML =====
ob_end_clean();

$nama_file = 'Laporan_PlasticPoint_' . $tgl_dari . '_sd_' . $tgl_sampai . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nama_file . '"');
header('Cache-Control: max-age=0');
header('Content-Length: ' . strlen($xml));

echo $xml;
