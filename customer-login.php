<?php
/**
 * customer-login.php — Halaman Login Customer Portal
 * CV. Tidar Teknik
 */


require_once 'customer-auth.php';
require_once 'lib.php';

// Jika customer sudah login, redirect langsung ke dashboard customer
if (isCustomerLoggedIn()) {
    header('Location: customer-dashboard.php');
    exit;
}

$error   = '';
$success = '';
$allowRegister = (bool)appConfig('allow_customer_register', false);

if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = 'Pendaftaran berhasil! Silakan masuk dengan email dan password Anda.';
}

if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $success = 'Anda telah berhasil keluar dari Customer Portal.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Harap isi email dan password.';
    } elseif (throttleBlocked('customer', $email)) {
        $error = 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.';
    } else {
        $cust = dbRow("SELECT * FROM customers WHERE email = ? LIMIT 1", [$email]);
        $okPass = $cust && !empty($cust['password']) && password_verify($password, $cust['password']);

        if ($okPass && $cust['status'] !== 'active') {
            $error = 'Akun Anda sedang dinonaktifkan. Silakan hubungi admin CV. Tidar Teknik.';
        } elseif ($okPass) {
            throttleClear('customer', $email);
            session_regenerate_id(true); // cegah session fixation
            $_SESSION[CUSTOMER_SESSION_KEY]   = true;
            $_SESSION[CUSTOMER_ID_KEY]        = $cust['id'];
            $_SESSION[CUSTOMER_NAME_KEY]      = $cust['nama'];
            $_SESSION[CUSTOMER_EMAIL_KEY]     = $cust['email'];
            $_SESSION['customer_auth_time']   = time();

            $redirect = $_SESSION['customer_redirect'] ?? 'customer-dashboard.php';
            unset($_SESSION['customer_redirect']);
            header('Location: ' . $redirect);
            exit;
        } else {
            throttleHit('customer', $email);
            // Pesan seragam: tidak membocorkan apakah email terdaftar
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Customer Portal — CV. Tidar Teknik</title>
  <meta name="description" content="Customer Portal CV. Tidar Teknik — Akses Marketplace & Pemesanan Barang Teknik">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy-950: #020617;
      --navy-900: #0f172a;
      --navy-800: #1e293b;
      --navy-700: #334155;
      --navy-500: #64748b;
      --navy-400: #94a3b8;
      --navy-300: #cbd5e1;
      --navy-200: #e2e8f0;
      --navy-100: #f1f5f9;
      --teal-600: #0284c7;
      --teal-500: #0ea5e9;
      --teal-400: #38bdf8;
      --teal-100: #e0f2fe;
      --emerald-600: #059669;
      --emerald-500: #10b981;
      --emerald-100: #d1fae5;
      --red-500:  #ef4444;
      --red-100:  #fee2e2;
    }

    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--navy-900);
      position: relative;
      overflow-x: hidden;
      padding: 24px 16px;
    }

    /* Ambient glowing orbs */
    .bg-orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      opacity: .32;
      pointer-events: none;
      animation: float 9s ease-in-out infinite;
    }
    .bg-orb-1 { width:520px; height:520px; background: radial-gradient(circle, #0284c7, transparent); top:-100px; left:-140px; }
    .bg-orb-2 { width:460px; height:460px; background: radial-gradient(circle, #059669, transparent); bottom:-120px; right:-120px; animation-delay:-4.5s; }
    .bg-orb-3 { width:300px; height:300px; background: radial-gradient(circle, #38bdf8, transparent); top:40%; left:65%; animation-delay:-2s; }

    @keyframes float {
      0%, 100% { transform: translateY(0) scale(1); }
      50%       { transform: translateY(-26px) scale(1.04); }
    }

    /* Grid overlay */
    body::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle, rgba(255,255,255,.06) 1px, transparent 1px);
      background-size: 32px 32px;
      pointer-events: none;
    }

    .login-card {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 440px;
      padding: 38px 34px;
      background: rgba(30, 41, 59, 0.82);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,.12);
      border-radius: 20px;
      box-shadow: 0 32px 64px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.05);
      animation: slideUp .45s cubic-bezier(.22,1,.36,1) both;
    }

    @keyframes slideUp {
      from { opacity:0; transform: translateY(24px); }
      to   { opacity:1; transform: translateY(0); }
    }

    .brand {
      text-align: center;
      margin-bottom: 24px;
    }
    .brand-logo-wrap {
      width: 64px;
      height: 64px;
      margin: 0 auto 14px;
      background: linear-gradient(135deg, var(--teal-600), var(--emerald-600));
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 24px rgba(14,165,233,.35);
    }
    .brand-logo-wrap img {
      width: 42px;
      height: 42px;
      object-fit: contain;
      border-radius: 8px;
    }
    .brand-logo-wrap .fallback-icon { font-size: 30px; }
    .brand-name {
      font-size: 20px;
      font-weight: 800;
      color: #fff;
      letter-spacing: -.3px;
    }
    .portal-badge {
      display: inline-block;
      margin-top: 6px;
      padding: 3px 10px;
      background: rgba(14, 165, 233, 0.15);
      border: 1px solid rgba(56, 189, 248, 0.3);
      color: var(--teal-400);
      font-size: 11.5px;
      font-weight: 700;
      border-radius: 20px;
      letter-spacing: .03em;
      text-transform: uppercase;
    }

    .nav-tabs-portal {
      display: flex;
      gap: 6px;
      margin-bottom: 24px;
      background: rgba(15, 23, 42, 0.5);
      padding: 4px;
      border-radius: 10px;
      border: 1px solid rgba(255,255,255,0.06);
    }
    .nav-tabs-portal a {
      flex: 1;
      text-align: center;
      padding: 8px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      color: var(--navy-400);
      text-decoration: none;
      transition: all .2s;
    }
    .nav-tabs-portal a.active {
      background: rgba(255,255,255,0.1);
      color: #fff;
    }
    .nav-tabs-portal a:hover:not(.active) {
      color: var(--navy-200);
    }

    .form-group { margin-bottom: 18px; }
    label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: var(--navy-300);
      margin-bottom: 7px;
      letter-spacing: .03em;
      text-transform: uppercase;
    }
    .input-wrap { position: relative; }
    .input-icon {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 15px;
      pointer-events: none;
      opacity: .7;
    }
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 11px 14px 11px 40px;
      background: rgba(255,255,255,.06);
      border: 1.5px solid rgba(255,255,255,.10);
      border-radius: 10px;
      font-family: 'Inter', sans-serif;
      font-size: 14px;
      color: #fff;
      outline: none;
      transition: all .2s;
    }
    input[type="email"]::placeholder,
    input[type="password"]::placeholder { color: #94a3b8; }
    input[type="email"]:focus,
    input[type="password"]:focus {
      border-color: var(--teal-400);
      background: rgba(14,165,233,.08);
      box-shadow: 0 0 0 3px rgba(14,165,233,.18);
    }

    .toggle-pass {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      font-size: 15px;
      opacity: .6;
      transition: opacity .15s;
    }
    .toggle-pass:hover { opacity: 1; }

    .alert {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 11px 14px;
      border-radius: 9px;
      font-size: 13px;
      font-weight: 500;
      margin-bottom: 18px;
    }
    .alert-error   { background: rgba(239,68,68,.12);  border: 1px solid rgba(239,68,68,.3);  color: #fca5a5; }
    .alert-success { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; }

    .btn-submit {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, #0369a1, #047857);
      border: none;
      border-radius: 10px;
      color: #fff;
      font-family: 'Inter', sans-serif;
      font-size: 14.5px;
      font-weight: 700;
      cursor: pointer;
      letter-spacing: .02em;
      transition: transform .15s, box-shadow .15s;
      box-shadow: 0 4px 16px rgba(14,165,233,.30);
      margin-top: 6px;
    }
    .btn-submit:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 24px rgba(14,165,233,.45);
    }
    .btn-submit:active { transform: translateY(0); }

    .demo-box {
      margin-top: 20px;
      padding: 12px;
      background: rgba(14, 165, 233, 0.08);
      border: 1px dashed rgba(56, 189, 248, 0.25);
      border-radius: 10px;
      font-size: 12px;
      color: var(--navy-300);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .btn-fill-demo {
      background: rgba(56, 189, 248, 0.2);
      border: 1px solid rgba(56, 189, 248, 0.4);
      color: #fff;
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      transition: all .15s;
    }
    .btn-fill-demo:hover {
      background: var(--teal-500);
    }

    .card-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 12px;
      color: var(--navy-400);
    }
    .card-footer a {
      color: var(--teal-400);
      text-decoration: none;
      font-weight: 600;
    }
    .card-footer a:hover { text-decoration: underline; }
  </style>
</head>
<body>

  <div class="bg-orb bg-orb-1"></div>
  <div class="bg-orb bg-orb-2"></div>
  <div class="bg-orb bg-orb-3"></div>

  <div class="login-card">
    <div style="margin-bottom: 16px;">
      <a href="landing.php" style="color: var(--teal-400); text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
        ← Kembali ke Katalog & Beranda
      </a>
    </div>
    <div class="brand">
      <div class="brand-logo-wrap">
        <?php if (file_exists(__DIR__ . '/img/logo.png')): ?>
          <img src="img/logo.png" alt="Logo CV. Tidar Teknik">
        <?php else: ?>
          <span class="fallback-icon">⚙️</span>
        <?php endif; ?>
      </div>
      <div class="brand-name">CV. Tidar Teknik</div>
      <span class="portal-badge">Customer Portal</span>
    </div>

    <!-- Switcher Tab -->
    <div class="nav-tabs-portal">
      <a href="customer-login.php" class="active">Masuk Akun</a>
      <?php if ($allowRegister): ?><a href="customer-register.php">Daftar Akun Baru</a><?php endif; ?>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success">
      <span>✅</span> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <span>🔒</span> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="customer-login.php" autocomplete="off" id="customer-login-form">
      <div class="form-group">
        <label for="email">Email Customer</label>
        <div class="input-wrap">
          <span class="input-icon">✉️</span>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="nama@perusahaan.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
            autofocus
          >
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrap">
          <span class="input-icon">🔑</span>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Masukkan password"
            required
          >
          <button type="button" class="toggle-pass" id="toggle-pass-btn" title="Lihat password">👁️</button>
        </div>
      </div>

      <button type="submit" class="btn-submit" id="btn-login-cust">
        🚀 Masuk ke Portal
      </button>

      <div class="card-footer">
        <a href="customer-forgot-password.php">Lupa password?</a><br>
        <?php if ($allowRegister): ?>Belum punya akun? <a href="customer-register.php">Daftar sekarang</a><?php else: ?>Akun customer dibuat oleh admin CV. Tidar Teknik.<?php endif; ?><br>
        <span style="font-size:11.5px;display:inline-block;margin-top:8px">
          Staff Internal Tidar Teknik? <a href="login.php" style="color:var(--navy-300)">Login Admin di sini</a>
        </span>
      </div>
    </form>
  </div>

  <script>
    // Toggle show/hide password
    const toggleBtn  = document.getElementById('toggle-pass-btn');
    const passInput  = document.getElementById('password');
    let   passVisible = false;
    toggleBtn?.addEventListener('click', () => {
      passVisible = !passVisible;
      passInput.type   = passVisible ? 'text' : 'password';
      toggleBtn.textContent = passVisible ? '🙈' : '👁️';
    });
  </script>
</body>
</html>
