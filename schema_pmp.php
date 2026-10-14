<?php
/**
 * schema_pmp.php — Penambahan skema sesuai PMP (idempotent, dijalankan dari db.php).
 * Tabel/kolom hanya ditambah bila belum ada, jadi aman dipanggil berkali-kali
 * dan aman untuk database lama maupun hasil import dump.
 */

function pmpColumnExists(mysqli $conn, string $table, string $col): bool {
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ss', $table, $col);
    mysqli_stmt_execute($stmt);
    $found = mysqli_stmt_get_result($stmt)->num_rows > 0;
    mysqli_stmt_close($stmt);
    return $found;
}

function pmpAddColumn(mysqli $conn, string $table, string $col, string $definition): void {
    if (!pmpColumnExists($conn, $table, $col)) {
        mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$col` $definition");
    }
}

function ensurePmpSchema(mysqli $conn): void {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS app_meta (k VARCHAR(50) PRIMARY KEY, v VARCHAR(100) NOT NULL)");
    $r = mysqli_query($conn, "SELECT v FROM app_meta WHERE k = 'pmp_schema'");
    if ($r && ($row = mysqli_fetch_row($r)) && (int)$row[0] >= 4) {
        return;
    }

    // --- Autentikasi & akun ---
    pmpAddColumn($conn, 'customers', 'must_change_password', "TINYINT(1) NOT NULL DEFAULT 0");
    pmpAddColumn($conn, 'customers', 'tutorial_seen', "TINYINT(1) NOT NULL DEFAULT 0");
    pmpAddColumn($conn, 'customers', 'reset_token_hash', "VARCHAR(64) NULL");
    pmpAddColumn($conn, 'customers', 'reset_expires', "DATETIME NULL");
    // Akun demo/lama tidak perlu tutorial ulang
    // (hanya akun baru dari admin yang tutorial_seen = 0 secara default).

    // --- Katalog & supplier ---
    pmpAddColumn($conn, 'products', 'harga_min', "DECIMAL(15,2) NULL");
    pmpAddColumn($conn, 'products', 'harga_max', "DECIMAL(15,2) NULL");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS product_suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id VARCHAR(50) NOT NULL,
        supplier_id VARCHAR(50) NOT NULL,
        harga_modal DECIMAL(15,2) NULL,
        catatan VARCHAR(255) NULL,
        is_final TINYINT(1) NOT NULL DEFAULT 0,
        UNIQUE KEY uq_prod_sup (product_id, supplier_id),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
    )");
    // Supplier lama (kolom supplier_id) dijadikan kandidat final
    mysqli_query($conn, "INSERT IGNORE INTO product_suppliers (product_id, supplier_id, is_final)
                         SELECT id, supplier_id, 1 FROM products WHERE supplier_id IS NOT NULL");

    // --- Keranjang ---
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cart_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id VARCHAR(50) NOT NULL,
        product_id VARCHAR(50) NOT NULL,
        qty DECIMAL(10,2) NOT NULL DEFAULT 1,
        UNIQUE KEY uq_cart (customer_id, product_id),
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");

    // --- Order: status pembayaran DP/Term/Partial/Paid, penerimaan, pembatalan ---
    mysqli_query($conn, "ALTER TABLE orders MODIFY status_pembayaran
        ENUM('menunggu_pembayaran','sudah_bayar','terverifikasi','dibatalkan','dp','term','partial','paid')
        NOT NULL DEFAULT 'menunggu_pembayaran'");
    pmpAddColumn($conn, 'orders', 'total_dibayar', "DECIMAL(15,2) NOT NULL DEFAULT 0");
    pmpAddColumn($conn, 'orders', 'dikirim_at', "DATETIME NULL");
    pmpAddColumn($conn, 'orders', 'tanggal_terima', "DATETIME NULL");
    pmpAddColumn($conn, 'orders', 'alasan_batal', "TEXT NULL");

    // --- Item order terhubung ke PO supplier (satu order bisa dipecah ke beberapa PO) ---
    pmpAddColumn($conn, 'order_items', 'po_id', "VARCHAR(50) NULL");
    pmpAddColumn($conn, 'order_items', 'supplier_id', "VARCHAR(50) NULL");

    // --- PO terhubung ke order & supplier (satu order bisa banyak PO) ---
    pmpAddColumn($conn, 'po', 'order_id', "VARCHAR(50) NULL");
    pmpAddColumn($conn, 'po', 'supplier_id', "VARCHAR(50) NULL");

    // --- Penawaran: versioning & respon customer ---
    pmpAddColumn($conn, 'penawaran', 'customer_id', "VARCHAR(50) NULL");
    pmpAddColumn($conn, 'penawaran', 'order_id', "VARCHAR(50) NULL");
    pmpAddColumn($conn, 'penawaran', 'versi', "INT NOT NULL DEFAULT 1");
    pmpAddColumn($conn, 'penawaran', 'parent_id', "VARCHAR(50) NULL");
    pmpAddColumn($conn, 'penawaran', 'alasan_respon', "TEXT NULL");
    pmpAddColumn($conn, 'penawaran', 'responded_at', "DATETIME NULL");

    // --- Pembayaran (bukti transfer + verifikasi admin) ---
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(50) NOT NULL,
        customer_id VARCHAR(50) NOT NULL,
        tipe ENUM('dp','term','partial','pelunasan') NOT NULL DEFAULT 'pelunasan',
        jumlah DECIMAL(15,2) NOT NULL DEFAULT 0,
        bukti_file VARCHAR(255) NULL,
        catatan TEXT NULL,
        status ENUM('menunggu','terverifikasi','ditolak') NOT NULL DEFAULT 'menunggu',
        catatan_admin TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        verified_at DATETIME NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");

    // --- Retur ---
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS returns (
        id VARCHAR(50) PRIMARY KEY,
        nomor_retur VARCHAR(100) NOT NULL UNIQUE,
        order_id VARCHAR(50) NOT NULL,
        customer_id VARCHAR(50) NOT NULL,
        alasan TEXT NOT NULL,
        solusi ENUM('ganti','refund') NOT NULL DEFAULT 'ganti',
        status ENUM('diajukan','disetujui','ditolak','selesai') NOT NULL DEFAULT 'diajukan',
        catatan_admin TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at DATETIME NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS return_media (
        id INT AUTO_INCREMENT PRIMARY KEY,
        return_id VARCHAR(50) NOT NULL,
        file VARCHAR(255) NOT NULL,
        tipe ENUM('foto','video') NOT NULL DEFAULT 'foto',
        FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE
    )");

    // --- Notifikasi internal ---
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        audience ENUM('admin','customer') NOT NULL,
        customer_id VARCHAR(50) NULL,
        pesan VARCHAR(255) NOT NULL,
        link VARCHAR(255) NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_notif (audience, customer_id, is_read)
    )");

    // --- Pembatas percobaan login / permintaan reset password ---
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scope VARCHAR(20) NOT NULL,
        ident VARCHAR(191) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_attempt (scope, ident, ip, created_at)
    )");

    // --- Verifikasi email akun customer ---
    if (!pmpColumnExists($conn, 'customers', 'email_verified_at')) {
        pmpAddColumn($conn, 'customers', 'email_verified_at', "DATETIME NULL");
        pmpAddColumn($conn, 'customers', 'verify_token_hash', "VARCHAR(64) NULL");
        pmpAddColumn($conn, 'customers', 'verify_expires', "DATETIME NULL");
        // Akun yang sudah ada sebelum fitur ini dianggap terverifikasi (tidak terkunci keluar)
        mysqli_query($conn, "UPDATE customers SET email_verified_at = NOW() WHERE email IS NOT NULL AND email <> ''");
    }

    mysqli_query($conn, "REPLACE INTO app_meta (k, v) VALUES ('pmp_schema', '4')");
}
