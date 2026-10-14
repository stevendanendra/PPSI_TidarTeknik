<?php
/**
 * home.php — Landing page publik untuk pengunjung/customer sebelum login.
 * Tidak memuat tautan ke login admin. Harga tidak ditampilkan ke pengunjung (hanya setelah login).
 */
require_once 'customer-auth.php';
require_once 'lib.php';

$loggedIn = isCustomerLoggedIn();
$loggedOut = isset($_GET['logout']);

$categories = dbRows("SELECT kategori, COUNT(*) AS n FROM products WHERE is_active = 1 AND kategori <> '' GROUP BY kategori ORDER BY n DESC, kategori LIMIT 8");
$products = dbRows("SELECT nama, kategori, satuan, spesifikasi FROM products WHERE is_active = 1 ORDER BY created_at DESC, nama LIMIT 6");
$totalProducts = (int)(dbRow("SELECT COUNT(*) AS c FROM products WHERE is_active = 1")['c'] ?? 0);
$wa = 'https://wa.me/6282225255251?text=' . rawurlencode('Halo CV. Tidar Teknik, saya ingin dibuatkan akun Customer Portal.');
$steps = [
    ['🔎', 'Pilih / Request Barang', 'Telusuri katalog atau ajukan barang yang belum ada di katalog.'],
    ['📝', 'Terima Penawaran', 'Setujui, tolak, atau minta revisi penawaran (V1, V2, V3).'],
    ['💳', 'Bayar via Transfer', 'Unggah bukti transfer: DP, termin, cicilan, atau pelunasan.'],
    ['🚚', 'Pantau Pengiriman', 'Lacak status dan nomor resi langsung dari portal.'],
    ['✅', 'Terima & Retur', 'Konfirmasi barang diterima, atau ajukan retur dengan foto/video.'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CV. Tidar Teknik — Pengadaan Komponen Teknik &amp; Industri</title>
  <meta name="description" content="CV. Tidar Teknik melayani pengadaan komponen teknik dan industri (bearing, roller chain, kontrol motor, dan lainnya) dengan penawaran, pembayaran, dan pelacakan pesanan lewat Customer Portal.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --navy-950: #020617; --navy-900: #0f172a; --navy-800: #1e293b; --navy-700: #334155;
      --navy-300: #cbd5e1; --navy-200: #e2e8f0; --navy-100: #f1f5f9; --navy-500: #64748b;
      --teal-500: #0ea5e9; --teal-400: #38bdf8; --teal-600: #0284c7; --em-500: #10b981;
    }
    html { scroll-behavior: smooth; }
    body { font-family: 'Inter', system-ui, sans-serif; color: var(--navy-800); background: #fff; line-height: 1.6; }
    a { color: inherit; }
    .wrap { max-width: 1080px; margin: 0 auto; padding: 0 20px; }

    header.nav { position: sticky; top: 0; z-index: 10; background: rgba(2, 6, 23, .92); backdrop-filter: blur(8px); color: #fff; }
    header.nav .wrap { display: flex; align-items: center; gap: 16px; height: 64px; }
    .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; text-decoration: none; }
    .brand img { width: 36px; height: 36px; object-fit: contain; border-radius: 8px; background: #fff; }
    .nav-links { margin-left: auto; display: flex; align-items: center; gap: 18px; font-size: 14px; }
    .nav-links a { text-decoration: none; color: var(--navy-300); }
    .nav-links a:hover { color: #fff; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 14px; text-decoration: none; border: 1.5px solid transparent; cursor: pointer; }
    .btn-primary { background: linear-gradient(135deg, var(--teal-600), var(--teal-500)); color: #fff; }
    .btn-ghost { border-color: rgba(255,255,255,.35); color: #fff; }
    .btn-outline { border-color: var(--navy-200); color: var(--navy-800); background: #fff; }

    .hero { background: radial-gradient(1200px 500px at 80% -10%, rgba(14,165,233,.35), transparent), linear-gradient(160deg, var(--navy-950), var(--navy-800)); color: #fff; padding: 84px 0 96px; }
    .hero h1 { font-size: clamp(28px, 5vw, 46px); line-height: 1.15; font-weight: 800; max-width: 760px; }
    .hero h1 span { color: var(--teal-400); }
    .hero p { margin-top: 16px; max-width: 620px; color: var(--navy-300); font-size: 17px; }
    .hero .cta { margin-top: 28px; display: flex; gap: 12px; flex-wrap: wrap; }
    .hero .notice { margin-top: 18px; display: inline-block; padding: 8px 14px; border-radius: 10px; background: rgba(16,185,129,.15); color: #6ee7b7; font-size: 13.5px; }

    section { padding: 64px 0; }
    section.alt { background: var(--navy-100); }
    h2 { font-size: 26px; font-weight: 800; color: var(--navy-900); }
    .lead { margin-top: 8px; color: var(--navy-500); max-width: 640px; }
    .grid { display: grid; gap: 16px; margin-top: 28px; }
    .steps { grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); }
    .card { background: #fff; border: 1px solid var(--navy-200); border-radius: 14px; padding: 20px; }
    .step-no { width: 40px; height: 40px; border-radius: 50%; background: rgba(14,165,233,.12); display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 10px; }
    .card h3 { font-size: 15px; font-weight: 700; color: var(--navy-900); }
    .card p { font-size: 13.5px; color: var(--navy-500); margin-top: 4px; }
    .cats { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; }
    .chip { padding: 8px 14px; border-radius: 999px; background: #fff; border: 1px solid var(--navy-200); font-size: 13.5px; font-weight: 600; }
    .chip small { color: var(--navy-500); font-weight: 500; margin-left: 4px; }
    .prod-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
    .prod .cat { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--teal-600); }
    .prod h3 { margin-top: 4px; }
    .prod p { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .why { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
    .contact { background: var(--navy-900); color: #fff; }
    .contact h2 { color: #fff; }
    .contact .lead { color: var(--navy-300); }
    .contact ul { list-style: none; margin-top: 20px; display: grid; gap: 10px; font-size: 15px; }
    footer { background: var(--navy-950); color: var(--navy-500); text-align: center; padding: 22px 0; font-size: 13px; }
    @media (max-width: 640px) { .nav-links a.hide-sm { display: none; } .hero { padding: 56px 0 64px; } section { padding: 44px 0; } }
  </style>
</head>
<body>
  <header class="nav">
    <div class="wrap">
      <a class="brand" href="home.php">
        <?php if (file_exists(__DIR__ . '/img/logo.png')): ?><img src="img/logo.png" alt="Logo CV. Tidar Teknik"><?php endif; ?>
        <span>CV. Tidar Teknik</span>
      </a>
      <nav class="nav-links">
        <a class="hide-sm" href="#cara-kerja">Cara Kerja</a>
        <a class="hide-sm" href="#produk">Produk</a>
        <a class="hide-sm" href="#kontak">Kontak</a>
        <?php if ($loggedIn): ?>
          <a class="btn btn-primary" href="customer-dashboard.php">Ke Dashboard</a>
        <?php else: ?>
          <a class="btn btn-primary" href="customer-login.php">Masuk</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="wrap">
        <h1>Pengadaan komponen teknik &amp; industri, <span>dari penawaran sampai barang tiba.</span></h1>
        <p>Pesan barang, terima penawaran, bayar, dan pantau pengiriman dalam satu portal. Kami carikan supplier terbaik untuk kebutuhan pabrik dan bengkel Anda.</p>
        <div class="cta">
          <?php if ($loggedIn): ?>
            <a class="btn btn-primary" href="customer-dashboard.php">Buka Dashboard</a>
          <?php else: ?>
            <a class="btn btn-primary" href="customer-login.php">Masuk ke Customer Portal</a>
            <a class="btn btn-ghost" href="<?= h($wa) ?>" target="_blank" rel="noopener">Minta Akun via WhatsApp</a>
          <?php endif; ?>
        </div>
        <?php if ($loggedOut): ?><div class="notice">Anda telah keluar dari Customer Portal.</div><?php endif; ?>
      </div>
    </section>

    <section id="cara-kerja">
      <div class="wrap">
        <h2>Cara kerja</h2>
        <p class="lead">Lima langkah dari kebutuhan sampai barang di tangan Anda.</p>
        <div class="grid steps">
          <?php foreach ($steps as $i => [$icon, $title, $desc]): ?>
            <div class="card"><div class="step-no"><?= $icon ?></div><h3><?= ($i + 1) . '. ' . h($title) ?></h3><p><?= h($desc) ?></p></div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <?php if ($products): ?>
    <section id="produk" class="alt">
      <div class="wrap">
        <h2>Produk yang kami layani</h2>
        <p class="lead"><?= $totalProducts ?> produk di katalog. Rentang harga dan pemesanan tersedia setelah Anda masuk.</p>
        <?php if ($categories): ?>
          <div class="cats"><?php foreach ($categories as $c): ?><span class="chip"><?= h($c['kategori']) ?><small><?= (int)$c['n'] ?></small></span><?php endforeach; ?></div>
        <?php endif; ?>
        <div class="grid prod-grid">
          <?php foreach ($products as $p): ?>
            <div class="card prod">
              <div class="cat"><?= h($p['kategori']) ?></div>
              <h3><?= h($p['nama']) ?></h3>
              <p><?= h($p['spesifikasi'] ?: 'Spesifikasi sesuai standar industri.') ?></p>
            </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:24px"><a class="btn btn-outline" href="<?= $loggedIn ? 'customer-products.php' : 'customer-login.php' ?>">Lihat katalog lengkap →</a></div>
      </div>
    </section>
    <?php endif; ?>

    <section>
      <div class="wrap">
        <h2>Kenapa CV. Tidar Teknik</h2>
        <div class="grid why">
          <div class="card"><h3>Satu portal, semua tahap</h3><p>Penawaran, pembayaran, pengiriman, hingga retur tercatat rapi dan bisa dipantau kapan saja.</p></div>
          <div class="card"><h3>Dokumen resmi</h3><p>Penawaran, invoice, dan surat jalan diterbitkan dengan penomoran otomatis.</p></div>
          <div class="card"><h3>Notifikasi langsung</h3><p>Dapat kabar saat penawaran siap, pembayaran terverifikasi, dan barang dikirim.</p></div>
        </div>
      </div>
    </section>

    <section id="kontak" class="contact">
      <div class="wrap">
        <h2>Hubungi kami</h2>
        <p class="lead">Akun Customer Portal dibuat oleh admin kami. Hubungi kami untuk dibuatkan akun.</p>
        <ul>
          <li>📞 <a href="tel:+6282225255251">0822 2525 5251</a></li>
          <li>✉️ <a href="mailto:tidarteknik@gmail.com">tidarteknik@gmail.com</a></li>
          <li>📍 Perum Bumi Mas Blok C1 No 2, Jedong, Wagir, Kab. Malang 65158</li>
        </ul>
        <div style="margin-top:22px"><a class="btn btn-primary" href="<?= h($wa) ?>" target="_blank" rel="noopener">Chat WhatsApp</a></div>
      </div>
    </section>
  </main>

  <footer><div class="wrap">© <?= date('Y') ?> CV. Tidar Teknik. Seluruh hak dilindungi.</div></footer>
</body>
</html>
