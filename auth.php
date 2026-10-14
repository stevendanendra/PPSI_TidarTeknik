<?php
// HTTPS Force removed for local dev
/**
 * auth.php — Session Guard
 * Include di bagian paling atas setiap halaman yang dilindungi.
 */

require_once __DIR__ . '/security.php';

// Kredensial dibaca dari config.php (tidak di-commit). Lihat config.sample.php.
$GLOBALS['app_config'] = (static function () {
    $file = __DIR__ . '/config.php';
    return is_file($file) ? (require $file) : [];
})();

/** Cek username + password admin terhadap config.php (hash bila ada). */
function verifyAdminLogin(string $user, string $pass): bool {
    $cfg  = $GLOBALS['app_config'];
    $u    = $cfg['admin_username'] ?? '';
    $hash = $cfg['admin_password_hash'] ?? '';
    $plain = $cfg['admin_password'] ?? '';
    if ($u === '' || !hash_equals($u, $user)) return false;
    if ($hash !== '') return password_verify($pass, $hash);
    return $plain !== '' && hash_equals($plain, $pass);
}
define('AUTH_SESSION_KEY', 'tidar_auth');
define('AUTH_SESSION_TIMEOUT', 8 * 60 * 60); // 8 jam dalam detik

/** True bila admin sedang login dengan sesi yang masih berlaku (tanpa redirect). */
function isAdminLoggedIn(): bool {
    return !empty($_SESSION[AUTH_SESSION_KEY])
        && (time() - ($_SESSION['auth_time'] ?? 0)) <= AUTH_SESSION_TIMEOUT;
}

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
        // Pengunjung yang membuka alamat root (/) diarahkan ke landing page, bukan ke login admin
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (substr($path, -1) === '/') {
            session_destroy();
            header('Location: home.php');
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
