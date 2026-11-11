# Implementation Plan: Sistem Aplikasi IT Helpdesk Enterprise

**Branch**: `004-sso-loa-integration` | **Date**: 2026-10-28 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from [`specs/004-sso-loa-integration/spec.md`](./spec.md) based on **Constitution v3.1.0**.

---

## Summary

Mengimplementasikan sistem aplikasi IT Helpdesk enterprise secara menyeluruh yang mengintegrasikan:
1. **Autentikasi SSO Korporat via JWT**: Autentikasi server-side tunggal, validasi klaim token, dan manajemen sesi `HttpOnly` (tanpa penyimpanan di `localStorage`).
2. **Integrasi Eksternal LOA API**: Delegasi persetujuan Atasan, Kadiv IT & Ops, dan Direksi dengan penanganan timeout, error HTTP, retry aman ber-idempotency key, serta sinkronisasi hybrid (webhook + polling).
3. **Penyelarasan Alur Minor vs Major**:
   - Request Minor langsung masuk ke `Pool Siap Progress` setelah disetujui Kabag IT.
   - Request Major melalui rantai eksekutif LOA Kadiv IT & Ops, LOA Direksi, diikuti **tiga tahapan wajib pasca-Direksi**: (1) Rapat Koordinasi Kabag IT, (2) Input Notulensi oleh User Pemohon, dan (3) Approval Notulensi oleh Kabag IT (dengan cabang revisi notulensi) sebelum diizinkan masuk ke `Pool Siap Progress`.
4. **Proteksi Konkurensi Tim IT**: Pengambilan tiket mandiri (*atomic self-assignment*) menggunakan `lockForUpdate` di `Pool Siap Progress`.
5. **Kedaulatan Sign-off Pemohon**: Penutupan resmi tiket mutlak memerlukan persetujuan (*Accept*) pemohon pembuat tiket.
6. **Observabilitas & Audit Trail Abadi**: Pencatatan riwayat transisi status, notulensi, dan referensi transaksi API yang tersanitasi secara *append-only*.

---

## Technical Context

**Language/Version**: PHP 8.2+ (Aktif di environment: PHP 8.5)

**Primary Dependencies**:
- Laravel Framework (v11.x)
- Laravel `Http` Client (Guzzle wrapper)
- Tailwind CSS (v3.x) & PostCSS
- Alpine.js (komponen antarmuka reaktif ringan)

**Storage**:
- Basis Data Relasional: SQLite (Testing/Local) / PostgreSQL / MySQL (Production) via Laravel Database Migrations
- Penyimpanan Berkas: Laravel Private Storage (`storage/app/private/attachments` dan `meeting_minutes`)

**Testing**: PHPUnit / Pest Framework dengan Feature & Unit tests

**Target Platform**: Linux Server / macOS / Containerized Environment over HTTPS

**Project Type**: Enterprise Web Service & Application (Clean Minimalist Enterprise Light Mode)

**Performance Goals**:
- Waktu respon transaksi login SSO < 2 detik
- Transisi status tiket lokal < 1 detik
- Nol insiden perebutan ganda (*0 race conditions*) pada `Pool Siap Progress`

**Constraints**:
- Dilarang menyimpan token JWT di `localStorage` peramban (Konstitusi Prinsip III)
- Dilarang mencatat token, password, atau client secret di log aplikasi (Konstitusi Prinsip IX)
- Panggilan API eksternal wajib menyertakan `Idempotency-Key` (Konstitusi Prinsip V)
- Tiket Major dilarang langsung masuk ke `Pool Siap Progress` setelah LOA Direksi tanpa tahapan rapat dan persetujuan notulensi (Konstitusi Prinsip II)

**Scale/Scope**:
- 7 peran pengguna (`requester`, `supervisor`, `it_head`, `kadiv_it`, `direksi`, `it_developer`, `admin`)
- 10 prioritas user stories komprehensif, 18 kebutuhan fungsional inti, 8 kriteria sukses terukur

---

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-checked after Phase 1 design.*

| Klausul Konstitusi v3.1.0 | Status Evaluasi | Bukti Pemenuhan dalam Desain |
|:---|:---:|:---|
| **I. Security-First & Least Privilege** | **PASS** | Form Requests, Laravel Policies, token validation server-side, sanitasi MIME type berkas lampiran. |
| **II. Business Workflow Integrity & Concurrency** | **PASS** | State machine sequence tegas: Minor langsung ke Pool Siap Progress pasca-Kabag IT; Major melalui Kadiv IT & Ops, Direksi, Rapat Kabag IT, Input Notulensi User, dan Approval Notulensi Kabag IT. Atomic `lockForUpdate` pada klaim tiket. |
| **III. Enterprise SSO via JWT Non-Negotiable** | **PASS** | `SsoServiceInterface` memanggil endpoint SSO resmi, verifikasi signature/expiration server-side, sesi `HttpOnly`, 0% token di `localStorage`. |
| **IV. Existing LOA Services as Authority Source** | **PASS** | `LoaServiceInterface` mendelegasikan approval ke API LOA eksternal, mencatat `external_reference_id`, tidak membangun mesin approval internal. |
| **V. API Integration Resilience & Idempotency** | **PASS** | Pengiriman berbekal `Idempotency-Key`, penanganan status `waiting` dan `sync_failed`, respons 202 tidak menandai tiket disetujui prematur. |
| **VI. Strict Server-Side Authorization** | **PASS** | Kebijakan otorisasi Laravel (`TicketPolicy`, `MeetingMinutesPolicy`) memeriksa kepemilikan objek dan peran secara mutlak (deny-by-default). |
| **VII. Test-Driven Verification** | **PASS** | Pengujian komprehensif disiapkan untuk jalur Minor, jalur Major, penolakan, revisi notulensi, konkurensi self-assign, dan kegagalan API LOA. |
| **VIII. Architectural Simplicity & YAGNI** | **PASS** | Menggunakan fitur bawaan Laravel (Blade, Eloquent, Http client, Form Request) tanpa microservices atau SPA framework tambahan. |
| **IX. Tamper-Resistant Audit Trail** | **PASS** | `TicketAuditLog` bersifat append-only, mencatat seluruh aksi bisnis dan transaksi eksternal dengan sanitasi kredensial. |

---

## Project Structure

### Documentation (this feature)

```text
specs/004-sso-loa-integration/
├── plan.md                                      # Dokumen rencana implementasi ini
├── research.md                                  # Phase 0: Keputusan arsitektur & investigasi teknis
├── data-model.md                                # Phase 1: Skema basis data & relasi entitas
├── quickstart.md                                # Phase 1: Panduan eksekusi & verifikasi runnable
├── checklists/
│   └── requirements.md                          # Daftar uji kualitas spesifikasi
└── contracts/                                   # Phase 1: Spesifikasi kontrak antarmuka
    ├── sso-api-contract.md                      # Kontrak integrasi API SSO JWT
    ├── loa-api-contract.md                      # Kontrak integrasi API LOA eksternal & webhook
    └── web-and-webhook-routes.md                # Kontrak rute web aplikasi & webhook callback
```

### Source Code Layout (Repository Root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php                   # Login SSO, manajemen sesi & logout
│   │   ├── DashboardController.php              # Dashboard KPI per peran
│   │   ├── TicketController.php                 # CRUD tiket, approval Kabag IT, self-assign
│   │   ├── TicketMeetingController.php          # Rapat koordinasi & approval notulensi Kabag IT
│   │   ├── TicketActivityController.php         # Pencatatan aktivitas developer
│   │   ├── TicketReviewController.php           # Review & sign-off pemohon
│   │   └── Api/
│   │       └── LoaWebhookController.php         # Inbound webhook callback keputusan LOA
│   ├── Middleware/
│   │   └── VerifyLoaWebhookSignature.php        # Validasi tanda tangan digital callback LOA
│   └── Requests/
│       ├── LoginRequest.php                     # Validasi form login SSO
│       ├── StoreTicketRequest.php               # Validasi formulir pengajuan tiket
│       ├── ScheduleMeetingRequest.php           # Validasi pencatatan rapat Major
│       ├── SubmitMeetingMinutesRequest.php      # Validasi penginputan notulensi oleh user
│       └── TicketReviewRequest.php              # Validasi review kepuasan pemohon
├── Models/
│   ├── User.php                                 # User model (sso_id, roles, supervisor_id)
│   ├── Ticket.php                               # Core Ticket entity & status constants
│   ├── ExternalLoaTransaction.php               # Transaksi integrasi LOA & Idempotency Key
│   ├── MeetingMinutes.php                       # Notulensi rapat Major pasca-Direksi
│   ├── TicketActivityLog.php                    # Progress logs developer
│   ├── TicketReview.php                         # Review & sign-off pemohon
│   └── TicketAuditLog.php                       # Immutable audit log
├── Policies/
│   ├── TicketPolicy.php                         # Server-side authorization rules
│   └── MeetingMinutesPolicy.php                 # Otorisasi input & approval notulensi
└── Services/
    ├── Contracts/
    │   ├── SsoServiceInterface.php              # Kontrak layanan autentikasi SSO
    │   └── LoaServiceInterface.php              # Kontrak layanan integrasi LOA eksternal
    ├── EnterpriseSsoService.php                 # Implementasi HTTP client SSO korporat
    ├── MockSsoService.php                       # Implementasi mock SSO untuk testing/dev
    ├── EnterpriseLoaService.php                 # Implementasi HTTP client LOA korporat
    ├── MockLoaService.php                       # Implementasi mock LOA untuk testing/dev
    ├── TicketStateMachine.php                   # Transisi alur tiket Minor vs Major
    └── AuditTrailService.php                    # Pencatatan audit log aman & tersanitasi

database/
└── migrations/
    ├── 2026_10_28_000001_create_users_table.php
    ├── 2026_10_28_000002_create_tickets_table.php
    ├── 2026_10_28_000003_create_external_loa_transactions_table.php
    ├── 2026_10_28_000004_create_meeting_minutes_table.php
    ├── 2026_10_28_000005_create_ticket_activity_logs_table.php
    ├── 2026_10_28_000006_create_ticket_reviews_table.php
    └── 2026_10_28_000007_create_ticket_audit_logs_table.php

resources/views/
├── auth/
│   └── login.blade.php                          # Antarmuka login SSO modern
├── dashboard/
│   ├── requester.blade.php                      # Dashboard karyawan pemohon
│   ├── supervisor.blade.php                     # Dashboard atasan langsung
│   ├── it_head.blade.php                        # Dashboard Kepala Bagian IT
│   ├── it_developer.blade.php                   # Dashboard pengembang IT (Pool Siap Progress)
│   ├── kadiv_it.blade.php                       # Dashboard Kepala Divisi IT
│   └── direksi.blade.php                        # Dashboard Direksi
├── tickets/
│   ├── create.blade.php                         # Formulir pengajuan tiket
│   ├── show.blade.php                           # Detail tiket, Visual Stepper, timeline audit
│   └── partials/
│       ├── visual_stepper.blade.php             # Stepper dinamis (Jalur Minor vs Major)
│       ├── meeting_modal.blade.php              # Modal rapat koordinasi Major
│       └── minutes_modal.blade.php              # Modal input notulensi oleh user pemohon
└── layouts/
    └── app.blade.php                            # Layout utama Clean Minimalist Enterprise

routes/
├── web.php                                      # Rute web terotentikasi & Blade views
└── api.php                                      # Rute webhook API LOA eksternal

tests/
├── Feature/
│   ├── SsoAuthenticationTest.php                # Uji login SSO, JWT invalid, session HttpOnly
│   ├── ExternalLoaIntegrationTest.php           # Uji dispatch LOA, webhook, idempotency, retry
│   ├── MinorWorkflowTest.php                    # Uji fast-track Minor ke Pool Siap Progress
│   ├── MajorWorkflowTest.php                    # Uji rantai eksekutif Major + post-Direksi stages
│   ├── MajorPostDireksiMeetingTest.php          # Uji rapat, input notulensi user, approval notulensi
│   ├── ConcurrencySelfAssignTest.php            # Uji klaim atomik lockForUpdate di Pool Siap Progress
│   └── UserReviewSignOffTest.php                # Uji kedaulatan Accept/Revisi pemohon
└── Unit/
    ├── TicketStateMachineTest.php               # Uji integritas transisi status
    └── LoaIdempotencyTest.php                   # Uji determinisme Idempotency Key
```

---

## Structure Decision

Mengadopsi struktur standar **Laravel Monolith Modular** (Option 1 - Single Project) sesuai Konstitusi Prinsip VIII. Seluruh logika domain bisnis, integrasi HTTP client ke SSO dan LOA, perutean webhook, dan antarmuka Blade terintegrasi secara modular di dalam repositori tanpa memerlukan arsitektur microservices terpisah yang prematur.

---

## Complexity Tracking

> *Section ini kosong karena seluruh desain sepenuhnya mematuhi prinsip Konstitusi v3.1.0 tanpa ada pelanggaran yang memerlukan justifikasi.*
