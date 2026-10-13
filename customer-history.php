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
<title>Riwayat Customer — CV. Tidar Teknik</title>
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<style>
  .brand-icon img { width: 100%; height: 100%; object-fit: contain; }
  .customer-list-wrap { 
    display: grid; 
    grid-template-columns: 320px 1fr; 
    gap: 24px; 
    height: calc(100vh - 140px); 
    transition: grid-template-columns 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .customer-list-wrap.sidebar-collapsed {
    grid-template-columns: 70px 1fr;
  }

  .customer-sidebar { 
    background: white; 
    border-radius: 16px; 
    border: 1px solid var(--navy-100); 
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
  }
  .customer-sidebar.collapsed {
    width: 70px;
  }
  .customer-sidebar.collapsed .customer-search-box,
  .customer-sidebar.collapsed .customer-item .name,
  .customer-sidebar.collapsed .customer-item .city {
    display: none;
  }
  .customer-sidebar.collapsed .customer-item {
    align-items: center;
    padding: 14px 0;
  }
  .customer-sidebar.collapsed .badge-code {
    font-size: 12px;
    padding: 6px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
  }

  .sidebar-toggle-btn {
    position: absolute;
    top: 12px; right: 12px;
    width: 32px; height: 32px;
    background: var(--navy-50);
    border: 1px solid var(--navy-100);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s;
  }
  .sidebar-toggle-btn:hover { background: var(--navy-100); color: var(--teal-600); }
  .customer-sidebar.collapsed .sidebar-toggle-btn {
    right: 50%;
    transform: translateX(50%);
  }
  .customer-search-box {
    padding: 16px;
    border-bottom: 1px solid var(--navy-50);
    background: var(--navy-50);
  }
  .customer-list-container {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
  }
  .customer-item { 
    padding: 14px 16px; 
    border-radius: 12px; 
    cursor: pointer; 
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
    margin-bottom: 10px; 
    border: 1px solid transparent; 
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 4px;
    background: white;
  }
  .customer-item:hover { 
    background: var(--navy-50); 
    border-color: var(--navy-200); 
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
  }
  .customer-item.active { 
    background: var(--teal-600); 
    border-color: var(--teal-700); 
    box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.3);
  }
  .customer-item .name { font-weight: 700; color: var(--navy-800); font-size: 14px; transition: color 0.2s; }
  .customer-item.active .name { color: white; }
  
  .customer-item .code-row { display: flex; align-items: center; gap: 8px; }
  .customer-item .badge-code { 
    font-size: 10px; 
    font-weight: 800; 
    padding: 2px 6px; 
    border-radius: 4px; 
    background: var(--navy-100); 
    color: var(--navy-600); 
  }
  .customer-item.active .badge-code { background: rgba(255,255,255,0.2); color: white; }
  .customer-item .city { font-size: 11px; color: var(--navy-400); }
  .customer-item.active .city { color: rgba(255,255,255,0.7); }

  .history-content { 
    background: white; 
    border-radius: 16px; 
    border: 1px solid var(--navy-100); 
    overflow-y: auto; 
    padding: 32px; 
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  }

  /* DRAWER EFFECT */
  #drawer-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  #drawer-overlay.open { 
    display: none; /* Default hide */
  }
  @media (max-width: 992px) {
    #drawer-overlay.open { 
      display: block; 
      opacity: 1; 
    }
  }

  @media (max-width: 992px) {
    .customer-list-wrap { grid-template-columns: 1fr; }
    .history-content {
      position: fixed;
      top: 0; right: -100%;
      width: 90%; max-width: 800px;
      height: 100vh;
      z-index: 1001;
      border-radius: 24px 0 0 24px;
      box-shadow: -10px 0 30px rgba(0,0,0,0.1);
    }
    .history-content.open { right: 0; }
    .btn-close-drawer { display: block !important; }
  }

  .btn-close-drawer {
    display: none;
    background: var(--navy-100);
    border: none;
    width: 36px; height: 36px;
    border-radius: 50%;
    font-size: 20px;
    line-height: 1;
    cursor: pointer;
    transition: all 0.2s;
  }
  .btn-close-drawer:hover { background: var(--navy-200); transform: rotate(90deg); }
  .history-header { margin-bottom: 24px; border-bottom: none; padding-bottom: 0; display: block; }
  .cus-identity-card { 
    background: linear-gradient(135deg, var(--navy-900), var(--navy-800)); 
    border-radius: 16px; 
    padding: 24px; 
    color: white; 
    margin-bottom: 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.2);
  }
  .cus-info-main h2 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
  .cus-info-details { display: flex; gap: 24px; margin-top: 12px; }
  .cus-info-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--navy-300); }
  .cus-info-item .icon { font-size: 16px; opacity: 0.8; }
  
  .trx-card {
    background: white;
    border: 1px solid var(--navy-100);
    border-radius: 16px;
    margin-bottom: 24px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  }
  .trx-card-header {
    background: var(--navy-50);
    padding: 16px 24px;
    border-bottom: 1px solid var(--navy-100);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .trx-card-title { display: flex; align-items: center; gap: 12px; }
  .trx-no-badge { background: var(--teal-100); color: var(--teal-700); font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-family: 'Courier New', monospace; }
  .trx-date { font-size: 13px; color: var(--navy-500); font-weight: 600; }
  
  .trx-meta-grid { 
    display: grid; 
    grid-template-columns: repeat(4, 1fr); 
    gap: 16px; 
    padding: 20px 24px; 
    background: white;
    border-bottom: 1px dashed var(--navy-100);
  }
  .meta-item { display: flex; flex-direction: column; gap: 4px; }
  .meta-item .label { font-size: 10px; font-weight: 700; color: var(--navy-400); text-transform: uppercase; letter-spacing: 0.05em; }
  .meta-item .value { font-size: 13.5px; font-weight: 700; color: var(--navy-800); }
  
  .trx-items-table { width: 100%; border-collapse: collapse; }
  .trx-items-table th { background: var(--navy-50); padding: 12px 24px; text-align: left; font-size: 11px; font-weight: 700; color: var(--navy-500); text-transform: uppercase; border-bottom: 1px solid var(--navy-100); }
  .trx-items-table td { padding: 14px 24px; border-bottom: 1px solid var(--navy-50); font-size: 13.5px; color: var(--navy-700); }
  .trx-items-table tr:last-child td { border-bottom: none; }
  
  .trx-summary-section {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 32px;
    padding: 24px;
    background: var(--navy-50);
  }
  .trx-notes-box { font-size: 12px; color: var(--navy-500); line-height: 1.6; }
  .summary-table-mini { width: 100%; }
  .summary-table-mini tr td { padding: 4px 0; font-size: 13px; color: var(--navy-600); }
  .summary-table-mini tr td:last-child { text-align: right; font-weight: 700; color: var(--navy-800); }
  .summary-table-mini tr.total-row td { padding-top: 12px; border-top: 1.5px solid var(--navy-200); font-size: 15px; color: var(--teal-700); }
  .summary-table-mini tr.highlight-row td:last-child { color: var(--red-600); }
  
  .badge-status-trx { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
  .status-paid { background: var(--em-100); color: var(--em-600); }
  .status-unpaid { background: var(--red-100); color: var(--red-600); }
  .status-partial { background: var(--am-100); color: var(--am-600); }

  @keyframes slideIn {
    to { opacity: 1; transform: translateY(0); }
  }
  .history-table tr:nth-child(1) { animation-delay: 0.05s; }
  .history-table tr:nth-child(2) { animation-delay: 0.1s; }
  .history-table tr:nth-child(3) { animation-delay: 0.15s; }
  .history-table tr:nth-child(4) { animation-delay: 0.2s; }

  /* CICILAN SECTION */
  .cicilan-section { margin-top: 20px; padding-top: 15px; border-top: 1px dashed var(--navy-200); }
  .cicilan-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
  .cicilan-header span { font-size: 11px; font-weight: 800; color: var(--navy-600); text-transform: uppercase; }
  .btn-add-cicilan { background: var(--teal-50); color: var(--teal-600); border: 1px solid var(--teal-100); padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; cursor: pointer; transition: all 0.2s; }
  .btn-add-cicilan:hover { background: var(--teal-100); }
  .cicilan-row { display: flex; gap: 8px; margin-bottom: 8px; align-items: center; animation: fadeIn 0.3s ease; }
  .cicilan-row input { padding: 5px 8px; font-size: 11.5px; border: 1px solid var(--navy-100); border-radius: 4px; width: 120px; }
  .cicilan-row input[type="date"] { width: 130px; }
  .btn-del-cicilan { background: none; border: none; color: var(--red-400); cursor: pointer; font-size: 14px; padding: 0 5px; }
  .btn-del-cicilan:hover { color: var(--red-600); }

  /* OVERRIDE MODAL EXCEL */
  .modal-xl-excel {
    max-width: 96vw !important;
    width: 96vw !important;
    margin: 2vh auto !important;
    display: flex;
    flex-direction: column;
    max-height: 96vh;
  }
  .modal-xl-excel .modal-body {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }
  .modal-xl-excel form {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
  }
  .modal-xl-excel .modal-body-scroll {
    flex: 1;
    overflow: auto;
    padding: 30px;
    background: #f8fafc;
  }
  .mega-row {
    display: flex;
    flex-direction: column;
    gap: 25px;
    width: 100%;
  }
  .input-block {
    flex-shrink: 0;
    width: auto;
    min-width: 400px;
  }

  .excel-input {
    width: 100%;
    border: none;
    padding: 8px 10px;
    font-size: 12px;
    outline: none;
    border-right: 1px solid #e2e8f0;
    transition: background 0.2s;
    background: transparent;
  }
  .ledger-grid.input-mode td {
    padding: 0 !important;
    height: 38px;
    border: 1px solid #e2e8f0;
  }
  .ledger-grid.input-mode .excel-input {
    height: 38px;
    border: none !important;
    background: transparent;
    padding: 0 10px !important;
    width: 100%;
    font-size: 11.5px;
    display: block;
  }
  .ledger-grid.input-mode .excel-input:focus {
    background: #f0f9ff;
    box-shadow: inset 0 0 0 2px #3b82f6;
    outline: none;
  }
  .ledger-grid th {
    position: sticky;
    top: 0;
    z-index: 20;
    background: #f1f5f9;
  }
  .ledger-grid thead tr:first-child th {
    top: 0;
    z-index: 30;
  }
  .ledger-grid thead tr.sub-header th {
    top: 35px;
    z-index: 25;
  }
  .sub-item-row {
    height: 38px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    box-sizing: border-box;
    width: 100%;
  }
  .sub-item-row:last-child {
    border-bottom: none;
  }
  .sub-item-row .excel-input {
    height: 37px !important;
  }
  .btn-mini-add {
    padding: 2px 6px;
    font-size: 10px;
    background: var(--navy-50);
    border: 1px solid var(--navy-200);
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    color: var(--navy-600);
  }
  .btn-mini-add:hover {
    background: var(--navy-100);
  }
</style>
</head>
<body>

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo">
      <div class="brand-icon"><img src="img/logo.png" alt="Logo"></div>
      <div>
        <div class="brand-name">CV. Tidar Teknik</div>
        <div class="brand-sub">Sistem Manajemen Dokumen</div>
      </div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-title">Menu Utama</div>
    <a href="index.php" class="nav-item"><span class="nav-icon">🏠</span> Dashboard</a>
    <a href="po.php"    class="nav-item"><span class="nav-icon">📋</span> Purchase Order</a>
    <a href="nota.php"  class="nav-item"><span class="nav-icon">🧾</span> Nota / Invoice</a>
    <a href="penawaran.php" class="nav-item"><span class="nav-icon">📝</span> Penawaran</a>
    <a href="customer-history.php" class="nav-item active"><span class="nav-icon">👥</span> Riwayat Customer</a>
    <div class="nav-section-title" style="margin-top:8px">Customer Portal</div>
    <a href="admin-requests.php" class="nav-item"><span class="nav-icon">📥</span> Permintaan Barang</a>
    <a href="admin-orders.php" class="nav-item"><span class="nav-icon">📦</span> Pesanan Customer</a>
    <a href="admin-products.php" class="nav-item"><span class="nav-icon">🛍️</span> Katalog Produk</a>
    <div class="nav-section-title" style="margin-top:8px">Pengaturan</div>
    <a href="master.php" class="nav-item"><span class="nav-icon">🏢</span> Master Perusahaan</a>
  </nav>
  <div style="padding:12px 0 0">
    <div style="font-size:11px;color:#94a3b8;margin-bottom:8px;text-align:center">tidarteknik@gmail.com</div>
    <a href="logout.php" style="display:flex;align-items:center;gap:6px;justify-content:center;padding:7px 12px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:8px;color:#fca5a5;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s" onmouseover="this.style.background='rgba(239,68,68,.25)'" onmouseout="this.style.background='rgba(239,68,68,.12)'" id="btn-logout-cus">🚪 Keluar</a>
  </div>
</aside>

<div class="main-wrap">
  <header class="topbar">
    <button class="hamburger-btn" id="hamburger-btn" onclick="toggleSidebar()" aria-label="Buka menu">☰</button>
    <div>
      <div class="topbar-title">Riwayat Customer</div>
      <div class="topbar-subtitle">Pencatatan independen transaksi Penjualan &amp; Pembelian</div>
    </div>
    <div class="topbar-actions">
      <button class="btn btn-primary" onclick="openNewLedger()">➕ Tambah Riwayat</button>
      <label class="btn btn-ghost" style="margin-bottom:0; cursor:pointer">
        📥 Import Excel <input type="file" id="import-ledger-file" hidden accept=".xlsx, .xls">
      </label>
    </div>
  </header>

  <main class="page-content">
    <div id="drawer-overlay" onclick="closeHistoryDrawer()"></div>
    <div class="customer-list-wrap" id="list-wrap-cus">
      <div class="customer-sidebar" id="sidebar-cus">
        <button class="sidebar-toggle-btn" onclick="toggleCustomerSidebar()" title="Toggle Sidebar">☰</button>
        <div class="customer-search-box">
          <input type="text" class="search-input" id="search-customer" placeholder="Cari nama pelanggan...">
        </div>
        <div class="customer-list-container" id="customer-sidebar-list">
          <div style="padding:20px; text-align:center; color:var(--navy-400)">Memuat data...</div>
        </div>
      </div>

      <!-- Detail Riwayat -->
      <div class="history-content" id="history-detail-panel">
        <div class="empty-history">
          <div style="font-size:48px; margin-bottom:16px">👥</div>
          <h3>Pilih Customer</h3>
          <p>Klik salah satu nama customer di sebelah kiri untuk melihat detail riwayat pembelian.</p>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- MODAL LEDGER (MANUAL ENTRY) -->
<div class="modal-overlay" id="modal-ledger">
  <div class="modal modal-xl-excel">
    <div class="modal-header">
      <h2 class="modal-title" id="modal-ledger-title">Tambah Riwayat Transaksi</h2>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body" style="padding:0">
      <form id="ledger-form">
        <input type="hidden" id="ledger-id">
        <div id="ledger-nama-ac" class="autocomplete-list" style="display:none"></div>
        <div class="modal-body-scroll" style="padding:0; overflow:hidden; display:flex; flex-direction:column; height:calc(96vh - 120px)">
          <!-- CONTAINER UTAMA SCROLL -->
          <div style="overflow:auto; flex:1; background:#fff">
            <table class="ledger-grid input-mode" style="width:max-content; border-collapse:collapse">
              <thead style="position:sticky; top:0; z-index:100">
                <tr>
                  <th rowspan="2" style="background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; width:50px; text-align:center">AKSI</th>
                  <th colspan="20" class="th-sales" style="text-align:center; background:#fef3c7; color:#92400e; border:1px solid #fde68a">PENJUALAN</th>
                  <th colspan="11" class="th-purch-start" style="text-align:center; background:#e0f2fe; color:#075985; border:1px solid #bae6fd">PEMBELIAN (AWAL)</th>
                  <th colspan="11" class="th-purch-ppn" style="text-align:center; background:#f3f0ff; color:#5b21b6; border:1px solid #ddd6fe">PEMBELIAN PPN</th>
                  <th colspan="5" class="th-purch-non" style="text-align:center; background:#fff1f2; color:#9f1239; border:1px solid #fecdd3">PEMBELIAN NON PPN</th>
                </tr>
                <tr class="sub-header" style="background:#f8fafc">
                  <!-- Sales (11-29) -->
                  <th>NO</th><th>TGL</th><th>NAMA</th><th>ALAMAT</th><th>TELP</th><th>NO PO</th><th>SERI F.PJK</th><th>BARANG</th><th>TAGIHAN</th><th>QTY</th><th>HRG</th><th>JUMLAH</th><th>PPN</th><th>STH PPN</th><th>BANK</th><th>SELISIH</th><th>TGL BAYAR</th><th>TITIPAN</th><th>UTANG</th><th style="background:#fef3c7 !important">CICILAN</th>
                  <!-- Purch Awal (1-10) -->
                  <th>NO</th><th>TGL</th><th>NAMA</th><th>ALAMAT</th><th>NPWP</th><th>NOTA/SJ</th><th>TGL KIRIM</th><th>SERI F.PJK</th><th>BARANG</th><th>TAGIHAN</th><th style="background:#e0f2fe !important">CICILAN</th>
                  <!-- Purch PPN (30-40) -->
                  <th>QTY</th><th>HRG</th><th>JUMLAH</th><th>PPN 11%</th><th>STH PPN</th><th>BANK</th><th>TUNAI</th><th>TGL</th><th>TITIPAN</th><th>BY ADM</th><th>UTANG</th>
                  <!-- Purch Non (41-45) -->
                  <th>QTY</th><th>JUMLAH</th><th>TOTAL</th><th>BANK</th><th>TUNAI</th>
                </tr>
              </thead>
              <tbody id="items-list-unified">
                <!-- JS injects rows here -->
              </tbody>
            </table>
          </div>
          
          <!-- TOMBOL TAMBAH DI BAWAH -->
          <div style="padding:12px; background:#f8fafc; border-top:1px solid #cbd5e1; display:flex; justify-content:center">
            <button type="button" class="btn btn-sm btn-primary" onclick="addItemRowUnified()" style="padding:8px 25px; font-weight:700">
               + Tambah Baris Baru (Bawah)
            </button>
          </div>
        </div>

        <div class="modal-footer" style="padding:15px 30px; border-top:1px solid #e2e8f0; background:white; display:flex; justify-content:flex-end; gap:12px; border-radius:0 0 16px 16px">
          <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal-ledger').classList.remove('open')">Batal</button>
          <button type="button" class="btn btn-primary" onclick="saveLedger()" style="padding:10px 30px">
            <span style="margin-right:8px">💾</span> Simpan Riwayat
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="js/app.js?v=<?= time() ?>"></script>
<script src="js/customer-history.js?v=<?= time() ?>"></script>
</body>
</html>
