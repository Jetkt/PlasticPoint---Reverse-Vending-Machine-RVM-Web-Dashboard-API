<?php
require_once '../auth/cek_session.php';
require_once '../config/db.php';
cek_login_role('admin');
$halaman_aktif = 'laporan';

// ===== Filter =====
$tgl_dari  = $_GET['tgl_dari']  ?? date('Y-m-01');
$tgl_sampai= $_GET['tgl_sampai']?? date('Y-m-d');
$filter_jenis = $_GET['jenis'] ?? '';

$where  = "WHERE r.waktu BETWEEN ? AND ?";
$params = [$tgl_dari . ' 00:00:00', $tgl_sampai . ' 23:59:59'];

if ($filter_jenis && in_array($filter_jenis, ['PET','PP'])) {
    $where  .= " AND r.jenis_sampah = ?";
    $params[] = $filter_jenis;
}

// ===== Data transaksi =====
$stmt = $conn->prepare("
    SELECT r.id, r.waktu, u.nama, r.jenis_sampah, r.jumlah, r.poin
    FROM riwayat_setor r JOIN users u ON r.user_id = u.id
    $where ORDER BY r.waktu DESC
");
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Ringkasan =====
$stmt_sum = $conn->prepare("
    SELECT COUNT(*) AS total_trx,
           COALESCE(SUM(jumlah),0) AS total_unit,
           COALESCE(SUM(poin),0)   AS total_poin,
           COALESCE(SUM(CASE WHEN jenis_sampah='PET' THEN jumlah ELSE 0 END),0) AS pet,
           COALESCE(SUM(CASE WHEN jenis_sampah='PP'  THEN jumlah ELSE 0 END),0) AS pp
    FROM riwayat_setor r JOIN users u ON r.user_id = u.id
    $where
");
$stmt_sum->execute($params);
$sum = $stmt_sum->fetch(PDO::FETCH_ASSOC);

// Teruskan filter ke URL export
$qstr = http_build_query(['tgl_dari'=>$tgl_dari,'tgl_sampai'=>$tgl_sampai,'jenis'=>$filter_jenis]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan - PlasticPoint Admin</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <style>
    .filter-card { background:#fff; border-radius:12px; padding:20px 24px; box-shadow:0 1px 3px rgba(0,0,0,.06); margin-bottom:22px; }
    .filter-row  { display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end; }
    .filter-row .fg { display:flex; flex-direction:column; gap:4px; }
    .filter-row label { font-size:12.5px; font-weight:600; color:#374151; }
    .filter-row input, .filter-row select {
      padding:8px 12px; border:1.5px solid #d1d5db; border-radius:8px;
      font-size:13.5px; outline:none; background:#fff; min-width:140px;
    }
    .filter-row input:focus, .filter-row select:focus { border-color:#0f2147; }
    .btn-filter  { padding:9px 20px; background:#0f2147; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:13.5px; font-weight:600; }
    .btn-filter:hover { background:#16a34a; }

    .sum-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:22px; }
    .sum-card { background:#fff; border-radius:10px; padding:16px 18px; box-shadow:0 1px 3px rgba(0,0,0,.06); }
    .sum-card .slabel { font-size:12px; color:#6b7280; margin-bottom:5px; }
    .sum-card .sval   { font-size:24px; font-weight:700; color:#0f2147; }
    .sum-card .ssub   { font-size:11px; color:#9ca3af; margin-top:3px; }

    .export-bar { display:flex; gap:10px; justify-content:flex-end; margin-bottom:14px; flex-wrap:wrap; }
    .btn-excel { padding:9px 18px; background:#16a34a; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:13px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
    .btn-pdf   { padding:9px 18px; background:#dc2626; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:13px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
    .btn-excel:hover { background:#15803d; }
    .btn-pdf:hover   { background:#b91c1c; }

    @media(max-width:768px){
      .sum-grid { grid-template-columns:1fr 1fr; }
      .filter-row { flex-direction:column; }
      .filter-row input, .filter-row select { min-width:unset; width:100%; }
      .btn-filter { width:100%; }
      .export-bar { justify-content:stretch; }
      .btn-excel, .btn-pdf { flex:1; justify-content:center; }
    }
  </style>
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>
<div class="desktop-wrapper"><div class="main">

  <div class="topbar">
    <div>
      <h1>Laporan & Ekspor Data</h1>
      <div class="subtitle">Filter, preview, dan unduh laporan transaksi</div>
    </div>
  </div>

  <!-- Filter -->
  <div class="filter-card">
    <form method="GET" action="">
      <div class="filter-row">
        <div class="fg">
          <label>Dari Tanggal</label>
          <input type="date" name="tgl_dari" value="<?= $tgl_dari ?>">
        </div>
        <div class="fg">
          <label>Sampai Tanggal</label>
          <input type="date" name="tgl_sampai" value="<?= $tgl_sampai ?>">
        </div>
        <div class="fg">
          <label>Jenis Sampah</label>
          <select name="jenis">
            <option value="">Semua</option>
            <option value="PET" <?= $filter_jenis==='PET'?'selected':'' ?>>PET</option>
            <option value="PP"  <?= $filter_jenis==='PP' ?'selected':'' ?>>PP</option>
          </select>
        </div>
        <div class="fg">
          <label>&nbsp;</label>
          <button type="submit" class="btn-filter">🔍 Tampilkan</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Ringkasan -->
  <div class="sum-grid">
    <div class="sum-card">
      <div class="slabel">Total Transaksi</div>
      <div class="sval"><?= $sum['total_trx'] ?></div>
      <div class="ssub">kali setor</div>
    </div>
    <div class="sum-card">
      <div class="slabel">Total Unit Sampah</div>
      <div class="sval"><?= $sum['total_unit'] ?></div>
      <div class="ssub">unit terkumpul</div>
    </div>
    <div class="sum-card">
      <div class="slabel">Total Poin Diberikan</div>
      <div class="sval" style="color:#16a34a"><?= $sum['total_poin'] ?></div>
      <div class="ssub">poin</div>
    </div>
    <div class="sum-card">
      <div class="slabel">PET / PP</div>
      <div class="sval"><?= $sum['pet'] ?> / <?= $sum['pp'] ?></div>
      <div class="ssub">unit per jenis</div>
    </div>
  </div>

  <!-- Tabel preview + tombol ekspor -->
  <div class="panel full">
    <div class="export-bar">
      <a class="btn-excel" href="export_excel.php?<?= $qstr ?>">
        📊 Unduh Excel (.xlsx)
      </a>
      <a class="btn-pdf" href="export_pdf.php?<?= $qstr ?>" target="_blank">
        📄 Unduh PDF
      </a>
    </div>
    <div class="actions-row" style="margin-bottom:12px;">
      <h2 style="margin:0;">Preview Data (<?= count($data) ?> transaksi)</h2>
      <input class="search-input" type="text" id="s-lap" placeholder="Cari...">
    </div>
    <div class="table-wrap">
      <table id="t-lap">
        <thead>
          <tr>
            <th>No</th>
            <th>Waktu</th>
            <th>Nama User</th>
            <th>Jenis Plastik</th>
            <th>Jumlah (unit)</th>
            <th>Poin</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data)): ?>
            <tr><td colspan="6" class="empty-msg">Tidak ada data pada periode ini</td></tr>
          <?php else: foreach ($data as $i => $d): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><?= htmlspecialchars($d['waktu']) ?></td>
              <td><?= htmlspecialchars($d['nama']) ?></td>
              <td class="<?= $d['jenis_sampah']==='PET'?'jenis-pet':'jenis-pp' ?>"><?= $d['jenis_sampah'] ?></td>
              <td><?= $d['jumlah'] ?></td>
              <td class="poin-plus">+<?= $d['poin'] ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div></div>
<script>
document.getElementById('s-lap').addEventListener('input', function () {
  const k = this.value.toLowerCase();
  document.querySelectorAll('#t-lap tbody tr').forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(k) ? '' : 'none';
  });
});
</script>
</body>
</html>
