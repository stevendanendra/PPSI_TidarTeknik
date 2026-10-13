<?php
/**
 * customer-cart.php — Keranjang belanja multi-produk
 */
$pageTitle = 'Keranjang Belanja';
$pageSubtitle = 'Satu order bisa berisi beberapa produk';
require_once 'customer-header.php';

$cId = $currentCust['id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'update') {
        foreach (($_POST['qty'] ?? []) as $pid => $q) {
            $q = round((float)$q, 2);
            if (!is_finite($q) || $q > 100000) continue;
            if ($q <= 0) {
                dbRun("DELETE FROM cart_items WHERE customer_id = ? AND product_id = ?", [$cId, (string)$pid]);
            } else {
                dbRun("UPDATE cart_items SET qty = ? WHERE customer_id = ? AND product_id = ?", [$q, $cId, (string)$pid]);
            }
        }
        $msg = 'Keranjang diperbarui.';
    } elseif ($act === 'remove') {
        dbRun("DELETE FROM cart_items WHERE customer_id = ? AND product_id = ?", [$cId, (string)($_POST['product_id'] ?? '')]);
        $msg = 'Barang dihapus dari keranjang.';
    }
}

$rows = dbRows("SELECT c.product_id, c.qty, p.nama, p.satuan, p.harga, p.harga_min, p.harga_max, p.kode
                FROM cart_items c JOIN products p ON p.id = c.product_id AND p.is_active = 1
                WHERE c.customer_id = ? ORDER BY c.id", [$cId]);
$subtotal = 0.0;
foreach ($rows as $r) $subtotal += (float)$r['qty'] * (float)$r['harga'];
?>
<?php if ($msg): ?><div class="alert alert-success"><span>✅</span> <?= h($msg) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header"><div class="card-title">Isi Keranjang (<?= count($rows) ?> jenis barang)</div></div>
  <?php if (!$rows): ?>
    <div class="empty-state"><div class="empty-icon">🛒</div><p>Keranjang masih kosong.</p>
      <a href="customer-products.php" class="btn btn-primary">🛍️ Belanja di Marketplace</a></div>
  <?php else: ?>
  <form method="POST" id="cart-form">
    <input type="hidden" name="act" value="update">
    <div class="table-wrap" style="border-radius:0;border:none;border-top:1px solid var(--navy-200);box-shadow:none">
      <table>
        <thead><tr><th>Barang</th><th>Harga Satuan</th><th style="width:130px">Jumlah</th><th style="text-align:right">Subtotal</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><strong><?= h($r['nama']) ?></strong><div style="font-size:11px;color:var(--navy-400)"><?= h($r['kode']) ?></div></td>
            <td><?= h(hargaLabel($r)) ?><div style="font-size:11px;color:var(--navy-400)">/ <?= h($r['satuan']) ?></div></td>
            <td><input type="number" name="qty[<?= h($r['product_id']) ?>]" value="<?= h((float)$r['qty']) ?>" min="0" step="1" class="form-control" style="width:100px"></td>
            <td style="text-align:right;font-weight:700">Rp <?= number_format((float)$r['qty'] * (float)$r['harga'], 0, ',', '.') ?></td>
            <td><button type="submit" form="rm-<?= h($r['product_id']) ?>" class="btn btn-sm btn-danger" title="Hapus">🗑️</button></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:var(--navy-50)"><td colspan="3" style="text-align:right;font-weight:700">Subtotal Produk (estimasi):</td>
            <td style="text-align:right;font-weight:800;color:var(--teal-600)">Rp <?= number_format($subtotal, 0, ',', '.') ?></td><td></td></tr>
        </tfoot>
      </table>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;padding:16px 20px;flex-wrap:wrap">
      <a href="customer-products.php" class="btn btn-ghost">+ Tambah Barang Lain</a>
      <button type="submit" class="btn btn-ghost">🔄 Perbarui Jumlah</button>
      <a href="customer-checkout.php?cart=1" class="btn btn-success" style="font-weight:700">Lanjut Checkout →</a>
    </div>
  </form>
  <?php foreach ($rows as $r): ?>
    <form method="POST" id="rm-<?= h($r['product_id']) ?>"><input type="hidden" name="act" value="remove"><input type="hidden" name="product_id" value="<?= h($r['product_id']) ?>"></form>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php require_once 'customer-footer.php'; ?>
