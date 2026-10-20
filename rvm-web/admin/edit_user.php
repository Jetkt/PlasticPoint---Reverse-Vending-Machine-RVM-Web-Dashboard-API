<?php
require_once '../auth/cek_session.php';
require_once '../config/db.php';
cek_login_role('admin');
$halaman_aktif = 'user';
$error = '';
$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { header('Location: kelola_user.php'); exit; }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nama=trim($_POST['nama']); $email=trim($_POST['email']); $role=$_POST['role']; $pw=trim($_POST['password']);
    $cek=$conn->prepare("SELECT id FROM users WHERE email=? AND id!=?"); $cek->execute([$email,$id]);
    if ($cek->fetch()) { $error='Email sudah dipakai user lain'; }
    else {
        if ($pw!=='') { $conn->prepare("UPDATE users SET nama=?,email=?,role=?,password=? WHERE id=?")->execute([$nama,$email,$role,password_hash($pw,PASSWORD_BCRYPT),$id]); }
        else { $conn->prepare("UPDATE users SET nama=?,email=?,role=? WHERE id=?")->execute([$nama,$email,$role,$id]); }
        header('Location: kelola_user.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit User - PlasticPoint Admin</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>
<div class="desktop-wrapper"><div class="main">
  <div class="topbar"><div><h1>Edit User</h1><div class="subtitle">Ubah data: <?= htmlspecialchars($user['nama']) ?></div></div></div>
  <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <div class="panel full" style="max-width:520px;">
    <form class="tambah" method="POST">
      <label>Nama</label><input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
      <label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
      <label>Password baru (kosongkan jika tidak diubah)</label><input type="text" name="password" placeholder="Kosongkan jika tidak ganti">
      <label>Role</label>
      <select name="role">
        <option value="user"  <?= $user['role']==='user'?'selected':''  ?>>User</option>
        <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Admin</option>
      </select>
      <div style="display:flex;gap:12px;align-items:center;margin-top:4px;">
        <button type="submit" class="btn-submit" style="width:auto;">Simpan</button>
        <a href="kelola_user.php" style="font-size:13px;color:#6b7280;">Batal</a>
      </div>
    </form>
  </div>
</div></div>
</body></html>
