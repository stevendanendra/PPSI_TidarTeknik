<?php
/**
 * customer-checkout.php — Checkout & Pemilihan Pembayaran
 * Sumber item: keranjang (?cart=1, multi-produk), request disetujui (?request_id=), atau produk langsung (?product_id=).
 * Stok tidak ditampilkan/dicek (sesuai PMP); ketersediaan dikonfirmasi admin ke supplier.
 */

$pageTitle = 'Checkout Pesanan';
$pageSubtitle = 'Lengkapi alamat pengiriman dan pilih metode pembayaran';
require_once 'customer-header.php';

$cId = $currentCust['id'];
$errorMsg = '';

$cartMode  = isset($_GET['cart']) || !empty($_POST['cart']);
$productId = trim($_GET['product_id'] ?? '');
$requestId = trim($_GET['request_id'] ?? '');

$items = []; // item: request_id, product_id, nama_barang, spesifikasi, satuan, harga_satuan, qty, qty_editable
$sourceType = '';

if ($cartMode) {
    $sourceType = 'cart';
    $rows = dbRows("SELECT c.product_id, c.qty, p.nama, p.spesifikasi, p.satuan, p.harga
                    FROM cart_items c JOIN products p ON p.id = c.product_id AND p.is_active = 1
                    WHERE c.customer_id = ? ORDER BY c.id", [$cId]);
    foreach ($rows as $r) {
        $items[] = ['request_id' => null, 'product_id' => $r['product_id'], 'nama_barang' => $r['nama'],
                    'spesifikasi' => $r['spesifikasi'], 'satuan' => $r['satuan'], 'harga_satuan' => (float)$r['harga'],
                    'qty' => (float)$r['qty'], 'qty_editable' => true];
    }
} elseif ($requestId !== '') {
    $rowR = dbRow("SELECT * FROM product_requests WHERE id = ? AND customer_id = ?", [$requestId, $cId]);
    if ($rowR) {
        if ($rowR['status'] !== 'approved') {
            echo "<div class='alert alert-error'>Permintaan barang ini belum disetujui atau sudah diproses. <a href='customer-request.php'>Lihat riwayat request</a></div>";
            require_once 'customer-footer.php';
            exit;
        }
        $sourceType = 'request';
        $items[] = ['request_id' => $rowR['id'], 'product_id' => $rowR['product_id'], 'nama_barang' => $rowR['nama_barang'],
                    'spesifikasi' => $rowR['spesifikasi'], 'satuan' => $rowR['satuan'], 'harga_satuan' => (float)$rowR['harga_disetujui'],
                    'qty' => (float)$rowR['qty'], 'qty_editable' => false];
    }
} elseif ($productId !== '') {
    $rowP = activeProduct($productId);
    if ($rowP) {
        $sourceType = 'product';
        $items[] = ['request_id' => null, 'product_id' => $rowP['id'], 'nama_barang' => $rowP['nama'],
                    'spesifikasi' => $rowP['spesifikasi'], 'satuan' => $rowP['satuan'], 'harga_satuan' => (float)$rowP['harga'],
                    'qty' => (float)max(1, (int)($_GET['qty'] ?? 1)), 'qty_editable' => true];
    }
}

if (!$items) {
    echo "<div class='alert alert-error'>Belum ada barang untuk di-checkout. Pilih produk dari <a href='customer-products.php'>Marketplace</a>, buka <a href='customer-cart.php'>Keranjang</a>, atau pakai <a href='customer-request.php'>Request yang disetujui</a>.</div>";
    require_once 'customer-footer.php';
    exit;
}

// Handle submit order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    $alamat      = trim($_POST['alamat_pengiriman'] ?? '');
    $kota        = trim($_POST['kota_pengiriman'] ?? '');
    $ekspedisi   = trim($_POST['ekspedisi'] ?? 'Ekspedisi Rekanan CV. Tidar Teknik');
    $metodeBayar = trim($_POST['metode_pembayaran'] ?? '');
    $catatan     = trim($_POST['catatan'] ?? '');

    $subtotal = 0.0;
    $qtyOk = true;
    foreach ($items as $i => $it) {
        if ($it['qty_editable']) {
            $q = (float)($_POST['qty'][$it['product_id']] ?? $it['qty']);
            if ($q <= 0) $qtyOk = false;
            $items[$i]['qty'] = $q;
        }
        $subtotal += $items[$i]['qty'] * $items[$i]['harga_satuan'];
    }

    if ($alamat === '' || $kota === '' || $metodeBayar === '') {
        $errorMsg = 'Harap lengkapi alamat pengiriman, kota, dan metode pembayaran.';
    } elseif (!$qtyOk) {
        $errorMsg = 'Jumlah barang tidak valid.';
    } else {
        $ongkir = (strtoupper($kota) === 'SURABAYA' || strtoupper($kota) === 'MALANG') ? 0 : 50000;
        $total = $subtotal + $ongkir;
        $nomorOrder = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 4));
        $newOrderId = newId('ord');

        mysqli_begin_transaction($conn);
        try {
            dbRun("INSERT INTO orders (id, nomor_order, customer_id, tanggal, subtotal, ongkir, total, alamat_pengiriman, kota_pengiriman, ekspedisi, metode_pembayaran, status_pembayaran, status_pesanan, catatan)
                   VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, 'menunggu_pembayaran', 'pending', ?)",
                [$newOrderId, $nomorOrder, $cId, $subtotal, (float)$ongkir, $total, $alamat, strtoupper($kota), $ekspedisi, $metodeBayar, $catatan]);
            foreach ($items as $it) {
                dbRun("INSERT INTO order_items (order_id, product_id, request_id, nama_barang, qty, satuan, harga_satuan, subtotal, spesifikasi)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$newOrderId, $it['product_id'], $it['request_id'], $it['nama_barang'], $it['qty'], $it['satuan'],
                     $it['harga_satuan'], $it['qty'] * $it['harga_satuan'], (string)$it['spesifikasi']]);
                if ($it['request_id']) {
                    dbRun("UPDATE product_requests SET status = 'ordered' WHERE id = ?", [$it['request_id']]);
                }
            }
            if ($sourceType === 'cart') {
                dbRun("DELETE FROM cart_items WHERE customer_id = ?", [$cId]);
            }
            mysqli_commit($conn);
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $errorMsg = 'Gagal memproses pesanan: ' . $e->getMessage();
        }

        if ($errorMsg === '') {
            notify('admin', null, "Pesanan baru {$nomorOrder} dari {$currentCust['nama']} (" . count($items) . " barang, Rp " . number_format($total, 0, ',', '.') . ").", 'admin-orders.php');
            header("Location: customer-order-detail.php?id={$newOrderId}&pay=1");
            exit;
        }
    }
}
$subtotalAll = 0.0;
foreach ($items as $it) $subtotalAll += $it['qty'] * $it['harga_satuan'];
?>

<div style="margin-bottom: 20px;">
  <a href="<?= $cartMode ? 'customer-cart.php' : 'customer-products.php' ?>" class="btn btn-ghost" style="padding: 6px 12px; font-size: 12.5px;">
    ← Kembali
  </a>
</div>

<?php if ($errorMsg): ?>
  <div class="alert alert-error" style="margin-bottom: 20px;">
    <span>⚠️</span> <?= h($errorMsg) ?>
  </div>
<?php endif; ?>

<form method="POST" action="" id="checkout-form">
  <div class="cust-split cust-split-right wide">
    
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
        
        <!-- ITEM PREVIEW (satu atau banyak barang) -->
        <input type="hidden" name="cart" value="<?= $cartMode ? '1' : '' ?>">
        <div style="font-size: 11px; font-weight: 700; color: var(--teal-600); text-transform: uppercase; margin-bottom: 6px;">
          <?= $sourceType === 'request' ? '📋 Dari Request yang Disetujui' : ($sourceType === 'cart' ? '🛒 Dari Keranjang (' . count($items) . ' barang)' : '🛍️ Produk Katalog') ?>
        </div>
        <?php foreach ($items as $it): ?>
        <div style="background: var(--navy-50); border: 1px solid var(--navy-200); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 10px;">
          <div style="font-size: 14.5px; font-weight: 800; color: var(--navy-900);"><?= h($it['nama_barang']) ?></div>
          <?php if (!empty($it['spesifikasi'])): ?>
            <div style="font-size: 11.5px; color: var(--navy-500); margin-top: 3px;"><?= h($it['spesifikasi']) ?></div>
          <?php endif; ?>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; border-top: 1px dashed var(--navy-200); padding-top: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 12px;">Jumlah:</span>
              <?php if ($it['qty_editable']): ?>
                <input type="number" name="qty[<?= h($it['product_id']) ?>]" value="<?= h($it['qty']) ?>" min="1" step="1" data-price="<?= h($it['harga_satuan']) ?>"
                       class="form-control item-qty" style="width: 70px; padding: 4px 8px; font-weight: 700; text-align: center;" onchange="recalcTotals()">
              <?php else: ?>
                <strong><?= h($it['qty']) ?></strong>
                <span class="item-fixed" data-subtotal="<?= h($it['qty'] * $it['harga_satuan']) ?>"></span>
              <?php endif; ?>
              <span style="font-size: 12px; color: var(--navy-600);"><?= h($it['satuan']) ?></span>
            </div>
            <span style="font-size: 11px; color: var(--navy-400);">@ Rp <?= number_format($it['harga_satuan'], 0, ',', '.') ?></span>
          </div>
        </div>
        <?php endforeach; ?>
        <div style="margin-bottom: 8px;"></div>

        <!-- KALKULASI HARGA -->
        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px; color: var(--navy-700); margin-bottom: 16px;">
          <div style="display: flex; justify-content: space-between;">
            <span>Subtotal Produk:</span>
            <strong id="label-subtotal">Rp <?= number_format($subtotalAll, 0, ',', '.') ?></strong>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span>Estimasi Ongkos Kirim:</span>
            <strong id="label-ongkir" style="color: var(--em-600);">Gratis (Malang/Surabaya)</strong>
          </div>
          <div style="border-top: 1.5px solid var(--navy-200); margin-top: 6px; padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 14px; font-weight: 700; color: var(--navy-900);">Total Pembayaran:</span>
            <span style="font-size: 20px; font-weight: 800; color: var(--teal-600);" id="label-total">
              Rp <?= number_format($subtotalAll, 0, ',', '.') ?>
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
  function recalcTotals() {
    const kotaInput = document.getElementById('kota_pengiriman');
    const kota = (kotaInput ? kotaInput.value : '').toUpperCase().trim();

    let subtotal = 0;
    document.querySelectorAll('.item-qty').forEach(el => {
      subtotal += (parseFloat(el.value) || 0) * (parseFloat(el.dataset.price) || 0);
    });
    document.querySelectorAll('.item-fixed').forEach(el => { subtotal += parseFloat(el.dataset.subtotal) || 0; });

    let ongkir = 0;
    if (kota === 'SURABAYA' || kota === 'MALANG' || kota === '') {
      document.getElementById('label-ongkir').textContent = 'Gratis (Malang/Surabaya)';
      document.getElementById('label-ongkir').style.color = 'var(--em-600)';
    } else {
      ongkir = 50000;
      document.getElementById('label-ongkir').textContent = 'Rp 50.000 (Luar Area)';
      document.getElementById('label-ongkir').style.color = 'var(--navy-700)';
    }

    document.getElementById('label-subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
    document.getElementById('label-total').textContent = 'Rp ' + (subtotal + ongkir).toLocaleString('id-ID');
  }
  recalcTotals();
</script>

<?php require_once 'customer-footer.php'; ?>
