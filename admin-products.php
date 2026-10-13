<?php
/**
 * admin-products.php — Kelola Katalog Produk Marketplace
 * CV. Tidar Teknik (Panel Internal Admin)
 */

require_once 'auth.php';
requireLogin();
require_once 'lib.php';

$success = '';
$error = '';

// Ambil list suppliers untuk dropdown
$supRes = mysqli_query($conn, "SELECT id, nama FROM suppliers ORDER BY nama ASC");
$suppliers = mysqli_fetch_all($supRes, MYSQLI_ASSOC);

// Handle Tambah / Edit Produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId      = trim($_POST['edit_id'] ?? '');
    $kode        = trim($_POST['kode'] ?? '');
    $nama        = trim($_POST['nama'] ?? '');
    $kategori    = trim($_POST['kategori'] ?? '');
    $harga       = (float)($_POST['harga'] ?? 0);
    $hargaMin    = ($_POST['harga_min'] ?? '') !== '' ? (float)$_POST['harga_min'] : null;
    $hargaMax    = ($_POST['harga_max'] ?? '') !== '' ? (float)$_POST['harga_max'] : null;
    $stok        = (int)($_POST['stok'] ?? 0);
    $satuan      = trim($_POST['satuan'] ?? 'PCS');
    $supplier_id = trim($_POST['supplier_id'] ?? '');
    $spesifikasi = trim($_POST['spesifikasi'] ?? '');
    $deskripsi   = trim($_POST['deskripsi'] ?? '');

    if ($nama === '' || $kategori === '') {
        $error = 'Nama produk dan kategori wajib diisi.';
    } elseif ($hargaMin !== null && $hargaMax !== null && $hargaMin > $hargaMax) {
        $error = 'Harga minimum tidak boleh lebih besar dari harga maksimum.';
    } else {
        $supVal = $supplier_id !== '' ? $supplier_id : null;
        try {
            if ($editId !== '') {
                dbRun("UPDATE products SET kode = ?, nama = ?, kategori = ?, harga = ?, harga_min = ?, harga_max = ?, stok = ?, satuan = ?,
                              supplier_id = ?, spesifikasi = ?, deskripsi = ? WHERE id = ?",
                    [$kode, $nama, $kategori, $harga, $hargaMin, $hargaMax, $stok, $satuan, $supVal, $spesifikasi, $deskripsi, $editId]);
                if ($supVal) {
                    // supplier utama ikut menjadi kandidat final
                    dbRun("UPDATE product_suppliers SET is_final = 0 WHERE product_id = ?", [$editId]);
                    dbRun("INSERT INTO product_suppliers (product_id, supplier_id, is_final) VALUES (?, ?, 1)
                           ON DUPLICATE KEY UPDATE is_final = 1", [$editId, $supVal]);
                }
                $success = 'Produk berhasil diperbarui.';
            } else {
                $newId = 'PRD-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
                dbRun("INSERT INTO products (id, kode, nama, kategori, harga, harga_min, harga_max, stok, satuan, supplier_id, spesifikasi, deskripsi)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$newId, $kode, $nama, $kategori, $harga, $hargaMin, $hargaMax, $stok, $satuan, $supVal, $spesifikasi, $deskripsi]);
                if ($supVal) {
                    dbRun("INSERT INTO product_suppliers (product_id, supplier_id, is_final) VALUES (?, ?, 1)", [$newId, $supVal]);
                }
                $success = 'Produk baru berhasil ditambahkan ke katalog.';
            }
        } catch (Throwable $e) {
            $error = 'Gagal menyimpan produk: ' . $e->getMessage();
        }
    }
}

// Handle Hapus Produk
if (isset($_GET['delete'])) {
    dbRun("DELETE FROM products WHERE id = ?", [trim($_GET['delete'])]);
    header('Location: admin-products.php?deleted=1');
    exit;
}

if (isset($_GET['deleted'])) {
    $success = 'Produk berhasil dihapus.';
}

// Ambil daftar produk
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $like = '%' . $search . '%';
    $products = dbRows("SELECT p.*, s.nama AS nama_supplier,
                               (SELECT COUNT(*) FROM product_suppliers ps WHERE ps.product_id = p.id) AS jml_kandidat
                        FROM products p LEFT JOIN suppliers s ON p.supplier_id = s.id
                        WHERE p.nama LIKE ? OR p.kode LIKE ? OR p.kategori LIKE ?
                        ORDER BY p.created_at DESC", [$like, $like, $like]);
} else {
    $products = dbRows("SELECT p.*, s.nama AS nama_supplier,
                               (SELECT COUNT(*) FROM product_suppliers ps WHERE ps.product_id = p.id) AS jml_kandidat
                        FROM products p LEFT JOIN suppliers s ON p.supplier_id = s.id
                        ORDER BY p.created_at DESC");
}

// Hitung request pending untuk badge sidebar
$cntPendingRes = mysqli_query($conn, "SELECT COUNT(*) FROM product_requests WHERE status = 'pending'");
$totalPending = (int)mysqli_fetch_row($cntPendingRes)[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Katalog Produk — CV. Tidar Teknik</title>
  <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
  <style>
    .brand-icon img { width:40px; height:40px; object-fit:contain; border-radius:10px; }
  </style>
</head>
<body>

  <!-- SIDEBAR OVERLAY -->
  <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

  <!-- SIDEBAR ADMIN -->
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
      <a href="index.php" class="nav-item"><span class="nav-icon">🏠</span> Dashboard</a>
      <a href="po.php" class="nav-item"><span class="nav-icon">📋</span> Purchase Order</a>
      <a href="nota.php" class="nav-item"><span class="nav-icon">🧾</span> Nota / Invoice</a>
      <a href="penawaran.php" class="nav-item"><span class="nav-icon">📝</span> Penawaran</a>
      <a href="customer-history.php" class="nav-item"><span class="nav-icon">👥</span> Riwayat Customer</a>

      <div class="nav-section-title" style="margin-top:14px">Modul Customer Portal</div>
      <a href="admin-requests.php" class="nav-item">
        <span class="nav-icon">📥</span> Permintaan Barang
        <?php if ($totalPending > 0): ?>
          <span class="nav-badge" style="background:var(--am-500);color:#fff"><?= $totalPending ?></span>
        <?php endif; ?>
      </a>
      <a href="admin-orders.php" class="nav-item">
        <span class="nav-icon">📦</span> Pesanan Customer
      </a>
      <a href="admin-products.php" class="nav-item active">
        <span class="nav-icon">🛍️</span> Katalog Produk
      </a>

      <div class="nav-section-title" style="margin-top:14px">Pengaturan</div>
      <a href="master.php" class="nav-item"><span class="nav-icon">🏢</span> Master Perusahaan</a>
    </nav>
    <div class="sidebar-footer">
      <div style="margin-bottom:8px">📞 0822 2525 5251 &nbsp;|&nbsp; tidarteknik@gmail.com</div>
      <a href="logout.php" style="display:flex;align-items:center;gap:6px;justify-content:center;padding:7px 12px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:8px;color:#ef4444;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s" id="btn-logout">🚪 Keluar</a>
    </div>
  </aside>

  <div class="main-wrap">
    <header class="topbar">
      <button class="hamburger-btn" id="hamburger-btn" onclick="toggleSidebar()" aria-label="Buka menu">☰</button>
      <div>
        <div class="topbar-title">Katalog Produk Marketplace</div>
        <div class="topbar-subtitle">Kelola daftar komponen teknik, harga estimasi, dan stok barang ready</div>
      </div>
      <div class="topbar-actions">
        <a href="customer-products.php" target="_blank" class="btn btn-ghost">👁️ Lihat Sisi Customer ↗</a>
        <a href="index.php" class="btn btn-ghost">← Kembali ke Dashboard</a>
      </div>
    </header>

    <main class="page-content">

      <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom: 20px;">
          <span>✅</span> <?= $success ?>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom: 20px;">
          <span>⚠️</span> <?= $error ?>
        </div>
      <?php endif; ?>

      <div style="display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start;">
        <!-- TABEL PRODUK -->
        <div class="card">
          <div class="card-header" style="justify-content: space-between;">
            <div class="card-title">Daftar Produk (<?= count($products) ?>)</div>
            <form method="GET" action="admin-products.php" style="display:flex;gap:8px">
              <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama/kode..." value="<?= htmlspecialchars($search) ?>" style="width:180px">
              <button type="submit" class="btn btn-sm btn-primary">Cari</button>
              <?php if (!empty($search)): ?>
                <a href="admin-products.php" class="btn btn-sm btn-ghost">Reset</a>
              <?php endif; ?>
            </form>
          </div>

          <div class="table-wrap" style="border-radius: 0; box-shadow: none; border: none; border-top: 1px solid var(--navy-200);">
            <?php if (empty($products)): ?>
              <div style="text-align: center; padding: 48px 16px; color: var(--navy-500);">
                Tidak ada data produk. Silakan gunakan form di samping untuk menambah produk baru.
              </div>
            <?php else: ?>
              <table>
                <thead>
                  <tr>
                    <th>Produk &amp; Kategori</th>
                    <th>Harga</th>
                    <th>Stok Internal</th>
                    <th>Supplier Rekanan</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($products as $p): ?>
                    <tr>
                      <td>
                        <strong><?= htmlspecialchars($p['nama']) ?></strong>
                        <div style="font-size: 11px; color: var(--navy-500); margin-top: 2px;">
                          Kode: <?= htmlspecialchars($p['kode'] ?: '-') ?> | Kategori: <strong><?= htmlspecialchars($p['kategori']) ?></strong>
                        </div>
                      </td>
                      <td>
                        <strong><?= htmlspecialchars(hargaLabel($p)) ?></strong>
                        <div style="font-size: 11px; color: var(--navy-400);">/<?= htmlspecialchars($p['satuan']) ?></div>
                      </td>
                      <td>
                        <?php if ($p['stok'] > 0): ?>
                          <span class="badge badge-green">Ready: <?= $p['stok'] ?> <?= htmlspecialchars($p['satuan']) ?></span>
                        <?php else: ?>
                          <span class="badge badge-gray">0</span>
                        <?php endif; ?>
                      </td>
                      <td style="font-size: 12px; color: var(--navy-600);">
                        <?= htmlspecialchars($p['nama_supplier'] ?: '-') ?>
                        <div style="font-size: 11px; color: var(--navy-400);"><?= (int)$p['jml_kandidat'] ?> kandidat supplier</div>
                      </td>
                      <td class="td-actions">
                        <button 
                          type="button" 
                          class="btn btn-sm btn-warning btn-icon"
                          onclick="editProduct(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)"
                          title="Edit Produk"
                        >✏️</button>
                        <a 
                          href="admin-products.php?delete=<?= urlencode($p['id']) ?>" 
                          class="btn btn-sm btn-danger btn-icon" 
                          onclick="return confirm('Hapus produk ini dari katalog?')"
                          title="Hapus Produk"
                        >🗑️</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>

        <!-- FORM TAMBAH / EDIT PRODUK -->
        <div class="card" id="form-card" style="position: sticky; top: 80px;">
          <div class="card-header">
            <span style="font-size: 18px;" id="form-icon">➕</span>
            <div class="card-title" id="form-title">Tambah Produk Baru</div>
          </div>

          <div class="card-body" style="padding: 20px;">
            <form method="POST" action="admin-products.php" id="prod-form" autocomplete="off">
              <input type="hidden" name="edit_id" id="prod-edit-id">

              <div class="form-group">
                <label class="form-label required">Nama Komponen / Produk</label>
                <input type="text" name="nama" id="prod-nama" class="form-control" placeholder="Contoh: BEARING 33A120 ZWZ" required>
              </div>

              <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                  <label class="form-label">Kode SKU</label>
                  <input type="text" name="kode" id="prod-kode" class="form-control" placeholder="BRG-33A120">
                </div>
                <div class="form-group">
                  <label class="form-label required">Kategori</label>
                  <input type="text" name="kategori" id="prod-kategori" class="form-control" placeholder="Bearing & Bushing" required>
                </div>
              </div>

              <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                  <label class="form-label required">Harga Estimasi (Rp)</label>
                  <input type="number" name="harga" id="prod-harga" class="form-control" placeholder="1500000" step="any" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Stok Internal (tidak tampil ke customer)</label>
                  <input type="number" name="stok" id="prod-stok" class="form-control" placeholder="0" min="0" value="0">
                </div>
              </div>

              <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                  <label class="form-label">Rentang Harga: Minimum (Rp)</label>
                  <input type="number" name="harga_min" id="prod-harga-min" class="form-control" placeholder="Opsional" step="any" min="0">
                </div>
                <div class="form-group">
                  <label class="form-label">Rentang Harga: Maksimum (Rp)</label>
                  <input type="number" name="harga_max" id="prod-harga-max" class="form-control" placeholder="Opsional" step="any" min="0">
                </div>
              </div>

              <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                  <label class="form-label required">Satuan</label>
                  <input type="text" name="satuan" id="prod-satuan" class="form-control" value="PCS" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Supplier Rekanan</label>
                  <select name="supplier_id" id="prod-sup-id" class="form-control">
                    <option value="">-- Tanpa Supplier --</option>
                    <?php foreach ($suppliers as $s): ?>
                      <option value="<?= htmlspecialchars($s['id']) ?>"><?= htmlspecialchars($s['nama']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Spesifikasi Teknis</label>
                <textarea name="spesifikasi" id="prod-specs" class="form-control" rows="2" placeholder="Brand, dimensi, material, grade..."></textarea>
              </div>

              <div class="form-group">
                <label class="form-label">Deskripsi Tambahan</label>
                <textarea name="deskripsi" id="prod-desc" class="form-control" rows="2" placeholder="Keterangan aplikasi, catatan garansi..."></textarea>
              </div>

              <div style="display: flex; gap: 8px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Simpan Produk</button>
                <button type="button" class="btn btn-ghost" onclick="resetProdForm()" title="Reset form">↺</button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </main>
  </div>

  <script src="js/app.js?v=<?= time() ?>"></script>
  <script>
    function editProduct(p) {
      document.getElementById('prod-edit-id').value = p.id;
      document.getElementById('prod-nama').value = p.nama;
      document.getElementById('prod-kode').value = p.kode || '';
      document.getElementById('prod-kategori').value = p.kategori;
      document.getElementById('prod-harga').value = p.harga;
      document.getElementById('prod-stok').value = p.stok;
      document.getElementById('prod-harga-min').value = p.harga_min || '';
      document.getElementById('prod-harga-max').value = p.harga_max || '';
      document.getElementById('prod-satuan').value = p.satuan;
      document.getElementById('prod-sup-id').value = p.supplier_id || '';
      document.getElementById('prod-specs').value = p.spesifikasi || '';
      document.getElementById('prod-desc').value = p.deskripsi || '';

      document.getElementById('form-icon').textContent = '✏️';
      document.getElementById('form-title').textContent = 'Edit Produk: ' + p.nama;
      document.getElementById('form-card').scrollIntoView({ behavior: 'smooth' });
    }

    function resetProdForm() {
      document.getElementById('prod-edit-id').value = '';
      document.getElementById('prod-form').reset();
      document.getElementById('prod-satuan').value = 'PCS';
      document.getElementById('prod-stok').value = '0';
      document.getElementById('form-icon').textContent = '➕';
      document.getElementById('form-title').textContent = 'Tambah Produk Baru';
    }
  </script>
</body>
</html>
