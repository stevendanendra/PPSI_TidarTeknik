<?php
require_once 'db.php';

function addColumn($conn, $table, $column, $type) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE '$column'");
    if (mysqli_num_rows($res) == 0) {
        if (mysqli_query($conn, "ALTER TABLE $table ADD COLUMN $column $type")) {
            echo "Kolom $column ditambahkan ke $table.<br>";
        } else {
            echo "Gagal menambahkan $column ke $table: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Kolom $column sudah ada di $table.<br>";
    }
}

echo "Starting migration...<br>";

// PO Items
addColumn($conn, 'po_items', 'has_specs', 'TINYINT(1) DEFAULT 0');
addColumn($conn, 'po_items', 'specs', 'TEXT');

// Penawaran Items
addColumn($conn, 'penawaran_items', 'has_specs', 'TINYINT(1) DEFAULT 0');
addColumn($conn, 'penawaran_items', 'specs', 'TEXT');

echo "Migration finished.<br>";
?>
