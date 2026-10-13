<?php
require_once 'db.php';

// Check if columns exist
$res = mysqli_query($conn, "SHOW COLUMNS FROM nota_items LIKE 'has_specs'");
if (mysqli_num_rows($res) == 0) {
    if (mysqli_query($conn, "ALTER TABLE nota_items ADD COLUMN has_specs TINYINT(1) DEFAULT 0")) {
        echo "Kolom has_specs ditambahkan.<br>";
    } else {
        echo "Gagal menambahkan has_specs: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Kolom has_specs sudah ada.<br>";
}

$res = mysqli_query($conn, "SHOW COLUMNS FROM nota_items LIKE 'specs'");
if (mysqli_num_rows($res) == 0) {
    if (mysqli_query($conn, "ALTER TABLE nota_items ADD COLUMN specs TEXT")) {
        echo "Kolom specs ditambahkan.<br>";
    } else {
        echo "Gagal menambahkan specs: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Kolom specs sudah ada.<br>";
}
?>
