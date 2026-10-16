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
// GET hanya untuk membaca notifikasi; aksi lain wajib POST
$action = $_POST['action'] ?? ((($_GET['action'] ?? '') === 'notifications') ? 'notifications' : '');

switch ($action) {
    case 'toggle_wishlist':
        $pId = trim($_POST['product_id'] ?? '');
        if ($pId === '' || !activeProduct($pId)) {
            echo json_encode(['status' => 'error', 'message' => 'Product ID is required']);
            exit;
        }

        if (dbRow("SELECT id FROM customer_wishlist WHERE customer_id = ? AND product_id = ? LIMIT 1", [$cId, $pId])) {
            dbRun("DELETE FROM customer_wishlist WHERE customer_id = ? AND product_id = ?", [$cId, $pId]);
            echo json_encode(['status' => 'success', 'is_wishlist' => false]);
        } else {
            dbRun("INSERT INTO customer_wishlist (customer_id, product_id) VALUES (?, ?)", [$cId, $pId]);
            echo json_encode(['status' => 'success', 'is_wishlist' => true]);
        }
        break;

    case 'add_to_cart':
        $pid = trim($_POST['product_id'] ?? '');
        $rawQty = $_POST['qty'] ?? 1;
        $qty = is_numeric($rawQty) ? (float)$rawQty : 0.0;
        if (!is_finite($qty) || $qty < 1 || $qty > 100000 || floor($qty) != $qty) {
            echo json_encode(['status' => 'error', 'message' => 'Jumlah tidak valid (bilangan bulat 1–100.000).']);
            break;
        }
        if (!activeProduct($pid)) {
            echo json_encode(['status' => 'error', 'message' => 'Produk tidak ditemukan']);
            break;
        }
        $inCart = (float)(dbRow("SELECT qty FROM cart_items WHERE customer_id = ? AND product_id = ?", [$cId, $pid])['qty'] ?? 0);
        if ($inCart + $qty > 100000) {
            echo json_encode(['status' => 'error', 'message' => 'Total jumlah di keranjang untuk barang ini maksimal 100.000.']);
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
