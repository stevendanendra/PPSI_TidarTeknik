# 🛠️ Sistem Informasi Manajemen CV. Tidar Teknik (SIM-Dropship & Customer Portal)

Sistem Informasi Manajemen Terintegrasi untuk **CV. Tidar Teknik** (Supplier Teknik & Industri). Aplikasi ini mengelola rantai transaksi dropship mulai dari penawaran, purchase order (PO), nota/invoice, pembukuan laba-rugi, hingga modul interaktif **Customer Portal** untuk kemudahan pemesanan mandiri oleh pelanggan.

---

## 🌟 Fitur Utama

### 1. Panel Admin & Manajemen Dropship
- **Dashboard Ringkasan Eksekutif**: Metrik omzet, laba kotor, pesanan berjalan, dan grafik tren.
- **Manajemen Penawaran Harga**: Pembuatan dan cetak PDF/kertas surat penawaran resmi.
- **Purchase Order (PO)**: Penerbitan PO ke supplier dengan spesifikasi teknis dan pelacakan status.
- **Nota & Invoice**: Pencatatan faktur penjualan, pelunasan bertahap (DP / Sisa Tagihan), serta cetak nota rapi.
- **Master Data**: Kelola data Supplier, Customer, Produk, dan Katalog Barang.
- **Manajemen Pesanan & Permintaan Customer**: Validasi pesanan masuk dan penanganan request custom produk dari pelanggan.

### 2. Modul Customer Portal
- **Katalog Produk Interaktif**: Pencarian dan filter suku cadang industri dengan spesifikasi lengkap.
- **Keranjang & Checkout**: Satu order bisa berisi beberapa produk; admin memecahnya menjadi beberapa PO per supplier.
- **Penawaran**: Terima penawaran versi V1/V2/V3, lalu setujui, tolak, atau minta revisi.
- **Pembayaran**: Transfer manual BCA dengan upload bukti (DP / Term / Partial / Pelunasan), diverifikasi admin.
- **Tracking & Penerimaan**: Pantau status, konfirmasi barang diterima (otomatis selesai setelah 7 hari), batalkan sebelum PO dibuat.
- **Retur**: Ajukan retur dengan foto/video; admin menyetujui penggantian atau refund.
- **Request Produk Custom**: Form pengajuan kebutuhan teknis khusus atau suku cadang langka.
- **Notifikasi, Help Center, lupa password**.

---

## 💻 Kebutuhan Sistem & Teknologi

- **Bahasa Pemrograman**: PHP (versi 7.4 - 8.x)
- **Database**: MySQL / MariaDB
- **Web Server**: Apache (Laragon / XAMPP)
- **Frontend**: HTML5, Vanilla CSS, JavaScript, FontAwesome

---

## 🚀 Panduan Instalasi Lokal

1. **Clone Repository**:
   ```bash
   git clone https://github.com/<username>/PPSI_TidarTeknik.git
   ```
2. **Pindahkan ke Web Directory**:
   Pindahkan folder ke direktori web server (misalnya `C:/laragon/www/PPSI_TidarTeknik` atau `htdocs/PPSI_TidarTeknik`).

3. **Setup Database**:
   - Buka **phpMyAdmin** atau HeidiSQL.
   - Buat database baru bernama `ppsi_tidarteknik`.
   - Import file [db/ppsi_tidarteknik.sql](db/ppsi_tidarteknik.sql) (kompatibel MySQL 8 dan MariaDB/XAMPP).
   - Tabel dan kolom tambahan untuk fitur PMP dibuat otomatis saat halaman pertama dibuka.

4. **Konfigurasi (kredensial tidak ikut Git)**:
   Salin [config.sample.php](config.sample.php) menjadi `config.php`, lalu isi:
   ```php
   'admin_username'      => 'tidarteknik',
   'admin_password'      => 'password-pilihan-anda',   // atau isi admin_password_hash (disarankan di production)
   'db_host' => 'localhost', 'db_user' => 'root', 'db_pass' => '', 'db_name' => 'ppsi_tidarteknik',
   ```
   `config.php` sudah di-gitignore. Buat hash password dengan
   `php -r "echo password_hash('PASSWORD_BARU', PASSWORD_DEFAULT);"`.

5. **Jalankan Aplikasi**:
   Akses melalui browser di:
   - Panel Admin / Sistem: `http://localhost/PPSI_TidarTeknik/login.php`
   - Customer Portal: `http://localhost/PPSI_TidarTeknik/customer-login.php`

   Akun customer dibuat oleh Admin lewat menu **Akun Customer** (registrasi mandiri dimatikan; bisa dinyalakan di `config.php` dengan `allow_customer_register`).

6. **Folder `uploads/`**: bukti pembayaran dan bukti retur disimpan di sini. Pastikan folder bisa ditulis oleh web server. File skrip di folder ini diblokir lewat `.htaccess` (Apache).

---

## 🔒 Catatan Keamanan

- **Login**: admin dan customer dibatasi 5 percobaan gagal per 15 menit; ID sesi diganti setelah login; cookie sesi `HttpOnly` + `SameSite=Lax`.
- **CSRF**: semua request POST wajib membawa token (disisipkan otomatis ke form dan ke `fetch`).
- **Query**: seluruh akses database memakai prepared statement.
- **XSS**: data pengguna di-escape di PHP (`h()`) dan di JavaScript (`esc()`).
- **File upload**: divalidasi dari isi file (bukan ekstensi), disimpan dengan nama acak, dan hanya bisa dibuka lewat `download.php` oleh admin atau pemilik pesanan. Untuk server Nginx, letakkan folder upload di luar web root lewat `upload_dir` di `config.php`, karena `.htaccess` hanya berlaku di Apache.
- **Production**: isi `admin_password_hash` dan `base_url` (alamat publik; dipakai untuk tautan reset password, header Host pengunjung tidak dipercaya), matikan `show_reset_link` (tautan di layar hanya tampil untuk localhost), biarkan `debug` bernilai false (detail error hanya ke log server), dan pakai HTTPS.
- **Akun customer**: nama perusahaan unik dan hanya bisa diubah admin; akun yang dinonaktifkan langsung kehilangan akses.

---

## 📁 Dokumen Perencanaan

- [Ringkasan PMP](docs/Ringkasan_PMP.md) — jadwal, biaya, dan product scope menurut Project Management Plan.
- Skrip migrasi lama diarsipkan di [db/legacy](db/legacy/README.md) dan tidak dipakai lagi.
