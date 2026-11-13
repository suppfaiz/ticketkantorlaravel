# Kontrak API untuk Tim Penyedia Aplikasi Eksternal

**Status:** draf handoff, versi 0.1. **Target:** API autentikasi pegawai dan API persetujuan LOA yang akan dipakai aplikasi NALA IT Helpdesk. Dokumen ini menjelaskan bentuk yang diharapkan adapter Helpdesk saat ini dan hal yang harus disepakati bersama. Contoh domain dan identitas di bawah hanya ilustrasi.

> Mohon tim penyedia API meninjau kontrak ini, menandai bagian yang berbeda dari API yang sudah ada, lalu memberikan spesifikasi final dan akses sandbox. Jangan mengaktifkan `SSO_DRIVER=api` atau `LOA_DRIVER=api` sebelum butir pada bagian **Kesiapan integrasi** selesai.

## 1. Pembagian tanggung jawab

| Komponen | Disediakan oleh | Dipanggil oleh |
|---|---|---|
| `POST /auth/login` | Aplikasi identitas/SSO | Helpdesk saat pegawai login |
| `POST /approvals` | Aplikasi LOA | Helpdesk saat persetujuan diperlukan |
| `GET /approvals/{reference_id}` | Aplikasi LOA | Helpdesk saat sinkronisasi status manual |
| `POST /api/v1/webhooks/loa-decision` | Helpdesk | Aplikasi LOA saat keputusan dibuat |

Semua komunikasi melalui HTTPS. API SSO dan LOA boleh berada pada aplikasi/server yang sama atau berbeda; URL dasarnya dikonfigurasi terpisah. Sistem Helpdesk tetap menyimpan tiket, status pekerjaan, dan audit. Aplikasi eksternal menjadi sumber autentikasi pegawai dan keputusan LOA setelah integrasi diaktifkan.

```text
Pegawai -> formulir login Helpdesk -> API SSO -> JWT + data pegawai -> sesi Helpdesk
Tiket  -> Helpdesk -> POST approval ke API LOA -> reference_id
Keputusan di aplikasi LOA -> webhook Helpdesk -> status tiket berubah
```

## 2. Kontrak autentikasi pegawai

### 2.1 Request login

```http
POST {SSO_API_URL}/auth/login
Content-Type: application/json
Accept: application/json
X-Client-Id: helpdesk
```

```json
{
  "username": "pegawai@kantor.co.id",
  "password": "contoh-password"
}
```

Form Helpdesk saat ini mewajibkan **alamat email** dan mengirimkannya sebagai `username`. API tidak perlu membuka endpoint login milik Helpdesk; Helpdesk yang menjadi client. `X-Client-Id` berasal dari `SSO_CLIENT_ID`. `SSO_CLIENT_SECRET` memang tersedia sebagai konfigurasi, tetapi **belum dikirim oleh adapter saat ini**. Cara autentikasi aplikasi ke aplikasi, misalnya client secret dalam header, harus disepakati dan ditambahkan ke adapter sebelum produksi.

### 2.2 Respons sukses

```http
HTTP/1.1 200 OK
Content-Type: application/json
```

```json
{
  "token": "<JWT-RS256-ditandatangani>",
  "user": {
    "sso_id": "EMP-00123",
    "name": "Nama Pegawai",
    "email": "pegawai@kantor.co.id",
    "department": "Operasional",
    "role": "requester"
  }
}
```

| Field | Ketentuan |
|---|---|
| `token` | Wajib. JWT tiga bagian dengan signature RS256 yang dapat diverifikasi memakai public key yang diberikan penyedia. |
| `user.sso_id` | Wajib untuk kontrak final. ID pegawai permanen, unik, tidak berubah saat email/nama berubah. |
| `user.email` | Wajib dan unik sesuai kebijakan identitas. Saat ini dipakai juga untuk pencocokan akun lokal. |
| `user.name` | Nama tampilan pegawai. |
| `user.department` | Nama/kode departemen; format dan sumber kebenarannya perlu disepakati. |
| `user.role` | Jangan mengirim role Helpdesk yang bebas ditentukan pengguna. Mapping role disepakati terpisah; default aman adalah `requester`. |

JWT minimal memiliki `sub` (sama dengan `sso_id`), `iss`, `aud`, `iat`, dan `exp` berupa Unix timestamp. `nbf` boleh ditambahkan. Berikan **public key PEM** yang sesuai dengan private key penandatangan, nilai `iss`, nilai `aud`, algoritma, masa berlaku token, serta prosedur rotasi kunci. Adapter saat ini menerima public key statis; endpoint JWKS/rotasi otomatis belum diterapkan.

### 2.3 Respons gagal dan kasus akun

Usulan respons error untuk disepakati:

```json
{
  "code": "INVALID_CREDENTIALS",
  "message": "Email atau kata sandi tidak sesuai"
}
```

Sepakati kode untuk kredensial salah (`401`), akun nonaktif (`403`), permintaan tidak valid (`422`), terlalu banyak percobaan (`429`), dan gangguan layanan (`5xx`), termasuk batas waktu dan rate limit. **Adapter Helpdesk saat ini memperlakukan semua respons non-2xx sebagai kegagalan kredensial**, sehingga pemetaan error yang lebih tepat adalah pekerjaan integrasi di sisi Helpdesk. Password tidak boleh dikirim balik atau dicatat di log. Tentukan apakah perubahan nama, email, departemen, status aktif, dan atasan diteruskan saat login atau melalui endpoint sinkronisasi terpisah.

## 3. Kontrak API persetujuan LOA

### 3.1 Membuat permintaan approval

```http
POST {LOA_API_URL}/approvals
Content-Type: application/json
Accept: application/json
X-Api-Key: <LOA_API_KEY>
Idempotency-Key: <64-karakter-hex>
```

Contoh payload sesuai adapter Helpdesk saat ini:

```json
{
  "ticket_id": 123,
  "ticket_number": "TICK-20261112-0001",
  "title": "Permintaan akses aplikasi",
  "classification": "Major",
  "approval_level": "kadiv_it_ops",
  "approver": {
    "role": "kadiv_it",
    "title": "LOA Kadiv IT & Operasional"
  },
  "callback_url": "https://helpdesk.kantor.co.id/api/v1/webhooks/loa-decision"
}
```

`ticket_id` adalah ID internal Helpdesk; untuk tampilan manusia gunakan `ticket_number`. `Idempotency-Key` stabil untuk kombinasi tiket dan level persetujuan. Penyedia harus mengembalikan **approval yang sama** jika key yang sama dikirim ulang, bukan membuat approval kedua. Simpan key tersebut untuk dikirim balik dalam webhook. Bentuk `approver` tidak selalu sama: untuk `supervisor` saat ini berisi `supervisor_id`; untuk `kadiv_it_ops` dan `direksi` berisi `role` serta `title`. ID supervisor ini adalah ID lokal Helpdesk, sehingga bila API LOA memakai ID pegawai eksternal diperlukan mapping di sisi Helpdesk.

| Field request | Tipe | Catatan |
|---|---|---|
| `ticket_id` | integer | ID lokal Helpdesk; gunakan bersama `ticket_number`. |
| `ticket_number` | string | Nomor tiket untuk tampilan dan pencarian. |
| `title` | string | Judul tiket. |
| `classification` | string/null | `Minor`, `Major`, atau belum terisi pada tahap atasan. |
| `approval_level` | string | Salah satu nilai pada tabel di bawah. |
| `approver` | object | Data petunjuk penerima; format berbeda per level. |
| `callback_url` | URL HTTPS | Alamat tujuan keputusan dari aplikasi LOA. |

Nilai `approval_level` yang dipakai Helpdesk:

| Nilai | Peran/kejadian |
|---|---|
| `supervisor` | Persetujuan atasan langsung setelah tiket diajukan |
| `kadiv_it_ops` | Persetujuan Kadiv untuk tiket Major |
| `direksi` | Persetujuan Direksi setelah Kadiv menyetujui tiket Major |

Respons sukses yang diminta:

```http
HTTP/1.1 202 Accepted
Content-Type: application/json
```

```json
{
  "reference_id": "LOA-2026-000123",
  "decision": "waiting"
}
```

Adapter menerima respons 2xx dan membaca `reference_id` atau `loa_id`. **Salah satunya wajib ada pada kontrak final** agar status dapat diperiksa. Jika key yang sama dikirim ulang, kembalikan `reference_id` yang sama. Sepakati respons untuk input salah (`422`), autentikasi gagal (`401`/`403`), konflik key dengan isi berbeda (`409`), limit (`429`), dan gangguan server (`5xx`). Timeout request Helpdesk saat ini 5 detik; pengiriman ulang belum menggunakan antrean otomatis.

### 3.2 Memeriksa status approval

```http
GET {LOA_API_URL}/approvals/LOA-2026-000123
Accept: application/json
X-Api-Key: <LOA_API_KEY>
```

```json
{
  "reference_id": "LOA-2026-000123",
  "decision": "approved",
  "notes": "Disetujui sesuai kebutuhan"
}
```

Nilai `decision`: `waiting`, `approved`, `rejected`, dan `revision`. API boleh menambahkan waktu keputusan, identitas pengambil keputusan, atau kode alasan. Saat ini tombol sinkronisasi Helpdesk memperbarui **catatan transaksi LOA**, tetapi perubahan **status tiket** diproses melalui webhook. Karena itu webhook tetap wajib dikirim untuk setiap keputusan final.

### 3.3 Mengirim keputusan ke Helpdesk

```http
POST https://helpdesk.kantor.co.id/api/v1/webhooks/loa-decision
Content-Type: application/json
X-Signature-SHA256: <hmac-sha256-hex>
```

```json
{
  "idempotency_key": "<key-dari-header-request-awal>",
  "external_reference_id": "LOA-2026-000123",
  "approval_level": "kadiv_it_ops",
  "decision": "approved",
  "notes": "Disetujui Kadiv",
  "event_id": "evt-000123",
  "decided_at": "2026-11-12T10:30:00+07:00"
}
```

Field yang **dipakai kode saat ini** ialah `idempotency_key`, `decision`, dan `notes`. Field `external_reference_id`, `approval_level`, `event_id`, dan `decided_at` direkomendasikan untuk kontrak final, tetapi saat ini belum divalidasi penuh oleh webhook Helpdesk. Signature memakai HMAC-SHA256 atas byte **raw body** JSON dengan secret bersama `LOA_WEBHOOK_SECRET`, hasilnya hex lowercase. Jangan memasukkan signature ke dalam body yang ditandatangani. Penyedia sebaiknya menyimpan body persis yang dikirim saat retry agar tanda tangan konsisten.

Respons sukses saat ini berbentuk:

```json
{
  "status": "success",
  "ticket_id": 123,
  "new_status": "Menunggu LOA Direksi"
}
```

Header signature hilang menghasilkan `401`; signature salah menghasilkan `403` di middleware; transaksi tidak ditemukan atau verifikasi service gagal menghasilkan `400`. Ulangi pengiriman hanya untuk timeout/`5xx` sesuai kebijakan retry yang disepakati. Keputusan yang sama tidak boleh mengubah tiket dua kali. **Ada dua pemeriksaan signature pada kode Helpdesk saat ini**, satu terhadap raw body dan satu terhadap JSON yang di-encode ulang; keduanya harus disatukan sebelum UAT webhook agar JSON valid dengan format berbeda tidak ditolak.

### 3.4 Dampak keputusan pada alur tiket

| Level | `approved` | `rejected` | `revision` |
|---|---|---|---|
| `supervisor` | Menunggu Kepala Bagian IT | Ditolak Atasan | Revisi Pemohon |
| `kadiv_it_ops` | Menunggu LOA Direksi dan dibuat approval Direksi | Ditolak Kadiv IT | Belum ditangani pada level ini |
| `direksi` | Menunggu Rapat Major | Ditolak Direksi | Belum ditangani pada level ini |

Untuk level Kadiv/Direksi, bila penyedia membutuhkan keputusan `revision`, tentukan tujuan revisi bersama sebelum implementasi. Tidak boleh mengasumsikan semua keputusan punya transisi yang sama.

## 4. Data organisasi dan identitas approver

API login dan API LOA perlu memakai pengenal pegawai yang stabil. Saat ini Helpdesk punya `sso_id` pada tabel pengguna, tetapi `supervisor_id` dan role internalnya disimpan lokal. Sepakati salah satu mekanisme berikut sebelum integrasi: API identitas mengirim `supervisor_sso_id`/kode departemen yang stabil, atau Helpdesk memelihara mapping lokal yang dikelola admin. Tetapkan siapa sumber kebenaran untuk penonaktifan pegawai, perubahan atasan, perpindahan divisi, dan delegasi saat cuti. Jangan memakai nama atau email sebagai satu-satunya pengenal approval.

## 5. Kesiapan integrasi yang perlu disepakati

### Disediakan tim aplikasi eksternal

- Base URL sandbox/production, OpenAPI/Swagger atau Postman Collection, dan versi API.
- Contoh request/response sukses dan gagal untuk semua endpoint di atas, termasuk kode HTTP.
- Akun uji untuk pegawai aktif/nonaktif, atasan, Kadiv, Direksi, dan kasus perubahan email/divisi.
- Public key JWT, `iss`, `aud`, masa berlaku, jadwal rotasi kunci, serta cara autentikasi antar aplikasi.
- API key LOA, secret webhook, aturan idempotensi, kebijakan retry, dan akses jaringan dari/ke server cPanel.
- Kesepakatan mapping ID pegawai, departemen, atasan, role, dan delegasi approval.

### Diselesaikan di sisi Helpdesk sebelum mengganti driver

- Menyamakan satu metode verifikasi HMAC webhook dan menguji JSON dengan variasi whitespace/urutan field.
- Menyepakati lalu menerapkan autentikasi client SSO (`SSO_CLIENT_SECRET` sekarang belum dipakai).
- Memvalidasi kecocokan identitas JWT dengan `user.sso_id`, serta membatasi mapping role dan pencocokan email agar akun tidak tertukar.
- Memastikan sinkronisasi akun nonaktif, departemen/atasan, dan retry LOA sesuai kontrak final.
- Menjaga payload retry LOA tetap identik untuk key idempotensi yang sama; adapter saat ini dapat mengirim `approver` kosong saat retry.
- Menguji callback berulang, urutan keputusan tidak sesuai, `revision` tiap level, serta kegagalan API tanpa approval otomatis.

## 6. Skenario UAT minimum

1. Login valid membuat sesi Helpdesk untuk pegawai yang tepat; password salah, akun nonaktif, JWT salah signature/expired, dan API timeout ditolak.
2. Perubahan email pegawai tetap dipetakan melalui `sso_id`; tidak membuat akun duplikat atau mengambil akun orang lain.
3. Tiket baru mengirim approval `supervisor`; key yang sama dikirim ulang menghasilkan satu approval dan satu `reference_id`.
4. LOA Major berjalan Kadiv lalu Direksi; setiap keputusan mengubah status tiket sesuai tabel di atas.
5. Webhook salah signature, key tidak dikenal, dan callback berulang tidak mengubah tiket secara keliru.
6. Saat API LOA tidak dapat dihubungi, tiket tetap menunggu persetujuan dan dapat dicoba ulang tanpa approval ganda.
7. Sinkronisasi status (`GET`) dan webhook menunjukkan keputusan yang sama pada catatan transaksi dan tiket.

## 7. Aktivasi setelah kontrak final dan UAT lulus

Konfigurasi berada di `.env` server Helpdesk, **bukan** di source code atau dokumen yang di-commit:

```env
SSO_DRIVER=api
SSO_API_URL=https://identitas.kantor.co.id/api/v1
SSO_CLIENT_ID=helpdesk
SSO_CLIENT_SECRET=<secret-dari-penyedia>
SSO_JWT_PUBLIC_KEY=<public-key-yang-sesuai>
SSO_JWT_ISSUER=<issuer-yang-disepakati>
SSO_JWT_AUDIENCE=<audience-helpdesk>

LOA_DRIVER=api
LOA_API_URL=https://loa.kantor.co.id/api/v1
LOA_API_KEY=<api-key-dari-penyedia>
LOA_WEBHOOK_SECRET=<secret-bersama>
```

Setelah mengubah `.env`, jalankan `php artisan config:clear` dan `php artisan config:cache`. Uji di staging terlebih dahulu, lalu pilot pengguna terbatas sebelum production. Selama penyedia API belum siap, tetap gunakan `SSO_DRIVER=local` dan `LOA_DRIVER=local`.

Dokumen sisi Helpdesk yang terkait: [Rancangan Integrasi](INTEGRATION.md) dan [Panduan Deployment cPanel](DEPLOYMENT_CPANEL.md).
