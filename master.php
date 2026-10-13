<?php
require_once 'auth.php';
requireLogin();
include 'db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Master Perusahaan — CV. Tidar Teknik</title>
<style>
.brand-icon img { width:40px; height:40px; object-fit:contain; border-radius:10px; }
</style>
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body>

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo">
      <div class="brand-icon"><img src="img/logo.png" alt="Logo CV. Tidar Teknik"></div>
      <div>
        <div class="brand-name">CV. Tidar Teknik</div>
        <div class="brand-sub">Sistem Manajemen Dokumen</div>
      </div>
    </div>
    <div class="brand-address">📍 Perum Bumi Mas Blok C1 No 2, Jedong Wagir, Kab. Malang 65158</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-title">Menu Utama</div>
    <a href="index.php" class="nav-item"><span class="nav-icon">🏠</span> Dashboard</a>
    <a href="po.php"    class="nav-item"><span class="nav-icon">📋</span> Purchase Order</a>
    <a href="nota.php"  class="nav-item"><span class="nav-icon">🧾</span> Nota / Invoice</a>
    <a href="penawaran.php" class="nav-item"><span class="nav-icon">📝</span> Penawaran</a>
    <a href="customer-history.php" class="nav-item"><span class="nav-icon">👥</span> Riwayat Customer</a>
    <div class="nav-section-title" style="margin-top:8px">Customer Portal</div>
    <a href="admin-requests.php" class="nav-item"><span class="nav-icon">📥</span> Permintaan Barang</a>
    <a href="admin-orders.php" class="nav-item"><span class="nav-icon">📦</span> Pesanan Customer</a>
    <a href="admin-products.php" class="nav-item"><span class="nav-icon">🛍️</span> Katalog Produk</a>
    <div class="nav-section-title" style="margin-top:8px">Pengaturan</div>
    <a href="master.php" class="nav-item active"><span class="nav-icon">🏢</span> Master Perusahaan</a>
  </nav>
  <div class="sidebar-footer">
    <div style="margin-bottom:8px">📞 0822 2525 5251 &nbsp;|&nbsp; tidarteknik@gmail.com</div>
    <a href="logout.php" style="display:flex;align-items:center;gap:6px;justify-content:center;padding:7px 12px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:8px;color:#fca5a5;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s" onmouseover="this.style.background='rgba(239,68,68,.25)'" onmouseout="this.style.background='rgba(239,68,68,.12)'" id="btn-logout-master">🚪 Keluar</a>
  </div>
</aside>

<div class="main-wrap">
  <header class="topbar">
    <button class="hamburger-btn" id="hamburger-btn" onclick="toggleSidebar()" aria-label="Buka menu">☰</button>
    <div>
      <div class="topbar-title">Master Perusahaan</div>
      <div class="topbar-subtitle">Kelola data Supplier &amp; Customer untuk autocomplete</div>
    </div>
    <div class="topbar-actions">
      <button class="btn btn-ghost" onclick="window.location.href='index.php'">← Kembali ke Dashboard</button>
    </div>
  </header>

  <main class="page-content">
    <!-- TABS -->
    <div class="tab-list">
      <button class="tab-btn active" data-tab="supplier">🏭 Supplier (PO)</button>
      <button class="tab-btn" data-tab="customer">👥 Customer (Nota)</button>
    </div>

    <!-- TAB SUPPLIER -->
    <div class="tab-panel active" id="tab-supplier">
      <div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">
        <!-- List -->
        <div class="card">
          <div class="card-header">
            <span style="font-size:20px">🏭</span>
            <div class="card-title">Daftar Supplier</div>
          </div>
          <div class="table-wrap" style="border-radius:0;box-shadow:none;border:none;border-top:1px solid var(--navy-200)">
            <table>
              <thead><tr><th>Nama</th><th>Kode</th><th>Kota</th><th>Alamat</th><th>Aksi</th></tr></thead>
              <tbody id="supplier-tbody"></tbody>
            </table>
          </div>
        </div>
        <!-- Form -->
        <div class="card" id="sup-form-section" style="position:sticky;top:80px">
          <div class="card-header">
            <span style="font-size:18px">➕</span>
            <div class="card-title" id="sup-form-title">Tambah Supplier</div>
          </div>
          <div class="card-body">
            <input type="hidden" id="sup-edit-id">
            <div class="form-group">
              <label class="form-label required">Nama Perusahaan</label>
              <input type="text" class="form-control" id="sup-nama" placeholder="CV. MEGA TEKNIK ENGINEERING">
            </div>
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Kode</label>
                <input type="text" class="form-control" id="sup-kode" placeholder="MTE">
              </div>
              <div class="form-group">
                <label class="form-label">Kota</label>
                <input type="text" class="form-control" id="sup-kota" placeholder="SURABAYA">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Alamat</label>
              <textarea class="form-control" id="sup-alamat" rows="2" placeholder="Alamat lengkap..."></textarea>
            </div>
            <div style="display:flex;gap:8px">
              <button type="button" class="btn btn-primary" id="btn-save-supplier" style="flex:1">💾 Simpan</button>
              <button type="button" class="btn btn-ghost" id="btn-reset-supplier">↺</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB CUSTOMER -->
    <div class="tab-panel" id="tab-customer">
      <div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">
        <!-- List -->
        <div class="card">
          <div class="card-header">
            <span style="font-size:20px">👥</span>
            <div class="card-title">Daftar Customer</div>
          </div>
          <div class="table-wrap" style="border-radius:0;box-shadow:none;border:none;border-top:1px solid var(--navy-200)">
            <table>
              <thead><tr><th>Nama</th><th>Kode</th><th>Kota</th><th>Alamat</th><th>Aksi</th></tr></thead>
              <tbody id="customer-tbody"></tbody>
            </table>
          </div>
        </div>
        <!-- Form -->
        <div class="card" id="cus-form-section" style="position:sticky;top:80px">
          <div class="card-header">
            <span style="font-size:18px">➕</span>
            <div class="card-title" id="cus-form-title">Tambah Customer</div>
          </div>
          <div class="card-body">
            <input type="hidden" id="cus-edit-id">
            <div class="form-group">
              <label class="form-label required">Nama Perusahaan / Customer</label>
              <input type="text" class="form-control" id="cus-nama" placeholder="PT MCENERGY BIOMAS INDONESIA">
            </div>
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Kode</label>
                <input type="text" class="form-control" id="cus-kode" placeholder="MBI">
              </div>
              <div class="form-group">
                <label class="form-label">Kota</label>
                <input type="text" class="form-control" id="cus-kota" placeholder="SURABAYA">
              </div>
              <div class="form-group">
                <label class="form-label">No. NPWP</label>
                <input type="text" class="form-control" id="cus-npwp" placeholder="00.000.000.0-000.000">
              </div>
              <div class="form-group">
                <label class="form-label">PIC / Contact Person</label>
                <input type="text" class="form-control" id="cus-pic" placeholder="Contoh: BAPAK SYAMSUDIN">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Alamat Default</label>
              <textarea class="form-control" id="cus-alamat" rows="2" placeholder="Alamat lengkap..."></textarea>
            </div>
            <div style="display:flex;gap:8px">
              <button type="button" class="btn btn-success" id="btn-save-customer" style="flex:1">💾 Simpan</button>
              <button type="button" class="btn btn-ghost" id="btn-reset-customer">↺</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="js/app.js?v=<?= time() ?>"></script>
<script src="js/master.js"></script>
</body>
</html>
