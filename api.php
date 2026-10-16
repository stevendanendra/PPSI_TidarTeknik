<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

require_once 'auth.php';
requireLogin(true); // endpoint AJAX: balas JSON 401, bukan redirect HTML
session_write_close(); // sesi hanya dibaca; jangan blokir request paralel

require_once 'lib.php';

// Error mysqli dilempar sebagai exception supaya transaksi bisa di-rollback.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_set_charset($conn, 'utf8mb4');

$action = $_GET['action'] ?? '';

// Aksi yang mengubah data hanya boleh lewat POST (tidak bisa dipicu lewat link/GET)
if ((strpos($action, 'save_') === 0 || strpos($action, 'penawaran_') === 0 || $action === 'read_notifications')
    && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

/* ---------- helper ---------- */

/** Prepared statement: bind $params dengan tipe otomatis (i/d/s), execute, kembalikan stmt. */
function run(mysqli $conn, string $sql, array $params = []): mysqli_stmt {
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

function fetchAll(mysqli $conn, string $sql, array $params = []): array {
    $stmt = run($conn, $sql, $params);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function respond(array $body, int $code = 200): void {
    http_response_code($code);
    echo json_encode($body);
    exit;
}

/** Ambil nilai string dari array; null/array/objek jadi default. */
function str(array $a, string $key, string $default = ''): string {
    $v = $a[$key] ?? $default;
    return is_scalar($v) ? (string)$v : $default;
}

/**
 * Baca body JSON. Harus berupa array (daftar) berisi objek yang masing-masing punya id.
 * Payload kosong hanya boleh menghapus semua data bila ada flag eksplisit ?allow_empty=1.
 */
function readList(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data) || (!empty($data) && !array_is_list($data))) {
        respond(['status' => 'error', 'message' => 'Invalid data format'], 400);
    }
    foreach ($data as $row) {
        if (!is_array($row) || !is_scalar($row['id'] ?? null) || (string)$row['id'] === '') {
            respond(['status' => 'error', 'message' => 'Invalid data format: setiap item wajib punya id'], 400);
        }
    }
    return $data;
}

/**
 * Hapus baris di $table yang id-nya tidak ada di $ids.
 * $ids kosong => tidak menghapus apa pun, kecuali ?allow_empty=1.
 */
function deleteMissing(mysqli $conn, string $table, array $ids): void {
    if (!$ids) {
        if (($_GET['allow_empty'] ?? '') === '1') {
            mysqli_query($conn, "DELETE FROM `$table`");
        }
        return;
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    run($conn, "DELETE FROM `$table` WHERE id NOT IN ($in)", $ids);
}

/** Jalankan $fn dalam transaksi; rollback + JSON error bila gagal. */
function transaction(mysqli $conn, callable $fn): void {
    mysqli_begin_transaction($conn);
    try {
        $fn();
        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        respond(['status' => 'error', 'message' => safeError($e, 'Gagal menyimpan data. Periksa isian lalu coba lagi.')], 500);
    }
    echo json_encode(['status' => 'success']);
}

try {
switch ($action) {
    case 'get_suppliers':
        echo json_encode(fetchAll($conn, "SELECT * FROM suppliers"));
        break;

    case 'save_suppliers':
        $data = readList();
        transaction($conn, function () use ($conn, $data) {
            $ids = [];
            foreach ($data as $item) {
                $id = (string)$item['id'];
                $ids[] = $id;
                run($conn, "INSERT INTO suppliers (id, nama, kode, kota, alamat) VALUES (?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE nama = VALUES(nama), kode = VALUES(kode), kota = VALUES(kota), alamat = VALUES(alamat)",
                    [$id, str($item, 'nama'), str($item, 'kode'), str($item, 'kota'), str($item, 'alamat')]);
            }
            deleteMissing($conn, 'suppliers', $ids);
        });
        break;

    case 'get_customers':
        echo json_encode(fetchAll($conn, "SELECT * FROM customers"));
        break;

    case 'save_customers':
        $data = readList();
        transaction($conn, function () use ($conn, $data) {
            $ids = [];
            foreach ($data as $item) {
                $id = (string)$item['id'];
                $ids[] = $id;
                run($conn, "INSERT INTO customers (id, nama, kode, kota, alamat, npwp, pic) VALUES (?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                                nama = VALUES(nama), kode = VALUES(kode), kota = VALUES(kota),
                                alamat = VALUES(alamat), npwp = VALUES(npwp),
                                pic = IF(VALUES(pic) != '', VALUES(pic), pic)",
                    [$id, str($item, 'nama'), str($item, 'kode'), str($item, 'kota'), str($item, 'alamat'), str($item, 'npwp'), str($item, 'pic')]);
            }
            // Hapus customer yang tidak ada di payload, kecuali yang punya orders / product_requests.
            if ($ids) {
                $in = implode(',', array_fill(0, count($ids), '?'));
                $missing = fetchAll($conn, "SELECT id FROM customers WHERE id NOT IN ($in)", $ids);
            } elseif (($_GET['allow_empty'] ?? '') === '1') {
                $missing = fetchAll($conn, "SELECT id FROM customers");
            } else {
                $missing = [];
            }
            foreach ($missing as $row) {
                $delId = $row['id'];
                $used = fetchAll($conn, "SELECT 1 FROM orders WHERE customer_id = ? LIMIT 1", [$delId])
                     || fetchAll($conn, "SELECT 1 FROM product_requests WHERE customer_id = ? LIMIT 1", [$delId]);
                if (!$used) {
                    run($conn, "DELETE FROM customers WHERE id = ?", [$delId]);
                }
            }
        });
        break;

    case 'get_po':
        $pos = fetchAll($conn, "SELECT * FROM po ORDER BY tanggal DESC, id DESC");
        foreach ($pos as &$p) {
            $p['total'] = (float)($p['total'] ?? 0);
            $p['dp'] = (float)($p['dp'] ?? 0);
            $p['disc'] = (float)($p['disc'] ?? 0);
            $p['ppnMode'] = $p['ppn_mode'] ?? 'none';
            $p['alamatKirim'] = $p['alamat_kirim'] ?? '';
            $p['catatan'] = $p['catatan'] ?? '';
            $p['termin'] = (int)($p['termin'] ?? 30);
            $items = fetchAll($conn, "SELECT *, deskripsi AS 'desc', tipe AS 'type' FROM po_items WHERE po_id = ?", [$p['id']]);
            foreach ($items as &$it) {
                $it['qty'] = (float)($it['qty'] ?? 0);
                $it['price'] = (float)($it['price'] ?? 0);
                $it['amount'] = (float)($it['amount'] ?? 0);
                $it['hasSpecs'] = (bool)($it['has_specs'] ?? false);
                $it['specs'] = json_decode($it['specs'] ?? '{}', true);
            }
            unset($it);
            $p['items'] = $items;
        }
        unset($p);
        echo json_encode($pos);
        break;

    case 'save_po':
        $data = readList();
        transaction($conn, function () use ($conn, $data) {
            $ids = [];
            foreach ($data as $po) {
                $id = (string)$po['id'];
                $ids[] = $id;
                $nomorPo = str($po, 'nomor') !== '' ? str($po, 'nomor') : nextDocNumber('po', 'PO');
                run($conn, "INSERT INTO po (id, nomor, tanggal, supplier, kota, note1, note2, alamat_kirim, ppn_mode, disc, dp, total, status, catatan, termin)
                            VALUES (?, ?, COALESCE(NULLIF(?, ''), CURDATE()), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                                nomor = VALUES(nomor), tanggal = VALUES(tanggal), supplier = VALUES(supplier), kota = VALUES(kota),
                                note1 = VALUES(note1), note2 = VALUES(note2), alamat_kirim = VALUES(alamat_kirim),
                                ppn_mode = VALUES(ppn_mode), disc = VALUES(disc), dp = VALUES(dp), total = VALUES(total),
                                status = VALUES(status), catatan = VALUES(catatan), termin = VALUES(termin)",
                    [$id, $nomorPo, str($po, 'tanggal'), str($po, 'supplier'), str($po, 'kota'),
                     str($po, 'note1'), str($po, 'note2'), str($po, 'alamatKirim'), str($po, 'ppnMode', 'none'),
                     (float)($po['disc'] ?? 0), (float)($po['dp'] ?? 0), (float)($po['total'] ?? 0),
                     str($po, 'status', 'Belum Bayar'), str($po, 'catatan'), (int)($po['termin'] ?? 30)]);

                run($conn, "DELETE FROM po_items WHERE po_id = ?", [$id]);
                foreach (is_array($po['items'] ?? null) ? $po['items'] : [] as $item) {
                    if (!is_array($item)) continue;
                    run($conn, "INSERT INTO po_items (po_id, deskripsi, tipe, qty, price, amount, has_specs, specs) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [$id, str($item, 'desc'), str($item, 'type', str($item, 'tipe')),
                         (float)($item['qty'] ?? 0), (float)($item['price'] ?? 0), (float)($item['amount'] ?? 0),
                         ($item['hasSpecs'] ?? false) ? 1 : 0, json_encode($item['specs'] ?? [])]);
                }
            }
            deleteMissing($conn, 'po', $ids); // po_items ikut terhapus (ON DELETE CASCADE)
        });
        break;

    case 'get_nota':
        $notas = fetchAll($conn, "SELECT * FROM nota ORDER BY tanggal DESC, id DESC");
        foreach ($notas as &$n) {
            $n['total'] = (float)($n['total'] ?? 0);
            $n['dp'] = (float)($n['dp'] ?? 0);
            $n['disc'] = (float)($n['disc'] ?? 0);
            $n['ppnMode'] = $n['ppn_mode'] ?? 'none';
            $n['alamatInvoice'] = $n['alamat_invoice'] ?? '';
            $n['alamatSJ'] = $n['alamat_sj'] ?? '';
            $n['refPO'] = $n['ref_po'] ?? '';
            $n['tujuanKirim'] = $n['tujuan_kirim'] ?? '';
            $n['tanggalKirim'] = $n['tanggal_kirim'] ?? '';
            $n['seriFaktur'] = $n['seri_faktur'] ?? '';
            $n['pembayaran'] = (float)($n['pembayaran'] ?? 0);
            $n['tanggalPembayaran'] = $n['tanggal_pembayaran'] ?? '';
            $n['catatan'] = $n['catatan'] ?? '';
            $n['termin'] = (int)($n['termin'] ?? 30);
            $items = fetchAll($conn, "SELECT *, deskripsi AS 'desc' FROM nota_items WHERE nota_id = ?", [$n['id']]);
            foreach ($items as &$it) {
                $it['qty'] = (float)($it['qty'] ?? 0);
                $it['price'] = (float)($it['price'] ?? 0);
                $it['amount'] = (float)($it['amount'] ?? 0);
                $it['jasaRakit'] = (float)($it['jasa_rakit'] ?? 0);
                $it['hasSpecs'] = (bool)($it['has_specs'] ?? false);
                $it['specs'] = json_decode($it['specs'] ?? '{}', true);
            }
            unset($it);
            $n['items'] = $items;
        }
        unset($n);
        echo json_encode($notas);
        break;

    case 'save_nota':
        $data = readList();
        transaction($conn, function () use ($conn, $data) {
            $ids = [];
            foreach ($data as $nota) {
                $id = (string)$nota['id'];
                $ids[] = $id;
                $nomorNota = str($nota, 'nomor') !== '' ? str($nota, 'nomor') : nextDocNumber('nota', 'INV');
                run($conn, "INSERT INTO nota (id, nomor, tanggal, customer, alamat_invoice, alamat_sj, ref_po, ekspedisi, tujuan_kirim, tanggal_kirim, seri_faktur, pembayaran, tanggal_pembayaran, ppn_mode, disc, dp, total, status, catatan, termin)
                            VALUES (?, ?, NULLIF(?, ''), ?, ?, ?, ?, ?, ?, NULLIF(?, ''), ?, ?, NULLIF(?, ''), ?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                                nomor = VALUES(nomor), tanggal = VALUES(tanggal), customer = VALUES(customer),
                                alamat_invoice = VALUES(alamat_invoice), alamat_sj = VALUES(alamat_sj), ref_po = VALUES(ref_po),
                                ekspedisi = VALUES(ekspedisi), tujuan_kirim = VALUES(tujuan_kirim), tanggal_kirim = VALUES(tanggal_kirim),
                                seri_faktur = VALUES(seri_faktur), pembayaran = VALUES(pembayaran), tanggal_pembayaran = VALUES(tanggal_pembayaran),
                                ppn_mode = VALUES(ppn_mode), disc = VALUES(disc), dp = VALUES(dp), total = VALUES(total),
                                status = VALUES(status), catatan = VALUES(catatan), termin = VALUES(termin)",
                    [$id, $nomorNota, str($nota, 'tanggal'), str($nota, 'customer'),
                     str($nota, 'alamatInvoice'), str($nota, 'alamatSJ'), str($nota, 'refPO'), str($nota, 'ekspedisi'),
                     str($nota, 'tujuanKirim'), str($nota, 'tanggalKirim'), str($nota, 'seriFaktur'),
                     (float)($nota['pembayaran'] ?? 0), str($nota, 'tanggalPembayaran'), str($nota, 'ppnMode', 'none'),
                     (float)($nota['disc'] ?? 0), (float)($nota['dp'] ?? 0), (float)($nota['total'] ?? 0),
                     str($nota, 'status', 'Belum Bayar'), str($nota, 'catatan'), (int)($nota['termin'] ?? 30)]);

                run($conn, "DELETE FROM nota_items WHERE nota_id = ?", [$id]);
                foreach (is_array($nota['items'] ?? null) ? $nota['items'] : [] as $item) {
                    if (!is_array($item)) continue;
                    run($conn, "INSERT INTO nota_items (nota_id, qty, satuan, code, deskripsi, jasa_rakit, price, amount, has_specs, specs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$id, (float)($item['qty'] ?? 0), str($item, 'satuan'), str($item, 'code'), str($item, 'desc'),
                         (float)($item['jasaRakit'] ?? 0), (float)($item['price'] ?? 0), (float)($item['amount'] ?? 0),
                         ($item['hasSpecs'] ?? false) ? 1 : 0, json_encode($item['specs'] ?? [])]);
                }
            }
            deleteMissing($conn, 'nota', $ids);
        });
        break;

    case 'get_penawaran':
        $penawarans = fetchAll($conn, "SELECT * FROM penawaran ORDER BY tanggal DESC, id DESC");
        foreach ($penawarans as &$p) {
            $p['total'] = (float)($p['total'] ?? 0);
            $p['dp'] = (float)($p['dp'] ?? 0);
            $p['disc'] = (float)($p['disc'] ?? 0);
            $p['ppnMode'] = $p['ppn_mode'] ?? 'none';
            $items = fetchAll($conn, "SELECT *, deskripsi AS 'desc' FROM penawaran_items WHERE penawaran_id = ?", [$p['id']]);
            foreach ($items as &$it) {
                $it['qty'] = (float)($it['qty'] ?? 0);
                $it['price'] = (float)($it['price'] ?? 0);
                $it['amount'] = (float)($it['amount'] ?? 0);
                $it['hasSpecs'] = (bool)($it['has_specs'] ?? false);
                $it['specs'] = json_decode($it['specs'] ?? '{}', true);
            }
            unset($it);
            $p['items'] = $items;
        }
        unset($p);
        echo json_encode($penawarans);
        break;

    case 'save_penawaran':
        $data = readList();
        transaction($conn, function () use ($conn, $data) {
            $ids = [];
            foreach ($data as $pn) {
                $id = (string)$pn['id'];
                $ids[] = $id;
                $nomorPn = str($pn, 'nomor') !== '' ? str($pn, 'nomor') : nextDocNumber('penawaran', 'SP');
                run($conn, "INSERT INTO penawaran (id, nomor, tanggal, customer, kota, att, sub_judul, note, show_total, total, ppn_mode, disc, dp)
                            VALUES (?, ?, NULLIF(?, ''), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                                nomor = VALUES(nomor), tanggal = VALUES(tanggal), customer = VALUES(customer), kota = VALUES(kota),
                                att = VALUES(att), sub_judul = VALUES(sub_judul), note = VALUES(note), show_total = VALUES(show_total),
                                total = VALUES(total), ppn_mode = VALUES(ppn_mode), disc = VALUES(disc), dp = VALUES(dp)",
                    [$id, $nomorPn, str($pn, 'tanggal'), str($pn, 'customer'), str($pn, 'kota'), str($pn, 'att'),
                     str($pn, 'sub_judul'), str($pn, 'note'), isset($pn['show_total']) ? (int)$pn['show_total'] : 1,
                     (float)($pn['total'] ?? 0), str($pn, 'ppnMode', 'none'), (float)($pn['disc'] ?? 0), (float)($pn['dp'] ?? 0)]);

                run($conn, "DELETE FROM penawaran_items WHERE penawaran_id = ?", [$id]);
                foreach (is_array($pn['items'] ?? null) ? $pn['items'] : [] as $item) {
                    if (!is_array($item)) continue;
                    run($conn, "INSERT INTO penawaran_items (penawaran_id, deskripsi, qty, satuan, price, amount, note_item, has_specs, specs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$id, str($item, 'desc'), (float)($item['qty'] ?? 0), str($item, 'satuan'),
                         (float)($item['price'] ?? 0), (float)($item['amount'] ?? 0), str($item, 'note_item'),
                         ($item['hasSpecs'] ?? false) ? 1 : 0, json_encode($item['specs'] ?? [])]);
                }
            }
            deleteMissing($conn, 'penawaran', $ids);
        });
        break;

    case 'get_ledger':
        try {
            $ledger = fetchAll($conn, "SELECT * FROM ledger ORDER BY tanggal DESC, created_at DESC");
        } catch (mysqli_sql_exception $e) {
            echo json_encode([]);
            break;
        }
        foreach ($ledger as &$l) {
            $decoded = json_decode($l['data'] ?? '[]', true);
            if (is_array($decoded) && isset($decoded['data'])) {
                foreach ($decoded as $k => $v) {
                    if ($k !== 'id' && $k !== 'type' && $k !== 'tanggal' && $k !== 'nama') {
                        $l[$k] = $v;
                    }
                }
            } else {
                $l['data'] = $decoded;
            }
        }
        unset($l);
        echo json_encode($ledger);
        break;

    case 'save_ledger':
        $data = readList();
        transaction($conn, function () use ($conn, $data) {
            $ids = [];
            foreach ($data as $l) {
                $id = (string)$l['id'];
                $ids[] = $id;
                run($conn, "INSERT INTO ledger (id, type, tanggal, nama, data) VALUES (?, ?, NULLIF(?, ''), ?, ?)
                            ON DUPLICATE KEY UPDATE type = VALUES(type), tanggal = VALUES(tanggal), nama = VALUES(nama), data = VALUES(data)",
                    [$id, str($l, 'type', 'sales'), str($l, 'tanggal'), str($l, 'nama', 'Tanpa Nama'), json_encode($l)]);
            }
            deleteMissing($conn, 'ledger', $ids);
        });
        break;

    /* ---------- Penawaran: kirim ke customer & revisi (V1/V2/V3) ---------- */
    case 'penawaran_send':
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $pn = dbRow("SELECT * FROM penawaran WHERE id = ?", [(string)($in['id'] ?? '')]);
        if (!$pn) respond(['status' => 'error', 'message' => 'Penawaran tidak ditemukan'], 404);
        $custId = $pn['customer_id'];
        if (!$custId) {
            // Cocokkan nama customer ke akun portal
            $cands = fetchAll($conn, "SELECT id FROM customers WHERE LOWER(nama) = LOWER(?) AND email IS NOT NULL AND email <> ''", [$pn['customer']]);
            if (count($cands) === 0) respond(['status' => 'error', 'message' => 'Customer "' . $pn['customer'] . '" belum punya akun portal (nama harus sama dengan data customer).'], 422);
            if (count($cands) > 1) respond(['status' => 'error', 'message' => 'Ada lebih dari satu akun customer bernama "' . $pn['customer'] . '". Buat nama unik di menu Akun Customer sebelum mengirim penawaran.'], 422);
            $custId = $cands[0]['id'];
        }
        run($conn, "UPDATE penawaran SET customer_id = ?, status = 'Dikirim', alasan_respon = NULL, responded_at = NULL WHERE id = ?", [$custId, $pn['id']]);
        notify('customer', $custId, "Penawaran {$pn['nomor']} (V{$pn['versi']}) menunggu keputusan Anda.", 'customer-quotations.php');
        echo json_encode(['status' => 'success']);
        break;

    case 'penawaran_revise':
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $src = dbRow("SELECT * FROM penawaran WHERE id = ?", [(string)($in['id'] ?? '')]);
        if (!$src) respond(['status' => 'error', 'message' => 'Penawaran tidak ditemukan'], 404);
        $newId = newId('pn');
        transaction($conn, function () use ($conn, $src, $newId) {
            // Semua versi satu grup memakai nomor yang sama; isi dulu bila nomor induk masih kosong
            if ($src['nomor'] === '') {
                $src['nomor'] = nextDocNumber('penawaran', 'SP');
                run($conn, "UPDATE penawaran SET nomor = ? WHERE id = ?", [$src['nomor'], $src['id']]);
            }
            $root = $src['parent_id'] ?: $src['id'];
            $maxV = dbRow("SELECT MAX(versi) AS m FROM penawaran WHERE id = ? OR parent_id = ?", [$root, $root]);
            $versi = (int)($maxV['m'] ?? 1) + 1;
            run($conn, "INSERT INTO penawaran (id, nomor, tanggal, customer, kota, att, sub_judul, note, show_total, total, ppn_mode, disc, dp, status, customer_id, order_id, versi, parent_id)
                        VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?)",
                [$newId, $src['nomor'], $src['customer'], $src['kota'], $src['att'], $src['sub_judul'], $src['note'],
                 (int)$src['show_total'], (float)$src['total'], $src['ppn_mode'], (float)$src['disc'], (float)$src['dp'],
                 $src['customer_id'], $src['order_id'], $versi, $root]);
            run($conn, "INSERT INTO penawaran_items (penawaran_id, deskripsi, qty, satuan, price, amount, note_item, has_specs, specs)
                        SELECT ?, deskripsi, qty, satuan, price, amount, note_item, has_specs, specs FROM penawaran_items WHERE penawaran_id = ?",
                [$newId, $src['id']]);
            // Versi lama yang sudah dikirim/ditolak ditandai digantikan revisi
            run($conn, "UPDATE penawaran SET status = 'Revisi' WHERE id = ? AND status IN ('Dikirim', 'Ditolak')", [$src['id']]);
        });
        break;

    /* ---------- Notifikasi internal (admin) ---------- */
    case 'get_notifications':
        autoCompleteOrders();
        $rows = fetchAll($conn, "SELECT id, pesan, link, is_read, created_at FROM notifications WHERE audience = 'admin' ORDER BY id DESC LIMIT 30");
        $unread = fetchAll($conn, "SELECT COUNT(*) AS c FROM notifications WHERE audience = 'admin' AND is_read = 0")[0]['c'];
        echo json_encode(['status' => 'success', 'unread' => (int)$unread, 'items' => $rows]);
        break;

    case 'read_notifications':
        run($conn, "UPDATE notifications SET is_read = 1 WHERE audience = 'admin'");
        echo json_encode(['status' => 'success']);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
} catch (Throwable $e) {
    respond(['status' => 'error', 'message' => safeError($e, 'Terjadi kesalahan di server.')], 500);
}
?>
