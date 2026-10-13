<?php
/**
 * customer-auth.php — Session Guard & Helper khusus Customer Portal
 * CV. Tidar Teknik
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

define('CUSTOMER_SESSION_KEY', 'tidar_customer_auth');
define('CUSTOMER_ID_KEY',      'tidar_customer_id');
define('CUSTOMER_NAME_KEY',    'tidar_customer_name');
define('CUSTOMER_EMAIL_KEY',   'tidar_customer_email');
define('CUSTOMER_SESSION_TIMEOUT', 8 * 60 * 60); // 8 jam

/**
 * Cek apakah customer saat ini sudah login dan sesinya valid
 */
function isCustomerLoggedIn(): bool {
    if (
        empty($_SESSION[CUSTOMER_SESSION_KEY]) ||
        empty($_SESSION[CUSTOMER_ID_KEY]) ||
        (time() - ($_SESSION['customer_auth_time'] ?? 0)) > CUSTOMER_SESSION_TIMEOUT
    ) {
        return false;
    }
    return true;
}

/**
 * Middleware: Paksa login customer. Jika belum login, redirect ke customer-login.php
 */
function requireCustomerLogin(): void {
    if (!isCustomerLoggedIn()) {
        $_SESSION['customer_redirect'] = $_SERVER['REQUEST_URI'];
        unset($_SESSION[CUSTOMER_SESSION_KEY]);
        unset($_SESSION[CUSTOMER_ID_KEY]);
        header('Location: customer-login.php');
        exit;
    }
}

/**
 * Dapatkan data lengkap customer yang sedang login dari database
 */
function getCurrentCustomer(): ?array {
    global $conn;
    if (!isCustomerLoggedIn()) {
        return null;
    }
    $cId = mysqli_real_escape_string($conn, $_SESSION[CUSTOMER_ID_KEY]);
    $res = mysqli_query($conn, "SELECT id, nama, kode, kota, alamat, npwp, email, telepon, pic, status, must_change_password, tutorial_seen FROM customers WHERE id = '$cId' LIMIT 1");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return $row;
    }
    return null;
}
?>
