<?php
/**
 * admin-orders.php — Manajemen Pesanan Customer & Pengiriman (Fase 2)
 * CV. Tidar Teknik (Panel Internal Admin)
 */

require_once 'auth.php';
requireLogin();
require_once 'db.php';

$success = '';
$error = '';

// Handle update status pesanan / pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_order'])) {
    $orderId    = mysqli_real_escape_string($conn, $_POST['order_id'] ?? '');
    $statBayar  = mysqli_real_escape_string($conn, $_POST['status_pembayaran'] ?? '');
    $statPesan  = mysqli_real_escape_string($conn, $_POST['status_pesanan'] ?? '');
    $noResi     = mysqli_real_escape_string($conn, $_POST['no_resi'] ?? '');

    if (empty($orderId)) {
        $error = 'Order ID tidak valid.';
    } else {
        $q = "UPDATE orders SET 
                status_pembayaran = '$statBayar',
                status_pesanan = '$statPesan',
                no_resi = '$noResi'
              WHERE id = '$orderId'";
        if (mysqli_query($conn, $q)) {
            $success = 'Status pesanan berhasil diperbarui.';
        } else {
            $error = 'Gagal memperbarui pesanan: ' . mysqli_error($conn);
        }
    }
}

// Filter status
$filterStatus = trim($_GET['status'] ?? '');
$where = ["1=1"];
if (!empty($filterStatus)) {
    $fs = mysqli_real_escape_string($conn, $filterStatus);
    $where[] = "o.status_pesanan = '$fs'";
}
$whereSql = implode(" AND ", $where);

// Ambil orders
$sql = "SELECT o.*, c.nama AS nama_customer, c.telepon AS telp_customer, c.email AS email_customer 
        FROM orders o 
        JOIN customers c ON o.customer_id = c.id 
        WHERE $whereSql 
        ORDER BY o.created_at DESC";
$resOrders = mysqli_query($conn, $sql);
$orders = mysqli_fetch_all($resOrders, MYSQLI_ASSOC);

// Load items per order
foreach ($orders as &$ord) {
    $oid = $ord['id'];
    $iRes = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = '$oid'");
    $ord['items'] = mysqli_fetch_all($iRes, MYSQLI_ASSOC);
}

// Hitung request pending untuk badge
$cntPendingRes = mysqli_query($conn, "SELECT COUNT(*) FROM product_requests WHERE status = 'pending'");
$totalPending = (int)mysqli_fetch_row($cntPendingRes)[0];
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
      <a href="logout.php" style="display:flex;align-items:center;gap:6px;justify-content:center;padding:7px 12px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:8px;color:#ef4444;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s" id="btn-logout">🚪 Keluar</a>
    </div>
  </aside>

  <div class="main-wrap">
    <header class="topbar">
      <button class="hamburger-btn" id="hamburger-btn" onclick="toggleSidebar()" aria-label="Buka menu">☰</button>
      <div>
        <div class="topbar-title">Pesanan Masuk Customer (Fase 2)</div>
        <div class="topbar-subtitle">Verifikasi bukti pembayaran &amp; perbarui status proses/pengiriman barang</div>
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
                      <?php if ($o['status_pembayaran'] === 'terverifikasi'): ?>
                        <span class="badge badge-green">🟢 Lunas / Terverifikasi</span>
                      <?php elseif ($o['status_pembayaran'] === 'sudah_bayar'): ?>
                        <span class="badge badge-blue">🔵 Butuh Verifikasi</span>
                      <?php else: ?>
                        <span class="badge badge-amber">🟡 Belum Bayar</span>
                      <?php endif; ?>
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
                      <button 
                        type="button" 
                        class="btn btn-sm btn-primary"
                        onclick="openUpdateOrderModal(<?= htmlspecialchars(json_encode($o), ENT_QUOTES, 'UTF-8') ?>)"
                      >
                        ⚙️ Kelola Status
                      </button>
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

  <!-- MODAL KELOLA STATUS ORDER -->
  <div id="modal-order" style="display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #fff; width: 100%; max-width: 500px; border-radius: var(--radius); overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,.2); animation: modalIn .2s ease;">
      <div style="padding: 16px 20px; border-bottom: 1px solid var(--navy-100); display: flex; justify-content: space-between; align-items: center; background: var(--navy-50);">
        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--navy-800);" id="modal-order-title">Kelola Status Pesanan</h3>
        <button type="button" onclick="closeOrderModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--navy-400);">✕</button>
      </div>

      <form method="POST" action="admin-orders.php" style="padding: 20px;">
        <input type="hidden" name="action_order" value="1">
        <input type="hidden" name="order_id" id="modal-order-id">

        <div style="background: var(--navy-50); border: 1px solid var(--navy-200); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 16px; font-size: 12.5px;">
          <div style="font-weight: 700; color: var(--navy-900);" id="modal-order-cust">-</div>
          <div style="color: var(--navy-600); margin-top: 2px;" id="modal-order-total">-</div>
          <div style="color: var(--navy-500); margin-top: 2px;" id="modal-order-alamat">-</div>
        </div>

        <div class="form-group">
          <label class="form-label required">Status Pembayaran</label>
          <select name="status_pembayaran" id="modal-order-stat-bayar" class="form-control" required>
            <option value="menunggu_pembayaran">🟡 Menunggu Pembayaran</option>
            <option value="sudah_bayar">🔵 Sudah Bayar (Konfirmasi Customer)</option>
            <option value="terverifikasi">🟢 Terverifikasi / Lunas</option>
            <option value="dibatalkan">🔴 Pembayaran Dibatalkan</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label required">Status Pesanan &amp; Pengiriman</label>
          <select name="status_pesanan" id="modal-order-stat-pesan" class="form-control" required>
            <option value="pending">⏳ Pending (Menunggu Diproses)</option>
            <option value="diproses">⚙️ Diproses (Packing / Pengadaan Supplier)</option>
            <option value="dikirim">🚚 Dikirim (Dalam Perjalanan)</option>
            <option value="selesai">✅ Selesai (Diterima Customer)</option>
            <option value="dibatalkan">❌ Pesanan Dibatalkan</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Nomor Resi / Surat Jalan</label>
          <input type="text" name="no_resi" id="modal-order-resi" class="form-control" placeholder="Contoh: TT-SJ-2026/04/001 atau No Resi Ekspedisi">
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
          <button type="button" class="btn btn-ghost" onclick="closeOrderModal()">Batal</button>
          <button type="submit" class="btn btn-primary">💾 Simpan Status</button>
        </div>
      </form>
    </div>
  </div>

  <script src="js/app.js?v=<?= time() ?>"></script>
  <script>
    function openUpdateOrderModal(ord) {
      document.getElementById('modal-order-id').value = ord.id;
      document.getElementById('modal-order-title').textContent = 'Kelola Pesanan: ' + ord.nomor_order;
      document.getElementById('modal-order-cust').textContent = 'Customer: ' + ord.nama_customer;
      document.getElementById('modal-order-total').textContent = 'Total: Rp ' + parseFloat(ord.total).toLocaleString('id-ID') + ' (' + ord.metode_pembayaran + ')';
      document.getElementById('modal-order-alamat').textContent = 'Tujuan: ' + ord.kota_pengiriman + ' • ' + ord.ekspedisi;

      document.getElementById('modal-order-stat-bayar').value = ord.status_pembayaran;
      document.getElementById('modal-order-stat-pesan').value = ord.status_pesanan;
      document.getElementById('modal-order-resi').value = ord.no_resi || '';

      document.getElementById('modal-order').style.display = 'flex';
    }

    function closeOrderModal() {
      document.getElementById('modal-order').style.display = 'none';
    }
  </script>
</body>
</html>
