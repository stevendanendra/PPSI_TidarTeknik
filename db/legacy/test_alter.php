<?php
$conn = mysqli_connect('localhost', 'root', '', 'tidar_teknik');
if (!$conn) { die('Connection failed'); }

// Disable foreign key checks to allow altering
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

mysqli_query($conn, "ALTER TABLE nota MODIFY id VARCHAR(50)");
mysqli_query($conn, "ALTER TABLE nota_items MODIFY nota_id VARCHAR(50)");

mysqli_query($conn, "ALTER TABLE po MODIFY id VARCHAR(50)");
mysqli_query($conn, "ALTER TABLE po_items MODIFY po_id VARCHAR(50)");

mysqli_query($conn, "ALTER TABLE penawaran MODIFY id VARCHAR(50)");
mysqli_query($conn, "ALTER TABLE penawaran_items MODIFY penawaran_id VARCHAR(50)");

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

echo "Tables altered successfully.";
?>
