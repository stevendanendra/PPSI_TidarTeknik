<?php
/**
 * customer-help.php — Help Center: panduan alur belanja & jawaban pertanyaan umum.
 */
$pageTitle = 'Help Center';
$pageSubtitle = 'Panduan memakai Portal Customer CV. Tidar Teknik';
require_once 'customer-header.php';

$faq = [
    ['Bagaimana cara memesan barang?', 'Buka <a href="customer-products.php">Marketplace</a>, masukkan beberapa produk ke <a href="customer-cart.php">Keranjang</a>, lalu klik Lanjut Checkout. Satu order boleh berisi beberapa produk. Isi alamat, pilih ekspedisi, dan buat pesanan.'],
    ['Barang yang saya cari tidak ada di katalog.', 'Gunakan <a href="customer-request.php?new=1">Request Barang</a>. Admin akan mengecek ke supplier dan mengirim harga. Setelah request disetujui, Anda bisa checkout dari halaman Request.'],
    ['Apa itu penawaran V1, V2, V3?', 'Admin dapat mengirim penawaran harga. Jika Anda minta revisi atau menolak, admin menyusun versi berikutnya (V2, V3, dst.). Tanggapi di menu <a href="customer-quotations.php">Penawaran</a>: setujui, tolak, atau minta revisi dengan catatan.'],
    ['Bagaimana cara membayar?', 'Pembayaran lewat transfer manual ke rekening BCA CV. Tidar Teknik. Di halaman detail pesanan, pilih jenis pembayaran (DP, Term, Partial, atau Pelunasan), isi jumlah, dan unggah bukti transfer. Admin akan memverifikasi, lalu status berubah menjadi DP / Term / Partial / Paid sesuai total yang sudah terverifikasi.'],
    ['Apa arti status DP, Term, Partial, dan Paid?', 'DP = uang muka diterima. Term = pembayaran termin. Partial = dibayar sebagian. Paid = tagihan lunas. Sisa tagihan selalu ditampilkan di detail pesanan.'],
    ['Kapan pesanan dianggap selesai?', 'Setelah barang tiba, klik "Barang Sudah Diterima" di detail pesanan. Jika tidak dikonfirmasi, pesanan otomatis selesai ' . (int)AUTO_COMPLETE_DAYS . ' hari setelah dikirim.'],
    ['Bisakah saya membatalkan pesanan?', 'Bisa, selama pesanan belum diteruskan ke supplier (PO belum dibuat). Tombol Batalkan Pesanan ada di detail pesanan. Jika sudah ada pembayaran, admin akan memproses pengembalian dana.'],
    ['Barang yang datang rusak atau tidak sesuai.', 'Ajukan retur di detail pesanan atau menu <a href="customer-returns.php">Retur Barang</a>. Lampirkan foto/video. Admin akan memeriksa, lalu menyetujui penggantian barang atau refund.'],
    ['Saya lupa password.', 'Di halaman login klik "Lupa password?", masukkan email akun, lalu ikuti tautan reset yang dikirim. Anda juga bisa menghubungi admin untuk reset manual. Password dapat diganti kapan saja di menu <a href="customer-profile.php">Profil &amp; Akun</a>.'],
    ['Bagaimana saya tahu ada pembaruan?', 'Klik ikon lonceng 🔔 di pojok kanan atas. Notifikasi tentang penawaran, pembayaran, pengiriman, dan retur muncul di sana.'],
];
?>
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><span style="font-size:18px">🧭</span><div class="card-title">Alur Singkat</div></div>
  <ol style="padding:16px 20px 16px 38px;line-height:1.9;font-size:13.5px;margin:0">
    <li>Pilih produk → masukkan ke <b>Keranjang</b> (atau ajukan <b>Request Barang</b>).</li>
    <li>Tanggapi <b>Penawaran</b> dari admin bila ada (setujui / tolak / revisi).</li>
    <li><b>Checkout</b> dan buat pesanan.</li>
    <li><b>Bayar</b> via transfer BCA dan unggah bukti.</li>
    <li>Pantau status pesanan sampai <b>dikirim</b>.</li>
    <li>Konfirmasi <b>barang diterima</b>, atau ajukan <b>retur</b> bila ada masalah.</li>
  </ol>
</div>

<div class="card">
  <div class="card-header"><span style="font-size:18px">❓</span><div class="card-title">Pertanyaan Umum</div></div>
  <?php foreach ($faq as $i => [$q, $a]): ?>
    <details style="border-top:1px solid var(--navy-200);padding:12px 20px" <?= $i === 0 ? 'open' : '' ?>>
      <summary style="cursor:pointer;font-weight:700;font-size:13.5px;color:var(--navy-800)"><?= h($q) ?></summary>
      <div style="margin-top:8px;font-size:13px;line-height:1.7;color:var(--navy-600)"><?= $a ?></div>
    </details>
  <?php endforeach; ?>
</div>

<div class="card" style="margin-top:20px">
  <div class="card-body" style="padding:18px 20px;font-size:13px">
    Butuh bantuan langsung? Hubungi admin CV. Tidar Teknik: 📞 0822 2525 5251 • ✉️ tidarteknik@gmail.com
    <div style="margin-top:10px"><button type="button" class="btn btn-ghost btn-sm" onclick="replayTutorial()">▶ Putar ulang tutorial</button></div>
  </div>
</div>
<script>
  async function replayTutorial() {
    const fd = new FormData(); fd.append('action', 'tutorial_reset');
    await fetch('customer-api.php', { method: 'POST', body: fd });
    location.href = 'customer-dashboard.php';
  }
</script>
<?php require_once 'customer-footer.php'; ?>
