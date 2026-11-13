# NALA IT Helpdesk

NALA IT Helpdesk adalah aplikasi internal untuk mengelola permohonan layanan dan perubahan sistem IT dari pengajuan, persetujuan berjenjang, pelaksanaan pekerjaan, sampai verifikasi dan penutupan tiket.

> **Status proyek:** refactor inti untuk mode lokal dan cPanel telah diterapkan. Deployment tetap harus melewati staging, UAT, serta checklist pada bagian [Kesiapan production](#kesiapan-production).

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
- Notifikasi tersimpan dengan status dibaca/belum dibaca untuk perubahan status, assignment, progres, dan diskusi tiket.
- Delegasi approval bertanggal untuk menggantikan Supervisor, Kabag IT, Kadiv IT, atau Direksi yang sedang tidak tersedia.
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
- Laravel `^12.72.0`
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

Hasil verifikasi terbaru:

- Frontend build berhasil.
- `npm audit --omit=dev` tidak menemukan vulnerability production dependency.
- Fresh migration melalui test database berhasil.
- `120` automated test lulus dengan `474` assertion.
- Test mencakup mode lokal, larangan mock di production, JWT RS256, otorisasi chat, dan workflow tiket.

Release tidak boleh dilakukan sebelum fresh migration dan seluruh test yang relevan lulus.

## Konfigurasi autentikasi dan LOA

Konfigurasi berada pada `.env` dan dibaca melalui `config/services.php`:

```env
SSO_DRIVER=local
LOA_DRIVER=local
REALTIME_DRIVER=polling
```

Driver yang tersedia:

- `local`: akun, password, dan approval LOA diproses di aplikasi Helpdesk.
- `mock`: untuk development/testing saja.
- `api` atau `enterprise`: memakai API eksternal.

Driver dipilih secara eksplisit. Driver tidak dikenal menghasilkan error dan driver mock ditolak pada environment production.

Rancangan perpindahan local-to-API dijelaskan di [Dokumentasi Integrasi](docs/INTEGRATION.md).
Dokumen yang dapat diberikan kepada tim pembuat API eksternal tersedia di [Kontrak API Eksternal](docs/KONTRAK_API_EKSTERNAL.md).

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

Refactor yang sudah selesai:

- Fresh migration dan seluruh automated test lulus.
- Autentikasi lokal dan LOA lokal tersedia.
- Pemilihan driver bersifat eksplisit dan fail-safe.
- Mock ditolak di production.
- Otorisasi chat bertiket diperketat.
- JWT API diverifikasi dengan RS256 dan claim yang dikonfigurasi.
- Realtime default memakai polling; WebSocket bersifat opsional.
- Email status tiket menggunakan queue.
- Lonceng memakai riwayat notifikasi database yang konsisten pada desktop dan mobile.
- Delegasi approval memiliki periode aktif, cakupan kewenangan, penerima pengganti, dan pencatatan pembuat delegasi.

Pekerjaan operasional sebelum go-live:

1. Menyiapkan staging cPanel, database, HTTPS, SMTP, queue, dan cron.
2. Mengganti credential admin awal dengan nilai unik dan kuat.
3. Menjalankan UAT seluruh role dan workflow Minor/Major.
4. Menguji backup serta restore database dan attachment.
5. Menjalankan smoke test dan monitoring setelah deployment.

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
- [Kontrak API untuk tim aplikasi eksternal](docs/KONTRAK_API_EKSTERNAL.md)
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
