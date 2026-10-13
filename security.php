<?php
/**
 * security.php — fondasi keamanan bersama (dimuat paling awal oleh auth.php dan customer-auth.php):
 *  - cookie sesi aman (HttpOnly, SameSite=Lax, Secure bila HTTPS) lalu session_start()
 *  - header keamanan dasar
 *  - perlindungan CSRF: token per sesi; setiap request POST wajib membawa token
 *    (field "csrf_token" pada form, atau header "X-CSRF-Token" untuk fetch/AJAX).
 *    Token disisipkan otomatis ke setiap <form method="post"> dan ke <meta name="csrf-token">
 *    pada output HTML, dan js/app.js menambahkan header ke semua fetch non-GET.
 */

if (!defined('SECURITY_BOOTED')) {
    define('SECURITY_BOOTED', true);

    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();
    }

    if (!headers_sent()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /** Token CSRF untuk sesi ini (dibuat bila belum ada). */
    function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function csrfTokenValid(?string $given): bool {
        return is_string($given) && $given !== '' && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $given);
    }

    /** Tolak request POST tanpa token valid. */
    function csrfEnforce(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
        $given = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (csrfTokenValid($given)) return;

        http_response_code(403);
        $wantsJson = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'json') !== false
            || isset($_SERVER['HTTP_X_CSRF_TOKEN'])
            || in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['api.php', 'customer-api.php'], true);
        if ($wantsJson) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Sesi tidak valid (CSRF). Muat ulang halaman lalu coba lagi.']);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><meta charset="utf-8"><title>403</title><body style="font-family:sans-serif;padding:40px">'
               . '<h2>Permintaan ditolak</h2><p>Token keamanan (CSRF) tidak valid atau sudah kedaluwarsa. '
               . 'Muat ulang halaman sebelumnya lalu ulangi aksi Anda.</p></body>';
        }
        exit;
    }

    /** Sisipkan token ke semua form POST dan <meta> ke <head> pada output HTML. */
    function csrfInject(string $html): string {
        foreach (headers_list() as $h) {
            if (stripos($h, 'content-type:') === 0 && stripos($h, 'text/html') === false) return $html;
        }
        if (stripos($html, '<form') === false && stripos($html, '</head>') === false) return $html;
        $tok = htmlspecialchars(csrfToken(), ENT_QUOTES);
        $html = preg_replace_callback('/<form\b[^>]*\bmethod\s*=\s*["\']?post["\']?[^>]*>/i', function ($m) use ($tok) {
            return $m[0] . '<input type="hidden" name="csrf_token" value="' . $tok . '">';
        }, $html);
        if (stripos($html, 'name="csrf-token"') === false) {
            // color-scheme: tema aplikasi dirancang sendiri; cegah "auto dark mode" browser membalik warna & merusak kontras
            $meta = '<meta name="csrf-token" content="' . $tok . '">'
                  . (stripos($html, 'name="color-scheme"') === false ? '<meta name="color-scheme" content="only light">' : '');
            $html = preg_replace('/<\/head>/i', $meta . '</head>', $html, 1);
        }
        return $html;
    }

    csrfEnforce();
    if (!defined('NO_CSRF_INJECT')) {
        ob_start('csrfInject');
    }
}
