<?php
// $halaman_aktif: 'dashboard' | 'riwayat' | 'profil'
?>
<!-- ===== DESKTOP SIDEBAR ===== -->
<aside class="sidebar">
  <div class="sidebar-title">
    ♻️ PlasticPoint
    <div style="font-size:10px; color:#94a3b8; font-weight:400; margin-top:3px; letter-spacing:0.3px;">Reverse Vending Machine</div>
  </div>
  <div class="sidebar-profile">
    <div class="sidebar-avatar">👤</div>
    <div class="name"><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></div>
    <div class="role">Pengguna</div>
  </div>
  <div class="sidebar-divider"></div>
  <nav class="nav-desktop">
    <a href="dashboard.php" class="<?= $halaman_aktif==='dashboard' ? 'active':'' ?>"><span>🏠</span> Dashboard</a>
    <a href="riwayat.php"   class="<?= $halaman_aktif==='riwayat'   ? 'active':'' ?>"><span>📋</span> Riwayat</a>
    <a href="profil.php"    class="<?= $halaman_aktif==='profil'    ? 'active':'' ?>"><span>👤</span> Profil</a>
    <a href="../auth/logout.php" class="logout"><span>↪</span> Logout</a>
  </nav>
</aside>

<!-- ===== MOBILE HEADER ===== -->
<div class="mobile-header">
  <div class="mobile-header-top">
    <div style="display:flex; flex-direction:column; line-height:1.2;">
      <span class="brand">♻️ PlasticPoint</span>
      <span style="font-size:10px; color:#94a3b8; margin-left:22px;">Reverse Vending Machine</span>
    </div>
    <button class="btn-notif" title="Notifikasi">🔔</button>
  </div>
  <div class="mobile-header-profile">
    <div class="mobile-avatar">👤</div>
    <div>
      <div class="username"><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></div>
      <div class="greeting">Selamat datang kembali 👋</div>
    </div>
  </div>
</div>

<!-- ===== BOTTOM NAV (mobile) ===== -->
<nav class="bottom-nav">
  <a href="dashboard.php" class="<?= $halaman_aktif==='dashboard' ? 'active':'' ?>">
    <span class="bn-icon">🏠</span> Dashboard
  </a>
  <a href="riwayat.php" class="<?= $halaman_aktif==='riwayat' ? 'active':'' ?>">
    <span class="bn-icon">📋</span> Riwayat
  </a>
  <a href="profil.php" class="<?= $halaman_aktif==='profil' ? 'active':'' ?>">
    <span class="bn-icon">👤</span> Profil
  </a>
  <a href="../auth/logout.php">
    <span class="bn-icon">↪</span> Logout
  </a>
</nav>
