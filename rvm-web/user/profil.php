<?php
require_once '../auth/cek_session.php';
require_once '../config/db.php';
cek_login_role('user');

$halaman_aktif = 'profil';
$uid = $_SESSION['user_id'];
$pesan = ''; $error = '';

$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'profil') {
        $nama  = trim($_POST['nama']);
        $email = trim($_POST['email']);
        if (strlen($nama) < 3) { $error = 'Nama minimal 3 karakter'; }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Format email tidak valid'; }
        else {
            $cek = $conn->prepare("SELECT id FROM users WHERE email=? AND id!=?");
            $cek->execute([$email, $uid]);
            if ($cek->fetch()) { $error = 'Email sudah dipakai akun lain'; }
            else {
                $conn->prepare("UPDATE users SET nama=?, email=? WHERE id=?")->execute([$nama, $email, $uid]);
                $_SESSION['nama'] = $nama;
                $pesan = 'Profil berhasil diperbarui';
                $user['nama'] = $nama; $user['email'] = $email;
            }
        }
    }
    if ($aksi === 'password') {
        $lama = $_POST['pw_lama']; $baru = $_POST['pw_baru']; $ulang = $_POST['pw_ulang'];
        if (!password_verify($lama, $user['password'])) { $error = 'Password lama tidak sesuai'; }
        elseif (strlen($baru) < 6) { $error = 'Password baru minimal 6 karakter'; }
        elseif ($baru !== $ulang)  { $error = 'Konfirmasi password tidak cocok'; }
        else {
            $conn->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($baru, PASSWORD_BCRYPT), $uid]);
            $pesan = 'Password berhasil diubah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil - PlasticPoint</title>
  <link rel="stylesheet" href="../assets/css/user.css">
</head>
<body>

<?php include '../includes/sidebar_user.php'; ?>

<div class="desktop-wrapper">
  <div class="main">
    <div class="page-header"><h1>Profil Saya</h1></div>

    <?php if ($pesan): ?><div class="alert sukses"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="panel">
      <div class="panel-title">Data Diri</div>
      <form method="POST">
        <input type="hidden" name="aksi" value="profil">
        <div class="profil-grid">
          <div>
            <label>Nama lengkap</label>
            <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
          </div>
          <div>
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
          </div>
          <div>
            <label>Role</label>
            <input type="text" value="User" disabled>
          </div>
          <div>
            <label>Terdaftar sejak</label>
            <input type="text" value="<?= htmlspecialchars($user['created_at']) ?>" disabled>
          </div>
        </div>
        <button type="submit" class="btn-simpan">Simpan perubahan</button>
      </form>
    </div>

    <div class="panel">
      <div class="panel-title">Ganti Password</div>
      <form method="POST">
        <input type="hidden" name="aksi" value="password">
        <div class="profil-grid">
          <div>
            <label>Password lama</label>
            <div class="pw-wrap">
              <input type="password" name="pw_lama" id="pw1" required placeholder="Password saat ini">
              <span class="mata" onclick="toggleMata('pw1','e1','e1c')">
                <svg id="e1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg id="e1c" style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </span>
            </div>
          </div>
          <div></div>
          <div>
            <label>Password baru</label>
            <div class="pw-wrap">
              <input type="password" name="pw_baru" id="pw2" required placeholder="Min. 6 karakter">
              <span class="mata" onclick="toggleMata('pw2','e2','e2c')">
                <svg id="e2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg id="e2c" style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </span>
            </div>
          </div>
          <div>
            <label>Konfirmasi password baru</label>
            <div class="pw-wrap">
              <input type="password" name="pw_ulang" id="pw3" required placeholder="Ulangi password baru">
              <span class="mata" onclick="toggleMata('pw3','e3','e3c')">
                <svg id="e3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg id="e3c" style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </span>
            </div>
          </div>
        </div>
        <button type="submit" class="btn-simpan">Ubah password</button>
      </form>
    </div>
  </div>
</div>

<script>
function toggleMata(inputId, a, b) {
  const el = document.getElementById(inputId);
  const isHidden = el.type === 'password';
  el.type = isHidden ? 'text' : 'password';
  document.getElementById(a).style.display = isHidden ? 'none' : 'block';
  document.getElementById(b).style.display = isHidden ? 'block' : 'none';
}
</script>
</body>
</html>
