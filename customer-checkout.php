<?php
/**
 * customer-checkout.php — Halaman Checkout & Pemilihan Pembayaran (Fase 2)
 * Sesuai Sitemap 3.3 Fase 2: Checkout -> Pilih Metode Bayar -> Bayar -> Konfirmasi
 */

$pageTitle = 'Checkout Pesanan';
$pageSubtitle = 'Lengkapi alamat pengiriman dan pilih metode pembayaran';
require_once 'customer-header.php';

$cId = $currentCust['id'];
$errorMsg = '';

// Ambil sumber item: dari produk ready stock ATAU dari approved request
$productId = trim($_GET['product_id'] ?? '');
$requestId = trim($_GET['request_id'] ?? '');

$checkoutItem = null;
$sourceType   = ''; // 'product' atau 'request'

if (!empty($requestId)) {
    $cleanReqId = mysqli_real_escape_string($conn, $requestId);
    $resR = mysqli_query($conn, "SELECT * FROM product_requests WHERE id = '$cleanReqId' AND customer_id = '$cId' LIMIT 1");
    if ($resR && $rowR = mysqli_fetch_assoc($resR)) {
        if ($rowR['status'] !== 'approved') {
            echo "<div class='alert alert-error'>Permintaan barang ini belum disetujui atau sudah diproses. <a href='customer-request.php'>Lihat riwayat request</a></div>";
            require_once 'customer-footer.php';
            exit;
        }
        $sourceType = 'request';
        $checkoutItem = [
            'request_id'   => $rowR['id'],
            'product_id'   => $rowR['product_id'],
            'nama_barang'  => $rowR['nama_barang'],
            'spesifikasi'  => $rowR['spesifikasi'],
            'qty'          => (float)$rowR['qty'],
            'satuan'       => $rowR['satuan'],
            'harga_satuan' => (float)$rowR['harga_disetujui'],
            'subtotal'     => (float)$rowR['qty'] * (float)$rowR['harga_disetujui'],
            'catatan'      => $rowR['catatan_admin']
        ];
    }
} elseif (!empty($productId)) {
    $cleanPId = mysqli_real_escape_string($conn, $productId);
    $resP = mysqli_query($conn, "SELECT * FROM products WHERE id = '$cleanPId' AND is_active = 1 LIMIT 1");
    if ($resP && $rowP = mysqli_fetch_assoc($resP)) {
        if ($rowP['stok'] <= 0) {
            echo "<div class='alert alert-error'>Maaf, stok produk ini sedang kosong. Silakan <a href='customer-request.php?product_id={$rowP['id']}'>ajukan permintaan barang</a>.</div>";
            require_once 'customer-footer.php';
            exit;
        }
        $sourceType = 'product';
        $reqQty = max(1, min((int)($_GET['qty'] ?? 1), $rowP['stok']));
        $checkoutItem = [
            'request_id'   => null,
            'product_id'   => $rowP['id'],
            'nama_barang'  => $rowP['nama'],
            'spesifikasi'  => $rowP['spesifikasi'],
            'qty'          => (float)$reqQty,
            'satuan'       => $rowP['satuan'],
            'harga_satuan' => (float)$rowP['harga'],
            'subtotal'     => (float)$reqQty * (float)$rowP['harga'],
            'max_stok'     => $rowP['stok'],
            'catatan'      => ''
        ];
    }
}

if (!$checkoutItem) {
    echo "<div class='alert alert-error'>Pilihan barang tidak ditemukan. Silakan pilih produk dari <a href='customer-products.php'>Marketplace</a> atau <a href='customer-request.php'>Request yang disetujui</a>.</div>";
    require_once 'customer-footer.php';
    exit;
}

// Handle submit order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    $inputQty    = (float)($_POST['qty'] ?? $checkoutItem['qty']);
    $alamat      = trim($_POST['alamat_pengiriman'] ?? '');
    $kota        = trim($_POST['kota_pengiriman'] ?? '');
    $ekspedisi   = trim($_POST['ekspedisi'] ?? 'Ekspedisi Rekanan CV. Tidar Teknik');
    $metodeBayar = trim($_POST['metode_pembayaran'] ?? '');
    $catatan     = trim($_POST['catatan'] ?? '');

    if (empty($alamat) || empty($kota) || empty($metodeBayar)) {
        $errorMsg = 'Harap lengkapi alamat pengiriman, kota, dan metode pembayaran.';
    } elseif ($inputQty <= 0) {
        $errorMsg = 'Jumlah barang tidak valid.';
    } elseif ($sourceType === 'product' && $inputQty > $checkoutItem['max_stok']) {
        $errorMsg = "Jumlah melebihi stok yang tersedia ({$checkoutItem['max_stok']} {$checkoutItem['satuan']}).";
    } else {
        // Hitung ulang total
        $subtotal = $inputQty * $checkoutItem['harga_satuan'];
        $ongkir = ($kota === 'SURABAYA' || $kota === 'MALANG') ? 0 : 50000;
        $total = $subtotal + $ongkir;

        $dateCode = date('Ymd');
        $rnd = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        $nomorOrder = "ORD-{$dateCode}-{$rnd}";
        $newOrderId = 'ord_' . substr(md5(uniqid(mt_rand(), true)), 0, 10);

        $cleanNomor = mysqli_real_escape_string($conn, $nomorOrder);
        $cleanAlamat= mysqli_real_escape_string($conn, $alamat);
        $cleanKota  = mysqli_real_escape_string($conn, strtoupper($kota));
        $cleanEksp  = mysqli_real_escape_string($conn, $ekspedisi);
        $cleanMetode= mysqli_real_escape_string($conn, $metodeBayar);
        $cleanCat   = mysqli_real_escape_string($conn, $catatan);

        // Insert ke tabel orders
        $insOrder = "INSERT INTO orders (id, nomor_order, customer_id, tanggal, subtotal, ongkir, total, alamat_pengiriman, kota_pengiriman, ekspedisi, metode_pembayaran, status_pembayaran, status_pesanan, catatan) 
                     VALUES ('$newOrderId', '$cleanNomor', '$cId', CURDATE(), $subtotal, $ongkir, $total, '$cleanAlamat', '$cleanKota', '$cleanEksp', '$cleanMetode', 'menunggu_pembayaran', 'pending', '$cleanCat')";

        if (mysqli_query($conn, $insOrder)) {
            // Insert order item
            $pIdVal = $checkoutItem['product_id'] ? "'" . mysqli_real_escape_string($conn, $checkoutItem['product_id']) . "'" : "NULL";
            $rIdVal = $checkoutItem['request_id'] ? "'" . mysqli_real_escape_string($conn, $checkoutItem['request_id']) . "'" : "NULL";
            $cleanBarang = mysqli_real_escape_string($conn, $checkoutItem['nama_barang']);
            $cleanSat = mysqli_real_escape_string($conn, $checkoutItem['satuan']);
            $cleanSpec = mysqli_real_escape_string($conn, $checkoutItem['spesifikasi'] ?? '');
            $hargaSat = $checkoutItem['harga_satuan'];

            $insItem = "INSERT INTO order_items (order_id, product_id, request_id, nama_barang, qty, satuan, harga_satuan, subtotal, spesifikasi) 
                        VALUES ('$newOrderId', $pIdVal, $rIdVal, '$cleanBarang', $inputQty, '$cleanSat', $hargaSat, $subtotal, '$cleanSpec')";
            mysqli_query($conn, $insItem);

            // Update stok jika dari produk ready
            if ($sourceType === 'product' && !empty($checkoutItem['product_id'])) {
                $pid = mysqli_real_escape_string($conn, $checkoutItem['product_id']);
                mysqli_query($conn, "UPDATE products SET stok = GREATEST(0, stok - $inputQty) WHERE id = '$pid'");
            }

            // Update status request jika dari approved request
            if ($sourceType === 'request' && !empty($checkoutItem['request_id'])) {
                $rid = mysqli_real_escape_string($conn, $checkoutItem['request_id']);
                mysqli_query($conn, "UPDATE product_requests SET status = 'ordered' WHERE id = '$rid'");
            }

            // Redirect ke halaman detail pesanan & pembayaran
            header("Location: customer-order-detail.php?id={$newOrderId}&pay=1");
            exit;
        } else {
            $errorMsg = 'Gagal memproses pesanan: ' . mysqli_error($conn);
        }
    }
}
?>

<div style="margin-bottom: 20px;">
  <a href="customer-products.php" class="btn btn-ghost" style="padding: 6px 12px; font-size: 12.5px;">
    ← Kembali
  </a>
</div>

<?php if ($errorMsg): ?>
  <div class="alert alert-error" style="margin-bottom: 20px;">
    <span>⚠️</span> <?= $errorMsg ?>
  </div>
<?php endif; ?>

<form method="POST" action="" id="checkout-form">
  <div style="display: grid; grid-template-columns: 1fr 420px; gap: 24px; align-items: start;">
    
    <!-- SISI KIRI: ALAMAT, PENGIRIMAN & METODE BAYAR -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
      
      <!-- CARD 1: ALAMAT PENGIRIMAN -->
      <div class="card">
        <div class="card-header">
          <span style="font-size: 18px;">📍</span>
          <div class="card-title">1. Alamat Pengiriman</div>
        </div>
        <div class="card-body" style="padding: 20px;">
          <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="form-group">
              <label class="form-label required">Nama Penerima / Perusahaan</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($currentCust['nama']) ?>" readonly style="background: var(--navy-50);">
            </div>
            <div class="form-group">
              <label class="form-label required">Kontak / No. Telepon / WA</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($currentCust['telepon'] ?: '-') ?>" readonly style="background: var(--navy-50);">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label required">Kota Tujuan</label>
            <input 
              type="text" 
              name="kota_pengiriman" 
              id="kota_pengiriman"
              class="form-control" 
              placeholder="Contoh: SURABAYA / MALANG / SIDOARJO" 
              value="<?= htmlspecialchars($currentCust['kota'] ?: 'SURABAYA') ?>" 
              required
              onchange="recalcTotals()"
            >
            <span style="font-size: 11px; color: var(--navy-400); margin-top: 3px; display: block;">
              Gratis Ongkir untuk area Malang &amp; Surabaya (diantar armada rekanan dropship CV. Tidar Teknik).
            </span>
          </div>

          <div class="form-group">
            <label class="form-label required">Alamat Lengkap Tujuan</label>
            <textarea 
              name="alamat_pengiriman" 
              class="form-control" 
              rows="3" 
              placeholder="Nama jalan, nomor gudang/pabrik, patokan..." 
              required
            ><?= htmlspecialchars($currentCust['alamat'] ?: '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- CARD 2: METODE PENGIRIMAN -->
      <div class="card">
        <div class="card-header">
          <span style="font-size: 18px;">🚚</span>
          <div class="card-title">2. Pilihan Pengiriman</div>
        </div>
        <div class="card-body" style="padding: 20px;">
          <div class="form-group">
            <label class="form-label required">Opsi Ekspedisi / Kurir</label>
            <select name="ekspedisi" class="form-control" required>
              <option value="Ekspedisi Rekanan CV. Tidar Teknik">Ekspedisi Rekanan CV. Tidar Teknik (Rekomendasi Dropship)</option>
              <option value="JNE Trucking (JTR)">JNE Trucking (JTR) - Kargo Komponen Berat</option>
              <option value="Dakota Cargo">Dakota Cargo</option>
              <option value="Ambil Sendiri di Gudang">Ambil Sendiri di Gudang Malang/Surabaya</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Catatan Tambahan untuk Pengiriman</label>
            <input type="text" name="catatan" class="form-control" placeholder="Contoh: Kirim jam kerja, packing kayu, dll.">
          </div>
        </div>
      </div>

      <!-- CARD 3: METODE PEMBAYARAN (SIMULASI / MANUAL SESUAI OUT-OF-SCOPE) -->
      <div class="card">
        <div class="card-header">
          <span style="font-size: 18px;">💳</span>
          <div class="card-title">3. Metode Pembayaran</div>
        </div>
        <div class="card-body" style="padding: 20px;">
          <p style="font-size: 12.5px; color: var(--navy-500); margin: 0 0 16px 0;">
            Pilih rekening transfer CV. Tidar Teknik. Setelah checkout, Anda dapat mengunggah bukti transfer atau melakukan konfirmasi pembayaran.
          </p>

          <div style="display: flex; flex-direction: column; gap: 10px;">
            <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1.5px solid var(--navy-200); border-radius: var(--radius-sm); cursor: pointer; background: #fff;">
              <input type="radio" name="metode_pembayaran" value="Transfer Bank BCA" checked style="accent-color: var(--teal-500); width: 18px; height: 18px;">
              <div style="flex: 1;">
                <strong style="color: var(--navy-900); font-size: 13.5px;">Transfer Bank BCA</strong>
                <div style="font-size: 11.5px; color: var(--navy-500); margin-top: 2px;">No. Rek: <strong>123-456-7890</strong> a/n CV. TIDAR TEKNIK</div>
              </div>
              <span style="font-size: 20px;">🏦</span>
            </label>

            <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1.5px solid var(--navy-200); border-radius: var(--radius-sm); cursor: pointer; background: #fff;">
              <input type="radio" name="metode_pembayaran" value="Transfer Bank Mandiri" style="accent-color: var(--teal-500); width: 18px; height: 18px;">
              <div style="flex: 1;">
                <strong style="color: var(--navy-900); font-size: 13.5px;">Transfer Bank Mandiri</strong>
                <div style="font-size: 11.5px; color: var(--navy-500); margin-top: 2px;">No. Rek: <strong>098-765-4321</strong> a/n CV. TIDAR TEKNIK</div>
              </div>
              <span style="font-size: 20px;">🏦</span>
            </label>

            <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1.5px solid var(--navy-200); border-radius: var(--radius-sm); cursor: pointer; background: #fff;">
              <input type="radio" name="metode_pembayaran" value="Virtual Account / Bank Lain" style="accent-color: var(--teal-500); width: 18px; height: 18px;">
              <div style="flex: 1;">
                <strong style="color: var(--navy-900); font-size: 13.5px;">Transfer Antar Bank Lain / VA</strong>
                <div style="font-size: 11.5px; color: var(--navy-500); margin-top: 2px;">Dukungan BI-FAST dari seluruh bank di Indonesia</div>
              </div>
              <span style="font-size: 20px;">📱</span>
            </label>
          </div>
        </div>
      </div>

    </div>

    <!-- SISI KANAN: RINGKASAN PESANAN & TOTAL -->
    <div class="card" style="position: sticky; top: 80px;">
      <div class="card-header">
        <span style="font-size: 18px;">🧾</span>
        <div class="card-title">Ringkasan Pesanan</div>
      </div>
      <div class="card-body" style="padding: 20px;">
        
        <!-- ITEM PREVIEW -->
        <div style="background: var(--navy-50); border: 1px solid var(--navy-200); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 18px;">
          <div style="font-size: 11px; font-weight: 700; color: var(--teal-600); text-transform: uppercase;">
            <?= $sourceType === 'request' ? '📋 Dari Request yang Disetujui' : '🛍️ Ready Stock Katalog' ?>
          </div>
          <div style="font-size: 14.5px; font-weight: 800; color: var(--navy-900); margin-top: 4px;">
            <?= htmlspecialchars($checkoutItem['nama_barang']) ?>
          </div>
          <?php if (!empty($checkoutItem['spesifikasi'])): ?>
            <div style="font-size: 11.5px; color: var(--navy-500); margin-top: 3px;">
              <?= htmlspecialchars($checkoutItem['spesifikasi']) ?>
            </div>
          <?php endif; ?>

          <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; border-top: 1px dashed var(--navy-200); padding-top: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <label for="input-qty" style="font-size: 12px; margin: 0; text-transform: none;">Jumlah:</label>
              <?php if ($sourceType === 'product'): ?>
                <input 
                  type="number" 
                  name="qty" 
                  id="input-qty" 
                  value="<?= $checkoutItem['qty'] ?>" 
                  min="1" 
                  max="<?= $checkoutItem['max_stok'] ?>" 
                  class="form-control" 
                  style="width: 70px; padding: 4px 8px; font-weight: 700; text-align: center;"
                  onchange="recalcTotals()"
                >
              <?php else: ?>
                <strong><?= $checkoutItem['qty'] ?></strong>
                <input type="hidden" name="qty" id="input-qty" value="<?= $checkoutItem['qty'] ?>">
              <?php endif; ?>
              <span style="font-size: 12px; color: var(--navy-600);"><?= htmlspecialchars($checkoutItem['satuan']) ?></span>
            </div>

            <div style="text-align: right;">
              <span style="font-size: 11px; color: var(--navy-400);">@ Rp <?= number_format($checkoutItem['harga_satuan'], 0, ',', '.') ?></span>
            </div>
          </div>
        </div>

        <!-- KALKULASI HARGA -->
        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px; color: var(--navy-700); margin-bottom: 16px;">
          <div style="display: flex; justify-content: space-between;">
            <span>Subtotal Produk:</span>
            <strong id="label-subtotal">Rp <?= number_format($checkoutItem['subtotal'], 0, ',', '.') ?></strong>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span>Estimasi Ongkos Kirim:</span>
            <strong id="label-ongkir" style="color: var(--em-600);">Gratis (Malang/Surabaya)</strong>
          </div>
          <div style="border-top: 1.5px solid var(--navy-200); margin-top: 6px; padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 14px; font-weight: 700; color: var(--navy-900);">Total Pembayaran:</span>
            <span style="font-size: 20px; font-weight: 800; color: var(--teal-600);" id="label-total">
              Rp <?= number_format($checkoutItem['subtotal'], 0, ',', '.') ?>
            </span>
          </div>
        </div>

        <button type="submit" name="submit_order" class="btn btn-success" style="width: 100%; justify-content: center; padding: 14px; font-size: 15px; font-weight: 700; box-shadow: 0 4px 14px rgba(16,185,129,.35);">
          ✅ Buat Pesanan Sekarang
        </button>

        <div style="text-align: center; margin-top: 12px; font-size: 11px; color: var(--navy-400);">
          🔒 Transaksi aman • Invoice resmi diterbitkan oleh CV. Tidar Teknik
        </div>
      </div>
    </div>

  </div>
</form>

<script>
  const unitPrice = <?= $checkoutItem['harga_satuan'] ?>;

  function recalcTotals() {
    const qtyInput = document.getElementById('input-qty');
    const kotaInput = document.getElementById('kota_pengiriman');
    const qty = parseFloat(qtyInput ? qtyInput.value : 1) || 1;
    const kota = (kotaInput ? kotaInput.value : '').toUpperCase().trim();

    const subtotal = qty * unitPrice;
    let ongkir = 0;
    if (kota === 'SURABAYA' || kota === 'MALANG' || kota === '') {
      ongkir = 0;
      document.getElementById('label-ongkir').textContent = 'Gratis (Malang/Surabaya)';
      document.getElementById('label-ongkir').style.color = 'var(--em-600)';
    } else {
      ongkir = 50000;
      document.getElementById('label-ongkir').textContent = 'Rp 50.000 (Luar Area)';
      document.getElementById('label-ongkir').style.color = 'var(--navy-700)';
    }

    const grandTotal = subtotal + ongkir;
    document.getElementById('label-subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
    document.getElementById('label-total').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
  }
</script>

<?php require_once 'customer-footer.php'; ?>
