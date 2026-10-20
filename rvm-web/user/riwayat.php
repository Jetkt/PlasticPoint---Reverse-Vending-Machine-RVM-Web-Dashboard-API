<?php
require_once '../auth/cek_session.php';
require_once '../config/db.php';
cek_login_role('user');

$halaman_aktif = 'riwayat';
$uid = $_SESSION['user_id'];
$filter_jenis = $_GET['jenis'] ?? '';

if ($filter_jenis && in_array($filter_jenis, ['PET','PP'])) {
    $stmt = $conn->prepare("SELECT * FROM riwayat_setor WHERE user_id=? AND jenis_sampah=? ORDER BY waktu DESC");
    $stmt->execute([$uid, $filter_jenis]);
} else {
    $stmt = $conn->prepare("SELECT * FROM riwayat_setor WHERE user_id=? ORDER BY waktu DESC");
    $stmt->execute([$uid]);
}
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt_rekap = $conn->prepare("SELECT jenis_sampah, COUNT(*) AS jtrx, SUM(jumlah) AS junit, SUM(poin) AS jpoin FROM riwayat_setor WHERE user_id=? GROUP BY jenis_sampah");
$stmt_rekap->execute([$uid]);
$rekap = $stmt_rekap->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat - PlasticPoint</title>
  <link rel="stylesheet" href="../assets/css/user.css">
</head>
<body>

<?php include '../includes/sidebar_user.php'; ?>

<div class="desktop-wrapper">
  <div class="main">
    <div class="page-header"><h1>Riwayat Transaksi</h1></div>

    <!-- Rekap per jenis -->
    <?php if (!empty($rekap)): ?>
    <div class="rekap-row">
      <?php foreach ($rekap as $r): ?>
      <div class="rekap-card">
        <div class="rjenis"><?= $r['jenis_sampah'] ?></div>
        <div class="rnilai <?= $r['jenis_sampah']==='PET' ? 'jenis-pet':'jenis-pp' ?>"><?= $r['jpoin'] ?> poin</div>
        <div class="rsub"><?= $r['jtrx'] ?> transaksi &bull; <?= $r['junit'] ?> unit</div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="filter-bar">
      <a href="riwayat.php"          class="btn-filter <?= $filter_jenis===''    ? 'aktif':'' ?>">Semua</a>
      <a href="riwayat.php?jenis=PET" class="btn-filter <?= $filter_jenis==='PET' ? 'aktif':'' ?>">PET</a>
      <a href="riwayat.php?jenis=PP"  class="btn-filter <?= $filter_jenis==='PP'  ? 'aktif':'' ?>">PP</a>
    </div>

    <!-- Mobile: card list -->
    <div class="panel" id="list-mobile">
      <div class="panel-title">Semua Transaksi</div>
      <div class="riwayat-list">
        <?php if (empty($riwayat)): ?>
          <div class="empty-msg">Belum ada data</div>
        <?php else: foreach ($riwayat as $r): ?>
          <?php $isPet = $r['jenis_sampah']==='PET'; ?>
          <div class="riwayat-item">
            <div class="riwayat-icon <?= $isPet ? 'pet':'pp' ?>">♻️</div>
            <div class="riwayat-info">
              <div class="riwayat-jenis"><?= $r['jenis_sampah'] ?> &mdash; <?= $r['jumlah'] ?> unit</div>
              <div class="riwayat-tanggal"><?= htmlspecialchars($r['waktu']) ?></div>
            </div>
            <div class="riwayat-right">
              <div class="riwayat-poin">+<?= $r['poin'] ?></div>
              <div class="riwayat-status">Berhasil</div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Desktop: tabel -->
    <div class="panel" id="tabel-desktop" style="display:none;">
      <div class="table-wrap">
        <table>
          <thead><tr><th>No</th><th>Waktu</th><th>Jenis</th><th>Jumlah</th><th>Poin</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (empty($riwayat)): ?>
              <tr><td colspan="6" class="empty-msg">Belum ada data</td></tr>
            <?php else: foreach ($riwayat as $i => $r): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($r['waktu']) ?></td>
                <td class="<?= $r['jenis_sampah']==='PET' ? 'jenis-pet':'jenis-pp' ?>"><?= $r['jenis_sampah'] ?></td>
                <td><?= $r['jumlah'] ?> unit</td>
                <td class="poin-plus">+<?= $r['poin'] ?></td>
                <td style="color:#16a34a; font-weight:600;">Berhasil</td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<script>
  function switchView() {
    const isDesktop = window.innerWidth > 768;
    document.getElementById('list-mobile').style.display   = isDesktop ? 'none'  : 'block';
    document.getElementById('tabel-desktop').style.display = isDesktop ? 'block' : 'none';
  }
  switchView();
  window.addEventListener('resize', switchView);
</script>
</body>
</html>
