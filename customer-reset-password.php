<?php
/**
 * customer-reset-password.php — Atur password baru lewat token dari customer-forgot-password.php
 */
require_once 'customer-auth.php';
require_once 'lib.php';

$error = '';
$success = '';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$cust = $token !== '' ? dbRow("SELECT id FROM customers WHERE reset_token_hash = ? AND reset_expires > NOW() LIMIT 1", [hash('sha256', $token)]) : null;

if (!$cust) {
    $error = 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p1 = $_POST['password'] ?? '';
    $p2 = $_POST['password_confirm'] ?? '';
    if (strlen($p1) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($p1 !== $p2) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        dbRun("UPDATE customers SET password = ?, reset_token_hash = NULL, reset_expires = NULL, must_change_password = 0, email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?", [password_hash($p1, PASSWORD_BCRYPT), $cust['id']]);
        $success = 'Password berhasil diubah. Silakan masuk dengan password baru.';
        $cust = null;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password Portal — CV. Tidar Teknik</title>
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

    <form method="POST" action="customer-reset-password.php" autocomplete="off">
      <?php if ($cust): ?>
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="form-group">
        <label for="password">Password Baru</label>
        <div class="input-wrap"><span class="input-icon">🔑</span>
          <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6" autofocus></div>
      </div>
      <div class="form-group">
        <label for="password_confirm">Ulangi Password Baru</label>
        <div class="input-wrap"><span class="input-icon">🔑</span>
          <input type="password" id="password_confirm" name="password_confirm" required minlength="6"></div>
      </div>
      <button type="submit" class="btn-submit">💾 Simpan Password</button>
      <?php endif; ?>
      <div class="card-footer"><a href="customer-login.php">← Ke halaman Login</a><?php if (!$cust && !$success): ?> · <a href="customer-forgot-password.php">Minta tautan baru</a><?php endif; ?></div>
    </form>
  </div>

  
</body>
</html>
