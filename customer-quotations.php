<?php
/**
 * customer-quotations.php — Penawaran (quotation) untuk customer: setujui / tolak / minta revisi.
 * Versi V1/V2/V3 dikelola admin; di sini customer melihat versi yang dikirim ke mereka.
 */
$pageTitle = 'Penawaran Harga';
$pageSubtitle = 'Tinjau penawaran dari CV. Tidar Teknik, lalu setujui, tolak, atau minta revisi';
require_once 'customer-header.php';

$cId = $currentCust['id'];
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = trim($_POST['id'] ?? '');
    $decision = $_POST['decision'] ?? '';
    $reason = trim($_POST['alasan'] ?? '');
    $pn = dbRow("SELECT id, nomor, versi, status FROM penawaran WHERE id = ? AND customer_id = ?", [$pid, $cId]);
    if (!$pn || $pn['status'] !== 'Dikirim') {
        $err = 'Penawaran tidak ditemukan atau sudah diproses.';
    } elseif (!in_array($decision, ['setuju', 'tolak', 'revisi'], true)) {
        $err = 'Pilihan tidak valid.';
    } elseif ($decision !== 'setuju' && $reason === '') {
        $err = 'Mohon isi alasan atau catatan revisi.';
    } else {
        $newStatus = $decision === 'setuju' ? 'Disetujui' : 'Ditolak';
        $st = dbRun("UPDATE penawaran SET status = ?, alasan_respon = ?, responded_at = NOW() WHERE id = ? AND status = 'Dikirim'", [$newStatus, $reason !== '' ? $reason : null, $pn['id']]);
        if (mysqli_stmt_affected_rows($st) !== 1) {
            $err = 'Penawaran sudah diproses sebelumnya.';
        }
    }
    if ($err === '' && isset($newStatus)) {
        $label = ['setuju' => 'MENYETUJUI', 'tolak' => 'MENOLAK', 'revisi' => 'meminta REVISI'][$decision];
        notify('admin', null, "{$currentCust['nama']} $label penawaran {$pn['nomor']} (V{$pn['versi']})" . ($reason !== '' ? ": $reason" : ''), 'penawaran.php');
        $msg = $decision === 'setuju' ? 'Penawaran disetujui. Tim kami akan memproses pesanan Anda.' : 'Tanggapan Anda sudah dikirim ke admin.';
    }
}

$list = dbRows("SELECT * FROM penawaran WHERE customer_id = ? AND status <> 'Pending' ORDER BY tanggal DESC, versi DESC", [$cId]);
$badge = ['Dikirim' => 'badge-blue', 'Disetujui' => 'badge-green', 'Ditolak' => 'badge-danger', 'Revisi' => 'badge-gray'];
?>
<?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= h($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header"><div class="card-title">Daftar Penawaran (<?= count($list) ?>)</div></div>
  <?php if (!$list): ?>
    <div class="empty-state"><div class="empty-icon">📝</div><p>Belum ada penawaran untuk Anda.</p></div>
  <?php else: foreach ($list as $p):
    $items = dbRows("SELECT * FROM penawaran_items WHERE penawaran_id = ?", [$p['id']]); ?>
    <div style="border-top:1px solid var(--navy-200);padding:16px 20px">
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <strong><?= h($p['nomor']) ?></strong>
        <span class="badge badge-amber">V<?= (int)$p['versi'] ?></span>
        <span class="badge <?= $badge[$p['status']] ?? 'badge-gray' ?>"><?= h($p['status']) ?></span>
        <span style="color:var(--navy-400);font-size:12px;margin-left:auto"><?= h($p['tanggal']) ?></span>
      </div>
      <?php if ($p['sub_judul']): ?><div style="margin-top:6px;font-size:13px"><?= h($p['sub_judul']) ?></div><?php endif; ?>
      <div class="table-wrap" style="margin-top:10px">
        <table>
          <thead><tr><th>Deskripsi</th><th>Qty</th><th>Harga</th><th>Jumlah</th></tr></thead>
          <tbody>
          <?php foreach ($items as $it): ?>
            <tr><td><?= h($it['deskripsi']) ?></td><td><?= h(rtrim(rtrim($it['qty'], '0'), '.')) ?> <?= h($it['satuan']) ?></td>
                <td>Rp <?= number_format((float)$it['price'], 0, ',', '.') ?></td>
                <td>Rp <?= number_format((float)$it['amount'], 0, ',', '.') ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div style="text-align:right;margin-top:8px;font-weight:700">Total: Rp <?= number_format((float)$p['total'], 0, ',', '.') ?></div>
      <?php if ($p['note']): ?><div style="font-size:12px;color:var(--navy-500);margin-top:6px;white-space:pre-line"><?= h($p['note']) ?></div><?php endif; ?>
      <?php if ($p['alasan_respon']): ?><div style="font-size:12px;margin-top:6px"><b>Catatan Anda:</b> <?= h($p['alasan_respon']) ?></div><?php endif; ?>

      <?php if ($p['status'] === 'Dikirim'): ?>
      <form method="POST" style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start">
        <input type="hidden" name="id" value="<?= h($p['id']) ?>">
        <textarea name="alasan" class="form-control" rows="2" style="flex:1;min-width:220px" placeholder="Alasan penolakan / catatan revisi (wajib untuk Tolak & Minta Revisi)"></textarea>
        <button name="decision" value="setuju" class="btn btn-primary" onclick="return confirm('Setujui penawaran ini?')">✅ Setujui</button>
        <button name="decision" value="revisi" class="btn btn-ghost">🔁 Minta Revisi</button>
        <button name="decision" value="tolak" class="btn btn-danger" onclick="return confirm('Tolak penawaran ini?')">❌ Tolak</button>
      </form>
      <?php endif; ?>
    </div>
  <?php endforeach; endif; ?>
</div>
<?php require_once 'customer-footer.php'; ?>
