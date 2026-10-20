<?php
// $halaman_aktif: 'dashboard' | 'user' | 'sampah' | 'laporan' | 'monitoring'
?>
<!-- ===== MOBILE TOPBAR ===== -->
<div class="admin-topbar-mobile">
  <button class="admin-hamburger" onclick="toggleAdminSidebar()" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
  <div style="display:flex; flex-direction:column; align-items:center; line-height:1.2;">
    <span class="brand">♻️ PlasticPoint Admin</span>
    <span style="font-size:9px; color:#94a3b8;">Reverse Vending Machine</span>
  </div>
  <div style="width:40px;"></div>
</div>

<!-- ===== OVERLAY (mobile) ===== -->
<div class="admin-overlay" id="admin-overlay" onclick="tutupAdminSidebar()"></div>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="admin-sidebar">
  <div class="sidebar-title">
    ♻️ PlasticPoint Admin
    <div style="font-size:10px; color:#94a3b8; font-weight:400; margin-top:3px; letter-spacing:0.3px;">Reverse Vending Machine</div>
  </div>
  <div class="sidebar-profile">
    <div class="avatar">🛠️</div>
    <div class="name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></div>
    <div class="role">Administrator</div>
  </div>
  <div class="sidebar-divider"></div>
  <nav class="nav">
    <a href="dashboard.php"  class="<?= ($halaman_aktif==='dashboard')  ? 'active':'' ?>"><span class="icon">🏠</span> Dashboard</a>
    <a href="monitoring.php" class="<?= ($halaman_aktif==='monitoring') ? 'active':'' ?>"><span class="icon">📡</span> Monitoring</a>
    <a href="kelola_user.php" class="<?= ($halaman_aktif==='user')      ? 'active':'' ?>"><span class="icon">👥</span> Data Pengguna</a>
    <a href="data_sampah.php" class="<?= ($halaman_aktif==='sampah')    ? 'active':'' ?>"><span class="icon">📋</span> Data Transaksi</a>
    <a href="laporan.php"    class="<?= ($halaman_aktif==='laporan')    ? 'active':'' ?>"><span class="icon">📊</span> Laporan & Ekspor</a>
    <a href="../auth/logout.php" class="logout"><span class="icon">↪</span> Logout</a>
  </nav>
</aside>

<script>
function toggleAdminSidebar() {
  document.getElementById('admin-sidebar').classList.toggle('terbuka');
  document.getElementById('admin-overlay').classList.toggle('aktif');
}
function tutupAdminSidebar() {
  document.getElementById('admin-sidebar').classList.remove('terbuka');
  document.getElementById('admin-overlay').classList.remove('aktif');
}
window.addEventListener('resize', function () {
  if (window.innerWidth > 768) tutupAdminSidebar();
});
</script>
