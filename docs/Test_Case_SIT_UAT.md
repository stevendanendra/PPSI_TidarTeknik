# Test Case SIT & UAT — SIM Dropship CV. Tidar Teknik

Dipakai untuk **SIT** (24–29 Okt 2026, tim pengembang) dan **UAT** (30 Okt–1 Nov 2026, CEO dan Admin). Isi kolom **Hasil** (Lulus / Gagal) dan **Catatan** saat menguji; lampirkan tangkapan layar untuk yang gagal. Rekap akhir dipakai sebagai Berita Acara UAT.

Kolom **Otomatis** menandai skenario yang sudah dicakup skrip uji pengembang (HTTP/browser). Tetap diuji manual di server tujuan karena lingkungan berbeda.

Persiapan: import `db/ppsi_tidarteknik.sql`, buat `config.php` dari `config.sample.php`, siapkan 1 akun admin dan minimal 2 akun customer (buat lewat menu **Akun Customer**), minimal 2 supplier, 2 produk aktif dengan supplier berbeda.

## A. Autentikasi & akun

| ID | Skenario | Langkah singkat | Hasil yang diharapkan | Otomatis | Hasil | Catatan |
|---|---|---|---|---|---|---|
| A01 | Login admin benar | Isi username/password admin | Masuk dashboard | Ya | | |
| A02 | Login salah | Password salah | Pesan gagal; tidak masuk | Ya | | |
| A03 | Batas percobaan | 5× salah berturut-turut | Pesan "Terlalu banyak percobaan", terkunci 15 menit | Ya | | |
| A04 | Halaman admin tanpa login | Buka `index.php` tanpa login | Diarahkan ke `login.php` | Ya | | |
| A05 | Admin buat akun customer | Menu Akun Customer → isi nama, email, password | Akun muncul di daftar; customer bisa login | Ya | | |
| A06 | Nama perusahaan kembar | Buat akun dengan nama yang sudah ada | Ditolak dengan pesan | Ya | | |
| A07 | Wajib ganti password | Buat akun dengan opsi wajib ganti | Login pertama diarahkan ke Profil untuk ganti password | | | |
| A08 | Tutorial login pertama | Login customer baru | Tutorial muncul sekali; tombol "Mengerti" menutupnya | | | |
| A09 | Lupa password | Klik "Lupa password?", isi email | Pesan seragam; email berisi tautan reset (server produksi) | Sebagian | | |
| A10 | Reset password | Buka tautan, isi password baru | Bisa login dengan password baru; tautan tidak bisa dipakai ulang | Ya | | |
| A11 | Akun dinonaktifkan | Admin nonaktifkan akun customer yang sedang login | Request berikutnya diarahkan ke login | Ya | | |
| A12 | Help Center | Menu Help Center | Alur singkat dan FAQ tampil | | | |
| A13 | Registrasi mandiri mati | Buka `customer-register.php` | Diarahkan ke login | Ya | | |
| A14 | Login sebelum email diverifikasi | Login akun baru yang emailnya belum diverifikasi | Ditolak dengan pesan "belum diverifikasi" + tautan kirim ulang | Ya | | |
| A15 | Kirim ulang verifikasi | Isi email di halaman kirim ulang | Pesan seragam (email terdaftar/tidak); tautan lama tidak berlaku | Ya | | |
| A16 | Verifikasi email | Buka tautan verifikasi dari email | Email terverifikasi, bisa login; tautan tidak bisa dipakai ulang | Ya | | |

## L. Landing page & akses

| ID | Skenario | Hasil yang diharapkan | Otomatis | Hasil | Catatan |
|---|---|---|---|---|---|
| L01 | Landing page publik | Tombol Masuk ada; tidak ada tautan login admin; harga tidak tampil | Ya | | |
| L02 | Alamat root (`/`) | Menampilkan landing page, bukan login admin | Ya | | |
| L03 | Halaman login/lupa password/kirim ulang customer | Tidak ada tautan/teks login admin | Ya | | |
| L04 | Logout customer | Kembali ke landing page | Ya | | |
| L05 | Pengunjung buka halaman customer langsung | Diarahkan ke login customer | Ya | | |

## B. Katalog & supplier

| ID | Skenario | Hasil yang diharapkan | Otomatis | Hasil | Catatan |
|---|---|---|---|---|---|
| B01 | Tambah produk dengan rentang harga | Katalog customer menampilkan "Rp X – Rp Y" | | | |
| B02 | Stok tidak tampil ke customer | Tidak ada tulisan stok/ready di katalog dan detail produk | Ya | | |
| B03 | Kandidat supplier | Tambah ≥2 kandidat, jadikan salah satu final | Final tampil di daftar; supplier produk ikut berubah | | |
| B04 | Pencarian & filter kategori | Hasil sesuai | | | |
| B05 | Wishlist | Tambah/hapus favorit | | | |

## C. Order, keranjang, penawaran

| ID | Skenario | Hasil yang diharapkan | Otomatis | Hasil | Catatan |
|---|---|---|---|---|---|
| C01 | Keranjang multi-produk | 2 produk masuk keranjang, jumlah bisa diubah | Ya | | |
| C02 | Checkout multi-produk | 1 order berisi 2 item; keranjang kosong | Ya | | |
| C03 | Klik "Buat Pesanan" dua kali | Hanya 1 pesanan terbentuk | Ya | | |
| C04 | Jumlah tidak wajar | 0, pecahan sangat kecil, atau angka raksasa ditolak | Ya | | |
| C05 | Request barang custom | Admin menyetujui dengan harga; customer checkout dari Request | | | |
| C06 | Request disetujui dipakai sekali | Percobaan checkout kedua ditolak | Ya | | |
| C07 | Penawaran V1 dikirim | Admin klik Kirim; customer melihat di menu Penawaran | Ya | | |
| C08 | Customer minta revisi / tolak | Alasan wajib; admin menerima notifikasi | Ya | | |
| C09 | Revisi V2 | Admin klik Revisi: versi V2 berisi salinan item | Ya | | |
| C10 | Customer setujui | Status Disetujui; notifikasi ke admin | | | |
| C11 | Penomoran otomatis | PO/Nota/Penawaran baru bernomor `PREFIX/0001/TT/BLN/THN` | Ya | | |

## D. PO, pembayaran, pengiriman

| ID | Skenario | Hasil yang diharapkan | Otomatis | Hasil | Catatan |
|---|---|---|---|---|---|
| D01 | Satu order → beberapa PO | Admin pilih supplier berbeda per barang → 2 PO terbit | Ya | | |
| D02 | PO tercetak | Cetak PO: nomor, supplier, item, PPN, total benar | | | |
| D03 | Upload bukti DP | Bukti diterima; status menunggu verifikasi | Ya | | |
| D04 | File bukan gambar/PDF | Ditolak | Ya | | |
| D05 | Jumlah melebihi sisa tagihan | Ditolak | Ya | | |
| D06 | Verifikasi DP | Status pembayaran DP; sisa tagihan berkurang | Ya | | |
| D07 | Pelunasan | Status Paid | Ya | | |
| D08 | Tolak bukti | Catatan wajib; customer menerima notifikasi dan bisa unggah ulang | | | |
| D09 | Status Term & Partial | Pembayaran sebagian/termin → status sesuai | | | |
| D10 | Kirim barang | Admin isi resi, status Dikirim; customer dapat notifikasi | Ya | | |
| D11 | Konfirmasi terima | Customer klik; status Selesai | Ya | | |
| D12 | Selesai otomatis | Pesanan dikirim >7 hari tanpa konfirmasi → Selesai | Ya | | |
| D13 | Invoice | Customer membuka/mencetak invoice pesanannya | Ya | | |

## E. Pembatalan & retur

| ID | Skenario | Hasil yang diharapkan | Otomatis | Hasil | Catatan |
|---|---|---|---|---|---|
| E01 | Batal sebelum PO | Alasan wajib; status Dibatalkan | Ya | | |
| E02 | Batal setelah PO terbit | Tombol tidak ada / ditolak | Ya | | |
| E03 | Ajukan retur | Foto/video terunggah; admin dapat notifikasi | Ya | | |
| E04 | Setujui → selesai (ganti/refund) | Status mengikuti; customer menerima notifikasi | Ya | | |
| E05 | Tolak retur | Alasan wajib | | | |

## F. Dashboard, notifikasi, dokumen

| ID | Skenario | Hasil yang diharapkan | Otomatis | Hasil | Catatan |
|---|---|---|---|---|---|
| F01 | Grafik PO & Nota | Terisi dari data; tombol 3/6/12 bulan berfungsi | | | |
| F02 | Notifikasi jatuh tempo | Banner dokumen jatuh tempo benar | | | |
| F03 | Lonceng notifikasi | Angka belum-dibaca naik; membuka daftar menandai terbaca | Ya | | |
| F04 | Cetak Penawaran/PO/Nota/Surat Jalan | Layout, PPN, diskon, tanda tangan/stempel tampil | | | |
| F05 | Tampilan HP (lebar 390px) | Tidak ada halaman yang melebar ke samping | | | |

## G. Keamanan (SIT)

| ID | Skenario | Hasil yang diharapkan | Otomatis | Hasil | Catatan |
|---|---|---|---|---|---|
| G01 | API tanpa login | `api.php` → 401 JSON | Ya | | |
| G02 | POST tanpa token CSRF | 403 | Ya | | |
| G03 | Customer A membuka pesanan/invoice/bukti customer B | Ditolak / "tidak ditemukan" | Ya | | |
| G04 | Akses langsung `/uploads/...`, `/db/...sql`, `/README.md` | 403/404 | Ya | | |
| G05 | Nama berisi tag HTML (mis. `<img src=x onerror=alert(1)>`) | Tampil sebagai teks di semua halaman, termasuk cetak dan Riwayat | Ya | | |
| G06 | Simpan ulang data lama lewat UI | Jumlah PO/Nota/Penawaran tidak berubah; daftar kosong tidak menghapus data | Ya | | |
| G07 | Server produksi | `admin_password_hash` terisi, `base_url` diisi, `show_reset_link` false, HTTPS aktif | | | |

## H. Kompatibilitas

| ID | Skenario | Hasil yang diharapkan | Hasil | Catatan |
|---|---|---|---|---|
| H01 | Chrome terbaru | Semua alur berjalan | | |
| H02 | Edge / Firefox | Tampilan dan form berfungsi | | |
| H03 | HP (Chrome Android / Safari iOS) | Form, upload foto, dan tabel dapat dipakai | | |
| H04 | MySQL 8 dan MariaDB | Import dump sukses; tabel tambahan dibuat otomatis | | |

## Rekap

| Kategori | Jumlah | Lulus | Gagal | Belum diuji |
|---|---|---|---|---|
| A. Autentikasi | 13 | | | |
| B. Katalog | 5 | | | |
| C. Order/Penawaran | 11 | | | |
| D. PO/Pembayaran/Pengiriman | 13 | | | |
| E. Batal/Retur | 5 | | | |
| F. Dashboard/Dokumen | 5 | | | |
| G. Keamanan | 7 | | | |
| H. Kompatibilitas | 4 | | | |

Penguji: ______________  Tanggal: ______________  Disetujui (CEO/Admin): ______________
