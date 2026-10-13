<?php
require_once 'db.php';

// Add columns to po table if not exist
$columns = [
    "catatan" => "TEXT",
    "termin" => "INT DEFAULT 30"
];

foreach ($columns as $col => $type) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM po LIKE '$col'");
    if (mysqli_num_rows($res) == 0) {
        echo "Adding column $col to table po...\n";
        mysqli_query($conn, "ALTER TABLE po ADD COLUMN $col $type");
    }
}

echo "Database update check complete.\n";
?>
