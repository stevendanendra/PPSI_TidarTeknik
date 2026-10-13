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
    // Tampilkan tautan reset password di layar (khusus dev/tanpa email server). Matikan di produksi.
    'show_reset_link' => false,

    // Folder penyimpanan bukti bayar & bukti retur. Disarankan DI LUAR web root (mis. 'C:/laragon/storage/tidar').
    // Kosongkan untuk memakai folder uploads/ di dalam proyek (dilindungi .htaccess, hanya dibuka lewat download.php).
    // 'upload_dir' => __DIR__ . '/../storage/tidar-uploads',

    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'ppsi_tidarteknik',
];
