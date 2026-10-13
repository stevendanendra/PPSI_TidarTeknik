<?php
require_once 'db.php';

$queries = [
    "DROP TABLE IF EXISTS ledger",
    "CREATE TABLE ledger (
        id VARCHAR(50) PRIMARY KEY,
        type ENUM('sales', 'purchase_ppn', 'purchase_non_ppn') DEFAULT 'sales',
        tanggal DATE,
        nama VARCHAR(255),
        alamat TEXT,
        npwp_telp VARCHAR(100),
        ref_no VARCHAR(100),
        ship_date DATE,
        tax_serial VARCHAR(100),
        item_name TEXT,
        jasa_rakit DECIMAL(15,2) DEFAULT 0,
        qty DECIMAL(15,2) DEFAULT 0,
        price DECIMAL(15,2) DEFAULT 0,
        amount DECIMAL(15,2) DEFAULT 0,
        ppn DECIMAL(15,2) DEFAULT 0,
        grand_total DECIMAL(15,2) DEFAULT 0,
        pay_bank DECIMAL(15,2) DEFAULT 0,
        pay_cash DECIMAL(15,2) DEFAULT 0,
        selisih DECIMAL(15,2) DEFAULT 0,
        payment_date DATE,
        deposit DECIMAL(15,2) DEFAULT 0,
        adm_fee DECIMAL(15,2) DEFAULT 0,
        outstanding DECIMAL(15,2) DEFAULT 0,
        catatan TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success: $q\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>
