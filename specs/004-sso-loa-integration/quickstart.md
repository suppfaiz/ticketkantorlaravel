# Quickstart & Verification Guide: Sistem IT Helpdesk Enterprise

**Feature**: `004-sso-loa-integration`  
**Date**: 2026-10-28  
**Status**: Completed (Phase 1)  
**Governance**: Aligned with Constitution v3.1.0

---

## 1. Prerequisites & Environment Setup

Pastikan lingkungan lokal telah terkonfigurasi dengan dependensi yang benar:
- PHP >= 8.2 (aktif: PHP 8.5)
- SQLite atau PostgreSQL / MySQL
- Composer & NPM

Tambahkan konfigurasi integrasi pada file `.env`:
```dotenv
APP_ENV=local
APP_DEBUG=true

# SSO Configuration
SSO_DRIVER=mock
SSO_BASE_URL=https://sso.internal.corp
SSO_CLIENT_ID=it-helpdesk-app
SSO_TIMEOUT_SECONDS=10

# LOA Configuration
LOA_DRIVER=mock
LOA_BASE_URL=https://loa.internal.corp
LOA_API_KEY=mock-loa-api-key
LOA_WEBHOOK_SECRET=mock-webhook-secret-key
LOA_TIMEOUT_SECONDS=15
```

Jalankan migrasi basis data dan build asset antarmuka:
```bash
php artisan migrate
npm run build
```

---

## 2. Runnable Verification Scenarios

### Scenario 1: SSO JWT Authentication Lifecycle
1. Buka halaman `/login`.
2. Masukkan kredensial mock requester: `requester@internal.corp` / `password`.
3. **Hasil yang Diharapkan**:
   - `MockSsoService` menghasilkan token JWT yang valid.
   - Sesi terotentikasi dibuat di sisi server via cookie `HttpOnly`.
   - Tidak ada token JWT yang tersimpan di `localStorage` peramban.
   - Pengguna diarahkan ke dashboard requester `/dashboard/requester`.

### Scenario 2: Alur Tiket Minor (Fast-Track ke Pool Siap Progress)
1. Login sebagai `requester@internal.corp`, ajukan tiket baru dengan kategori `hardware` dan klasifikasi yang diusulkan.
2. Status tiket menjadi `menunggu_approval_atasan`, dan panggilan API LOA Atasan terpicu.
3. Kirimkan callback webhook approval Atasan:
   ```bash
   curl -X POST http://localhost:8000/api/v1/webhooks/loa-decision \
     -H "Content-Type: application/json" \
     -d '{"event":"loa.decision.updated","data":{"ticket_number":"TICK-XXX","approval_level":"supervisor","decision":"approved"}}'
   ```
4. Status tiket berubah menjadi `menunggu_approval_it_head`.
5. Login sebagai `kabagit@internal.corp`, buka tiket dan pilih aksi "Setujui Klasifikasi Minor".
6. **Hasil yang Diharapkan**:
   - Tiket langsung beralih ke `siap_dikerjakan` dan muncul di **Pool Siap Progress**.
   - Tiket TIDAK dialirkan ke Kadiv IT, Direksi, rapat, maupun notulensi.

### Scenario 3: Alur Tiket Major Lengkap (LOA Kadiv IT → LOA Direksi → Rapat → Notulensi → Approval Notulensi)
1. Ajukan tiket baru, setujui LOA Atasan via webhook.
2. Login sebagai Kabag IT, pilih "Setujui Klasifikasi Major".
3. Status tiket berubah menjadi `menunggu_loa_kadiv_it` (pengajuan LOA Kadiv IT via API terpicu).
4. Kirim webhook persetujuan Kadiv IT -> status otomatis beralih ke `menunggu_loa_direksi` (pengajuan LOA Direksi via API terpicu).
5. Kirim webhook persetujuan Direksi -> status berubah menjadi `menunggu_rapat_major`.
   *(Verifikasi bahwa tiket BELUM MASUK ke Pool Siap Progress)*.
6. Login sebagai Kabag IT, buka tiket dan isi data pelaksanaan rapat -> status menjadi `menunggu_input_notulensi`.
7. Login sebagai Requester, buka tiket dan input notulensi hasil rapat beserta file lampiran -> status menjadi `menunggu_approval_notulensi`.
8. Login sebagai Kabag IT:
   - *Uji Cabang Revisi*: Pilih "Minta Revisi Notulensi" dengan komentar -> status menjadi `revisi_notulensi_major`, pemohon memperbaiki dan submit ulang.
   - *Uji Cabang Setujui*: Pilih "Setujui Notulensi" -> status resmi beralih ke `siap_dikerjakan` di **Pool Siap Progress**.

### Scenario 4: Concurrency-Safe Self-Assignment di Pool Siap Progress
1. Buka tiket berstatus `siap_dikerjakan` di dua jendela peramban berbeda dengan akun developer berbeda: `developer1@internal.corp` dan `developer2@internal.corp`.
2. Klik tombol "Ambil Tiket" pada kedua jendela secara bersamaan.
3. **Hasil yang Diharapkan**:
   - Tepat satu developer berhasil mengklaim tiket menjadi `dalam_pengerjaan`.
   - Developer kedua menerima notifikasi bahwa tiket telah diambil rekan lain (0 duplikasi klaim).

### Scenario 5: Review Pemohon & Penutupan Resmi
1. Developer menyelesaikan pekerjaan dan menekan tombol "Selesai Dikerjakan" -> status menjadi `menunggu_review_user`.
2. Login sebagai Requester, buka tiket dan pilih "Accept" beserta rating 5 bintang dan feedback.
3. **Hasil yang Diharapkan**:
   - Tiket resmi beralih ke status `selesai` (Closed).
   - Rating dan riwayat terekam abadi di tabel `ticket_reviews` dan `ticket_audit_logs`.

---

## 3. Automated Test Execution Commands

Jalankan seluruh pengujian otomatis fitur ini menggunakan PHPUnit:
```bash
php artisan test --filter=SsoAuthenticationTest
php artisan test --filter=ExternalLoaIntegrationTest
php artisan test --filter=MajorPostDireksiMeetingTest
php artisan test --filter=TicketStateMachineTest
php artisan test --filter=TicketSelfAssignConcurrencyTest
```
