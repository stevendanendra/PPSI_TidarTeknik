<?php
/**
 * download.php — melayani file upload (bukti bayar & bukti retur) hanya untuk pihak yang berhak:
 *  - Admin yang sedang login, atau
 *  - Customer pemilik pesanan/retur tersebut.
 * Pemakaian: download.php?t=payment&id=<payments.id>  |  download.php?t=return&id=<return_media.id>
 */
define('NO_CSRF_INJECT', true); // jangan ubah isi file biner
require_once 'auth.php';
require_once 'customer-auth.php';
require_once 'lib.php';

function deny(int $code = 404): void {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    echo $code === 403 ? 'Akses ditolak' : 'File tidak ditemukan';
    exit;
}

$type = $_GET['t'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$isAdmin = isAdminLoggedIn();
$custId = isCustomerLoggedIn() ? ($_SESSION[CUSTOMER_ID_KEY] ?? null) : null;
if (!$isAdmin && !$custId) deny(403);

if ($type === 'payment') {
    $row = dbRow("SELECT bukti_file AS file, customer_id FROM payments WHERE id = ?", [$id]);
} elseif ($type === 'return') {
    $row = dbRow("SELECT m.file, r.customer_id FROM return_media m JOIN returns r ON r.id = m.return_id WHERE m.id = ?", [$id]);
} else {
    deny();
}
if (!$row || empty($row['file'])) deny();
if (!$isAdmin && $row['customer_id'] !== $custId) deny(403);

// Path harus tetap di dalam folder upload (cegah path traversal)
$base = realpath(uploadDir());
$path = $base ? realpath($base . '/' . ltrim($row['file'], '/\\')) : false;
if (!$path || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) deny();

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
$allowed = array_keys(MIME_PROOF + MIME_VIDEO);
if (!in_array($mime, $allowed, true)) deny(403);

$size = filesize($path);
$start = 0;
$end = $size - 1;
header('Accept-Ranges: bytes');
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
header('Cache-Control: private, max-age=3600');
header('Content-Disposition: inline; filename="' . basename($path) . '"');

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
    if ($m[1] !== '') $start = (int)$m[1];
    if ($m[2] !== '') $end = min((int)$m[2], $size - 1);
    if ($m[1] === '' && $m[2] !== '') { $start = max(0, $size - (int)$m[2]); $end = $size - 1; }
    if ($start > $end || $start >= $size) {
        http_response_code(416);
        header("Content-Range: bytes */$size");
        exit;
    }
    http_response_code(206);
    header("Content-Range: bytes $start-$end/$size");
}
header('Content-Length: ' . ($end - $start + 1));

while (ob_get_level() > 0) ob_end_clean();
$fh = fopen($path, 'rb');
fseek($fh, $start);
$left = $end - $start + 1;
while ($left > 0 && !feof($fh)) {
    $chunk = fread($fh, min(8192, $left));
    echo $chunk;
    $left -= strlen($chunk);
}
fclose($fh);
