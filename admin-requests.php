<?php
/**
 * admin-requests.php — Manajemen & Review Permintaan Barang Customer (Fase 1)
 * CV. Tidar Teknik (Panel Internal Admin)
 */

require_once 'auth.php';
requireLogin();
require_once 'lib.php';

$success = '';
$error = '';

// Handle review submit (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_review'])) {
    $reqId        = trim($_POST['request_id'] ?? '');
    $newStatus    = trim($_POST['status'] ?? 'pending');
    $harga        = (float)($_POST['harga_disetujui'] ?? 0);
    $catatanAdmin = trim($_POST['catatan_admin'] ?? '');
    $req = $reqId !== '' ? dbRow("SELECT id, customer_id, nomor_request, nama_barang FROM product_requests WHERE id = ?", [$reqId]) : null;

    if (!$req) {
        $error = 'ID request tidak valid.';
    } elseif (!in_array($newStatus, ['pending', 'approved', 'rejected'], true)) {
        $error = 'Status tidak valid.';
    } elseif ($newStatus === 'approved' && $harga <= 0) {
        $error = 'Harga yang disetujui harus lebih besar dari Rp 0.';
    } else {
        try {
            dbRun("UPDATE product_requests SET status = ?, harga_disetujui = ?, catatan_admin = ?, tanggal_review = NOW() WHERE id = ?",
                [$newStatus, $newStatus === 'approved' ? $harga : null, $catatanAdmin, $reqId]);
            $success = "Permintaan barang berhasil diperbarui menjadi status: <strong>" . h(strtoupper($newStatus)) . "</strong>.";
            if ($newStatus !== 'pending') {
                $label = $newStatus === 'approved' ? 'disetujui dengan harga Rp ' . number_format($harga, 0, ',', '.') . '. Silakan checkout' : 'ditolak';
                notify('customer', $req['customer_id'], "Request {$req['nomor_request']} ({$req['nama_barang']}) $label.", 'customer-request.php');
            }
        } catch (Throwable $e) {
            $error = 'Gagal menyimpan review. Coba lagi.';
        }
    }
}

// Filter status
$filterStatus = trim($_GET['status'] ?? '');
$where = ["1=1"];
$params = [];
if (in_array($filterStatus, ['pending', 'approved', 'rejected', 'ordered'], true)) {
    $where[] = "r.status = ?";
    $params[] = $filterStatus;
}
$whereClause = implode(" AND ", $where);

// Ambil data requests
$requests = dbRows("SELECT r.*, c.nama AS nama_customer, c.email AS email_customer, c.telepon AS telp_customer, c.kota AS kota_customer
                    FROM product_requests r
                    JOIN customers c ON r.customer_id = c.id
                    WHERE $whereClause
                    ORDER BY FIELD(r.status, 'pending', 'approved', 'rejected', 'ordered'), r.created_at DESC", $params);

// Hitung total pending untuk badge
$totalPending = (int)dbRow("SELECT COUNT(*) AS c FROM product_requests WHERE status = 'pending'")['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Permintaan Barang Customer — CV. Tidar Teknik</title>
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
      <a href="admin-requests.php" class="nav-item active">
        <span class="nav-icon">📥</span> Permintaan Barang
        <?php if ($totalPending > 0): ?>
          <span class="nav-badge" style="background:var(--am-500);color:#fff"><?= $totalPending ?></span>
        <?php endif; ?>
      </a>
      <a href="admin-orders.php" class="nav-item">
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
        <div class="topbar-title">Permintaan Barang Masuk (Fase 1)</div>
        <div class="topbar-subtitle">Review ketersediaan produk &amp; tentukan harga jual untuk customer</div>
      </div>
      <div class="topbar-actions">
        <a href="admin-products.php" class="btn btn-ghost">🛍️ Kelola Katalog</a>
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

      <!-- FILTER TABS -->
      <div class="tab-list" style="margin-bottom: 20px;">
        <a href="admin-requests.php" class="tab-btn <?= empty($filterStatus) ? 'active' : '' ?>">
          Semua Request (<?= count($requests) ?>)
        </a>
        <a href="admin-requests.php?status=pending" class="tab-btn <?= $filterStatus === 'pending' ? 'active' : '' ?>">
          🟡 Menunggu Review (<?= $totalPending ?>)
        </a>
        <a href="admin-requests.php?status=approved" class="tab-btn <?= $filterStatus === 'approved' ? 'active' : '' ?>">
          🟢 Disetujui
        </a>
        <a href="admin-requests.php?status=rejected" class="tab-btn <?= $filterStatus === 'rejected' ? 'active' : '' ?>">
          🔴 Ditolak
        </a>
      </div>

      <!-- TABEL REQUEST -->
      <div class="card">
        <div class="card-header" style="justify-content: space-between;">
          <div class="card-title">Daftar Permintaan Produk dari Customer Portal</div>
          <span style="font-size: 12px; color: var(--navy-500);">Klik "Review &amp; Beri Harga" untuk menindaklanjuti permintaan</span>
        </div>

        <div class="table-wrap" style="border-radius: 0; box-shadow: none; border: none; border-top: 1px solid var(--navy-200);">
          <?php if (empty($requests)): ?>
            <div style="text-align: center; padding: 48px 16px;">
              <div style="font-size: 40px; margin-bottom: 8px;">📭</div>
              <p style="color: var(--navy-500); font-size: 14px; margin: 0;">Tidak ada permintaan barang pada filter ini.</p>
            </div>
          <?php else: ?>
            <table>
              <thead>
                <tr>
                  <th>No. Request &amp; Tanggal</th>
                  <th>Customer</th>
                  <th>Nama Barang &amp; Spesifikasi</th>
                  <th>Qty</th>
                  <th>Status</th>
                  <th>Harga Disetujui</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($requests as $r): ?>
                  <tr>
                    <td>
                      <strong><?= htmlspecialchars($r['nomor_request']) ?></strong>
                      <div style="font-size: 11px; color: var(--navy-400); margin-top: 2px;">
                        <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                      </div>
                    </td>
                    <td>
                      <strong><?= htmlspecialchars($r['nama_customer']) ?></strong>
                      <div style="font-size: 11px; color: var(--navy-500); margin-top: 2px;">
                        📍 <?= htmlspecialchars($r['kota_customer'] ?: '-') ?> | 📞 <?= htmlspecialchars($r['telp_customer'] ?: '-') ?>
                      </div>
                    </td>
                    <td>
                      <strong style="color: var(--navy-900);"><?= htmlspecialchars($r['nama_barang']) ?></strong>
                      <?php if (!empty($r['spesifikasi'])): ?>
                        <div style="font-size: 11.5px; color: var(--navy-500); margin-top: 2px;">
                          <?= htmlspecialchars($r['spesifikasi']) ?>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($r['catatan'])): ?>
                        <div style="font-size: 11.5px; color: var(--am-600); margin-top: 3px; font-style: italic;">
                          Catatan Customer: "<?= htmlspecialchars($r['catatan']) ?>"
                        </div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <strong><?= (float)$r['qty'] ?></strong> <?= htmlspecialchars($r['satuan']) ?>
                    </td>
                    <td>
                      <?php if ($r['status'] === 'pending'): ?>
                        <span class="badge badge-amber">🟡 Menunggu Review</span>
                      <?php elseif ($r['status'] === 'approved'): ?>
                        <span class="badge badge-green">🟢 Disetujui</span>
                      <?php elseif ($r['status'] === 'rejected'): ?>
                        <span class="badge badge-red">🔴 Ditolak</span>
                      <?php elseif ($r['status'] === 'ordered'): ?>
                        <span class="badge badge-blue">🔵 Sudah Dipesan</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($r['status'] === 'approved' && $r['harga_disetujui'] > 0): ?>
                        <strong style="color: var(--em-600); font-size: 14px;">
                          Rp <?= number_format($r['harga_disetujui'], 0, ',', '.') ?>
                        </strong>
                        <span style="font-size: 10.5px; color: var(--navy-400);">/<?= htmlspecialchars($r['satuan']) ?></span>
                      <?php else: ?>
                        <span style="color: var(--navy-400); font-size: 12px;">-</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button 
                        type="button" 
                        class="btn btn-sm btn-primary"
                        onclick="openReviewModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)"
                      >
                        ⚖️ Review &amp; Harga
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

  <!-- MODAL REVIEW REQUEST -->
  <div id="modal-review" style="display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #fff; width: 100%; max-width: 520px; border-radius: var(--radius); overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,.2); animation: modalIn .2s ease;">
      <div style="padding: 16px 20px; border-bottom: 1px solid var(--navy-100); display: flex; justify-content: space-between; align-items: center; background: var(--navy-50);">
        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--navy-800);" id="modal-req-title">Review Permintaan</h3>
        <button type="button" onclick="closeReviewModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--navy-400);">✕</button>
      </div>

      <form method="POST" action="admin-requests.php" style="padding: 20px;">
        <input type="hidden" name="action_review" value="1">
        <input type="hidden" name="request_id" id="modal-input-req-id">

        <div style="background: var(--navy-50); border: 1px solid var(--navy-200); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 16px; font-size: 12.5px;">
          <div style="font-weight: 700; color: var(--navy-900);" id="modal-barang-name">-</div>
          <div style="color: var(--navy-600); margin-top: 3px;" id="modal-customer-info">-</div>
          <div style="color: var(--navy-500); margin-top: 4px; font-size: 11.5px;" id="modal-spesifikasi-info">-</div>
        </div>

        <div class="form-group">
          <label class="form-label required">Keputusan Admin</label>
          <select name="status" id="modal-select-status" class="form-control" onchange="toggleHargaField()" required>
            <option value="approved">🟢 Setujui (Tentukan Harga Jual)</option>
            <option value="rejected">🔴 Tolak (Barang Tidak Dapat Dipenuhi)</option>
            <option value="pending">🟡 Pending (Masih Cek Supplier)</option>
          </select>
        </div>

        <div class="form-group" id="group-harga">
          <label class="form-label required">Harga Jual yang Diberikan (Rp)</label>
          <input 
            type="number" 
            name="harga_disetujui" 
            id="modal-input-harga" 
            class="form-control" 
            placeholder="Contoh: 1500000" 
            step="any"
          >
          <span style="font-size: 11px; color: var(--navy-400); margin-top: 4px; display: block;">
            Harga per satuan ini akan muncul di dashboard customer untuk di-checkout.
          </span>
        </div>

        <div class="form-group">
          <label class="form-label">Catatan Admin untuk Customer</label>
          <textarea 
            name="catatan_admin" 
            id="modal-input-catatan" 
            class="form-control" 
            rows="3" 
            placeholder="Contoh: Stok tersedia di supplier Surabaya, siap kirim estimasi 2 hari setelah checkout."
          ></textarea>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
          <button type="button" class="btn btn-ghost" onclick="closeReviewModal()">Batal</button>
          <button type="submit" class="btn btn-primary">💾 Simpan &amp; Informasikan ke Customer</button>
        </div>
      </form>
    </div>
  </div>

  <script src="js/app.js?v=<?= time() ?>"></script>
  <script>
    function openReviewModal(req) {
      document.getElementById('modal-input-req-id').value = req.id;
      document.getElementById('modal-req-title').textContent = 'Review Permintaan: ' + req.nomor_request;
      document.getElementById('modal-barang-name').textContent = req.nama_barang + ' (' + req.qty + ' ' + req.satuan + ')';
      document.getElementById('modal-customer-info').textContent = 'Customer: ' + req.nama_customer + ' | ' + (req.telp_customer || '-');
      document.getElementById('modal-spesifikasi-info').textContent = 'Spesifikasi: ' + (req.spesifikasi || '-');

      document.getElementById('modal-select-status').value = req.status === 'pending' ? 'approved' : req.status;
      document.getElementById('modal-input-harga').value = req.harga_disetujui > 0 ? req.harga_disetujui : '';
      document.getElementById('modal-input-catatan').value = req.catatan_admin || '';

      toggleHargaField();
      document.getElementById('modal-review').style.display = 'flex';
    }

    function closeReviewModal() {
      document.getElementById('modal-review').style.display = 'none';
    }

    function toggleHargaField() {
      const status = document.getElementById('modal-select-status').value;
      const grpHarga = document.getElementById('group-harga');
      if (status === 'approved') {
        grpHarga.style.display = 'block';
        document.getElementById('modal-input-harga').required = true;
      } else {
        grpHarga.style.display = 'none';
        document.getElementById('modal-input-harga').required = false;
      }
    }
  </script>
</body>
</html>
