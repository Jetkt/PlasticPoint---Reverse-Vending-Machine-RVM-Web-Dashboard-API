<?php
require_once '../auth/cek_session.php';
require_once '../config/db.php';
cek_login_role('admin');
$halaman_aktif = 'sampah';
$pesan=''; $error='';
$poin_per=['PET'=>10,'PP'=>10];

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['aksi']??'')==='tambah') {
    $uid=(int)$_POST['user_id']; $jenis=$_POST['jenis_sampah']; $jumlah=(int)$_POST['jumlah'];
    $poin=$jumlah*$poin_per[$jenis];
    $conn->prepare("INSERT INTO riwayat_setor(user_id,jenis_sampah,jumlah,poin)VALUES(?,?,?,?)")->execute([$uid,$jenis,$jumlah,$poin]);
    $pesan="Data berhasil ditambahkan ($poin poin)";
}
if (isset($_GET['hapus'])) { $conn->prepare("DELETE FROM riwayat_setor WHERE id=?")->execute([(int)$_GET['hapus']]); $pesan='Data berhasil dihapus'; }

$daftar_user=$conn->query("SELECT id,nama FROM users WHERE role='user' ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
$data_setor=$conn->query("SELECT r.*,u.nama FROM riwayat_setor r JOIN users u ON r.user_id=u.id ORDER BY r.waktu DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Transaksi - PlasticPoint Admin</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>
<div class="desktop-wrapper"><div class="main">
  <div class="topbar"><div><h1>Data Transaksi</h1><div class="subtitle">Input manual sampai ESP32 terhubung</div></div></div>

  <?php if ($pesan): ?><div class="alert sukses"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>

  <?php if (!empty($daftar_user)): ?>
  <div class="panel full">
    <h2>Tambah Data Setor</h2>
    <form class="tambah" method="POST">
      <input type="hidden" name="aksi" value="tambah">
      <div class="grid3">
        <div><label>User</label><select name="user_id"><?php foreach($daftar_user as $u): ?><option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?></option><?php endforeach; ?></select></div>
        <div><label>Jenis Sampah</label><select name="jenis_sampah"><option value="PET">PET</option><option value="PP">PP</option></select></div>
        <div><label>Jumlah (unit)</label><input type="number" name="jumlah" min="1" value="1" required></div>
      </div>
      <div class="info">PET dan PP = 10 poin/unit</div>
      <button type="submit" class="btn-submit">Tambah Data</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="panel full">
    <div class="actions-row">
      <h2 style="margin-bottom:0;">Riwayat Setor</h2>
      <input class="search-input" type="text" id="s-setor" placeholder="Cari data...">
    </div>
    <div class="table-wrap">
      <table id="t-setor">
        <thead><tr><th>User</th><th>Jenis</th><th>Jumlah</th><th>Poin</th><th>Waktu</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($data_setor as $d): ?>
          <tr>
            <td><?= htmlspecialchars($d['nama']) ?></td>
            <td class="<?= $d['jenis_sampah']==='PET'?'jenis-pet':'jenis-pp' ?>"><?= $d['jenis_sampah'] ?></td>
            <td><?= $d['jumlah'] ?></td>
            <td class="poin-plus">+<?= $d['poin'] ?></td>
            <td><?= htmlspecialchars($d['waktu']) ?></td>
            <td><a class="btn-small btn-delete" href="?hapus=<?= $d['id'] ?>" onclick="return confirm('Hapus?')">Hapus</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($data_setor)): ?><tr><td colspan="6" class="empty-msg">Belum ada data</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div></div>
<script>
document.getElementById('s-setor').addEventListener('input',function(){
  const k=this.value.toLowerCase();
  document.querySelectorAll('#t-setor tbody tr').forEach(r=>r.style.display=r.textContent.toLowerCase().includes(k)?'':'none');
});
</script>
</body></html>
