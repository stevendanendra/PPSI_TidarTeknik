<?php
/**
 * customer-profile.php — Profil Customer & Ubah Password
 * Sesuai Sitemap 3.5: Data Perusahaan / Pribadi, Alamat, Kontak, Ubah Password
 */

$pageTitle = 'Profil & Akun Saya';
$pageSubtitle = 'Kelola data perusahaan, kontak pengiriman, dan keamanan akun Anda';
require_once 'customer-header.php';

$cId = $currentCust['id'];
$successProfile = '';
$errorProfile   = '';
$successPass    = '';
$errorPass      = '';

// 1. Handle Update Profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_profile'])) {
    $nama    = trim($_POST['nama'] ?? '');
    $pic     = trim($_POST['pic'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $kota    = trim($_POST['kota'] ?? '');
    $alamat  = trim($_POST['alamat'] ?? '');
    $npwp    = trim($_POST['npwp'] ?? '');

    if (empty($nama) || empty($telepon) || empty($kota)) {
        $errorProfile = 'Nama, nomor telepon, dan kota wajib diisi.';
    } else {
        $cleanNama = mysqli_real_escape_string($conn, $nama);
        $cleanPic  = mysqli_real_escape_string($conn, $pic);
        $cleanTelp = mysqli_real_escape_string($conn, $telepon);
        $cleanKota = mysqli_real_escape_string($conn, strtoupper($kota));
        $cleanAlm  = mysqli_real_escape_string($conn, $alamat);
        $cleanNpwp = mysqli_real_escape_string($conn, $npwp);

        $sql = "UPDATE customers SET 
                    nama = '$cleanNama',
                    pic = '$cleanPic',
                    telepon = '$cleanTelp',
                    kota = '$cleanKota',
                    alamat = '$cleanAlm',
                    npwp = '$cleanNpwp'
                WHERE id = '$cId'";

        if (mysqli_query($conn, $sql)) {
            $successProfile = 'Data profil berhasil diperbarui.';
            $_SESSION[CUSTOMER_NAME_KEY] = $cleanNama;
            $currentCust = getCurrentCustomer(); // refresh
        } else {
            $errorProfile = 'Gagal memperbarui profil: ' . mysqli_error($conn);
        }
    }
}

// 2. Handle Ubah Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_password'])) {
    $passLama = $_POST['pass_lama'] ?? '';
    $passBaru = $_POST['pass_baru'] ?? '';
    $passConf = $_POST['pass_conf'] ?? '';

    // Ambil password saat ini dari DB
    $resP = mysqli_query($conn, "SELECT password FROM customers WHERE id = '$cId' LIMIT 1");
    $rowP = mysqli_fetch_assoc($resP);

    if (empty($passLama) || empty($passBaru) || empty($passConf)) {
        $errorPass = 'Harap isi semua kolom password.';
    } elseif (!empty($rowP['password']) && !password_verify($passLama, $rowP['password'])) {
        $errorPass = 'Password saat ini salah.';
    } elseif (strlen($passBaru) < 6) {
        $errorPass = 'Password baru minimal harus 6 karakter.';
    } elseif ($passBaru !== $passConf) {
        $errorPass = 'Konfirmasi password baru tidak cocok.';
    } else {
        $hashed = password_hash($passBaru, PASSWORD_BCRYPT);
        if (mysqli_query($conn, "UPDATE customers SET password = '$hashed', must_change_password = 0 WHERE id = '$cId'")) {
            $successPass = 'Password akun berhasil diubah.';
            if (isset($_GET['force'])) {
                header('Location: customer-dashboard.php');
                exit;
            }
        } else {
            $errorPass = 'Gagal mengubah password: ' . mysqli_error($conn);
        }
    }
}
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
  
  <?php if (isset($_GET['force']) || !empty($currentCust['must_change_password'])): ?>
    <div class="alert alert-error" style="margin-bottom:16px"><span>🔐</span> Demi keamanan, ganti password sementara Anda terlebih dahulu sebelum memakai portal.</div>
  <?php endif; ?>

  <!-- FORM DATA PERUSAHAAN & KONTAK -->
  <div class="card">
    <div class="card-header">
      <span style="font-size: 18px;">🏢</span>
      <div class="card-title">Data Perusahaan &amp; Kontak</div>
    </div>
    <div class="card-body" style="padding: 24px 20px;">
      
      <?php if ($successProfile): ?>
        <div class="alert alert-success" style="margin-bottom: 16px;">
          <span>✅</span> <?= $successProfile ?>
        </div>
      <?php endif; ?>

      <?php if ($errorProfile): ?>
        <div class="alert alert-error" style="margin-bottom: 16px;">
          <span>⚠️</span> <?= $errorProfile ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="customer-profile.php" autocomplete="off">
        <input type="hidden" name="action_profile" value="1">

        <div class="form-group">
          <label class="form-label required">Nama Perusahaan / Pribadi</label>
          <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($currentCust['nama']) ?>" required>
        </div>

        <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">PIC / Contact Person</label>
            <input type="text" name="pic" class="form-control" value="<?= htmlspecialchars($currentCust['pic'] ?? '') ?>" placeholder="Bpk. Hendra">
          </div>
          <div class="form-group">
            <label class="form-label required">No. Telepon / WhatsApp</label>
            <input type="text" name="telepon" class="form-control" value="<?= htmlspecialchars($currentCust['telepon'] ?? '') ?>" placeholder="081234567890" required>
          </div>
        </div>

        <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label class="form-label">Email Terdaftar (Akun)</label>
            <input type="email" class="form-control" value="<?= htmlspecialchars($currentCust['email'] ?? '-') ?>" readonly style="background: var(--navy-50);">
          </div>
          <div class="form-group">
            <label class="form-label required">Kota</label>
            <input type="text" name="kota" class="form-control" value="<?= htmlspecialchars($currentCust['kota'] ?? '') ?>" placeholder="SURABAYA" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Nomor Pokok Wajib Pajak (NPWP)</label>
          <input type="text" name="npwp" class="form-control" value="<?= htmlspecialchars($currentCust['npwp'] ?? '') ?>" placeholder="00.000.000.0-000.000">
        </div>

        <div class="form-group">
          <label class="form-label">Alamat Lengkap Pengiriman</label>
          <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($currentCust['alamat'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 6px;">
          💾 Simpan Perubahan Profil
        </button>
      </form>
    </div>
  </div>

  <!-- FORM UBAH PASSWORD -->
  <div class="card">
    <div class="card-header">
      <span style="font-size: 18px;">🔐</span>
      <div class="card-title">Keamanan &amp; Ubah Password</div>
    </div>
    <div class="card-body" style="padding: 24px 20px;">
      
      <?php if ($successPass): ?>
        <div class="alert alert-success" style="margin-bottom: 16px;">
          <span>✅</span> <?= $successPass ?>
        </div>
      <?php endif; ?>

      <?php if ($errorPass): ?>
        <div class="alert alert-error" style="margin-bottom: 16px;">
          <span>⚠️</span> <?= $errorPass ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="customer-profile.php<?= isset($_GET['force']) ? '?force=1' : '' ?>" autocomplete="off">
        <input type="hidden" name="action_password" value="1">

        <div class="form-group">
          <label class="form-label required">Password Saat Ini</label>
          <input type="password" name="pass_lama" class="form-control" placeholder="••••••••" required>
        </div>

        <div class="form-group">
          <label class="form-label required">Password Baru (Min. 6 Karakter)</label>
          <input type="password" name="pass_baru" class="form-control" placeholder="••••••••" required>
        </div>

        <div class="form-group">
          <label class="form-label required">Konfirmasi Password Baru</label>
          <input type="password" name="pass_conf" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-warning" style="margin-top: 6px;">
          🔑 Perbarui Password
        </button>
      </form>

      <div style="margin-top: 24px; padding: 14px; background: var(--navy-50); border: 1px solid var(--navy-200); border-radius: var(--radius-sm); font-size: 12px; color: var(--navy-500);">
        🔒 Password Anda dienkripsi secara aman dengan algoritma BCRYPT. Pastikan untuk tidak membagikan kredensial akun kepada pihak yang tidak berkepentingan.
      </div>
    </div>
  </div>

</div>

<?php require_once 'customer-footer.php'; ?>
