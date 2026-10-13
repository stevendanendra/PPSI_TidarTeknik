<?php
/**
 * customer-product-detail.php — Detail Produk Marketplace
 * CV. Tidar Teknik
 */

$pageTitle = 'Detail Produk';
$pageSubtitle = 'Rincian spesifikasi teknis dan ketersediaan barang';
require_once 'customer-header.php';

$id = trim($_GET['id'] ?? '');
if (empty($id)) {
    echo "<div class='alert alert-error'>ID produk tidak valid. <a href='customer-products.php'>Kembali ke katalog</a></div>";
    require_once 'customer-footer.php';
    exit;
}

$product = dbRow("SELECT p.*, s.nama AS nama_supplier
                  FROM products p
                  LEFT JOIN suppliers s ON p.supplier_id = s.id
                  WHERE p.id = ? AND p.is_active = 1 LIMIT 1", [$id]);

if (!$product) {
    echo "<div class='alert alert-error'>Produk tidak ditemukan atau telah dinonaktifkan. <a href='customer-products.php'>Kembali ke katalog</a></div>";
    require_once 'customer-footer.php';
    exit;
}

// Cek wishlist
$isWish = (bool)dbRow("SELECT id FROM customer_wishlist WHERE customer_id = ? AND product_id = ? LIMIT 1", [$currentCust['id'], $product['id']]);

// Helper icon kategori
function getCatIcon($cat) {
    if (stripos($cat, 'Bearing') !== false) return '⚙️';
    if (stripos($cat, 'Chain') !== false || stripos($cat, 'Rantai') !== false) return '🔗';
    if (stripos($cat, 'Electric') !== false || stripos($cat, 'Control') !== false) return '⚡';
    if (stripos($cat, 'Seal') !== false) return '⭕';
    return '📦';
}
?>

<div style="margin-bottom: 20px;">
  <a href="customer-products.php" class="btn btn-ghost" style="padding: 6px 12px; font-size: 12.5px;">
    ← Kembali ke Marketplace
  </a>
</div>

<div class="cust-split cust-split-left">
  <!-- SISI KIRI: GAMBAR / THUMBNAIL -->
  <div class="card" style="padding: 24px; text-align: center;">
    <div style="height: 280px; background: linear-gradient(135deg, var(--navy-50), var(--navy-100)); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; border: 1px solid var(--navy-200); position: relative; margin-bottom: 16px;">
      <?php if (!empty($product['foto']) && file_exists(__DIR__ . '/' . $product['foto'])): ?>
        <img src="<?= htmlspecialchars($product['foto']) ?>" alt="<?= htmlspecialchars($product['nama']) ?>" style="max-width: 90%; max-height: 90%; object-fit: contain;">
      <?php else: ?>
        <div style="font-size: 80px; filter: drop-shadow(0 8px 12px rgba(0,0,0,.1));">
          <?= getCatIcon($product['kategori']) ?>
        </div>
      <?php endif; ?>

      <button 
        type="button" 
        class="wishlist-btn-corner <?= $isWish ? 'active' : '' ?>" 
        onclick="toggleWishlist(this, '<?= $product['id'] ?>')"
        title="<?= $isWish ? 'Hapus dari Wishlist' : 'Simpan ke Wishlist' ?>"
        style="top: 14px; left: 14px; width: 38px; height: 38px; font-size: 18px;"
      >
        <?= $isWish ? '❤️' : '🤍' ?>
      </button>
    </div>

    <div style="display: flex; gap: 10px; justify-content: center;">
      <button type="button" class="btn btn-ghost" onclick="toggleWishlist(document.querySelector('.wishlist-btn-corner'), '<?= $product['id'] ?>')" style="flex:1">
        ❤️ <?= $isWish ? 'Di Wishlist' : 'Tambah ke Wishlist' ?>
      </button>
    </div>
  </div>

  <!-- SISI KANAN: SPESIFIKASI & AKSI -->
  <div class="card">
    <div class="card-body" style="padding: 28px;">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 12px;">
        <div>
          <span class="badge badge-blue" style="font-size: 11px; margin-bottom: 8px; display: inline-block;">
            <?= htmlspecialchars($product['kategori']) ?>
          </span>
          <h1 style="font-size: 22px; font-weight: 800; color: var(--navy-900); margin: 0 0 6px 0;">
            <?= htmlspecialchars($product['nama']) ?>
          </h1>
          <div style="font-size: 12px; color: var(--navy-400);">
            Kode SKU: <strong><?= htmlspecialchars($product['kode'] ?: '-') ?></strong>
            <?php if (!empty($product['nama_supplier'])): ?>
              &nbsp;•&nbsp; Rekanan: <?= htmlspecialchars($product['nama_supplier']) ?>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- PRICE CARD -->
      <div style="background: var(--navy-50); border-radius: var(--radius-sm); padding: 18px 22px; border: 1px solid var(--navy-200); margin: 20px 0; display: flex; align-items: center; justify-content: space-between;">
        <div>
          <div style="font-size: 11.5px; color: var(--navy-500); font-weight: 600; text-transform: uppercase;">Harga Satuan</div>
          <div style="font-size: 26px; font-weight: 800; color: var(--navy-900); margin-top: 2px;">
            <?= h(hargaLabel($product)) ?>
            <span style="font-size: 13px; font-weight: 500; color: var(--navy-500);">/ <?= htmlspecialchars($product['satuan']) ?></span>
          </div>
        </div>
        <div style="text-align: right; font-size: 11.5px; color: var(--navy-400);">
          Termasuk PPN Mode Tertera<br>
          <span style="color: var(--em-600); font-weight: 600;">Jaminan Kualitas Standar Industri</span>
        </div>
      </div>

      <!-- SPESIFIKASI TEKNIS -->
      <div style="margin-bottom: 24px;">
        <h3 style="font-size: 14px; font-weight: 700; color: var(--navy-800); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 12px; border-bottom: 1px solid var(--navy-100); padding-bottom: 6px;">
          Spesifikasi Teknis
        </h3>
        <div style="background: #fff; border: 1px solid var(--navy-200); border-radius: var(--radius-sm); padding: 14px; font-size: 13px; line-height: 1.7; color: var(--navy-700);">
          <?= nl2br(htmlspecialchars($product['spesifikasi'] ?: 'Tidak ada rincian spesifikasi tambahan.')) ?>
        </div>
      </div>

      <!-- DESKRIPSI -->
      <?php if (!empty($product['deskripsi'])): ?>
      <div style="margin-bottom: 28px;">
        <h3 style="font-size: 14px; font-weight: 700; color: var(--navy-800); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 8px;">
          Deskripsi Produk
        </h3>
        <p style="font-size: 13px; color: var(--navy-600); line-height: 1.6; margin: 0;">
          <?= nl2br(htmlspecialchars($product['deskripsi'])) ?>
        </p>
      </div>
      <?php endif; ?>

      <!-- ACTION BUTTONS ACCORDING TO SITEMAP 3.2 & 3.3 -->
      <div style="padding-top: 16px; border-top: 1px solid var(--navy-200);">
        <div style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
          <input type="number" id="cart-qty" class="form-control" value="1" min="1" step="1" style="width: 90px;" aria-label="Jumlah">
          <button type="button" class="btn btn-success" style="padding: 13px 28px; font-size: 14.5px; font-weight: 700; flex: 1; justify-content: center; box-shadow: 0 4px 14px rgba(16,185,129,.3);" onclick="addToCart('<?= h($product['id']) ?>', document.getElementById('cart-qty').value)">
            🛒 Masukkan ke Keranjang
          </button>
          <a href="customer-request.php?product_id=<?= urlencode($product['id']) ?>" class="btn btn-warning" title="Minta harga/penawaran khusus untuk barang ini">
            📋 Minta Penawaran
          </a>
        </div>
        <div style="font-size: 11.5px; color: var(--navy-400); margin-top: 8px;">
          Ketersediaan dipastikan admin ke supplier setelah pesanan dibuat.
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once 'customer-footer.php'; ?>
