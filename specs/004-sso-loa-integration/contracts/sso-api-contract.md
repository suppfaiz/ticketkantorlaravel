# SSO JWT API Contract Specification

**Feature**: `004-sso-loa-integration`  
**Date**: 2026-10-28  
**Status**: Completed (Phase 1)  
**Governance**: Aligned with Constitution v3.1.0 (Prinsip III)

---

## 1. Enterprise SSO Login Endpoint

Panggilan HTTP dilakukan dari sisi server (`App\Services\EnterpriseSsoService`) ke server SSO perusahaan.

- **Endpoint**: `POST {SSO_BASE_URL}/api/v1/auth/login`
- **Headers**:
  ```http
  Content-Type: application/json
  Accept: application/json
  X-Client-ID: {SSO_CLIENT_ID}
  ```
- **Request Body Payload**:
  ```json
  {
    "username": "karyawan.it@internal.corp",
    "password": "UserSecretPasswordHere"
  }
  ```

### Response 200 OK (Autentikasi Berhasil):
```json
{
  "status": "success",
  "message": "Authentication successful",
  "data": {
    "token_type": "Bearer",
    "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_in": 7200,
    "user": {
      "id": "sso-usr-98214",
      "name": "Budi Santoso",
      "email": "karyawan.it@internal.corp",
      "department": "Finance & Accounting",
      "role": "requester",
      "supervisor": {
        "id": "sso-usr-98001",
        "name": "Agus Setiawan",
        "email": "agus.setiawan@internal.corp"
      }
    }
  }
}
```

### Response 401 Unauthorized (Kredensial Tidak Sah):
```json
{
  "status": "error",
  "message": "Invalid username or password"
}
```

### Response 503 Service Unavailable (SSO Gangguan):
```json
{
  "status": "error",
  "message": "SSO authentication provider temporarily unavailable"
}
```

---

## 2. Server-Side Token Validation Rules

Setiap token JWT yang diterima wajib divalidasi oleh `App\Services\EnterpriseSsoService` dengan kriteria berikut:
1. **Signature Verification**: Menggunakan public key SSO (`SSO_PUBLIC_KEY` atau jwks endpoint).
2. **Algorithm Verification**: Algoritma token wajib sesuai spesifikasi (RS256 / HS256).
3. **Issuer Check (`iss`)**: Nilai klaim `iss` harus persis sama dengan `{SSO_BASE_URL}`.
4. **Audience Check (`aud`)**: Nilai klaim `aud` harus persis sama dengan `{SSO_CLIENT_ID}`.
5. **Expiration Check (`exp`)**: Token yang telah melewati waktu kedaluwarsa ditolak secara tegas.
6. **Zero Client Storage**: Token JWT disimpan pada sesi server internal; token **TIDAK PERNAH** dikirimkan ke peramban klien untuk disimpan di `localStorage`.

---

## 3. Mock Adapter Contract (`MockSsoService`)

Untuk kebutuhan automated testing dan local development tanpa server SSO live:
- Saat `SSO_DRIVER=mock`, `MockSsoService` menyediakan kredensial mock berstandar untuk seluruh peran:
  - `requester@internal.corp` / `password` -> Role Requester
  - `supervisor@internal.corp` / `password` -> Role Supervisor
  - `kabagit@internal.corp` / `password` -> Role IT Head
  - `kadiv@internal.corp` / `password` -> Role Kadiv IT
  - `direksi@internal.corp` / `password` -> Role Direksi
  - `developer@internal.corp` / `password` -> Role IT Developer
  - `admin@internal.corp` / `password` -> Role Administrator
- Menghasilkan token JWT mock yang sah secara struktur dan payload profil lengkap.
