<?php
/**
 * customer-orders.php — Riwayat Pesanan Customer Portal
 * Sesuai Sitemap 3.4: Daftar Pesanan, Detail, Status & Tracking, Unduh Invoice
 */

$pageTitle = 'Riwayat Pesanan Saya';
$pageSubtitle = 'Pantau status pemrosesan, tracking pengiriman, dan unduh invoice resmi';
require_once 'customer-header.php';

$cId = $currentCust['id'];
$statusFilt = trim($_GET['status'] ?? '');

$where = ["o.customer_id = ?"];
$params = [$cId];
if (!empty($statusFilt)) {
    if ($statusFilt === 'unpaid') {
        $where[] = "o.status_pembayaran IN ('menunggu_pembayaran', 'dp', 'term', 'partial')";
    } elseif (in_array($statusFilt, ['pending', 'diproses', 'dikirim', 'selesai', 'dibatalkan'], true)) {
        $where[] = "o.status_pesanan = ?";
        $params[] = $statusFilt;
    }
}
$whereSql = implode(" AND ", $where);

$orders = dbRows("SELECT * FROM orders o WHERE $whereSql ORDER BY o.created_at DESC", $params);

// Load items per order
foreach ($orders as &$ord) {
    $ord['items'] = dbRows("SELECT * FROM order_items WHERE order_id = ?", [$ord['id']]);
}
unset($ord);
?>

<!-- FILTER STATUS TABS -->
<div class="tab-list" style="margin-bottom: 20px;">
  <a href="customer-orders.php" class="tab-btn <?= empty($statusFilt) ? 'active' : '' ?>">
    Semua Pesanan
  </a>
  <a href="customer-orders.php?status=unpaid" class="tab-btn <?= $statusFilt === 'unpaid' ? 'active' : '' ?>">
    🟡 Menunggu Pembayaran
  </a>
  <a href="customer-orders.php?status=diproses" class="tab-btn <?= $statusFilt === 'diproses' ? 'active' : '' ?>">
    ⚙️ Diproses
  </a>
  <a href="customer-orders.php?status=dikirim" class="tab-btn <?= $statusFilt === 'dikirim' ? 'active' : '' ?>">
    🚚 Sedang Dikirim
  </a>
  <a href="customer-orders.php?status=selesai" class="tab-btn <?= $statusFilt === 'selesai' ? 'active' : '' ?>">
    ✅ Selesai
  </a>
</div>

<div class="card">
  <div class="card-header" style="justify-content: space-between;">
    <div class="card-title">Daftar Transaksi Pesanan (<?= count($orders) ?>)</div>
    <a href="customer-products.php" class="btn btn-sm btn-primary">🛍️ Belanja Lagi</a>
  </div>

  <div class="table-wrap" style="border-radius:0;border:none;border-top:1px solid var(--navy-200);box-shadow:none;">
    <?php if (empty($orders)): ?>
      <div style="text-align: center; padding: 48px 16px;">
        <div style="font-size: 44px; margin-bottom: 8px;">📦</div>
        <p style="color: var(--navy-500); font-size: 13.5px; margin: 0 0 16px 0;">Tidak ada transaksi pesanan pada filter ini.</p>
        <a href="customer-products.php" class="btn btn-primary">Lihat Katalog Produk</a>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Nomor Order &amp; Tanggal</th>
            <th>Rincian Item</th>
            <th>Total Tagihan</th>
            <th>Status Bayar</th>
            <th>Status Pengiriman</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>
                <strong style="color: var(--navy-900);"><?= htmlspecialchars($o['nomor_order']) ?></strong>
                <div style="font-size: 11px; color: var(--navy-400); margin-top: 2px;">
                  Tanggal: <?= date('d M Y', strtotime($o['tanggal'])) ?>
                </div>
              </td>
              <td>
                <?php foreach ($o['items'] as $it): ?>
                  <div>
                    <strong><?= (float)$it['qty'] ?> <?= htmlspecialchars($it['satuan']) ?></strong> - <?= htmlspecialchars($it['nama_barang']) ?>
                  </div>
                <?php endforeach; ?>
                <div style="font-size: 11px; color: var(--navy-400); margin-top: 2px;">
                  Tujuan: <?= htmlspecialchars($o['kota_pengiriman']) ?> (<?= htmlspecialchars($o['ekspedisi']) ?>)
                </div>
              </td>
              <td>
                <strong style="color: var(--navy-900); font-size: 14px;">
                  Rp <?= number_format($o['total'], 0, ',', '.') ?>
                </strong>
                <div style="font-size: 11px; color: var(--navy-400); margin-top: 2px;">
                  Ongkir: Rp <?= number_format($o['ongkir'], 0, ',', '.') ?>
                </div>
              </td>
              <td>
                <?php if ($o['status_pembayaran'] === 'terverifikasi'): ?>
                  <span class="badge badge-green">🟢 Terverifikasi</span>
                <?php elseif ($o['status_pembayaran'] === 'sudah_bayar'): ?>
                  <span class="badge badge-blue">🔵 Menunggu Cek Admin</span>
                <?php elseif ($o['status_pembayaran'] === 'dibatalkan'): ?>
                  <span class="badge badge-red">🔴 Batal</span>
                <?php else: ?>
                  <span class="badge badge-amber">🟡 Menunggu Pembayaran</span>
                <?php endif; ?>
                <div style="font-size: 11px; color: var(--navy-500); margin-top: 3px;">
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
                  <span class="badge badge-red">❌ Dibatalkan</span>
                <?php else: ?>
                  <span class="badge badge-amber">⏳ Menunggu Proses</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display: flex; gap: 6px; flex-direction: column;">
                  <a href="customer-order-detail.php?id=<?= urlencode($o['id']) ?>" class="btn btn-sm btn-ghost" style="font-size: 11.5px; justify-content: center;">
                    🔍 Detail &amp; Tracking
                  </a>
                  <a href="customer-invoice.php?id=<?= urlencode($o['id']) ?>" target="_blank" class="btn btn-sm btn-primary" style="font-size: 11.5px; justify-content: center;">
                    🖨️ Invoice
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once 'customer-footer.php'; ?>
