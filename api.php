<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
require_once 'db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_suppliers':
        $res = mysqli_query($conn, "SELECT * FROM suppliers");
        echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
        break;

    case 'save_suppliers':
        $data = json_decode(file_get_contents('php://input'), true);
        mysqli_query($conn, "TRUNCATE TABLE suppliers");
        foreach ($data as $item) {
            $id = mysqli_real_escape_string($conn, $item['id']);
            $nama = mysqli_real_escape_string($conn, $item['nama']);
            $kode = mysqli_real_escape_string($conn, $item['kode']);
            $kota = mysqli_real_escape_string($conn, $item['kota']);
            $alamat = mysqli_real_escape_string($conn, $item['alamat']);
            mysqli_query($conn, "INSERT INTO suppliers (id, nama, kode, kota, alamat) VALUES ('$id', '$nama', '$kode', '$kota', '$alamat')");
        }
        echo json_encode(['status' => 'success']);
        break;

    case 'get_customers':
        $res = mysqli_query($conn, "SELECT * FROM customers");
        echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
        break;

    case 'save_customers':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data format']);
            exit;
        }
        $existingIds = [];
        foreach ($data as $item) {
            $id = mysqli_real_escape_string($conn, $item['id']);
            $nama = mysqli_real_escape_string($conn, $item['nama']);
            $kode = mysqli_real_escape_string($conn, $item['kode'] ?? '');
            $kota = mysqli_real_escape_string($conn, $item['kota'] ?? '');
            $alamat = mysqli_real_escape_string($conn, $item['alamat'] ?? '');
            $npwp = mysqli_real_escape_string($conn, $item['npwp'] ?? '');
            $pic = mysqli_real_escape_string($conn, $item['pic'] ?? '');
            $existingIds[] = "'$id'";
            
            $q = "INSERT INTO customers (id, nama, kode, kota, alamat, npwp, pic) 
                  VALUES ('$id', '$nama', '$kode', '$kota', '$alamat', '$npwp', '$pic')
                  ON DUPLICATE KEY UPDATE 
                    nama = VALUES(nama),
                    kode = VALUES(kode),
                    kota = VALUES(kota),
                    alamat = VALUES(alamat),
                    npwp = VALUES(npwp),
                    pic = IF(VALUES(pic) != '', VALUES(pic), pic)";
            if (!mysqli_query($conn, $q)) {
                echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
                exit;
            }
        }
        if (!empty($existingIds)) {
            $inIds = implode(',', $existingIds);
            $toDelete = mysqli_query($conn, "SELECT id FROM customers WHERE id NOT IN ($inIds)");
            if ($toDelete) {
                while ($delRow = mysqli_fetch_assoc($toDelete)) {
                    $delId = $delRow['id'];
                    $hasOrders = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM orders WHERE customer_id = '$delId' LIMIT 1"));
                    $hasReqs = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM product_requests WHERE customer_id = '$delId' LIMIT 1"));
                    if (!$hasOrders && !$hasReqs) {
                        mysqli_query($conn, "DELETE FROM customers WHERE id = '$delId'");
                    }
                }
            }
        }
        echo json_encode(['status' => 'success']);
        break;

    case 'get_po':
        $res = mysqli_query($conn, "SELECT * FROM po ORDER BY tanggal DESC, id DESC");
        $pos = mysqli_fetch_all($res, MYSQLI_ASSOC);
        foreach ($pos as &$p) {
            $p['total'] = (float)($p['total'] ?? 0);
            $p['dp'] = (float)($p['dp'] ?? 0);
            $p['disc'] = (float)($p['disc'] ?? 0);
            $p['ppnMode'] = $p['ppn_mode'] ?? 'none';
            $p['alamatKirim'] = $p['alamat_kirim'] ?? '';
            $p['catatan'] = $p['catatan'] ?? '';
            $p['termin'] = (int)($p['termin'] ?? 30);
            $po_id = $p['id'];
            $res_items = mysqli_query($conn, "SELECT *, deskripsi AS 'desc', tipe AS 'type' FROM po_items WHERE po_id = '$po_id'");
            $items = mysqli_fetch_all($res_items, MYSQLI_ASSOC);
            foreach ($items as &$it) {
                $it['qty'] = (float)($it['qty'] ?? 0);
                $it['price'] = (float)($it['price'] ?? 0);
                $it['amount'] = (float)($it['amount'] ?? 0);
                $it['hasSpecs'] = (bool)($it['has_specs'] ?? false);
                $it['specs'] = json_decode($it['specs'] ?? '{}', true);
            }
            $p['items'] = $items;
        }
        echo json_encode($pos);
        break;

    case 'save_po':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data format']);
            exit;
        }

        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
        mysqli_query($conn, "TRUNCATE TABLE po_items");
        mysqli_query($conn, "TRUNCATE TABLE po");
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
        
        foreach ($data as $po) {
            $id = mysqli_real_escape_string($conn, (string)($po['id'] ?? ''));
            $nomor = mysqli_real_escape_string($conn, (string)($po['nomor'] ?? ''));
            $tanggal = mysqli_real_escape_string($conn, (string)($po['tanggal'] ?? ''));
            $supplier = mysqli_real_escape_string($conn, (string)($po['supplier'] ?? ''));
            $kota = mysqli_real_escape_string($conn, (string)($po['kota'] ?? ''));
            $note1 = mysqli_real_escape_string($conn, (string)($po['note1'] ?? ''));
            $note2 = mysqli_real_escape_string($conn, (string)($po['note2'] ?? ''));
            $alamat_kirim = mysqli_real_escape_string($conn, (string)($po['alamatKirim'] ?? ''));
            $ppn_mode = mysqli_real_escape_string($conn, (string)($po['ppnMode'] ?? 'none'));
            $disc = (float)($po['disc'] ?? 0);
            $dp = (float)($po['dp'] ?? 0);
            $total = (float)($po['total'] ?? 0);
            $catatan = mysqli_real_escape_string($conn, (string)($po['catatan'] ?? ''));
            $termin = (int)($po['termin'] ?? 30);
            $status = mysqli_real_escape_string($conn, (string)($po['status'] ?? 'Belum Bayar'));
            
            // Fallback date if empty to avoid MySQL error
            $finalTanggal = !empty($tanggal) ? "'$tanggal'" : "CURDATE()";
            
            $q = "INSERT INTO po (id, nomor, tanggal, supplier, kota, note1, note2, alamat_kirim, ppn_mode, disc, dp, total, status, catatan, termin) 
                  VALUES ('$id', '$nomor', $finalTanggal, '$supplier', '$kota', '$note1', '$note2', '$alamat_kirim', '$ppn_mode', $disc, $dp, $total, '$status', '$catatan', $termin)";
            
            if (!mysqli_query($conn, $q)) {
                echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
                exit;
            }
            $po_id = $id;
            
            if (!empty($po['items'])) {
                foreach ($po['items'] as $item) {
                    $desc = mysqli_real_escape_string($conn, (string)($item['desc'] ?? ''));
                    $tipe = mysqli_real_escape_string($conn, (string)($item['type'] ?? $item['tipe'] ?? ''));
                    $qty = (float)($item['qty'] ?? 0);
                    $price = (float)($item['price'] ?? 0);
                    $amount = (float)($item['amount'] ?? 0);
                    $has_specs = ($item['hasSpecs'] ?? false) ? 1 : 0;
                    $specs = mysqli_real_escape_string($conn, json_encode($item['specs'] ?? []));
                    $iq = "INSERT INTO po_items (po_id, deskripsi, tipe, qty, price, amount, has_specs, specs) 
                           VALUES ('$po_id', '$desc', '$tipe', $qty, $price, $amount, $has_specs, '$specs')";
                    if (!mysqli_query($conn, $iq)) {
                        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
                        exit;
                    }
                }
            }
        }
        echo json_encode(['status' => 'success']);
        break;

    case 'get_nota':
        $res = mysqli_query($conn, "SELECT * FROM nota ORDER BY tanggal DESC, id DESC");
        $notas = mysqli_fetch_all($res, MYSQLI_ASSOC);
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
            $nid = $n['id'];
            $res_items = mysqli_query($conn, "SELECT *, deskripsi AS 'desc' FROM nota_items WHERE nota_id = '$nid'");
            $items = mysqli_fetch_all($res_items, MYSQLI_ASSOC);
            foreach ($items as &$it) {
                $it['qty'] = (float)($it['qty'] ?? 0);
                $it['price'] = (float)($it['price'] ?? 0);
                $it['amount'] = (float)($it['amount'] ?? 0);
                $it['jasaRakit'] = (float)($it['jasa_rakit'] ?? 0);
                $it['hasSpecs'] = (bool)($it['has_specs'] ?? false);
                $it['specs'] = json_decode($it['specs'] ?? '{}', true);
            }
            $n['items'] = $items;
        }
        echo json_encode($notas);
        break;

    case 'save_nota':
        $data = json_decode(file_get_contents('php://input'), true);
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
        mysqli_query($conn, "TRUNCATE TABLE nota_items");
        mysqli_query($conn, "TRUNCATE TABLE nota");
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

        foreach ($data as $nota) {
            $id = mysqli_real_escape_string($conn, (string)($nota['id'] ?? ''));
            $nomor = mysqli_real_escape_string($conn, (string)($nota['nomor'] ?? ''));
            $tanggal = mysqli_real_escape_string($conn, (string)($nota['tanggal'] ?? ''));
            $customer = mysqli_real_escape_string($conn, (string)($nota['customer'] ?? ''));
            $alamat_invoice = mysqli_real_escape_string($conn, (string)($nota['alamatInvoice'] ?? ''));
            $alamat_sj = mysqli_real_escape_string($conn, (string)($nota['alamatSJ'] ?? ''));
            $ref_po = mysqli_real_escape_string($conn, (string)($nota['refPO'] ?? ''));
            $ekspedisi = mysqli_real_escape_string($conn, (string)($nota['ekspedisi'] ?? ''));
            $tujuan_kirim = mysqli_real_escape_string($conn, (string)($nota['tujuanKirim'] ?? ''));
            $tanggal_kirim = mysqli_real_escape_string($conn, (string)($nota['tanggalKirim'] ?? ''));
            $seri_faktur = mysqli_real_escape_string($conn, (string)($nota['seriFaktur'] ?? ''));
            $pembayaran = (float)($nota['pembayaran'] ?? 0);
            $tanggal_pembayaran = mysqli_real_escape_string($conn, (string)($nota['tanggalPembayaran'] ?? ''));
            $ppn_mode = mysqli_real_escape_string($conn, (string)($nota['ppnMode'] ?? 'none'));
            $disc = (float)($nota['disc'] ?? 0);
            $dp = (float)($nota['dp'] ?? 0);
            $total = (float)($nota['total'] ?? 0);
            $catatan = mysqli_real_escape_string($conn, (string)($nota['catatan'] ?? ''));
            $termin = (int)($nota['termin'] ?? 30);
            $status = mysqli_real_escape_string($conn, (string)($nota['status'] ?? 'Belum Bayar'));

            $qTanggalKirim = !empty($tanggal_kirim) ? "'$tanggal_kirim'" : "NULL";
            $qTanggalBayar = !empty($tanggal_pembayaran) ? "'$tanggal_pembayaran'" : "NULL";

            if (!mysqli_query($conn, "INSERT INTO nota (id, nomor, tanggal, customer, alamat_invoice, alamat_sj, ref_po, ekspedisi, tujuan_kirim, tanggal_kirim, seri_faktur, pembayaran, tanggal_pembayaran, ppn_mode, disc, dp, total, status, catatan, termin) 
                                VALUES ('$id', '$nomor', '$tanggal', '$customer', '$alamat_invoice', '$alamat_sj', '$ref_po', '$ekspedisi', '$tujuan_kirim', $qTanggalKirim, '$seri_faktur', $pembayaran, $qTanggalBayar, '$ppn_mode', $disc, $dp, $total, '$status', '$catatan', $termin)")) {
                echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
                exit;
            }
            $nota_id = $id;

            if (!empty($nota['items'])) {
                foreach ($nota['items'] as $item) {
                    $qty = (float)($item['qty'] ?? 0);
                    $satuan = mysqli_real_escape_string($conn, (string)($item['satuan'] ?? ''));
                    $code = mysqli_real_escape_string($conn, (string)($item['code'] ?? ''));
                    $desc = mysqli_real_escape_string($conn, (string)($item['desc'] ?? ''));
                    $jasa_rakit = (float)($item['jasaRakit'] ?? 0);
                    $price = (float)($item['price'] ?? 0);
                    $amount = (float)($item['amount'] ?? 0);
                    $has_specs = ($item['hasSpecs'] ?? false) ? 1 : 0;
                    $specs = mysqli_real_escape_string($conn, json_encode($item['specs'] ?? []));
                    
                    if (!mysqli_query($conn, "INSERT INTO nota_items (nota_id, qty, satuan, code, deskripsi, jasa_rakit, price, amount, has_specs, specs) 
                                        VALUES ('$nota_id', $qty, '$satuan', '$code', '$desc', $jasa_rakit, $price, $amount, $has_specs, '$specs')")) {
                        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
                        exit;
                    }
                }
            }
        }
        echo json_encode(['status' => 'success']);
        break;

    case 'get_penawaran':
        $res = mysqli_query($conn, "SELECT * FROM penawaran ORDER BY tanggal DESC, id DESC");
        $penawarans = mysqli_fetch_all($res, MYSQLI_ASSOC);
        foreach ($penawarans as &$p) {
            $p['total'] = (float)($p['total'] ?? 0);
            $p['dp'] = (float)($p['dp'] ?? 0);
            $p['disc'] = (float)($p['disc'] ?? 0);
            $p['ppnMode'] = $p['ppn_mode'] ?? 'none';
            $pid = $p['id'];
            $res_items = mysqli_query($conn, "SELECT *, deskripsi AS 'desc' FROM penawaran_items WHERE penawaran_id = '$pid'");
            $items = mysqli_fetch_all($res_items, MYSQLI_ASSOC);
            foreach ($items as &$it) {
                $it['qty'] = (float)($it['qty'] ?? 0);
                $it['price'] = (float)($it['price'] ?? 0);
                $it['amount'] = (float)($it['amount'] ?? 0);
                $it['hasSpecs'] = (bool)($it['has_specs'] ?? false);
                $it['specs'] = json_decode($it['specs'] ?? '{}', true);
            }
            $p['items'] = $items;
        }
        echo json_encode($penawarans);
        break;

    case 'save_penawaran':
        $data = json_decode(file_get_contents('php://input'), true);
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
        mysqli_query($conn, "TRUNCATE TABLE penawaran_items");
        mysqli_query($conn, "TRUNCATE TABLE penawaran");
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

        foreach ($data as $pn) {
            $id = mysqli_real_escape_string($conn, (string)($pn['id'] ?? ''));
            $nomor = mysqli_real_escape_string($conn, (string)($pn['nomor'] ?? ''));
            $tanggal = mysqli_real_escape_string($conn, (string)($pn['tanggal'] ?? ''));
            $customer = mysqli_real_escape_string($conn, (string)($pn['customer'] ?? ''));
            $kota = mysqli_real_escape_string($conn, (string)($pn['kota'] ?? ''));
            $att = mysqli_real_escape_string($conn, (string)($pn['att'] ?? ''));
            $sub_judul = mysqli_real_escape_string($conn, (string)($pn['sub_judul'] ?? ''));
            $note = mysqli_real_escape_string($conn, (string)($pn['note'] ?? ''));
            $show_total = isset($pn['show_total']) ? (int)$pn['show_total'] : 1;
            $total = (float)($pn['total'] ?? 0);
            $ppn_mode = mysqli_real_escape_string($conn, (string)($pn['ppnMode'] ?? 'none'));
            $disc = (float)($pn['disc'] ?? 0);
            $dp = (float)($pn['dp'] ?? 0);

            mysqli_query($conn, "INSERT INTO penawaran (id, nomor, tanggal, customer, kota, att, sub_judul, note, show_total, total, ppn_mode, disc, dp) 
                                VALUES ('$id', '$nomor', '$tanggal', '$customer', '$kota', '$att', '$sub_judul', '$note', $show_total, $total, '$ppn_mode', $disc, $dp)");
            $pn_id = $id;

            if (!empty($pn['items'])) {
                foreach ($pn['items'] as $item) {
                    $desc = mysqli_real_escape_string($conn, (string)($item['desc'] ?? ''));
                    $qty = (float)($item['qty'] ?? 0);
                    $satuan = mysqli_real_escape_string($conn, (string)($item['satuan'] ?? ''));
                    $price = (float)($item['price'] ?? 0);
                    $amount = (float)($item['amount'] ?? 0);
                    $note_item = mysqli_real_escape_string($conn, (string)($item['note_item'] ?? ''));
                    $has_specs = ($item['hasSpecs'] ?? false) ? 1 : 0;
                    $specs = mysqli_real_escape_string($conn, json_encode($item['specs'] ?? []));
                    
                    mysqli_query($conn, "INSERT INTO penawaran_items (penawaran_id, deskripsi, qty, satuan, price, amount, note_item, has_specs, specs) 
                                        VALUES ('$pn_id', '$desc', $qty, '$satuan', $price, $amount, '$note_item', $has_specs, '$specs')");
                }
            }
        }
        echo json_encode(['status' => 'success']);
        break;

    case 'get_ledger':
        $res = mysqli_query($conn, "SELECT * FROM ledger ORDER BY tanggal DESC, created_at DESC");
        if (!$res) {
            echo json_encode([]);
            break;
        }
        $ledger = mysqli_fetch_all($res, MYSQLI_ASSOC);
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
        echo json_encode($ledger);
        break;

    case 'save_ledger':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data format']);
            exit;
        }
        mysqli_query($conn, "TRUNCATE TABLE ledger");
        foreach ($data as $l) {
            $id = mysqli_real_escape_string($conn, $l['id']);
            $type = mysqli_real_escape_string($conn, $l['type'] ?? 'sales');
            $tanggal = mysqli_real_escape_string($conn, $l['tanggal'] ?? '');
            $nama = mysqli_real_escape_string($conn, $l['nama'] ?? 'Tanpa Nama');
            $raw_data = mysqli_real_escape_string($conn, json_encode($l));

            $qTang = !empty($tanggal) ? "'$tanggal'" : "NULL";

            $q = "INSERT INTO ledger (id, type, tanggal, nama, data) VALUES ('$id', '$type', $qTang, '$nama', '$raw_data')";
            if (!mysqli_query($conn, $q)) {
                echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
                exit;
            }
        }
        echo json_encode(['status' => 'success']);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
