# Rancangan Integrasi SSO dan LOA

Deployment pertama ditargetkan memakai akun dan approval lokal. Dokumen ini adalah rancangan kontrak dari sisi Helpdesk, bukan dokumentasi final API aplikasi lain. Setelah tim pemilik API menyerahkan spesifikasi dan sandbox, cocokkan endpoint, header, payload, respons, dan keamanan dengan adapter sebelum mengubah driver.

Untuk diserahkan kepada tim pembuat API eksternal, gunakan [Kontrak API Eksternal](KONTRAK_API_EKSTERNAL.md).

## 1. Target arsitektur driver

```text
Authentication
├── local   akun dan password pada database Helpdesk
├── api     autentikasi melalui aplikasi eksternal
└── mock    development/testing saja

LOA
├── local   approval dilakukan pada dashboard Helpdesk
├── api     approval dikirim ke aplikasi eksternal
└── mock    development/testing saja
```

Pemilihan awal:

```env
SSO_DRIVER=local
LOA_DRIVER=local
```

Ketika API siap:

```env
SSO_DRIVER=api
SSO_API_URL=https://aplikasi-lain.example.internal/api/v1
SSO_CLIENT_ID=helpdesk
SSO_CLIENT_SECRET=change-me
SSO_JWT_PUBLIC_KEY="change-me"

LOA_DRIVER=api
LOA_API_URL=https://aplikasi-lain.example.internal/api/v1
LOA_API_KEY=change-me
LOA_WEBHOOK_SECRET=change-me
```

Sesudah mengubah konfigurasi:

```bash
php artisan config:clear
php artisan config:cache
```

## 2. Kondisi implementasi saat ini

Kode saat ini memiliki:

- `SsoServiceInterface`, `LocalSsoService`, `MockSsoService`, dan `EnterpriseSsoService`.
- `LoaServiceInterface`, `LocalLoaService`, `MockLoaService`, dan `EnterpriseLoaService`.
- Webhook `POST /api/v1/webhooks/loa-decision`.
- Pemilihan driver eksplisit dan larangan mock pada production.
- Verifikasi JWT RS256 beserta issuer, audience, expiry, dan not-before.

Pekerjaan yang menunggu kesiapan aplikasi eksternal:

- Finalisasi kontrak endpoint dan payload.
- Penetapan issuer, audience, serta mekanisme rotasi public key/JWKS.
- Penetapan source of truth role, departemen, dan supervisor.
- Sandbox, UAT integrasi, pilot user, dan prosedur rollback.

## 3. Kontrak SSO minimum

Helpdesk saat ini mengirim email dari formulir login sebagai `username` ke API eksternal. Endpoint ini **disediakan aplikasi identitas**, bukan endpoint publik Helpdesk. `SSO_CLIENT_SECRET` sudah ada di konfigurasi, tetapi adapter saat ini belum mengirimnya; mekanisme autentikasi client harus disepakati dan diimplementasikan sebelum integrasi aktif.

### Permintaan login

```http
POST {SSO_API_URL}/auth/login
Content-Type: application/json
X-Client-Id: helpdesk
```

```json
{
  "username": "pegawai@kantor.co.id",
  "password": "secret"
}
```

### Respons minimum

```json
{
  "token": "signed-jwt",
  "user": {
    "sso_id": "EMP-00123",
    "name": "Nama Pegawai",
    "email": "pegawai@kantor.co.id",
    "department": "Keuangan",
    "role": "requester"
  }
}
```

Respons gagal perlu menyebut kode HTTP dan bentuk error yang konsisten; password dan token tidak boleh muncul dalam log. Sepakati juga batas waktu respons, kebijakan rate limit, masa berlaku token, dan apakah pengguna yang belum ada boleh dibuat otomatis di Helpdesk.

Sebelum production, sepakati:

- Claim JWT dan algorithm.
- Issuer dan audience.
- Public key/JWKS rotation.
- Sumber role, departemen, dan supervisor.
- Mekanisme menonaktifkan pegawai.
- Penanganan perubahan email dan duplikasi identitas.

Kunci pencocokan utama harus `sso_id`; email hanya fallback migrasi awal.

## 4. Verifikasi JWT

Driver API wajib memeriksa:

- Signature token.
- Daftar algorithm yang diizinkan.
- `iss`.
- `aud`.
- `exp` dan, bila ada, `nbf`/`iat`.
- Kesesuaian identitas token dengan respons user.

Decode payload tanpa memverifikasi signature tidak boleh dianggap autentikasi yang valid.

## 5. Kontrak LOA minimum

Endpoint `approvals` berikut **disediakan aplikasi LOA**. URL webhook pada bagian "Menerima keputusan" disediakan oleh Helpdesk. Nama endpoint dan field masih harus dikonfirmasi dengan pemilik aplikasi LOA.

### Mengirim approval

```http
POST {LOA_API_URL}/approvals
X-Api-Key: {LOA_API_KEY}
Idempotency-Key: {stable-key}
Content-Type: application/json
```

Payload yang dikirim adapter saat ini:

```json
{
  "ticket_id": 123,
  "ticket_number": "TICK-20261112-0001",
  "title": "Permintaan perubahan sistem",
  "classification": "Major",
  "approval_level": "kadiv_it_ops",
  "approver": {"role": "kadiv_it_ops"},
  "callback_url": "https://helpdesk.example.com/api/v1/webhooks/loa-decision"
}
```

`approval_level` saat ini memakai `supervisor`, `kadiv_it_ops`, atau `direksi`. Kunci idempotensi dikirim di header. Respons sukses harus berisi `reference_id` atau `loa_id` agar status dapat diperiksa. Identitas pemohon, ringkasan, dan bentuk data approver yang dibutuhkan aplikasi LOA perlu disepakati; field tersebut belum seluruhnya dikirim adapter saat ini.

### Memeriksa status

```http
GET {LOA_API_URL}/approvals/{external_reference_id}
X-Api-Key: {LOA_API_KEY}
```

Respons status perlu memuat `decision` (`waiting`, `approved`, `rejected`, atau `revision`) dan `notes` bila ada.

### Menerima keputusan

```http
POST /api/v1/webhooks/loa-decision
X-Signature-SHA256: {hmac}
Content-Type: application/json
```

Payload webhook saat ini harus memuat `idempotency_key`, `decision`, dan opsional `notes`. Middleware memeriksa HMAC-SHA256 atas **raw body JSON** dengan `LOA_WEBHOOK_SECRET`, ditulis sebagai string heksadesimal di header. Service juga menghitung ulang HMAC dari JSON yang sudah diparse; kedua pemeriksaan ini perlu disatukan sebelum mode API diaktifkan agar perbedaan format JSON tidak menolak webhook yang valid. Untuk kontrak final, sepakati pula event ID, format waktu, cara pengiriman ulang, serta respons bila keputusan sudah diproses.

## 6. Dokumen yang perlu diminta dari pemilik API

- Base URL sandbox dan production, daftar endpoint, metode HTTP, serta contoh request/response sukses dan gagal.
- Cara autentikasi antar aplikasi, distribusi secret/public key, rotasi kunci, dan izin akses jaringan dari server cPanel.
- Identitas pegawai yang stabil (`sso_id`), mapping role/departemen/atasan, serta aturan akun nonaktif dan perubahan data pegawai.
- Kode keputusan LOA, level approver, idempotensi, retry, timeout, webhook, dan kontak teknis untuk pengujian.
- OpenAPI/Swagger atau Postman Collection bila tersedia, berikut versi API dan kebijakan perubahan kontrak.

## 7. Kepemilikan data

| Data | Fase awal | Setelah integrasi |
|---|---|---|
| Password | Helpdesk lokal | Tidak disimpan; divalidasi SSO |
| Nama dan email | Helpdesk lokal | SSO/API |
| Department | Helpdesk lokal | Disepakati dengan pemilik API |
| Role Helpdesk | Helpdesk lokal | Tetap lokal kecuali mapping resmi tersedia |
| Supervisor | Helpdesk lokal | API struktur organisasi bila tersedia |
| Status tiket | Helpdesk | Helpdesk |
| Keputusan LOA | Helpdesk lokal | Aplikasi LOA/API |
| Audit trail | Helpdesk | Helpdesk |

Role Helpdesk tidak sebaiknya langsung dipercaya dari string bebas pada API. Gunakan mapping yang disepakati dan default paling terbatas.

## 8. Strategi perpindahan

1. Bekukan dan dokumentasikan kontrak API.
2. Siapkan sandbox aplikasi eksternal.
3. Implementasikan driver `api` di belakang interface.
4. Tambahkan test untuk success, timeout, invalid token, retry, dan duplicate webhook.
5. Isi `sso_id` pengguna lokal melalui mapping terkontrol.
6. Uji pada staging dengan data yang sudah dianonimkan.
7. Jalankan pilot pada beberapa akun.
8. Backup database.
9. Ubah driver melalui `.env`.
10. Cache ulang konfigurasi.
11. Pantau login, dispatch LOA, webhook, retry, dan audit log.
12. Siapkan rollback ke driver lokal.

## 9. Prinsip fail-safe

- Driver tidak dikenal harus menghasilkan error, bukan fallback ke mock.
- Mock harus ditolak pada production.
- Gangguan SSO tidak boleh membuat authentication bypass.
- Gangguan LOA tidak boleh menghasilkan approval otomatis.
- Request keluar harus memiliki timeout dan retry terbatas.
- Keputusan webhook harus idempotent.
- Secret tidak boleh ditulis ke log atau audit trail.
