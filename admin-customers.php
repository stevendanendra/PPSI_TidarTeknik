<?php
/**
 * admin-customers.php — Admin membuat & mengelola akun Customer Portal
 * (sesuai PMP: akun customer dibuat oleh Admin, bukan registrasi mandiri).
 */
require_once 'admin-layout.php';

const DEFAULT_CUSTOMER_PASSWORD = 'tidar123';
$flash = '';
$flashType = 'success';
$edit = null;
$devVerifyLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'save') {
        $id = trim($_POST['id'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');
        $mustChange = !empty($_POST['must_change']) ? 1 : 0;
        $fields = [trim($_POST['kode'] ?? ''), strtoupper(trim($_POST['kota'] ?? '')), trim($_POST['alamat'] ?? ''),
                   trim($_POST['telepon'] ?? ''), trim($_POST['pic'] ?? ''), trim($_POST['npwp'] ?? '')];
        $dupe = $email !== '' ? dbRow("SELECT id FROM customers WHERE email = ? AND id <> ?", [$email, $id]) : null;
        $dupeName = $nama !== '' ? dbRow("SELECT id FROM customers WHERE LOWER(nama) = LOWER(?) AND id <> ? AND email IS NOT NULL AND email <> ''", [$nama, $id]) : null;

        if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash = 'Nama dan email yang valid wajib diisi.'; $flashType = 'error';
        } elseif ($dupe) {
            $flash = 'Email sudah dipakai akun lain.'; $flashType = 'error';
        } elseif ($dupeName) {
            $flash = 'Sudah ada akun dengan nama perusahaan yang sama. Gunakan nama yang unik (mis. tambahkan kota/cabang).'; $flashType = 'error';
        } elseif ($id === '' && strlen($pass) < 6) {
            $flash = 'Password awal minimal 6 karakter.'; $flashType = 'error';
        } elseif ($pass !== '' && strlen($pass) < 6) {
            $flash = 'Password minimal 6 karakter.'; $flashType = 'error';
        } else {
            [$kode, $kota, $alamat, $telp, $pic, $npwp] = $fields;
            if ($id === '') {
                $id = newId('cus');
                if ($kode === '') $kode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $nama), 0, 3)) ?: 'CUS';
                dbRun("INSERT INTO customers (id, nama, kode, kota, alamat, npwp, email, password, telepon, pic, status, must_change_password)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)",
                    [$id, $nama, $kode, $kota, $alamat, $npwp, $email, password_hash($pass, PASSWORD_BCRYPT), $telp, $pic, $mustChange]);
                $ver = issueEmailVerification($id, $email, $nama);
                if (appConfig('show_reset_link', false) && isLocalRequest()) $devVerifyLink = $ver['link'];
                $flash = "Akun $email dibuat. Berikan email & password awal ke customer. " .
                         ($ver['sent'] ? 'Email verifikasi sudah dikirim.' : 'Email verifikasi belum terkirim (cek pengaturan email/base_url) — gunakan "Kirim ulang" atau "Tandai terverifikasi".');
            } else {
                $oldEmail = (string)(dbRow("SELECT email FROM customers WHERE id = ?", [$id])['email'] ?? '');
                dbRun("UPDATE customers SET nama = ?, kode = ?, kota = ?, alamat = ?, npwp = ?, email = ?, telepon = ?, pic = ? WHERE id = ?",
                    [$nama, $kode, $kota, $alamat, $npwp, $email, $telp, $pic, $id]);
                if (strtolower($oldEmail) !== $email) { // email diganti -> wajib verifikasi ulang
                    $ver = issueEmailVerification($id, $email, $nama);
                    if (appConfig('show_reset_link', false) && isLocalRequest()) $devVerifyLink = $ver['link'];
                }
                if ($pass !== '') {
                    dbRun("UPDATE customers SET password = ?, must_change_password = ? WHERE id = ?", [password_hash($pass, PASSWORD_BCRYPT), $mustChange, $id]);
                }
                $flash = 'Akun diperbarui' . ($pass !== '' ? ' (password direset).' : '.');
            }
        }
        if ($flashType === 'error') {
            $edit = ['id' => $_POST['id'] ?? '', 'nama' => $nama, 'email' => $email, 'kode' => $fields[0], 'kota' => $fields[1],
                     'alamat' => $fields[2], 'telepon' => $fields[3], 'pic' => $fields[4], 'npwp' => $fields[5]];
        }
    } elseif ($act === 'resend_verify') {
        $c = dbRow("SELECT id, nama, email FROM customers WHERE id = ? AND email IS NOT NULL AND email <> '' AND email_verified_at IS NULL", [trim($_POST['id'] ?? '')]);
        if ($c) {
            $ver = issueEmailVerification($c['id'], $c['email'], $c['nama']);
            if (appConfig('show_reset_link', false) && isLocalRequest()) $devVerifyLink = $ver['link'];
            $flash = $ver['sent'] ? "Email verifikasi dikirim ulang ke {$c['email']}." : 'Email verifikasi belum terkirim (cek pengaturan email/base_url).';
            if (!$ver['sent']) $flashType = 'error';
        }
    } elseif ($act === 'mark_verified') {
        dbRun("UPDATE customers SET email_verified_at = NOW(), verify_token_hash = NULL, verify_expires = NULL WHERE id = ? AND email IS NOT NULL", [trim($_POST['id'] ?? '')]);
        $flash = 'Email ditandai terverifikasi secara manual.';
    } elseif ($act === 'toggle') {
        dbRun("UPDATE customers SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ? AND email IS NOT NULL", [trim($_POST['id'] ?? '')]);
        $flash = 'Status akun diubah.';
    }
}

if (!$edit && isset($_GET['edit'])) {
    $edit = dbRow("SELECT * FROM customers WHERE id = ?", [trim($_GET['edit'])]);
}

$accounts = dbRows("SELECT id, nama, email, kota, telepon, status, must_change_password, email_verified_at FROM customers WHERE email IS NOT NULL AND email <> '' ORDER BY nama");
$noAccount = (int)dbRow("SELECT COUNT(*) AS c FROM customers WHERE email IS NULL OR email = ''")['c'];

adminHead('Akun Customer', 'Admin membuat akun & password awal untuk customer portal', 'admin-customers.php');
$v = fn($k, $d = '') => h($edit[$k] ?? $d);
?>
<?php if ($flash): ?><div class="alert alert-<?= $flashType ?>" style="margin-bottom:16px"><span><?= $flashType === 'success' ? '✅' : '⚠️' ?></span> <?= h($flash) ?>
  <?php if ($devVerifyLink): ?><br><a href="<?= h($devVerifyLink) ?>" style="word-break:break-all">Tautan verifikasi (mode dev)</a><?php endif; ?></div><?php endif; ?>

<div class="order-detail-grid" style="grid-template-columns:minmax(0,1.5fr) minmax(0,1fr)">
  <div class="card">
    <div class="card-header"><div class="card-title">Akun Customer Portal (<?= count($accounts) ?>)</div></div>
    <div class="table-wrap" style="border-radius:0;border:none;border-top:1px solid var(--navy-200);box-shadow:none">
      <table>
        <thead><tr><th>Customer</th><th>Email</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($accounts as $a): ?>
          <tr>
            <td><strong><?= h($a['nama']) ?></strong><div style="font-size:11px;color:var(--navy-400)"><?= h($a['kota']) ?> • <?= h($a['telepon']) ?></div></td>
            <td><?= h($a['email']) ?><?= $a['must_change_password'] ? '<div style="font-size:10.5px;color:var(--am-600)">wajib ganti password</div>' : '' ?>
              <?= $a['email_verified_at'] ? '<div style="font-size:10.5px;color:var(--em-600)">✔ email terverifikasi</div>' : '<div style="font-size:10.5px;color:var(--am-600)">⚠ email belum terverifikasi</div>' ?></td>
            <td><span class="badge <?= $a['status'] === 'active' ? 'badge-green' : 'badge-red' ?>"><?= $a['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td>
            <td style="display:flex;gap:4px">
              <a class="btn btn-sm btn-warning" href="admin-customers.php?edit=<?= urlencode($a['id']) ?>">✏️ Edit / Reset</a>
              <?php if (!$a['email_verified_at']): ?>
                <form method="POST" style="display:inline"><input type="hidden" name="act" value="resend_verify"><input type="hidden" name="id" value="<?= h($a['id']) ?>"><button class="btn btn-sm btn-ghost">📧 Kirim ulang</button></form>
                <form method="POST" style="display:inline"><input type="hidden" name="act" value="mark_verified"><input type="hidden" name="id" value="<?= h($a['id']) ?>"><button class="btn btn-sm btn-ghost" onclick="return confirm('Tandai email ini terverifikasi tanpa tautan?')">✔ Tandai terverifikasi</button></form>
              <?php endif; ?>
              <form method="POST" style="display:inline"><input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="<?= h($a['id']) ?>">
                <button class="btn btn-sm btn-ghost" onclick="return confirm('Ubah status akun ini?')"><?= $a['status'] === 'active' ? '⏸ Nonaktifkan' : '▶ Aktifkan' ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$accounts): ?><tr><td colspan="4"><div class="empty-state"><p>Belum ada akun customer.</p></div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($noAccount): ?><div style="padding:12px 20px;font-size:12px;color:var(--navy-500)"><?= $noAccount ?> customer di Master belum punya akun portal.</div><?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title"><?= !empty($edit['id']) ? 'Edit Akun' : 'Buat Akun Baru' ?></div></div>
    <form method="POST" style="padding:16px 20px">
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="id" value="<?= $v('id') ?>">
      <div class="form-group"><label class="form-label required">Nama Perusahaan / Customer</label>
        <input type="text" name="nama" class="form-control" value="<?= $v('nama') ?>" required></div>
      <div class="form-group"><label class="form-label required">Email (untuk login)</label>
        <input type="email" name="email" class="form-control" value="<?= $v('email') ?>" required></div>
      <div class="form-group"><label class="form-label <?= empty($edit['id']) ? 'required' : '' ?>"><?= !empty($edit['id']) ? 'Reset Password (kosongkan bila tidak diubah)' : 'Password Awal' ?></label>
        <input type="text" name="password" class="form-control" value="<?= empty($edit['id']) ? h(DEFAULT_CUSTOMER_PASSWORD) : '' ?>" autocomplete="off" <?= empty($edit['id']) ? 'required' : '' ?>></div>
      <label style="display:flex;gap:8px;align-items:center;font-size:12.5px;margin-bottom:12px">
        <input type="checkbox" name="must_change" value="1" checked> Wajib ganti password saat login pertama (disarankan)</label>
      <div class="form-grid" style="grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">Kode</label><input type="text" name="kode" class="form-control" value="<?= $v('kode') ?>"></div>
        <div class="form-group"><label class="form-label">Kota</label><input type="text" name="kota" class="form-control" value="<?= $v('kota') ?>"></div>
        <div class="form-group"><label class="form-label">Telepon / WA</label><input type="text" name="telepon" class="form-control" value="<?= $v('telepon') ?>"></div>
        <div class="form-group"><label class="form-label">PIC</label><input type="text" name="pic" class="form-control" value="<?= $v('pic') ?>"></div>
      </div>
      <div class="form-group"><label class="form-label">NPWP</label><input type="text" name="npwp" class="form-control" value="<?= $v('npwp') ?>"></div>
      <div class="form-group"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2"><?= $v('alamat') ?></textarea></div>
      <div style="display:flex;gap:8px;justify-content:flex-end">
        <?php if (!empty($edit['id'])): ?><a href="admin-customers.php" class="btn btn-ghost">Batal</a><?php endif; ?>
        <button class="btn btn-primary">💾 <?= !empty($edit['id']) ? 'Simpan' : 'Buat Akun' ?></button>
      </div>
    </form>
  </div>
</div>
<?php adminFoot(); ?>
