<?php
/**
 * customer-order-detail.php — Detail Pesanan, Tracking Pengiriman & Konfirmasi Bayar
 * Sesuai Sitemap 3.4 & Alur Bisnis Sistem
 */

$pageTitle = 'Detail Pesanan & Tracking';
$pageSubtitle = 'Informasi lengkap pemesanan, invoice, dan pelacakan pengiriman barang';
require_once 'customer-header.php';

$cId = $currentCust['id'];
$orderId = trim($_GET['id'] ?? '');

if (empty($orderId)) {
    echo "<div class='alert alert-error'>ID pesanan tidak valid. <a href='customer-orders.php'>Kembali ke riwayat pesanan</a></div>";
    require_once 'customer-footer.php';
    exit;
}

$order = dbRow("SELECT * FROM orders WHERE id = ? AND customer_id = ? LIMIT 1", [$orderId, $cId]);
if (!$order) {
    echo "<div class='alert alert-error'>Pesanan tidak ditemukan. <a href='customer-orders.php'>Kembali ke riwayat pesanan</a></div>";
    require_once 'customer-footer.php';
    exit;
}

// Handle konfirmasi pembayaran oleh customer
$alertMsg = '';
$alertType = '';
$hasPo = (bool)dbRow("SELECT 1 AS x FROM po WHERE order_id = ? LIMIT 1", [$orderId]);
$orderOpen = !in_array($order['status_pesanan'], ['dibatalkan'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $orderOpen) {
    // 1) Upload bukti pembayaran (DP / Term / Partial / Pelunasan)
    if (isset($_POST['submit_payment'])) {
        $tipe   = $_POST['tipe'] ?? 'pelunasan';
        $jumlah = (float)str_replace(['.', ','], ['', '.'], (string)($_POST['jumlah'] ?? '0'));
        $sisa   = (float)$order['total'] - (float)$order['total_dibayar'];
        [$path, $upErr] = saveUpload($_FILES['bukti'] ?? [], 'bukti', MIME_PROOF, 5 * 1024 * 1024);
        if (!in_array($tipe, ['dp', 'term', 'partial', 'pelunasan'], true)) {
            $alertMsg = 'Jenis pembayaran tidak valid.'; $alertType = 'error';
        } elseif ($jumlah <= 0) {
            $alertMsg = 'Jumlah pembayaran harus lebih dari 0.'; $alertType = 'error';
        } elseif ($jumlah > $sisa + 0.01) {
            $alertMsg = 'Jumlah melebihi sisa tagihan (Rp ' . number_format($sisa, 0, ',', '.') . ').'; $alertType = 'error';
        } elseif ($upErr) {
            $alertMsg = $upErr; $alertType = 'error';
        } elseif (!$path) {
            $alertMsg = 'Bukti transfer (foto/PDF) wajib diunggah.'; $alertType = 'error';
        } else {
            $catatan = 'An. ' . trim($_POST['nama_pengirim'] ?? '') . ' (' . trim($_POST['bank_pengirim'] ?? '') . '). ' . trim($_POST['catatan_bayar'] ?? '');
            dbRun("INSERT INTO payments (order_id, customer_id, tipe, jumlah, bukti_file, catatan) VALUES (?, ?, ?, ?, ?, ?)",
                [$orderId, $cId, $tipe, $jumlah, $path, $catatan]);
            dbRun("UPDATE orders SET waktu_bayar = NOW() WHERE id = ?", [$orderId]);
            recalcOrderPayment($orderId);
            notify('admin', null, "{$currentCust['nama']} mengunggah bukti pembayaran " . strtoupper($tipe) . " Rp " . number_format($jumlah, 0, ',', '.') . " untuk {$order['nomor_order']}.", 'admin-order-detail.php?id=' . $orderId);
            $alertMsg = 'Bukti pembayaran terkirim. Admin CV. Tidar Teknik akan memverifikasi.'; $alertType = 'success';
        }
    }
    // 2) Konfirmasi barang diterima
    if (isset($_POST['confirm_received']) && $order['status_pesanan'] === 'dikirim') {
        dbRun("UPDATE orders SET status_pesanan = 'selesai', tanggal_terima = NOW() WHERE id = ? AND customer_id = ?", [$orderId, $cId]);
        notify('admin', null, "{$currentCust['nama']} mengonfirmasi penerimaan pesanan {$order['nomor_order']}.", 'admin-order-detail.php?id=' . $orderId);
        $alertMsg = 'Terima kasih! Pesanan ditandai selesai.'; $alertType = 'success';
    }
    // 3) Pembatalan (hanya sebelum PO dibuat)
    if (isset($_POST['cancel_order']) && in_array($order['status_pesanan'], ['pending', 'diproses'], true) && !$hasPo) {
        $alasan = trim($_POST['alasan_batal'] ?? '');
        if ($alasan === '') {
            $alertMsg = 'Mohon isi alasan pembatalan.'; $alertType = 'error';
        } else {
            dbRun("UPDATE orders SET status_pesanan = 'dibatalkan', alasan_batal = ? WHERE id = ? AND customer_id = ?", [$alasan, $orderId, $cId]);
            notify('admin', null, "{$currentCust['nama']} membatalkan pesanan {$order['nomor_order']}: $alasan", 'admin-order-detail.php?id=' . $orderId);
            $alertMsg = 'Pesanan dibatalkan. Jika sudah ada pembayaran, admin akan memproses pengembalian dana.'; $alertType = 'success';
        }
    }
    $order = dbRow("SELECT * FROM orders WHERE id = ?", [$orderId]);
    $hasPo = (bool)dbRow("SELECT 1 AS x FROM po WHERE order_id = ? LIMIT 1", [$orderId]);
}

// Load items & pembayaran
$items = dbRows("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
$payments = dbRows("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC", [$orderId]);
$sisaTagihan = max(0.0, (float)$order['total'] - (float)$order['total_dibayar']);
$returnCount = (int)dbRow("SELECT COUNT(*) AS c FROM returns WHERE order_id = ?", [$orderId])['c'];

// Visual status tracker flags
$statusPesan = $order['status_pesanan'];
$statusBayar = $order['status_pembayaran'];

$step1 = true; // Pesanan dibuat
$step2 = in_array($statusBayar, ['sudah_bayar', 'terverifikasi', 'dp', 'term', 'partial', 'paid']); // Pembayaran
$step3 = in_array($statusPesan, ['diproses', 'dikirim', 'selesai']); // Diproses
$step4 = in_array($statusPesan, ['dikirim', 'selesai']); // Dikirim
$step5 = ($statusPesan === 'selesai'); // Selesai
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <a href="customer-orders.php" class="btn btn-ghost" style="font-size: 12.5px;">
    ← Kembali ke Daftar Pesanan
  </a>
  <div style="display: flex; gap: 10px;">
    <a href="customer-invoice.php?id=<?= urlencode($order['id']) ?>" target="_blank" class="btn btn-primary" style="font-size: 13px;">
      🖨️ Cetak / Unduh Invoice Resmi
    </a>
  </div>
</div>

<?php if ($alertMsg): ?>
  <div class="alert alert-<?= $alertType ?>" style="margin-bottom: 20px;">
    <span><?= $alertType === 'success' ? '✅' : '⚠️' ?></span> <?= $alertMsg ?>
  </div>
<?php endif; ?>

<!-- TRACKING CARD -->
<div class="card" style="margin-bottom: 24px; border-top: 4px solid var(--teal-500);">
  <div class="card-header" style="justify-content: space-between;">
    <div>
      <div style="font-size: 11px; font-weight: 700; color: var(--teal-600); text-transform: uppercase;">Tracking Status Pengiriman</div>
      <div class="card-title" style="margin-top: 2px;">Nomor Order: <?= htmlspecialchars($order['nomor_order']) ?></div>
    </div>
    <div>
      <?php if ($statusPesan === 'selesai'): ?>
        <span class="badge badge-green" style="font-size: 12px;">✅ Pesanan Selesai</span>
      <?php elseif ($statusPesan === 'dikirim'): ?>
        <span class="badge badge-blue" style="font-size: 12px;">🚚 Sedang Dikirim</span>
      <?php elseif ($statusPesan === 'diproses'): ?>
        <span class="badge badge-amber" style="font-size: 12px;">⚙️ Sedang Diproses</span>
      <?php else: ?>
        <span class="badge badge-amber" style="font-size: 12px;">⏳ Menunggu Pembayaran &amp; Proses</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="card-body" style="padding: 24px 20px;">
    <!-- PROGRESS TRACKER -->
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; text-align: center;">
      <div>
        <div style="width: 34px; height: 34px; border-radius: 50%; background: var(--em-500); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; margin: 0 auto 6px auto;">✓</div>
        <div style="font-size: 12px; font-weight: 700; color: var(--navy-800);">1. Pesanan Dibuat</div>
        <div style="font-size: 10.5px; color: var(--navy-400); margin-top: 2px;"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></div>
      </div>

      <div>
        <div style="width: 34px; height: 34px; border-radius: 50%; background: <?= $step2 ? 'var(--em-500)' : 'var(--navy-200)' ?>; color: <?= $step2 ? '#fff' : 'var(--navy-400)' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; margin: 0 auto 6px auto;">
          <?= $step2 ? '✓' : '2' ?>
        </div>
        <div style="font-size: 12px; font-weight: 700; color: <?= $step2 ? 'var(--navy-800)' : 'var(--navy-400)' ?>;">2. Pembayaran</div>
        <div style="font-size: 10.5px; color: var(--navy-400); margin-top: 2px;">
          <?= $statusBayar === 'terverifikasi' ? 'Terverifikasi' : ($statusBayar === 'sudah_bayar' ? 'Dikonfirmasi' : 'Menunggu') ?>
        </div>
      </div>

      <div>
        <div style="width: 34px; height: 34px; border-radius: 50%; background: <?= $step3 ? 'var(--em-500)' : 'var(--navy-200)' ?>; color: <?= $step3 ? '#fff' : 'var(--navy-400)' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; margin: 0 auto 6px auto;">
          <?= $step3 ? '✓' : '3' ?>
        </div>
        <div style="font-size: 12px; font-weight: 700; color: <?= $step3 ? 'var(--navy-800)' : 'var(--navy-400)' ?>;">3. Diproses</div>
        <div style="font-size: 10.5px; color: var(--navy-400); margin-top: 2px;">Pengadaan &amp; Packing</div>
      </div>

      <div>
        <div style="width: 34px; height: 34px; border-radius: 50%; background: <?= $step4 ? 'var(--teal-500)' : 'var(--navy-200)' ?>; color: <?= $step4 ? '#fff' : 'var(--navy-400)' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; margin: 0 auto 6px auto;">
          <?= $step4 ? '🚚' : '4' ?>
        </div>
        <div style="font-size: 12px; font-weight: 700; color: <?= $step4 ? 'var(--teal-600)' : 'var(--navy-400)' ?>;">4. Dikirim</div>
        <div style="font-size: 10.5px; color: var(--navy-400); margin-top: 2px;">
          <?= !empty($order['no_resi']) ? 'Resi: ' . htmlspecialchars($order['no_resi']) : ($step4 ? 'Kurir Jalan' : 'Menunggu') ?>
        </div>
      </div>

      <div>
        <div style="width: 34px; height: 34px; border-radius: 50%; background: <?= $step5 ? 'var(--em-500)' : 'var(--navy-200)' ?>; color: <?= $step5 ? '#fff' : 'var(--navy-400)' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; margin: 0 auto 6px auto;">
          <?= $step5 ? '✓' : '5' ?>
        </div>
        <div style="font-size: 12px; font-weight: 700; color: <?= $step5 ? 'var(--em-600)' : 'var(--navy-400)' ?>;">5. Selesai</div>
        <div style="font-size: 10.5px; color: var(--navy-400); margin-top: 2px;"><?= $step5 ? 'Diterima' : 'Tujuan' ?></div>
      </div>
    </div>
  </div>
</div>

<div class="cust-split cust-split-right">
  
  <!-- SISI KIRI: RINCIAN ITEM -->
  <div class="card">
    <div class="card-header">
      <span style="font-size: 18px;">📋</span>
      <div class="card-title">Rincian Komponen Pesanan</div>
    </div>

    <div class="table-wrap" style="border-radius:0;border:none;border-top:1px solid var(--navy-200);box-shadow:none;">
      <table>
        <thead>
          <tr>
            <th>Barang / Deskripsi</th>
            <th>Qty</th>
            <th>Harga Satuan</th>
            <th style="text-align: right;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td>
                <strong style="color: var(--navy-900);"><?= htmlspecialchars($it['nama_barang']) ?></strong>
                <?php if (!empty($it['spesifikasi'])): ?>
                  <div style="font-size: 11.5px; color: var(--navy-500); margin-top: 2px;">
                    <?= htmlspecialchars($it['spesifikasi']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td><strong><?= (float)$it['qty'] ?></strong> <?= htmlspecialchars($it['satuan']) ?></td>
              <td>Rp <?= number_format($it['harga_satuan'], 0, ',', '.') ?></td>
              <td style="text-align: right; font-weight: 700;">
                Rp <?= number_format($it['subtotal'], 0, ',', '.') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3" style="text-align: right; font-weight: 600;">Subtotal Produk:</td>
            <td style="text-align: right; font-weight: 700;">Rp <?= number_format($order['subtotal'], 0, ',', '.') ?></td>
          </tr>
          <tr>
            <td colspan="3" style="text-align: right; font-weight: 600;">Ongkos Kirim (<?= htmlspecialchars($order['ekspedisi']) ?>):</td>
            <td style="text-align: right; font-weight: 700;">Rp <?= number_format($order['ongkir'], 0, ',', '.') ?></td>
          </tr>
          <tr style="background: var(--navy-50);">
            <td colspan="3" style="text-align: right; font-weight: 800; font-size: 15px; color: var(--navy-900);">Total Tagihan:</td>
            <td style="text-align: right; font-weight: 800; font-size: 16px; color: var(--teal-600);">
              Rp <?= number_format($order['total'], 0, ',', '.') ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div style="padding: 20px; border-top: 1px solid var(--navy-200); background: var(--navy-50); font-size: 12.5px; color: var(--navy-600);">
      <strong>Alamat Pengiriman:</strong><br>
      <?= nl2br(htmlspecialchars($order['alamat_pengiriman'])) ?><br>
      <strong>Kota:</strong> <?= htmlspecialchars($order['kota_pengiriman']) ?> &nbsp;|&nbsp; <strong>Ekspedisi:</strong> <?= htmlspecialchars($order['ekspedisi']) ?>
      <?php if (!empty($order['catatan'])): ?>
        <div style="margin-top: 6px; font-style: italic; color: var(--navy-500);">
          Catatan: "<?= htmlspecialchars($order['catatan']) ?>"
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- SISI KANAN: STATUS PEMBAYARAN & KONFIRMASI -->
  <div style="display: flex; flex-direction: column; gap: 20px;">
    
    <div class="card">
      <div class="card-header">
        <span style="font-size: 18px;">💳</span>
        <div class="card-title">Informasi Pembayaran</div>
      </div>
      <div class="card-body" style="padding: 20px;">
        <div style="margin-bottom: 16px;">
          <div style="font-size: 11px; color: var(--navy-400); text-transform: uppercase;">Metode Pembayaran</div>
          <div style="font-size: 15px; font-weight: 700; color: var(--navy-900); margin-top: 2px;">
            <?= htmlspecialchars($order['metode_pembayaran']) ?>
          </div>
        </div>

        <div style="margin-bottom: 16px;">
          <div style="font-size: 11px; color: var(--navy-400); text-transform: uppercase;">Status Pembayaran</div>
          <div style="margin-top: 4px;">
            <?php if (in_array($order['status_pembayaran'], ['paid', 'terverifikasi'], true)): ?>
              <span class="badge badge-green" style="font-size: 12px;">🟢 Lunas (Paid)</span>
            <?php elseif ($order['status_pembayaran'] === 'dp'): ?>
              <span class="badge badge-blue" style="font-size: 12px;">🔵 DP Terverifikasi</span>
            <?php elseif ($order['status_pembayaran'] === 'term'): ?>
              <span class="badge badge-blue" style="font-size: 12px;">🔵 Term Terverifikasi</span>
            <?php elseif ($order['status_pembayaran'] === 'partial'): ?>
              <span class="badge badge-blue" style="font-size: 12px;">🔵 Dibayar Sebagian (Partial)</span>
            <?php elseif ($order['status_pembayaran'] === 'sudah_bayar'): ?>
              <span class="badge badge-blue" style="font-size: 12px;">🔵 Menunggu Verifikasi Admin</span>
            <?php elseif ($order['status_pembayaran'] === 'dibatalkan'): ?>
              <span class="badge badge-red" style="font-size: 12px;">🔴 Dibatalkan</span>
            <?php else: ?>
              <span class="badge badge-amber" style="font-size: 12px;">🟡 Menunggu Pembayaran</span>
            <?php endif; ?>
            <div style="font-size: 12px; color: var(--navy-600); margin-top: 6px;">
              Terbayar: <strong>Rp <?= number_format((float)$order['total_dibayar'], 0, ',', '.') ?></strong>
              &nbsp;•&nbsp; Sisa: <strong>Rp <?= number_format($sisaTagihan, 0, ',', '.') ?></strong>
            </div>
          </div>
        </div>

        <?php if ($order['status_pembayaran'] !== 'dibatalkan' && $orderOpen && $sisaTagihan > 0.01): ?>
          <div style="background: rgba(14, 165, 233, 0.08); border: 1px dashed rgba(56, 189, 248, 0.4); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 18px; font-size: 12px;">
            <strong>Silakan transfer ke:</strong><br>
            Bank BCA: <strong>123-456-7890</strong><br>
            a/n <strong>CV. TIDAR TEKNIK</strong><br>
            Sisa tagihan: <strong style="color:var(--teal-600); font-size:14px;">Rp <?= number_format($sisaTagihan, 0, ',', '.') ?></strong>
          </div>

          <!-- FORM UPLOAD BUKTI PEMBAYARAN -->
          <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
              <label class="form-label required">Jenis Pembayaran</label>
              <select name="tipe" class="form-control" required>
                <option value="dp">DP (Uang Muka)</option>
                <option value="term">Term (Termin)</option>
                <option value="partial">Partial (Sebagian)</option>
                <option value="pelunasan" selected>Pelunasan</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label required">Jumlah Ditransfer (Rp)</label>
              <input type="number" name="jumlah" class="form-control" min="1" step="1" value="<?= (int)round($sisaTagihan) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label required">Nama Pemilik Rekening / Pengirim</label>
              <input type="text" name="nama_pengirim" class="form-control" placeholder="Contoh: Hendra / PT. Indo Mina" required>
            </div>
            <div class="form-group">
              <label class="form-label required">Dari Bank Mana</label>
              <input type="text" name="bank_pengirim" class="form-control" placeholder="Contoh: BCA / Mandiri / BNI" required>
            </div>
            <div class="form-group">
              <label class="form-label required">Bukti Transfer (JPG/PNG/WebP/PDF, maks 5 MB)</label>
              <input type="file" name="bukti" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf" required>
            </div>
            <div class="form-group">
              <label class="form-label">Catatan</label>
              <input type="text" name="catatan_bayar" class="form-control" placeholder="Contoh: Transfer m-banking jam 14:30">
            </div>

            <button type="submit" name="submit_payment" class="btn btn-success" style="width: 100%; justify-content: center; padding: 12px; font-weight: 700;">
              📤 Kirim Bukti Pembayaran
            </button>
          </form>
        <?php endif; ?>

        <?php if ($payments): ?>
          <div style="margin-top: 18px; font-size: 12.5px;">
            <strong>Riwayat Pembayaran</strong>
            <?php foreach ($payments as $pm): ?>
              <div style="border-top: 1px solid var(--navy-200); padding: 8px 0;">
                <span class="badge badge-gray" style="font-size:10px"><?= h(strtoupper($pm['tipe'])) ?></span>
                <strong>Rp <?= number_format((float)$pm['jumlah'], 0, ',', '.') ?></strong>
                <?php if ($pm['status'] === 'terverifikasi'): ?><span class="badge badge-green" style="font-size:10px">Terverifikasi</span>
                <?php elseif ($pm['status'] === 'ditolak'): ?><span class="badge badge-red" style="font-size:10px">Ditolak</span>
                <?php else: ?><span class="badge badge-amber" style="font-size:10px">Menunggu verifikasi</span><?php endif; ?>
                <div style="color: var(--navy-500); font-size: 11px;">
                  <?= h(date('d/m/Y H:i', strtotime($pm['created_at']))) ?>
                  <?php if ($pm['bukti_file']): ?> • <a href="download.php?t=payment&id=<?= (int)$pm['id'] ?>" target="_blank" rel="noopener">Lihat bukti</a><?php endif; ?>
                </div>
                <?php if ($pm['catatan_admin']): ?><div style="font-size: 11px; color: var(--navy-600);">Admin: <?= h($pm['catatan_admin']) ?></div><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>


    <?php if ($order['status_pesanan'] === 'dikirim'): ?>
    <div class="card">
      <div class="card-header"><span style="font-size: 18px;">📦</span><div class="card-title">Konfirmasi Penerimaan</div></div>
      <div class="card-body" style="padding: 20px; font-size: 12.5px;">
        <p style="margin: 0 0 12px 0;">Sudah menerima barang? Konfirmasi agar pesanan selesai. Bila tidak dikonfirmasi, pesanan otomatis selesai <?= (int)AUTO_COMPLETE_DAYS ?> hari setelah dikirim.</p>
        <form method="POST" onsubmit="return confirm('Konfirmasi barang sudah diterima dengan baik?')">
          <button type="submit" name="confirm_received" class="btn btn-success" style="width: 100%; justify-content: center;">✅ Barang Sudah Diterima</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <?php if (in_array($order['status_pesanan'], ['pending', 'diproses'], true) && !$hasPo): ?>
    <div class="card">
      <div class="card-header"><span style="font-size: 18px;">🚫</span><div class="card-title">Batalkan Pesanan</div></div>
      <div class="card-body" style="padding: 20px; font-size: 12.5px;">
        <p style="margin: 0 0 12px 0;">Pesanan masih bisa dibatalkan selama belum diteruskan ke supplier (PO belum dibuat).</p>
        <form method="POST" onsubmit="return confirm('Batalkan pesanan ini?')">
          <textarea name="alasan_batal" class="form-control" rows="2" placeholder="Alasan pembatalan" required></textarea>
          <button type="submit" name="cancel_order" class="btn btn-danger" style="width: 100%; justify-content: center; margin-top: 10px;">❌ Batalkan Pesanan</button>
        </form>
      </div>
    </div>
    <?php elseif ($order['status_pesanan'] === 'dibatalkan'): ?>
    <div class="alert alert-error"><span>❌</span> Pesanan dibatalkan<?= $order['alasan_batal'] ? ': ' . h($order['alasan_batal']) : '' ?></div>
    <?php endif; ?>

    <?php if (in_array($order['status_pesanan'], ['dikirim', 'selesai'], true)): ?>
    <div class="card">
      <div class="card-header"><span style="font-size: 18px;">↩️</span><div class="card-title">Retur Barang</div></div>
      <div class="card-body" style="padding: 20px; font-size: 12.5px;">
        <p style="margin: 0 0 12px 0;">Barang rusak atau tidak sesuai? Ajukan retur lengkap dengan foto/video.<?= $returnCount ? " (Sudah ada $returnCount pengajuan.)" : '' ?></p>
        <a href="customer-return-new.php?order_id=<?= urlencode($order['id']) ?>" class="btn btn-warning" style="width: 100%; justify-content: center;">↩️ Ajukan Retur</a>
      </div>
    </div>
    <?php endif; ?>

  </div>

</div>

<?php require_once 'customer-footer.php'; ?>
