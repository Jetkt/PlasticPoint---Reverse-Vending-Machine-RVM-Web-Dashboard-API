<?php
require_once '../auth/cek_session.php';
require_once '../config/db.php';
cek_login_role('admin');
$halaman_aktif = 'user';
$pesan = ''; $error = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['aksi']??'')==='tambah') {
    $nama=$_POST['nama']; $email=trim($_POST['email']); $pw=$_POST['password']; $role=$_POST['role'];
    $cek=$conn->prepare("SELECT id FROM users WHERE email=?"); $cek->execute([$email]);
    if ($cek->fetch()) { $error='Email sudah dipakai'; }
    else { $conn->prepare("INSERT INTO users(nama,email,password,role)VALUES(?,?,?,?)")->execute([$nama,$email,password_hash($pw,PASSWORD_BCRYPT),$role]); $pesan='User berhasil ditambahkan'; }
}
if (isset($_GET['hapus'])) {
    $id=(int)$_GET['hapus'];
    if ($id!==(int)$_SESSION['user_id']) { $conn->prepare("DELETE FROM users WHERE id=?")->execute([$id]); $pesan='User berhasil dihapus'; }
    else { $error='Tidak bisa menghapus akun sendiri'; }
}
$data_users=$conn->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Pengguna - PlasticPoint Admin</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>
<div class="desktop-wrapper"><div class="main">
  <div class="topbar"><div><h1>Data Pengguna</h1><div class="subtitle">Kelola akun user dan admin</div></div></div>

  <?php if ($pesan): ?><div class="alert sukses"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
  <?php if ($error):  ?><div class="alert error"><?= htmlspecialchars($error)  ?></div><?php endif; ?>

  <div class="panel full">
    <h2>Tambah User Baru</h2>
    <form class="tambah" method="POST">
      <input type="hidden" name="aksi" value="tambah">
      <div class="grid2">
        <div><label>Nama</label><input type="text" name="nama" required></div>
        <div><label>Email</label><input type="email" name="email" required></div>
        <div><label>Password</label><input type="text" name="password" required></div>
        <div><label>Role</label><select name="role"><option value="user">User</option><option value="admin">Admin</option></select></div>
      </div>
      <button type="submit" class="btn-submit">Tambah User</button>
    </form>
  </div>

  <div class="panel full">
    <div class="actions-row">
      <h2 style="margin-bottom:0;">Daftar User</h2>
      <input class="search-input" type="text" id="s-user" placeholder="Cari user...">
    </div>
    <div class="table-wrap">
      <table id="t-user">
        <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Terdaftar</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($data_users as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['nama']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge <?= $u['role'] ?>"><?= $u['role'] ?></span></td>
            <td><?= htmlspecialchars($u['created_at']) ?></td>
            <td>
              <a class="btn-small btn-edit" href="edit_user.php?id=<?= $u['id'] ?>">Edit</a>
              <a class="btn-small btn-delete" href="?hapus=<?= $u['id'] ?>" onclick="return confirm('Hapus user ini?')">Hapus</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div></div>
<script>
document.getElementById('s-user').addEventListener('input',function(){
  const k=this.value.toLowerCase();
  document.querySelectorAll('#t-user tbody tr').forEach(r=>r.style.display=r.textContent.toLowerCase().includes(k)?'':'none');
});
</script>
</body></html>
