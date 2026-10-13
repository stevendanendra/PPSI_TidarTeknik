<?php
// HTTPS Force removed for local dev
/**
 * auth.php — Session Guard
 * Include di bagian paling atas setiap halaman yang dilindungi.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi akun (ganti password sesuai kebutuhan)
define('AUTH_USERNAME', 'tidarteknik');
define('AUTH_PASSWORD', 'tidarteknik333777');
define('AUTH_SESSION_KEY', 'tidar_auth');
define('AUTH_SESSION_TIMEOUT', 8 * 60 * 60); // 8 jam dalam detik

/**
 * Cek apakah user sudah login dan sesi masih valid.
 * Jika belum, redirect ke halaman login.
 * Dengan $json = true (untuk endpoint AJAX), balas JSON 401 tanpa redirect.
 */
function requireLogin(bool $json = false): void {
    if (
        !isset($_SESSION[AUTH_SESSION_KEY]) ||
        !$_SESSION[AUTH_SESSION_KEY] ||
        (time() - ($_SESSION['auth_time'] ?? 0)) > AUTH_SESSION_TIMEOUT
    ) {
        if ($json) {
            session_destroy();
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        // Simpan halaman tujuan agar bisa redirect balik setelah login
        $_SESSION['auth_redirect'] = $_SERVER['REQUEST_URI'];
        session_destroy();
        header('Location: login.php');
        exit;
    }
}
?>
