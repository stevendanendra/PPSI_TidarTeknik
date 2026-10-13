<?php
/**
 * customer-request.php — Form & Riwayat Pengajuan Permintaan Barang (Fase 1)
 * Sesuai Sitemap 3.3 Fase 1: Request -> Review Admin -> Approval & Penentuan Harga -> Checkout
 */

$pageTitle = 'Permintaan Barang (Request)';
$pageSubtitle = 'Ajukan kebutuhan komponen teknik di luar stok untuk dicek dan diberi penawaran harga oleh admin';
require_once 'customer-header.php';

$cId = $currentCust['id'];
$successMsg = '';
$errorMsg   = '';

// Cek apakah request berasal dari produk katalog stok 0
$preloadProduct = null;
$preProductId = trim($_GET['product_id'] ?? '');
if (!empty($preProductId)) {
    $preloadProduct = dbRow("SELECT * FROM products WHERE id = ? LIMIT 1", [$preProductId]);
}

// Proses submit form request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $namaBarang = trim($_POST['nama_barang'] ?? '');
    $qty        = (float)($_POST['qty'] ?? 1);
    $satuan     = trim($_POST['satuan'] ?? 'PCS');
    $spesifikasi= trim($_POST['spesifikasi'] ?? '');
    $catatan    = trim($_POST['catatan'] ?? '');
    $prodId     = trim($_POST['product_id'] ?? '');

    if (empty($namaBarang)) {
        $errorMsg = 'Nama barang wajib diisi.';
    } elseif ($qty <= 0) {
        $errorMsg = 'Jumlah kebutuhan (qty) harus lebih dari 0.';
    } else {
        // Generate nomor request otomatis: REQ-YYYYMMDD-XXXX
        $dateCode = date('Ymd');
        $randomCode = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        $nomorRequest = "REQ-{$dateCode}-{$randomCode}";
        $newReqId = 'req_' . substr(md5(uniqid(mt_rand(), true)), 0, 10);

        try {
            dbRun("INSERT INTO product_requests (id, nomor_request, customer_id, product_id, nama_barang, spesifikasi, qty, satuan, catatan, status)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
                [$newReqId, $nomorRequest, $cId, $prodId !== '' ? $prodId : null, $namaBarang, $spesifikasi, $qty, $satuan, $catatan]);
            $successMsg = "Permintaan barang berhasil dikirim dengan Nomor: <strong>" . h($nomorRequest) . "</strong>. Admin CV. Tidar Teknik akan segera meninjau dan memberikan penawaran harga.";
            notify('admin', null, "Request barang baru {$nomorRequest} dari {$currentCust['nama']}: {$namaBarang}", 'admin-requests.php');
            $preloadProduct = null; // reset form
        } catch (Throwable $e) {
            $errorMsg = 'Gagal mengirim permintaan. Pastikan data valid lalu coba lagi.';
        }
    }
}

// Ambil daftar riwayat permintaan milik customer ini
$myRequests = dbRows("SELECT r.*, p.kode AS kode_produk
                       FROM product_requests r
                       LEFT JOIN products p ON r.product_id = p.id
                       WHERE r.customer_id = ?
                       ORDER BY r.created_at DESC", [$cId]);

// Hitung rekap status
$cntPending  = 0;
$cntApproved = 0;
$cntRejected = 0;
foreach ($myRequests as $mr) {
    if ($mr['status'] === 'pending')  $cntPending++;
    if ($mr['status'] === 'approved') $cntApproved++;
    if ($mr['status'] === 'rejected') $cntRejected++;
}
?>

<?php if ($successMsg): ?>
  <div class="alert alert-success" style="margin-bottom: 20px;">
    <span>✅</span> <?= $successMsg ?>
  </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
  <div class="alert alert-error" style="margin-bottom: 20px;">
    <span>⚠️</span> <?= $errorMsg ?>
  </div>
<?php endif; ?>

<!-- REKAP STATUS SUMMARY -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
  <div class="card" style="padding: 18px; border-left: 4px solid var(--am-500);">
    <div style="font-size: 11px; font-weight: 700; color: var(--navy-400); text-transform: uppercase;">Menunggu Review Admin</div>
    <div style="font-size: 24px; font-weight: 800; color: var(--am-600); margin-top: 4px;"><?= $cntPending ?></div>
  </div>
  <div class="card" style="padding: 18px; border-left: 4px solid var(--em-500);">
    <div style="font-size: 11px; font-weight: 700; color: var(--navy-400); text-transform: uppercase;">Disetujui &amp; Ada Harga</div>
    <div style="font-size: 24px; font-weight: 800; color: var(--em-600); margin-top: 4px;"><?= $cntApproved ?></div>
  </div>
  <div class="card" style="padding: 18px; border-left: 4px solid var(--red-500);">
    <div style="font-size: 11px; font-weight: 700; color: var(--navy-400); text-transform: uppercase;">Tidak Dapat Dipenuhi</div>
    <div style="font-size: 24px; font-weight: 800; color: var(--red-600); margin-top: 4px;"><?= $cntRejected ?></div>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start;">
  
  <!-- SISI KIRI: DAFTAR RIWAYAT REQUEST -->
  <div class="card">
    <div class="card-header" style="justify-content: space-between;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 20px;">📋</span>
        <div class="card-title">Riwayat Permintaan Barang Saya</div>
      </div>
      <span class="badge badge-blue"><?= count($myRequests) ?> Permintaan</span>
    </div>

    <div class="table-wrap" style="border-radius: 0; border: none; border-top: 1px solid var(--navy-200); box-shadow: none;">
      <?php if (empty($myRequests)): ?>
        <div style="text-align: center; padding: 48px 16px;">
          <div style="font-size: 44px; margin-bottom: 8px;">📝</div>
          <p style="color: var(--navy-500); font-size: 13.5px; margin: 0;">Belum ada pengajuan permintaan barang. Silakan isi form di samping untuk mengajukan barang baru atau barang yang sedang kosong stoknya.</p>
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Nomor &amp; Tanggal</th>
              <th>Nama Barang &amp; Spesifikasi</th>
              <th>Jumlah</th>
              <th>Status &amp; Harga Admin</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($myRequests as $r): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($r['nomor_request']) ?></strong>
                  <div style="font-size: 11px; color: var(--navy-400); margin-top: 2px;">
                    <?= date('d M Y, H:i', strtotime($r['created_at'])) ?>
                  </div>
                </td>
                <td>
                  <strong style="color: var(--navy-900);"><?= htmlspecialchars($r['nama_barang']) ?></strong>
                  <?php if (!empty($r['spesifikasi'])): ?>
                    <div style="font-size: 11.5px; color: var(--navy-500); margin-top: 2px;">
                      <?= htmlspecialchars(mb_strimwidth($r['spesifikasi'], 0, 60, '...')) ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($r['catatan_admin'])): ?>
                    <div style="font-size: 11.5px; color: var(--teal-600); margin-top: 4px; background: var(--teal-100); padding: 3px 8px; border-radius: 4px; display: inline-block;">
                      💬 Catatan Admin: <?= htmlspecialchars($r['catatan_admin']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <strong><?= (float)$r['qty'] ?></strong> <?= htmlspecialchars($r['satuan']) ?>
                </td>
                <td>
                  <?php if ($r['status'] === 'pending'): ?>
                    <span class="badge badge-amber" style="font-size: 11px;">🟡 Menunggu Review</span>
                    <div style="font-size: 11px; color: var(--navy-400); margin-top: 3px;">Pengecekan supplier</div>
                  <?php elseif ($r['status'] === 'approved'): ?>
                    <span class="badge badge-green" style="font-size: 11px;">🟢 Disetujui</span>
                    <div style="font-size: 13px; font-weight: 800; color: var(--em-600); margin-top: 4px;">
                      Rp <?= number_format($r['harga_disetujui'], 0, ',', '.') ?>
                      <span style="font-size: 10.5px; font-weight: normal; color: var(--navy-500);">/<?= htmlspecialchars($r['satuan']) ?></span>
                    </div>
                  <?php elseif ($r['status'] === 'rejected'): ?>
                    <span class="badge badge-red" style="font-size: 11px;">🔴 Tidak Tersedia</span>
                  <?php elseif ($r['status'] === 'ordered'): ?>
                    <span class="badge badge-blue" style="font-size: 11px;">🔵 Sudah Dipesan</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($r['status'] === 'approved'): ?>
                    <!-- FASE 1 LANGKAH 6: Customer konfirmasi & checkout dengan harga yang admin tentukan -->
                    <a href="customer-checkout.php?request_id=<?= urlencode($r['id']) ?>" class="btn btn-sm btn-success" style="font-weight: 700;">
                      🛒 Konfirmasi &amp; Checkout
                    </a>
                  <?php else: ?>
                    <span style="font-size: 12px; color: var(--navy-400);">-</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- SISI KANAN: FORM PENGAJUAN REQUEST -->
  <div class="card" style="position: sticky; top: 80px;">
    <div class="card-header">
      <span style="font-size: 18px;">✍️</span>
      <div class="card-title">
        <?= $preloadProduct ? 'Request Barang Katalog' : 'Form Permintaan Barang' ?>
      </div>
    </div>

    <div class="card-body" style="padding: 20px;">
      <?php if ($preloadProduct): ?>
        <div style="background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: var(--radius-sm); padding: 10px 14px; margin-bottom: 16px; font-size: 12px; color: var(--navy-700);">
          Mengajukan barang dari katalog yang stoknya kosong: <strong><?= htmlspecialchars($preloadProduct['nama']) ?></strong>
          <a href="customer-request.php?new=1" style="color: var(--teal-600); display: block; margin-top: 4px; font-weight: 600;">(Ganti ke form request custom)</a>
        </div>
      <?php endif; ?>

      <form method="POST" action="customer-request.php" autocomplete="off">
        <input type="hidden" name="product_id" value="<?= htmlspecialchars($preloadProduct['id'] ?? '') ?>">

        <div class="form-group">
          <label class="form-label required">Nama Barang / Deskripsi</label>
          <input 
            type="text" 
            name="nama_barang" 
            class="form-control" 
            placeholder="Contoh: ROLLER CHAIN 80-2 SS JEPANG" 
            value="<?= htmlspecialchars($preloadProduct['nama'] ?? '') ?>"
            required
          >
        </div>

        <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label required">Jumlah (Qty)</label>
            <input 
              type="number" 
              name="qty" 
              class="form-control" 
              min="1" 
              step="any" 
              value="1" 
              required
            >
          </div>
          <div class="form-group">
            <label class="form-label required">Satuan</label>
            <input 
              type="text" 
              name="satuan" 
              class="form-control" 
              placeholder="PCS / BOX / UNIT" 
              value="<?= htmlspecialchars($preloadProduct['satuan'] ?? 'PCS') ?>" 
              required
            >
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Spesifikasi Teknis / Ukuran</label>
          <textarea 
            name="spesifikasi" 
            class="form-control" 
            rows="3" 
            placeholder="Contoh: Size ANSI 80-2, Material Stainless Steel SUS304, Brand Tsubaki / Daido..."
          ><?= htmlspecialchars($preloadProduct['spesifikasi'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Catatan Tambahan / Kebutuhan</label>
          <textarea 
            name="catatan" 
            class="form-control" 
            rows="2" 
            placeholder="Kebutuhan mendesak, target tanggal pemakaian, dll..."
          ></textarea>
        </div>

        <button type="submit" name="submit_request" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-weight: 700;">
          🚀 Kirim Request ke Admin
        </button>
      </form>
    </div>
  </div>

</div>

<?php require_once 'customer-footer.php'; ?>
