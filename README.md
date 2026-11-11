# NALA IT Helpdesk

NALA IT Helpdesk adalah aplikasi internal untuk mengelola permohonan layanan dan perubahan sistem IT dari pengajuan, persetujuan berjenjang, pelaksanaan pekerjaan, sampai verifikasi dan penutupan tiket.

> **Status proyek:** baseline pengembangan. Aplikasi belum direkomendasikan untuk production sebelum blocker pada bagian [Kesiapan production](#kesiapan-production) diselesaikan.

## Fitur utama

- Pengajuan tiket beserta kategori, prioritas, deskripsi, dan lampiran.
- Draf, pembatalan, revisi, dan pelacakan status tiket.
- Persetujuan Supervisor dan klasifikasi Minor/Major oleh Kabag IT.
- Rantai persetujuan Major melalui Kadiv IT dan Direksi.
- Penjadwalan rapat, notulensi, revisi, dan persetujuan notulensi.
- Pool pekerjaan dan self-assignment untuk IT Developer.
- Catatan aktivitas, lampiran pekerjaan, dan penyelesaian pekerjaan.
- Verifikasi hasil, rating, dan permintaan revisi oleh pemohon.
- Dashboard berbeda untuk setiap role.
- Kalender, timeline, laporan CSV, audit trail, diskusi tiket, dan chat.
- Adapter integrasi SSO dan LOA eksternal yang akan digunakan pada fase berikutnya.

## Role pengguna

| Role | Tanggung jawab utama |
|---|---|
| `requester` | Mengajukan tiket, melengkapi revisi/notulensi, dan memverifikasi hasil |
| `supervisor` | Menyetujui, menolak, atau meminta revisi tiket bawahan |
| `it_head` | Mengklasifikasikan tiket, menetapkan target/teknisi, dan menyetujui notulensi |
| `kadiv_it` | Memberikan keputusan LOA tahap Kadiv untuk tiket Major |
| `direksi` | Memberikan keputusan LOA tahap Direksi untuk tiket Major |
| `it_developer` | Mengambil tiket, mencatat progres, dan menyelesaikan pekerjaan |
| `admin` | Mengelola pengguna, departemen, kategori, serta membantu operasional |

## Alur tiket

### Tiket Minor

```text
Requester
  -> Supervisor
  -> Kabag IT (klasifikasi Minor)
  -> Pool/penugasan IT Developer
  -> Pengerjaan
  -> Verifikasi Requester
  -> Selesai atau revisi pekerjaan
```

### Tiket Major

```text
Requester
  -> Supervisor
  -> Kabag IT (klasifikasi Major)
  -> Kadiv IT
  -> Direksi
  -> Rapat dan notulensi
  -> Persetujuan Kabag IT
  -> Pool/penugasan IT Developer
  -> Pengerjaan
  -> Verifikasi Requester
  -> Selesai atau revisi pekerjaan
```

Status tiket divalidasi melalui `TicketStateMachine` dan setiap transisi penting dicatat pada audit trail.

## Teknologi

- PHP `^8.2`
- Laravel `^11.31`
- MySQL/MariaDB untuk production; SQLite in-memory untuk test
- Blade, Alpine.js, Tailwind CSS, dan Vite
- Chart.js dan Trumbowyg
- PHPUnit 11

## Menjalankan secara lokal

### Prasyarat

- PHP 8.2 atau lebih baru beserta extension Laravel yang dibutuhkan
- Composer 2
- Node.js dan npm
- SQLite atau MySQL/MariaDB

### Instalasi

```bash
git clone https://github.com/suppfaiz/ticketkantorlaravel.git
cd ticketkantorlaravel
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Atur koneksi database di `.env`, lalu jalankan:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

> **Catatan saat ini:** fresh migration masih terhalang urutan migration `target_completion_at` yang lebih awal daripada pembuatan tabel `tickets`. Perbaiki blocker tersebut sebelum memakai langkah instalasi ini sebagai prosedur resmi.

Untuk development terpadu:

```bash
composer run dev
```

Seeder non-production membuat akun demonstrasi dengan password `password`. Akun tersebut hanya untuk local development/testing dan tidak dibuat oleh `ProductionSeeder`.

## Pengujian

```bash
php artisan test
npm run build
```

Hasil audit baseline:

- Frontend build berhasil.
- `npm audit --omit=dev` tidak menemukan vulnerability production dependency.
- Test repo saat ini tertahan oleh urutan migration.
- Pada salinan pengujian dengan urutan migration diperbaiki, 100 dari 102 test lulus; dua test registrasi sudah tidak sesuai karena registrasi publik dinonaktifkan.

Release tidak boleh dilakukan sebelum fresh migration dan seluruh test yang relevan lulus.

## Konfigurasi autentikasi dan LOA

Konfigurasi berada pada `.env` dan dibaca melalui `config/services.php`:

```env
SSO_DRIVER=mock
LOA_DRIVER=mock
```

Driver yang efektif pada kode saat ini:

- `mock`: untuk development/testing saja.
- `api` atau `enterprise`: memakai API eksternal.

Mode `local` yang aman untuk deployment awal **belum diimplementasikan**. Nilai selain `api`/`enterprise` saat ini masih jatuh ke mock. Refactor berikutnya harus menambahkan pemilihan eksplisit `local`, `api`, dan `mock`, serta menolak mock di production.

Rancangan perpindahan local-to-API dijelaskan di [Dokumentasi Integrasi](docs/INTEGRATION.md).

## Endpoint API

### Public webhook

```http
POST /api/v1/webhooks/loa-decision
```

Webhook menerima keputusan LOA eksternal dan dilindungi signature HMAC.

### Endpoint JSON internal

```http
GET  /chat/messages
POST /chat/messages
POST /tickets/{ticket}/discussions
```

Endpoint internal menggunakan session Laravel dan middleware autentikasi, bukan public REST API.

## Kesiapan production

Blocker yang harus diselesaikan sebelum deployment:

1. Memperbaiki urutan migration agar `migrate:fresh` berhasil.
2. Menambahkan autentikasi lokal yang eksplisit dan aman.
3. Menambahkan `LocalLoaService` untuk approval di aplikasi.
4. Menolak driver mock pada environment production.
5. Memperbaiki otorisasi pengiriman chat bertiket.
6. Memverifikasi signature, issuer, audience, algorithm, dan expiry JWT SSO.
7. Memperbarui test registrasi publik dan membuat seluruh test hijau.
8. Menentukan strategi polling/WebSocket yang kompatibel dengan cPanel.
9. Memindahkan email dan komunikasi eksternal ke queue.
10. Menyelesaikan UAT seluruh role pada staging.

Lihat [Panduan Deployment cPanel](docs/DEPLOYMENT_CPANEL.md) untuk checklist yang lebih rinci.

## Struktur direktori penting

```text
app/Services/              Integrasi SSO, LOA, state machine, dan audit trail
app/Policies/              Aturan otorisasi tiket dan notulensi
database/migrations/       Skema database
database/seeders/          Master data development/production
resources/views/           Antarmuka Blade
routes/web.php             Halaman dan aksi berbasis session
routes/api.php             Webhook/API publik
specs/                     Spesifikasi dan rencana fitur
tests/                     Unit dan feature tests
docs/                      Dokumentasi operasional dan integrasi
```

## Dokumen terkait

- [Panduan deployment cPanel](docs/DEPLOYMENT_CPANEL.md)
- [Rancangan integrasi SSO dan LOA](docs/INTEGRATION.md)
- [Desain antarmuka](DESIGN.md)
- [Spesifikasi fitur](specs/)
- [Laporan audit keamanan](Laporan_Audit_Keamanan_Aplikasi_Helpdesk_IT.pdf)

## Keamanan

- Jangan commit `.env`, private key, token, database, log, atau attachment pengguna.
- Gunakan `APP_DEBUG=false` di production.
- Gunakan HTTPS dan secure session cookie.
- Ganti credential admin awal sebelum seeding production.
- Simpan source Laravel di luar public document root bila cPanel memungkinkan.
- Laporkan vulnerability secara privat kepada pengelola sistem; jangan menuliskan credential atau data pengguna pada GitHub issue.
