# Panduan Deployment cPanel

Dokumen ini menjelaskan deployment awal NALA IT Helpdesk pada cPanel menggunakan MySQL/MariaDB, autentikasi lokal, LOA lokal, dan realtime polling. Lakukan deployment pertama di staging, jalankan UAT, lalu promosikan release yang sama ke production.

## 1. Kebutuhan server

Konfirmasikan hal berikut kepada administrator cPanel:

- PHP 8.2 atau lebih baru yang didukung Laravel 12, untuk web dan CLI.
- Extension PHP: BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PDO, PDO MySQL, Tokenizer, dan XML.
- Terminal/SSH dan Composer 2, atau kemampuan mengunggah release yang sudah memuat `vendor/` dan menjalankan perintah Artisan melalui menu Cron Jobs.
- MySQL atau MariaDB.
- Kemampuan mengatur document root subdomain.
- Cron Jobs setiap menit atau process manager untuk queue worker.
- SMTP kantor, domain/subdomain, dan sertifikat HTTPS.
- Kapasitas upload, memory, execution time, dan storage yang sesuai.
- Prosedur backup dan retensi database serta attachment.

## 2. Struktur direktori dan document root

Simpan seluruh source Laravel, termasuk `.env`, `vendor`, dan `storage`, di luar `public_html`:

```text
/home/ACCOUNT/apps/ticketkantorlaravel/  source Laravel
```

Arahkan document root subdomain langsung ke folder `public` aplikasi jika pengaturan cPanel mengizinkannya:

```text
/home/ACCOUNT/apps/ticketkantorlaravel/public
```

Sebagian penyedia cPanel membatasi document root agar tetap berada di bawah `public_html`. Jika demikian, gunakan document root khusus subdomain, misalnya `/home/ACCOUNT/public_html/helpdesk`, lalu **salin hanya isi folder `public/`** ke sana. Pada salinan `index.php` di document root tersebut, arahkan `$basePath` ke lokasi source yang berada di luar `public_html`:

```php
$basePath = '/home/ACCOUNT/apps/ticketkantorlaravel';
```

Pastikan file `public/build` ikut disalin dan PHP dapat membaca source aplikasi. Ulangi penyalinan aset dan `index.php` yang sudah disesuaikan pada setiap release. Jangan menaruh seluruh repository di `public_html`, mengarahkan document root ke root repository, atau membuat symlink dari web root ke `storage/app/private`. File `.env`, `vendor`, `storage`, `database`, `routes`, dan source aplikasi tidak boleh dapat diakses langsung melalui web.

## 3. Membuat database MySQL

Melalui menu **MySQL Databases** di cPanel:

1. Buat database kosong, misalnya `ACCOUNT_nala`.
2. Buat user database khusus aplikasi, misalnya `ACCOUNT_nalauser`.
3. Tambahkan user tersebut ke database.
4. Berikan **ALL PRIVILEGES** untuk database aplikasi.
5. Simpan nama database, username, password, host, dan port yang diberikan cPanel.

Tidak perlu membuat tabel secara manual. Laravel akan membuat seluruh tabel melalui migration.

## 4. Mengambil source dan memasang dependency

Clone repository melalui Terminal/SSH atau upload release aplikasi:

```bash
cd /home/ACCOUNT/apps
git clone https://github.com/suppfaiz/ticketkantorlaravel.git
cd ticketkantorlaravel
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
cp .env.cpanel.example .env
```

Build frontend sebaiknya dilakukan pada mesin development/build:

```bash
npm ci
npm run build
```

Pastikan hasil `public/build` disertakan dalam release. Server production tidak perlu menjalankan Vite development server.

## 5. Konfigurasi `.env`

Edit `.env` dan sesuaikan seluruh nilai contoh. Konfigurasi minimum deployment awal:

```env
APP_NAME="NALA IT Helpdesk"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://helpdesk.domainkantor.com
LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14
TRUSTED_PROXIES=
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
FORCE_HTTPS=true

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ACCOUNT_nala
DB_USERNAME=ACCOUNT_nalauser
DB_PASSWORD="PASSWORD_DATABASE_YANG_KUAT"
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

SSO_DRIVER=local
LOA_DRIVER=local
REALTIME_DRIVER=polling
REALTIME_POLL_INTERVAL_MS=5000

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

Biarkan `TRUSTED_PROXIES` kosong jika aplikasi diakses langsung dari web server cPanel. Jika ada reverse proxy, isi hanya alamat IP proxy tersebut (pisahkan dengan koma bila lebih dari satu); jangan isi `*`. Pastikan `APP_URL` memakai domain resmi aplikasi.

Gunakan nama database dan username lengkap dari cPanel; keduanya biasanya memiliki prefix nama akun. Host dapat berupa `localhost` atau nilai khusus dari penyedia hosting.

Jangan menggunakan driver `mock` pada production. Aplikasi akan menolak konfigurasi tersebut.

## 6. Konfigurasi admin pertama

Sebelum menjalankan seeder, isi akun admin awal pada `.env`:

```env
INITIAL_ADMIN_EMAIL=admin@domainkantor.com
INITIAL_ADMIN_PASSWORD="PASSWORD_UNIK_MINIMAL_12_KARAKTER"
```

Seeder production hanya membuat master data dasar dan satu administrator; akun demonstrasi tidak dibuat. Setelah seeding berhasil dan login admin sudah diuji, kosongkan atau hapus `INITIAL_ADMIN_PASSWORD` dari `.env`. Jika `ProductionSeeder` perlu dijalankan kembali, nilai tersebut harus diisi sementara.

## 7. Konfigurasi SMTP

Contoh koneksi SMTP implicit TLS pada port 465:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=mail.domainkantor.com
MAIL_PORT=465
MAIL_USERNAME=helpdesk@domainkantor.com
MAIL_PASSWORD="PASSWORD_EMAIL"
MAIL_FROM_ADDRESS=helpdesk@domainkantor.com
MAIL_FROM_NAME="${APP_NAME}"
```

Untuk SMTP port 587/STARTTLS, ikuti parameter yang diberikan administrator email; umumnya gunakan `MAIL_SCHEME=smtp`. Uji pengiriman setelah queue worker aktif.

## 8. Inisialisasi aplikasi

Jalankan perintah berikut dari root aplikasi:

```bash
php artisan key:generate --force
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
php artisan config:cache
php artisan view:cache
```

Migration membuat tabel secara otomatis. Jangan menjalankan `php artisan migrate:fresh` pada production karena perintah tersebut menghapus seluruh tabel dan data.

### Jika hanya memiliki akses UI cPanel

Anda **tidak perlu membuat tabel satu per satu** di phpMyAdmin. Sebelum menjalankan migration:

1. Siapkan release di komputer lokal dengan `vendor/` yang dibangun untuk versi PHP server dan `public/build`; unggah melalui File Manager.
2. Buat database kosong beserta user-nya melalui **MySQL Databases**, lalu isi kredensial database pada `.env` melalui File Manager.
3. Untuk instalasi pertama, buat `APP_KEY` unik pada komputer lokal dengan `php artisan key:generate --show`, lalu salin hasilnya ke `APP_KEY` pada `.env` cPanel. Jangan memakai key dari development atau membuat key baru saat aplikasi production sudah memiliki data terenkripsi.

Setelah file aplikasi dan `.env` siap, gunakan **Advanced → Cron Jobs** untuk menjalankan perintah Artisan berikut **satu per satu sesuai urutan**. Pilih waktu beberapa menit ke depan sesuai waktu server pada cPanel, isi kolom **Command** dengan path PHP CLI dan path aplikasi yang sesuai, lalu hapus cron tersebut segera setelah hasilnya diperiksa. Cron Jobs tidak menyediakan tombol "jalankan sekali", sehingga penghapusan job setelah eksekusi adalah bagian dari prosedur.

| Urutan | Perintah Artisan | Periksa setelah berjalan |
| --- | --- | --- |
| 1 | `optimize:clear` | Log tidak berisi error. |
| 2 | `migrate --force` | Tabel `migrations` dan tabel aplikasi muncul di phpMyAdmin. |
| 3 | `db:seed --class=ProductionSeeder --force` | Akun admin awal dan master data muncul; `INITIAL_ADMIN_EMAIL` dan `INITIAL_ADMIN_PASSWORD` sudah diisi lebih dahulu. |
| 4 | `config:cache` | Log tidak berisi error dan aplikasi tetap membaca konfigurasi production. |
| 5 | `view:cache` | Log tidak berisi error dan halaman aplikasi terbuka normal. |

Contoh isi kolom **Command** untuk langkah migration (ganti `ACCOUNT` dan `/PATH/KE/PHP` sesuai akun hosting):

```text
/PATH/KE/PHP /home/ACCOUNT/apps/ticketkantorlaravel/artisan migrate --force > /home/ACCOUNT/apps/ticketkantorlaravel/storage/logs/migrate-once.log 2>&1
```

Untuk langkah lain, ganti bagian `migrate --force` dan gunakan nama log berbeda, misalnya `seed-once.log`. Lihat log melalui **File Manager** sebelum melanjutkan ke langkah berikutnya. Gunakan binary PHP CLI dengan versi yang sama seperti website; minta path-nya kepada penyedia hosting jika belum diketahui. Setelah login admin pertama berhasil, hapus `INITIAL_ADMIN_PASSWORD` dari `.env` sesuai bagian konfigurasi di atas. Pada pembaruan aplikasi berikutnya, jangan ulangi pembuatan `APP_KEY` atau seeding admin awal; setelah backup data, jalankan lagi langkah 1, 2, 4, dan 5.

Jika menu Cron Jobs dinonaktifkan, minta administrator hosting menjalankan perintah Artisan tersebut. Impor SQL via phpMyAdmin hanya menjadi opsi terakhir jika release SQL dibuat khusus dari versi aplikasi dan mencakup riwayat tabel `migrations`; jangan menyusun tabel manual karena mudah berbeda dari migration berikutnya.

Lampiran tiket disimpan di `storage/app/private/tickets` dan notulensi di `storage/app/private/meeting_minutes`. Keduanya diunduh melalui controller yang memeriksa hak akses. Alur awal tidak memakai disk `public`, sehingga `storage:link` tidak diperlukan.

`route:cache` belum digunakan karena route aplikasi masih memiliki Closure. Pindahkan Closure ke controller sebelum mengaktifkannya.

## 9. Permission

Web server harus dapat menulis ke:

```text
storage/
bootstrap/cache/
```

Gunakan permission paling terbatas yang masih berfungsi. Contoh umum:

```bash
chmod -R 775 storage bootstrap/cache
```

Kepemilikan file harus sesuai user web server/cPanel. Hindari permission `777`.

## 10. Queue dan scheduler

Email notifikasi dijalankan melalui database queue. Jika cPanel tidak menyediakan daemon permanen, tambahkan Cron Jobs berikut.

Queue worker setiap menit:

```cron
* * * * * /usr/local/bin/php /home/ACCOUNT/apps/ticketkantorlaravel/artisan queue:work --stop-when-empty --tries=3 --timeout=60 --quiet >> /home/ACCOUNT/apps/ticketkantorlaravel/storage/logs/queue-cron.log 2>&1
```

Scheduler Laravel:

```cron
* * * * * /usr/local/bin/php /home/ACCOUNT/apps/ticketkantorlaravel/artisan schedule:run --quiet >> /home/ACCOUNT/apps/ticketkantorlaravel/storage/logs/scheduler-cron.log 2>&1
```

Sesuaikan lokasi binary PHP dan aplikasi. Pastikan Cron Jobs menggunakan PHP yang sama dengan website. Pantau `storage/logs`, atur rotasi untuk log cron, dan periksa `php artisan queue:failed`. Jika cPanel menyediakan Process Manager/Supervisor, jalankan `queue:work` sebagai proses permanen dan restart worker setiap kali melakukan deployment.

## 11. Realtime chat

Deployment awal menggunakan:

```env
REALTIME_DRIVER=polling
REALTIME_POLL_INTERVAL_MS=5000
```

Polling tidak memerlukan Node.js permanen, port WebSocket, atau reverse proxy tambahan sehingga lebih sesuai untuk cPanel. WebSocket hanya akan digunakan jika `REALTIME_DRIVER=websocket` dan `WEBSOCKET_URL` dikonfigurasi secara eksplisit.

## 12. Data organisasi setelah login

Login sebagai administrator, lalu lengkapi melalui menu administrasi:

1. Departemen/unit kerja.
2. Kategori tiket.
3. Pengguna dan role: requester, supervisor, it_head, kadiv_it, direksi, it_developer, dan admin.
4. Hubungan setiap requester dengan supervisor yang benar.
5. Status aktif pengguna.
6. Delegasi approval untuk pejabat yang sedang cuti atau tidak tersedia, jika diperlukan.

Requester tanpa supervisor tidak akan dipasangkan secara otomatis dan tidak dapat mengikuti alur approval dengan benar.

## 13. Checklist staging dan UAT

- [ ] `/up` dan halaman login dapat diakses melalui HTTPS.
- [ ] Versi PHP web dan CLI sesuai, serta `composer check-platform-reqs --no-dev` lulus.
- [ ] Respons `/login` memiliki `Content-Security-Policy`, `X-Frame-Options: SAMEORIGIN`, dan `X-Content-Type-Options: nosniff`.
- [ ] Aset `/build/assets/*.css` memiliki `X-Content-Type-Options: nosniff`; pastikan aturan `public/.htaccess` diproses oleh Apache/LiteSpeed (`mod_headers`).
- [ ] Respons tidak mengungkap `X-Powered-By`; jika header masih muncul, nonaktifkan `expose_php` pada konfigurasi PHP hosting.
- [ ] Fresh migration berhasil pada database kosong.
- [ ] `APP_ENV=production` dan `APP_DEBUG=false`.
- [ ] Source serta `.env` tidak dapat diakses dari web.
- [ ] Document root hanya berisi file `public/`; source dan `storage/app/private` berada di luar web root.
- [ ] Coba akses `/.env`, `/storage/app/private/...`, dan lokasi source melalui URL: tidak boleh mengembalikan isi file. Uji dengan path lampiran nyata dari staging, bukan hanya path yang tidak ada.
- [ ] Unduhan lampiran tanpa login atau dengan akun yang tidak berhak ditolak; akun yang berhak tetap dapat mengunduhnya.
- [ ] Login/logout lokal berhasil.
- [ ] Seluruh role dan hubungan requester–supervisor sudah benar.
- [ ] Lonceng mencatat notifikasi baru dan dapat menandainya sebagai dibaca.
- [ ] Delegasi aktif menerima antrean dan dapat mengambil keputusan atas nama pejabat asal.
- [ ] Delegasi kedaluwarsa tidak lagi dapat mengambil keputusan.
- [ ] Tiket Minor berhasil dijalankan sampai selesai.
- [ ] Tiket Major berhasil dijalankan sampai selesai.
- [ ] Upload dan download attachment berhasil.
- [ ] Diskusi tiket, chat, audit trail, dan export CSV berhasil.
- [ ] SMTP dan email melalui queue berhasil.
- [ ] Cron queue dan scheduler berjalan tanpa failed job.
- [ ] `composer audit --no-dev` tidak melaporkan advisory pada dependency production.
- [ ] Backup database dan attachment berhasil direstore.
- [ ] Log aplikasi tidak dapat diakses dari web.
- [ ] Prosedur rollback tersedia.

Header keamanan pada halaman dinamis juga dipasang oleh middleware Laravel. Kebijakan CSP awal membatasi embedding halaman, objek, base URL, dan tujuan formulir; kebijakan ini belum membatasi skrip inline karena interaksi Alpine dan editor masih memerlukannya. Aset statis saat `php artisan serve` tidak melewati `.htaccess`, jadi verifikasi header aset dilakukan pada staging cPanel.

Jalankan automated test dan build sebelum release:

```bash
php artisan test
npm run build
composer audit --no-dev
```

## 14. Deployment update berikutnya

Sebelum update production, buat backup database dan attachment. Kemudian:

```bash
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan view:cache
php artisan queue:restart
```

Jangan menjalankan seeder pada setiap deployment kecuali memang ada perubahan master data yang harus diterapkan.

Jika update gagal setelah migration atau perubahan data, hentikan cron queue/scheduler sementara, pulihkan **source release sebelumnya bersama backup database dan `storage/app/private` dari waktu yang sama**, lalu jalankan `php artisan optimize:clear`, `php artisan config:cache`, `php artisan view:cache`, dan `php artisan queue:restart`. Uji login dan satu tiket sebelum mengaktifkan cron kembali. Mengembalikan source saja tidak cukup bila struktur database sudah berubah.

Alur release yang disarankan:

```text
Local development
  -> Automated test dan build
  -> Staging cPanel
  -> UAT seluruh role
  -> Backup
  -> Production
  -> Smoke test
  -> Monitoring
```

Setelah aplikasi lokal stabil dan API eksternal tersedia, ikuti [Dokumentasi Integrasi](INTEGRATION.md) untuk mengganti driver SSO/LOA dari `local` ke `api`.
