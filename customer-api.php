<?php
/**
 * customer-api.php — AJAX API khusus Customer Portal
 * CV. Tidar Teknik
 */

header('Content-Type: application/json');
require_once 'customer-auth.php';
require_once 'lib.php';

if (!isCustomerLoggedIn()) {
    http_response_code(401);
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

    case 'add_to_cart':
        $pid = trim($_POST['product_id'] ?? '');
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        if (!activeProduct($pid)) {
            echo json_encode(['status' => 'error', 'message' => 'Produk tidak ditemukan']);
            break;
        }
        dbRun("INSERT INTO cart_items (customer_id, product_id, qty) VALUES (?, ?, ?)
               ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)", [$cId, $pid, (float)$qty]);
        $cnt = (int)dbRow("SELECT COUNT(*) AS c FROM cart_items WHERE customer_id = ?", [$cId])['c'];
        echo json_encode(['status' => 'success', 'count' => $cnt]);
        break;

    case 'notifications':
        $items = dbRows("SELECT id, pesan, link, is_read, created_at FROM notifications WHERE audience = 'customer' AND customer_id = ? ORDER BY id DESC LIMIT 30", [$cId]);
        $unread = (int)dbRow("SELECT COUNT(*) AS c FROM notifications WHERE audience = 'customer' AND customer_id = ? AND is_read = 0", [$cId])['c'];
        echo json_encode(['status' => 'success', 'unread' => $unread, 'items' => $items]);
        break;

    case 'notifications_read':
        dbRun("UPDATE notifications SET is_read = 1 WHERE audience = 'customer' AND customer_id = ?", [$cId]);
        echo json_encode(['status' => 'success']);
        break;

    case 'tutorial_reset':
        dbRun("UPDATE customers SET tutorial_seen = 0 WHERE id = ?", [$cId]);
        echo json_encode(['status' => 'success']);
        break;

    case 'tutorial_done':
        dbRun("UPDATE customers SET tutorial_seen = 1 WHERE id = ?", [$cId]);
        echo json_encode(['status' => 'success']);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
        break;
}
