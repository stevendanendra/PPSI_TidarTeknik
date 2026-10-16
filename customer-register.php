<?php
/**
 * customer-register.php — Halaman Registrasi Akun Customer Portal
 * CV. Tidar Teknik
 */


require_once 'customer-auth.php';

// Jika sudah login, langsung ke dashboard
if (isCustomerLoggedIn()) {
    header('Location: customer-dashboard.php');
    exit;
}

if (!appConfig('allow_customer_register', false)) {
    // Sesuai PMP: akun customer dibuat oleh Admin
    header('Location: customer-login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $pic      = trim($_POST['pic'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telepon  = trim($_POST['telepon'] ?? '');
    $kota     = trim($_POST['kota'] ?? '');
    $alamat   = trim($_POST['alamat'] ?? '');
    $password = $_POST['password'] ?? '';
    $passConf = $_POST['password_confirm'] ?? '';

    if (empty($nama) || empty($email) || empty($password) || empty($telepon) || empty($kota)) {
        $error = 'Harap lengkapi semua kolom bertanda bintang (*).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal harus terdiri dari 6 karakter.';
    } elseif ($password !== $passConf) {
        $error = 'Konfirmasi password tidak cocok dengan password.';
    } else {
        if (dbRow("SELECT id FROM customers WHERE email = ? LIMIT 1", [$email])) {
            $error = 'Email ini sudah terdaftar. Silakan gunakan email lain atau langsung masuk.';
        } else {
            // Generate ID unik
            $newId = 'cus_' . substr(md5(uniqid(mt_rand(), true)), 0, 10);
            
            // Generate kode customer otomatis (misal 3 huruf kapital pertama kata)
            $words = explode(' ', strtoupper($nama));
            $kode = '';
            foreach ($words as $w) {
                if (!empty($w)) $kode .= $w[0];
                if (strlen($kode) >= 4) break;
            }
            if (empty($kode)) $kode = 'CUS';

            $hashedPass = password_hash($password, PASSWORD_BCRYPT);
            $cleanNama = $nama;
            $cleanEmail = $email;

            try {
                dbRun("INSERT INTO customers (id, nama, kode, kota, alamat, email, password, telepon, pic, status)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')",
                    [$newId, $nama, $kode, strtoupper($kota), $alamat, $email, $hashedPass, $telepon, $pic]);
                session_regenerate_id(true);
                // Auto-login setelah registrasi
                $_SESSION[CUSTOMER_SESSION_KEY] = true;
                $_SESSION[CUSTOMER_ID_KEY]      = $newId;
                $_SESSION[CUSTOMER_NAME_KEY]    = $cleanNama;
                $_SESSION[CUSTOMER_EMAIL_KEY]   = $cleanEmail;
                $_SESSION['customer_auth_time'] = time();
                $_SESSION['customer_flash_welcome'] = 'Selamat datang di Customer Portal CV. Tidar Teknik!';

                header('Location: customer-dashboard.php');
                exit;
            } catch (Throwable $e) {
                $error = 'Terjadi kesalahan saat menyimpan data. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Customer — CV. Tidar Teknik</title>
  <meta name="description" content="Registrasi Akun Baru Customer Portal CV. Tidar Teknik">
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
      padding: 32px 16px;
    }

    /* Ambient glowing orbs */
    .bg-orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      opacity: .3;
      pointer-events: none;
      animation: float 10s ease-in-out infinite;
    }
    .bg-orb-1 { width:520px; height:520px; background: radial-gradient(circle, #0284c7, transparent); top:-100px; left:-140px; }
    .bg-orb-2 { width:460px; height:460px; background: radial-gradient(circle, #059669, transparent); bottom:-120px; right:-120px; animation-delay:-5s; }

    @keyframes float {
      0%, 100% { transform: translateY(0) scale(1); }
      50%       { transform: translateY(-24px) scale(1.03); }
    }

    body::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle, rgba(255,255,255,.06) 1px, transparent 1px);
      background-size: 32px 32px;
      pointer-events: none;
    }

    .register-card {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 580px;
      padding: 38px 34px;
      background: rgba(30, 41, 59, 0.85);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,.12);
      border-radius: 20px;
      box-shadow: 0 32px 64px rgba(0,0,0,.55);
      animation: slideUp .45s cubic-bezier(.22,1,.36,1) both;
    }

    @keyframes slideUp {
      from { opacity:0; transform: translateY(24px); }
      to   { opacity:1; transform: translateY(0); }
    }

    .brand {
      text-align: center;
      margin-bottom: 20px;
    }
    .brand-logo-wrap {
      width: 60px;
      height: 60px;
      margin: 0 auto 12px;
      background: linear-gradient(135deg, var(--teal-600), var(--emerald-600));
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 24px rgba(14,165,233,.35);
    }
    .brand-logo-wrap img {
      width: 38px;
      height: 38px;
      object-fit: contain;
      border-radius: 8px;
    }
    .brand-name {
      font-size: 20px;
      font-weight: 800;
      color: #fff;
    }
    .brand-sub {
      font-size: 13px;
      color: var(--navy-300);
      margin-top: 3px;
    }

    .nav-tabs-portal {
      display: flex;
      gap: 6px;
      margin-bottom: 22px;
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

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    @media (max-width: 600px) {
      .form-grid { grid-template-columns: 1fr; }
    }

    .form-group { margin-bottom: 16px; }
    label {
      display: block;
      font-size: 11.5px;
      font-weight: 600;
      color: var(--navy-300);
      margin-bottom: 6px;
      letter-spacing: .03em;
      text-transform: uppercase;
    }
    label span.req { color: var(--teal-400); }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    textarea {
      width: 100%;
      padding: 10px 14px;
      background: rgba(255,255,255,.06);
      border: 1.5px solid rgba(255,255,255,.10);
      border-radius: 10px;
      font-family: 'Inter', sans-serif;
      font-size: 13.5px;
      color: #fff;
      outline: none;
      transition: all .2s;
    }
    input::placeholder, textarea::placeholder { color: #94a3b8; }
    input:focus, textarea:focus {
      border-color: var(--teal-400);
      background: rgba(14,165,233,.08);
      box-shadow: 0 0 0 3px rgba(14,165,233,.18);
    }
    textarea { resize: vertical; min-height: 65px; }

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
    .alert-error { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }

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
      margin-top: 10px;
    }
    .btn-submit:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 24px rgba(14,165,233,.45);
    }

    .card-footer {
      text-align: center;
      margin-top: 20px;
      font-size: 12px;
      color: var(--navy-400);
    }
    .card-footer a {
      color: var(--teal-400);
      text-decoration: none;
      font-weight: 600;
    }
  </style>
</head>
<body>

  <div class="bg-orb bg-orb-1"></div>
  <div class="bg-orb bg-orb-2"></div>

  <div class="register-card">
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
          <span style="font-size:28px">⚙️</span>
        <?php endif; ?>
      </div>
      <div class="brand-name">CV. Tidar Teknik</div>
      <div class="brand-sub">Registrasi Akun Customer Portal</div>
    </div>

    <div class="nav-tabs-portal">
      <a href="customer-login.php">Masuk Akun</a>
      <a href="customer-register.php" class="active">Daftar Akun Baru</a>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <span>⚠️</span> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="customer-register.php" autocomplete="off">
      <div class="form-grid">
        <div class="form-group">
          <label for="nama">Nama Perusahaan / Pribadi <span class="req">*</span></label>
          <input
            type="text"
            id="nama"
            name="nama"
            placeholder="PT. Sinar Jaya Abadi"
            value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
            required
            autofocus
          >
        </div>
        <div class="form-group">
          <label for="pic">Nama PIC / Kontak Person</label>
          <input
            type="text"
            id="pic"
            name="pic"
            placeholder="Bpk. Rahmat"
            value="<?= htmlspecialchars($_POST['pic'] ?? '') ?>"
          >
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="email">Email Login <span class="req">*</span></label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="purchasing@sinarjaya.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
          >
        </div>
        <div class="form-group">
          <label for="telepon">No. Telepon / WhatsApp <span class="req">*</span></label>
          <input
            type="text"
            id="telepon"
            name="telepon"
            placeholder="081234567890"
            value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>"
            required
          >
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="kota">Kota <span class="req">*</span></label>
          <input
            type="text"
            id="kota"
            name="kota"
            placeholder="Surabaya / Malang / dll"
            value="<?= htmlspecialchars($_POST['kota'] ?? '') ?>"
            required
          >
        </div>
        <div class="form-group">
          <label for="alamat">Alamat Lengkap Pengiriman</label>
          <textarea
            id="alamat"
            name="alamat"
            rows="2"
            placeholder="Jl. Industri Raya No. 45, Kawasan Industri..."
          ><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="password">Password (Min. 6 Karakter) <span class="req">*</span></label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            required
          >
        </div>
        <div class="form-group">
          <label for="password_confirm">Konfirmasi Password <span class="req">*</span></label>
          <input
            type="password"
            id="password_confirm"
            name="password_confirm"
            placeholder="••••••••"
            required
          >
        </div>
      </div>

      <button type="submit" class="btn-submit">
        ✨ Buat Akun &amp; Mulai Akses Katalog
      </button>

      <div class="card-footer">
        Sudah memiliki akun? <a href="customer-login.php">Masuk ke sini</a>
      </div>
    </form>
  </div>
</body>
</html>
