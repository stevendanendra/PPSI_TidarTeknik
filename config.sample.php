<?php
/**
 * config.sample.php — contoh konfigurasi.
 * Salin menjadi config.php (sudah di-gitignore), lalu isi nilai asli.
 * Jangan commit config.php.
 *
 * Buat hash password admin dengan:
 *   php -r "echo password_hash('PASSWORD_BARU', PASSWORD_DEFAULT), PHP_EOL;"
 */
return [
    'admin_username'      => 'tidarteknik',
    'admin_password_hash' => '', // isi hash dari perintah di atas
    // Dipakai hanya jika admin_password_hash kosong (mode pengembangan lokal):
    'admin_password'      => 'ganti-password-ini',

    // Akun customer dibuat admin (PMP). Ubah ke true hanya bila ingin registrasi mandiri.
    'allow_customer_register' => false,
    // Customer wajib memverifikasi email (tautan dikirim saat admin membuat akun) sebelum bisa login.
    // Matikan hanya bila server belum bisa mengirim email (admin masih bisa 'Tandai terverifikasi').
    'require_email_verification' => true,
    // Tampilkan tautan reset password di layar (khusus dev/tanpa email server). Matikan di produksi.
    'show_reset_link' => false,

    // Folder penyimpanan bukti bayar & bukti retur. Disarankan DI LUAR web root (mis. 'C:/laragon/storage/tidar').
    // Kosongkan untuk memakai folder uploads/ di dalam proyek (dilindungi .htaccess, hanya dibuka lewat download.php).
    // 'upload_dir' => __DIR__ . '/../storage/tidar-uploads',

    // WAJIB di production: alamat publik aplikasi untuk tautan di email reset password (tanpa slash di akhir).
    // Header Host dari pengunjung tidak dipercaya. Contoh: 'https://tidarteknik.my.id'
    // 'base_url' => 'https://tidarteknik.my.id',
    // Pengiriman email (verifikasi & reset password). Kosongkan smtp_host untuk memakai mail() bawaan PHP.
    // Contoh Gmail: host smtp.gmail.com, port 587, secure 'tls', user = alamat Gmail, pass = "App Password" (bukan sandi akun).
    // 'mail_from'      => 'noreply@tidarteknik.my.id',   // harus sama/diizinkan oleh akun SMTP
    // 'mail_from_name' => 'CV. Tidar Teknik',
    // 'smtp_host'   => 'smtp.gmail.com',
    // 'smtp_port'   => 587,
    // 'smtp_secure' => 'tls',    // 'tls' (STARTTLS, 587) | 'ssl' (465) | '' (tanpa enkripsi, hanya uji lokal)
    // 'smtp_user'   => 'akun@gmail.com',
    // 'smtp_pass'   => 'app-password',
    // Tampilkan detail error teknis (hanya saat pengembangan). Default false: detail hanya ke log server.
    // 'debug' => false,

    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'ppsi_tidarteknik',
];
