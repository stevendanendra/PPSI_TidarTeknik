<?php
/**
 * customer-api.php — AJAX API khusus Customer Portal
 * CV. Tidar Teknik
 */

header('Content-Type: application/json');
require_once 'customer-auth.php';

if (!isCustomerLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$currentCust = getCurrentCustomer();
$cId = $currentCust['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'toggle_wishlist':
        $pId = mysqli_real_escape_string($conn, $_POST['product_id'] ?? '');
        if (empty($pId)) {
            echo json_encode(['status' => 'error', 'message' => 'Product ID is required']);
            exit;
        }

        $check = mysqli_query($conn, "SELECT id FROM customer_wishlist WHERE customer_id = '$cId' AND product_id = '$pId' LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "DELETE FROM customer_wishlist WHERE customer_id = '$cId' AND product_id = '$pId'");
            echo json_encode(['status' => 'success', 'is_wishlist' => false]);
        } else {
            mysqli_query($conn, "INSERT INTO customer_wishlist (customer_id, product_id) VALUES ('$cId', '$pId')");
            echo json_encode(['status' => 'success', 'is_wishlist' => true]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
        break;
}
