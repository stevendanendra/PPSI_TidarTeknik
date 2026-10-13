<?php
/**
 * customer-products.php — Marketplace & Katalog Produk CV. Tidar Teknik
 * Sesuai Sitemap 3.2: Pencarian, Filter Kategori/Supplier, Cek Stok, Wishlist, Request Barang
 */

$pageTitle = 'Marketplace & Katalog Produk';
$pageSubtitle = 'Cari komponen teknik & industri ready stock atau ajukan permintaan barang';
require_once 'customer-header.php';

// Parameter pencarian & filter
$search   = trim($_GET['q'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$viewTab  =trim($_GET['tab'] ?? 'all'); // 'all' atau 'wishlist'

// Ambil daftar wishlist customer
$wishlistIds = [];
$wRes = mysqli_query($conn, "SELECT product_id FROM customer_wishlist WHERE customer_id = '{$currentCust['id']}'");
while ($wRow = mysqli_fetch_assoc($wRes)) {
    $wishlistIds[] = $wRow['product_id'];
}

// Ambil list semua kategori yang ada di DB
$catRes = mysqli_query($conn, "SELECT DISTINCT kategori FROM products WHERE is_active = 1 ORDER BY kategori ASC");
$categories = [];
while ($cRow = mysqli_fetch_assoc($catRes)) {
    $categories[] = $cRow['kategori'];
}

// Bangun query filter produk
$where = ["p.is_active = 1"];

if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(p.nama LIKE '%$s%' OR p.kode LIKE '%$s%' OR p.spesifikasi LIKE '%$s%' OR s.nama LIKE '%$s%')";
}

if (!empty($kategori)) {
    $k = mysqli_real_escape_string($conn, $kategori);
    $where[] = "p.kategori = '$k'";
}

if ($viewTab === 'wishlist') {
    if (empty($wishlistIds)) {
        $where[] = "1 = 0"; // kosongkan hasil jika belum ada wishlist
    } else {
        $inWish = "'" . implode("','", array_map(function($id) use ($conn) { return mysqli_real_escape_string($conn, $id); }, $wishlistIds)) . "'";
        $where[] = "p.id IN ($inWish)";
    }
}

$whereSql = implode(" AND ", $where);
$query = "SELECT p.*, s.nama AS nama_supplier 
          FROM products p 
          LEFT JOIN suppliers s ON p.supplier_id = s.id 
          WHERE $whereSql 
          ORDER BY p.created_at DESC";
$resProds = mysqli_query($conn, $query);
$products = mysqli_fetch_all($resProds, MYSQLI_ASSOC);

// Helper icon kategori
function getCategoryIcon($cat) {
    if (stripos($cat, 'Bearing') !== false) return '⚙️';
    if (stripos($cat, 'Chain') !== false || stripos($cat, 'Rantai') !== false) return '🔗';
    if (stripos($cat, 'Electric') !== false || stripos($cat, 'Control') !== false) return '⚡';
    if (stripos($cat, 'Seal') !== false) return '⭕';
    if (stripos($cat, 'Motor') !== false) return '🏎️';
    return '📦';
}
?>

<!-- BANNER HERO / CUSTOM REQUEST SHORTCUT -->
<div style="background: linear-gradient(135deg, var(--navy-900), var(--navy-800)); border-radius: var(--radius); padding: 24px; color: white; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 8px 24px rgba(0,0,0,0.12);">
  <div>
    <div style="font-size: 11px; font-weight: 700; color: var(--teal-400); text-transform: uppercase; letter-spacing: .06em;">Katalog Suplai Industri</div>
    <h2 style="font-size: 20px; font-weight: 800; margin: 4px 0 8px 0;">Butuh Barang Khusus di Luar Katalog?</h2>
    <p style="font-size: 13px; color: var(--navy-300); margin: 0; max-width: 620px;">
      CV. Tidar Teknik melayani pengadaan sistem dropship berbagai komponen teknik &amp; industri. Jika barang yang Anda cari tidak ada di katalog, Anda dapat mengajukan permintaan ketersediaan dan penawaran harga kepada admin.
    </p>
  </div>
  <a href="customer-request.php?new=1" class="btn btn-primary" style="padding: 12px 20px; font-weight: 700; box-shadow: 0 4px 16px rgba(14,165,233,.4);">
    📋 Ajukan Permintaan Khusus
  </a>
</div>

<!-- FILTER & SEARCH BAR -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-body" style="padding: 16px 20px;">
    <form method="GET" action="customer-products.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
      <!-- Tab mode -->
      <div style="display: flex; gap: 4px; background: var(--navy-100); padding: 4px; border-radius: 8px;">
        <a href="customer-products.php?tab=all" class="btn btn-sm <?= $viewTab !== 'wishlist' ? 'btn-primary' : 'btn-ghost' ?>">
          Semua Produk
        </a>
        <a href="customer-products.php?tab=wishlist" class="btn btn-sm <?= $viewTab === 'wishlist' ? 'btn-primary' : 'btn-ghost' ?>">
          ❤️ Wishlist (<?= count($wishlistIds) ?>)
        </a>
      </div>

      <!-- Search input -->
      <div style="flex: 1; min-width: 220px; position: relative;">
        <input 
          type="text" 
          name="q" 
          class="form-control" 
          placeholder="Cari nama barang, kode, tipe, atau spesifikasi..." 
          value="<?= htmlspecialchars($search) ?>"
          style="padding-left: 36px;"
        >
        <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 14px; opacity: 0.6;">🔍</span>
      </div>

      <!-- Kategori -->
      <div style="min-width: 170px;">
        <select name="kategori" class="form-control" onchange="this.form.submit()">
          <option value="">Semua Kategori</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= $kategori === $cat ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary">Filter</button>
      <?php if (!empty($search) || !empty($kategori) || $viewTab === 'wishlist'): ?>
        <a href="customer-products.php" class="btn btn-ghost" title="Reset filter">↺ Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- PRODUCT GRID -->
<?php if (empty($products)): ?>
  <div class="card" style="text-align: center; padding: 60px 20px;">
    <div style="font-size: 52px; margin-bottom: 12px;">🔍</div>
    <h3 style="font-size: 18px; color: var(--navy-800); margin-bottom: 6px;">Produk Tidak Ditemukan</h3>
    <p style="color: var(--navy-500); font-size: 13px; max-width: 460px; margin: 0 auto 20px auto;">
      <?= $viewTab === 'wishlist' ? 'Anda belum menambahkan produk ke wishlist.' : 'Tidak ada produk yang cocok dengan kriteria pencarian Anda. Anda dapat mengajukan permintaan barang baru ke admin CV. Tidar Teknik.' ?>
    </p>
    <a href="customer-request.php?new=1" class="btn btn-primary" style="margin: 0 auto; display: inline-flex;">
      ➕ Ajukan Permintaan Barang Baru
    </a>
  </div>
<?php else: ?>
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div style="font-size: 13px; color: var(--navy-500); font-weight: 500;">
      Menampilkan <strong><?= count($products) ?></strong> produk
      <?= !empty($kategori) ? 'dalam kategori <em>' . htmlspecialchars($kategori) . '</em>' : '' ?>
    </div>
  </div>

  <div class="product-grid">
    <?php foreach ($products as $p): 
      $isWish = in_array($p['id'], $wishlistIds);
    ?>
      <div class="product-card">
        <!-- Thumbnail -->
        <div class="product-thumb">
          <?php if (!empty($p['foto']) && file_exists(__DIR__ . '/' . $p['foto'])): ?>
            <img src="<?= htmlspecialchars($p['foto']) ?>" alt="<?= htmlspecialchars($p['nama']) ?>">
          <?php else: ?>
            <div class="icon-box"><?= getCategoryIcon($p['kategori']) ?></div>
          <?php endif; ?>

          <!-- Wishlist corner toggle button -->
          <button 
            type="button" 
            class="wishlist-btn-corner <?= $isWish ? 'active' : '' ?>" 
            onclick="toggleWishlist(this, '<?= $p['id'] ?>')"
            title="<?= $isWish ? 'Hapus dari Wishlist' : 'Simpan ke Wishlist' ?>"
          >
            <?= $isWish ? '❤️' : '🤍' ?>
          </button>

        </div>

        <!-- Body -->
        <div class="product-body">
          <div class="product-category"><?= htmlspecialchars($p['kategori']) ?></div>
          <a href="customer-product-detail.php?id=<?= urlencode($p['id']) ?>" class="product-title" style="text-decoration: none;">
            <?= htmlspecialchars($p['nama']) ?>
          </a>
          
          <div class="product-specs-preview">
            <?= htmlspecialchars($p['spesifikasi'] ?: ($p['deskripsi'] ?: 'Spesifikasi standar industri.')) ?>
          </div>

          <div class="product-price-box">
            <div>
              <div style="font-size: 10.5px; color: var(--navy-400); text-transform: uppercase;">Harga Estimasi</div>
              <div class="product-price">
                <?= h(hargaLabel($p)) ?>
              </div>
            </div>
            <span class="product-unit">/ <?= htmlspecialchars($p['satuan']) ?></span>
          </div>

          <!-- ACTIONS ACCORDING TO SITEMAP 3.2 & 3.3 -->
          <div class="product-actions">
            <a href="customer-product-detail.php?id=<?= urlencode($p['id']) ?>" class="btn btn-ghost" title="Lihat detail spesifikasi">
              Detail
            </a>

            <!-- Order multi-produk lewat keranjang (stok tidak ditampilkan; ketersediaan dicek admin ke supplier) -->
            <button type="button" class="btn btn-success" title="Masukkan ke keranjang" onclick="addToCart('<?= h($p['id']) ?>')">
              🛒 Keranjang
            </button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once 'customer-footer.php'; ?>
