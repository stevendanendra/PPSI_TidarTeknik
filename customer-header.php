<?php
/**
 * customer-header.php — Layout Header & Navigasi Panel Customer
 * CV. Tidar Teknik
 */

// Buffer output supaya halaman bisa redirect (header Location) setelah header HTML tercetak
ob_start();

require_once __DIR__ . '/customer-auth.php';
require_once __DIR__ . '/lib.php';
requireCustomerLogin();

$currentCust = getCurrentCustomer();
$currentPage = basename($_SERVER['PHP_SELF']);
autoCompleteOrders();

// Akun yang dibuat admin / hasil reset wajib ganti password dulu
if ($currentCust && !empty($currentCust['must_change_password'])
    && !in_array($currentPage, ['customer-profile.php', 'customer-logout.php'], true)) {
    header('Location: customer-profile.php?force=1');
    exit;
}

// Hitung badge request yang pending/approved
$cntReq = 0;
$cntOrder = 0;
if ($currentCust) {
    $cId = $currentCust['id'];
    $rRes = mysqli_query($conn, "SELECT COUNT(*) FROM product_requests WHERE customer_id = '$cId' AND status IN ('pending', 'approved')");
    if ($rRes) $cntReq = (int)mysqli_fetch_row($rRes)[0];

    $oRes = mysqli_query($conn, "SELECT COUNT(*) FROM orders WHERE customer_id = '$cId' AND status_pesanan NOT IN ('selesai', 'dibatalkan')");
    if ($oRes) $cntOrder = (int)mysqli_fetch_row($oRes)[0];

    $cntCart  = (int)(dbRow("SELECT COUNT(*) AS c FROM cart_items WHERE customer_id = ?", [$cId])['c'] ?? 0);
    $cntQuote = (int)(dbRow("SELECT COUNT(*) AS c FROM penawaran WHERE customer_id = ? AND status = 'Dikirim'", [$cId])['c'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'Customer Portal' ?> — CV. Tidar Teknik</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
  <style>
    /* Styling khusus Customer Portal */
    :root {
      --portal-brand: #0284c7;
      --portal-accent: #059669;
    }
    .brand-icon img {
      width: 40px;
      height: 40px;
      object-fit: contain;
      border-radius: 10px;
    }
    .badge-portal {
      display: inline-block;
      padding: 2px 8px;
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .04em;
      border-radius: 12px;
      background: rgba(14, 165, 233, 0.18);
      color: var(--teal-400);
      border: 1px solid rgba(56, 189, 248, 0.3);
      margin-top: 3px;
    }
    .user-pill {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 6px 14px;
      background: #fff;
      border: 1px solid var(--navy-200);
      border-radius: 30px;
      box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .user-avatar {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--teal-500), var(--em-500));
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 12px;
    }
    .user-meta {
      line-height: 1.2;
      text-align: left;
    }
    .user-name {
      font-size: 12.5px;
      font-weight: 700;
      color: var(--navy-800);
      max-width: 160px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .user-role {
      font-size: 11px;
      color: var(--navy-400);
    }

    /* Product card grid */
    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 18px;
    }
    .product-card {
      background: #fff;
      border-radius: var(--radius);
      border: 1px solid var(--navy-200);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: all var(--transition);
      position: relative;
    }
    .product-card:hover {
      border-color: var(--teal-400);
      transform: translateY(-3px);
      box-shadow: 0 10px 25px -5px rgba(0,0,0,.08);
    }
    .product-thumb {
      height: 160px;
      background: linear-gradient(135deg, var(--navy-50), var(--navy-100));
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      border-bottom: 1px solid var(--navy-200);
    }
    .product-thumb .icon-box {
      font-size: 56px;
      filter: drop-shadow(0 4px 6px rgba(0,0,0,.1));
    }
    .product-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .stock-tag {
      position: absolute;
      top: 12px;
      right: 12px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .02em;
    }
    .stock-available {
      background: rgba(16, 185, 129, 0.15);
      color: var(--em-600);
      border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .stock-empty {
      background: rgba(239, 68, 68, 0.12);
      color: var(--red-600);
      border: 1px solid rgba(239, 68, 68, 0.3);
    }
    .wishlist-btn-corner {
      position: absolute;
      top: 12px;
      left: 12px;
      width: 32px;
      height: 32px;
      background: rgba(255,255,255,.9);
      border: 1px solid var(--navy-200);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 15px;
      cursor: pointer;
      transition: all .15s;
    }
    .wishlist-btn-corner:hover {
      transform: scale(1.1);
      background: #fff;
    }
    .wishlist-btn-corner.active {
      color: #ef4444;
    }
    .product-body {
      padding: 18px;
      display: flex;
      flex-direction: column;
      flex: 1;
    }
    .product-category {
      font-size: 11px;
      font-weight: 700;
      color: var(--teal-600);
      text-transform: uppercase;
      letter-spacing: .05em;
      margin-bottom: 6px;
    }
    .product-title {
      font-size: 15px;
      font-weight: 700;
      color: var(--navy-900);
      line-height: 1.35;
      margin-bottom: 8px;
    }
    .product-specs-preview {
      font-size: 11.5px;
      color: var(--navy-500);
      margin-bottom: 14px;
      flex: 1;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .product-price-box {
      border-top: 1px solid var(--navy-100);
      padding-top: 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: auto;
    }
    .product-price {
      font-size: 17px;
      font-weight: 800;
      color: var(--navy-900);
    }
    .product-unit {
      font-size: 11px;
      color: var(--navy-400);
      font-weight: 500;
    }
    .product-actions {
      display: flex;
      gap: 8px;
      margin-top: 12px;
    }
    .product-actions .btn {
      flex: 1;
      justify-content: center;
      padding: 8px 12px;
      font-size: 12.5px;
    }

    /* Tracking timeline */
    .timeline {
      position: relative;
      padding-left: 28px;
      margin: 20px 0;
    }
    .timeline::before {
      content: '';
      position: absolute;
      left: 10px;
      top: 5px;
      bottom: 5px;
      width: 2px;
      background: var(--navy-200);
    }
    .timeline-step {
      position: relative;
      margin-bottom: 24px;
    }
    .timeline-step:last-child { margin-bottom: 0; }
    .timeline-dot {
      position: absolute;
      left: -28px;
      top: 0;
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: #fff;
      border: 2.5px solid var(--navy-300);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      font-weight: 800;
    }
    .timeline-step.completed .timeline-dot {
      background: var(--em-500);
      border-color: var(--em-500);
      color: #fff;
    }
    .timeline-step.active .timeline-dot {
      background: var(--teal-500);
      border-color: var(--teal-500);
      color: #fff;
      box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.25);
    }
    .timeline-title {
      font-size: 13.5px;
      font-weight: 700;
      color: var(--navy-800);
    }
    .timeline-desc {
      font-size: 12px;
      color: var(--navy-500);
      margin-top: 2px;
    }
  </style>
</head>
<body>

  <!-- MOBILE SIDEBAR OVERLAY -->
  <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

  <!-- SIDEBAR PANEL CUSTOMER -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-logo">
        <div class="brand-icon">
          <?php if (file_exists(__DIR__ . '/img/logo.png')): ?>
            <img src="img/logo.png" alt="Logo CV. Tidar Teknik">
          <?php else: ?>
            <span>🏭</span>
          <?php endif; ?>
        </div>
        <div>
          <div class="brand-name">CV. Tidar Teknik</div>
          <span class="badge-portal">Portal Customer</span>
        </div>
      </div>
      <div class="brand-address">📍 CV. Tidar Teknik • Dropship &amp; Teknik Industri</div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-title">Menu Portal</div>
      <a href="customer-dashboard.php" class="nav-item <?= $currentPage === 'customer-dashboard.php' ? 'active' : '' ?>">
        <span class="nav-icon">🏠</span> Dashboard
      </a>
      <a href="customer-products.php" class="nav-item <?= $currentPage === 'customer-products.php' ? 'active' : '' ?>">
        <span class="nav-icon">🛍️</span> Marketplace / Katalog
      </a>
      <a href="customer-request.php" class="nav-item <?= $currentPage === 'customer-request.php' ? 'active' : '' ?>">
        <span class="nav-icon">📋</span> Request Barang
        <?php if ($cntReq > 0): ?>
          <span class="nav-badge"><?= $cntReq ?></span>
        <?php endif; ?>
      </a>
      <a href="customer-cart.php" class="nav-item <?= $currentPage === 'customer-cart.php' ? 'active' : '' ?>">
        <span class="nav-icon">🛒</span> Keranjang
        <?php if (($cntCart ?? 0) > 0): ?>
          <span class="nav-badge"><?= $cntCart ?></span>
        <?php endif; ?>
      </a>
      <a href="customer-quotations.php" class="nav-item <?= $currentPage === 'customer-quotations.php' ? 'active' : '' ?>">
        <span class="nav-icon">📝</span> Penawaran
        <?php if (($cntQuote ?? 0) > 0): ?>
          <span class="nav-badge"><?= $cntQuote ?></span>
        <?php endif; ?>
      </a>
      <a href="customer-orders.php" class="nav-item <?= in_array($currentPage, ['customer-orders.php', 'customer-order-detail.php', 'customer-checkout.php']) ? 'active' : '' ?>">
        <span class="nav-icon">📦</span> Riwayat Pesanan
        <?php if ($cntOrder > 0): ?>
          <span class="nav-badge"><?= $cntOrder ?></span>
        <?php endif; ?>
      </a>

      <div class="nav-section-title" style="margin-top:14px">Akun &amp; Bantuan</div>
      <a href="customer-returns.php" class="nav-item <?= in_array($currentPage, ['customer-returns.php', 'customer-return-new.php']) ? 'active' : '' ?>">
        <span class="nav-icon">↩️</span> Retur Barang
      </a>
      <a href="customer-profile.php" class="nav-item <?= $currentPage === 'customer-profile.php' ? 'active' : '' ?>">
        <span class="nav-icon">👤</span> Profil &amp; Akun
      </a>
      <a href="customer-help.php" class="nav-item <?= $currentPage === 'customer-help.php' ? 'active' : '' ?>">
        <span class="nav-icon">❓</span> Help Center
      </a>
    </nav>

    <div class="sidebar-footer">
      <div style="margin-bottom:8px;font-size:11px;color:var(--navy-400)">
        📞 0822 2525 5251<br>tidarteknik@gmail.com
      </div>
      <a href="customer-logout.php"
         style="display:flex;align-items:center;gap:6px;justify-content:center;padding:7px 12px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:8px;color:#fca5a5;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s"
         onmouseover="this.style.background='rgba(239,68,68,.25)'"
         onmouseout="this.style.background='rgba(239,68,68,.12)'" id="btn-logout-cust">
        🚪 Keluar Akun
      </a>
    </div>
  </aside>

  <!-- MAIN WRAPPER -->
  <div class="main-wrap">
    <header class="topbar">
      <button class="hamburger-btn" id="hamburger-btn" onclick="toggleSidebar()" aria-label="Buka menu">☰</button>
      <div>
        <div class="topbar-title"><?= $pageTitle ?? 'Customer Portal' ?></div>
        <div class="topbar-subtitle"><?= $pageSubtitle ?? 'Selamat datang di SIM-Dropship CV. Tidar Teknik' ?></div>
      </div>
      <div class="topbar-actions">
        <a href="customer-products.php" class="btn btn-ghost" style="font-size:12px">🔍 Cari Produk</a>
        <a href="customer-request.php?new=1" class="btn btn-primary" style="font-size:12px">➕ Request Barang</a>
        <div style="position:relative">
          <button type="button" class="btn btn-ghost" id="notif-bell" style="font-size:16px;padding:6px 10px" aria-label="Notifikasi">🔔<span id="notif-count" class="nav-badge" style="display:none;position:absolute;top:-4px;right:-4px">0</span></button>
          <div id="notif-panel" class="card" style="display:none;position:absolute;right:0;top:44px;width:320px;max-width:85vw;z-index:50;padding:0;max-height:360px;overflow:auto"></div>
        </div>
        <div class="user-pill">
          <div class="user-avatar"><?= strtoupper(substr($currentCust['nama'] ?? 'C', 0, 1)) ?></div>
          <div class="user-meta">
            <div class="user-name"><?= htmlspecialchars($currentCust['nama'] ?? 'Customer') ?></div>
            <div class="user-role"><?= htmlspecialchars($currentCust['kota'] ?? 'Customer Portal') ?></div>
          </div>
        </div>
      </div>
    </header>

    <main class="page-content">
