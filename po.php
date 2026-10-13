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
<title>Purchase Order — CV. Tidar Teknik</title>
<style>
.brand-icon img { width:40px; height:40px; object-fit:contain; border-radius:10px; }
</style>
<meta name="description" content="Kelola Purchase Order CV. Tidar Teknik">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
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
    <a href="po.php"    class="nav-item active"><span class="nav-icon">📋</span> Purchase Order <span class="nav-badge" id="sb-po-count">0</span></a>
    <a href="nota.php"  class="nav-item"><span class="nav-icon">🧾</span> Nota / Invoice <span class="nav-badge" id="sb-nota-count">0</span></a>
    <a href="penawaran.php" class="nav-item"><span class="nav-icon">📝</span> Penawaran <span class="nav-badge" id="sb-penawaran-count">0</span></a>
    <a href="customer-history.php" class="nav-item"><span class="nav-icon">👥</span> Riwayat Customer</a>
    <div class="nav-section-title" style="margin-top:8px">Customer Portal</div>
    <a href="admin-requests.php" class="nav-item"><span class="nav-icon">📥</span> Permintaan Barang</a>
    <a href="admin-orders.php" class="nav-item"><span class="nav-icon">📦</span> Pesanan Customer</a>
    <a href="admin-products.php" class="nav-item"><span class="nav-icon">🛍️</span> Katalog Produk</a>
    <div class="nav-section-title" style="margin-top:8px">Pengaturan</div>
    <a href="master.php" class="nav-item"><span class="nav-icon">🏢</span> Master Perusahaan</a>
  </nav>
  <div class="sidebar-footer">
    <div style="margin-bottom:8px">📞 0822 2525 5251 &nbsp;|&nbsp; tidarteknik@gmail.com</div>
    <a href="logout.php" style="display:flex;align-items:center;gap:6px;justify-content:center;padding:7px 12px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:8px;color:#ef4444;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s" onmouseover="this.style.background='rgba(239,68,68,.25)'" onmouseout="this.style.background='rgba(239,68,68,.12)'" id="btn-logout-po">🚪 Keluar</a>
  </div>
</aside>

<!-- MAIN -->
<div class="main-wrap">
  <header class="topbar">
    <button class="hamburger-btn" id="hamburger-btn" onclick="toggleSidebar()" aria-label="Buka menu">☰</button>
    <div>
      <div class="topbar-title">Purchase Order</div>
      <div class="topbar-subtitle">Kelola pembelian dari supplier</div>
    </div>
    <div class="topbar-actions">
      <button class="btn btn-ghost" onclick="window.location.href='index.php'" style="margin-right:8px">← Kembali</button>
      <input type="file" id="import-po-file" accept=".xlsx, .xls, .csv" style="display:none">
      <button class="btn btn-ghost" onclick="document.getElementById('import-po-file').click()" style="margin-right:8px; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0;">📥 Import Excel</button>
      <button class="btn btn-primary" id="btn-new-po">+ PO Baru</button>
    </div>
  </header>

  <main class="page-content">
    <!-- PAGE OVERDUE ALERT -->
    <div id="overdue-page-alert" class="card" style="display:none;background:#fee2e2;border:1px solid #ef4444;margin-bottom:16px;padding:12px;flex-direction:row;align-items:center;gap:12px">
      <div style="font-size:24px">⚠️</div>
      <div style="flex:1">
        <div style="font-weight:700;color:#991b1b;font-size:14px">Perhatian: Ada Dokumen Jatuh Tempo!</div>
        <div style="color:#b91c1c;font-size:13px">Terdapat <strong id="overdue-page-count">0</strong> dokumen yang telah melewati batas termin pembayaran.</div>
      </div>
      <button class="btn btn-danger btn-sm" onclick="document.getElementById('filter-status-po').value='Overdue';applyPOFilter();">Tampilkan Saja</button>
    </div>

    <!-- STAT -->
    <div class="stat-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:16px">
      <div class="stat-card"><div class="stat-icon blue">📋</div><div><div class="stat-label">Total PO</div><div class="stat-value" id="po-stat-total">0</div></div></div>
      <div class="stat-card"><div class="stat-icon amber">📅</div><div><div class="stat-label">Bulan Ini</div><div class="stat-value" id="po-stat-bulan">0</div></div></div>
      <div class="stat-card" style="grid-column:span 2"><div class="stat-icon green">💰</div><div><div class="stat-label">Total Nilai PO Bulan Ini</div><div class="stat-value" id="po-stat-nilai-bulan" style="font-size:15px">Rp 0</div><div class="stat-sub">Keseluruhan: <span id="po-stat-nilai">Rp 0</span></div></div></div>
    </div>

    <!-- FILTER -->
    <div class="filter-bar">
      <div class="filter-group">
        <label class="filter-label">Supplier</label>
        <select class="form-control form-select" id="filter-supplier" style="min-width:200px">
          <option value="">Semua Supplier</option>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Tahun</label>
        <select class="form-control form-select" id="filter-year" style="width:110px">
          <option value="">Semua Tahun</option>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Bulan</label>
        <select class="form-control form-select" id="filter-month" style="width:130px">
          <option value="">Semua Bulan</option>
          <option value="1">Januari</option><option value="2">Februari</option>
          <option value="3">Maret</option><option value="4">April</option>
          <option value="5">Mei</option><option value="6">Juni</option>
          <option value="7">Juli</option><option value="8">Agustus</option>
          <option value="9">September</option><option value="10">Oktober</option>
          <option value="11">November</option><option value="12">Desember</option>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Cari Nomor PO</label>
        <input type="text" class="form-control" id="filter-nomor-po" placeholder="Ketik nomor PO..." style="width:180px">
      </div>
      <div class="filter-group">
        <label class="filter-label">Status</label>
        <select class="form-control form-select" id="filter-status-po" style="width:140px">
          <option value="">Semua Status</option>
          <option value="Belum Bayar">⏳ Belum Bayar</option>
          <option value="Lunas">✅ Lunas</option>
          <option value="Overdue">⚠️ Jatuh Tempo</option>
        </select>
      </div>
      <div class="filter-actions">
        <button class="btn btn-primary" id="btn-filter-po">🔍 Filter</button>
        <button class="btn btn-ghost" id="btn-reset-filter-po">↺ Reset</button>
      </div>
    </div>

    <!-- TABLE -->
    <div class="card">
      <div class="card-header">
        <span style="font-size:20px">📋</span>
        <div>
          <div class="card-title">Daftar Purchase Order</div>
          <div class="card-subtitle" id="po-count">0 PO</div>
        </div>
      </div>
      <div class="table-wrap" style="border-radius:0;box-shadow:none;border:none;border-top:1px solid var(--navy-200)">
        <table>
          <thead><tr>
            <th>Nomor PO</th>
            <th class="sortable" data-col="tanggal">Tanggal / Due</th>
            <th>Supplier / Kota</th>
            <th>Payment / Note</th>
            <th>PPN</th>
            <th>Status</th>
            <th class="sortable" data-col="total">Total</th>
            <th>Aksi</th>
          </tr></thead>
          <tbody id="po-tbody">
            <tr><td colspan="7"><div class="empty-state"><div class="empty-icon">📋</div><p>Memuat data...</p></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- MODAL PO -->
<div class="modal-overlay" id="modal-po">
  <div class="modal modal-xl">
    <div class="modal-header">
      <span style="font-size:22px">📋</span>
      <div class="modal-title" id="modal-po-title">Tambah Purchase Order</div>
      <button class="modal-close">✕</button>
    </div>
    <div class="modal-body">
      <form id="po-form" autocomplete="off">

        <!-- HEADER -->
        <div class="section-divider">📄 Informasi Dokumen</div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label required">Nomor PO</label>
            <input type="text" class="form-control" id="po-nomor" placeholder="Contoh: 4030/TT-MTE/IV/26">
            <div style="font-size: 11px; color: var(--teal-600); margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                💡 <a href="#" id="btn-suggest-po" style="color: var(--teal-600); text-decoration: underline;">Auto-suggest nomor berikutnya</a>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label required">Tanggal</label>
            <input type="date" class="form-control" id="po-tanggal">
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label required">Kepada Yth. (Nama Supplier)</label>
            <div class="autocomplete-wrap">
              <input type="text" class="form-control" id="po-supplier" placeholder="Nama supplier..." autocomplete="off">
              <div class="autocomplete-list" id="po-supplier-ac" style="display:none"></div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Kota</label>
            <input type="text" class="form-control" id="po-kota" placeholder="Contoh: SURABAYA">
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Note 1 (Payment Terms)</label>
            <input type="text" class="form-control" id="po-note1" placeholder="Contoh: PAYMENT 30HARI">
          </div>
          <div class="form-group">
            <label class="form-label">Note 2</label>
            <input type="text" class="form-control" id="po-note2" placeholder="Contoh: INCLUDE PPN">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Alamat Kirim (Opsional)</label>
          <textarea class="form-control" id="po-alamat-kirim" rows="2" placeholder="Opsional — kosongkan jika tidak ada alamat kirim khusus"></textarea>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Termin Pembayaran (Hari)</label>
            <input type="number" class="form-control" id="po-termin" placeholder="30" value="30">
          </div>
          <div class="form-group">
            <label class="form-label">Status Pembayaran</label>
            <select class="form-control form-select" id="po-status">
              <option value="Belum Bayar">⏳ Belum Bayar</option>
              <option value="Lunas">✅ Lunas</option>
            </select>
          </div>
        </div>

        <!-- ITEM TABLE -->
        <div class="section-divider" style="margin-top:4px">📦 Daftar Item</div>
        <div class="item-table-wrap" style="overflow-x:auto">
          <table>
            <thead><tr>
              <th style="width:36px">#</th>
              <th>Description / Specification</th>
              <th style="width:90px">Qty</th>
              <th style="width:90px">Type</th>
              <th style="width:120px">Price (Rp)</th>
              <th style="width:130px">Amount (Rp)</th>
              <th style="width:36px"></th>
            </tr></thead>
            <tbody id="po-items-tbody"></tbody>
          </table>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" id="btn-add-item-po" style="margin-bottom:16px">+ Tambah Baris</button>

        <!-- SUMMARY -->
        <div class="section-divider">💰 Ringkasan Keuangan</div>
        <div class="form-summary-grid">
          <!-- PPN Options -->
          <div>
            <div class="form-group">
              <label class="form-label">Mode PPN</label>
              <div class="ppn-toggle">
                <input type="radio" name="po-ppn" id="po-ppn-none" value="none" class="ppn-radio" checked>
                <label for="po-ppn-none" class="ppn-label">Tidak Ada PPN</label>
                <input type="radio" name="po-ppn" id="po-ppn-include" value="include" class="ppn-radio">
                <label for="po-ppn-include" class="ppn-label">PPN Include</label>
                <input type="radio" name="po-ppn" id="po-ppn-exclude" value="exclude" class="ppn-radio">
                <label for="po-ppn-exclude" class="ppn-label">PPN 11% Terpisah</label>
              </div>
            </div>
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Diskon (Rp)</label>
                <input type="text" class="form-control mask-money" id="po-disc" placeholder="0">
              </div>
              <div class="form-group">
                <label class="form-label">Down Payment / DP (Rp)</label>
                <input type="text" class="form-control mask-money" id="po-dp" placeholder="0">
              </div>
            </div>
          </div>
          <!-- Summary -->
          <div class="summary-container" style="display:flex; justify-content: flex-end; margin-top:10px">
            <!-- Summary box -->
            <div class="summary-box">
              <div class="summary-row"><span class="summary-label">Sub Total</span><span class="summary-value" id="po-sum-subtotal">Rp 0</span></div>
              <div class="summary-row"><span class="summary-label">Diskon</span><span class="summary-value" id="po-sum-disc">-</span></div>
              <div class="summary-row"><span class="summary-label">Down Payment</span><span class="summary-value" id="po-sum-dp">-</span></div>
              <div class="summary-row"><span class="summary-label">PPN 11%</span><span class="summary-value" id="po-sum-ppn">-</span></div>
              <div class="summary-row total-row"><span class="summary-label">TOTAL</span><span class="summary-value" id="po-sum-total">Rp 0</span></div>
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="document.getElementById('modal-po').classList.remove('open')">Batal</button>
      <button class="btn btn-primary" id="btn-save-po">💾 Simpan PO</button>
    </div>
  </div>
</div>

<script src="js/app.js?v=<?= time() ?>"></script>
<script src="js/smart-parser.js?v=<?= time() ?>"></script>
<script src="js/po.js?v=<?= time() ?>"></script>
</body>
</html>
