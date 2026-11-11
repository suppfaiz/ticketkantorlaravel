# Panduan Deployment cPanel

Dokumen ini menjelaskan deployment NALA IT Helpdesk pada cPanel kantor. Refactor mode lokal sudah tersedia, tetapi deployment production tetap harus melalui staging dan UAT.

## 1. Informasi dari administrator server

- Versi PHP minimal 8.2.
- Extension PHP: BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PDO, PDO MySQL, Tokenizer, dan XML.
- Kemampuan mengatur document root subdomain.
- Akses Terminal/SSH dan Composer.
- Fitur Cron Jobs.
- Database MySQL/MariaDB dengan credential khusus aplikasi.
- SMTP kantor.
- Domain/subdomain dan sertifikat HTTPS.
- Batas upload, execution time, memory, dan storage.
- Kebijakan backup serta retensi database/attachment.

## 2. Struktur direktori

Paling aman adalah menyimpan source di luar `public_html`:

```text
/home/ACCOUNT/apps/ticketkantorlaravel/  source Laravel
/home/ACCOUNT/public_html/helpdesk/      hanya isi direktori public
```

Pilihan yang lebih sederhana adalah mengarahkan document root subdomain langsung ke:

```text
/home/ACCOUNT/apps/ticketkantorlaravel/public
```

Jangan mengekspos `.env`, `vendor`, `storage`, `database`, `routes`, atau source aplikasi melalui web. Root front controller dan `.htaccess` dalam repository mendukung skenario cPanel, tetapi document root ke direktori `public` tetap menjadi pilihan utama.

## 3. Konfigurasi target deployment awal

Konfigurasi deployment awal:

```env
APP_NAME="NALA IT Helpdesk"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://helpdesk.example.internal
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

SSO_DRIVER=local
LOA_DRIVER=local
REALTIME_DRIVER=polling

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

SESSION_SECURE_COOKIE=true
FORCE_HTTPS=true
```

## 4. Prosedur instalasi target

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan view:cache
```

`route:cache` belum dimasukkan karena route aplikasi saat ini masih memiliki Closure. Pindahkan Closure tersebut ke controller sebelum mengaktifkan route cache.

Build frontend sebaiknya dilakukan pada mesin build/development:

```bash
npm ci
npm run build
```

Upload hasil `public/build` bersama release. Production tidak perlu menjalankan Vite development server.

## 5. Permission

Web server harus dapat menulis ke:

```text
storage/
bootstrap/cache/
```

Gunakan permission paling terbatas yang masih berfungsi. Hindari permission `777`.

## 6. Queue dan scheduler

Email serta komunikasi API sebaiknya diproses melalui database queue. Jika cPanel tidak mendukung daemon permanen, gunakan Cron Jobs.

Contoh queue worker setiap menit:

```cron
* * * * * /usr/local/bin/php /home/ACCOUNT/apps/ticketkantorlaravel/artisan queue:work --stop-when-empty --tries=3 --timeout=60 >> /dev/null 2>&1
```

Contoh scheduler:

```cron
* * * * * /usr/local/bin/php /home/ACCOUNT/apps/ticketkantorlaravel/artisan schedule:run >> /dev/null 2>&1
```

Sesuaikan lokasi PHP dan aplikasi dengan akun cPanel. Pastikan provider hosting mengizinkan cron setiap menit.

## 7. Realtime

Deployment awal disarankan memakai polling karena:

- Tidak memerlukan proses Node.js permanen.
- Tidak memerlukan port WebSocket khusus.
- Lebih mudah dioperasikan pada shared hosting.

Implementasi saat ini masih mencoba `ws://HOST:6001` pada widget chat. Koneksi tersebut dapat diblokir browser saat aplikasi memakai HTTPS. Nonaktifkan dependensi WebSocket sebelum production atau sediakan reverse proxy `wss://` jika infrastruktur mendukungnya.

## 8. Checklist sebelum production

- [ ] Fresh migration berhasil pada database kosong.
- [ ] Seluruh automated test lulus.
- [ ] Frontend production build berhasil.
- [ ] `APP_DEBUG=false`.
- [ ] Driver mock ditolak di production.
- [ ] Admin awal memakai password unik yang kuat.
- [ ] HTTPS dan secure cookie aktif.
- [ ] Login dan logout diuji.
- [ ] Semua role menjalani UAT.
- [ ] Upload dan download attachment diuji.
- [ ] SMTP diuji.
- [ ] Queue dan cron diuji.
- [ ] Backup database dan attachment berhasil direstore.
- [ ] Halaman `/up` dapat dipantau secara internal.
- [ ] Log tidak dapat diakses dari web.
- [ ] Prosedur rollback disiapkan.

## 9. Deployment bertahap

```text
Local development
  -> Automated test
  -> Staging cPanel
  -> UAT seluruh role
  -> Backup
  -> Production
  -> Smoke test
  -> Monitoring
```

Jangan menjalankan migration berisiko di production tanpa backup dan rencana rollback.

## 10. Smoke test

1. Buka `/up` dan halaman login.
2. Login menggunakan akun admin production.
3. Buat satu akun uji untuk setiap role.
4. Jalankan satu tiket Minor sampai selesai.
5. Jalankan satu tiket Major sampai selesai.
6. Uji attachment, diskusi, email, audit trail, dan export CSV.
7. Pastikan cron queue tidak menghasilkan failed job.
8. Periksa `storage/logs` tanpa menyalin informasi sensitif ke kanal publik.
