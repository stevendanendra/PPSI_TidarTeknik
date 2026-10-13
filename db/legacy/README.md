# Skrip lama (arsip)

Skrip migrasi dan percobaan sementara dari tahap awal pengembangan. **Tidak dipakai lagi** dan tidak boleh dijalankan di production:

- Skema database sekarang dibuat/dilengkapi otomatis oleh `db.php` + `schema_pmp.php`, dan lengkap di `db/ppsi_tidarteknik.sql`.
- Skrip di sini tidak punya proteksi login dan memakai path lama (`require 'db.php'`); karena dipindah ke subfolder, sebagian tidak akan berjalan tanpa penyesuaian path.
- Folder `db/` diblokir dari akses web lewat `db/.htaccess`.

Disimpan hanya sebagai catatan riwayat; boleh dihapus kapan saja (riwayat tetap ada di Git).
