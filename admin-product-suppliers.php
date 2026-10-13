<?php
/**
 * admin-product-suppliers.php — Kandidat supplier per produk & pemilihan supplier final.
 */
require_once 'admin-layout.php';

$productId = trim($_GET['product_id'] ?? $_POST['product_id'] ?? '');
$product = dbRow("SELECT * FROM products WHERE id = ?", [$productId]);
if (!$product) {
    adminHead('Produk tidak ditemukan', '', 'admin-products.php');
    echo "<div class='alert alert-error'>Produk tidak ditemukan. <a href='admin-products.php'>Kembali ke katalog</a></div>";
    adminFoot();
    exit;
}

$flash = '';
$flashType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    $supId = trim($_POST['supplier_id'] ?? '');
    try {
        if ($act === 'add' && $supId !== '') {
            $modal = ($_POST['harga_modal'] ?? '') !== '' ? (float)$_POST['harga_modal'] : null;
            dbRun("INSERT INTO product_suppliers (product_id, supplier_id, harga_modal, catatan) VALUES (?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE harga_modal = VALUES(harga_modal), catatan = VALUES(catatan)",
                [$productId, $supId, $modal, trim($_POST['catatan'] ?? '')]);
            $flash = 'Kandidat supplier disimpan.';
        } elseif ($act === 'final' && $supId !== '') {
            dbRun("UPDATE product_suppliers SET is_final = IF(supplier_id = ?, 1, 0) WHERE product_id = ?", [$supId, $productId]);
            dbRun("UPDATE products SET supplier_id = ? WHERE id = ?", [$supId, $productId]);
            $flash = 'Supplier final dipilih.';
        } elseif ($act === 'remove' && $supId !== '') {
            dbRun("DELETE FROM product_suppliers WHERE product_id = ? AND supplier_id = ?", [$productId, $supId]);
            $final = dbRow("SELECT supplier_id FROM product_suppliers WHERE product_id = ? AND is_final = 1", [$productId]);
            dbRun("UPDATE products SET supplier_id = ? WHERE id = ?", [$final['supplier_id'] ?? null, $productId]);
            $flash = 'Kandidat dihapus.';
        }
    } catch (Throwable $e) {
        $flash = 'Gagal: ' . $e->getMessage();
        $flashType = 'error';
    }
}

$cands = dbRows("SELECT ps.*, s.nama, s.kota FROM product_suppliers ps JOIN suppliers s ON s.id = ps.supplier_id
                 WHERE ps.product_id = ? ORDER BY ps.is_final DESC, s.nama", [$productId]);
$available = dbRows("SELECT id, nama FROM suppliers WHERE id NOT IN (SELECT supplier_id FROM product_suppliers WHERE product_id = ?) ORDER BY nama", [$productId]);

adminHead('Kandidat Supplier', $product['nama'], 'admin-products.php');
?>
<?php if ($flash): ?><div class="alert alert-<?= $flashType ?>" style="margin-bottom:16px"><span><?= $flashType === 'success' ? '✅' : '⚠️' ?></span> <?= h($flash) ?></div><?php endif; ?>
<div style="margin-bottom:16px"><a href="admin-products.php" class="btn btn-ghost">← Katalog Produk</a></div>

<div class="order-detail-grid">
  <div class="card">
    <div class="card-header"><div class="card-title">Kandidat Supplier (<?= count($cands) ?>)</div></div>
    <div class="table-wrap" style="border-radius:0;border:none;border-top:1px solid var(--navy-200);box-shadow:none">
      <table>
        <thead><tr><th>Supplier</th><th>Harga Modal</th><th>Catatan</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($cands as $c): ?>
          <tr>
            <td><strong><?= h($c['nama']) ?></strong> <?= $c['is_final'] ? '<span class="badge badge-green">Final</span>' : '' ?>
              <div style="font-size:11px;color:var(--navy-400)"><?= h($c['kota']) ?></div></td>
            <td><?= $c['harga_modal'] !== null ? 'Rp ' . number_format((float)$c['harga_modal'], 0, ',', '.') : '-' ?></td>
            <td style="font-size:12px"><?= h($c['catatan']) ?></td>
            <td style="display:flex;gap:4px;flex-wrap:wrap">
              <?php if (!$c['is_final']): ?>
              <form method="POST"><input type="hidden" name="product_id" value="<?= h($productId) ?>"><input type="hidden" name="supplier_id" value="<?= h($c['supplier_id']) ?>">
                <button name="act" value="final" class="btn btn-sm btn-success">✔ Jadikan Final</button></form>
              <?php endif; ?>
              <form method="POST"><input type="hidden" name="product_id" value="<?= h($productId) ?>"><input type="hidden" name="supplier_id" value="<?= h($c['supplier_id']) ?>">
                <button name="act" value="remove" class="btn btn-sm btn-danger" onclick="return confirm('Hapus kandidat ini?')">🗑️</button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$cands): ?><tr><td colspan="4"><div class="empty-state"><p>Belum ada kandidat supplier.</p></div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Tambah / Ubah Kandidat</div></div>
    <form method="POST" style="padding:16px 20px">
      <input type="hidden" name="act" value="add">
      <input type="hidden" name="product_id" value="<?= h($productId) ?>">
      <div class="form-group"><label class="form-label required">Supplier</label>
        <select name="supplier_id" class="form-control" required>
          <option value="">— pilih —</option>
          <?php foreach ($available as $s): ?><option value="<?= h($s['id']) ?>"><?= h($s['nama']) ?></option><?php endforeach; ?>
          <?php foreach ($cands as $c): ?><option value="<?= h($c['supplier_id']) ?>"><?= h($c['nama']) ?> (ubah)</option><?php endforeach; ?>
        </select></div>
      <div class="form-group"><label class="form-label">Harga Modal (Rp)</label><input type="number" name="harga_modal" class="form-control" min="0" step="any"></div>
      <div class="form-group"><label class="form-label">Catatan</label><input type="text" name="catatan" class="form-control" placeholder="Lead time, MOQ, dll."></div>
      <button class="btn btn-primary" style="width:100%;justify-content:center">💾 Simpan Kandidat</button>
    </form>
  </div>
</div>
<?php adminFoot(); ?>
