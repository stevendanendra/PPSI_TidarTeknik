**DOKUMEN PERENCANAAN PROYEK**

**(PROJECT PLANNING DOCUMENT)**

Pengembangan Sistem Informasi Manajemen Dropship

dan Modul Customer Portal — CV. Tidar Teknik

*Studi Kasus: CV. Tidar Teknik — Supplier Teknik & Industri*

Perum Bumi Mas Blok C1 No 2, Jedong Wagir, Kab. Malang 65158

tidarteknik.my.id \| tidarteknik@gmail.com \| 0822 2525 5251

Mata Kuliah: Perencanaan Proyek Sistem Informasi (PPSI)

Fase: Planning (Perencanaan) — SDLC / PMBOK

Tahun Akademik 2026

# **Daftar Isi**

1\. Project Charter

2\. Identifikasi Stakeholder (Stakeholder Register)

3\. Work Breakdown Structure (WBS)

4\. Jadwal Proyek (Timeline)

5\. Estimasi Biaya (Cost Estimate)

6\. Risk Register

7\. Rencana Komunikasi (Communication Plan)

8\. Rencana Pengadaan (Procurement Plan)

9\. Tabel Perencanaan Proyek (PMBOK Planning Table)

# **1. Project Charter**

## **1.1 Nama Proyek**

Pengembangan Sistem Informasi Manajemen Dropship CV. Tidar Teknik, yang mencakup modul Dashboard, Purchase Order, Nota/Invoice, Penawaran, Master Data (Customer & Supplier), serta Customer Portal, dibangun secara terintegrasi sebagai satu kesatuan sistem (selanjutnya disebut “SIM-Dropship Tidar Teknik”).

## **1.2 Latar Belakang**

CV\. Tidar Teknik adalah perusahaan yang bergerak sebagai supplier teknik dan industri dengan model bisnis dropship: ketika ada customer yang hendak membeli suatu barang, CV. Tidar Teknik akan mencari dan membeli barang tersebut ke supplier terkait, kemudian menjualnya kembali kepada customer. Proses ini melibatkan pembuatan Purchase Order (PO) ke supplier, penerbitan Nota/Invoice dan Surat Jalan ke customer, serta pembuatan Penawaran harga.

Selama ini seluruh proses bisnis dropship di CV. Tidar Teknik — mulai dari pencatatan Purchase Order ke supplier, penerbitan Nota/Invoice dan Penawaran ke customer, hingga informasi ketersediaan produk kepada customer — masih dilakukan secara manual dan tersebar (catatan fisik, telepon, dan WhatsApp). Customer harus menghubungi admin satu per satu untuk mengetahui produk apa saja yang sedang ready, dan apabila membutuhkan produk di luar yang biasa ditawarkan, permintaan tersebut sering kali tidak terdokumentasi dengan baik. Kondisi ini menyebabkan proses menjadi lambat, informasi yang diterima antar customer tidak konsisten, dan berisiko kehilangan peluang penjualan produk baru yang sebenarnya bisa dipenuhi CV. Tidar Teknik.

Oleh karena itu, CV. Tidar Teknik berencana membangun sebuah Sistem Informasi Manajemen Dropship yang terintegrasi dari awal, yang tidak hanya mengelola Dashboard, Purchase Order, Nota/Invoice, Penawaran, dan Master Data Customer/Supplier, tetapi juga menyediakan modul Customer Portal sejak tahap perancangan. Melalui Customer Portal, customer dapat login untuk melihat katalog produk yang sedang ready, serta mengajukan permintaan produk baru di luar katalog yang selanjutnya akan ditindaklanjuti dan dicek ketersediaannya oleh admin CV. Tidar Teknik, sebagai satu kesatuan proses sejak sistem pertama kali dibangun.

## **1.3 Tujuan Proyek**

1.  Membangun sistem informasi terpusat untuk mengelola Purchase Order, Nota/Invoice, Penawaran, serta data Customer dan Supplier.

2.  Mengembangkan Modul Customer Portal agar customer dapat login, melihat katalog produk yang ready, dan mengajukan permintaan produk baru di luar katalog.

3.  Mempercepat penyampaian informasi ketersediaan produk kepada customer serta penanganan permintaan produk baru oleh admin.

4.  Menyediakan dashboard monitoring transaksi, nilai PO, dan nilai penjualan secara real-time bagi manajemen.

5.  Meningkatkan akurasi pencatatan dan mengurangi kesalahan manual dalam proses dropship.

## **1.4 Ruang Lingkup Proyek**

Termasuk dalam ruang lingkup (in-scope):

- Modul Dashboard (ringkasan PO, nota, grafik transaksi per bulan)

- Modul Purchase Order (pembelian ke supplier)

- Modul Nota/Invoice dan Surat Jalan (penjualan ke customer)

- Modul Penawaran harga ke customer

- Modul Master Data: Riwayat Customer, Supplier, dan Master Perusahaan

- Modul Customer Portal: login customer, lihat katalog produk ready, serta pengajuan permintaan produk baru di luar katalog untuk ditindaklanjuti admin

Tidak termasuk dalam ruang lingkup (out-of-scope):

- Sistem akuntansi/pembukuan penuh (jurnal umum, neraca)

- Integrasi payment gateway otomatis

- Aplikasi mobile native (Android/iOS)

## **1.5 Deliverables Utama**

- Dokumen SRS (Software Requirement Specification)

- Desain basis data (ERD) dan desain antarmuka (wireframe/prototype)

- Aplikasi web SIM-Dropship (sisi admin dan sisi Customer Portal)

- Dokumen manual penggunaan (user guide) untuk staf dan customer

- Laporan hasil User Acceptance Test (UAT)

- Laporan akhir proyek (UTS–UAS)

- Artikel/naskah publikasi jurnal ilmiah

- Surat Terima Mitra (Berita Acara serah terima sistem ke CV. Tidar Teknik)

## **1.6 Milestone Utama**

- M1 – Project Charter disetujui (Kick-off)

- M2 – Dokumen SRS disetujui stakeholder

- M3 – Desain sistem (database & UI/UX) selesai

- M4 – Modul inti (Dashboard, PO, Nota, Penawaran, Master Data) selesai dikembangkan

- M5 – Modul Customer Portal selesai dikembangkan

- M6 – UAT selesai dan sistem dinyatakan siap

- M7 – Go-live dan pelatihan pengguna

- M8 – Laporan akhir proyek selesai disusun

- M9 – Artikel ilmiah disubmit ke jurnal/prosiding

- M10 – Surat Terima Mitra ditandatangani (serah terima ke CV. Tidar Teknik)

## **1.7 Estimasi Anggaran Keseluruhan**

Estimasi total biaya pengembangan proyek adalah ± Rp 46.860.000 (empat puluh enam juta delapan ratus enam puluh ribu rupiah), termasuk biaya tenaga kerja, infrastruktur, penyusunan laporan, publikasi jurnal, serah terima mitra, dan cadangan risiko (contingency). Rincian per fase disajikan pada Bab 5.

## **1.8 Asumsi dan Batasan**

Asumsi:

- Customer bersedia menggunakan Customer Portal untuk melihat katalog dan mengajukan permintaan produk baru.

- Data transaksi PO dan Nota yang selama ini dicatat secara manual dapat dimigrasikan/diinput ke dalam sistem baru.

- Pihak manajemen CV. Tidar Teknik dapat dihubungi untuk keperluan validasi kebutuhan dan serah terima.

Batasan:

- Proyek dimulai 7 September 2026 dan seluruh tahapan (hingga Surat Terima Mitra) harus selesai sebelum awal November 2026; fase Maintenance berjalan setelahnya secara berkelanjutan.

- Tim pengembang terbatas (Project Manager, Analyst, Designer, Programmer, Tester).

- Anggaran proyek terbatas sesuai estimasi pada dokumen ini.

## **1.9 Kriteria Keberhasilan Proyek**

- Sistem mampu mengelola data PO dan Nota secara akurat, menggantikan pencatatan manual.

- Modul Customer Portal berhasil diakses dan digunakan oleh minimal 10 customer aktif dalam 1 bulan pertama.

- Waktu respons informasi ketersediaan produk ke customer lebih cepat dibanding proses manual sebelumnya.

- Sistem lolos UAT tanpa bug kritikal dan disetujui oleh Direktur CV. Tidar Teknik.

## **1.10 Sponsor dan Struktur Proyek**

| **Peran**            | **Nama / Jabatan**                        |
|----------------------|-------------------------------------------|
| Project Sponsor      | Direktur CV. Tidar Teknik                 |
| Project Manager      | Mahasiswa / Ketua Tim Proyek              |
| Business Analyst     | Anggota Tim (Analisis Kebutuhan & SRS)    |
| System/UI Designer   | Anggota Tim (Desain Database & Antarmuka) |
| Programmer/Developer | Anggota Tim (Pengembang Aplikasi)         |
| QA / Tester          | Anggota Tim (Pengujian Sistem)            |

# **2. Identifikasi Stakeholder (Stakeholder Register)**

Berikut adalah pihak-pihak yang terlibat atau terdampak oleh proyek pengembangan SIM-Dropship CV. Tidar Teknik, beserta peran, kepentingan, dan tingkat pengaruhnya terhadap proyek.

| **Stakeholder**           | **Peran dalam Proyek**                                                        | **Kepentingan** | **Pengaruh** |
|---------------------------|-------------------------------------------------------------------------------|-----------------|--------------|
| Direktur CV. Tidar Teknik | Sponsor & pengambil keputusan akhir                                           | Tinggi          | Tinggi       |
| Manager Operasional       | Pemberi requirement bisnis, validasi proses                                   | Tinggi          | Sedang       |
| Staf Admin / Purchasing   | Pengguna harian (input PO, Nota, Penawaran)                                   | Tinggi          | Rendah       |
| Supplier                  | Mitra pemasok barang (dihubungi manual oleh admin, bukan pengguna sistem)     | Rendah          | Rendah       |
| Customer                  | Pengguna eksternal Customer Portal (lihat katalog & ajukan permintaan produk) | Tinggi          | Sedang       |
| Tim Pengembang Proyek     | Pelaksana teknis proyek (analisis s.d. maintenance)                           | Tinggi          | Tinggi       |

# **3. Work Breakdown Structure (WBS)**

Struktur rincian pekerjaan proyek disusun mengikuti tahapan SDLC (Planning → Analysis → Design → Implementation → Maintenance) sebagai berikut:

1.  Inisiasi Proyek

    1.  Pembentukan tim proyek dan penyusunan Project Charter

        Studi kelayakan dan identifikasi kebutuhan awal

2.  Analisis Kebutuhan (Requirement Analysis)

    1.  Wawancara dan kuesioner kepada stakeholder

        Penyusunan dokumen SRS

        Validasi SRS bersama stakeholder

3.  Desain Sistem

    1.  Desain arsitektur sistem

        Desain basis data (ERD)

        Desain UI/UX (wireframe & prototype), termasuk halaman Customer Portal

4.  Pengembangan (Implementation)

    1.  Setup infrastruktur (hosting, domain, database)

        Pengembangan modul Dashboard

        Pengembangan modul Purchase Order

        Pengembangan modul Nota/Invoice dan Penawaran

        Pengembangan modul Master Data (Customer & Supplier)

        Pengembangan modul Customer Portal (login, katalog produk, permintaan produk baru)

5.  Pengujian (Testing)

    1.  Unit testing per modul

        Integration testing antar modul

        User Acceptance Test (UAT) bersama staf dan perwakilan customer

6.  Implementasi & Pelatihan (Deployment & Training)

    1.  Instalasi/go-live sistem

        Migrasi data historis PO dan Nota

        Pelatihan staf internal dan sosialisasi Customer Portal ke customer

7.  Penyusunan Laporan Proyek

    1.  Kompilasi dokumentasi teknis dan non-teknis

        Penyusunan laporan akhir proyek (UTS–UAS)

        Review dan konsultasi dengan dosen pembimbing

8.  Publikasi Jurnal Ilmiah

    1.  Penyusunan naskah artikel ilmiah

        Review internal dan revisi naskah

        Submission ke jurnal/prosiding ilmiah

9.  Serah Terima Mitra

    1.  Persiapan dokumen serah terima (Berita Acara)

        Pertemuan resmi serah terima sistem ke CV. Tidar Teknik

        Penandatanganan Surat Terima Mitra

10. Pemeliharaan (Maintenance)

    1.  Monitoring sistem dan perbaikan bug

        Pengembangan lanjutan / penambahan fitur

# **4. Jadwal Proyek (Timeline)**

Proyek dimulai 7 September 2026 dan seluruh tahapan — mulai dari Inisiasi hingga Serah Terima Mitra — ditargetkan selesai sebelum awal November 2026 (± 55 hari), agar sesuai dengan tenggat kalender akademik UTS–UAS. Fase Maintenance berjalan setelahnya secara berkelanjutan. Rincian jadwal per fase adalah sebagai berikut:

| **Fase**                         | **Durasi**    | **Mulai**   | **Selesai** |
|----------------------------------|---------------|-------------|-------------|
| Planning (Inisiasi)              | 4 hari        | 7 Sep 2026  | 10 Sep 2026 |
| Analysis (Kebutuhan & SRS)       | 6 hari        | 11 Sep 2026 | 16 Sep 2026 |
| Design (Database & UI/UX)        | 6 hari        | 17 Sep 2026 | 22 Sep 2026 |
| Implementation – Modul Inti      | 10 hari       | 23 Sep 2026 | 2 Okt 2026  |
| Implementation – Customer Portal | 8 hari        | 3 Okt 2026  | 10 Okt 2026 |
| Testing (Unit, Integrasi, UAT)   | 6 hari        | 11 Okt 2026 | 16 Okt 2026 |
| Deployment & Training            | 4 hari        | 17 Okt 2026 | 20 Okt 2026 |
| Penyusunan Laporan               | 4 hari        | 21 Okt 2026 | 24 Okt 2026 |
| Publikasi Jurnal Ilmiah          | 5 hari        | 25 Okt 2026 | 29 Okt 2026 |
| Serah Terima Mitra               | 2 hari        | 30 Okt 2026 | 31 Okt 2026 |
| Maintenance                      | Berkelanjutan | 1 Nov 2026  | Seterusnya  |

# **5. Estimasi Biaya (Cost Estimate)**

| **Fase**                         | **Estimasi Biaya** |
|----------------------------------|--------------------|
| Planning (Inisiasi)              | Rp 500.000         |
| Analysis (SRS)                   | Rp 5.000.000       |
| Design (Database & UI/UX)        | Rp 4.000.000       |
| Implementation – Modul Inti      | Rp 15.000.000      |
| Implementation – Customer Portal | Rp 10.000.000      |
| Testing                          | Rp 3.000.000       |
| Deployment & Training            | Rp 2.500.000       |
| Penyusunan Laporan               | Rp 1.500.000       |
| Publikasi Jurnal Ilmiah          | Rp 500.000         |
| Serah Terima Mitra               | Rp 100.000         |
| Contingency (± 10%)              | Rp 4.260.000       |

**TOTAL ESTIMASI: ± Rp 46.860.000**

# **6. Risk Register**

| **No** | **Risiko**                                                                  | **Kategori**    | **Kemungkinan** | **Dampak** | **Mitigasi**                                                              | **PIC**     |
|--------|-----------------------------------------------------------------------------|-----------------|-----------------|------------|---------------------------------------------------------------------------|-------------|
| 1      | Manajemen sulit ditemui / sulit mengambil kesepakatan awal                  | Organisasi      | Sedang          | Sedang     | Jadwalkan pertemuan lebih awal, gunakan komunikasi digital (WA/Email)     | PM          |
| 2      | Requirement berubah di tengah proyek                                        | Scope           | Tinggi          | Tinggi     | Gunakan SRS yang disetujui formal & proses change request                 | Analyst     |
| 3      | Stakeholder kurang memahami kebutuhannya sendiri terhadap sistem            | Requirement     | Sedang          | Sedang     | Interview mendalam & workshop bersama pengguna                            | Analyst     |
| 4      | Customer enggan menggunakan Customer Portal, tetap request lewat WA/telepon | Adopsi Pengguna | Sedang          | Tinggi     | Sosialisasi, edukasi, dan kemudahan UX saat login & request produk        | PM          |
| 5      | Keterlambatan pengembangan modul                                            | Jadwal          | Sedang          | Sedang     | Monitoring progress mingguan, tambahan resource bila perlu                | Dev Lead    |
| 6      | Data pribadi/kontak customer disalahgunakan atau bocor                      | Keamanan        | Rendah          | Tinggi     | Role-based access control & enkripsi data customer                        | Developer   |
| 7      | Server/hosting mengalami downtime                                           | Teknis          | Rendah          | Tinggi     | Pilih hosting uptime tinggi & backup data rutin                           | IT Support  |
| 8      | Artikel ilmiah ditolak/perlu revisi signifikan                              | Akademik        | Sedang          | Sedang     | Ajukan ke jurnal/prosiding cadangan, revisi cepat sesuai catatan reviewer | Tim Penulis |
| 9      | Pihak CV. Tidar Teknik sulit dijadwalkan untuk serah terima                 | Organisasi      | Sedang          | Sedang     | Jadwalkan pertemuan serah terima sejak awal & siapkan alternatif waktu    | PM          |

# **7. Rencana Komunikasi (Communication Plan)**

| **Stakeholder**            | **Informasi**                                              | **Metode**                        | **Frekuensi**             | **PIC**         |
|----------------------------|------------------------------------------------------------|-----------------------------------|---------------------------|-----------------|
| Direktur                   | Progress proyek & anggaran                                 | Laporan tertulis & presentasi     | Bulanan                   | PM              |
| Manager Operasional        | Progress requirement & desain                              | Meeting tatap muka                | Mingguan                  | Analyst         |
| Staf Admin/Purchasing      | Update fitur & jadwal training                             | Email & sesi training             | Sesuai kebutuhan          | Developer       |
| Customer (Pengguna Portal) | Panduan penggunaan Customer Portal & status request produk | Email & pemberitahuan di aplikasi | Saat onboarding & berkala | PM              |
| Supplier (Mitra)           | Negosiasi harga & ketersediaan barang untuk PO             | Telepon / WhatsApp                | Sesuai kebutuhan          | Staf Purchasing |
| Tim Pengembang             | Status teknis & kendala pengembangan                       | Standup meeting                   | Mingguan                  | Dev Lead        |
| Dosen Pembimbing           | Progress akademik, laporan & naskah jurnal                 | Bimbingan tatap muka/online       | Mingguan                  | PM              |

# **8. Rencana Pengadaan (Procurement Plan)**

| **Item**                             | **Kebutuhan**                           | **Sumber**                 |
|--------------------------------------|-----------------------------------------|----------------------------|
| Hosting & Domain (tidarteknik.my.id) | Menjalankan aplikasi web secara online  | Penyedia hosting eksternal |
| Software desain (mis. Figma)         | Membuat wireframe & prototype UI/UX     | Lisensi/langganan          |
| Tools pengembangan                   | Text editor, framework, database engine | Open source / gratis       |
| Server tambahan (opsional)           | Antisipasi traffic Customer Portal      | Penyedia layanan cloud     |

# **9. Tabel Perencanaan Proyek (PMBOK Planning Table)**

Tabel berikut merangkum seluruh tahapan proyek pengembangan SIM-Dropship CV. Tidar Teknik dalam satu pandangan, mencakup durasi, biaya, penanggung jawab, target, risiko, komunikasi, stakeholder terkait, dan kebutuhan pengadaan pada tiap tahap.

<table>
<colgroup>
<col style="width: 2%" />
<col style="width: 10%" />
<col style="width: 9%" />
<col style="width: 9%" />
<col style="width: 15%" />
<col style="width: 13%" />
<col style="width: 10%" />
<col style="width: 11%" />
<col style="width: 9%" />
<col style="width: 9%" />
</colgroup>
<thead>
<tr class="header">
<th><strong>No</strong></th>
<th><strong>Task</strong></th>
<th><strong>Duration</strong></th>
<th><strong>Cost</strong></th>
<th><strong>PIC</strong></th>
<th><strong>Goals/Target</strong></th>
<th><strong>Risk</strong></th>
<th><strong>Communication</strong></th>
<th><strong>Stakeholder</strong></th>
<th><strong>Procurement</strong></th>
</tr>
</thead>
<tbody>
<tr class="odd">
<td>1</td>
<td>Inisiasi Proyek</td>
<td>4 hari</td>
<td>Rp 500.000</td>
<td>Project Manager</td>
<td>Project Charter ditandatangani kedua belah pihak</td>
<td><p>Manajemen sulit ditemui</p>
<p>Sulitnya mengambil kesepakatan</p></td>
<td>Presentasi &amp; Dokumen Kontrak</td>
<td><p>Top management:</p>
<p>Direktur, Manager</p></td>
<td>-</td>
</tr>
<tr class="even">
<td>2</td>
<td>Analisis Kebutuhan (Menyusun SRS)</td>
<td>6 hari</td>
<td>Rp 5.000.000</td>
<td>Analyst</td>
<td>Dokumen SRS lengkap sesuai kebutuhan sistem dropship</td>
<td><p>Stakeholder kurang memahami kebutuhannya</p>
<p>Data historis PO/Nota belum lengkap</p></td>
<td>Interview &amp; Kuisioner</td>
<td>Staf operasional (Admin, Purchasing)</td>
<td>-</td>
</tr>
<tr class="odd">
<td>3</td>
<td>Desain Sistem (Database &amp; UI/UX)</td>
<td>6 hari</td>
<td>Rp 4.000.000</td>
<td>System Designer</td>
<td>ERD &amp; wireframe/prototype disetujui stakeholder</td>
<td><p>Desain tidak sesuai ekspektasi pengguna</p>
<p>Kebutuhan customer portal belum jelas</p></td>
<td>Review desain &amp; mockup review</td>
<td>Manager Operasional, Admin</td>
<td>Software desain (Figma)</td>
</tr>
<tr class="even">
<td>4</td>
<td>Pengembangan Modul Inti (Dashboard, PO, Nota, Penawaran, Master Data)</td>
<td>10 hari</td>
<td>Rp 15.000.000</td>
<td>Programmer/Developer</td>
<td>Modul berfungsi sesuai SRS, dapat mengelola data PO &amp; Nota</td>
<td><p>Bug/error saat coding</p>
<p>Keterlambatan pengembangan</p></td>
<td>Progress meeting mingguan</td>
<td>Admin, Staf Purchasing</td>
<td>Hosting &amp; domain</td>
</tr>
<tr class="odd">
<td>5</td>
<td>Pengembangan Modul Customer Portal</td>
<td>8 hari</td>
<td>Rp 10.000.000</td>
<td>Programmer/Developer</td>
<td>Customer dapat login, melihat katalog produk ready, dan mengajukan request produk baru</td>
<td><p>Customer belum familiar dengan sistem</p>
<p>Request produk baru menumpuk tanpa tindak lanjut admin</p></td>
<td>Sosialisasi &amp; panduan penggunaan</td>
<td>Customer</td>
<td>Server tambahan (opsional)</td>
</tr>
<tr class="even">
<td>6</td>
<td>Pengujian (Unit, Integrasi, UAT)</td>
<td>6 hari</td>
<td>Rp 3.000.000</td>
<td>QA/Tester</td>
<td>Sistem bebas bug kritikal dan lolos UAT</td>
<td><p>Testing tidak menemukan seluruh bug</p>
<p>Waktu pengujian terbatas</p></td>
<td>Laporan hasil pengujian</td>
<td>Seluruh pengguna internal</td>
<td>-</td>
</tr>
<tr class="odd">
<td>7</td>
<td>Deployment &amp; Pelatihan</td>
<td>4 hari</td>
<td>Rp 2.500.000</td>
<td>Project Manager, Developer</td>
<td>Sistem live, staf &amp; customer terlatih menggunakan sistem</td>
<td><p>Kendala migrasi data lama</p>
<p>Pengguna belum terbiasa dengan sistem baru</p></td>
<td>Sesi pelatihan &amp; user manual</td>
<td>Seluruh pengguna &amp; Customer</td>
<td>-</td>
</tr>
<tr class="even">
<td>8</td>
<td>Penyusunan Laporan</td>
<td>4 hari</td>
<td>Rp 1.500.000</td>
<td>Project Manager, Tim</td>
<td>Laporan akhir proyek (UTS–UAS) tersusun lengkap &amp; sesuai format</td>
<td><p>Keterlambatan data teknis dari tim developer</p>
<p>Revisi mendadak dari dosen pembimbing</p></td>
<td>Bimbingan &amp; konsultasi dosen</td>
<td>Dosen Pembimbing, Tim Proyek</td>
<td>-</td>
</tr>
<tr class="odd">
<td>9</td>
<td>Publikasi Jurnal Ilmiah</td>
<td>5 hari</td>
<td>Rp 500.000</td>
<td>Tim Penulis</td>
<td>Artikel ilmiah tersusun &amp; disubmit ke jurnal/prosiding</td>
<td><p>Artikel ditolak/perlu revisi signifikan</p>
<p>Batas waktu submission jurnal ketat</p></td>
<td>Review naskah bersama dosen/reviewer</td>
<td>Dosen Pembimbing, Pengelola Jurnal</td>
<td>Biaya publikasi (jika ada)</td>
</tr>
<tr class="even">
<td>10</td>
<td>Serah Terima Mitra</td>
<td>2 hari</td>
<td>Rp 100.000</td>
<td>Project Manager</td>
<td>Surat Terima Mitra ditandatangani CV. Tidar Teknik sebagai bukti proyek selesai</td>
<td><p>Pihak CV. Tidar Teknik sulit dihubungi untuk tanda tangan</p>
<p>Jadwal serah terima bentrok dengan operasional mitra</p></td>
<td>Pertemuan resmi serah terima &amp; dokumentasi</td>
<td>Direktur CV. Tidar Teknik</td>
<td>Materai / dokumen legal</td>
</tr>
<tr class="odd">
<td>11</td>
<td>Maintenance</td>
<td>Berkelanjutan</td>
<td>± Rp 4.000.000/thn</td>
<td>Support Team</td>
<td>Sistem berjalan stabil dengan downtime minimal</td>
<td><p>Server mengalami downtime</p>
<p>Kebutuhan penambahan fitur baru</p></td>
<td>Helpdesk &amp; laporan bulanan</td>
<td>Direktur, Admin, Supplier</td>
<td>Layanan hosting berkelanjutan</td>
</tr>
</tbody>
</table>
