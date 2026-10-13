<?php
/**
 * customer-return-new.php — Pengajuan retur barang dengan foto/video bukti.
 */
$pageTitle = 'Ajukan Retur';
$pageSubtitle = 'Lampirkan foto atau video kondisi barang sebagai bukti';
require_once 'customer-header.php';

$cId = $currentCust['id'];
$orderId = trim($_GET['order_id'] ?? $_POST['order_id'] ?? '');
$order = dbRow("SELECT * FROM orders WHERE id = ? AND customer_id = ? AND status_pesanan IN ('dikirim','selesai')", [$orderId, $cId]);
$err = '';

if (!$order) {
    echo "<div class='alert alert-error'>Pesanan tidak ditemukan atau belum bisa diretur. <a href='customer-orders.php'>Kembali ke pesanan</a></div>";
    require_once 'customer-footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alasan = trim($_POST['alasan'] ?? '');
    $solusi = $_POST['solusi'] ?? 'ganti';
    $files = array_values(array_filter(normalizeFiles($_FILES['media'] ?? []), fn($f) => ($f['error'] ?? 4) !== UPLOAD_ERR_NO_FILE));
    if ($alasan === '') {
        $err = 'Jelaskan alasan retur.';
    } elseif (!in_array($solusi, ['ganti', 'refund'], true)) {
        $err = 'Pilih solusi yang diinginkan.';
    } elseif (!$files) {
        $err = 'Lampirkan minimal satu foto atau video bukti.';
    } elseif (count($files) > 6) {
        $err = 'Maksimal 6 file.';
    } else {
        $saved = [];
        foreach ($files as $f) {
            $mime = $f['tmp_name'] ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : '';
            $isVideo = str_starts_with((string)$mime, 'video/');
            [$path, $upErr] = saveUpload($f, 'retur', MIME_IMAGE + MIME_VIDEO, $isVideo ? 30 * 1048576 : 5 * 1048576);
            if ($upErr) { $err = $f['name'] . ': ' . $upErr; break; }
            $saved[] = [$path, $isVideo ? 'video' : 'foto'];
        }
        if ($err === '') {
            $rid = newId('ret');
            $nomor = 'RTR-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 4));
            dbRun("INSERT INTO returns (id, nomor_retur, order_id, customer_id, alasan, solusi) VALUES (?, ?, ?, ?, ?, ?)",
                [$rid, $nomor, $orderId, $cId, $alasan, $solusi]);
            foreach ($saved as [$path, $tipe]) {
                dbRun("INSERT INTO return_media (return_id, file, tipe) VALUES (?, ?, ?)", [$rid, $path, $tipe]);
            }
            notify('admin', null, "{$currentCust['nama']} mengajukan retur {$nomor} untuk pesanan {$order['nomor_order']} ($solusi).", 'admin-returns.php#r-' . $rid);
            header('Location: customer-returns.php?ok=1');
            exit;
        }
    }
}
?>
<?php if ($err): ?><div class="alert alert-error"><span>⚠️</span> <?= h($err) ?></div><?php endif; ?>
<div class="card" style="max-width:640px">
  <div class="card-header"><div class="card-title">Retur untuk pesanan <?= h($order['nomor_order']) ?></div></div>
  <form method="POST" enctype="multipart/form-data" style="padding:20px">
    <input type="hidden" name="order_id" value="<?= h($orderId) ?>">
    <div class="form-group">
      <label class="form-label required">Alasan / Kondisi Barang</label>
      <textarea name="alasan" class="form-control" rows="4" required placeholder="Contoh: bearing retak saat dibuka, ukuran tidak sesuai pesanan"><?= h($_POST['alasan'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
      <label class="form-label required">Solusi yang Diminta</label>
      <select name="solusi" class="form-control">
        <option value="ganti">Penggantian barang</option>
        <option value="refund">Pengembalian dana (refund)</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label required">Foto / Video Bukti (maks 6 file; foto 5 MB, video 30 MB)</label>
      <input type="file" name="media[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" required>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <a href="customer-order-detail.php?id=<?= urlencode($orderId) ?>" class="btn btn-ghost">Batal</a>
      <button class="btn btn-primary">📤 Kirim Pengajuan Retur</button>
    </div>
  </form>
</div>
<?php require_once 'customer-footer.php'; ?>
