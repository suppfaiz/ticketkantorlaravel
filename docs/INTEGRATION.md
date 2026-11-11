# Rancangan Integrasi SSO dan LOA

Deployment pertama ditargetkan memakai akun dan approval lokal. Setelah aplikasi eksternal siap, integrasi dapat dialihkan melalui konfigurasi tanpa mengubah workflow tiket.

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

- `SsoServiceInterface`, `MockSsoService`, dan `EnterpriseSsoService`.
- `LoaServiceInterface`, `MockLoaService`, dan `EnterpriseLoaService`.
- Webhook `POST /api/v1/webhooks/loa-decision`.

Kekurangan yang masih harus direfactor:

- Belum ada driver SSO lokal yang eksplisit.
- Belum ada `LocalLoaService`.
- Nilai driver yang tidak dikenali masih jatuh ke mock.
- Mock belum dilarang pada production.
- JWT SSO belum diverifikasi secara kriptografis secara lengkap.

## 3. Kontrak SSO minimum

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

### Mengirim approval

```http
POST {LOA_API_URL}/approvals
Authorization: Bearer {LOA_API_KEY}
Idempotency-Key: {stable-key}
Content-Type: application/json
```

Payload minimal membawa nomor/ID tiket, level approval, identitas pemohon, judul, ringkasan, callback URL, dan idempotency key.

### Memeriksa status

```http
GET {LOA_API_URL}/approvals/{external_reference_id}
Authorization: Bearer {LOA_API_KEY}
```

### Menerima keputusan

```http
POST /api/v1/webhooks/loa-decision
X-Signature-SHA256: {hmac}
```

Webhook harus memiliki event ID atau idempotency key agar pengiriman ulang tidak menghasilkan transisi status ganda.

## 6. Kepemilikan data

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

## 7. Strategi perpindahan

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

## 8. Prinsip fail-safe

- Driver tidak dikenal harus menghasilkan error, bukan fallback ke mock.
- Mock harus ditolak pada production.
- Gangguan SSO tidak boleh membuat authentication bypass.
- Gangguan LOA tidak boleh menghasilkan approval otomatis.
- Request keluar harus memiliki timeout dan retry terbatas.
- Keputusan webhook harus idempotent.
- Secret tidak boleh ditulis ke log atau audit trail.
