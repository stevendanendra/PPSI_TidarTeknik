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

    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'ppsi_tidarteknik',
];
