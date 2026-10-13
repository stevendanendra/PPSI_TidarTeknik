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
<title>Penawaran — CV. Tidar Teknik</title>
<style>
.brand-icon img { width:40px; height:40px; object-fit:contain; border-radius:10px; }
.notes-shortcuts { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
.notes-shortcut-badge { background: var(--teal-100); color: var(--teal-700); padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; cursor: pointer; border: 1px solid var(--teal-300); transition: all 0.2s; }
.notes-shortcut-badge:hover { background: var(--teal-500); color: white; }
textarea.desc-multiline { min-height: 60px; font-family: inherit; font-size: 12px; line-height: 1.4; resize: vertical; }
</style>
<meta name="description" content="Kelola Surat Penawaran Harga CV. Tidar Teknik">
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
    <a href="po.php"    class="nav-item"><span class="nav-icon">📋</span> Purchase Order <span class="nav-badge" id="sb-po-count">0</span></a>
    <a href="nota.php"  class="nav-item"><span class="nav-icon">🧾</span> Nota / Invoice <span class="nav-badge" id="sb-nota-count">0</span></a>
    <a href="penawaran.php" class="nav-item active"><span class="nav-icon">📝</span> Penawaran <span class="nav-badge" id="sb-penawaran-count">0</span></a>
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
    <a href="logout.php" style="display:flex;align-items:center;gap:6px;justify-content:center;padding:7px 12px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:8px;color:#ef4444;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s" onmouseover="this.style.background='rgba(239,68,68,.25)'" onmouseout="this.style.background='rgba(239,68,68,.12)'" id="btn-logout-pn">🚪 Keluar</a>
  </div>
</aside>

<!-- MAIN -->
<div class="main-wrap">
  <header class="topbar">
    <button class="hamburger-btn" id="hamburger-btn" onclick="toggleSidebar()" aria-label="Buka menu">☰</button>
    <div>
      <div class="topbar-title">Penawaran Harga</div>
      <div class="topbar-subtitle">Kelola surat penawaran ke calon customer</div>
    </div>
    <div class="topbar-actions">
      <button class="btn btn-ghost" onclick="window.location.href='index.php'" style="margin-right:8px">← Kembali</button>
      <input type="file" id="import-pn-file" accept=".xlsx, .xls, .csv" style="display:none">
      <button class="btn btn-ghost" onclick="document.getElementById('import-pn-file').click()" style="margin-right:8px; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0;">📥 Import Excel</button>
      <button class="btn btn-primary" id="btn-new-pn" style="background:var(--teal-600);border-color:var(--teal-600)">+ Penawaran Baru</button>
    </div>
  </header>

  <main class="page-content">
    <!-- STAT -->
    <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
      <div class="stat-card"><div class="stat-icon blue">📝</div><div><div class="stat-label">Total Penawaran</div><div class="stat-value" id="pn-stat-total">0</div></div></div>
      <div class="stat-card"><div class="stat-icon amber">📅</div><div><div class="stat-label">Bulan Ini</div><div class="stat-value" id="pn-stat-bulan">0</div></div></div>
      <div class="stat-card"><div class="stat-icon green">💰</div><div><div class="stat-label">Total Nilai Penawaran</div><div class="stat-value" id="pn-stat-nilai" style="font-size:15px">Rp 0</div></div></div>
    </div>

    <!-- FILTER -->
    <div class="filter-bar">
      <div class="filter-group">
        <label class="filter-label">Customer</label>
        <select class="form-control form-select" id="filter-customer" style="min-width:220px">
          <option value="">Semua Customer</option>
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
        <label class="filter-label">Cari Nomor Surat</label>
        <input type="text" class="form-control" id="filter-nomor" placeholder="Ketik nomor..." style="width:180px">
      </div>
      <div class="filter-actions">
        <button class="btn btn-primary" id="btn-filter-pn">🔍 Filter</button>
        <button class="btn btn-ghost" id="btn-reset-filter-pn">↺ Reset</button>
      </div>
    </div>

    <!-- TABLE -->
    <div class="card">
      <div class="card-header">
        <span style="font-size:20px">📝</span>
        <div>
          <div class="card-title">Daftar Surat Penawaran</div>
          <div class="card-subtitle" id="pn-count">0 Penawaran</div>
        </div>
      </div>
      <div class="table-wrap" style="border-radius:0;box-shadow:none;border:none;border-top:1px solid var(--navy-200)">
        <table>
          <thead><tr>
            <th>Nomor Surat</th>
            <th class="sortable" data-col="tanggal">Tanggal</th>
            <th>Kepada Yth</th>
            <th>ATT</th>
            <th>Notes Singkat</th>
            <th class="sortable" data-col="total">Total</th>
            <th style="min-width: 150px">Aksi</th>
          </tr></thead>
          <tbody id="pn-tbody">
            <tr><td colspan="7"><div class="empty-state"><div class="empty-icon">📝</div><p>Memuat data...</p></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- MODAL PENAWARAN -->
<div class="modal-overlay" id="modal-pn">
  <div class="modal modal-xl">
    <div class="modal-header">
      <span style="font-size:22px">📝</span>
      <div class="modal-title" id="modal-pn-title">Tambah Penawaran Harga</div>
      <button class="modal-close">✕</button>
    </div>
    <div class="modal-body">
      <form id="pn-form" autocomplete="off">

        <!-- HEADER -->
        <div class="section-divider">📄 Informasi Surat</div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label required">Nomor Surat</label>
            <input type="text" class="form-control" id="pn-nomor" placeholder="Contoh: 2578/SP-JPE/TT/II/25">
            <div style="font-size: 11px; color: var(--teal-600); margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                💡 <a href="#" id="btn-suggest-nomor" style="color: var(--teal-600); text-decoration: underline;">Auto-suggest nomor berikutnya</a>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label required">Tanggal</label>
            <input type="date" class="form-control" id="pn-tanggal">
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label required">Kepada Yth. (Nama Perusahaan)</label>
            <div class="autocomplete-wrap">
              <input type="text" class="form-control" id="pn-customer" placeholder="Nama perusahaan..." autocomplete="off">
              <div class="autocomplete-list" id="pn-customer-ac" style="display:none"></div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Kota / Lokasi</label>
            <input type="text" class="form-control" id="pn-kota" placeholder="Contoh: SEMARANG JAWA TENGAH">
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">ATT (Contact Person)</label>
            <input type="text" class="form-control" id="pn-att" placeholder="Contoh: BAPAK SYAMSUDIN">
          </div>
          <div class="form-group">
            <label class="form-label">Sub Judul Barang (Opsional)</label>
            <input type="text" class="form-control" id="pn-sub-judul" placeholder="Contoh: SPAREPART POMPA FLUGO TYPE FA 150-50">
          </div>
        </div>

        <!-- ITEM TABLE -->
        <div class="section-divider" style="margin-top:4px">📦 Daftar Barang yang Ditawarkan</div>
        <div class="item-table-wrap" style="overflow-x:auto">
          <table>
            <thead><tr>
              <th style="width:36px">#</th>
              <th>Nama Barang / Spesifikasi Lengkap</th>
              <th style="width:70px">Qty</th>
              <th style="width:80px">Type</th>
              <th style="width:120px">Harga (Rp)</th>
              <th style="width:130px">Jumlah (Rp)</th>
              <th style="width:120px">Note Item (Opsional)</th>
              <th style="width:36px"></th>
            </tr></thead>
            <tbody id="pn-items-tbody"></tbody>
          </table>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" id="btn-add-item-pn" style="margin-bottom:16px">+ Tambah Baris</button>

        <!-- SUMMARY & NOTES -->
        <div class="section-divider">📝 Notes & Ringkasan Keuangan</div>
        <div class="form-summary-grid">
          <!-- Left side: Catatan & PPN/Discount/DP -->
          <div>
            <div class="form-group">
              <label class="form-label">Klik untuk tambah shortcut catatan:</label>
              <div class="notes-shortcuts">
                <div class="notes-shortcut-badge" onclick="addNote('BARANG DAN HARGA TIDAK MENGIKAT')">+ Barang & Harga Tdk Mengikat</div>
                <div class="notes-shortcut-badge" onclick="addNote('HARGA INCLUDE PPN')">+ Include PPN</div>
                <div class="notes-shortcut-badge" onclick="addNote('HARGA EXCLUDE PPN')">+ Exclude PPN</div>
                <div class="notes-shortcut-badge" onclick="addNote('READY STOCK')">+ Ready Stock</div>
                <div class="notes-shortcut-badge" onclick="addNote('PAYMENT 30 HARI')">+ Payment 30 Hari</div>
                <div class="notes-shortcut-badge" onclick="addNote('CASH BEFORE DELIVERY (CBD)')">+ CBD</div>
              </div>
              <textarea class="form-control" id="pn-catatan" rows="3" placeholder="Catatan di bawah tabel..."></textarea>
            </div>
            
            <div class="form-group" style="margin-top: 12px;">
              <label class="form-label">Mode PPN</label>
              <div class="ppn-toggle">
                <input type="radio" name="pn-ppn" id="pn-ppn-none" value="none" class="ppn-radio" checked>
                <label for="pn-ppn-none" class="ppn-label">Tidak Ada PPN</label>
                <input type="radio" name="pn-ppn" id="pn-ppn-include" value="include" class="ppn-radio">
                <label for="pn-ppn-include" class="ppn-label">PPN Include</label>
                <input type="radio" name="pn-ppn" id="pn-ppn-exclude" value="exclude" class="ppn-radio">
                <label for="pn-ppn-exclude" class="ppn-label">PPN 11% Terpisah</label>
              </div>
            </div>

            <div class="form-grid" style="margin-top: 12px;">
              <div class="form-group">
                <label class="form-label">Diskon (Rp)</label>
                <input type="text" class="form-control mask-money" id="pn-disc" placeholder="0">
              </div>
              <div class="form-group">
                <label class="form-label">Down Payment / DP (Rp)</label>
                <input type="text" class="form-control mask-money" id="pn-dp" placeholder="0">
              </div>
            </div>
          </div>

          <!-- Right side: Summary -->
          <div class="summary-container" style="display:flex; flex-direction:column; align-items: flex-end; justify-content: flex-start;">
            <div class="form-group" style="margin-bottom: 16px; width: 100%;">
                <label class="form-label" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" id="pn-show-total" checked style="width:16px;height:16px"> Tampilkan Total di Surat Penawaran
                </label>
                <div style="font-size:11px;color:var(--navy-400)">Jika dimatikan, baris TOTAL tidak akan dicetak.</div>
            </div>
            <div class="summary-box" style="width: 100%;">
              <div class="summary-row"><span class="summary-label">Sub Total</span><span class="summary-value" id="pn-sum-subtotal">Rp 0</span></div>
              <div class="summary-row"><span class="summary-label">Diskon</span><span class="summary-value" id="pn-sum-disc">-</span></div>
              <div class="summary-row"><span class="summary-label">Down Payment</span><span class="summary-value" id="pn-sum-dp">-</span></div>
              <div class="summary-row" id="pn-row-dpp" style="display:none"><span class="summary-label" id="pn-sum-dpp-label">DPP</span><span class="summary-value" id="pn-sum-dpp">-</span></div>
              <div class="summary-row"><span class="summary-label" id="pn-sum-ppn-label">PPN</span><span class="summary-value" id="pn-sum-ppn">-</span></div>
              <div class="summary-row total-row"><span class="summary-label">TOTAL</span><span class="summary-value" id="pn-sum-total">Rp 0</span></div>
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="document.getElementById('modal-pn').classList.remove('open')">Batal</button>
      <button class="btn btn-primary" id="btn-save-pn" style="background:var(--teal-600);border-color:var(--teal-600)">💾 Simpan Penawaran</button>
    </div>
  </div>
</div>

<script src="js/app.js?v=<?= time(); ?>"></script>
<script src="js/smart-parser.js?v=<?= time() ?>"></script>
<script src="js/penawaran.js?v=<?= time(); ?>"></script>
<script>
    // Inline script for note shortcuts
    function addNote(text) {
        const textarea = document.getElementById('pn-catatan');
        let current = textarea.value.trim();
        if (current.includes(text)) return; // prevent duplicate
        if (current === '') {
            textarea.value = text;
        } else {
            textarea.value = current + '\n' + text;
        }
    }
</script>
</body>
</html>
