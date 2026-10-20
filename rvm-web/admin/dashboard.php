<?php
require_once '../auth/cek_session.php';
require_once '../config/db.php';
cek_login_role('admin');
$halaman_aktif = 'dashboard';

$total_user     = $conn->query("SELECT COUNT(*) AS t FROM users WHERE role='user'")->fetch()['t'];
$total_transaksi= $conn->query("SELECT COUNT(*) AS t FROM riwayat_setor")->fetch()['t'];
$total_sampah   = $conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM riwayat_setor")->fetch()['t'];
$pet_total      = (int)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM riwayat_setor WHERE jenis_sampah='PET'")->fetch()['t'];
$pp_total       = (int)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM riwayat_setor WHERE jenis_sampah='PP'")->fetch()['t'];
$max_jenis      = max($pet_total, $pp_total, 1);

$last_sensor = $conn->query("SELECT * FROM sensor_log ORDER BY timestamp DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$alat_online = $last_sensor && ((time() - strtotime($last_sensor['timestamp'])) / 60 <= 10);

$data_pengguna = $conn->query("
    SELECT u.id, u.nama, COUNT(r.id) AS total_trx, COALESCE(SUM(r.poin),0) AS total_poin
    FROM users u LEFT JOIN riwayat_setor r ON r.user_id=u.id
    WHERE u.role='user' GROUP BY u.id, u.nama ORDER BY u.nama ASC
")->fetchAll(PDO::FETCH_ASSOC);

$riwayat_global = $conn->query("
    SELECT r.*, u.nama FROM riwayat_setor r
    JOIN users u ON r.user_id=u.id ORDER BY r.waktu DESC LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

$sensor_terbaru = $conn->query("SELECT * FROM sensor_log ORDER BY timestamp DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - PlasticPoint Admin</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<?php include '../includes/sidebar_admin.php'; ?>

<div class="desktop-wrapper">
<div class="main">

  <div class="topbar">
    <div>
      <h1>Dashboard Admin</h1>
      <div class="subtitle">Pantau seluruh aktivitas sistem PlasticPoint</div>
    </div>
    <div class="machine-status">
      <span class="dot <?= $alat_online ? '' : 'offline' ?>"></span>
      Alat RVM: <?= $alat_online ? 'Online' : 'Offline' ?>
    </div>
  </div>

  <!-- Cards -->
  <div class="cards">
    <div class="card users">
      <div>
        <div class="card-label">Total Pengguna</div>
        <div class="card-value"><?= $total_user ?> <span class="unit">User</span></div>
      </div>
      <div class="card-icon">👥</div>
    </div>
    <div class="card trx">
      <div>
        <div class="card-label">Total Transaksi</div>
        <div class="card-value"><?= $total_transaksi ?> <span class="unit">Kali</span></div>
      </div>
      <div class="card-icon">♻️</div>
    </div>
    <div class="card waste">
      <div>
        <div class="card-label">Sampah Terkumpul</div>
        <div class="card-value"><?= $total_sampah ?> <span class="unit">Unit</span></div>
      </div>
      <div class="card-icon">🧴</div>
    </div>
  </div>

  <!-- Grafik & Sensor -->
  <div class="row">
    <div class="panel half">
      <h2>Distribusi Jenis Plastik</h2>
      <div class="bar-chart">
        <div class="bar-group">
          <div class="bar-value"><?= $pet_total ?></div>
          <div class="bar-track">
            <div class="bar-fill pet" style="height:<?= ($pet_total/$max_jenis)*100 ?>%"></div>
          </div>
          <div class="bar-label">PET</div>
        </div>
        <div class="bar-group">
          <div class="bar-value"><?= $pp_total ?></div>
          <div class="bar-track">
            <div class="bar-fill pp" style="height:<?= ($pp_total/$max_jenis)*100 ?>%"></div>
          </div>
          <div class="bar-label">PP</div>
        </div>
      </div>
    </div>
    <div class="panel half">
      <h2>Aktivitas Sensor Terbaru</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Waktu</th><th>Klasifikasi</th><th>Confidence</th></tr></thead>
          <tbody>
            <?php if (empty($sensor_terbaru)): ?>
              <tr><td colspan="3" class="empty-msg">Belum ada data sensor</td></tr>
            <?php else: foreach ($sensor_terbaru as $s): ?>
              <tr>
                <td><?= htmlspecialchars($s['timestamp']) ?></td>
                <td><?= htmlspecialchars($s['hasil_klasifikasi'] ?? '-') ?></td>
                <td><?= $s['confidence'] !== null ? number_format($s['confidence']*100,1).'%' : '-' ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Tabel Pengguna -->
  <div class="panel full">
    <div class="actions-row">
      <h2 style="margin-bottom:0;">Data Pengguna</h2>
      <input class="search-input" type="text" id="s-user" placeholder="Cari pengguna...">
    </div>
    <div class="table-wrap">
      <table id="t-user">
        <thead><tr><th>No</th><th>Nama</th><th>Total Transaksi</th><th>Total Poin</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($data_pengguna as $i => $u): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($u['nama']) ?></td>
            <td><?= $u['total_trx'] ?></td>
            <td><?= $u['total_poin'] ?></td>
            <td>
              <a class="btn-small btn-edit" href="edit_user.php?id=<?= $u['id'] ?>">Edit</a>
              <a class="btn-small btn-delete" href="kelola_user.php?hapus=<?= $u['id'] ?>" onclick="return confirm('Hapus user ini?')">Hapus</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($data_pengguna)): ?><tr><td colspan="5" class="empty-msg">Belum ada pengguna</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Riwayat Global -->
  <div class="panel full">
    <div class="actions-row">
      <h2 style="margin-bottom:0;">Riwayat Transaksi</h2>
      <input class="search-input" type="text" id="s-trx" placeholder="Cari transaksi...">
    </div>
    <div class="table-wrap">
      <table id="t-trx">
        <thead><tr><th>No</th><th>Waktu</th><th>Pengguna</th><th>Jenis</th><th>Poin</th></tr></thead>
        <tbody>
          <?php foreach ($riwayat_global as $i => $t): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($t['waktu']) ?></td>
            <td><?= htmlspecialchars($t['nama']) ?></td>
            <td class="<?= $t['jenis_sampah']==='PET' ? 'jenis-pet':'jenis-pp' ?>"><?= $t['jenis_sampah'] ?></td>
            <td class="poin-plus">+<?= $t['poin'] ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($riwayat_global)): ?><tr><td colspan="5" class="empty-msg">Belum ada transaksi</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>

<script>
function pasangFilter(inp, tabel) {
  document.getElementById(inp).addEventListener('input', function() {
    const kata = this.value.toLowerCase();
    document.querySelectorAll('#' + tabel + ' tbody tr').forEach(r => {
      r.style.display = r.textContent.toLowerCase().includes(kata) ? '' : 'none';
    });
  });
}
pasangFilter('s-user','t-user');
pasangFilter('s-trx','t-trx');
</script>
</body>
</html>
