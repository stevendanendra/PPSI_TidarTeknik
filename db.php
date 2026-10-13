<?php
/**
 * Database configuration and connection
 */

/** Nilai konfigurasi dari config.php (dengan default). */
function appConfig(string $key, $default = null) {
    static $c = null;
    if ($c === null) $c = is_file(__DIR__ . '/config.php') ? (require __DIR__ . '/config.php') : [];
    return $c[$key] ?? $default;
}

$cfg  = is_file(__DIR__ . '/config.php') ? (require __DIR__ . '/config.php') : [];
$host = $cfg['db_host'] ?? 'localhost';
$user = $cfg['db_user'] ?? 'root';
$pass = $cfg['db_pass'] ?? '';
$db   = $cfg['db_name'] ?? 'ppsi_tidarteknik';

$conn = mysqli_connect($host, $user, $pass);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $db";
if (!mysqli_query($conn, $sql)) {
    die("Error creating database: " . mysqli_error($conn));
}

mysqli_select_db($conn, $db);

// Create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS suppliers (
        id VARCHAR(50) PRIMARY KEY,
        nama VARCHAR(255) NOT NULL,
        kode VARCHAR(50),
        kota VARCHAR(100),
        alamat TEXT
    )",
    "CREATE TABLE IF NOT EXISTS customers (
        id VARCHAR(50) PRIMARY KEY,
        nama VARCHAR(255) NOT NULL,
        kode VARCHAR(50),
        kota VARCHAR(100),
        alamat TEXT,
        npwp VARCHAR(100),
        email VARCHAR(191) UNIQUE,
        password VARCHAR(255),
        telepon VARCHAR(50),
        pic VARCHAR(100),
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS po (
        id VARCHAR(50) PRIMARY KEY,
        nomor VARCHAR(100) NOT NULL,
        tanggal DATE,
        supplier VARCHAR(255),
        kota VARCHAR(100),
        note1 TEXT,
        note2 TEXT,
        alamat_kirim TEXT,
        ppn_mode VARCHAR(20),
        disc DECIMAL(15,2) DEFAULT 0,
        dp DECIMAL(15,2) DEFAULT 0,
        total DECIMAL(15,2) DEFAULT 0,
        status VARCHAR(50) DEFAULT 'Pending'
    )",
    "CREATE TABLE IF NOT EXISTS po_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_id VARCHAR(50),
        deskripsi TEXT,
        tipe VARCHAR(100),
        qty DECIMAL(10,2),
        price DECIMAL(15,2),
        amount DECIMAL(15,2),
        has_specs TINYINT(1) DEFAULT 0,
        specs TEXT,
        FOREIGN KEY (po_id) REFERENCES po(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS nota (
        id VARCHAR(50) PRIMARY KEY,
        nomor VARCHAR(100) NOT NULL,
        tanggal DATE,
        customer VARCHAR(255),
        alamat_invoice TEXT,
        alamat_sj TEXT,
        ref_po VARCHAR(100),
        ekspedisi VARCHAR(100),
        tujuan_kirim VARCHAR(255),
        ppn_mode VARCHAR(20),
        disc DECIMAL(15,2) DEFAULT 0,
        dp DECIMAL(15,2) DEFAULT 0,
        total DECIMAL(15,2) DEFAULT 0,
        status VARCHAR(50) DEFAULT 'Pending',
        catatan TEXT,
        termin INT DEFAULT 30
    )",
    "CREATE TABLE IF NOT EXISTS nota_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nota_id VARCHAR(50),
        qty DECIMAL(10,2),
        satuan VARCHAR(50),
        code VARCHAR(50),
        deskripsi TEXT,
        price DECIMAL(15,2),
        amount DECIMAL(15,2),
        has_specs TINYINT(1) DEFAULT 0,
        specs TEXT,
        FOREIGN KEY (nota_id) REFERENCES nota(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS penawaran (
        id VARCHAR(50) PRIMARY KEY,
        nomor VARCHAR(100) NOT NULL,
        tanggal DATE,
        customer VARCHAR(255),
        kota VARCHAR(100),
        att VARCHAR(100),
        sub_judul VARCHAR(255),
        note TEXT,
        show_total TINYINT(1) DEFAULT 1,
        total DECIMAL(15,2) DEFAULT 0,
        ppn_mode VARCHAR(20) DEFAULT 'none',
        disc DECIMAL(15,2) DEFAULT 0,
        dp DECIMAL(15,2) DEFAULT 0,
        status VARCHAR(50) DEFAULT 'Pending'
    )",
    "CREATE TABLE IF NOT EXISTS penawaran_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        penawaran_id VARCHAR(50),
        deskripsi TEXT,
        qty DECIMAL(10,2),
        satuan VARCHAR(50),
        price DECIMAL(15,2),
        amount DECIMAL(15,2),
        note_item TEXT,
        has_specs TINYINT(1) DEFAULT 0,
        specs TEXT,
        FOREIGN KEY (penawaran_id) REFERENCES penawaran(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS ledger (
        id VARCHAR(50) PRIMARY KEY,
        type VARCHAR(50) DEFAULT 'sales',
        tanggal DATE,
        nama VARCHAR(255),
        data LONGTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS products (
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
    )",
    "CREATE TABLE IF NOT EXISTS product_requests (
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
    )",
    "CREATE TABLE IF NOT EXISTS orders (
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
    )",
    "CREATE TABLE IF NOT EXISTS order_items (
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
    )",
    "CREATE TABLE IF NOT EXISTS customer_wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id VARCHAR(50) NOT NULL,
        product_id VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_wishlist (customer_id, product_id),
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )"
];

foreach ($tables as $sql) {
    if (!mysqli_query($conn, $sql)) {
        die("Error creating table: " . mysqli_error($conn));
    }
}

require_once __DIR__ . '/schema_pmp.php';
ensurePmpSchema($conn);
?>
