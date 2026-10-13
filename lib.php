<?php
/**
 * lib.php — helper bersama (notifikasi, penomoran dokumen, upload file, status order).
 * Butuh $conn dari db.php.
 */
require_once __DIR__ . '/db.php';

const AUTO_COMPLETE_DAYS = 7; // pesanan "dikirim" otomatis selesai setelah N hari tanpa konfirmasi customer

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** Prepared statement ringkas: kembalikan semua baris. */
function dbRows(string $sql, array $params = []): array {
    global $conn;
    $stmt = dbRun($sql, $params);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function dbRow(string $sql, array $params = []): ?array {
    return dbRows($sql, $params)[0] ?? null;
}

/** Jalankan statement non-select (atau select), kembalikan stmt. */
function dbRun(string $sql, array $params = []): mysqli_stmt {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    if ($params) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return $stmt;
}

function newId(string $prefix): string {
    return $prefix . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 10);
}

/** Notifikasi internal. $audience: 'admin' atau 'customer' (isi $customerId untuk customer). */
function notify(string $audience, ?string $customerId, string $pesan, ?string $link = null): void {
    dbRun("INSERT INTO notifications (audience, customer_id, pesan, link) VALUES (?, ?, ?, ?)",
        [$audience, $customerId, mb_substr($pesan, 0, 255), $link]);
}

function romanMonth(int $m): string {
    return ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$m];
}

/**
 * Nomor dokumen otomatis: PREFIX/0001/TT/BULAN-ROMAWI/TAHUN, urut per tahun.
 * $table salah satu: po, nota, penawaran.
 */
function nextDocNumber(string $table, string $prefix): string {
    if (!in_array($table, ['po', 'nota', 'penawaran'], true)) {
        throw new InvalidArgumentException('Tabel tidak dikenal');
    }
    $year = date('Y');
    $rows = dbRows("SELECT nomor FROM `$table` WHERE nomor LIKE ?", [$prefix . '/%/TT/%/' . $year]);
    $max = 0;
    foreach ($rows as $r) {
        $parts = explode('/', $r['nomor']);
        $max = max($max, (int)($parts[1] ?? 0));
    }
    return sprintf('%s/%04d/TT/%s/%s', $prefix, $max + 1, romanMonth((int)date('n')), $year);
}

/** Folder penyimpanan upload. Disarankan di luar web root (set 'upload_dir' di config.php). */
function uploadDir(): string {
    return rtrim((string)appConfig('upload_dir', __DIR__ . '/uploads'), "/\\");
}

/**
 * Simpan file upload ke <upload_dir>/<sub>/ setelah validasi tipe (berdasarkan isi file) & ukuran.
 * Kembalikan [path relatif "sub/nama.ext" | null, pesan error | null]. File hanya bisa dibuka lewat download.php.
 */
function saveUpload(array $file, string $sub, array $allowedMime, int $maxBytes): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'Upload gagal (kode ' . $file['error'] . ').'];
    if ($file['size'] > $maxBytes) return [null, 'Ukuran file melebihi ' . round($maxBytes / 1048576) . ' MB.'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowedMime[$mime])) return [null, 'Tipe file tidak diizinkan.'];
    $dir = uploadDir() . '/' . $sub;
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) return [null, 'Folder upload tidak bisa dibuat.'];
    $name = bin2hex(random_bytes(16)) . '.' . $allowedMime[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) return [null, 'File gagal disimpan.'];
    return [$sub . '/' . $name, null];
}

const MIME_IMAGE = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const MIME_VIDEO = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];
const MIME_PROOF = MIME_IMAGE + ['application/pdf' => 'pdf'];

/**
 * Hitung ulang status pembayaran order dari payments terverifikasi.
 * paid bila total_dibayar >= total; selain itu ikuti tipe pembayaran terakhir (dp/term/partial).
 */
function recalcOrderPayment(string $orderId): void {
    $order = dbRow("SELECT total, status_pembayaran FROM orders WHERE id = ?", [$orderId]);
    if (!$order) return;
    $verified = dbRows("SELECT tipe, jumlah FROM payments WHERE order_id = ? AND status = 'terverifikasi' ORDER BY id", [$orderId]);
    $sum = 0.0; $last = null;
    foreach ($verified as $v) { $sum += (float)$v['jumlah']; $last = $v['tipe']; }
    if (!$verified) {
        $hasPending = dbRow("SELECT 1 AS x FROM payments WHERE order_id = ? AND status = 'menunggu' LIMIT 1", [$orderId]);
        $status = $hasPending ? 'sudah_bayar' : 'menunggu_pembayaran';
    } elseif ($sum + 0.001 >= (float)$order['total']) {
        $status = 'paid';
    } else {
        $status = ['dp' => 'dp', 'term' => 'term'][$last] ?? 'partial';
    }
    if ($order['status_pembayaran'] !== 'dibatalkan') {
        dbRun("UPDATE orders SET total_dibayar = ?, status_pembayaran = ? WHERE id = ?", [$sum, $status, $orderId]);
    }
}

/** Selesaikan otomatis pesanan 'dikirim' yang melewati batas hari tanpa konfirmasi. */
function autoCompleteOrders(): void {
    $rows = dbRows("SELECT id, customer_id, nomor_order FROM orders
                    WHERE status_pesanan = 'dikirim' AND dikirim_at IS NOT NULL
                      AND dikirim_at < (NOW() - INTERVAL " . (int)AUTO_COMPLETE_DAYS . " DAY)");
    foreach ($rows as $r) {
        dbRun("UPDATE orders SET status_pesanan = 'selesai', tanggal_terima = NOW() WHERE id = ?", [$r['id']]);
        notify('customer', $r['customer_id'], "Pesanan {$r['nomor_order']} otomatis selesai (batas " . AUTO_COMPLETE_DAYS . " hari).", 'customer-order-detail.php?id=' . $r['id']);
        notify('admin', null, "Pesanan {$r['nomor_order']} otomatis selesai.", 'admin-orders.php');
    }
}

/** Label harga katalog: rentang bila harga_min/harga_max diisi, selain itu harga tunggal. */
function hargaLabel(array $p): string {
    $fmt = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
    $min = isset($p['harga_min']) ? (float)$p['harga_min'] : 0.0;
    $max = isset($p['harga_max']) ? (float)$p['harga_max'] : 0.0;
    if ($min > 0 && $max > $min) return $fmt($min) . ' – ' . $fmt($max);
    return $fmt($p['harga'] ?? 0);
}

/** Pastikan item keranjang/produk valid: kembalikan produk aktif atau null. */
function activeProduct(string $id): ?array {
    return dbRow("SELECT * FROM products WHERE id = ? AND is_active = 1", [$id]);
}

/** Ubah struktur $_FILES multi-file ($_FILES['x']['name'][]) jadi daftar file tunggal. */
function normalizeFiles(array $f): array {
    if (!isset($f['name'])) return [];
    if (!is_array($f['name'])) return [$f];
    $out = [];
    foreach ($f['name'] as $i => $_) {
        $out[] = ['name' => $f['name'][$i], 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i],
                  'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

// ---------- Pembatas percobaan (anti brute-force) ----------

function clientIp(): string {
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/**
 * True bila kombinasi (scope, ident, IP) sudah gagal >= $max kali dalam $minutes menit,
 * atau IP yang sama sudah gagal >= $max * 4 kali pada scope itu (mencegah ganti-ganti username).
 */
function throttleBlocked(string $scope, string $ident, int $max = 5, int $minutes = 15): bool {
    $ident = strtolower(substr($ident, 0, 191));
    $ip = clientIp();
    $per = dbRow("SELECT COUNT(*) AS c FROM login_attempts WHERE scope = ? AND ident = ? AND ip = ? AND created_at > (NOW() - INTERVAL " . (int)$minutes . " MINUTE)", [$scope, $ident, $ip]);
    if ((int)($per['c'] ?? 0) >= $max) return true;
    $all = dbRow("SELECT COUNT(*) AS c FROM login_attempts WHERE scope = ? AND ip = ? AND created_at > (NOW() - INTERVAL " . (int)$minutes . " MINUTE)", [$scope, $ip]);
    return (int)($all['c'] ?? 0) >= $max * 4;
}

function throttleHit(string $scope, string $ident): void {
    dbRun("INSERT INTO login_attempts (scope, ident, ip) VALUES (?, ?, ?)", [$scope, strtolower(substr($ident, 0, 191)), clientIp()]);
    dbRun("DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)");
}

function throttleClear(string $scope, string $ident): void {
    dbRun("DELETE FROM login_attempts WHERE scope = ? AND ident = ? AND ip = ?", [$scope, strtolower(substr($ident, 0, 191)), clientIp()]);
}

// ---------- Helper keamanan tambahan ----------

/** True bila request datang dari mesin lokal / domain pengembangan (localhost, *.test, *.local). */
function isLocalRequest(): bool {
    $host = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
    return in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)
        || (bool)preg_match('/\.(test|local|localhost)$/', $host);
}

/**
 * Base URL aplikasi untuk tautan di email. Pakai 'base_url' dari config.php (wajib di production);
 * bila kosong, hanya diizinkan untuk request lokal. Header Host dari klien TIDAK dipercaya.
 */
function appBaseUrl(): string {
    $cfg = rtrim((string)appConfig('base_url', ''), '/');
    if ($cfg !== '') return $cfg;
    if (!isLocalRequest()) return '';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(str_replace("\\", '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
}

/** Catat error detail ke log server; tampilkan pesan generik (detail hanya jika 'debug' => true di config.php). */
function safeError(Throwable $e, string $generic = 'Terjadi kesalahan. Coba lagi atau hubungi admin.'): string {
    error_log('[tidar] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
    return appConfig('debug', false) ? $generic . ' (' . $e->getMessage() . ')' : $generic;
}
