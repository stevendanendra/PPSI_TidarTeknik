<?php
/**
 * customer-auth.php — Session Guard & Helper khusus Customer Portal
 * CV. Tidar Teknik
 */

require_once __DIR__ . '/security.php';

require_once __DIR__ . '/lib.php';

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
    // Akun yang dinonaktifkan/dihapus admin langsung kehilangan akses (tidak menunggu sesi habis)
    static $statusCache = [];
    $id = (string)$_SESSION[CUSTOMER_ID_KEY];
    if (!array_key_exists($id, $statusCache)) {
        $row = dbRow("SELECT status FROM customers WHERE id = ?", [$id]);
        $statusCache[$id] = $row['status'] ?? null;
    }
    if ($statusCache[$id] !== 'active') {
        unset($_SESSION[CUSTOMER_SESSION_KEY], $_SESSION[CUSTOMER_ID_KEY]);
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
    return dbRow("SELECT id, nama, kode, kota, alamat, npwp, email, telepon, pic, status, must_change_password, tutorial_seen
                  FROM customers WHERE id = ? LIMIT 1", [(string)$_SESSION[CUSTOMER_ID_KEY]]);
}
?>
