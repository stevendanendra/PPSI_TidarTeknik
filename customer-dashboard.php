<?php
/**
 * customer-dashboard.php — Dashboard Utama Customer Portal
 * Sesuai Sitemap 3.1: Ringkasan Profil, Riwayat Pesanan, Status Tracking, Notifikasi/Info Promo
 */

$pageTitle = 'Dashboard Customer';
$pageSubtitle = 'Ringkasan aktivitas, status pesanan, dan informasi layanan CV. Tidar Teknik';
require_once 'customer-header.php';

$cId = $currentCust['id'];

// Ambil statistik customer
$totalOrders = (int)dbRow("SELECT COUNT(*) AS c FROM orders WHERE customer_id = ?", [$cId])['c'];
$activeOrders = (int)dbRow("SELECT COUNT(*) AS c FROM orders WHERE customer_id = ? AND status_pesanan IN ('pending', 'diproses', 'dikirim')", [$cId])['c'];
$pendingRequests = (int)dbRow("SELECT COUNT(*) AS c FROM product_requests WHERE customer_id = ? AND status = 'pending'", [$cId])['c'];
$approvedRequests = (int)dbRow("SELECT COUNT(*) AS c FROM product_requests WHERE customer_id = ? AND status = 'approved'", [$cId])['c'];
$totalWishlist = (int)dbRow("SELECT COUNT(*) AS c FROM customer_wishlist WHERE customer_id = ?", [$cId])['c'];

// Pesanan terbaru, pesanan terakhir, dan request terbaru
$latestOrder = dbRow("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 1", [$cId]);
$recentOrders = dbRows("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5", [$cId]);
$recentRequests = dbRows("SELECT * FROM product_requests WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5", [$cId]);
?>

<?php if (!empty($_SESSION['customer_flash_welcome'])): ?>
  <div class="alert alert-success" style="margin-bottom: 20px;">
    <span>🎉</span> <?= htmlspecialchars($_SESSION['customer_flash_welcome']) ?>
  </div>
  <?php unset($_SESSION['customer_flash_welcome']); ?>
<?php endif; ?>

<!-- BANNER PROMO & NOTIFIKASI LAYANAN (Sitemap 3.1) -->
<div style="background: linear-gradient(135deg, #075985, #0f766e); border-radius: var(--radius); padding: 22px 26px; color: #fff; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(2,132,199,0.22); position: relative; overflow: hidden;">
  <div style="position: relative; z-index: 2; max-width: 720px;">
    <span style="background: rgba(0,0,0,0.28); padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; display: inline-block; margin-bottom: 8px;">
      📢 Informasi Layanan &amp; Promo Dropship
    </span>
    <h2 style="font-size: 20px; font-weight: 800; margin: 0 0 6px 0; color: #fff;">
      Selamat Datang, <?= htmlspecialchars($currentCust['nama']) ?>!
    </h2>
    <p style="font-size: 13px; color: #fff; line-height: 1.6; margin: 0;">
      CV. Tidar Teknik menyediakan jaminan kecepatan pengadaan komponen industri langsung dari jaringan mitra supplier. 
      Nikmati <strong>Gratis Ongkir</strong> armada rekanan untuk area Malang &amp; Surabaya, serta fasilitas <strong>Request Barang Khusus</strong> dengan respon review admin langsung.
    </p>
  </div>
</div>

<!-- KARTU STATISTIK (Sitemap 3.1) -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
  
  <div class="stat-card" onclick="window.location.href='customer-orders.php'">
    <div class="stat-header">
      <span class="stat-title">Pesanan Aktif</span>
      <span class="stat-icon" style="background: rgba(14,165,233,.12); color: var(--teal-600);">🚚</span>
    </div>
    <div class="stat-value"><?= $activeOrders ?></div>
    <div class="stat-arrow">Total keseluruhan: <?= $totalOrders ?> pesanan →</div>
  </div>

  <div class="stat-card" onclick="window.location.href='customer-request.php'">
    <div class="stat-header">
      <span class="stat-title">Permintaan Disetujui</span>
      <span class="stat-icon" style="background: rgba(16,185,129,.12); color: var(--em-600);">✅</span>
    </div>
    <div class="stat-value" style="color: var(--em-600);"><?= $approvedRequests ?></div>
    <div class="stat-arrow" style="color: var(--em-600);"><?= $pendingRequests ?> request menunggu review admin →</div>
  </div>

  <div class="stat-card" onclick="window.location.href='customer-products.php?tab=wishlist'">
    <div class="stat-header">
      <span class="stat-title">Wishlist / Favorit</span>
      <span class="stat-icon" style="background: rgba(239,68,68,.12); color: #ef4444;">❤️</span>
    </div>
    <div class="stat-value"><?= $totalWishlist ?></div>
    <div class="stat-arrow">Item disimpan untuk pembelian nanti →</div>
  </div>

  <div class="stat-card" onclick="window.location.href='customer-profile.php'">
    <div class="stat-header">
      <span class="stat-title">Status Akun</span>
      <span class="stat-icon" style="background: rgba(99,102,241,.12); color: #6366f1;">👤</span>
    </div>
    <div class="stat-value" style="font-size: 20px; color: var(--navy-800); text-transform: uppercase;">
      <?= htmlspecialchars($currentCust['status']) ?>
    </div>
    <div class="stat-arrow"><?= htmlspecialchars($currentCust['kota'] ?: 'Malang') ?> • Kelola profil →</div>
  </div>

</div>

<!-- TRACKING PESANAN AKTIF TERBARU (Sitemap 3.1 & 3.4) -->
<?php if ($latestOrder): 
  $statusPesan = $latestOrder['status_pesanan'];
  $statusBayar = $latestOrder['status_pembayaran'];

  // Step 1: Pesanan Dibuat (selalu done jika order ada)
  $step1 = true;
  // Step 2: Pembayaran Dikonfirmasi
  $step2 = in_array($statusBayar, ['sudah_bayar', 'terverifikasi']);
  // Step 3: Diproses / Pengadaan
  $step3 = in_array($statusPesan, ['diproses', 'dikirim', 'selesai']);
  // Step 4: Dikirim
  $step4 = in_array($statusPesan, ['dikirim', 'selesai']);
  // Step 5: Selesai
  $step5 = ($statusPesan === 'selesai');
?>
  <div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--teal-500);">
    <div class="card-header" style="justify-content: space-between;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 18px;">📍</span>
        <div class="card-title">Tracking Pesanan Terbaru: <?= htmlspecialchars($latestOrder['nomor_order']) ?></div>
      </div>
      <a href="customer-order-detail.php?id=<?= urlencode($latestOrder['id']) ?>" class="btn btn-sm btn-ghost">
        Lihat Detail &amp; Invoice →
      </a>
    </div>
    <div class="card-body" style="padding: 24px 20px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
          <div style="font-size: 12px; color: var(--navy-400);">Tanggal Pesanan: <?= date('d M Y', strtotime($latestOrder['tanggal'])) ?></div>
          <div style="font-size: 16px; font-weight: 800; color: var(--navy-900); margin-top: 2px;">
            Total: Rp <?= number_format($latestOrder['total'], 0, ',', '.') ?>
            <span style="font-size: 12px; font-weight: normal; color: var(--navy-500);">(<?= htmlspecialchars($latestOrder['metode_pembayaran']) ?>)</span>
          </div>
        </div>
        <div>
          <?php if (!empty($latestOrder['no_resi'])): ?>
            <span style="background: var(--navy-100); padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; color: var(--navy-700);">
              🚚 No. Resi: <?= htmlspecialchars($latestOrder['no_resi']) ?> (<?= htmlspecialchars($latestOrder['ekspedisi']) ?>)
            </span>
          <?php endif; ?>
        </div>
      </div>

      <!-- VISUAL TRACKING STEPPER -->
      <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; text-align: center; position: relative;">
        <!-- Step 1 -->
        <div style="display: flex; flex-direction: column; align-items: center;">
          <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--em-500); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 6px;">✓</div>
          <div style="font-size: 11.5px; font-weight: 700; color: var(--navy-800);">1. Pesanan Dibuat</div>
          <div style="font-size: 10px; color: var(--navy-400);"><?= date('d/m H:i', strtotime($latestOrder['created_at'])) ?></div>
        </div>

        <!-- Step 2 -->
        <div style="display: flex; flex-direction: column; align-items: center;">
          <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $step2 ? 'var(--em-500)' : 'var(--navy-200)' ?>; color: <?= $step2 ? '#fff' : 'var(--navy-400)' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 6px;">
            <?= $step2 ? '✓' : '2' ?>
          </div>
          <div style="font-size: 11.5px; font-weight: 700; color: <?= $step2 ? 'var(--navy-800)' : 'var(--navy-400)' ?>;">2. Pembayaran</div>
          <div style="font-size: 10px; color: var(--navy-400);"><?= $statusBayar === 'terverifikasi' ? 'Terverifikasi' : ($statusBayar === 'sudah_bayar' ? 'Dikonfirmasi' : 'Menunggu') ?></div>
        </div>

        <!-- Step 3 -->
        <div style="display: flex; flex-direction: column; align-items: center;">
          <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $step3 ? 'var(--em-500)' : 'var(--navy-200)' ?>; color: <?= $step3 ? '#fff' : 'var(--navy-400)' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 6px;">
            <?= $step3 ? '✓' : '3' ?>
          </div>
          <div style="font-size: 11.5px; font-weight: 700; color: <?= $step3 ? 'var(--navy-800)' : 'var(--navy-400)' ?>;">3. Diproses</div>
          <div style="font-size: 10px; color: var(--navy-400);">Supplier &amp; Packing</div>
        </div>

        <!-- Step 4 -->
        <div style="display: flex; flex-direction: column; align-items: center;">
          <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $step4 ? 'var(--teal-500)' : 'var(--navy-200)' ?>; color: <?= $step4 ? '#fff' : 'var(--navy-400)' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 6px;">
            <?= $step4 ? '🚚' : '4' ?>
          </div>
          <div style="font-size: 11.5px; font-weight: 700; color: <?= $step4 ? 'var(--teal-600)' : 'var(--navy-400)' ?>;">4. Dikirim</div>
          <div style="font-size: 10px; color: var(--navy-400);"><?= $step4 ? 'Dalam Perjalanan' : 'Menunggu Kirim' ?></div>
        </div>

        <!-- Step 5 -->
        <div style="display: flex; flex-direction: column; align-items: center;">
          <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $step5 ? 'var(--em-500)' : 'var(--navy-200)' ?>; color: <?= $step5 ? '#fff' : 'var(--navy-400)' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 6px;">
            <?= $step5 ? '✓' : '5' ?>
          </div>
          <div style="font-size: 11.5px; font-weight: 700; color: <?= $step5 ? 'var(--em-600)' : 'var(--navy-400)' ?>;">5. Selesai</div>
          <div style="font-size: 10px; color: var(--navy-400);"><?= $step5 ? 'Diterima' : 'Pesanan Tiba' ?></div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- 2 KOLOM: RIWAYAT PESANAN & PERMINTAAN TERBARU -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
  
  <!-- KOLOM 1: PESANAN TERBARU -->
  <div class="card">
    <div class="card-header" style="justify-content: space-between;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 18px;">📦</span>
        <div class="card-title">Riwayat Pesanan Terbaru</div>
      </div>
      <a href="customer-orders.php" class="btn btn-sm btn-ghost">Lihat Semua →</a>
    </div>

    <div class="table-wrap" style="border-radius:0;border:none;border-top:1px solid var(--navy-200);box-shadow:none;">
      <?php if (empty($recentOrders)): ?>
        <div style="text-align: center; padding: 32px 16px; color: var(--navy-500); font-size: 13px;">
          Belum ada riwayat pesanan.
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>No. Order</th>
              <th>Total</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $ro): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($ro['nomor_order']) ?></strong>
                  <div style="font-size: 11px; color: var(--navy-400);"><?= date('d/m/Y', strtotime($ro['tanggal'])) ?></div>
                </td>
                <td>
                  <strong>Rp <?= number_format($ro['total'], 0, ',', '.') ?></strong>
                </td>
                <td>
                  <?php if ($ro['status_pesanan'] === 'selesai'): ?>
                    <span class="badge badge-green">Selesai</span>
                  <?php elseif ($ro['status_pesanan'] === 'dikirim'): ?>
                    <span class="badge badge-blue">Dikirim</span>
                  <?php elseif ($ro['status_pesanan'] === 'diproses'): ?>
                    <span class="badge badge-amber">Diproses</span>
                  <?php else: ?>
                    <span class="badge badge-amber">Pending</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="customer-order-detail.php?id=<?= urlencode($ro['id']) ?>" class="btn btn-sm btn-ghost">Detail</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- KOLOM 2: PERMINTAAN BARANG TERBARU -->
  <div class="card">
    <div class="card-header" style="justify-content: space-between;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 18px;">📋</span>
        <div class="card-title">Permintaan Barang Terbaru</div>
      </div>
      <a href="customer-request.php" class="btn btn-sm btn-ghost">Lihat Semua →</a>
    </div>

    <div class="table-wrap" style="border-radius:0;border:none;border-top:1px solid var(--navy-200);box-shadow:none;">
      <?php if (empty($recentRequests)): ?>
        <div style="text-align: center; padding: 32px 16px; color: var(--navy-500); font-size: 13px;">
          Belum ada riwayat permintaan barang.
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Barang</th>
              <th>Qty</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentRequests as $rq): ?>
              <tr>
                <td>
                  <strong style="color: var(--navy-900);"><?= htmlspecialchars(mb_strimwidth($rq['nama_barang'], 0, 24, '...')) ?></strong>
                  <div style="font-size: 11px; color: var(--navy-400);"><?= htmlspecialchars($rq['nomor_request']) ?></div>
                </td>
                <td><?= (float)$rq['qty'] ?> <?= htmlspecialchars($rq['satuan']) ?></td>
                <td>
                  <?php if ($rq['status'] === 'approved'): ?>
                    <span class="badge badge-green">Disetujui</span>
                  <?php elseif ($rq['status'] === 'rejected'): ?>
                    <span class="badge badge-red">Ditolak</span>
                  <?php elseif ($rq['status'] === 'ordered'): ?>
                    <span class="badge badge-blue">Dipesan</span>
                  <?php else: ?>
                    <span class="badge badge-amber">Review</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($rq['status'] === 'approved'): ?>
                    <a href="customer-checkout.php?request_id=<?= urlencode($rq['id']) ?>" class="btn btn-sm btn-success">Checkout</a>
                  <?php else: ?>
                    <a href="customer-request.php" class="btn btn-sm btn-ghost">Lihat</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require_once 'customer-footer.php'; ?>
