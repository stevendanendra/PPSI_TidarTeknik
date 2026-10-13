# Ringkasan Project Management Plan (PMP)

SIM Dropship CV. Tidar Teknik — Program Studi Sistem Informasi, Universitas Ma Chung.
Tim: Steven Danendra (PM & System Analyst), Trevin Yoris Kenjiro (UI/UX & Frontend), Felix Chrisyanto (Backend & QA Lead).

Dokumen resmi: *Laporan Manajemen Proyek — Aplikasi Dropship CV Tidar Teknik* (PDF PMP). Ringkasan ini hanya rujukan cepat di repo; bila ada selisih, PDF PMP yang berlaku.

## Jadwal

Periode proyek **21 September – 21 November 2026** (satuan hari, mengikuti Logbook PPSI Okt–Nov 2026).

| Tahap | Mulai | Selesai |
|---|---|---|
| Project Charter, identifikasi stakeholder, analisis kebutuhan | 21 Sep | 24 Sep |
| Rancangan arsitektur & database | 24 Sep | 29 Sep |
| Rancangan UI/UX | 29 Sep | 4 Okt |
| Coding backend & frontend (paralel, SS) | 4 Okt | 24 Okt |
| System Integration Testing (SIT) | 24 Okt | 29 Okt |
| Deployment ke production server | 29 Okt | 30 Okt |
| User Acceptance Testing (UAT) | 30 Okt | 1 Nov |
| Serah terima, artikel jurnal, evaluasi, penutupan | 1 Nov | 14 Nov |

Monitoring progress & change request berjalan 4 Okt – 21 Nov.

## Biaya

- Biaya langsung (17 aktivitas): **Rp 9.000.000**
- Biaya operasional: 15% (Rp 1.350.000); contingency reserve: 10%; margin: 25%
- **Nilai jual proyek: Rp 14.231.250** = (biaya langsung + operasional + contingency) × 1,25
- Anggaran honor tim (rincian internal biaya langsung): Rp 8.590.000

## Product scope (fitur)

1. **Autentikasi & akun** — login/logout, lupa password, akun customer dibuat Admin, Help Center, tutorial login pertama.
2. **Katalog produk** — nama, kategori, foto, satuan, spesifikasi, rentang harga, kandidat supplier; stok tidak ditampilkan.
3. **Order & request** — order dengan beberapa produk, custom product request.
4. **Supplier management** — beberapa kandidat supplier per produk, pemilihan supplier final; supplier tanpa akses login.
5. **Penawaran & negosiasi** — quotation, approve/reject/revisi, versioning V1/V2/V3.
6. **Dokumen transaksi (PDF)** — quotation, beberapa PO dalam satu order, invoice/receipt, delivery note; penomoran otomatis, pajak, diskon, ongkir, tanda tangan/stempel.
7. **Pembayaran** — transfer manual BCA, upload bukti, verifikasi Admin, status DP / Term / Partial / Paid.
8. **Pengiriman & penerimaan** — delivery note, status pengiriman, konfirmasi terima customer, selesai otomatis berdasarkan durasi.
9. **Pembatalan & retur** — pembatalan sebelum PO, retur dengan foto/video, verifikasi Admin, penggantian atau refund.
10. **Dashboard & notifikasi** — status transaksi, notifikasi jatuh tempo, notifikasi internal.

## Di luar scope

Portal login supplier, payment gateway otomatis, aplikasi native Android/iOS, integrasi stok real-time supplier, integrasi pelacakan kurir, modul akuntansi lengkap, pengiriman WhatsApp otomatis via API, pemeliharaan setelah handover.

## Deliverable

SRS, sitemap & business flow, rancangan database & UI/UX, source code, modul transaksi teruji, dokumen PDF transaksi, hasil SIT, berita acara UAT, aplikasi ter-deploy, user manual & tutorial customer, artikel jurnal, dokumen handover & lesson learned.
