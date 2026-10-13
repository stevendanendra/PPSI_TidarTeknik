<?php
/**
 * admin-orders.php — Manajemen Pesanan Customer & Pengiriman (Fase 2)
 * CV. Tidar Teknik (Panel Internal Admin)
 */

require_once 'auth.php';
requireLogin();
require_once 'lib.php';

$success = '';
$error = '';

// Filter status
$filterStatus = trim($_GET['status'] ?? '');
$where = ["1=1"];
$params = [];
if (in_array($filterStatus, ['pending', 'diproses', 'dikirim', 'selesai', 'dibatalkan'], true)) {
    $where[] = "o.status_pesanan = ?";
    $params[] = $filterStatus;
}
$whereSql = implode(" AND ", $where);

// Ambil orders
$orders = dbRows("SELECT o.*, c.nama AS nama_customer, c.telepon AS telp_customer, c.email AS email_customer
                  FROM orders o
                  JOIN customers c ON o.customer_id = c.id
                  WHERE $whereSql
                  ORDER BY o.created_at DESC", $params);

// Load items per order
foreach ($orders as &$ord) {
    $ord['items'] = dbRows("SELECT * FROM order_items WHERE order_id = ?", [$ord['id']]);
}
unset($ord);

// Hitung request pending untuk badge
$totalPending = (int)dbRow("SELECT COUNT(*) AS c FROM product_requests WHERE status = 'pending'")['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pesanan Customer — CV. Tidar Teknik</title>
  <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
  <style>
    .brand-icon img { width:40px; height:40px; object-fit:contain; border-radius:10px; }
  </style>
</head>
<body>

  <!-- SIDEBAR OVERLAY -->
  <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

  <!-- SIDEBAR ADMIN -->
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
      <a href="po.php" class="nav-item"><span class="nav-icon">📋</span> Purchase Order</a>
      <a href="nota.php" class="nav-item"><span class="nav-icon">🧾</span> Nota / Invoice</a>
      <a href="penawaran.php" class="nav-item"><span class="nav-icon">📝</span> Penawaran</a>
      <a href="customer-history.php" class="nav-item"><span class="nav-icon">👥</span> Riwayat Customer</a>

      <div class="nav-section-title" style="margin-top:14px">Modul Customer Portal</div>
      <a href="admin-requests.php" class="nav-item">
        <span class="nav-icon">📥</span> Permintaan Barang
        <?php if ($totalPending > 0): ?>
          <span class="nav-badge" style="background:var(--am-500);color:#fff"><?= $totalPending ?></span>
        <?php endif; ?>
      </a>
      <a href="admin-orders.php" class="nav-item active">
        <span class="nav-icon">📦</span> Pesanan Customer
      </a>
      <a href="admin-products.php" class="nav-item">
        <span class="nav-icon">🛍️</span> Katalog Produk
      </a>

      <div class="nav-section-title" style="margin-top:14px">Pengaturan</div>
      <a href="master.php" class="nav-item"><span class="nav-icon">🏢</span> Master Perusahaan</a>
    </nav>
    <div class="sidebar-footer">
      <div style="margin-bottom:8px">📞 0822 2525 5251 &nbsp;|&nbsp; tidarteknik@gmail.com</div>
      <a href="logout.php" style="display:flex;align-items:center;gap:6px;justify-content:center;padding:7px 12px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:8px;color:#fca5a5;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s" id="btn-logout">🚪 Keluar</a>
    </div>
  </aside>

  <div class="main-wrap">
    <header class="topbar">
      <button class="hamburger-btn" id="hamburger-btn" onclick="toggleSidebar()" aria-label="Buka menu">☰</button>
      <div>
        <div class="topbar-title">Pesanan Masuk Customer</div>
        <div class="topbar-subtitle">Verifikasi pembayaran, buat PO per supplier &amp; perbarui pengiriman</div>
      </div>
      <div class="topbar-actions">
        <a href="index.php" class="btn btn-ghost">← Kembali ke Dashboard</a>
      </div>
    </header>

    <main class="page-content">

      <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom: 20px;">
          <span>✅</span> <?= $success ?>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom: 20px;">
          <span>⚠️</span> <?= $error ?>
        </div>
      <?php endif; ?>

      <!-- FILTER STATUS TABS -->
      <div class="tab-list" style="margin-bottom: 20px;">
        <a href="admin-orders.php" class="tab-btn <?= empty($filterStatus) ? 'active' : '' ?>">
          Semua Pesanan (<?= count($orders) ?>)
        </a>
        <a href="admin-orders.php?status=pending" class="tab-btn <?= $filterStatus === 'pending' ? 'active' : '' ?>">
          🟡 Pending
        </a>
        <a href="admin-orders.php?status=diproses" class="tab-btn <?= $filterStatus === 'diproses' ? 'active' : '' ?>">
          🔵 Diproses
        </a>
        <a href="admin-orders.php?status=dikirim" class="tab-btn <?= $filterStatus === 'dikirim' ? 'active' : '' ?>">
          🚚 Dikirim
        </a>
        <a href="admin-orders.php?status=selesai" class="tab-btn <?= $filterStatus === 'selesai' ? 'active' : '' ?>">
          ✅ Selesai
        </a>
      </div>

      <!-- TABEL PESANAN -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Daftar Transaksi Pesanan Customer Portal</div>
        </div>

        <div class="table-wrap" style="border-radius:0;border:none;border-top:1px solid var(--navy-200);box-shadow:none;">
          <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 48px 16px; color: var(--navy-500);">
              Tidak ada transaksi pesanan pada kategori ini.
            </div>
          <?php else: ?>
            <table>
              <thead>
                <tr>
                  <th>No. Order &amp; Tanggal</th>
                  <th>Customer</th>
                  <th>Item Pesanan</th>
                  <th>Total</th>
                  <th>Pembayaran</th>
                  <th>Status Pesanan</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $o): ?>
                  <tr>
                    <td>
                      <strong><?= htmlspecialchars($o['nomor_order']) ?></strong>
                      <div style="font-size: 11px; color: var(--navy-400); margin-top: 2px;">
                        <?= date('d/m/Y', strtotime($o['tanggal'])) ?>
                      </div>
                    </td>
                    <td>
                      <strong><?= htmlspecialchars($o['nama_customer']) ?></strong>
                      <div style="font-size: 11px; color: var(--navy-500); margin-top: 2px;">
                        📞 <?= htmlspecialchars($o['telp_customer'] ?: '-') ?> | 📍 <?= htmlspecialchars($o['kota_pengiriman']) ?>
                      </div>
                    </td>
                    <td>
                      <?php foreach ($o['items'] as $it): ?>
                        <div>
                          <strong><?= (float)$it['qty'] ?> <?= htmlspecialchars($it['satuan']) ?></strong> - <?= htmlspecialchars($it['nama_barang']) ?>
                        </div>
                      <?php endforeach; ?>
                    </td>
                    <td>
                      <strong style="color: var(--navy-900);">Rp <?= number_format($o['total'], 0, ',', '.') ?></strong>
                      <div style="font-size: 10.5px; color: var(--navy-400); margin-top: 2px;">
                        Ongkir: Rp <?= number_format($o['ongkir'], 0, ',', '.') ?>
                      </div>
                    </td>
                    <td>
                      <?php
                        $payMap = ['paid' => ['badge-green', '🟢 Paid (Lunas)'], 'terverifikasi' => ['badge-green', '🟢 Lunas'],
                                   'dp' => ['badge-blue', '🔵 DP'], 'term' => ['badge-blue', '🔵 Term'], 'partial' => ['badge-blue', '🔵 Partial'],
                                   'sudah_bayar' => ['badge-blue', '🔵 Butuh Verifikasi'], 'dibatalkan' => ['badge-red', '🔴 Batal']];
                        [$pc, $pl] = $payMap[$o['status_pembayaran']] ?? ['badge-amber', '🟡 Belum Bayar']; ?>
                      <span class="badge <?= $pc ?>"><?= $pl ?></span>
                      <div style="font-size: 11px; color: var(--navy-500); margin-top: 2px;">
                        <?= htmlspecialchars($o['metode_pembayaran']) ?>
                      </div>
                    </td>
                    <td>
                      <?php if ($o['status_pesanan'] === 'selesai'): ?>
                        <span class="badge badge-green">✅ Selesai</span>
                      <?php elseif ($o['status_pesanan'] === 'dikirim'): ?>
                        <span class="badge badge-blue">🚚 Dikirim</span>
                        <?php if (!empty($o['no_resi'])): ?>
                          <div style="font-size: 11px; color: var(--navy-500); margin-top: 2px;">
                            Resi: <strong><?= htmlspecialchars($o['no_resi']) ?></strong>
                          </div>
                        <?php endif; ?>
                      <?php elseif ($o['status_pesanan'] === 'diproses'): ?>
                        <span class="badge badge-blue" style="background:#e0e7ff;color:#4338ca">⚙️ Diproses</span>
                      <?php elseif ($o['status_pesanan'] === 'dibatalkan'): ?>
                        <span class="badge badge-red">❌ Batal</span>
                      <?php else: ?>
                        <span class="badge badge-amber">⏳ Pending</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="admin-order-detail.php?id=<?= urlencode($o['id']) ?>" class="btn btn-sm btn-primary">⚙️ Kelola</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

    </main>
  </div>

  <script src="js/app.js?v=<?= time() ?>"></script>
</body>
</html>
