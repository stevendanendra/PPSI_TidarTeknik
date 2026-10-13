<?php
/**
 * customer-invoice.php — Halaman Cetak & PDF Invoice Resmi Customer
 * CV. Tidar Teknik
 */

require_once 'customer-auth.php';
requireCustomerLogin();

$cId = $currentCust['id'];
$orderId = trim($_GET['id'] ?? '');

if (empty($orderId)) {
    die("ID Pesanan tidak ditemukan.");
}

$cleanOId = mysqli_real_escape_string($conn, $orderId);
$resO = mysqli_query($conn, "SELECT o.*, c.nama AS nama_customer, c.telepon AS telp_customer, c.email AS email_customer, c.npwp AS npwp_customer 
                            FROM orders o 
                            JOIN customers c ON o.customer_id = c.id 
                            WHERE o.id = '$cleanOId' AND o.customer_id = '$cId' LIMIT 1");

if (!$resO || mysqli_num_rows($resO) == 0) {
    die("Pesanan tidak ditemukan atau Anda tidak memiliki akses.");
}

$order = mysqli_fetch_assoc($resO);

// Ambil items
$resI = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = '$cleanOId'");
$items = mysqli_fetch_all($resI, MYSQLI_ASSOC);

// Helper terbilang PHP
function terbilangIndo($n) {
    $satuan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan',
               'Sepuluh', 'Sebelas', 'Dua Belas', 'Tiga Belas', 'Empat Belas', 'Lima Belas',
               'Enam Belas', 'Tujuh Belas', 'Delapan Belas', 'Sembilan Belas'];
    $n = (int)abs($n);
    if ($n < 20) return $satuan[$n];
    if ($n < 100) return $satuan[floor($n / 10)] . ' Puluh ' . $satuan[$n % 10];
    if ($n < 200) return 'Seratus ' . terbilangIndo($n - 100);
    if ($n < 1000) return $satuan[floor($n / 100)] . ' Ratus ' . terbilangIndo($n % 100);
    if ($n < 2000) return 'Seribu ' . terbilangIndo($n - 1000);
    if ($n < 1000000) return terbilangIndo(floor($n / 1000)) . ' Ribu ' . terbilangIndo($n % 1000);
    if ($n < 1000000000) return terbilangIndo(floor($n / 1000000)) . ' Juta ' . terbilangIndo($n % 1000000);
    return terbilangIndo(floor($n / 1000000000)) . ' Miliar ' . terbilangIndo($n % 1000000000);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice <?= htmlspecialchars($order['nomor_order']) ?> — CV. Tidar Teknik</title>
  <link rel="stylesheet" href="css/print.css?v=<?= time() ?>">
  <style>
    .kop-logo img {
      width: 60px;
      height: 60px;
      object-fit: contain;
    }
  </style>
</head>
<body>

  <!-- PRINT TOOLBAR (Sembunyi saat dicetak) -->
  <div class="print-toolbar no-print">
    <h2 style="margin:0;font-size:16px;">🖨️ Preview Invoice: <?= htmlspecialchars($order['nomor_order']) ?></h2>
    <div style="display:flex;gap:8px;">
      <button class="btn-back" onclick="window.location.href='customer-order-detail.php?id=<?= urlencode($order['id']) ?>'">
        ← Kembali ke Detail
      </button>
      <button class="btn-print-action" onclick="window.print()">
        🖨️ Cetak / Simpan PDF
      </button>
    </div>
  </div>

  <div class="print-page-wrapper">
    <div class="sheet">
      <!-- KOP PERUSAHAAN -->
      <div class="kop-header">
        <div class="kop-logo">
          <?php if (file_exists(__DIR__ . '/img/logo.png')): ?>
            <img src="img/logo.png" alt="Logo CV. Tidar Teknik">
          <?php else: ?>
            <span style="font-size:40px;">🏭</span>
          <?php endif; ?>
        </div>
        <div class="kop-info">
          <div class="kop-title">CV. TIDAR TEKNIK</div>
          <div class="kop-sub">GENERAL SUPPLIER, TEKNIK &amp; INDUSTRI</div>
          <div class="kop-address">
            Perum Bumi Mas Blok C1 No 2, Jedong Wagir, Kab. Malang 65158<br>
            Telp / WA: 0822 2525 5251 &nbsp;|&nbsp; Email: tidarteknik@gmail.com
          </div>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 22px; font-weight: 800; color: #0284c7; letter-spacing: 1px;">INVOICE</div>
          <div style="font-size: 11pt; font-weight: 700; color: #0f172a; margin-top: 4px;">
            <?= htmlspecialchars($order['nomor_order']) ?>
          </div>
          <div style="font-size: 9pt; color: #475569; margin-top: 2px;">
            Tanggal: <?= date('d F Y', strtotime($order['tanggal'])) ?>
          </div>
        </div>
      </div>

      <hr style="border: none; border-top: 2px solid #0f172a; margin: 12px 0 16px 0;">

      <!-- INFORMASI CUSTOMER & TUJUAN -->
      <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 9pt; line-height: 1.5;">
        <div style="width: 55%;">
          <div style="font-size: 8pt; color: #64748b; font-weight: 700; text-transform: uppercase;">Kepada Yth:</div>
          <div style="font-size: 11pt; font-weight: 700; color: #0f172a; margin-top: 2px;">
            <?= htmlspecialchars($order['nama_customer']) ?>
          </div>
          <div style="color: #334155; margin-top: 2px;">
            <?= nl2br(htmlspecialchars($order['alamat_pengiriman'])) ?><br>
            <strong>Kota:</strong> <?= htmlspecialchars($order['kota_pengiriman']) ?> &nbsp;|&nbsp; 
            <strong>Telp/WA:</strong> <?= htmlspecialchars($order['telp_customer'] ?: '-') ?>
          </div>
        </div>

        <div style="width: 40%; text-align: right;">
          <div style="font-size: 8pt; color: #64748b; font-weight: 700; text-transform: uppercase;">Metode Pembayaran:</div>
          <div style="font-size: 10pt; font-weight: 700; color: #0f172a; margin-top: 2px;">
            <?= htmlspecialchars($order['metode_pembayaran']) ?>
          </div>
          <div style="margin-top: 6px;">
            Status Bayar: 
            <?php if ($order['status_pembayaran'] === 'terverifikasi'): ?>
              <span style="font-weight: 800; color: #059669;">[ LUNAS ]</span>
            <?php else: ?>
              <span style="font-weight: 800; color: #d97706;">[ <?= strtoupper($order['status_pembayaran']) ?> ]</span>
            <?php endif; ?>
          </div>
          <div style="font-size: 8.5pt; color: #475569; margin-top: 2px;">
            Ekspedisi: <?= htmlspecialchars($order['ekspedisi']) ?>
          </div>
        </div>
      </div>

      <!-- TABEL ITEM PESANAN -->
      <table class="table-doc" style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <thead>
          <tr style="background: #f1f5f9; text-transform: uppercase; font-size: 8.5pt;">
            <th style="padding: 8px; border: 1px solid #cbd5e1; width: 36px; text-align: center;">No</th>
            <th style="padding: 8px; border: 1px solid #cbd5e1; text-align: left;">Deskripsi Komponen / Barang</th>
            <th style="padding: 8px; border: 1px solid #cbd5e1; width: 60px; text-align: center;">Qty</th>
            <th style="padding: 8px; border: 1px solid #cbd5e1; width: 60px; text-align: center;">Satuan</th>
            <th style="padding: 8px; border: 1px solid #cbd5e1; width: 120px; text-align: right;">Harga Satuan</th>
            <th style="padding: 8px; border: 1px solid #cbd5e1; width: 130px; text-align: right;">Jumlah (Rp)</th>
          </tr>
        </thead>
        <tbody style="font-size: 9pt;">
          <?php $no = 1; foreach ($items as $it): ?>
            <tr>
              <td style="padding: 8px; border: 1px solid #cbd5e1; text-align: center; vertical-align: top;"><?= $no++ ?></td>
              <td style="padding: 8px; border: 1px solid #cbd5e1; vertical-align: top;">
                <strong style="color: #0f172a;"><?= htmlspecialchars($it['nama_barang']) ?></strong>
                <?php if (!empty($it['spesifikasi'])): ?>
                  <div style="font-size: 8pt; color: #475569; margin-top: 2px;">
                    <?= htmlspecialchars($it['spesifikasi']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td style="padding: 8px; border: 1px solid #cbd5e1; text-align: center; vertical-align: top;">
                <?= (float)$it['qty'] ?>
              </td>
              <td style="padding: 8px; border: 1px solid #cbd5e1; text-align: center; vertical-align: top;">
                <?= htmlspecialchars($it['satuan']) ?>
              </td>
              <td style="padding: 8px; border: 1px solid #cbd5e1; text-align: right; vertical-align: top;">
                <?= number_format($it['harga_satuan'], 0, ',', '.') ?>
              </td>
              <td style="padding: 8px; border: 1px solid #cbd5e1; text-align: right; vertical-align: top; font-weight: 600;">
                <?= number_format($it['subtotal'], 0, ',', '.') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot style="font-size: 9pt;">
          <tr>
            <td colspan="4" rowspan="3" style="border: 1px solid #cbd5e1; padding: 10px; vertical-align: top; background: #fafafa;">
              <div style="font-size: 8pt; color: #64748b; font-weight: 700; text-transform: uppercase;">Terbilang:</div>
              <div style="font-style: italic; font-weight: 600; color: #0f172a; margin-top: 3px;">
                # <?= terbilangIndo($order['total']) ?> Rupiah #
              </div>
              <div style="font-size: 8pt; color: #475569; margin-top: 10px;">
                Pembayaran via Transfer: <strong>Bank BCA 123-456-7890</strong> a/n CV. TIDAR TEKNIK
              </div>
            </td>
            <td style="border: 1px solid #cbd5e1; padding: 6px 10px; text-align: right; font-weight: 600;">Subtotal:</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px 10px; text-align: right; font-weight: 600;">
              Rp <?= number_format($order['subtotal'], 0, ',', '.') ?>
            </td>
          </tr>
          <tr>
            <td style="border: 1px solid #cbd5e1; padding: 6px 10px; text-align: right; font-weight: 600;">Ongkos Kirim:</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px 10px; text-align: right; font-weight: 600;">
              Rp <?= number_format($order['ongkir'], 0, ',', '.') ?>
            </td>
          </tr>
          <tr style="background: #f8fafc;">
            <td style="border: 1px solid #cbd5e1; padding: 8px 10px; text-align: right; font-weight: 800; font-size: 10pt; color: #0f172a;">GRAND TOTAL:</td>
            <td style="border: 1px solid #cbd5e1; padding: 8px 10px; text-align: right; font-weight: 800; font-size: 11pt; color: #0284c7;">
              Rp <?= number_format($order['total'], 0, ',', '.') ?>
            </td>
          </tr>
        </tfoot>
      </table>

      <!-- TANDA TANGAN & STEMPEL RESMI -->
      <div style="display: flex; justify-content: space-between; margin-top: 32px; font-size: 9pt;">
        <div style="width: 200px; text-align: center;">
          <div>Penerima / Customer,</div>
          <div style="height: 60px;"></div>
          <div style="border-bottom: 1px solid #0f172a; font-weight: 700;">
            <?= htmlspecialchars($order['nama_customer']) ?>
          </div>
        </div>

        <div style="width: 220px; text-align: center; position: relative;">
          <div>Malang, <?= date('d F Y', strtotime($order['tanggal'])) ?></div>
          <div style="font-weight: 700; margin-bottom: 4px;">Hormat kami,<br>CV. TIDAR TEKNIK</div>
          
          <div style="height: 65px; display: flex; align-items: center; justify-content: center;">
            <?php if (file_exists(__DIR__ . '/img/logo.tandatangan.png')): ?>
              <img src="img/logo.tandatangan.png" alt="Stempel" style="height: 65px; object-fit: contain;">
            <?php else: ?>
              <span style="font-size: 11px; color: #94a3b8; font-style: italic;">(Digital Verified)</span>
            <?php endif; ?>
          </div>

          <div style="border-bottom: 1px solid #0f172a; font-weight: 700;">
            DIREKTUR CV. TIDAR TEKNIK
          </div>
        </div>
      </div>

    </div>
  </div>

</body>
</html>
