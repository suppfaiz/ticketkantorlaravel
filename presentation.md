---
marp: true
theme: default
paginate: true
size: 16:9
---

# NALA IT Helpdesk

### Pengelolaan permohonan layanan IT dari pengajuan sampai penutupan tiket

**Presentasi untuk pimpinan**

Status: siap untuk uji staging dan UAT sebelum go-live

<!-- Narasi: NALA membantu setiap permohonan memiliki penanggung jawab, tahapan persetujuan, dan riwayat yang dapat ditelusuri. Hindari menyebut aplikasi sudah live sebelum deployment dan UAT selesai. -->

---

## 1. Masalah yang diselesaikan

- Permohonan IT mudah tersebar di chat atau percakapan pribadi.
- Status, penanggung jawab, dan keputusan persetujuan sulit dilacak bersama.
- Hasil pekerjaan perlu diverifikasi oleh pemohon sebelum tiket ditutup.
- Pimpinan memerlukan gambaran antrean dan riwayat keputusan.

**NALA menyatukan proses tersebut dalam satu tiket dengan nomor dan jejak audit.**

<!-- Narasi: Ini adalah masalah proses yang dituju aplikasi, bukan klaim angka penghematan yang sudah terbukti. -->

---

## 2. Apa yang sudah tersedia?

| Area | Kemampuan saat ini |
| --- | --- |
| Pengajuan | Form tiket, kategori, prioritas, lampiran, draf, revisi |
| Persetujuan | Atasan, Kabag IT, Kadiv IT, Direksi sesuai jenis tiket |
| Pengerjaan | Pool teknisi, penugasan, progres, lampiran hasil |
| Penutupan | Konfirmasi pemohon, rating, atau permintaan perbaikan |
| Pemantauan | Dashboard per peran, kalender, notifikasi, audit, ekspor CSV |

<!-- Narasi: Fitur di tabel sudah ada dalam aplikasi. WhatsApp dan deteksi request serupa belum termasuk. -->

---

## 3. Siapa melakukan apa?

| Peran | Tindakan utama |
| --- | --- |
| Pemohon | Membuat tiket dan memeriksa hasil |
| Atasan langsung | Menyetujui, menolak, atau meminta revisi |
| Kabag IT | Mengklasifikasikan Minor/Major, menentukan target dan teknisi |
| Kadiv IT dan Direksi | Memberi keputusan untuk alur Major |
| Teknisi IT | Mengambil/menangani tiket dan mencatat progres |
| Admin | Mengelola akun, divisi, kategori, dan delegasi |

**Atasan langsung ditentukan pada data pengguna, bukan hanya berdasarkan nama role.**

<!-- Narasi: Dua orang bisa sama-sama berperan sebagai atasan, tetapi tiket diarahkan ke atasan yang terhubung ke pemohonnya. -->

---

## 4. Alur tiket Minor

```text
Pemohon membuat tiket
  → Atasan langsung menyetujui
  → Kabag IT menetapkan klasifikasi Minor
  → Masuk pool teknisi atau langsung ditugaskan
  → Teknisi mengerjakan dan mengirim hasil
  → Pemohon menerima hasil atau meminta revisi
  → Selesai
```

Tiket dapat dikembalikan untuk revisi pada tahap yang memang mengizinkannya.

<!-- Narasi: Tunjukkan satu tiket Minor saat demo agar tiap perpindahan status terlihat jelas. -->

---

## 5. Alur tiket Major

```text
Pemohon → Atasan → Kabag IT (klasifikasi Major)
  → Kadiv IT → Direksi
  → Rapat dan notulensi → Persetujuan notulensi
  → Pool/penugasan teknisi → Pengerjaan
  → Verifikasi pemohon → Selesai atau revisi
```

**Alur Major memiliki persetujuan dan dokumentasi tambahan sebelum pengerjaan.**

<!-- Narasi: Jelaskan bahwa tahapan tambahan mengikuti kebutuhan tata kelola permohonan besar. -->

---

## 6. Cara kerja aplikasi

```text
Browser pengguna
  → Route memilih fungsi tujuan
  → Middleware dan Policy memeriksa akses
  → Request memvalidasi input
  → Controller menerima aksi
  → Service menjalankan aturan bisnis
  → Model menyimpan/membaca MySQL
  → Halaman Blade menampilkan hasil
```

Perubahan status juga dicatat pada **audit trail** dan dapat menghasilkan **notifikasi**.

<!-- Narasi: Route seperti resepsionis, controller menerima pekerjaan, service menjalankan aturan, dan database menyimpan hasilnya. -->

---

## 7. Routing: tombol terhubung ke proses apa?

| Metode dan URL | Fungsi |
| --- | --- |
| `GET /login` | Menampilkan halaman login |
| `POST /login` | Memeriksa kredensial dan membuat sesi |
| `GET /dashboard` | Menampilkan dashboard sesuai peran |
| `POST /tickets` | Membuat tiket baru |
| `POST /tickets/{ticket}/supervisor-approve` | Persetujuan atasan |
| `POST /tickets/{ticket}/self-assign` | Teknisi mengambil tiket dari pool |
| `GET /tickets/{ticket}/attachments/{attachment}/download` | Unduh lampiran dengan pemeriksaan akses |

`{ticket}` adalah ID tiket pada URL yang dicari menjadi data tiket oleh Laravel.

Sumber: [route web](routes/web.php) dan [route login](routes/auth.php).

<!-- Narasi: GET membaca/menampilkan, POST mengirim tindakan. Route adalah peta alamat; aturan bisnisnya ada di controller dan service. -->

---

## 8. Contoh kode: persetujuan atasan

Di [routes/web.php](routes/web.php), route ini menghubungkan tombol dengan fungsi controller:

```php
Route::post('/tickets/{ticket}/supervisor-approve',
    [SupervisorApprovalController::class, 'approve'])
    ->name('tickets.supervisor-approve');
```

[Controller](app/Http/Controllers/SupervisorApprovalController.php) meminta perubahan status ke **Menunggu Approval Kepala Bagian IT**. [TicketStateMachine](app/Services/TicketStateMachine.php) memeriksa apakah perpindahan status sah, mencatat audit, lalu mengirim notifikasi.

**Intinya: satu tombol tidak langsung mengubah database tanpa pemeriksaan alur dan hak akses.**

<!-- Narasi: Buka tiga file ini saat ditanya sisi teknis. Tidak perlu membacakan seluruh kode. -->

---

## 9. Data yang disimpan

| Kelompok data | Contoh tabel |
| --- | --- |
| Pengguna dan organisasi | `users`, `departments`, `approval_delegations` |
| Permohonan | `tickets`, `ticket_attachments`, `meeting_minutes` |
| Aktivitas dan hasil | `ticket_activity_logs`, `ticket_reviews`, `chat_messages` |
| Pengawasan | `ticket_audit_logs`, `notifications` |
| Integrasi LOA | `external_loa_transactions` |

Skema tabel dibuat oleh [migration](database/migrations/), bukan dengan membuat kolom manual di cPanel.

<!-- Narasi: Nomor tiket, pemohon, atasan, status, target, teknisi, waktu persetujuan, dan penutupan tersimpan pada tiket; riwayatnya disimpan di tabel terkait. -->

---

## 10. Pengamanan akses dan lampiran

- Halaman kerja memerlukan login; tindakan admin dibatasi untuk role admin.
- [Policy tiket](app/Policies/TicketPolicy.php) memeriksa siapa boleh melihat atau mengubah tiket.
- Akun nonaktif ditolak saat login dan sesi aktifnya dihentikan.
- Lampiran tersimpan di `storage/app/private`, di luar folder yang disajikan langsung oleh web.
- Unduhan melewati [controller](app/Http/Controllers/AttachmentController.php) yang memeriksa hak akses setiap kali URL dibuka.

**Syarat deployment:** document root cPanel hanya mengarah ke file dari `public/`.

<!-- Narasi: Nama file acak bukan pengaman utama. Pemisahan folder privat dan pemeriksaan hak akses adalah pengamannya. -->

---

## 11. Integrasi sekarang dan rencana berikutnya

**Saat ini:** akun/login dan keputusan LOA dapat dijalankan secara lokal di NALA.

**Ketika aplikasi eksternal siap:** adapter SSO dan LOA dapat dipilih melalui konfigurasi `SSO_DRIVER` dan `LOA_DRIVER`. Kontrak API eksternal tetap perlu diuji bersama pemilik aplikasi tersebut sebelum diaktifkan.

Route API masuk yang tersedia: `POST /api/v1/webhooks/loa-decision`, dengan verifikasi signature. Lihat [routes/api.php](routes/api.php) dan [rancangan integrasi](docs/INTEGRATION.md).

**Belum diimplementasikan:** pengiriman WhatsApp dan deteksi otomatis request yang mirip untuk backlog.

<!-- Narasi: Hindari menyebut integrasi eksternal, WhatsApp, atau backlog sebagai fitur yang sudah aktif. -->

---

## 12. Rencana deployment cPanel

1. Siapkan staging, domain HTTPS, PHP yang sesuai, dan MySQL.
2. Letakkan source serta `storage` di luar web root; tayangkan hanya `public/`.
3. Isi `.env` production; gunakan `SSO_DRIVER=local` dan `LOA_DRIVER=local` pada tahap awal.
4. Jalankan migration dan seeder. Tanpa Terminal, perintah Artisan dapat dijalankan sementara lewat UI **Cron Jobs** jika tersedia.
5. Uji alur Minor/Major, hak akses, email, backup dan restore sebelum go-live.

Panduan lengkap: [DEPLOYMENT_CPANEL.md](docs/DEPLOYMENT_CPANEL.md).

<!-- Narasi: Pembuatan tabel dilakukan oleh migration. Jika Cron Jobs tidak tersedia, minta admin hosting menjalankan perintah Artisan. -->

---

## 13. Demo yang disarankan

1. Login sebagai pemohon, buat tiket dan tunjukkan nomor tiket.
2. Login sebagai atasan yang terkait, beri persetujuan.
3. Login sebagai Kabag IT, tentukan Minor dan target/teknisi.
4. Login sebagai teknisi, ambil tiket dan catat progres.
5. Login sebagai pemohon, terima hasil atau minta revisi.
6. Tunjukkan jejak audit, notifikasi, dan lampiran yang hanya dapat diakses pengguna berhak.

**Gunakan data demonstrasi di staging, bukan tiket atau kredensial production.**

<!-- Narasi: Jika waktu terbatas, cukup jalankan langkah 1, 2, 3, dan tunjukkan status akhir dari tiket yang sudah disiapkan. -->

---

## 14. Kebutuhan sebelum go-live

- Akses staging cPanel, MySQL, HTTPS, dan mekanisme menjalankan Artisan.
- Penetapan admin aplikasi, pemilik data pengguna, dan struktur atasan langsung.
- SMTP serta cron/queue untuk pengiriman email dan pekerjaan terjadwal.
- UAT dari tiap peran dan persetujuan alur Minor/Major.
- Backup database dan lampiran yang sudah diuji pemulihannya.
- Jadwal dan kontrak teknis integrasi SSO/LOA eksternal untuk fase berikutnya.

**Keputusan yang diminta:** persetujuan uji staging dan penanggung jawab UAT tiap peran.

<!-- Narasi: Presentasi ini menunjukkan kesiapan fungsi aplikasi; keputusan go-live dilakukan setelah hasil staging dan UAT memenuhi kebutuhan kantor. -->
