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

$cleanOId = mysqli_real_escape_string($conn, $orderId);
$oRes = mysqli_query($conn, "SELECT * FROM orders WHERE id = '$cleanOId' AND customer_id = '$cId' LIMIT 1");
if (!$oRes || mysqli_num_rows($oRes) == 0) {
    echo "<div class='alert alert-error'>Pesanan tidak ditemukan. <a href='customer-orders.php'>Kembali ke riwayat pesanan</a></div>";
    require_once 'customer-footer.php';
    exit;
}

$order = mysqli_fetch_assoc($oRes);

// Handle konfirmasi pembayaran oleh customer
$alertMsg = '';
$alertType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $namaPengirim = trim($_POST['nama_pengirim'] ?? '');
    $bankPengirim = trim($_POST['bank_pengirim'] ?? '');
    $catatanBayar = trim($_POST['catatan_bayar'] ?? '');

    $infoBayar = "Konfirmasi Customer: An. {$namaPengirim} ({$bankPengirim}). " . $catatanBayar;
    $cleanInfo = mysqli_real_escape_string($conn, $infoBayar);

    $upd = "UPDATE orders SET 
                status_pembayaran = 'sudah_bayar',
                waktu_bayar = NOW(),
                bukti_transfer = '$cleanInfo'
            WHERE id = '$cleanOId' AND customer_id = '$cId'";
    if (mysqli_query($conn, $upd)) {
        $alertMsg = 'Konfirmasi pembayaran berhasil dikirim. Admin CV. Tidar Teknik akan segera memverifikasi.';
        $alertType = 'success';
        // Refresh data order
        $order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = '$cleanOId' LIMIT 1"));
    } else {
        $alertMsg = 'Gagal menyimpan konfirmasi pembayaran: ' . mysqli_error($conn);
        $alertType = 'error';
    }
}

// Load items
$iRes = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = '$cleanOId'");
$items = mysqli_fetch_all($iRes, MYSQLI_ASSOC);

// Visual status tracker flags
$statusPesan = $order['status_pesanan'];
$statusBayar = $order['status_pembayaran'];

$step1 = true; // Pesanan dibuat
$step2 = in_array($statusBayar, ['sudah_bayar', 'terverifikasi']); // Pembayaran
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

<div style="display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start;">
  
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
            <?php if ($order['status_pembayaran'] === 'terverifikasi'): ?>
              <span class="badge badge-green" style="font-size: 12px;">🟢 Lunas / Terverifikasi</span>
            <?php elseif ($order['status_pembayaran'] === 'sudah_bayar'): ?>
              <span class="badge badge-blue" style="font-size: 12px;">🔵 Menunggu Verifikasi Admin</span>
            <?php else: ?>
              <span class="badge badge-amber" style="font-size: 12px;">🟡 Menunggu Pembayaran</span>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($order['status_pembayaran'] === 'menunggu_pembayaran'): ?>
          <div style="background: rgba(14, 165, 233, 0.08); border: 1px dashed rgba(56, 189, 248, 0.4); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 18px; font-size: 12px;">
            <strong>Silakan transfer total pembayaran ke:</strong><br>
            Bank BCA: <strong>123-456-7890</strong><br>
            Bank Mandiri: <strong>098-765-4321</strong><br>
            a/n <strong>CV. TIDAR TEKNIK</strong><br>
            Jumlah: <strong style="color:var(--teal-600); font-size:14px;">Rp <?= number_format($order['total'], 0, ',', '.') ?></strong>
          </div>

          <!-- FORM KONFIRMASI PEMBAYARAN -->
          <form method="POST" action="">
            <div class="form-group">
              <label class="form-label required">Nama Pemilik Rekening / Pengirim</label>
              <input type="text" name="nama_pengirim" class="form-control" placeholder="Contoh: Hendra / PT. Indo Mina" required>
            </div>
            <div class="form-group">
              <label class="form-label required">Dari Bank Mana</label>
              <input type="text" name="bank_pengirim" class="form-control" placeholder="Contoh: BCA / Mandiri / BNI" required>
            </div>
            <div class="form-group">
              <label class="form-label">Nomor Referensi / Catatan</label>
              <input type="text" name="catatan_bayar" class="form-control" placeholder="Contoh: Transfer m-banking jam 14:30">
            </div>

            <button type="submit" name="confirm_payment" class="btn btn-success" style="width: 100%; justify-content: center; padding: 12px; font-weight: 700;">
              ✅ Konfirmasi Saya Sudah Bayar
            </button>
          </form>
        <?php else: ?>
          <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: var(--radius-sm); padding: 14px; font-size: 12.5px; color: var(--navy-800);">
            <strong>Data Konfirmasi:</strong><br>
            <?= nl2br(htmlspecialchars($order['bukti_transfer'] ?: 'Pembayaran telah dikonfirmasi.')) ?>
            <?php if (!empty($order['waktu_bayar'])): ?>
              <div style="font-size: 11px; color: var(--navy-500); margin-top: 4px;">
                Waktu konfirmasi: <?= date('d/m/Y H:i', strtotime($order['waktu_bayar'])) ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>

<?php require_once 'customer-footer.php'; ?>
