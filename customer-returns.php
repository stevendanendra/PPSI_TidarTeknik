<?php
/**
 * customer-returns.php — Daftar pengajuan retur customer.
 */
$pageTitle = 'Retur Barang';
$pageSubtitle = 'Pantau status pengajuan retur Anda';
require_once 'customer-header.php';

$cId = $currentCust['id'];
$rows = dbRows("SELECT r.*, o.nomor_order FROM returns r JOIN orders o ON o.id = r.order_id WHERE r.customer_id = ? ORDER BY r.created_at DESC", [$cId]);
$badge = ['diajukan' => ['badge-amber', 'Diajukan'], 'disetujui' => ['badge-blue', 'Disetujui'], 'ditolak' => ['badge-red', 'Ditolak'], 'selesai' => ['badge-green', 'Selesai']];
?>
<?php if (isset($_GET['ok'])): ?><div class="alert alert-success"><span>✅</span> Pengajuan retur terkirim. Admin akan meninjau bukti Anda.</div><?php endif; ?>
<div class="card">
  <div class="card-header"><div class="card-title">Pengajuan Retur (<?= count($rows) ?>)</div>
    <a href="customer-orders.php" class="btn btn-sm btn-primary" style="margin-left:auto">Pilih pesanan untuk diretur</a></div>
  <?php if (!$rows): ?>
    <div class="empty-state"><div class="empty-icon">↩️</div><p>Belum ada pengajuan retur. Buka detail pesanan yang sudah dikirim, lalu klik "Ajukan Retur".</p></div>
  <?php endif; ?>
  <?php foreach ($rows as $r):
    [$bc, $bl] = $badge[$r['status']] ?? ['badge-gray', $r['status']];
    $media = dbRows("SELECT * FROM return_media WHERE return_id = ?", [$r['id']]); ?>
    <div style="border-top:1px solid var(--navy-200);padding:16px 20px;font-size:13px">
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <strong><?= h($r['nomor_retur']) ?></strong> <span class="badge <?= $bc ?>"><?= $bl ?></span>
        <span class="badge badge-gray"><?= $r['solusi'] === 'refund' ? 'Refund' : 'Ganti barang' ?></span>
        <span style="margin-left:auto;color:var(--navy-400);font-size:12px">Pesanan <?= h($r['nomor_order']) ?> • <?= h(date('d/m/Y', strtotime($r['created_at']))) ?></span>
      </div>
      <div style="margin-top:6px"><?= nl2br(h($r['alasan'])) ?></div>
      <?php if ($r['catatan_admin']): ?><div style="margin-top:6px"><b>Tanggapan admin:</b> <?= h($r['catatan_admin']) ?></div><?php endif; ?>
      <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
        <?php foreach ($media as $m): ?>
          <?php if ($m['tipe'] === 'video'): ?><video src="download.php?t=return&id=<?= (int)$m['id'] ?>" controls style="height:90px;border-radius:6px"></video>
          <?php else: ?><a href="download.php?t=return&id=<?= (int)$m['id'] ?>" target="_blank" rel="noopener"><img src="download.php?t=return&id=<?= (int)$m['id'] ?>" alt="Bukti retur" style="height:90px;border-radius:6px;border:1px solid var(--navy-200)"></a><?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php require_once 'customer-footer.php'; ?>
