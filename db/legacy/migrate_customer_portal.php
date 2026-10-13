<?php
/**
 * migrate_customer_portal.php
 * Skrip migrasi database untuk Modul Customer Portal CV. Tidar Teknik
 */

require_once 'db.php';

echo "=== MEMULAI MIGRASI SKEMA CUSTOMER PORTAL ===\n\n";

// 1. Helper function untuk menambahkan kolom jika belum ada
function addColumnIfNotExists($conn, $table, $column, $definition) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if (mysqli_query($conn, $sql)) {
            echo "[OK] Kolom `$column` berhasil ditambahkan ke tabel `$table`.\n";
        } else {
            echo "[ERROR] Gagal menambahkan kolom `$column` ke `$table`: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "[SKIP] Kolom `$column` sudah ada pada tabel `$table`.\n";
    }
}

// 2. Tambah kolom pada tabel customers
echo "1. Menyesuaikan tabel `customers`...\n";
addColumnIfNotExists($conn, 'customers', 'email', 'VARCHAR(191) NULL UNIQUE AFTER `npwp`');
addColumnIfNotExists($conn, 'customers', 'password', 'VARCHAR(255) NULL AFTER `email`');
addColumnIfNotExists($conn, 'customers', 'telepon', 'VARCHAR(50) NULL AFTER `password`');
addColumnIfNotExists($conn, 'customers', 'pic', 'VARCHAR(100) NULL AFTER `telepon`');
addColumnIfNotExists($conn, 'customers', 'status', "VARCHAR(20) DEFAULT 'active' AFTER `pic`");
addColumnIfNotExists($conn, 'customers', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`');

// 3. Tabel products (Katalog Produk Marketplace)
echo "\n2. Membuat tabel `products`...\n";
$sqlProducts = "CREATE TABLE IF NOT EXISTS products (
    id VARCHAR(50) PRIMARY KEY,
    kode VARCHAR(50) UNIQUE,
    nama VARCHAR(255) NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    harga DECIMAL(15,2) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0,
    satuan VARCHAR(50) DEFAULT 'PCS',
    spesifikasi TEXT,
    deskripsi TEXT,
    foto VARCHAR(255) NULL,
    supplier_id VARCHAR(50) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (mysqli_query($conn, $sqlProducts)) {
    echo "[OK] Tabel `products` siap.\n";
} else {
    echo "[ERROR] Gagal membuat tabel `products`: " . mysqli_error($conn) . "\n";
}

// 4. Tabel product_requests (Fase 1: Request / Approval)
echo "\n3. Membuat tabel `product_requests`...\n";
$sqlRequests = "CREATE TABLE IF NOT EXISTS product_requests (
    id VARCHAR(50) PRIMARY KEY,
    nomor_request VARCHAR(100) NOT NULL UNIQUE,
    customer_id VARCHAR(50) NOT NULL,
    product_id VARCHAR(50) NULL,
    nama_barang VARCHAR(255) NOT NULL,
    spesifikasi TEXT,
    qty DECIMAL(10,2) NOT NULL DEFAULT 1,
    satuan VARCHAR(50) DEFAULT 'PCS',
    catatan TEXT,
    status ENUM('pending', 'approved', 'rejected', 'ordered') DEFAULT 'pending',
    harga_disetujui DECIMAL(15,2) NULL,
    catatan_admin TEXT,
    tanggal_review DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (mysqli_query($conn, $sqlRequests)) {
    echo "[OK] Tabel `product_requests` siap.\n";
} else {
    echo "[ERROR] Gagal membuat tabel `product_requests`: " . mysqli_error($conn) . "\n";
}

// 5. Tabel orders (Fase 2: Checkout & Payment)
echo "\n4. Membuat tabel `orders`...\n";
$sqlOrders = "CREATE TABLE IF NOT EXISTS orders (
    id VARCHAR(50) PRIMARY KEY,
    nomor_order VARCHAR(100) NOT NULL UNIQUE,
    customer_id VARCHAR(50) NOT NULL,
    tanggal DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    ongkir DECIMAL(15,2) NOT NULL DEFAULT 0,
    total DECIMAL(15,2) NOT NULL DEFAULT 0,
    alamat_pengiriman TEXT NOT NULL,
    kota_pengiriman VARCHAR(100) NOT NULL,
    ekspedisi VARCHAR(100) DEFAULT 'Ekspedisi Rekanan CV. Tidar Teknik',
    metode_pembayaran VARCHAR(100) NOT NULL,
    status_pembayaran ENUM('menunggu_pembayaran', 'sudah_bayar', 'terverifikasi', 'dibatalkan') DEFAULT 'menunggu_pembayaran',
    bukti_transfer VARCHAR(255) NULL,
    waktu_bayar DATETIME NULL,
    status_pesanan ENUM('pending', 'diproses', 'dikirim', 'selesai', 'dibatalkan') DEFAULT 'pending',
    no_resi VARCHAR(100) NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (mysqli_query($conn, $sqlOrders)) {
    echo "[OK] Tabel `orders` siap.\n";
} else {
    echo "[ERROR] Gagal membuat tabel `orders`: " . mysqli_error($conn) . "\n";
}

// 6. Tabel order_items (Detail Item Pesanan)
echo "\n5. Membuat tabel `order_items`...\n";
$sqlOrderItems = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL,
    product_id VARCHAR(50) NULL,
    request_id VARCHAR(50) NULL,
    nama_barang VARCHAR(255) NOT NULL,
    qty DECIMAL(10,2) NOT NULL DEFAULT 1,
    satuan VARCHAR(50) DEFAULT 'PCS',
    harga_satuan DECIMAL(15,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    spesifikasi TEXT,
    catatan TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (request_id) REFERENCES product_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (mysqli_query($conn, $sqlOrderItems)) {
    echo "[OK] Tabel `order_items` siap.\n";
} else {
    echo "[ERROR] Gagal membuat tabel `order_items`: " . mysqli_error($conn) . "\n";
}

// 7. Tabel customer_wishlist
echo "\n6. Membuat tabel `customer_wishlist`...\n";
$sqlWishlist = "CREATE TABLE IF NOT EXISTS customer_wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(50) NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (customer_id, product_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (mysqli_query($conn, $sqlWishlist)) {
    echo "[OK] Tabel `customer_wishlist` siap.\n";
} else {
    echo "[ERROR] Gagal membuat tabel `customer_wishlist`: " . mysqli_error($conn) . "\n";
}

// 8. Seeding data produk teknik awal (jika tabel products masih kosong)
$resProd = mysqli_query($conn, "SELECT COUNT(*) FROM products");
$countProd = mysqli_fetch_row($resProd)[0];

if ($countProd == 0) {
    echo "\n7. Menambahkan data produk teknik awal (seeding katalog)...\n";
    
    // Ambil supplier id pertama jika ada
    $resSup = mysqli_query($conn, "SELECT id FROM suppliers LIMIT 1");
    $supId = null;
    if ($rowSup = mysqli_fetch_assoc($resSup)) {
        $supId = $rowSup['id'];
    }
    
    $seedProducts = [
        [
            'id' => 'PRD-001',
            'kode' => 'BRG-33A120',
            'nama' => 'BEARING 33A120 ZWZ',
            'kategori' => 'Bearing & Bushing',
            'harga' => 1500000,
            'stok' => 15,
            'satuan' => 'PCS',
            'spesifikasi' => 'Brand: ZWZ | Type: Tapered Roller Bearing | Material: Chrome Steel | Origin: China High Grade',
            'deskripsi' => 'Bearing industri heavy duty cocok untuk beban radial dan aksial tinggi.',
            'supplier_id' => $supId
        ],
        [
            'id' => 'PRD-002',
            'kode' => 'BRG-NETRAL',
            'nama' => 'BEARING 33A120 NETRAL',
            'kategori' => 'Bearing & Bushing',
            'harga' => 450000,
            'stok' => 8,
            'satuan' => 'PCS',
            'spesifikasi' => 'Type: Neutral Roller Bearing | OD: 120mm | Standard Quality',
            'deskripsi' => 'Bearing netral untuk aplikasi putaran sedang dengan beban normal.',
            'supplier_id' => $supId
        ],
        [
            'id' => 'PRD-003',
            'kode' => 'CHN-80-1SS',
            'nama' => 'ROLLER CHAIN 80-1 SS JEPANG',
            'kategori' => 'Chain & Sprocket',
            'harga' => 13900000,
            'stok' => 5,
            'satuan' => 'BOX',
            'spesifikasi' => 'Size: ANSI 80-1 | Material: Stainless Steel SUS304 | Pitch: 25.4mm | Length: 10 FT / Box | Origin: Japan',
            'deskripsi' => 'Rantai industri stainless steel anti-korosi standar Jepang untuk pabrik biomassa & pengolahan makanan.',
            'supplier_id' => $supId
        ],
        [
            'id' => 'PRD-004',
            'kode' => 'CHN-80-2SS',
            'nama' => 'ROLLER CHAIN 80-2 SS JEPANG',
            'kategori' => 'Chain & Sprocket',
            'harga' => 17498000,
            'stok' => 0, // STOK KOSONG UNTUK UJI COBA FASE 1 (REQUEST BARANG)
            'satuan' => 'BOX',
            'spesifikasi' => 'Size: ANSI 80-2 (Double Strand) | Material: Stainless Steel SUS304 | Pitch: 25.4mm | Origin: Japan',
            'deskripsi' => 'Rantai ganda (double strand) stainless steel untuk transmisi daya beban sangat berat.',
            'supplier_id' => $supId
        ],
        [
            'id' => 'PRD-005',
            'kode' => 'ELC-SC-CY',
            'nama' => 'SPEED CONTROL CHANG YI 220V',
            'kategori' => 'Electrical & Automation',
            'harga' => 4000000,
            'stok' => 3,
            'satuan' => 'UNIT',
            'spesifikasi' => 'Input: AC 220V 50/60Hz | Output Power: Max 90W | Speed Range: 90-1400 RPM | Controller: Digital Display',
            'deskripsi' => 'Kontrol kecepatan motor AC presisi tinggi merek Chang Yi.',
            'supplier_id' => $supId
        ],
        [
            'id' => 'PRD-006',
            'kode' => 'CON-RP-80II',
            'nama' => 'CONNECTING LINK RP 80-II',
            'kategori' => 'Chain & Sprocket',
            'harga' => 350000,
            'stok' => 0, // STOK KOSONG UNTUK UJI COBA FASE 1 (REQUEST BARANG)
            'satuan' => 'PCS',
            'spesifikasi' => 'Type: Connecting Link / Master Link | Size: 80-2 | Material: Alloy Carbon Steel',
            'deskripsi' => 'Sambungan rantai tipe 80 double strand.',
            'supplier_id' => $supId
        ]
    ];

    foreach ($seedProducts as $p) {
        $id = mysqli_real_escape_string($conn, $p['id']);
        $kode = mysqli_real_escape_string($conn, $p['kode']);
        $nama = mysqli_real_escape_string($conn, $p['nama']);
        $kategori = mysqli_real_escape_string($conn, $p['kategori']);
        $harga = $p['harga'];
        $stok = $p['stok'];
        $satuan = mysqli_real_escape_string($conn, $p['satuan']);
        $specs = mysqli_real_escape_string($conn, $p['spesifikasi']);
        $desk = mysqli_real_escape_string($conn, $p['deskripsi']);
        $sup = $p['supplier_id'] ? "'" . mysqli_real_escape_string($conn, $p['supplier_id']) . "'" : "NULL";

        $ins = "INSERT INTO products (id, kode, nama, kategori, harga, stok, satuan, spesifikasi, deskripsi, supplier_id) 
                VALUES ('$id', '$kode', '$nama', '$kategori', $harga, $stok, '$satuan', '$specs', '$desk', $sup)";
        mysqli_query($conn, $ins);
    }
    echo "[OK] Berhasil memasukkan " . count($seedProducts) . " produk awal ke katalog (termasuk 2 produk dengan stok = 0 untuk tes Request Barang).\n";
} else {
    echo "\n[SKIP] Tabel `products` sudah memiliki $countProd data.\n";
}

// 9. Tambah akun demo customer pada data customer pertama jika belum ada akun
$chkCust = mysqli_query($conn, "SELECT id, nama, email FROM customers WHERE email IS NOT NULL LIMIT 1");
if (mysqli_num_rows($chkCust) == 0) {
    echo "\n8. Menyiapkan 1 akun customer demo untuk kemudahan testing...\n";
    $firstCust = mysqli_query($conn, "SELECT id FROM customers ORDER BY id ASC LIMIT 1");
    if ($rowC = mysqli_fetch_assoc($firstCust)) {
        $cId = $rowC['id'];
        $demoEmail = 'customer@tidarteknik.com';
        $demoPass = password_hash('customer123', PASSWORD_BCRYPT);
        $demoTelp = '081234567890';
        $demoPic = 'Bpk. Hendra';
        mysqli_query($conn, "UPDATE customers SET email = '$demoEmail', password = '$demoPass', telepon = '$demoTelp', pic = '$demoPic' WHERE id = '$cId'");
        echo "[OK] Akun demo berhasil dibuat pada Customer ID: $cId\n";
        echo "     Email: customer@tidarteknik.com\n";
        echo "     Password: customer123\n";
    }
}

echo "\n=== MIGRASI SELESAI DENGAN SUKSES ===\n";
