<?php
/**
 * admin-returns.php — Verifikasi retur customer: setujui/tolak, lalu selesaikan (ganti barang atau refund).
 */
require_once 'admin-layout.php';

$flash = '';
$flashType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid = trim($_POST['return_id'] ?? '');
    $decision = $_POST['decision'] ?? '';
    $note = trim($_POST['catatan_admin'] ?? '');
    $ret = dbRow("SELECT r.*, o.nomor_order FROM returns r JOIN orders o ON o.id = r.order_id WHERE r.id = ?", [$rid]);
    $map = ['setuju' => ['diajukan', 'disetujui'], 'tolak' => ['diajukan', 'ditolak'], 'selesai' => ['disetujui', 'selesai']];
    if ($ret && isset($map[$decision]) && $ret['status'] === $map[$decision][0]) {
        $new = $map[$decision][1];
        if ($decision === 'tolak' && $note === '') {
            $flash = 'Isi alasan penolakan.';
            $flashType = 'error';
        } else {
            dbRun("UPDATE returns SET status = ?, catatan_admin = ?, resolved_at = IF(? IN ('ditolak','selesai'), NOW(), resolved_at) WHERE id = ?",
                [$new, $note !== '' ? $note : $ret['catatan_admin'], $new, $rid]);
            $label = ['disetujui' => 'disetujui', 'ditolak' => 'ditolak', 'selesai' => $ret['solusi'] === 'refund' ? 'selesai (refund diproses)' : 'selesai (barang pengganti dikirim)'][$new];
            notify('customer', $ret['customer_id'], "Retur {$ret['nomor_retur']} $label." . ($note !== '' ? " Catatan: $note" : ''), 'customer-returns.php');
            $flash = "Retur {$ret['nomor_retur']} $label.";
        }
    }
}

$rows = dbRows("SELECT r.*, o.nomor_order, c.nama AS nama_customer FROM returns r
                JOIN orders o ON o.id = r.order_id JOIN customers c ON c.id = r.customer_id
                ORDER BY FIELD(r.status,'diajukan','disetujui','ditolak','selesai'), r.created_at DESC");
$badge = ['diajukan' => ['badge-amber', 'Diajukan'], 'disetujui' => ['badge-blue', 'Disetujui'], 'ditolak' => ['badge-red', 'Ditolak'], 'selesai' => ['badge-green', 'Selesai']];
adminHead('Retur Barang', 'Tinjau bukti foto/video, setujui atau tolak, lalu selesaikan', 'admin-returns.php');
?>
<?php if ($flash): ?><div class="alert alert-<?= $flashType ?>" style="margin-bottom:16px"><span><?= $flashType === 'success' ? '✅' : '⚠️' ?></span> <?= h($flash) ?></div><?php endif; ?>
<div class="card">
  <div class="card-header"><div class="card-title">Pengajuan Retur (<?= count($rows) ?>)</div></div>
  <?php if (!$rows): ?><div class="empty-state"><div class="empty-icon">↩️</div><p>Belum ada pengajuan retur.</p></div><?php endif; ?>
  <?php foreach ($rows as $r):
    [$bc, $bl] = $badge[$r['status']] ?? ['badge-gray', $r['status']];
    $media = dbRows("SELECT * FROM return_media WHERE return_id = ?", [$r['id']]); ?>
    <div id="r-<?= h($r['id']) ?>" style="border-top:1px solid var(--navy-200);padding:16px 20px;font-size:13px">
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <strong><?= h($r['nomor_retur']) ?></strong> <span class="badge <?= $bc ?>"><?= $bl ?></span>
        <span class="badge badge-gray"><?= $r['solusi'] === 'refund' ? 'Minta refund' : 'Minta ganti barang' ?></span>
        <a href="admin-order-detail.php?id=<?= urlencode($r['order_id']) ?>" style="margin-left:auto;font-size:12px">Pesanan <?= h($r['nomor_order']) ?> • <?= h($r['nama_customer']) ?></a>
      </div>
      <div style="margin-top:6px"><?= nl2br(h($r['alasan'])) ?></div>
      <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
        <?php foreach ($media as $m): ?>
          <?php if ($m['tipe'] === 'video'): ?><video src="download.php?t=return&id=<?= (int)$m['id'] ?>" controls style="height:110px;border-radius:6px"></video>
          <?php else: ?><a href="download.php?t=return&id=<?= (int)$m['id'] ?>" target="_blank" rel="noopener"><img src="download.php?t=return&id=<?= (int)$m['id'] ?>" alt="Bukti retur" style="height:110px;border-radius:6px;border:1px solid var(--navy-200)"></a><?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php if ($r['catatan_admin']): ?><div style="margin-top:6px"><b>Catatan admin:</b> <?= h($r['catatan_admin']) ?></div><?php endif; ?>
      <?php if (in_array($r['status'], ['diajukan', 'disetujui'], true)): ?>
        <form method="POST" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
          <input type="hidden" name="return_id" value="<?= h($r['id']) ?>">
          <input type="text" name="catatan_admin" class="form-control" placeholder="Catatan untuk customer (wajib bila ditolak)" style="flex:1;min-width:200px">
          <?php if ($r['status'] === 'diajukan'): ?>
            <button name="decision" value="setuju" class="btn btn-success">✅ Setujui</button>
            <button name="decision" value="tolak" class="btn btn-danger">❌ Tolak</button>
          <?php else: ?>
            <button name="decision" value="selesai" class="btn btn-primary">🏁 Tandai Selesai (<?= $r['solusi'] === 'refund' ? 'refund dikirim' : 'barang pengganti dikirim' ?>)</button>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php adminFoot(); ?>
