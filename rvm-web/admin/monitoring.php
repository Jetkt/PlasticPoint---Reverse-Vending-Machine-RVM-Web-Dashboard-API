<?php
require_once '../auth/cek_session.php';
require_once '../config/db.php';
cek_login_role('admin');
$halaman_aktif = 'monitoring';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monitoring - PlasticPoint Admin</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <style>
    /* ===== STATUS ALAT ===== */
    .status-bar {
      display: flex; align-items: center; gap: 16px;
      background: #fff; border-radius: 12px; padding: 16px 22px;
      box-shadow: 0 1px 3px rgba(0,0,0,.06); margin-bottom: 22px;
      flex-wrap: wrap; justify-content: space-between;
    }
    .status-left { display: flex; align-items: center; gap: 14px; }
    .pulse-wrap { position: relative; width: 18px; height: 18px; flex-shrink: 0; }
    .pulse-dot {
      width: 14px; height: 14px; border-radius: 50%;
      background: #dc2626; position: absolute; top: 2px; left: 2px;
      transition: background 0.5s;
    }
    .pulse-dot.online { background: #16a34a; }
    .pulse-ring {
      width: 18px; height: 18px; border-radius: 50%;
      border: 2px solid #16a34a; position: absolute; top: 0; left: 0;
      opacity: 0; display: none;
    }
    .pulse-dot.online ~ .pulse-ring {
      display: block;
      animation: pulse-anim 1.8s ease-out infinite;
    }
    @keyframes pulse-anim {
      0%   { transform: scale(1);   opacity: 0.7; }
      100% { transform: scale(2.4); opacity: 0;   }
    }
    .status-label { font-size: 16px; font-weight: 700; }
    .status-sub   { font-size: 12.5px; color: #6b7280; margin-top: 2px; }
    .waktu-server { font-size: 12px; color: #9ca3af; }

    /* ===== PANEL SENSOR ===== */
    .sensor-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px; margin-bottom: 22px;
    }
    .sensor-card {
      background: #fff; border-radius: 12px; padding: 18px 16px;
      box-shadow: 0 1px 3px rgba(0,0,0,.06);
      border-top: 3px solid #e5e7eb;
      transition: border-color 0.4s;
    }
    .sensor-card.aktif-pet  { border-top-color: #16a34a; }
    .sensor-card.aktif-pp   { border-top-color: #ea580c; }
    .sensor-card.aktif-ok   { border-top-color: #2563eb; }
    .sensor-card.aktif-warn { border-top-color: #f59e0b; }

    .sensor-label { font-size: 11.5px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
    .sensor-val   { font-size: 22px; font-weight: 700; color: #0f2147; }
    .sensor-sub   { font-size: 11.5px; color: #9ca3af; margin-top: 4px; }

    /* Badge status sensor */
    .sbadge {
      display: inline-block; padding: 4px 12px; border-radius: 20px;
      font-size: 13px; font-weight: 700;
    }
    .sbadge.terdeteksi  { background: #dcfce7; color: #16a34a; }
    .sbadge.tidak       { background: #fee2e2; color: #dc2626; }
    .sbadge.terbuka     { background: #e4ecfb; color: #2563eb; }
    .sbadge.tertutup    { background: #f3f4f6; color: #6b7280; }
    .sbadge.pet         { background: #dcfce7; color: #16a34a; }
    .sbadge.pp          { background: #ffedd5; color: #ea580c; }
    .sbadge.none        { background: #f3f4f6; color: #9ca3af; }

    /* Bar confidence */
    .conf-track { height: 10px; background: #f3f4f6; border-radius: 5px; margin-top: 10px; overflow: hidden; }
    .conf-fill  {
      height: 100%; border-radius: 5px;
      background: linear-gradient(90deg, #16a34a, #4ade80);
      transition: width 0.6s ease;
    }

    /* ===== GRAFIK CONFIDENCE ===== */
    .grafik-wrap {
      background: #fff; border-radius: 12px; padding: 20px 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,.06); margin-bottom: 22px;
    }
    .grafik-wrap h2 { font-size: 15px; font-weight: 700; margin-bottom: 16px; }
    .grafik-canvas-wrap { position: relative; height: 160px; }
    canvas#grafik-conf  { width: 100% !important; height: 160px !important; }
    .grafik-empty { text-align: center; color: #9ca3af; padding: 50px 0; font-size: 13px; }

    /* ===== LOG TABLE ===== */
    .log-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .log-header h2 { font-size: 15px; font-weight: 700; margin: 0; }
    .refresh-badge {
      font-size: 11.5px; color: #9ca3af;
      display: flex; align-items: center; gap: 6px;
    }
    .refresh-dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: #16a34a; animation: blink 1.2s ease-in-out infinite;
    }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

    /* Baris baru flash */
    @keyframes row-flash {
      from { background: #fef9c3; }
      to   { background: transparent; }
    }
    .row-baru td { animation: row-flash 1.2s ease-out forwards; }

    /* ===== STAT HARI INI ===== */
    .stat-grid {
      display: grid; grid-template-columns: repeat(4,1fr);
      gap: 12px; margin-bottom: 22px;
    }
    .stat-card {
      background: #fff; border-radius: 10px; padding: 14px 16px;
      box-shadow: 0 1px 3px rgba(0,0,0,.06); text-align: center;
    }
    .stat-label { font-size: 11.5px; color: #6b7280; margin-bottom: 6px; }
    .stat-val   { font-size: 22px; font-weight: 700; color: #0f2147; }

    /* ===== RESPONSIVE MOBILE ===== */
    @media (max-width: 768px) {
      .sensor-grid { grid-template-columns: 1fr 1fr; }
      .stat-grid   { grid-template-columns: 1fr 1fr; }
      .status-bar  { flex-direction: column; align-items: flex-start; gap: 10px; }
    }
    @media (max-width: 400px) {
      .sensor-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<?php include '../includes/sidebar_admin.php'; ?>
<div class="desktop-wrapper">
<div class="main">

  <!-- Topbar -->
  <div class="topbar">
    <div>
      <h1>Monitoring Real-Time</h1>
      <div class="subtitle">Status dan aktivitas sensor alat RVM secara langsung</div>
    </div>
  </div>

  <!-- Status alat -->
  <div class="status-bar">
    <div class="status-left">
      <div class="pulse-wrap">
        <div class="pulse-dot" id="pulse-dot"></div>
        <div class="pulse-ring"></div>
      </div>
      <div>
        <div class="status-label" id="status-label">Memeriksa koneksi...</div>
        <div class="status-sub"  id="status-sub">Memuat data sensor</div>
      </div>
    </div>
    <div class="waktu-server">
      Update terakhir: <span id="waktu-server">--:--:--</span>
      &nbsp;·&nbsp; Refresh otomatis setiap 5 detik
    </div>
  </div>

  <!-- User aktif di mesin -->
  <div id="panel-user-aktif" style="
    background:#fff; border-radius:12px; padding:16px 22px;
    box-shadow:0 1px 3px rgba(0,0,0,.06); margin-bottom:22px;
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
    border-left: 4px solid #e5e7eb; transition: border-color 0.4s;
  ">
    <div style="font-size:24px">👤</div>
    <div>
      <div style="font-size:13px; font-weight:700; color:#0f2147;" id="ua-nama">Tidak ada user aktif</div>
      <div style="font-size:12px; color:#9ca3af; margin-top:2px;" id="ua-sub">Menunggu user membuka dashboard...</div>
    </div>
  </div>

  <!-- Stat hari ini -->
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-label">Log hari ini</div>
      <div class="stat-val" id="stat-total">—</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">PET hari ini</div>
      <div class="stat-val" id="stat-pet" style="color:#16a34a">—</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">PP hari ini</div>
      <div class="stat-val" id="stat-pp" style="color:#ea580c">—</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Rata-rata confidence</div>
      <div class="stat-val" id="stat-conf" style="color:#2563eb">—</div>
    </div>
  </div>

  <!-- Panel sensor terkini -->
  <div class="sensor-grid">
    <!-- Infrared -->
    <div class="sensor-card" id="card-infrared">
      <div class="sensor-label">🔴 Sensor Infrared</div>
      <div class="sensor-val">
        <span class="sbadge none" id="val-infrared">—</span>
      </div>
      <div class="sensor-sub" id="sub-infrared">Menunggu data</div>
    </div>
    <!-- Servo -->
    <div class="sensor-card" id="card-servo">
      <div class="sensor-label">⚙️ Status Servo</div>
      <div class="sensor-val">
        <span class="sbadge none" id="val-servo">—</span>
      </div>
      <div class="sensor-sub" id="sub-servo">Menunggu data</div>
    </div>
    <!-- Klasifikasi CNN -->
    <div class="sensor-card" id="card-klasifikasi">
      <div class="sensor-label">🤖 Hasil CNN</div>
      <div class="sensor-val">
        <span class="sbadge none" id="val-klasifikasi">—</span>
      </div>
      <div class="sensor-sub" id="sub-klasifikasi">Belum ada klasifikasi</div>
    </div>
    <!-- Confidence -->
    <div class="sensor-card" id="card-conf">
      <div class="sensor-label">📊 Confidence Score</div>
      <div class="sensor-val" id="val-conf">—</div>
      <div class="conf-track"><div class="conf-fill" id="conf-fill" style="width:0%"></div></div>
      <div class="sensor-sub" id="sub-conf">Akurasi model CNN</div>
    </div>
  </div>

  <!-- Grafik confidence -->
  <div class="grafik-wrap">
    <h2>Grafik Confidence CNN (50 data terakhir)</h2>
    <div class="grafik-canvas-wrap">
      <canvas id="grafik-conf"></canvas>
      <div class="grafik-empty" id="grafik-empty">Belum ada data confidence — grafik akan muncul saat ESP32 mulai mengirim data</div>
    </div>
  </div>

  <!-- Log sensor -->
  <div class="panel full">
    <div class="log-header">
      <h2>Log Aktivitas Sensor</h2>
      <div class="refresh-badge">
        <div class="refresh-dot"></div>
        Live &mdash; auto-refresh
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Waktu</th>
            <th>Infrared</th>
            <th>Servo</th>
            <th>Klasifikasi CNN</th>
            <th>Confidence</th>
          </tr>
        </thead>
        <tbody id="log-tbody">
          <tr><td colspan="5" class="empty-msg">Memuat data...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<script>
// ===== Setup Chart.js =====
const ctx = document.getElementById('grafik-conf').getContext('2d');
const chart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: [],
    datasets: [
      {
        label: 'Confidence (%)',
        data: [],
        borderColor: '#2563eb',
        backgroundColor: 'rgba(37,99,235,0.08)',
        borderWidth: 2,
        pointRadius: 3,
        pointBackgroundColor: ctx => {
          const val = ctx.dataset.data[ctx.dataIndex];
          return val >= 80 ? '#16a34a' : val >= 60 ? '#f59e0b' : '#dc2626';
        },
        tension: 0.35,
        fill: true,
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => ctx.parsed.y.toFixed(1) + '% confidence',
          title: ctx => ctx[0].label,
        }
      }
    },
    scales: {
      y: {
        min: 0, max: 100,
        ticks: { callback: v => v + '%', font: { size: 11 } },
        grid: { color: 'rgba(0,0,0,0.05)' }
      },
      x: {
        ticks: {
          maxTicksLimit: 8,
          font: { size: 10 },
          maxRotation: 0,
        },
        grid: { display: false }
      }
    }
  }
});

// ===== Utilitas =====
function badgeKlas(k) {
  if (!k || k === 'Tidak Dikenali') return '<span class="sbadge none">—</span>';
  if (k === 'PET') return '<span class="sbadge pet">PET</span>';
  if (k === 'PP')  return '<span class="sbadge pp">PP</span>';
  return `<span class="sbadge none">${k}</span>`;
}
function badgeInfra(v) {
  if (!v) return '<span class="sbadge none">—</span>';
  const ok = v === 'terdeteksi';
  return `<span class="sbadge ${ok ? 'terdeteksi' : 'tidak'}">${ok ? 'Terdeteksi' : 'Tidak terdeteksi'}</span>`;
}
function badgeServo(v) {
  if (!v) return '<span class="sbadge none">—</span>';
  const ok = v === 'terbuka';
  return `<span class="sbadge ${ok ? 'terbuka' : 'tertutup'}">${ok ? 'Terbuka' : 'Tertutup'}</span>`;
}
function confColor(v) {
  if (v >= 90) return '#16a34a';
  if (v >= 75) return '#2563eb';
  if (v >= 60) return '#f59e0b';
  return '#dc2626';
}

let idLogLama = null;

// ===== Fungsi refresh data =====
async function refreshData() {
  try {
    const res  = await fetch('api_monitoring.php');
    const json = await res.json();

    // Waktu server
    document.getElementById('waktu-server').textContent = json.waktu_server;

    // User aktif di mesin
    const panel = document.getElementById('panel-user-aktif');
    const ua    = json.user_aktif;
    if (ua) {
      document.getElementById('ua-nama').textContent =
        `${ua.nama} sedang aktif di mesin`;
      document.getElementById('ua-sub').textContent =
        `Heartbeat ${ua.detik_lalu} detik lalu · siap menerima sampah`;
      panel.style.borderLeftColor = '#16a34a';
      panel.style.background      = '#f0fdf4';
    } else {
      document.getElementById('ua-nama').textContent = 'Tidak ada user aktif';
      document.getElementById('ua-sub').textContent  = 'Menunggu user membuka dashboard di HP...';
      panel.style.borderLeftColor = '#e5e7eb';
      panel.style.background      = '#fff';
    }

    // Status alat
    const dot   = document.getElementById('pulse-dot');
    const label = document.getElementById('status-label');
    const sub   = document.getElementById('status-sub');

    if (json.alat_online) {
      dot.className   = 'pulse-dot online';
      label.textContent = 'Alat RVM: Online';
      label.style.color = '#16a34a';
      sub.textContent   = 'Data diterima dalam 10 menit terakhir';
    } else {
      dot.className   = 'pulse-dot';
      label.textContent = 'Alat RVM: Offline';
      label.style.color = '#dc2626';
      sub.textContent   = json.menit_lalu
        ? `Terakhir aktif ${json.menit_lalu} menit lalu`
        : 'Belum ada data masuk dari ESP32';
    }

    // Stat hari ini
    if (json.stat) {
      document.getElementById('stat-total').textContent = json.stat.total_log;
      document.getElementById('stat-pet').textContent   = json.stat.total_pet;
      document.getElementById('stat-pp').textContent    = json.stat.total_pp;
      document.getElementById('stat-conf').textContent  =
        json.stat.rata_confidence > 0 ? json.stat.rata_confidence + '%' : '—';
    }

    // Panel sensor terkini
    const s = json.sensor_terbaru;
    if (s) {
      // Infrared
      document.getElementById('val-infrared').outerHTML =
        `<span class="sbadge ${s.status_infrared === 'terdeteksi' ? 'terdeteksi' : 'tidak'}" id="val-infrared">
          ${s.status_infrared === 'terdeteksi' ? 'Terdeteksi' : (s.status_infrared || '—')}
        </span>`;
      document.getElementById('sub-infrared').textContent = 'Data sensor infrared terkini';

      // Servo
      document.getElementById('val-servo').outerHTML =
        `<span class="sbadge ${s.status_servo === 'terbuka' ? 'terbuka' : 'tertutup'}" id="val-servo">
          ${s.status_servo ? s.status_servo.charAt(0).toUpperCase() + s.status_servo.slice(1) : '—'}
        </span>`;
      document.getElementById('sub-servo').textContent = 'Posisi servo saat ini';

      // Klasifikasi CNN
      const k = s.hasil_klasifikasi;
      document.getElementById('val-klasifikasi').outerHTML =
        `<span class="sbadge ${k === 'PET' ? 'pet' : k === 'PP' ? 'pp' : 'none'}" id="val-klasifikasi">
          ${k || '—'}
        </span>`;
      document.getElementById('card-klasifikasi').className =
        'sensor-card' + (k === 'PET' ? ' aktif-pet' : k === 'PP' ? ' aktif-pp' : '');
      document.getElementById('sub-klasifikasi').textContent =
        k ? `Hasil klasifikasi terakhir: ${k}` : 'Belum ada klasifikasi';

      // Confidence
      if (s.confidence !== null && s.confidence !== undefined) {
        const pct = Math.round(s.confidence * 100);
        document.getElementById('val-conf').textContent = pct + '%';
        document.getElementById('val-conf').style.color = confColor(pct);
        document.getElementById('conf-fill').style.width = pct + '%';
        document.getElementById('conf-fill').style.background =
          pct >= 80 ? 'linear-gradient(90deg,#16a34a,#4ade80)'
          : pct >= 60 ? 'linear-gradient(90deg,#f59e0b,#fcd34d)'
          : 'linear-gradient(90deg,#dc2626,#f87171)';
        document.getElementById('sub-conf').textContent = pct >= 80
          ? 'Keyakinan tinggi ✓' : pct >= 60
          ? 'Keyakinan cukup' : 'Keyakinan rendah — perlu dicek';
        document.getElementById('card-conf').className =
          'sensor-card' + (pct >= 80 ? ' aktif-ok' : pct >= 60 ? '' : ' aktif-warn');
      } else {
        document.getElementById('val-conf').textContent = '—';
        document.getElementById('sub-conf').textContent = 'Tidak ada data confidence';
      }
    }

    // ===== Log tabel =====
    const tbody = document.getElementById('log-tbody');
    if (!json.log || json.log.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="empty-msg">Belum ada log sensor — tunggu ESP32 mengirim data</td></tr>';
    } else {
      const idBaru = json.log[0]?.id;
      const adaBaru = idLogLama !== null && idBaru !== idLogLama;
      idLogLama = idBaru;

      tbody.innerHTML = json.log.map((r, i) => {
        const conf = r.confidence !== null
          ? `<span style="font-weight:700;color:${confColor(Math.round(r.confidence * 100))}">${Math.round(r.confidence * 100)}%</span>`
          : '<span style="color:#9ca3af">—</span>';
        const rowClass = adaBaru && i === 0 ? 'row-baru' : '';
        return `<tr class="${rowClass}">
          <td style="white-space:nowrap;font-size:12.5px">${r.timestamp}</td>
          <td>${badgeInfra(r.status_infrared)}</td>
          <td>${badgeServo(r.status_servo)}</td>
          <td>${badgeKlas(r.hasil_klasifikasi)}</td>
          <td>${conf}</td>
        </tr>`;
      }).join('');
    }

    // ===== Grafik confidence =====
    if (json.grafik && json.grafik.length > 0) {
      document.getElementById('grafik-empty').style.display = 'none';
      chart.data.labels   = json.grafik.map(g => {
        const t = new Date(g.timestamp);
        return t.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
      });
      chart.data.datasets[0].data = json.grafik.map(g => +(g.confidence * 100).toFixed(1));
      chart.data.datasets[0].pointBackgroundColor = json.grafik.map(g => {
        const pct = g.confidence * 100;
        return pct >= 80 ? '#16a34a' : pct >= 60 ? '#f59e0b' : '#dc2626';
      });
      chart.update('none'); // update tanpa animasi agar tidak berkedip
    } else {
      document.getElementById('grafik-empty').style.display = 'block';
    }

  } catch (e) {
    document.getElementById('status-label').textContent = 'Gagal terhubung ke server';
    document.getElementById('status-label').style.color = '#dc2626';
  }
}

// Jalankan pertama kali, lalu setiap 5 detik
refreshData();
setInterval(refreshData, 5000);
</script>
</body>
</html>
