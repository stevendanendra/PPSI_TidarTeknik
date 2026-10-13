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
- **Keranjang & Checkout**: Proses pemesanan online langsung ke sistem CV. Tidar Teknik.
- **Tracking Riwayat Pesanan**: Pantau status pesanan, invoice digital, dan konfirmasi pembayaran.
- **Request Produk Custom**: Form pengajuan kebutuhan teknis khusus atau suku cadang langka.

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
   - Buat database baru bernama `ppsi_tidarteknik` (atau biarkan `db.php` membuat otomatis saat pertama kali dibuka).
   - Import file database `backup_tidar_teknik.sql` untuk memuat data awal.

4. **Konfigurasi Koneksi**:
   Sesuaikan konfigurasi database pada file [db.php](db.php):
   ```php
   $host = 'localhost';
   $user = 'root';
   $pass = '';
   $db   = 'ppsi_tidarteknik';
   ```

5. **Jalankan Aplikasi**:
   Akses melalui browser di:
   - Panel Admin / Sistem: `http://localhost/PPSI_TidarTeknik/`
   - Customer Portal: `http://localhost/PPSI_TidarTeknik/customer-products.php`

---

## 📁 Struktur Dokumen Perencanaan

Dokumentasi lengkap perencanaan proyek (WBS, Project Charter, Risk Register, Cost Estimate) dapat ditemukan di:
- [Dokumen Perencanaan Tidar Teknik](docs/Dokumen_Perencanaan_Tidar_Teknik.md)
