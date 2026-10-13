<?php
/**
 * admin-layout.php — kerangka halaman admin (sidebar + topbar) untuk halaman baru.
 * Pemakaian: require 'admin-layout.php'; adminHead('Judul', 'Subjudul', 'admin-xxx.php'); ... adminFoot();
 * Halaman lama punya sidebar sendiri (tampilannya sama); menu tambahan disisipkan js/app.js.
 */
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/lib.php';

function adminHead(string $title, string $subtitle, string $active): void {
    $pending = (int)(dbRow("SELECT COUNT(*) AS c FROM product_requests WHERE status = 'pending'")['c'] ?? 0);
    $nav = function (string $href, string $icon, string $label, string $badge = '') use ($active): string {
        $cls = 'nav-item' . ($href === $active ? ' active' : '');
        return '<a href="' . $href . '" class="' . $cls . '"><span class="nav-icon">' . $icon . '</span> ' . $label . $badge . '</a>';
    };
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($title) ?> — CV. Tidar Teknik</title>
  <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
  <style>.brand-icon img { width:40px; height:40px; object-fit:contain; border-radius:10px; }</style>
</head>
<body>
  <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-logo">
        <div class="brand-icon"><img src="img/logo.png" alt="Logo CV. Tidar Teknik"></div>
        <div>
          <div class="brand-name">CV. Tidar Teknik</div>
          <div class="brand-sub">Sistem Manajemen Dokumen</div>
        </div>
      </div>
      <div class="brand-address">📍 Perum Bumi Mas Blok C1 No 2, Jedong Wagir, Kab. Malang 65158</div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-title">Menu Utama</div>
      <?= $nav('index.php', '🏠', 'Dashboard') ?>
      <?= $nav('po.php', '📋', 'Purchase Order') ?>
      <?= $nav('nota.php', '🧾', 'Nota / Invoice') ?>
      <?= $nav('penawaran.php', '📝', 'Penawaran') ?>
      <?= $nav('customer-history.php', '👥', 'Riwayat Customer') ?>

      <div class="nav-section-title" style="margin-top:14px">Modul Customer Portal</div>
      <?= $nav('admin-requests.php', '📥', 'Permintaan Barang', $pending > 0 ? ' <span class="nav-badge" style="background:var(--am-500);color:#fff">' . $pending . '</span>' : '') ?>
      <?= $nav('admin-orders.php', '📦', 'Pesanan Customer') ?>
      <?= $nav('admin-products.php', '🛍️', 'Katalog Produk') ?>
      <?= $nav('admin-customers.php', '🔑', 'Akun Customer') ?>
      <?= $nav('admin-returns.php', '↩️', 'Retur Barang') ?>

      <div class="nav-section-title" style="margin-top:14px">Pengaturan</div>
      <?= $nav('master.php', '🏢', 'Master Perusahaan') ?>
    </nav>
    <div class="sidebar-footer">
      <div style="margin-bottom:8px">📞 0822 2525 5251 &nbsp;|&nbsp; tidarteknik@gmail.com</div>
      <a href="logout.php" style="display:flex;align-items:center;gap:6px;justify-content:center;padding:7px 12px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:8px;color:#fca5a5;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s" id="btn-logout">🚪 Keluar</a>
    </div>
  </aside>

  <div class="main-wrap">
    <header class="topbar">
      <button class="hamburger-btn" id="hamburger-btn" onclick="toggleSidebar()" aria-label="Buka menu">☰</button>
      <div>
        <div class="topbar-title"><?= h($title) ?></div>
        <div class="topbar-subtitle"><?= h($subtitle) ?></div>
      </div>
      <div class="topbar-actions">
        <a href="index.php" class="btn btn-ghost">← Dashboard</a>
      </div>
    </header>
    <main class="page-content">
<?php
}

function adminFoot(): void {
    ?>
    </main>
  </div>
  <script src="js/app.js?v=<?= time() ?>"></script>
</body>
</html>
<?php
}
