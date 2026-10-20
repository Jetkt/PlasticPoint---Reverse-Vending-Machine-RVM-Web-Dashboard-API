<?php
require_once '../auth/cek_session.php';
require_once '../config/db.php';
cek_login_role('user');

$halaman_aktif = 'dashboard';
$uid = $_SESSION['user_id'];

// Total poin & transaksi
$stmt_poin = $conn->prepare("SELECT COALESCE(SUM(poin),0) AS t FROM riwayat_setor WHERE user_id=?");
$stmt_poin->execute([$uid]);
$total_poin = (int)$stmt_poin->fetch()['t'];

$stmt_trx = $conn->prepare("SELECT COUNT(*) AS t FROM riwayat_setor WHERE user_id=?");
$stmt_trx->execute([$uid]);
$total_trx = (int)$stmt_trx->fetch()['t'];

// Riwayat 10 terakhir
$stmt_riwayat = $conn->prepare("SELECT * FROM riwayat_setor WHERE user_id=? ORDER BY waktu DESC LIMIT 10");
$stmt_riwayat->execute([$uid]);
$riwayat = $stmt_riwayat->fetchAll(PDO::FETCH_ASSOC);

// ===== Grafik bulanan 12 bulan terakhir =====
// Siapkan semua 12 bulan dengan nilai 0 dulu
$bulan_labels = [];
$bulan_map    = [];
for ($i = 11; $i >= 0; $i--) {
    $key            = date('Y-m', strtotime("-$i months"));
    $label          = date('M Y', strtotime("-$i months")); // Jan 2025
    $bulan_labels[] = $label;
    $bulan_map[$key] = 0;
}

// Query data poin per bulan
$stmt_grafik = $conn->prepare("
    SELECT DATE_FORMAT(waktu, '%Y-%m') AS bulan,
           SUM(poin) AS total_poin
    FROM riwayat_setor
    WHERE user_id = ?
      AND waktu >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(waktu, '%Y-%m')
    ORDER BY bulan ASC
");
$stmt_grafik->execute([$uid]);
foreach ($stmt_grafik->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (isset($bulan_map[$row['bulan']])) {
        $bulan_map[$row['bulan']] = (int)$row['total_poin'];
    }
}

$grafik_labels = json_encode($bulan_labels);
$grafik_data   = json_encode(array_values($bulan_map));
$grafik_max    = max(array_values($bulan_map) ?: [0]);
$ada_data_grafik = $grafik_max > 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - PlasticPoint</title>
  <link rel="stylesheet" href="../assets/css/user.css">
  <style>
    /* Status aktif di mesin */
    .mesin-status {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 16px; border-radius: 10px;
      margin-bottom: 16px; font-size: 13.5px; font-weight: 600;
      transition: all 0.4s;
    }
    .mesin-status.aktif       { background:#dcfce7; color:#15803d; border:1.5px solid #bbf7d0; }
    .mesin-status.tidak-aktif { background:#f3f4f6; color:#6b7280; border:1.5px solid #e5e7eb; }
    .mesin-status .dot-mesin  {
      width:10px; height:10px; border-radius:50%;
      background:#9ca3af; flex-shrink:0; transition:background 0.4s;
    }
    .mesin-status.aktif .dot-mesin {
      background:#16a34a;
      animation: denyut 1.6s ease-in-out infinite;
    }
    @keyframes denyut { 0%,100%{opacity:1} 50%{opacity:0.4} }

    .btn-selesai {
      display: block; width: 100%;
      background: #dc2626; color: #fff; border: none;
      padding: 14px; border-radius: 10px;
      font-size: 14px; font-weight: 700; letter-spacing: 0.8px;
      text-transform: uppercase; cursor: pointer;
      margin-bottom: 20px; transition: background 0.2s;
    }
    .btn-selesai:hover    { background: #b91c1c; }
    .btn-selesai:disabled { background: #9ca3af; cursor: not-allowed; }

    /* Panel grafik */
    .grafik-panel {
      background: #fff; border-radius: 12px;
      padding: 18px 20px; box-shadow: 0 1px 4px rgba(0,0,0,.07);
      margin-bottom: 20px;
    }
    .grafik-header {
      display: flex; justify-content: space-between;
      align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;
    }
    .grafik-title  { font-size: 15px; font-weight: 700; color: #1f2937; }
    .grafik-total  {
      font-size: 12.5px; font-weight: 600;
      background: #dcfce7; color: #15803d;
      padding: 4px 12px; border-radius: 20px;
    }
    .grafik-canvas-wrap { position: relative; height: 180px; }
    canvas#chart-bulanan { width: 100% !important; height: 180px !important; }
    .grafik-kosong {
      text-align: center; color: #9ca3af;
      padding: 40px 0; font-size: 13px;
    }
    .grafik-kosong .ikon { font-size: 32px; margin-bottom: 8px; }

    /* Toast */
    .toast {
      position: fixed; bottom: 80px; left: 50%;
      transform: translateX(-50%);
      background: #0f2147; color: #fff;
      padding: 10px 22px; border-radius: 20px;
      font-size: 13px; font-weight: 600;
      z-index: 999; opacity: 0; transition: opacity 0.3s;
      white-space: nowrap; pointer-events: none;
    }
    .toast.tampil { opacity: 1; }
  </style>
</head>
<body>

<?php include '../includes/sidebar_user.php'; ?>
<div class="toast" id="toast"></div>

<div class="desktop-wrapper">
<div class="main">

  <!-- Header desktop -->
  <div class="page-header" id="desktop-header" style="display:none;">
    <h1>Dashboard</h1>
  </div>

  <!-- Status aktif di mesin -->
  <div class="mesin-status tidak-aktif" id="mesin-status">
    <div class="dot-mesin"></div>
    <span id="status-teks">Menghubungkan ke mesin...</span>
  </div>

  <!-- Kartu poin & transaksi -->
  <div class="cards">
    <div class="card poin">
      <div>
        <div class="card-label">Total Poin</div>
        <div class="card-value" id="val-poin"><?= $total_poin ?><span class="unit">Poin</span></div>
      </div>
      <div class="card-icon">⭐</div>
    </div>
    <div class="card trx">
      <div>
        <div class="card-label">Total Transaksi</div>
        <div class="card-value" id="val-trx"><?= $total_trx ?><span class="unit">Kali</span></div>
      </div>
      <div class="card-icon">♻️</div>
    </div>
  </div>

  <!-- Tombol selesai menyetor -->
  <button class="btn-selesai" id="btn-selesai" onclick="selesaiMenyetor()">
    ✓ Selesai Menyetor
  </button>

  <!-- ===== GRAFIK POIN BULANAN ===== -->
  <div class="grafik-panel">
    <div class="grafik-header">
      <div class="grafik-title">📊 Total Poin per Bulan</div>
      <div class="grafik-total">Total: <?= $total_poin ?> poin</div>
    </div>
    <?php if ($ada_data_grafik): ?>
    <div class="grafik-canvas-wrap">
      <canvas id="chart-bulanan"></canvas>
    </div>
    <?php else: ?>
    <div class="grafik-kosong">
      <div class="ikon">📊</div>
      Grafik akan muncul setelah kamu melakukan transaksi pertama
    </div>
    <?php endif; ?>
  </div>

  <!-- Riwayat transaksi -->
  <div class="panel">
    <div class="panel-title">Riwayat Transaksi</div>
    <div class="riwayat-list" id="riwayat-list">
      <?php if (empty($riwayat)): ?>
        <div class="empty-msg">Belum ada transaksi</div>
      <?php else: foreach ($riwayat as $r): ?>
        <?php $isPet = $r['jenis_sampah'] === 'PET'; ?>
        <div class="riwayat-item">
          <div class="riwayat-icon <?= $isPet ? 'pet':'pp' ?>">♻️</div>
          <div class="riwayat-info">
            <div class="riwayat-jenis"><?= $r['jenis_sampah'] ?></div>
            <div class="riwayat-tanggal"><?= htmlspecialchars($r['waktu']) ?></div>
          </div>
          <div class="riwayat-right">
            <div class="riwayat-poin">+<?= $r['poin'] ?></div>
            <div class="riwayat-status">Berhasil</div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <?php if (!empty($riwayat)): ?>
    <div style="margin-top:14px; text-align:center;">
      <a href="riwayat.php" style="font-size:13px; color:#16a34a; font-weight:600; text-decoration:none;">
        Lihat semua riwayat →
      </a>
    </div>
    <?php endif; ?>
  </div>

</div>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// ===== Grafik bulanan =====
<?php if ($ada_data_grafik): ?>
const ctx = document.getElementById('chart-bulanan').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= $grafik_labels ?>,
    datasets: [{
      label: 'Total Poin',
      data: <?= $grafik_data ?>,
      backgroundColor: function(ctx) {
        const val = ctx.dataset.data[ctx.dataIndex];
        const max = Math.max(...ctx.dataset.data);
        // Bulan tertinggi lebih pekat
        const alpha = max > 0 ? 0.4 + (val / max) * 0.55 : 0.4;
        return `rgba(22, 163, 74, ${alpha})`;
      },
      borderColor: '#16a34a',
      borderWidth: 1.5,
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => `${ctx.parsed.y} poin`,
          title: ctx => ctx[0].label,
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          stepSize: <?= max(10, ceil($grafik_max / 5 / 10) * 10) ?>,
          font: { size: 11 },
          color: '#9ca3af',
          callback: v => v + ' poin',
        },
        grid: { color: 'rgba(0,0,0,0.05)' },
        border: { display: false },
      },
      x: {
        ticks: {
          font: { size: 10 },
          color: '#6b7280',
          maxRotation: 35,
          minRotation: 0,
        },
        grid: { display: false },
        border: { display: false },
      }
    }
  }
});
<?php endif; ?>

// ===== Responsif desktop =====
if (window.innerWidth > 768) document.getElementById('desktop-header').style.display = 'flex';
window.addEventListener('resize', () => {
  document.getElementById('desktop-header').style.display =
    window.innerWidth > 768 ? 'flex' : 'none';
});

// ===== Toast =====
function tampilToast(pesan, durasi = 2800) {
  const t = document.getElementById('toast');
  t.textContent = pesan;
  t.classList.add('tampil');
  setTimeout(() => t.classList.remove('tampil'), durasi);
}

// ===== Heartbeat =====
let heartbeatAktif  = true;
let jumlahTrxSblm   = <?= $total_trx ?>;

async function kirimHeartbeat() {
  if (!heartbeatAktif) return;
  try {
    const res  = await fetch('../api/heartbeat.php');
    const json = await res.json();
    if (json.status === 'ok') {
      document.getElementById('mesin-status').className = 'mesin-status aktif';
      document.getElementById('status-teks').textContent = 'Anda sedang aktif di mesin — siap menyetor sampah';
      document.getElementById('btn-selesai').disabled = false;
    }
  } catch (e) {
    document.getElementById('mesin-status').className = 'mesin-status tidak-aktif';
    document.getElementById('status-teks').textContent = 'Tidak dapat terhubung ke server';
  }
}

// ===== Selesai menyetor =====
async function selesaiMenyetor() {
  const btn = document.getElementById('btn-selesai');
  btn.disabled = true;
  btn.textContent = 'Memproses...';
  heartbeatAktif = false;
  try { await fetch('../api/selesai_sesi.php'); } catch (e) {}
  document.getElementById('mesin-status').className = 'mesin-status tidak-aktif';
  document.getElementById('status-teks').textContent = 'Sesi selesai — terima kasih sudah menyetor!';
  btn.textContent = '✓ Sesi Selesai';
  tampilToast('✓ Sesi menyetor selesai!');
  await refreshData();
}

// ===== Refresh data berkala =====
async function refreshData() {
  try {
    const res  = await fetch('dashboard_data.php');
    const json = await res.json();

    document.getElementById('val-poin').innerHTML = json.total_poin + '<span class="unit">Poin</span>';
    document.getElementById('val-trx').innerHTML  = json.total_trx  + '<span class="unit">Kali</span>';

    if (json.total_trx > jumlahTrxSblm) {
      jumlahTrxSblm = json.total_trx;
      tampilToast('♻️ Sampah berhasil dicatat! +10 poin');
      renderRiwayat(json.riwayat);
    }
  } catch (e) {}
}

function renderRiwayat(list) {
  if (!list || !list.length) return;
  document.getElementById('riwayat-list').innerHTML = list.map(r => `
    <div class="riwayat-item">
      <div class="riwayat-icon ${r.jenis_sampah==='PET'?'pet':'pp'}">♻️</div>
      <div class="riwayat-info">
        <div class="riwayat-jenis">${r.jenis_sampah}</div>
        <div class="riwayat-tanggal">${r.waktu}</div>
      </div>
      <div class="riwayat-right">
        <div class="riwayat-poin">+${r.poin}</div>
        <div class="riwayat-status">Berhasil</div>
      </div>
    </div>`).join('');
}

// ===== Mulai =====
kirimHeartbeat();
setInterval(kirimHeartbeat, 10000);
setInterval(refreshData,    8000);

window.addEventListener('beforeunload', () => {
  heartbeatAktif = false;
  navigator.sendBeacon('../api/selesai_sesi.php');
});
</script>
</body>
</html>
