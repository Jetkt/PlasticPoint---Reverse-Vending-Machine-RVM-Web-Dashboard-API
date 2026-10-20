<?php
session_start();
require_once '../config/db.php';

$error  = '';
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = trim($_POST['nama']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi'];

    if (strlen($nama) < 3) {
        $error = 'Nama minimal 3 karakter';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok';
    } else {
        $cek = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $cek->execute([$email]);
        if ($cek->fetch()) {
            $error = 'Email sudah terdaftar, gunakan email lain';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'user')")
                 ->execute([$nama, $email, $hash]);
            $sukses = 'Akun berhasil dibuat!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Akun - PlasticPoint</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ===== HEADER MOBILE (hanya muncul di HP) ===== */
    .mobile-top {
      display: none;
      background: #0f2147;
      padding: 20px 20px 44px;
      flex-direction: column;
    }
    .mobile-top-bar {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
    }
    .btn-back {
      background: rgba(255,255,255,0.12);
      border: none; cursor: pointer;
      color: #fff; font-size: 18px;
      width: 36px; height: 36px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      text-decoration: none; flex-shrink: 0;
      transition: background 0.2s;
    }
    .btn-back:hover { background: rgba(255,255,255,0.2); }
    .mobile-top-bar .page-name {
      font-size: 16px; font-weight: 700; color: #fff;
    }
    .mobile-hero { text-align: center; }
    .mobile-hero .logo { font-size: 46px; margin-bottom: 10px; }
    .mobile-hero h1   { font-size: 22px; font-weight: 700; color: #fff; margin-bottom: 4px; }
    .mobile-hero p    { font-size: 13px; color: #94a3b8; }

    /* Langkah daftar (mobile) */
    .step-bar {
      display: flex; align-items: center; gap: 6px;
      justify-content: center; margin-top: 20px;
    }
    .step {
      display: flex; align-items: center; justify-content: center;
      width: 28px; height: 28px; border-radius: 50%;
      font-size: 12px; font-weight: 700;
      background: rgba(255,255,255,0.15); color: #fff;
    }
    .step.aktif { background: #16a34a; }
    .step-line { height: 2px; flex: 1; max-width: 32px; background: rgba(255,255,255,0.2); }

    /* ===== BACKGROUND DESKTOP ===== */
    .bg-desktop {
      flex: 1;
      background: linear-gradient(135deg, #0f2147 0%, #1a3a6e 55%, #16a34a 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 32px 16px;
    }

    /* ===== CARD FORM ===== */
    .daftar-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18);
      width: 100%;
      max-width: 420px;
      padding: 32px 32px 28px;
    }
    .card-logo  { text-align: center; font-size: 42px; margin-bottom: 6px; }
    .card-title { text-align: center; font-size: 20px; font-weight: 700; color: #0f2147; margin-bottom: 4px; }
    .card-sub   { text-align: center; font-size: 13px; color: #6b7280; margin-bottom: 22px; }

    /* ===== FORM ELEMEN ===== */
    label {
      display: block;
      font-size: 13px; font-weight: 600; color: #374151;
      margin-bottom: 5px;
    }
    input {
      width: 100%; padding: 11px 14px; margin-bottom: 14px;
      border: 1.5px solid #d1d5db; border-radius: 9px;
      font-size: 14px; outline: none; transition: border 0.2s;
    }
    input:focus  { border-color: #0f2147; }
    input.invalid { border-color: #dc2626; background: #fff5f5; }

    .pw-wrap { position: relative; }
    .pw-wrap input { padding-right: 44px; }
    .mata {
      position: absolute; right: 12px; top: 50%;
      transform: translateY(-62%);
      cursor: pointer; color: #9ca3af;
      background: none; border: none; padding: 0;
    }
    .mata:hover { color: #374151; }
    .mata svg { width: 20px; height: 20px; display: block; }

    .hint { font-size: 11.5px; color: #9ca3af; margin-top: -10px; margin-bottom: 14px; }

    .pw-match {
      font-size: 11.5px; margin-top: -10px; margin-bottom: 14px;
      display: none;
    }
    .pw-match.cocok  { color: #16a34a; display: block; }
    .pw-match.salah  { color: #dc2626; display: block; }

    /* ===== ALERT ===== */
    .alert {
      padding: 10px 14px; border-radius: 9px;
      font-size: 13px; margin-bottom: 16px; text-align: center;
    }
    .alert.error  { background: #fee2e2; color: #dc2626; }
    .alert.sukses { background: #dcfce7; color: #16a34a; }

    /* ===== TOMBOL ===== */
    .btn-daftar {
      width: 100%; padding: 13px;
      background: #0f2147; color: #fff; border: none;
      border-radius: 9px; font-size: 15px; font-weight: 700;
      cursor: pointer; margin-top: 4px;
      transition: background 0.2s, transform 0.1s;
    }
    .btn-daftar:hover  { background: #16a34a; }
    .btn-daftar:active { transform: scale(0.98); }
    .btn-masuk {
      display: block; width: 100%; padding: 13px;
      background: #16a34a; color: #fff; border: none;
      border-radius: 9px; font-size: 15px; font-weight: 700;
      cursor: pointer; text-align: center; text-decoration: none;
      margin-top: 4px; transition: background 0.2s;
    }
    .btn-masuk:hover { background: #15803d; }

    .footer-link {
      text-align: center; margin-top: 18px;
      font-size: 13px; color: #6b7280;
    }
    .footer-link a { color: #16a34a; font-weight: 600; text-decoration: none; }
    .footer-link a:hover { text-decoration: underline; }

    /* ===== MOBILE ≤ 600px ===== */
    @media (max-width: 600px) {
      body { background: #f0f2f5; }

      .mobile-top { display: flex; }

      .bg-desktop {
        background: transparent;
        padding: 0;
        align-items: flex-start;
      }

      .daftar-card {
        border-radius: 20px 20px 0 0;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
        margin-top: -20px;
        padding: 28px 22px 36px;
        position: relative; z-index: 1;
        max-width: 100%;
        min-height: calc(100vh - 180px);
      }

      /* Sembunyikan logo/judul desktop di dalam card */
      .card-logo, .card-title, .card-sub { display: none; }
    }
  </style>
</head>
<body>

<!-- ===== HEADER MOBILE ===== -->
<div class="mobile-top">
  <div class="mobile-top-bar">
    <a href="login.php" class="btn-back">←</a>
    <span class="page-name">Buat Akun Baru</span>
  </div>
  <div class="mobile-hero">
    <div class="logo">♻️</div>
    <h1>PlasticPoint</h1>
    <p style="font-size:11px; color:#94a3b8; margin-top:2px;">Reverse Vending Machine</p>
    <p style="margin-top:4px;">Daftarkan diri untuk mulai menyetor sampah</p>
  </div>
  <div class="step-bar">
    <div class="step aktif">1</div>
    <div class="step-line"></div>
    <div class="step">2</div>
    <div class="step-line"></div>
    <div class="step">✓</div>
  </div>
</div>

<!-- ===== BACKGROUND + CARD ===== -->
<div class="bg-desktop">
  <div class="daftar-card">

    <!-- Header card (desktop) -->
    <div class="card-logo">♻️</div>
    <div class="card-title">PlasticPoint</div>
    <div style="text-align:center; font-size:11px; color:#9ca3af; margin-top:-12px; margin-bottom:4px;">Reverse Vending Machine</div>
    <div class="card-sub">Buat akun baru untuk mulai menyetor sampah</div>

    <!-- Alert error / sukses -->
    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($sukses): ?>
      <!-- State sukses -->
      <div style="text-align:center; padding: 8px 0 20px;">
        <div style="font-size:52px; margin-bottom:12px;">🎉</div>
        <div style="font-size:17px; font-weight:700; color:#0f2147; margin-bottom:6px;">Akun Berhasil Dibuat!</div>
        <div style="font-size:13px; color:#6b7280; margin-bottom:24px;">Silakan masuk menggunakan email dan password yang sudah kamu daftarkan.</div>
        <a href="login.php" class="btn-masuk">Masuk Sekarang →</a>
      </div>
      <div class="footer-link">
        <a href="login.php">← Kembali ke halaman login</a>
      </div>

    <?php else: ?>
      <!-- Form daftar -->
      <form method="POST" id="form-daftar">
        <label for="nama">Nama Lengkap</label>
        <input type="text" name="nama" id="nama" required placeholder="Nama kamu"
               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">

        <label for="email">Email</label>
        <input type="email" name="email" id="email" required placeholder="contoh@email.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label for="password">Password</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="password" required placeholder="Min. 6 karakter">
          <button type="button" class="mata" onclick="toggleMata('password','e1o','e1c')">
            <svg id="e1o" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <svg id="e1c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
              <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>
        <div class="hint">Minimal 6 karakter</div>

        <label for="konfirmasi">Konfirmasi Password</label>
        <div class="pw-wrap">
          <input type="password" name="konfirmasi" id="konfirmasi" required placeholder="Ulangi password">
          <button type="button" class="mata" onclick="toggleMata('konfirmasi','e2o','e2c')">
            <svg id="e2o" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <svg id="e2c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
              <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>
        <div class="pw-match" id="pw-match"></div>

        <button type="submit" class="btn-daftar">Buat Akun</button>
      </form>

      <div class="footer-link">
        Sudah punya akun? <a href="login.php">Masuk di sini</a>
      </div>
    <?php endif; ?>

  </div>
</div>

<script>
function toggleMata(inputId, iconBuka, iconTutup) {
  const el = document.getElementById(inputId);
  const ib = document.getElementById(iconBuka);
  const it = document.getElementById(iconTutup);
  const hidden = el.type === 'password';
  el.type = hidden ? 'text' : 'password';
  ib.style.display = hidden ? 'none' : 'block';
  it.style.display = hidden ? 'block' : 'none';
}

// Validasi real-time konfirmasi password
const elKonfirmasi = document.getElementById('konfirmasi');
const elPassword   = document.getElementById('password');
const elMatch      = document.getElementById('pw-match');

function cekMatch() {
  if (!elKonfirmasi || !elPassword) return;
  const pw  = elPassword.value;
  const kfm = elKonfirmasi.value;
  if (!kfm) { elMatch.className = 'pw-match'; return; }
  if (pw === kfm) {
    elMatch.className = 'pw-match cocok';
    elMatch.textContent = '✓ Password cocok';
    elKonfirmasi.setCustomValidity('');
  } else {
    elMatch.className = 'pw-match salah';
    elMatch.textContent = '✗ Password tidak cocok';
    elKonfirmasi.setCustomValidity('Password tidak cocok');
  }
}

if (elKonfirmasi) elKonfirmasi.addEventListener('input', cekMatch);
if (elPassword)   elPassword.addEventListener('input', cekMatch);
</script>
</body>
</html>
