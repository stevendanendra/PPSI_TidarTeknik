<?php
require_once 'db.php';

echo "=== VERIFIKASI TAHAP 1: SKEMA DATABASE ===\n";

$tables = ['customers', 'products', 'product_requests', 'orders', 'order_items', 'customer_wishlist'];
foreach ($tables as $t) {
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$t'");
    if (mysqli_num_rows($res) > 0) {
        $cnt = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM `$t`"))[0];
        echo "[OK] Tabel `$t` ada ($cnt baris)\n";
    } else {
        echo "[FAIL] Tabel `$t` TIDAK DITEMUKAN!\n";
    }
}

echo "\nKolom customers:\n";
$cres = mysqli_query($conn, "SHOW COLUMNS FROM customers");
while ($c = mysqli_fetch_assoc($cres)) {
    echo "  - {$c['Field']} ({$c['Type']})\n";
}

echo "\nSample customer login demo:\n";
$demo = mysqli_query($conn, "SELECT id, nama, email, telepon, pic, status FROM customers WHERE email IS NOT NULL LIMIT 1");
if ($row = mysqli_fetch_assoc($demo)) {
    print_r($row);
} else {
    echo "Belum ada customer dengan email.\n";
}

echo "\nSample produk katalog:\n";
$prods = mysqli_query($conn, "SELECT id, kode, nama, kategori, harga, stok FROM products LIMIT 3");
while ($p = mysqli_fetch_assoc($prods)) {
    print_r($p);
}

echo "\n=== VERIFIKASI SELESAI ===\n";
