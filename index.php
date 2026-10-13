<?php
// HTTPS Force removed for local dev

require_once 'auth.php';
requireLogin();
include 'db.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — CV. Tidar Teknik</title>
  <meta name="description" content="Sistem Manajemen Dokumen CV. Tidar Teknik — Purchase Order & Invoice">
  <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    .brand-icon img {
      width: 40px;
      height: 40px;
      object-fit: contain;
      border-radius: 10px;
    }

    .stat-card {
      cursor: pointer;
    }

    .stat-card:hover .stat-value {
      color: var(--teal-500);
    }

    .stat-card .stat-arrow {
      font-size: 11px;
      color: var(--navy-400);
      margin-top: 4px;
      transition: color .2s;
    }

    .stat-card:hover .stat-arrow {
      color: var(--teal-500);
    }

    .recent-row {
      cursor: pointer;
    }

    .recent-row:hover {
      background: var(--teal-100) !important;
    }

    .chart-period-btns {
      display: flex;
      gap: 4px;
      margin-left: auto;
    }

    .period-btn {
      padding: 4px 10px;
      border: 1.5px solid var(--navy-200);
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      background: #fff;
      color: var(--navy-500);
      transition: all .15s;
    }

    .period-btn.active {
      background: var(--teal-500);
      border-color: var(--teal-500);
      color: #fff;
    }

    .quick-action-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      margin-bottom: 20px;
    }

    .quick-action-card {
      background: #fff;
      border-radius: var(--radius);
      border: 1.5px solid var(--navy-200);
      padding: 16px;
      text-align: center;
      cursor: pointer;
      transition: all var(--transition);
      text-decoration: none;
      display: block;
    }

    .quick-action-card:hover {
      border-color: var(--teal-500);
      transform: translateY(-2px);
      box-shadow: 0 4px 16px rgba(14, 165, 233, .15);
    }

    .quick-action-card .qa-icon {
      font-size: 28px;
      margin-bottom: 8px;
    }

    .quick-action-card .qa-label {
      font-size: 12.5px;
      font-weight: 700;
      color: var(--navy-700);
    }

    .quick-action-card .qa-sub {
      font-size: 11px;
      color: var(--navy-400);
      margin-top: 2px;
    }

    .row-actions-dash {
      display: flex;
      gap: 4px;
    }

    .row-actions-dash button {
      padding: 3px 8px;
      border-radius: 5px;
      font-size: 11px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all .15s;
    }

    .btn-dash-view {
      background: var(--teal-100);
      color: var(--teal-600);
    }

    .btn-dash-view:hover {
      background: var(--teal-500);
      color: #fff;
    }

    .btn-dash-print {
      background: var(--navy-100);
      color: var(--navy-600);
    }

    .btn-dash-print:hover {
      background: var(--navy-600);
      color: #fff;
    }

    .summary-highlight {
      background: linear-gradient(135deg, #0f172a, #1e293b);
      border-radius: var(--radius);
      padding: 20px;
      color: #fff;
      margin-bottom: 20px;
      display: flex;
      gap: 24px;
      align-items: center;
      flex-wrap: wrap;
    }

    .sh-item {
      flex: 1;
      min-width: 140px;
    }

    .sh-label {
      font-size: 11px;
      color: var(--navy-400);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .06em;
      margin-bottom: 4px;
    }

    .sh-value {
      font-size: 18px;
      font-weight: 800;
      color: #fff;
    }

    .sh-divider {
      width: 1px;
      background: var(--navy-700);
      height: 40px;
    }

    .badge-trend-up {
      background: rgba(16, 185, 129, .2);
      color: #34d399;
      font-size: 10px;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 10px;
      margin-left: 6px;
    }
  </style>
</head>

<body>

  <!-- SIDEBAR OVERLAY -->
  <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

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
      <a href="index.php" class="nav-item active"><span class="nav-icon">🏠</span> Dashboard</a>
      <a href="po.php" class="nav-item"><span class="nav-icon">📋</span> Purchase Order <span class="nav-badge"
          id="sb-po-count">0</span></a>
      <a href="nota.php" class="nav-item"><span class="nav-icon">🧾</span> Nota / Invoice <span class="nav-badge"
          id="sb-nota-count">0</span></a>
      <a href="penawaran.php" class="nav-item"><span class="nav-icon">📝</span> Penawaran <span class="nav-badge"
          id="sb-penawaran-count">0</span></a>
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
      <a href="logout.php"
        style="display:flex;align-items:center;gap:6px;justify-content:center;padding:7px 12px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:8px;color:#fca5a5;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s"
        onmouseover="this.style.background='rgba(239,68,68,.25)'"
        onmouseout="this.style.background='rgba(239,68,68,.12)'" id="btn-logout">🚪 Keluar</a>
    </div>
  </aside>

  <div class="main-wrap">
    <header class="topbar">
      <button class="hamburger-btn" id="hamburger-btn" onclick="toggleSidebar()" aria-label="Buka menu">☰</button>
      <div>
        <div class="topbar-title">Dashboard</div>
        <div class="topbar-subtitle" id="topbar-date">Minggu, 26 April 2026</div>
      </div>
      <div class="topbar-actions">
        <a href="po.php?new=1" class="btn btn-primary" id="btn-new-po-dash">+ PO Baru</a>
        <a href="nota.php?new=1" class="btn btn-success" id="btn-new-nota-dash">+ Nota Baru</a>
      </div>
    </header>

    <main class="page-content">

      <!-- SUMMARY HIGHLIGHT BAR -->
      <div class="summary-highlight">
        <div class="sh-item">
          <div class="sh-label">Total Pembelian (All)</div>
          <div class="sh-value" id="sh-po-all">Rp 0</div>
        </div>
        <div class="sh-divider"></div>
        <div class="sh-item">
          <div class="sh-label">Total Penjualan (All)</div>
          <div class="sh-value" id="sh-nota-all">Rp 0</div>
        </div>
        <div class="sh-divider"></div>
        <div class="sh-item">
          <div class="sh-label">Bulan Ini — PO</div>
          <div class="sh-value" id="sh-po-bulan">Rp 0</div>
        </div>
        <div class="sh-divider"></div>
        <div class="sh-item">
          <div class="sh-label">Bulan Ini — Nota</div>
          <div class="sh-value" id="sh-nota-bulan">Rp 0</div>
        </div>
        <div style="margin-left:auto;text-align:right">
          <div class="sh-label">Status Sistem</div>
          <div class="sh-value"
            style="font-size:14px;color:<?php echo $conn ? 'var(--teal-500)' : 'var(--red-500)'; ?>">●
            <?php echo $conn ? 'Online & Terhubung ke Database' : 'Koneksi Database Gagal'; ?></div>
        </div>
      </div>

      <!-- OVERDUE ALERT -->
      <div id="overdue-alert" class="card"
        style="display:none;background:#fee2e2;border:1px solid #ef4444;margin-bottom:16px;padding:16px;flex-direction:row;align-items:center;gap:16px">
        <div style="font-size:32px">⚠️</div>
        <div style="flex:1">
          <div style="font-weight:700;color:#991b1b;font-size:16px">Perhatian: Ada Dokumen Jatuh Tempo!</div>
          <div style="color:#b91c1c;font-size:14px">Terdapat <strong id="overdue-count-nota">0</strong> Nota dan <strong
              id="overdue-count-po">0</strong> PO yang telah melewati batas termin.</div>
        </div>
        <div style="display:flex;gap:8px">
          <a href="nota.php" class="btn btn-danger btn-sm">Cek Nota</a>
          <a href="po.php" class="btn btn-warning btn-sm"
            style="background:#f59e0b;border-color:#d97706;color:white">Cek PO</a>
        </div>
      </div>

      <!-- SEMENTARA ALERT -->
      <div id="sementara-alert-dash" class="card"
        style="display:none;background:#fffbeb;border:1px solid #f59e0b;margin-bottom:24px;padding:16px;flex-direction:row;align-items:center;gap:16px">
        <div style="font-size:32px">📝</div>
        <div style="flex:1">
          <div style="font-weight:700;color:#92400e;font-size:16px">Perhatian: Ada Nota Belum Fix (Sementara)!</div>
          <div style="color:#b45309;font-size:14px">Terdapat <strong id="sementara-count-dash">0</strong> nota berstatus
            Sementara yang sudah lebih dari 1 minggu. Segera hubungi customer.</div>
        </div>
        <div style="display:flex;gap:8px">
          <a href="nota.php?filter_status=Sementara" class="btn btn-warning btn-sm"
            style="background:#f59e0b;border-color:#d97706;color:white">Cek Nota Sementara</a>
        </div>
      </div>

      <!-- QUICK ACTIONS -->
      <div class="quick-action-grid">
        <a href="po.php?new=1" class="quick-action-card">
          <div class="qa-icon">📋</div>
          <div class="qa-label">Buat PO Baru</div>
          <div class="qa-sub">Purchase Order</div>
        </a>
        <a href="nota.php?new=1" class="quick-action-card">
          <div class="qa-icon">🧾</div>
          <div class="qa-label">Buat Nota Baru</div>
          <div class="qa-sub">Invoice / Surat Jalan</div>
        </a>
        <a href="po.php" class="quick-action-card">
          <div class="qa-icon">🔍</div>
          <div class="qa-label">Cari PO</div>
          <div class="qa-sub">Filter & Pencarian</div>
        </a>
        <a href="master.php" class="quick-action-card">
          <div class="qa-icon">🏢</div>
          <div class="qa-label">Master Data</div>
          <div class="qa-sub">Supplier & Customer</div>
        </a>
      </div>

      <!-- STAT CARDS -->
      <div class="stat-grid" style="margin-bottom:20px">
        <div class="stat-card" id="card-total-po" onclick="window.location='po.php'" title="Klik untuk lihat semua PO">
          <div class="stat-icon blue">📋</div>
          <div>
            <div class="stat-label">Total PO</div>
            <div class="stat-value" id="dash-total-po">0</div>
            <div class="stat-sub" id="dash-po-bulan">0 bulan ini</div>
            <div class="stat-arrow">→ Lihat semua PO</div>
          </div>
        </div>
        <div class="stat-card" id="card-total-nota" onclick="window.location='nota.php'"
          title="Klik untuk lihat semua Nota">
          <div class="stat-icon green">🧾</div>
          <div>
            <div class="stat-label">Total Nota</div>
            <div class="stat-value" id="dash-total-nota">0</div>
            <div class="stat-sub" id="dash-nota-bulan">0 bulan ini</div>
            <div class="stat-arrow">→ Lihat semua Nota</div>
          </div>
        </div>
        <div class="stat-card" onclick="window.location='po.php'" title="Klik untuk lihat PO bulan ini">
          <div class="stat-icon amber">💰</div>
          <div>
            <div class="stat-label">Nilai PO Bulan Ini</div>
            <div class="stat-value" id="dash-nilai-po" style="font-size:14px">Rp 0</div>
            <div class="stat-sub">Pembelian</div>
            <div class="stat-arrow">→ Filter bulan ini</div>
          </div>
        </div>
        <div class="stat-card" onclick="window.location='nota.php'" title="Klik untuk lihat Nota bulan ini">
          <div class="stat-icon" style="background:var(--em-100)">📈</div>
          <div>
            <div class="stat-label">Nilai Nota Bulan Ini</div>
            <div class="stat-value" id="dash-nilai-nota" style="font-size:14px">Rp 0</div>
            <div class="stat-sub">Penjualan</div>
            <div class="stat-arrow">→ Filter bulan ini</div>
          </div>
        </div>
      </div>

      <!-- CHARTS -->
      <div class="dash-grid-2" style="margin-bottom:20px">
        <div class="card">
          <div class="card-header">
            <span style="font-size:20px">📋</span>
            <div>
              <div class="card-title">Purchase Order per Bulan</div>
              <div class="card-subtitle" id="chart-po-sub">6 bulan terakhir</div>
            </div>
            <div class="chart-period-btns">
              <button class="period-btn" data-chart="po" data-n="3">3 Bln</button>
              <button class="period-btn active" data-chart="po" data-n="6">6 Bln</button>
              <button class="period-btn" data-chart="po" data-n="12">12 Bln</button>
            </div>
          </div>
          <div class="card-body">
            <div class="chart-wrap"><canvas id="chart-po"></canvas></div>
          </div>
        </div>
        <div class="card">
          <div class="card-header">
            <span style="font-size:20px">🧾</span>
            <div>
              <div class="card-title">Nilai Nota per Bulan</div>
              <div class="card-subtitle" id="chart-nota-sub">6 bulan terakhir (juta Rp)</div>
            </div>
            <div class="chart-period-btns">
              <button class="period-btn" data-chart="nota" data-n="3">3 Bln</button>
              <button class="period-btn active" data-chart="nota" data-n="6">6 Bln</button>
              <button class="period-btn" data-chart="nota" data-n="12">12 Bln</button>
            </div>
          </div>
          <div class="card-body">
            <div class="chart-wrap"><canvas id="chart-nota"></canvas></div>
          </div>
        </div>
      </div>

      <!-- RECENT TABLES -->
      <div class="dash-grid-2">
        <!-- RECENT PO -->
        <div class="card">
          <div class="card-header">
            <span style="font-size:20px">🕒</span>
            <div class="card-title">PO Terbaru</div>
            <a href="po.php" class="btn btn-ghost btn-sm" style="margin-left:auto">Lihat Semua →</a>
          </div>
          <div class="table-wrap"
            style="border-radius:0;box-shadow:none;border:none;border-top:1px solid var(--navy-200)">
            <table>
              <thead>
                <tr>
                  <th>Nomor PO</th>
                  <th>Supplier</th>
                  <th>Total</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="recent-po-tbody"></tbody>
            </table>
          </div>
        </div>
        <!-- RECENT NOTA -->
        <div class="card">
          <div class="card-header">
            <span style="font-size:20px">🕒</span>
            <div class="card-title">Nota Terbaru</div>
            <a href="nota.php" class="btn btn-ghost btn-sm" style="margin-left:auto">Lihat Semua →</a>
          </div>
          <div class="table-wrap"
            style="border-radius:0;box-shadow:none;border:none;border-top:1px solid var(--navy-200)">
            <table>
              <thead>
                <tr>
                  <th>Nomor Nota</th>
                  <th>Customer</th>
                  <th>Grand Total</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="recent-nota-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>

    </main>
  </div>

  <script src="js/app.js?v=<?= time() ?>"></script>
  <script>
    document.addEventListener('DOMContentLoaded', async () => {
      await syncLocalToDB();
      await updateSidebarBadges();

      const poList = await dbStore.get(KEYS.PO_LIST);
      const notaList = await dbStore.get(KEYS.NOTA_LIST);
      const now = new Date();

      // Topbar date
      const hariNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
      const bulanNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
      document.getElementById('topbar-date').textContent =
        `${hariNames[now.getDay()]}, ${now.getDate()} ${bulanNames[now.getMonth()]} ${now.getFullYear()}`;

      // Removed redundant sh-periode update as it's not in the HTML anymore

      function getPoTotal(p) { return calcPOTotals(p.items || [], p.disc || 0, p.dp || 0, p.ppnMode || 'none').total; }
      function getNotaGT(n) { return calcNotaTotals(n.items || [], n.disc || 0, n.dp || 0, n.ppnMode || 'none').grandTotal; }
      const thisM = d => { const dt = new Date(d); return dt.getMonth() === now.getMonth() && dt.getFullYear() === now.getFullYear(); };

      // Summary highlight (Exclude 'Sementara')
      document.getElementById('sh-po-all').textContent = fmt.rupiah(poList.reduce((s, p) => s + getPoTotal(p), 0));
      document.getElementById('sh-nota-all').textContent = fmt.rupiah(notaList.filter(n => n.status !== 'Sementara').reduce((s, n) => s + getNotaGT(n), 0));
      document.getElementById('sh-po-bulan').textContent = fmt.rupiah(poList.filter(p => thisM(p.tanggal)).reduce((s, p) => s + getPoTotal(p), 0));
      document.getElementById('sh-nota-bulan').textContent = fmt.rupiah(notaList.filter(n => n.status !== 'Sementara' && thisM(n.tanggal)).reduce((s, n) => s + getNotaGT(n), 0));

      // Stat cards (Exclude 'Sementara')
      document.getElementById('dash-total-po').textContent = poList.length;
      document.getElementById('dash-total-nota').textContent = notaList.filter(n => n.status !== 'Sementara').length;
      document.getElementById('dash-po-bulan').textContent = poList.filter(p => thisM(p.tanggal)).length + ' bulan ini';
      document.getElementById('dash-nota-bulan').textContent = notaList.filter(n => n.status !== 'Sementara' && thisM(n.tanggal)).length + ' bulan ini';
      document.getElementById('dash-nilai-po').textContent = fmt.rupiah(poList.filter(p => thisM(p.tanggal)).reduce((s, p) => s + getPoTotal(p), 0));
      document.getElementById('dash-nilai-nota').textContent = fmt.rupiah(notaList.filter(n => n.status !== 'Sementara' && thisM(n.tanggal)).reduce((s, n) => s + getNotaGT(n), 0));

      // Overdue Alert
      const today = new Date(); today.setHours(0, 0, 0, 0);
      const overdueList = notaList.filter(n => {
        if (n.status === 'Lunas') return false;
        const termin = parseInt(n.termin) || 30;
        const d = new Date(n.tanggal);
        d.setDate(d.getDate() + termin);
        return today > d;
      });

      const ovAlert = document.getElementById('overdue-alert');
      const overduePO = poList.filter(p => {
        if (p.status === 'Lunas') return false;
        const term = parseInt(p.termin) || 30;
        const d = new Date(p.tanggal);
        d.setDate(d.getDate() + term);
        return today > d;
      });

      if (overdueList.length > 0 || overduePO.length > 0) {
        ovAlert.style.display = 'flex';
        document.getElementById('overdue-count-nota').textContent = overdueList.length;
        document.getElementById('overdue-count-po').textContent = overduePO.length;
      } else {
        ovAlert.style.display = 'none';
      }

      // Sementara Alert Logic
      const semListOld = notaList.filter(n => {
        if (n.status !== 'Sementara') return false;
        const diff = Math.abs(today - new Date(n.tanggal));
        return diff >= (7 * 24 * 60 * 60 * 1000); // 7 days
      });

      const semAlertDash = document.getElementById('sementara-alert-dash');
      const semCountDash = document.getElementById('sementara-count-dash');
      if (semAlertDash && semListOld.length > 0) {
        semAlertDash.style.display = 'flex';
        if (semCountDash) semCountDash.textContent = semListOld.length;
      } else if (semAlertDash) {
        semAlertDash.style.display = 'none';
      }

      // ===== RECENT PO TABLE =====
      const recentPO = [...poList].sort((a, b) => new Date(b.tanggal) - new Date(a.tanggal)).slice(0, 5);
      const ptbody = document.getElementById('recent-po-tbody');
      if (!recentPO.length) {
        ptbody.innerHTML = `<tr><td colspan="4"><div class="empty-state" style="padding:20px"><div class="empty-icon" style="font-size:28px">📋</div><p>Belum ada PO. <a href="po.php?new=1" style="color:var(--teal-500)">Buat PO Baru →</a></p></div></td></tr>`;
      } else {
        ptbody.innerHTML = recentPO.map(p => `<tr class="recent-row">
      <td class="td-mono" style="font-size:11.5px">${p.nomor}</td>
      <td style="font-size:12px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${p.supplier}</td>
      <td class="td-amount">${fmt.rupiah(getPoTotal(p))}</td>
      <td>
        <div class="row-actions-dash">
          <button class="btn-dash-view" onclick="event.stopPropagation();window.location='po.php'" title="Edit PO">✏️ Edit</button>
          <button class="btn-dash-print" onclick="event.stopPropagation();window.open('print-po.php?id=${p.id}','_blank')" title="Cetak PO">🖨️</button>
        </div>
      </td>
    </tr>`).join('');
        // Row click → go to PO page
        ptbody.querySelectorAll('.recent-row').forEach((row, i) => {
          row.addEventListener('click', () => window.location = 'po.php');
        });
      }

      // ===== RECENT NOTA TABLE =====
      const recentNota = [...notaList].sort((a, b) => new Date(b.tanggal) - new Date(a.tanggal)).slice(0, 5);
      const ntbody = document.getElementById('recent-nota-tbody');
      if (!recentNota.length) {
        ntbody.innerHTML = `<tr><td colspan="4"><div class="empty-state" style="padding:20px"><div class="empty-icon" style="font-size:28px">🧾</div><p>Belum ada Nota. <a href="nota.php?new=1" style="color:var(--teal-500)">Buat Nota Baru →</a></p></div></td></tr>`;
      } else {
        ntbody.innerHTML = recentNota.map(n => `<tr class="recent-row">
      <td class="td-mono" style="font-size:11px">${n.nomor}</td>
      <td style="font-size:12px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${n.customer}</td>
      <td class="td-amount">${fmt.rupiah(getNotaGT(n))}</td>
      <td>
        <div class="row-actions-dash">
          <button class="btn-dash-view" onclick="event.stopPropagation();window.location='nota.php'" title="Edit Nota">✏️ Edit</button>
          <button class="btn-dash-print" onclick="event.stopPropagation();window.open('print-nota.php?id=${n.id}&mode=invoice','_blank')" title="Cetak Invoice">🖨️</button>
        </div>
      </td>
    </tr>`).join('');
        ntbody.querySelectorAll('.recent-row').forEach((row) => {
          row.addEventListener('click', () => window.location = 'nota.php');
        });
      }

      // ===== CHARTS =====
      const bulanShort = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];

      // Akhir rentang = bulan ini; bila tidak ada data dalam rentang itu (data lama),
      // geser ke bulan data terbaru supaya grafik tidak kosong.
      const chartEnd = (() => {
        const dates = [...poList, ...notaList].map(x => new Date(x.tanggal)).filter(d => !isNaN(d));
        if (!dates.length) return new Date(now.getFullYear(), now.getMonth(), 1);
        const latest = new Date(Math.max(...dates));
        const cur = new Date(now.getFullYear(), now.getMonth(), 1);
        const latestM = new Date(latest.getFullYear(), latest.getMonth(), 1);
        const windowStart = new Date(cur.getFullYear(), cur.getMonth() - 5, 1);
        return latestM < windowStart ? latestM : cur;
      })();

      function buildMonths(n) {
        const arr = [];
        for (let i = n - 1; i >= 0; i--) {
          const d = new Date(chartEnd.getFullYear(), chartEnd.getMonth() - i, 1);
          arr.push({ label: bulanShort[d.getMonth()] + "'" + (d.getFullYear() % 100), month: d.getMonth(), year: d.getFullYear() });
        }
        return arr;
      }

      let months = buildMonths(6);
      const chartDefaults = {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: false }, tooltip: {
            callbacks: {
              label: ctx => ctx.dataset.label === 'Nota'
                ? 'Rp ' + ctx.raw.toFixed(2) + 'jt'
                : ctx.raw + ' PO'
            }
          }
        }
      };

      let chartPO = new Chart(document.getElementById('chart-po'), {
        type: 'bar',
        data: {
          labels: months.map(m => m.label), datasets: [{
            label: 'PO',
            data: months.map(m => poList.filter(p => { const d = new Date(p.tanggal); return d.getMonth() === m.month && d.getFullYear() === m.year; }).length),
            backgroundColor: 'rgba(14,165,233,.7)', borderColor: '#0ea5e9', borderWidth: 2, borderRadius: 6
          }]
        },
        options: { ...chartDefaults, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,.05)' } }, x: { grid: { display: false } } } }
      });

      let chartNota = new Chart(document.getElementById('chart-nota'), {
        type: 'line',
        data: {
          labels: months.map(m => m.label), datasets: [{
            label: 'Nota',
            data: months.map(m => notaList.filter(n => { const d = new Date(n.tanggal); return n.status !== 'Sementara' && d.getMonth() === m.month && d.getFullYear() === m.year; }).reduce((s, n) => s + getNotaGT(n), 0) / 1000000),
            fill: true, backgroundColor: 'rgba(16,185,129,.12)', borderColor: '#10b981', borderWidth: 2.5,
            pointBackgroundColor: '#10b981', pointRadius: 4, tension: .4
          }]
        },
        options: {
          ...chartDefaults, scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { callback: v => 'Rp ' + v.toFixed(1) + 'jt' } },
            x: { grid: { display: false } }
          }
        }
      });

      // Period buttons
      document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          const which = btn.dataset.chart;
          const n = parseInt(btn.dataset.n);
          document.querySelectorAll(`.period-btn[data-chart="${which}"]`).forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          const newMonths = buildMonths(n);
          const labels = newMonths.map(m => m.label);
          if (which === 'po') {
            document.getElementById('chart-po-sub').textContent = n + ' bulan terakhir';
            chartPO.data.labels = labels;
            chartPO.data.datasets[0].data = newMonths.map(m =>
              poList.filter(p => { const d = new Date(p.tanggal); return d.getMonth() === m.month && d.getFullYear() === m.year; }).length
            );
            chartPO.update();
          } else {
            document.getElementById('chart-nota-sub').textContent = n + ' bulan terakhir (juta Rp)';
            chartNota.data.labels = labels;
            chartNota.data.datasets[0].data = newMonths.map(m =>
              notaList.filter(nn => { const d = new Date(nn.tanggal); return nn.status !== 'Sementara' && d.getMonth() === m.month && d.getFullYear() === m.year; }).reduce((s, nn) => s + getNotaGT(nn), 0) / 1000000
            );
            chartNota.update();
          }
        });
      });

      // Handle ?new=1 on po/nota pages
      // (handled on respective pages - just navigating with param)
    });
  </script>
</body>

</html>