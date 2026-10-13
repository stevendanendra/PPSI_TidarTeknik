<?php
/**
 * admin-order-detail.php — Detail pesanan customer (admin):
 * verifikasi pembayaran (DP/Term/Partial/Paid), PO per supplier (satu order → banyak PO), status & resi pengiriman.
 */
require_once 'admin-layout.php';

$orderId = trim($_GET['id'] ?? '');
$order = dbRow("SELECT o.*, c.nama AS nama_customer, c.telepon AS telp, c.email AS email_customer
                FROM orders o JOIN customers c ON c.id = o.customer_id WHERE o.id = ?", [$orderId]);
if (!$order) {
    adminHead('Pesanan tidak ditemukan', '', 'admin-orders.php');
    echo "<div class='alert alert-error'>Pesanan tidak ditemukan. <a href='admin-orders.php'>Kembali</a></div>";
    adminFoot();
    exit;
}

$flash = '';
$flashType = 'success';
$link = 'customer-order-detail.php?id=' . $orderId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'verify_payment') {
        $pid = (int)($_POST['payment_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        $note = trim($_POST['catatan_admin'] ?? '');
        $pm = dbRow("SELECT * FROM payments WHERE id = ? AND order_id = ?", [$pid, $orderId]);
        if ($pm && $pm['status'] === 'menunggu' && in_array($decision, ['terima', 'tolak'], true)) {
            if ($decision === 'tolak' && $note === '') {
                $flash = 'Isi catatan alasan penolakan.'; $flashType = 'error';
                goto done_verify;
            }
            $st = $decision === 'terima' ? 'terverifikasi' : 'ditolak';
            dbRun("UPDATE payments SET status = ?, catatan_admin = ?, verified_at = NOW() WHERE id = ?", [$st, $note !== '' ? $note : null, $pid]);
            recalcOrderPayment($orderId);
            $msg = $decision === 'terima'
                ? "Pembayaran " . strtoupper($pm['tipe']) . " Rp " . number_format((float)$pm['jumlah'], 0, ',', '.') . " untuk {$order['nomor_order']} telah diverifikasi."
                : "Bukti pembayaran untuk {$order['nomor_order']} ditolak" . ($note !== '' ? ": $note" : '.') . " Silakan unggah ulang.";
            notify('customer', $order['customer_id'], $msg, $link);
            $flash = $decision === 'terima' ? 'Pembayaran diverifikasi.' : 'Pembayaran ditolak.';
        }
        done_verify:
    } elseif ($act === 'create_po') {
        // Pecah item order (yang belum punya PO) menjadi satu PO per supplier
        $assign = $_POST['supplier'] ?? [];
        $groups = [];
        foreach ($assign as $itemId => $supId) {
            if ($supId === '') continue;
            $groups[(string)$supId][] = (int)$itemId;
        }
        if (!$groups) {
            $flash = 'Pilih supplier untuk minimal satu barang.';
            $flashType = 'error';
        } else {
            mysqli_begin_transaction($conn);
            try {
                $made = [];
                foreach ($groups as $supId => $itemIds) {
                    $sup = dbRow("SELECT * FROM suppliers WHERE id = ?", [$supId]);
                    if (!$sup) throw new RuntimeException('Supplier tidak ditemukan');
                    $poId = newId('po');
                    $nomor = nextDocNumber('po', 'PO');
                    dbRun("INSERT INTO po (id, nomor, tanggal, supplier, kota, note1, note2, alamat_kirim, ppn_mode, disc, dp, total, status, catatan, termin, order_id, supplier_id)
                           VALUES (?, ?, CURDATE(), ?, ?, ?, '', ?, 'none', 0, 0, ?, 'Belum Bayar', '', 30, ?, ?)",
                        [$poId, $nomor, $sup['nama'], (string)$sup['kota'], 'Ref Order ' . $order['nomor_order'],
                         $order['alamat_pengiriman'], 0.0, $orderId, $supId]);
                    $total = 0.0;
                    foreach ($itemIds as $itemId) {
                        $it = dbRow("SELECT * FROM order_items WHERE id = ? AND order_id = ? AND po_id IS NULL", [$itemId, $orderId]);
                        if (!$it) continue;
                        $cost = $it['product_id']
                            ? dbRow("SELECT harga_modal FROM product_suppliers WHERE product_id = ? AND supplier_id = ?", [$it['product_id'], $supId])
                            : null;
                        $price = (float)($cost['harga_modal'] ?? 0); // harga modal; isi/ubah di halaman PO bila 0
                        $amount = $price * (float)$it['qty'];
                        $total += $amount;
                        dbRun("INSERT INTO po_items (po_id, deskripsi, tipe, qty, price, amount, has_specs, specs) VALUES (?, ?, ?, ?, ?, ?, 0, '[]')",
                            [$poId, $it['nama_barang'], (string)$it['spesifikasi'], (float)$it['qty'], $price, $amount]);
                        dbRun("UPDATE order_items SET po_id = ?, supplier_id = ? WHERE id = ?", [$poId, $supId, $itemId]);
                    }
                    dbRun("UPDATE po SET total = ? WHERE id = ?", [$total, $poId]);
                    $made[] = $nomor . ' → ' . $sup['nama'];
                }
                if ($order['status_pesanan'] === 'pending') {
                    dbRun("UPDATE orders SET status_pesanan = 'diproses' WHERE id = ?", [$orderId]);
                }
                mysqli_commit($conn);
                notify('customer', $order['customer_id'], "Pesanan {$order['nomor_order']} sedang diproses (PO ke supplier dibuat).", $link);
                $flash = 'PO dibuat: ' . implode('; ', $made) . '. Lengkapi harga modal di menu Purchase Order bila masih Rp 0.';
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $flash = 'Gagal membuat PO: ' . $e->getMessage();
                $flashType = 'error';
            }
        }
    } elseif ($act === 'update_status') {
        $stPesan = $_POST['status_pesanan'] ?? $order['status_pesanan'];
        $resi = trim($_POST['no_resi'] ?? '');
        if (in_array($stPesan, ['pending', 'diproses', 'dikirim', 'selesai', 'dibatalkan'], true)) {
            $sentAt = ($stPesan === 'dikirim' && $order['status_pesanan'] !== 'dikirim') ? date('Y-m-d H:i:s') : $order['dikirim_at'];
            $doneAt = ($stPesan === 'selesai' && !$order['tanggal_terima']) ? date('Y-m-d H:i:s') : $order['tanggal_terima'];
            dbRun("UPDATE orders SET status_pesanan = ?, no_resi = ?, dikirim_at = ?, tanggal_terima = ? WHERE id = ?",
                [$stPesan, $resi, $sentAt, $doneAt, $orderId]);
            if ($stPesan !== $order['status_pesanan']) {
                $label = ['pending' => 'menunggu diproses', 'diproses' => 'sedang diproses', 'dikirim' => 'sedang dikirim' . ($resi !== '' ? " (resi: $resi)" : ''),
                          'selesai' => 'selesai', 'dibatalkan' => 'dibatalkan'][$stPesan];
                notify('customer', $order['customer_id'], "Pesanan {$order['nomor_order']} $label.", $link);
            }
            $flash = 'Status pesanan diperbarui.';
        }
    }
    $order = dbRow("SELECT o.*, c.nama AS nama_customer, c.telepon AS telp, c.email AS email_customer
                    FROM orders o JOIN customers c ON c.id = o.customer_id WHERE o.id = ?", [$orderId]);
}

$items = dbRows("SELECT oi.*, p.supplier_id AS prod_supplier FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ? ORDER BY oi.id", [$orderId]);
$payments = dbRows("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC", [$orderId]);
$pos = dbRows("SELECT id, nomor, supplier, total FROM po WHERE order_id = ? ORDER BY nomor", [$orderId]);
$suppliers = dbRows("SELECT id, nama FROM suppliers ORDER BY nama");
$returns = dbRows("SELECT id, nomor_retur, status FROM returns WHERE order_id = ?", [$orderId]);
$sisa = max(0.0, (float)$order['total'] - (float)$order['total_dibayar']);

adminHead('Pesanan ' . $order['nomor_order'], $order['nama_customer'] . ' • ' . date('d/m/Y', strtotime($order['tanggal'])), 'admin-orders.php');
$badgePay = ['paid' => ['badge-green', 'Paid (Lunas)'], 'terverifikasi' => ['badge-green', 'Lunas'], 'dp' => ['badge-blue', 'DP'],
             'term' => ['badge-blue', 'Term'], 'partial' => ['badge-blue', 'Partial'], 'sudah_bayar' => ['badge-blue', 'Butuh verifikasi'],
             'menunggu_pembayaran' => ['badge-amber', 'Belum bayar'], 'dibatalkan' => ['badge-red', 'Dibatalkan']];
[$bc, $bl] = $badgePay[$order['status_pembayaran']] ?? ['badge-gray', $order['status_pembayaran']];
?>
<?php if ($flash): ?><div class="alert alert-<?= $flashType ?>" style="margin-bottom:16px"><span><?= $flashType === 'success' ? '✅' : '⚠️' ?></span> <?= h($flash) ?></div><?php endif; ?>

<div style="margin-bottom:16px"><a href="admin-orders.php" class="btn btn-ghost">← Daftar Pesanan</a></div>

<div class="order-detail-grid">
  <div style="display:flex;flex-direction:column;gap:20px;min-width:0">

    <!-- ITEM & PO -->
    <div class="card">
      <div class="card-header"><span style="font-size:18px">📋</span><div class="card-title">Barang Pesanan &amp; PO Supplier</div></div>
      <form method="POST">
        <input type="hidden" name="act" value="create_po">
        <div class="table-wrap" style="border-radius:0;border:none;border-top:1px solid var(--navy-200);box-shadow:none">
          <table>
            <thead><tr><th>Barang</th><th>Qty</th><th>Harga</th><th>PO / Supplier</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><strong><?= h($it['nama_barang']) ?></strong></td>
                <td><?= h((float)$it['qty']) ?> <?= h($it['satuan']) ?></td>
                <td>Rp <?= number_format((float)$it['harga_satuan'], 0, ',', '.') ?></td>
                <td>
                  <?php if ($it['po_id']): ?>
                    <span class="badge badge-green">Sudah PO</span>
                  <?php else:
                    $finalSup = $it['product_id'] ? (dbRow("SELECT supplier_id FROM product_suppliers WHERE product_id = ? AND is_final = 1", [$it['product_id']])['supplier_id'] ?? $it['prod_supplier']) : null; ?>
                    <select name="supplier[<?= (int)$it['id'] ?>]" class="form-control" style="min-width:160px">
                      <option value="">— pilih supplier —</option>
                      <?php foreach ($suppliers as $s): ?>
                        <option value="<?= h($s['id']) ?>" <?= $finalSup === $s['id'] ? 'selected' : '' ?>><?= h($s['nama']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if (array_filter($items, fn($i) => !$i['po_id']) && $order['status_pesanan'] !== 'dibatalkan'): ?>
          <div style="padding:14px 20px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
            <span style="font-size:12px;color:var(--navy-500)">Barang dengan supplier yang sama digabung menjadi satu PO.</span>
            <button class="btn btn-primary" onclick="return confirm('Buat PO untuk barang yang dipilih?')">📋 Buat PO per Supplier</button>
          </div>
        <?php endif; ?>
      </form>
      <?php if ($pos): ?>
        <div style="padding:0 20px 16px;font-size:12.5px">
          <strong>PO terbit:</strong>
          <?php foreach ($pos as $p): ?>
            <div><a href="print-po.php?id=<?= urlencode($p['id']) ?>" target="_blank"><?= h($p['nomor']) ?></a> — <?= h($p['supplier']) ?> (Rp <?= number_format((float)$p['total'], 0, ',', '.') ?>)</div>
          <?php endforeach; ?>
          <div style="color:var(--navy-400);margin-top:4px">Ubah harga modal &amp; detail PO di menu Purchase Order.</div>
        </div>
      <?php endif; ?>
    </div>

    <!-- PEMBAYARAN -->
    <div class="card">
      <div class="card-header"><span style="font-size:18px">💳</span><div class="card-title">Pembayaran</div>
        <span class="badge <?= $bc ?>" style="margin-left:auto"><?= h($bl) ?></span></div>
      <div style="padding:14px 20px;font-size:13px">
        Total tagihan <strong>Rp <?= number_format((float)$order['total'], 0, ',', '.') ?></strong> •
        terverifikasi <strong>Rp <?= number_format((float)$order['total_dibayar'], 0, ',', '.') ?></strong> •
        sisa <strong>Rp <?= number_format($sisa, 0, ',', '.') ?></strong>
      </div>
      <?php if (!$payments): ?>
        <div style="padding:0 20px 16px;color:var(--navy-400);font-size:12.5px">Belum ada bukti pembayaran dari customer.</div>
      <?php endif; ?>
      <?php foreach ($payments as $pm): ?>
        <div style="border-top:1px solid var(--navy-200);padding:14px 20px;font-size:12.5px">
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <span class="badge badge-gray"><?= h(strtoupper($pm['tipe'])) ?></span>
            <strong>Rp <?= number_format((float)$pm['jumlah'], 0, ',', '.') ?></strong>
            <?php if ($pm['status'] === 'terverifikasi'): ?><span class="badge badge-green">Terverifikasi</span>
            <?php elseif ($pm['status'] === 'ditolak'): ?><span class="badge badge-red">Ditolak</span>
            <?php else: ?><span class="badge badge-amber">Menunggu verifikasi</span><?php endif; ?>
            <span style="color:var(--navy-400)"><?= h(date('d/m/Y H:i', strtotime($pm['created_at']))) ?></span>
            <?php if ($pm['bukti_file']): ?><a href="download.php?t=payment&id=<?= (int)$pm['id'] ?>" target="_blank" rel="noopener">📎 Lihat bukti</a><?php endif; ?>
          </div>
          <?php if ($pm['catatan']): ?><div style="margin-top:4px;color:var(--navy-600)"><?= h($pm['catatan']) ?></div><?php endif; ?>
          <?php if ($pm['catatan_admin']): ?><div style="margin-top:4px">Catatan admin: <?= h($pm['catatan_admin']) ?></div><?php endif; ?>
          <?php if ($pm['status'] === 'menunggu'): ?>
            <form method="POST" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
              <input type="hidden" name="act" value="verify_payment">
              <input type="hidden" name="payment_id" value="<?= (int)$pm['id'] ?>">
              <input type="text" name="catatan_admin" class="form-control" placeholder="Catatan (wajib bila ditolak)" style="flex:1;min-width:180px">
              <button name="decision" value="terima" class="btn btn-success">✅ Verifikasi</button>
              <button name="decision" value="tolak" class="btn btn-danger">❌ Tolak</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:20px;min-width:0">
    <!-- STATUS & PENGIRIMAN -->
    <div class="card">
      <div class="card-header"><span style="font-size:18px">🚚</span><div class="card-title">Status &amp; Pengiriman</div></div>
      <form method="POST" style="padding:16px 20px">
        <input type="hidden" name="act" value="update_status">
        <div class="form-group">
          <label class="form-label">Status Pesanan</label>
          <select name="status_pesanan" class="form-control">
            <?php foreach (['pending' => '⏳ Pending', 'diproses' => '⚙️ Diproses', 'dikirim' => '🚚 Dikirim', 'selesai' => '✅ Selesai', 'dibatalkan' => '❌ Dibatalkan'] as $v => $l): ?>
              <option value="<?= $v ?>" <?= $order['status_pesanan'] === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Nomor Resi / Surat Jalan</label>
          <input type="text" name="no_resi" class="form-control" value="<?= h($order['no_resi']) ?>" placeholder="No resi ekspedisi">
        </div>
        <button class="btn btn-primary" style="width:100%;justify-content:center">💾 Simpan Status</button>
        <?php if ($order['dikirim_at']): ?><div style="font-size:11.5px;color:var(--navy-500);margin-top:8px">Dikirim: <?= h(date('d/m/Y H:i', strtotime($order['dikirim_at']))) ?><?= $order['status_pesanan'] === 'dikirim' ? ' • auto-selesai ' . (int)AUTO_COMPLETE_DAYS . ' hari' : '' ?></div><?php endif; ?>
        <?php if ($order['tanggal_terima']): ?><div style="font-size:11.5px;color:var(--navy-500)">Diterima: <?= h(date('d/m/Y H:i', strtotime($order['tanggal_terima']))) ?></div><?php endif; ?>
        <?php if ($order['alasan_batal']): ?><div style="font-size:12px;margin-top:8px"><b>Alasan batal:</b> <?= h($order['alasan_batal']) ?></div><?php endif; ?>
      </form>
    </div>

    <div class="card">
      <div class="card-header"><span style="font-size:18px">👤</span><div class="card-title">Customer &amp; Tujuan</div></div>
      <div style="padding:16px 20px;font-size:12.5px;line-height:1.7">
        <strong><?= h($order['nama_customer']) ?></strong><br>
        📞 <?= h($order['telp'] ?: '-') ?> • ✉️ <?= h($order['email_customer'] ?: '-') ?><br>
        📍 <?= nl2br(h($order['alamat_pengiriman'])) ?>, <?= h($order['kota_pengiriman']) ?><br>
        🚚 <?= h($order['ekspedisi']) ?><br>
        <?php if ($order['catatan']): ?>📝 <?= h($order['catatan']) ?><?php endif; ?>
      </div>
    </div>

    <?php if ($returns): ?>
    <div class="card">
      <div class="card-header"><span style="font-size:18px">↩️</span><div class="card-title">Retur</div></div>
      <div style="padding:14px 20px;font-size:12.5px">
        <?php foreach ($returns as $r): ?>
          <div><a href="admin-returns.php?id=<?= urlencode($r['id']) ?>"><?= h($r['nomor_retur']) ?></a> — <?= h($r['status']) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php adminFoot(); ?>
