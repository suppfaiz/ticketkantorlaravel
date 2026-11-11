# Technical Research & Architecture Decisions: Sistem IT Helpdesk Enterprise

**Feature Branch**: `004-sso-loa-integration`  
**Date**: 2026-10-28  
**Status**: Completed (Phase 0)  
**Governance**: Aligned with Constitution v3.1.0

---

## 1. SSO JWT Authentication Architecture

### Decision
Menggunakan arsitektur **Server-Side Authentication Gateway**:
- Frontend login form mengirimkan kredensial (username/email dan kata sandi) ke backend Laravel via HTTPS.
- Service `App\Services\EnterpriseSsoService` (mengimplementasikan `App\Services\Contracts\SsoServiceInterface`) memanggil API login SSO perusahaan (`POST {SSO_BASE_URL}/api/v1/auth/login`).
- Respons SSO mengembalikan JWT token dan payload identitas pengguna.
- Backend memvalidasi JWT token secara ketat di sisi server (verifikasi signature, issuer, audience, dan expiration time).
- Sesi pengguna dikelola oleh session driver Laravel menggunakan cookie berstatus `HttpOnly`, `Secure`, dan `SameSite=Lax`.
- Token JWT disimpan di sesi server terenkripsi (tidak pernah dikirim ke `localStorage` peramban).
- Identitas pengguna disinkronkan ke tabel `users` dengan kolom `sso_id`.
- Untuk pengujian otomatis dan lokal development tanpa server SSO live, disediakan `App\Services\MockSsoService` yang dapat diaktifkan melalui `SSO_DRIVER=mock` pada file `.env`.

### Rationale
- **Kepatuhan Konstitusi v3.1.0 (Prinsip III)**: SSO perusahaan menjadi sumber autentikasi tunggal; dilarang membuat autentikasi login lokal terpisah.
- **Keamanan Siber (OWASP Top 10)**: Mencegah serangan Cross-Site Scripting (XSS) yang dapat mencuri token dari peramban jika JWT disimpan di `localStorage`.
- **Zero Log Leakage**: Kredensial dan signature token disanitasi secara ketat sehingga tidak pernah tercatat di berkas log.

### Alternatives Evaluated
- *Client-Side SPA Token Handling*: Ditolak karena menyimpan token JWT di `localStorage` peramban sangat rentan terhadap pencurian XSS dan melanggar aturan konstitusi.
- *Local Credentials Table*: Ditolak karena menduplikasi basis data kredensial perusahaan dan melanggar kebijakan sentralisasi akun korporat.

---

## 2. External LOA API Integration, Idempotency & Fault Tolerance

### Decision
Menggunakan arsitektur **Delegated Orchestrator** dengan interface `App\Services\Contracts\LoaServiceInterface`:
- Implementasi `EnterpriseLoaService` menggunakan Laravel `Http` client dengan timeout konfigurabel (`LOA_TIMEOUT_SECONDS=15`) dan batas pengulangan (`LOA_MAX_RETRIES=3`).
- Implementasi `MockLoaService` menyediakan pengujian terisolasi untuk semua skenario (Disetujui, Ditolak, Revisi, Timeout, Error 500).

Mekanisme Pengajuan & Idempotensi:
1. Setiap permohonan persetujuan (Atasan, Kadiv IT & Ops, Direksi) membangkitkan `idempotency_key` (kombinasi unik `ticket_id` + `approval_level` + `submission_attempt`) dan dikirimkan pada HTTP header `Idempotency-Key`.
2. Data transaksi integrasi dicatat pada tabel `external_loa_transactions`: `external_reference_id`, `approval_level`, `idempotency_key`, `sync_status` (`pending`, `synced`, `sync_failed`), dan `decision` (`waiting`, `approved`, `rejected`, `revision`).

Mekanisme Sinkronisasi Status (Hybrid Webhook + Polling):
- **Primary**: Webhook endpoint `POST /api/v1/webhooks/loa-decision` menerima callback instan dari aplikasi LOA saat approver mengambil keputusan, diverifikasi dengan tanda tangan digital `X-LOA-Signature` dan secret key.
- **Fallback**: Artisan command `php artisan helpdesk:sync-loa` (dijalankan via scheduler berkala) dan tombol aksi manual "Sinkronkan Status LOA" pada halaman detail tiket untuk memeriksa status jika webhook terlewat akibat fluktuasi jaringan.

Penanganan Kegagalan Jaringan & Timeout:
- Jika panggilan API LOA mengalami timeout atau HTTP 5xx, status tiket lokal **TIDAK BERUBAH SECARA PREMATUR**. Transaksi dicatat berstatus `sync_failed` dan sistem menyediakan mekanisme retry yang aman berbekal `Idempotency-Key` yang sama persis.

### Rationale
- **Kepatuhan Konstitusi v3.1.0 (Prinsip IV & V)**: Helpdesk mengorkestrasikan alur tiket, sedangkan kewenangan approval bersumber resmi dari aplikasi LOA eksternal.
- Menjamin tidak ada pengajuan LOA duplikat akibat retry di aplikasi sumber.

---

## 3. Post-Direksi Major Governance Workflow: Rapat, Notulensi User & Approval Kabag IT

### Decision
Memperbarui State Machine agar setelah request Major disetujui LOA Direksi, tiket **DILARANG LANGSUNG MASUK** ke `Pool Siap Progress`. Tiga tahapan wajib pasca-Direksi ditegakkan:
1. **Tahap C.3 — Penjadwalan & Pelaksanaan Rapat**:
   - Status tiket: `menunggu_rapat_major`.
   - Kabag IT mengadakan rapat bersama user pemohon dan Kabag unit pemohon, kemudian mencatat informasi rapat (tanggal, peserta, ringkasan arahan).
   - Setelah dicatat, status tiket beralih ke `menunggu_input_notulensi`.
2. **Tahap C.4 — Input Notulensi oleh User Pemohon**:
   - Status tiket: `menunggu_input_notulensi`.
   - User pemohon menginput notulensi hasil rapat beserta berkas lampiran pendukung ke tabel `meeting_minutes`.
   - Tiket tertahan menunggu penginputan notulensi sebelum dapat dilanjutkan. Setelah dikirim, status beralih ke `menunggu_approval_notulensi`.
3. **Tahap C.5 — Approval Notulensi oleh Kepala Bagian IT**:
   - Status tiket: `menunggu_approval_notulensi`.
   - Kabag IT memeriksa notulensi yang diinput pemohon:
     - **Setujui Notulensi**: Tiket Major resmi beralih ke `Pool Siap Progress` (status `siap_dikerjakan`).
     - **Revisi Notulensi**: Status tiket beralih ke `revisi_notulensi_major` disertai komentar perbaikan wajib. Tiket kembali ke user pemohon untuk diperbaiki dan disubmit ulang.

### Rationale
- Memenuhi arahan bisnis terbaru dan Konstitusi v3.1.0 secara presisi: permohonan berskala Major yang telah disetujui Direksi wajib memiliki kesepakatan teknis formal yang disahkan oleh Kabag IT sebelum developer mulai bekerja.

---

## 4. Minor vs Major State Machine Sequence & Concurrency Safety

### State Transitions Table
- **Jalur Minor**:  
  `draft` → `menunggu_approval_atasan` (LOA API) → `menunggu_approval_it_head` → `siap_dikerjakan` (`Pool Siap Progress`) → `dalam_pengerjaan` → `menunggu_review_user` → `selesai`.  
  *(Minor langsung masuk ke Pool Siap Progress tanpa melalui Kadiv IT, Direksi, rapat, maupun notulensi)*.
- **Jalur Major**:  
  `draft` → `menunggu_approval_atasan` (LOA API) → `menunggu_approval_it_head` → `menunggu_loa_kadiv_it` (LOA API) → `menunggu_loa_direksi` (LOA API) → `menunggu_rapat_major` → `menunggu_input_notulensi` → `menunggu_approval_notulensi` (Kabag IT) → `siap_dikerjakan` (`Pool Siap Progress`) → `dalam_pengerjaan` → `menunggu_review_user` → `selesai`.

### Terminal States & Rejection Paths:
- Penolakan Atasan → `ditolak_atasan`
- Penolakan Kabag IT → `ditolak_it_head`
- Penolakan Kadiv IT → `ditolak_kadiv_it`
- Penolakan Direksi → `ditolak_direksi`
Seluruh status penolakan memindahkan tiket secara permanen ke `Pool Tiket Ditolak`.

### Concurrency Protection pada Pool Siap Progress:
- Pengambilan tiket mandiri (`selfAssign`) oleh tim IT pengembang dibungkus dalam `DB::transaction` dengan `Ticket::where('id', $id)->lockForUpdate()->firstOrFail()`.
- Validasi memverifikasi status tiket harus `siap_dikerjakan` dan `assigned_to === null`. Klaim ganda secara bersamaan dijamin gagal secara bersih (0 konflik race condition).

### Kedaulatan Review Pemohon (Sign-Off):
- Penutupan tiket resmi (`selesai`) hanya sah dieksekusi oleh pemohon pembuat tiket (`$ticket->user_id === Auth::id()`) disertai rating kepuasan (1-5) dan ulasan feedback.
