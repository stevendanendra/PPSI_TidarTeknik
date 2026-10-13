<?php
$conn = mysqli_connect('localhost', 'root', '', 'tidar_teknik');
if (!$conn) { die('Connection failed'); }

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

mysqli_query($conn, "DROP TABLE IF EXISTS nota_items");
mysqli_query($conn, "DROP TABLE IF EXISTS nota");
mysqli_query($conn, "DROP TABLE IF EXISTS po_items");
mysqli_query($conn, "DROP TABLE IF EXISTS po");
mysqli_query($conn, "DROP TABLE IF EXISTS penawaran_items");
mysqli_query($conn, "DROP TABLE IF EXISTS penawaran");

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

require 'db.php';

echo "Tables dropped and recreated successfully v2.";
?>
