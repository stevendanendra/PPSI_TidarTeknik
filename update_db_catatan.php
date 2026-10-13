<?php
require_once 'db.php';

// Add missing columns to nota table
$queries = [
    "ALTER TABLE nota ADD COLUMN IF NOT EXISTS catatan TEXT",
    "ALTER TABLE nota ADD COLUMN IF NOT EXISTS termin INT DEFAULT 30"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Query executed: $q<br>";
    } else {
        echo "Error: " . mysqli_error($conn) . "<br>";
    }
}

echo "Database update finished.";
?>
