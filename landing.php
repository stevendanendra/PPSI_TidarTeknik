<?php
/**
 * landing.php — Landing Page Elegan & Minimalis CV. Tidar Teknik
 * Terinspirasi dari gaya editorial berkelas (R.Gen & Modern Agency/B2B Showcase)
 */

require_once __DIR__ . '/db.php';

// Ambil semua produk aktif dari database
// Hanya kolom yang aman ditampilkan ke publik: JANGAN ambil supplier, harga modal, atau stok.
$queryProducts = "SELECT p.id, p.kode, p.nama, p.kategori, p.satuan, p.spesifikasi, p.harga, p.harga_min, p.harga_max
                  FROM products p
                  WHERE p.is_active = 1
                  ORDER BY p.kategori ASC, p.nama ASC";
$resProducts = mysqli_query($conn, $queryProducts);
$products = [];
$categories = [];

if ($resProducts) {
    while ($row = mysqli_fetch_assoc($resProducts)) {
        $products[] = $row;
        if (!in_array($row['kategori'], $categories, true)) {
            $categories[] = $row['kategori'];
        }
    }
}

// Helper untuk mapping foto representasi berkualitas tinggi per kategori
function getProductThumb($product) {
    $cat = $product['kategori'] ?? '';
    if (stripos($cat, 'Bearing') !== false) return 'img/cat-bearing.jpg';
    if (stripos($cat, 'Chain') !== false || stripos($cat, 'Rantai') !== false) return 'img/cat-chain.jpg';
    if (stripos($cat, 'Electric') !== false || stripos($cat, 'Control') !== false) return 'img/cat-automation.jpg';
    return 'img/hero-bg.jpg';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CV. Tidar Teknik — Supplier Suku Cadang & Komponen Mesin Industri</title>
  <meta name="description" content="CV. Tidar Teknik melayani pengadaan suku cadang mesin industri berstandar presisi tinggi, rantai roller, bearing, inverter, dan layanan dropship resmi.">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* ========================================================
       DESIGN SYSTEM: EDITORIAL LUXURY & INDUSTRIAL PRECISION
       ======================================================== */
    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --dark-950: #090d16;
      --dark-900: #0f172a;
      --dark-800: #1e293b;
      --dark-700: #334155;
      
      --gray-900: #111827;
      --gray-700: #374151;
      --gray-600: #4b5563;
      --gray-500: #6b7280;
      --gray-400: #9ca3af;
      --gray-300: #d1d5db;
      --gray-200: #e5e7eb;
      --gray-100: #f3f4f6;
      --gray-50:  #f9fafb;
      --white:    #ffffff;

      --accent-blue: #0284c7;
      --accent-navy: #1e3a8a;
      --accent-gold: #d97706;

      --font-heading: 'Plus Jakarta Sans', -apple-system, sans-serif;
      --font-body: 'Inter', -apple-system, sans-serif;

      --container: 1180px;
      --radius-pill: 9999px;
      --radius-card: 16px;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: var(--font-body);
      background-color: #ffffff;
      color: var(--gray-700);
      line-height: 1.7;
      -webkit-font-smoothing: antialiased;
      overflow-x: hidden;
    }

    .container {
      max-width: var(--container);
      margin: 0 auto;
      padding: 0 28px;
    }

    a {
      text-decoration: none;
      color: inherit;
      transition: all 0.25s ease;
    }

    /* ========================================================
       TOP UTILITY BAR
       ======================================================== */
    .top-bar {
      background: var(--dark-950);
      color: var(--gray-400);
      font-size: 12.5px;
      letter-spacing: 0.02em;
      padding: 9px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .top-bar-inner {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
    }
    .top-bar-left span {
      margin-right: 20px;
    }
    .top-bar-right a {
      color: var(--gray-300);
      transition: color 0.2s;
    }
    .top-bar-right a:hover {
      color: #fff;
    }

    /* ========================================================
       MINIMALIST FLOATING NAVBAR
       ======================================================== */
    .navbar {
      position: sticky;
      top: 0;
      z-index: 100;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(0, 0, 0, 0.06);
      transition: all 0.3s;
    }
    .navbar-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 78px;
      gap: 20px;
    }
    .brand-group {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-shrink: 0;
    }
    .brand-logo-img {
      width: 42px;
      height: 42px;
      object-fit: contain;
    }
    .brand-title-wrap {
      line-height: 1.2;
    }
    .brand-name {
      font-family: var(--font-heading);
      font-size: 19px;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: var(--gray-900);
      white-space: nowrap;
    }
    .brand-sub {
      font-size: 10.5px;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--gray-500);
      white-space: nowrap;
    }

    .nav-menu {
      display: flex;
      align-items: center;
      gap: 26px;
      list-style: none;
      margin: 0;
      padding: 0;
    }
    .nav-menu a {
      font-size: 14px;
      font-weight: 500;
      color: var(--gray-600);
      white-space: nowrap;
      position: relative;
      padding: 6px 0;
      display: inline-block;
    }
    .nav-menu a:hover {
      color: var(--gray-900);
    }
    .nav-menu a::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 0;
      height: 1.5px;
      background: var(--gray-900);
      transition: width 0.25s ease;
    }
    .nav-menu a:hover::after {
      width: 100%;
    }

    .nav-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }

    /* Buttons */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 10px 24px;
      border-radius: var(--radius-pill);
      font-family: var(--font-heading);
      font-size: 13.5px;
      font-weight: 600;
      letter-spacing: 0.01em;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
      border: 1px solid transparent;
      white-space: nowrap;
    }
    .btn-dark {
      background: var(--gray-900);
      color: #fff;
    }
    .btn-dark:hover {
      background: #000;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }
    .btn-white {
      background: #fff;
      color: var(--gray-900);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    .btn-white:hover {
      background: #f9fafb;
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    .btn-outline-white {
      background: transparent;
      color: #fff;
      border: 1.5px solid rgba(255, 255, 255, 0.4);
    }
    .btn-outline-white:hover {
      border-color: #fff;
      background: rgba(255, 255, 255, 0.1);
    }
    .btn-outline-dark {
      background: transparent;
      color: var(--gray-900);
      border: 1px solid var(--gray-300);
    }
    .btn-outline-dark:hover {
      border-color: var(--gray-900);
      background: var(--gray-50);
    }

    /* ========================================================
       ATMOSPHERIC HERO (CENTERED & EDITORIAL)
       ======================================================== */
    .hero {
      position: relative;
      min-height: 580px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: #fff;
      padding: 110px 20px;
      background: 
        linear-gradient(180deg, rgba(9, 13, 22, 0.82) 0%, rgba(9, 13, 22, 0.92) 100%),
        url('img/hero-bg.jpg') center center / cover no-repeat;
    }
    .hero-content {
      max-width: 820px;
      margin: 0 auto;
      position: relative;
      z-index: 2;
    }
    .hero-tag {
      display: inline-block;
      font-size: 11.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.25em;
      color: rgba(255, 255, 255, 0.7);
      margin-bottom: 22px;
      padding: 6px 16px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: var(--radius-pill);
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(8px);
    }
    .hero-title {
      font-family: var(--font-heading);
      font-size: 48px;
      font-weight: 800;
      line-height: 1.2;
      letter-spacing: -0.03em;
      margin-bottom: 22px;
      color: #ffffff;
    }
    .hero-desc {
      font-size: 16.5px;
      color: rgba(255, 255, 255, 0.8);
      font-weight: 300;
      line-height: 1.7;
      margin-bottom: 38px;
      max-width: 660px;
      margin-left: auto;
      margin-right: auto;
    }
    .hero-buttons {
      display: flex;
      justify-content: center;
      gap: 16px;
      flex-wrap: wrap;
    }

    /* ========================================================
       SECTION HEADINGS (MINIMALIST & CENTERED)
       ======================================================== */
    .section {
      padding: 100px 0;
    }
    .section-header-center {
      text-align: center;
      max-width: 640px;
      margin: 0 auto 60px;
    }
    .section-eyebrow {
      font-size: 11.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.2em;
      color: var(--gray-500);
      margin-bottom: 12px;
      display: block;
    }
    .section-headline {
      font-family: var(--font-heading);
      font-size: 34px;
      font-weight: 800;
      color: var(--gray-900);
      letter-spacing: -0.02em;
      margin-bottom: 14px;
    }
    .section-subtext {
      font-size: 15.5px;
      color: var(--gray-500);
      font-weight: 400;
      line-height: 1.65;
    }

    /* ========================================================
       OUR WORK / PRODUCT SHOWCASE GRID (LIKE REFERENCE)
       ======================================================== */
    .catalog-filter-row {
      display: flex;
      justify-content: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 44px;
    }
    .filter-pill {
      background: transparent;
      border: 1px solid var(--gray-200);
      padding: 8px 20px;
      border-radius: var(--radius-pill);
      font-size: 13px;
      font-weight: 500;
      color: var(--gray-600);
      cursor: pointer;
      transition: all 0.2s;
    }
    .filter-pill:hover {
      border-color: var(--gray-900);
      color: var(--gray-900);
    }
    .filter-pill.active {
      background: var(--gray-900);
      color: #fff;
      border-color: var(--gray-900);
    }

    .gallery-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 32px;
    }
    .product-showcase-card {
      background: #ffffff;
      border: 1px solid var(--gray-200);
      border-radius: var(--radius-card);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .product-showcase-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
      border-color: var(--gray-300);
    }
    .card-img-wrap {
      position: relative;
      height: 230px;
      overflow: hidden;
      background: var(--dark-950);
    }
    .card-img-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    .product-showcase-card:hover .card-img-wrap img {
      transform: scale(1.06);
    }
    .card-badge-cat {
      position: absolute;
      top: 14px;
      left: 14px;
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(8px);
      color: var(--gray-900);
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      padding: 4px 10px;
      border-radius: var(--radius-pill);
    }

    .card-body-content {
      padding: 24px;
      display: flex;
      flex-direction: column;
      flex: 1;
      justify-content: space-between;
    }
    .card-sku {
      font-family: monospace;
      font-size: 11.5px;
      color: var(--gray-400);
      letter-spacing: 0.05em;
      margin-bottom: 6px;
    }
    .card-product-title {
      font-family: var(--font-heading);
      font-size: 18px;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 10px;
      line-height: 1.35;
    }
    .card-product-specs {
      font-size: 13px;
      color: var(--gray-500);
      line-height: 1.6;
      margin-bottom: 22px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .card-footer-flex {
      border-top: 1px solid var(--gray-100);
      padding-top: 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .card-price-value {
      font-family: var(--font-heading);
      font-size: 16px;
      font-weight: 800;
      color: var(--gray-900);
    }

    /* ========================================================
       ABOUT US / PHILOSOPHY (LIKE REFERENCE)
       ======================================================== */
    .about-section {
      background: var(--gray-50);
      border-top: 1px solid var(--gray-200);
      border-bottom: 1px solid var(--gray-200);
    }
    .about-lead-text {
      text-align: center;
      max-width: 720px;
      margin: 0 auto 60px;
      font-size: 16.5px;
      color: var(--gray-600);
      line-height: 1.8;
    }
    .features-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 36px;
      text-align: center;
    }
    .feature-item {
      padding: 10px;
    }
    .feature-item-icon {
      width: 54px;
      height: 54px;
      margin: 0 auto 20px;
      border: 1px solid var(--gray-300);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: var(--gray-900);
      background: #ffffff;
      transition: all 0.25s ease;
    }
    .feature-item:hover .feature-item-icon {
      background: var(--gray-900);
      color: #ffffff;
      border-color: var(--gray-900);
      transform: translateY(-4px);
    }
    .feature-item-title {
      font-family: var(--font-heading);
      font-size: 16px;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 8px;
    }
    .feature-item-desc {
      font-size: 13.5px;
      color: var(--gray-500);
      line-height: 1.6;
    }

    /* ========================================================
       CUSTOM PART CALLOUT (EDITORIAL LUXURY BANNER)
       ======================================================== */
    .editorial-callout {
      background: 
        linear-gradient(135deg, rgba(9, 13, 22, 0.94), rgba(15, 23, 42, 0.96)),
        url('img/hero-bg.jpg') center center / cover no-repeat;
      color: #fff;
      border-radius: 20px;
      padding: 60px 48px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .callout-inner {
      max-width: 680px;
      margin: 0 auto;
    }
    .callout-title {
      font-family: var(--font-heading);
      font-size: 32px;
      font-weight: 800;
      letter-spacing: -0.02em;
      margin-bottom: 14px;
      color: #ffffff;
    }
    .callout-desc {
      font-size: 15.5px;
      color: rgba(255, 255, 255, 0.75);
      line-height: 1.7;
      margin-bottom: 30px;
      font-weight: 300;
    }

    /* ========================================================
       WORKFLOW / HOW IT WORKS
       ======================================================== */
    .workflow-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 30px;
    }
    .workflow-card {
      padding: 24px 10px;
      text-align: left;
    }
    .workflow-card-num {
      font-family: var(--font-heading);
      font-size: 28px;
      font-weight: 800;
      color: var(--gray-300);
      margin-bottom: 12px;
    }
    .workflow-card-title {
      font-family: var(--font-heading);
      font-size: 16.5px;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 8px;
    }
    .workflow-card-desc {
      font-size: 13.5px;
      color: var(--gray-500);
      line-height: 1.6;
    }

    /* ========================================================
       TESTIMONIAL / SOCIAL PROOF (LIKE REFERENCE)
       ======================================================== */
    .testimonial-section {
      background: var(--gray-50);
      border-top: 1px solid var(--gray-200);
      border-bottom: 1px solid var(--gray-200);
      text-align: center;
    }
    .testimonial-quote {
      font-family: var(--font-heading);
      font-size: 20px;
      font-style: italic;
      color: var(--gray-900);
      max-width: 740px;
      margin: 0 auto 24px;
      line-height: 1.6;
    }
    .testimonial-author {
      font-size: 14px;
      font-weight: 700;
      color: var(--gray-900);
    }
    .testimonial-sub {
      font-size: 12px;
      color: var(--gray-400);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-top: 2px;
    }

    /* ========================================================
       MODAL DETAIL PRODUK
       ======================================================== */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(9, 13, 22, 0.7);
      backdrop-filter: blur(8px);
      z-index: 999;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-box {
      background: #ffffff;
      border-radius: var(--radius-card);
      max-width: 520px;
      width: 100%;
      padding: 34px;
      position: relative;
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
    }
    .modal-close-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      background: var(--gray-100);
      border: none;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--gray-600);
      transition: all 0.2s;
    }
    .modal-close-btn:hover {
      background: var(--gray-200);
      color: var(--gray-900);
    }

    /* ========================================================
       FOOTER (MINIMALIST LUXURY)
       ======================================================== */
    .footer {
      background: var(--dark-950);
      color: #ffffff;
      padding: 80px 0 36px;
    }
    .footer-grid {
      display: grid;
      grid-template-columns: 1.6fr 1fr 1fr 1.3fr;
      gap: 40px;
      margin-bottom: 50px;
    }
    .footer-brand-title {
      font-family: var(--font-heading);
      font-size: 20px;
      font-weight: 800;
      letter-spacing: -0.02em;
      margin-bottom: 12px;
    }
    .footer-brand-desc {
      font-size: 13.5px;
      color: var(--gray-400);
      line-height: 1.7;
      margin-bottom: 24px;
    }
    .footer-heading {
      font-family: var(--font-heading);
      font-size: 13.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: #fff;
      margin-bottom: 18px;
    }
    .footer-menu {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .footer-menu a {
      font-size: 13.5px;
      color: var(--gray-400);
      transition: color 0.2s;
    }
    .footer-menu a:hover {
      color: #fff;
    }
    .footer-contact-item {
      display: flex;
      gap: 12px;
      font-size: 13px;
      color: var(--gray-400);
      margin-bottom: 14px;
      line-height: 1.6;
    }
    .footer-contact-item i {
      color: var(--gray-300);
      margin-top: 3px;
    }
    .footer-bottom {
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      padding-top: 26px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
      font-size: 13px;
      color: var(--gray-500);
    }

    /* ========================================================
       RESPONSIVE BREAKPOINTS
       ======================================================== */
    @media (max-width: 1024px) {
      .gallery-grid { grid-template-columns: repeat(2, 1fr); }
      .features-row { grid-template-columns: repeat(2, 1fr); gap: 28px; }
      .workflow-grid { grid-template-columns: repeat(2, 1fr); gap: 24px; }
      .footer-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
      .nav-menu { display: none; }
      .hero-title { font-size: 34px; }
      .hero-desc { font-size: 15px; }
      .gallery-grid { grid-template-columns: 1fr; }
      .features-row { grid-template-columns: 1fr; }
      .workflow-grid { grid-template-columns: 1fr; }
      .footer-grid { grid-template-columns: 1fr; }
      .editorial-callout { padding: 40px 24px; }
    }
  </style>
</head>
<body>

  <!-- Top Utility Bar -->
  <div class="top-bar">
    <div class="container top-bar-inner">
      <div class="top-bar-left">
        <span><i class="fa-solid fa-shield-check" style="font-size:11px; margin-right:6px; color:#38bdf8;"></i> Supplier Suku Cadang &amp; Komponen Mesin Industri</span>
        <span>📍 Malang, Jawa Timur</span>
      </div>
      <div class="top-bar-right">
        <a href="https://wa.me/6282225255251" target="_blank" rel="noopener" style="display:inline-flex; align-items:center; gap:6px;">
          <i class="fa-brands fa-whatsapp" style="color:#34d399; font-size:13px;"></i>
          <span>Konsultasi Pelanggan: <strong>0822 2525 5251</strong></span>
        </a>
      </div>
    </div>
  </div>

  <!-- Minimalist Floating Navbar -->
  <header class="navbar">
    <div class="container navbar-inner">
      <a href="landing.php" class="brand-group">
        <?php if (file_exists(__DIR__ . '/img/logo.png')): ?>
          <img src="img/logo.png" alt="Logo CV. Tidar Teknik" class="brand-logo-img">
        <?php endif; ?>
        <div class="brand-title-wrap">
          <div class="brand-name">CV. TIDAR TEKNIK</div>
          <div class="brand-sub">Industrial Precision Supply</div>
        </div>
      </a>

      <nav>
        <ul class="nav-menu">
          <li><a href="#koleksi">Katalog Produk</a></li>
          <li><a href="#about">Tentang Kami</a></li>
          <li><a href="#custom">Request Part</a></li>
          <li><a href="#alur">Alur Kerja</a></li>
          <li><a href="#kontak">Kontak</a></li>
        </ul>
      </nav>

      <div class="nav-actions">
        <a href="customer-login.php" class="btn btn-dark" style="padding: 9px 20px; font-size: 13.5px;">
          <i class="fa-solid fa-arrow-right-to-bracket" style="font-size: 12px;"></i>
          <span>Masuk Portal</span>
        </a>
        <a href="https://wa.me/6282225255251?text=Halo%20CV.%20Tidar%20Teknik%2C%20saya%20ingin%20dibuatkan%20akun%20Customer%20Portal." target="_blank" rel="noopener" class="btn btn-outline-dark" style="padding: 9px 18px; font-size: 13.5px;">
          <span>Minta Akun</span>
        </a>
      </div>
    </div>
  </header>

  <!-- Atmospheric Centered Hero Section (Like Reference) -->
  <section class="hero" id="hero">
    <div class="hero-content">
      <span class="hero-tag">SUPPLIER RESMI SUKU CADANG INDUSTRI</span>
      
      <h1 class="hero-title">
        Presisi Komponen.<br>Keandalan Operasional Pabrik.
      </h1>

      <p class="hero-desc">
        Menghadirkan rantai pasok suku cadang mekanikal dan otomasi mesin industri dengan transparansi harga, surat penawaran resmi berstempel, dan pengiriman tepat waktu ke seluruh Indonesia.
      </p>

      <div class="hero-buttons">
        <a href="#koleksi" class="btn btn-white">
          <span>Jelajahi Suku Cadang</span>
          <i class="fa-solid fa-arrow-down" style="font-size:12px;"></i>
        </a>
        <a href="customer-login.php" class="btn btn-outline-white">
          <i class="fa-solid fa-lock" style="font-size:12px;"></i>
          <span>Masuk ke Customer Portal</span>
        </a>
      </div>
    </div>
  </section>

  <!-- Section 1: "Our Work" / Product Showcase Grid (Like Reference) -->
  <section class="section" id="koleksi">
    <div class="container">
      <div class="section-header-center">
        <span class="section-eyebrow">KATALOG PILIHAN</span>
        <h2 class="section-headline">Suku Cadang &amp; Komponen Unggulan</h2>
        <p class="section-subtext">
          Telusuri komponen mekanik dengan standar pabrikan presisi tinggi untuk mendukung produktivitas lini mesin Anda.
        </p>
      </div>

      <!-- Category Filter Pills -->
      <div class="catalog-filter-row">
        <button type="button" class="filter-pill active" data-category="all" onclick="filterGallery(this.dataset.category, this)">
          Semua Koleksi (<?= count($products) ?>)
        </button>
        <?php foreach ($categories as $cat): ?>
          <button type="button" class="filter-pill" data-category="<?= htmlspecialchars($cat) ?>" onclick="filterGallery(this.dataset.category, this)">
            <?= htmlspecialchars($cat) ?>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- Product Cards Gallery Grid -->
      <div class="gallery-grid" id="galleryGrid">
        <?php foreach ($products as $p): ?>
          <?php 
            $thumb = getProductThumb($p);
            $formattedPrice = 'Rp ' . number_format($p['harga'], 0, ',', '.');
            if (!empty($p['harga_min']) && !empty($p['harga_max'])) {
                $formattedPrice = 'Rp ' . number_format($p['harga_min'], 0, ',', '.') . ' – Rp ' . number_format($p['harga_max'], 0, ',', '.');
            }
          ?>
          <div class="product-showcase-card" data-category="<?= htmlspecialchars($p['kategori']) ?>">
            <div class="card-img-wrap">
              <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($p['nama']) ?>" loading="lazy">
              <span class="card-badge-cat"><?= htmlspecialchars($p['kategori']) ?></span>
            </div>

            <div class="card-body-content">
              <div>
                <div class="card-sku"><?= htmlspecialchars($p['kode']) ?></div>
                <h3 class="card-product-title"><?= htmlspecialchars($p['nama']) ?></h3>
                <p class="card-product-specs">
                  <?= htmlspecialchars($p['spesifikasi'] ?: 'Spesifikasi presisi standar pabrikan industri.') ?>
                </p>
              </div>

              <div class="card-footer-flex">
                <div>
                  <div style="font-size:11px; text-transform:uppercase; color:var(--gray-400); font-weight:600;">Estimasi Harga</div>
                  <div class="card-price-value"><?= $formattedPrice ?></div>
                </div>

                <div style="display:flex; gap:8px;">
                  <button 
                    type="button" 
                    class="btn btn-outline-dark" 
                    style="padding: 7px 16px; font-size:12.5px;"
                    onclick="openProductModal(<?= htmlspecialchars(json_encode(['kategori' => $p['kategori'], 'nama' => $p['nama'], 'kode' => $p['kode'], 'spesifikasi' => $p['spesifikasi'], 'harga' => $p['harga'], 'harga_min' => $p['harga_min'], 'harga_max' => $p['harga_max']], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)"
                  >
                    Detail
                  </button>
                  <a href="customer-login.php" class="btn btn-dark" style="padding: 7px 16px; font-size:12.5px;">
                    Pesan
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Section 2: "About us" / Clean Philosophy (Like Reference) -->
  <section class="section about-section" id="about">
    <div class="container">
      <div class="section-header-center" style="margin-bottom: 24px;">
        <span class="section-eyebrow">TENTANG KAMI</span>
        <h2 class="section-headline">Komitmen CV. Tidar Teknik</h2>
      </div>

      <p class="about-lead-text">
        Berbasis di Jawa Timur, CV. Tidar Teknik bergerak sebagai mitra pengadaan dropship terpercaya untuk kebutuhan permesinan manufaktur, pabrik, dan bengkel industri. Kami memastikan setiap komponen memiliki presisi tepat, spesifikasi akurat, dan didukung administrasi dokumen legal yang sah.
      </p>

      <div class="features-row">
        <div class="feature-item">
          <div class="feature-item-icon">
            <i class="fa-solid fa-certificate"></i>
          </div>
          <h4 class="feature-item-title">Presisi Terjamin</h4>
          <p class="feature-item-desc">Setiap part diperiksa sesuai toleransi ukuran dan standar manufaktur resmi.</p>
        </div>

        <div class="feature-item">
          <div class="feature-item-icon">
            <i class="fa-solid fa-file-invoice"></i>
          </div>
          <h4 class="feature-item-title">Quotation Resmi</h4>
          <p class="feature-item-desc">Menerbitkan surat penawaran harga resmi format PDF berstempel untuk verifikasi procurement.</p>
        </div>

        <div class="feature-item">
          <div class="feature-item-icon">
            <i class="fa-solid fa-handshake"></i>
          </div>
          <h4 class="feature-item-title">Pembayaran Fleksibel</h4>
          <p class="feature-item-desc">Mendukung sistem pembayaran DP, cicilan termin, hingga pelunasan transfer BCA.</p>
        </div>

        <div class="feature-item">
          <div class="feature-item-icon">
            <i class="fa-solid fa-truck-fast"></i>
          </div>
          <h4 class="feature-item-title">Logistik Nusantara</h4>
          <p class="feature-item-desc">Pengiriman cepat dengan nomor resi pelacakan aktif ke seluruh pelosok industri Indonesia.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Section 3: Custom & Rare Part Callout (Editorial Luxury) -->
  <section class="section" id="custom" style="padding-top: 80px; padding-bottom: 80px;">
    <div class="container">
      <div class="editorial-callout">
        <div class="callout-inner">
          <span style="font-size:11px; text-transform:uppercase; letter-spacing:0.2em; color:rgba(255,255,255,0.6); display:block; margin-bottom:12px;">SOLUSI PENGADAAN SPESIFIK</span>
          <h2 class="callout-title">Suku Cadang Mesin Khusus atau Impor?</h2>
          <p class="callout-desc">
            Jika komponen yang Anda cari tidak tercantum dalam katalog, tim teknis kami siap membantu mencarikan langsung ke jaringan supplier spesialis dengan penawaran harga terbaik.
          </p>
          <a href="customer-login.php" class="btn btn-white" style="padding: 12px 32px;">
            <span>Ajukan Permintaan di Portal</span>
            <i class="fa-solid fa-arrow-right" style="font-size:12px;"></i>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Section 4: Workflow / 4 Langkah Pemesanan -->
  <section class="section" id="alur" style="padding-top: 40px;">
    <div class="container">
      <div class="section-header-center">
        <span class="section-eyebrow">ALUR KERJA</span>
        <h2 class="section-headline">Proses Pemesanan Sederhana</h2>
        <p class="section-subtext">Transparansi penuh dari tahap penelusuran barang hingga komponen tiba di pabrik Anda.</p>
      </div>

      <div class="workflow-grid">
        <div class="workflow-card">
          <div class="workflow-card-num">01</div>
          <h4 class="workflow-card-title">Pilih / Request Part</h4>
          <p class="workflow-card-desc">Tentukan produk dari katalog atau kirimkan rincian spesifikasi untuk suku cadang khusus.</p>
        </div>

        <div class="workflow-card">
          <div class="workflow-card-num">02</div>
          <h4 class="workflow-card-title">Surat Penawaran Resmi</h4>
          <p class="workflow-card-desc">Admin menerbitkan draft surat penawaran harga resmi (Quotation V1–V3) lengkap dengan rincian PPN dan termin.</p>
        </div>

        <div class="workflow-card">
          <div class="workflow-card-num">03</div>
          <h4 class="workflow-card-title">Penerbitan PO &amp; DP</h4>
          <p class="workflow-card-desc">Setelah disetujui, Purchase Order (PO) diterbitkan dan lakukan pembayaran bertahap via transfer bank BCA.</p>
        </div>

        <div class="workflow-card">
          <div class="workflow-card-num">04</div>
          <h4 class="workflow-card-title">Ekspedisi &amp; Garansi</h4>
          <p class="workflow-card-desc">Barang dikirimkan via ekspedisi rekanan resmi dengan jaminan garansi retur jika tidak sesuai spesifikasi.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Section 5: Testimonial / Social Proof (Like Reference) -->
  <section class="section testimonial-section">
    <div class="container">
      <div class="section-eyebrow">KEPERCAYAAN MITRA</div>
      <blockquote class="testimonial-quote">
        "Kecepatan CV. Tidar Teknik dalam menerbitkan surat penawaran resmi dan mencarikan roller chain stainless ukuran spesifik sangat membantu kami meminimalkan downtime mesin pabrik."
      </blockquote>
      <div class="testimonial-author">Ir. Bambang S. — Manager Pemeliharaan &amp; Mesin</div>
      <div class="testimonial-sub">Mitra Manufaktur Industri Makanan &amp; Minuman, Jawa Timur</div>
    </div>
  </section>

  <!-- Editorial Luxury Footer -->
  <footer class="footer" id="kontak">
    <div class="container">
      <div class="footer-grid">
        <div>
          <div class="footer-brand-title">CV. TIDAR TEKNIK</div>
          <p class="footer-brand-desc">
            Supplier suku cadang teknik, rantai industri, bearing, dan perlengkapan otomasi mesin terpercaya. Melayani pengadaan profesional B2B dan sistem dropship terintegrasi.
          </p>
          <a href="https://wa.me/6282225255251" target="_blank" rel="noopener" class="btn btn-outline-white" style="font-size:12.5px; padding:8px 18px;">
            <i class="fa-brands fa-whatsapp" style="color:#34d399;"></i> Konsultasi WhatsApp
          </a>
        </div>

        <div>
          <h4 class="footer-heading">Navigasi</h4>
          <ul class="footer-menu">
            <li><a href="#hero">Beranda</a></li>
            <li><a href="#koleksi">Koleksi Suku Cadang</a></li>
            <li><a href="#about">Tentang Kami</a></li>
            <li><a href="#custom">Permintaan Khusus</a></li>
            <li><a href="#alur">Alur Pemesanan</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-heading">Layanan Customer</h4>
          <ul class="footer-menu">
            <li><a href="#koleksi">Katalog Suku Cadang</a></li>
            <li><a href="#custom">Permintaan Part Khusus</a></li>
            <li><a href="customer-login.php">Masuk Customer Portal</a></li>
            <li><a href="https://wa.me/6282225255251?text=Halo%20CV.%20Tidar%20Teknik%2C%20saya%20ingin%20dibuatkan%20akun%20Customer%20Portal." target="_blank" rel="noopener">Minta Akun Baru (via WhatsApp)</a></li>
            <li><a href="#alur">Alur Pemesanan</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-heading">Kantor Operasional</h4>
          <div class="footer-contact-item">
            <i class="fa-solid fa-location-dot"></i>
            <div>
              <strong>CV. Tidar Teknik</strong><br>
              Perum Bumi Mas Blok C1 No 2, Jedong Wagir, Kab. Malang, Jawa Timur 65158
            </div>
          </div>
          <div class="footer-contact-item">
            <i class="fa-solid fa-envelope"></i>
            <div>tidarteknik@gmail.com</div>
          </div>
          <div class="footer-contact-item">
            <i class="fa-solid fa-clock"></i>
            <div>Senin – Sabtu: 08.00 – 17.00 WIB</div>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <div>&copy; <?= date('Y') ?> CV. Tidar Teknik. Hak cipta dilindungi undang-undang.</div>
        <div>
          <a href="customer-login.php" style="color:var(--gray-400);">Customer Portal CV. Tidar Teknik</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Modal Detail Produk -->
  <div class="modal-overlay" id="productModal" onclick="closeModalBackdrop(event)">
    <div class="modal-box" onclick="event.stopPropagation()">
      <button type="button" class="modal-close-btn" onclick="closeModal()" title="Tutup">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <span id="modalCat" style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--gray-500); display:block; margin-bottom:6px;">KATEGORI</span>
      <h3 id="modalName" style="font-family:var(--font-heading); font-size:22px; font-weight:800; color:var(--gray-900); margin-bottom:4px;">Nama Produk</h3>
      <div id="modalSku" style="font-family:monospace; font-size:12.5px; color:var(--gray-400); margin-bottom:20px;">SKU: -</div>

      <div style="background:var(--gray-50); border:1px solid var(--gray-200); border-radius:10px; padding:18px; margin-bottom:24px;">
        <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--gray-900); margin-bottom:6px; letter-spacing:0.05em;">Spesifikasi Lengkap:</div>
        <div id="modalSpecs" style="font-size:13.5px; color:var(--gray-600); line-height:1.6;">Spesifikasi...</div>
      </div>

      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; padding-top:12px; border-top:1px solid var(--gray-100);">
        <div>
          <div style="font-size:11px; text-transform:uppercase; color:var(--gray-400); font-weight:600;">Estimasi Harga</div>
          <div id="modalPrice" style="font-family:var(--font-heading); font-size:20px; font-weight:800; color:var(--gray-900);">Rp 0</div>
        </div>
        <span style="font-size:12px; color:var(--gray-600); font-weight:600;"><i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Ketersediaan dikonfirmasi saat pemesanan</span>
      </div>

      <div style="display:flex; gap:10px;">
        <a href="customer-login.php" class="btn btn-dark" style="flex:1;">
          <i class="fa-solid fa-lock" style="font-size:11px;"></i> Masuk Portal untuk Membeli
        </a>
        <a href="https://wa.me/6282225255251?text=Halo%20CV.%20Tidar%20Teknik%2C%20saya%20ingin%20dibuatkan%20akun%20Customer%20Portal." target="_blank" rel="noopener" class="btn btn-outline-dark">
          Minta Akun
        </a>
      </div>
    </div>
  </div>

  <!-- Interactive JavaScript -->
  <script>
    function filterGallery(category, btn) {
      document.querySelectorAll('.filter-pill').forEach(el => el.classList.remove('active'));
      btn.classList.add('active');

      const cards = document.querySelectorAll('.product-showcase-card');
      cards.forEach(card => {
        const cardCat = card.getAttribute('data-category');
        if (category === 'all' || cardCat === category) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    }

    function openProductModal(product) {
      document.getElementById('modalCat').textContent = product.kategori || 'Umum';
      document.getElementById('modalName').textContent = product.nama || '';
      document.getElementById('modalSku').textContent = 'KODE: ' + (product.kode || '-');
      document.getElementById('modalSpecs').textContent = product.spesifikasi || 'Spesifikasi presisi standar pabrikan.';

      let price = 'Rp ' + Number(product.harga || 0).toLocaleString('id-ID');
      if (product.harga_min && product.harga_max) {
        price = 'Rp ' + Number(product.harga_min).toLocaleString('id-ID') + ' – Rp ' + Number(product.harga_max).toLocaleString('id-ID');
      }
      document.getElementById('modalPrice').textContent = price;

      document.getElementById('productModal').style.display = 'flex';
    }

    function closeModal() {
      document.getElementById('productModal').style.display = 'none';
    }

    function closeModalBackdrop(e) {
      if (e.target.id === 'productModal') closeModal();
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeModal();
    });
  </script>

</body>
</html>
