<?php
session_start();
require_once '../config/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $email=trim($_POST['email']); $password=$_POST['password'];
    $stmt=$conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$email]);
    $user=$stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password,$user['password'])) {
        $_SESSION['user_id']=$user['id']; $_SESSION['nama']=$user['nama']; $_SESSION['role']=$user['role'];
        header('Location: ../'.($user['role']==='admin'?'admin':'user').'/dashboard.php');
        exit;
    } else { $error='Email atau password salah'; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - PlasticPoint</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Header atas (hanya mobile) */
    .mobile-top {
      background: #0f2147;
      padding: 28px 24px 40px;
      text-align: center;
      display: none;
    }
    .mobile-top .logo { font-size: 48px; margin-bottom: 10px; }
    .mobile-top h1   { color: #fff; font-size: 22px; font-weight: 700; }
    .mobile-top p    { color: #94a3b8; font-size: 13px; margin-top: 4px; }

    /* Background desktop */
    .bg-desktop {
      flex: 1;
      background: linear-gradient(135deg, #0f2147 0%, #1a3a6e 55%, #16a34a 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 32px 16px;
    }

    /* Card login */
    .login-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18);
      width: 100%;
      max-width: 380px;
      padding: 36px 32px;
    }
    .card-logo { text-align:center; font-size: 44px; margin-bottom: 8px; }
    .card-title { text-align:center; font-size: 20px; font-weight: 700; color: #0f2147; margin-bottom: 4px; }
    .card-sub   { text-align:center; font-size: 13px; color: #6b7280; margin-bottom: 24px; }

    label  { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:4px; }
    input  {
      width:100%; padding:11px 14px; margin-bottom:16px;
      border:1.5px solid #d1d5db; border-radius:9px;
      font-size:14px; outline:none; transition: border 0.2s;
    }
    input:focus { border-color: #0f2147; }

    .pw-wrap { position:relative; }
    .pw-wrap input { padding-right:44px; }
    .mata {
      position:absolute; right:12px; top:50%;
      transform:translateY(-62%);
      cursor:pointer; color:#9ca3af; background:none; border:none;
    }
    .mata:hover { color:#374151; }
    .mata svg { width:20px; height:20px; display:block; }

    .btn-login {
      width:100%; padding:13px;
      background:#0f2147; color:#fff; border:none;
      border-radius:9px; font-size:15px; font-weight:700;
      cursor:pointer; margin-top:4px;
      transition: background 0.2s, transform 0.1s;
      letter-spacing:0.3px;
    }
    .btn-login:hover   { background:#16a34a; }
    .btn-login:active  { transform:scale(0.98); }

    .alert-error {
      background:#fee2e2; color:#dc2626;
      padding:10px 14px; border-radius:8px;
      font-size:13px; margin-bottom:16px; text-align:center;
    }

    .footer-link { text-align:center; margin-top:20px; font-size:13px; color:#6b7280; }
    .footer-link a { color:#16a34a; font-weight:600; text-decoration:none; }
    .footer-link a:hover { text-decoration:underline; }

    /* ===== MOBILE (≤ 600px) ===== */
    @media (max-width: 600px) {
      body { background: #f0f2f5; }
      .mobile-top { display: block; }
      .bg-desktop {
        background: transparent;
        padding: 0;
        align-items: flex-start;
      }
      .login-card {
        border-radius: 20px 20px 0 0;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
        margin-top: -20px;
        padding: 28px 24px 32px;
        position: relative;
        z-index: 1;
        max-width: 100%;
        min-height: 100%;
      }
      .card-logo, .card-title, .card-sub { display:none; }
    }
  </style>
</head>
<body>

<!-- Header mobile: logo + judul -->
<div class="mobile-top">
  <div class="logo">♻️</div>
  <h1>PlasticPoint</h1>
  <p style="font-size:11px; color:#94a3b8; margin-top:2px;">Reverse Vending Machine</p>
  <p style="margin-top:6px;">Masuk ke akun Anda</p>
</div>

<!-- Background + card -->
<div class="bg-desktop">
  <div class="login-card">
    <div class="card-logo">♻️</div>
    <div class="card-title">PlasticPoint</div>
    <div style="text-align:center; font-size:11px; color:#9ca3af; margin-top:-14px; margin-bottom:6px;">Reverse Vending Machine</div>
    <div class="card-sub">Masuk ke akun Anda</div>

    <?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <label>Email</label>
      <input type="email" name="email" required placeholder="contoh@email.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

      <label>Password</label>
      <div class="pw-wrap">
        <input type="password" name="password" id="pw" required placeholder="••••••••">
        <button type="button" class="mata" onclick="toggleMata()">
          <svg id="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          <svg id="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
          </svg>
        </button>
      </div>

      <button type="submit" class="btn-login">Masuk</button>
    </form>

    <div class="footer-link">
      Belum punya akun? <a href="daftar.php">Daftar di sini</a>
    </div>
  </div>
</div>

<script>
function toggleMata() {
  const pw = document.getElementById('pw');
  const eo = document.getElementById('eye-open');
  const ec = document.getElementById('eye-closed');
  if (pw.type === 'password') {
    pw.type = 'text'; eo.style.display = 'none'; ec.style.display = 'block';
  } else {
    pw.type = 'password'; eo.style.display = 'block'; ec.style.display = 'none';
  }
}
</script>
</body>
</html>
