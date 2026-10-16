<?php

// HTTPS Force removed for local dev
/**
 * login.php — Halaman Login CV. Tidar Teknik
 */

require_once 'auth.php';
require_once 'lib.php';

$error   = '';
$success = '';

// Jika sudah login, langsung ke dashboard
if (!empty($_SESSION[AUTH_SESSION_KEY])) {
    header('Location: index.php');
    exit;
}

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputUser = trim($_POST['username'] ?? '');
    $inputPass = trim($_POST['password'] ?? '');

    if (throttleBlocked('admin', $inputUser)) {
        $error = 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.';
    } elseif (verifyAdminLogin($inputUser, $inputPass)) {
        throttleClear('admin', $inputUser);
        session_regenerate_id(true); // cegah session fixation
        $_SESSION[AUTH_SESSION_KEY] = true;
        $_SESSION['auth_time']      = time();
        $_SESSION['auth_user']      = $inputUser;

        // Redirect ke halaman yang dituju, atau dashboard
        $redirect = $_SESSION['auth_redirect'] ?? 'index.php';
        unset($_SESSION['auth_redirect']);
        header('Location: ' . $redirect);
        exit;
    } else {
        throttleHit('admin', $inputUser);
        $error = 'Username atau password salah. Silakan coba lagi.';
    }
}

$loggedOut = isset($_GET['logout']) && $_GET['logout'] == '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — CV. Tidar Teknik</title>
  <meta name="description" content="Halaman login sistem manajemen dokumen CV. Tidar Teknik">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy-900: #0f172a;
      --navy-800: #1e293b;
      --navy-700: #334155;
      --navy-500: #64748b;
      --navy-300: #cbd5e1;
      --navy-200: #e2e8f0;
      --navy-100: #f1f5f9;
      --teal-600: #0891b2;
      --teal-500: #06b6d4;
      --teal-400: #22d3ee;
      --teal-100: #cffafe;
      --red-500:  #ef4444;
      --red-100:  #fee2e2;
      --green-500:#10b981;
      --green-100:#d1fae5;
    }

    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--navy-900);
      position: relative;
      overflow: hidden;
    }

    /* ---- Animated background ---- */
    .bg-orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      opacity: .35;
      pointer-events: none;
      animation: float 8s ease-in-out infinite;
    }
    .bg-orb-1 { width:500px; height:500px; background: radial-gradient(circle, #0891b2, transparent); top:-100px; left:-120px; animation-delay:0s; }
    .bg-orb-2 { width:400px; height:400px; background: radial-gradient(circle, #1d4ed8, transparent); bottom:-80px; right:-100px; animation-delay:-4s; }
    .bg-orb-3 { width:250px; height:250px; background: radial-gradient(circle, #7c3aed, transparent); top:50%; left:50%; transform:translate(-50%,-50%); animation-delay:-2s; }

    @keyframes float {
      0%, 100% { transform: translateY(0) scale(1); }
      50%       { transform: translateY(-24px) scale(1.04); }
    }
    .bg-orb-3 {
      animation: float3 8s ease-in-out infinite;
      animation-delay: -2s;
    }
    @keyframes float3 {
      0%, 100% { transform: translate(-50%, -50%) scale(1); }
      50%       { transform: translate(-50%, calc(-50% - 24px)) scale(1.04); }
    }

    /* Grid dots overlay */
    body::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle, rgba(255,255,255,.06) 1px, transparent 1px);
      background-size: 32px 32px;
      pointer-events: none;
    }

    /* ---- Card ---- */
    .login-card {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 420px;
      padding: 40px 36px;
      background: rgba(30, 41, 59, 0.80);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,.10);
      border-radius: 20px;
      box-shadow: 0 32px 64px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.04);
      animation: slideUp .5s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes slideUp {
      from { opacity:0; transform: translateY(28px); }
      to   { opacity:1; transform: translateY(0); }
    }

    /* ---- Brand ---- */
    .brand {
      text-align: center;
      margin-bottom: 32px;
    }
    .brand-logo-wrap {
      width: 68px;
      height: 68px;
      margin: 0 auto 16px;
      background: linear-gradient(135deg, #0369a1, #1d4ed8);
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 24px rgba(6,182,212,.35);
    }
    .brand-logo-wrap img {
      width: 44px;
      height: 44px;
      object-fit: contain;
      border-radius: 8px;
    }
    .brand-logo-wrap .fallback-icon { font-size: 32px; line-height: 1; }
    .brand-name {
      font-size: 20px;
      font-weight: 800;
      color: #fff;
      letter-spacing: -.3px;
    }
    .brand-sub {
      font-size: 13px;
      color: var(--navy-300);
      margin-top: 3px;
    }

    /* ---- Divider ---- */
    .divider {
      border: none;
      border-top: 1px solid rgba(255,255,255,.08);
      margin-bottom: 28px;
    }

    /* ---- Form ---- */
    .form-group { margin-bottom: 18px; }
    label {
      display: block;
      font-size: 12.5px;
      font-weight: 600;
      color: var(--navy-300);
      margin-bottom: 7px;
      letter-spacing: .04em;
      text-transform: uppercase;
    }
    .input-wrap {
      position: relative;
    }
    .input-icon {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 15px;
      pointer-events: none;
      opacity: .7;
    }
    input[type="text"],
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
      transition: border-color .2s, background .2s, box-shadow .2s;
    }
    input[type="text"]::placeholder,
    input[type="password"]::placeholder { color: #94a3b8; }
    input[type="text"]:focus,
    input[type="password"]:focus {
      border-color: var(--teal-500);
      background: rgba(6,182,212,.07);
      box-shadow: 0 0 0 3px rgba(6,182,212,.15);
    }

    /* Toggle password */
    .toggle-pass {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      font-size: 15px;
      opacity: .55;
      transition: opacity .15s;
      padding: 2px;
    }
    .toggle-pass:hover { opacity: 1; }

    /* ---- Alert messages ---- */
    .alert {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 11px 14px;
      border-radius: 9px;
      font-size: 13px;
      font-weight: 500;
      margin-bottom: 18px;
      animation: fadeIn .3s ease;
    }
    @keyframes fadeIn { from {opacity:0;transform:translateY(-4px)} to {opacity:1;transform:translateY(0)} }
    .alert-error   { background: rgba(239,68,68,.12);  border: 1px solid rgba(239,68,68,.3);  color: #fca5a5; }
    .alert-success { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; }
    .alert-icon    { font-size: 17px; flex-shrink: 0; }

    /* ---- Submit button ---- */
    .btn-login {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, #0369a1, #1d4ed8);
      border: none;
      border-radius: 10px;
      color: #fff;
      font-family: 'Inter', sans-serif;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      letter-spacing: .02em;
      transition: transform .15s, box-shadow .15s, filter .15s;
      box-shadow: 0 4px 16px rgba(6,182,212,.30);
      margin-top: 6px;
    }
    .btn-login:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 24px rgba(6,182,212,.40);
      filter: brightness(1.08);
    }
    .btn-login:active { transform: translateY(0); }

    /* ---- Footer ---- */
    .card-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 11.5px;
      color: #94a3b8;
    }
    .card-footer strong { color: var(--navy-300); }

    /* ---- Loading state ---- */
    .btn-login.loading { pointer-events:none; filter: brightness(.8); }
    .btn-login.loading::after {
      content: ' ⟳';
      display: inline-block;
      animation: spin .7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>

  <!-- Animated background -->
  <div class="bg-orb bg-orb-1"></div>
  <div class="bg-orb bg-orb-2"></div>
  <div class="bg-orb bg-orb-3"></div>

  <div class="login-card">

    <div style="margin-bottom: 16px;">
      <a href="landing.php" style="color: var(--teal-400); text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
        ← Kembali ke Beranda / Katalog Publik
      </a>
    </div>

    <!-- Brand -->
    <div class="brand">
      <div class="brand-logo-wrap">
        <?php if (file_exists(__DIR__ . '/img/logo.png')): ?>
          <img src="img/logo.png" alt="Logo CV. Tidar Teknik">
        <?php else: ?>
          <span class="fallback-icon">🏭</span>
        <?php endif; ?>
      </div>
      <div class="brand-name">CV. Tidar Teknik</div>
      <div class="brand-sub">Sistem Manajemen Dokumen</div>
    </div>

    <hr class="divider">

    <!-- Alert: logout sukses -->
    <?php if ($loggedOut): ?>
    <div class="alert alert-success" role="alert">
      <span class="alert-icon">✅</span>
      Anda telah berhasil keluar. Sampai jumpa!
    </div>
    <?php endif; ?>

    <!-- Alert: error login -->
    <?php if ($error): ?>
    <div class="alert alert-error" role="alert" id="login-error-msg">
      <span class="alert-icon">🔒</span>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="login.php" id="login-form" autocomplete="off" novalidate>

      <div class="form-group">
        <label for="username">Username</label>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
          <input
            type="text"
            id="username"
            name="username"
            placeholder="Masukkan username"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            autocomplete="username"
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
            autocomplete="current-password"
            required
          >
          <button type="button" class="toggle-pass" id="toggle-pass-btn" title="Tampilkan/Sembunyikan password" aria-label="Toggle password visibility">👁️</button>
        </div>
      </div>

      <button type="submit" class="btn-login" id="btn-login">
        🔐 Masuk ke Sistem
      </button>

    </form>

    <div class="card-footer">
      📍 Perum Bumi Mas Blok C1 No 2, Jedong Wagir<br>
      <strong>Kab. Malang 65158</strong> &nbsp;|&nbsp; tidarteknik@gmail.com
    </div>

  </div>

  <script>
    // Toggle show/hide password
    const toggleBtn  = document.getElementById('toggle-pass-btn');
    const passInput  = document.getElementById('password');
    let   passVisible = false;
    toggleBtn.addEventListener('click', () => {
      passVisible = !passVisible;
      passInput.type   = passVisible ? 'text' : 'password';
      toggleBtn.textContent = passVisible ? '🙈' : '👁️';
    });

    // Loading state on submit
    document.getElementById('login-form').addEventListener('submit', function() {
      const btn = document.getElementById('btn-login');
      btn.textContent = 'Memverifikasi';
      btn.classList.add('loading');
    });

    // Shake animation on error
    <?php if ($error): ?>
    const card = document.querySelector('.login-card');
    card.style.animation = 'none';
    setTimeout(() => { card.style.animation = 'shake .4s ease'; }, 10);
    const style = document.createElement('style');
    style.textContent = '@keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-7px)}40%,80%{transform:translateX(7px)}}';
    document.head.appendChild(style);
    <?php endif; ?>
  </script>

</body>
</html>
