# Implementation Plan: Aplikasi IT Helpdesk Internal Perusahaan

**Branch**: `001-it-helpdesk` | **Date**: 2026-10-28 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/001-it-helpdesk/spec.md`

## Summary

Membangun aplikasi IT Helpdesk internal perusahaan berbasis Laravel dan Blade untuk mengelola siklus hidup permintaan bantuan IT karyawan secara terstruktur: pengajuan tiket, persetujuan bertingkat (Atasan Langsung dan Kepala Bagian IT dengan jalur Minor vs Major), antrean pengerjaan mandiri (*self-assign*) yang aman terhadap *race condition*, pelaporan progres, hingga *sign-off* dan ulasan oleh Requester. Pendekatan teknis mengutamakan konvensi resmi Laravel (Form Requests, Eloquent, Policies, Session-based Auth) tanpa framework SPA eksternal, dengan pencatatan audit trail *append-only* yang kebal manipulasi.

## Technical Context

**Language/Version**: PHP 8.2+ (Terverifikasi di lingkungan: PHP 8.5.2 CLI)

**Primary Dependencies**: Laravel 11.x / 12.x (Framework inti), Laravel Breeze (Blade stack untuk autentikasi sesi), Composer 2.x

**Storage**: PostgreSQL (Rekomendasi utama) atau MySQL 8.0+; Private disk storage (`storage/app/private/tickets`) untuk lampiran berkas

**Testing**: PHPUnit / Pest (Feature tests untuk otorisasi & alur HTTP, Unit tests untuk transisi state machine, Concurrency tests untuk *self-assign*)

**Target Platform**: Internal Web Application (Linux Server / Nginx / PHP-FPM, diakses peramban modern desktop & mobile via responsive Blade)

**Project Type**: Monolithic Web Application (Laravel Blade + Tailwind CSS via Vite)

**Design System & UI Theme**: Diintegrasikan langsung dengan panduan [DESIGN.md](../../DESIGN.md) (Clean Minimalist Enterprise Light Mode):
- Background Utama: `#FFFFFF` (Putih bersih) dengan latar penunjang `#F8FAFC`.
- Permukaan Kartu & Form: `#FFFFFF` dengan garis tepi tipis `#E2E8F0` (border halus, tanpa glow/shadow tebal).
- Warna & Aksen: Minimalis monokromatik/slate (`#0F172A` / `#1E293B`) dengan satu aksen korporat fungsional (`#2563EB`).
- Teks: Slate gelap `#0F172A` (kontras tinggi) dan teks keterangan `#64748B`.
- Status Badge: Warna pastel fungsional lembut (Menunggu: Slate, Revisi: Amber, Tolak: Merah, Pengerjaan: Biru, Selesai: Hijau).
- Tipografi: *Inter* untuk seluruh antarmuka, *Font Monospace* khusus untuk penomoran tiket unik dan kode audit.

**Performance Goals**: Waktu respons server < 200ms p95 untuk seluruh operasi formulir; penanganan transaksi *self-assign* simultan bebas deadlock

**Constraints**:
- Sesi terisolasi over HTTPS, cookie `HttpOnly`, `SameSite=Lax`, CSRF protection aktif.
- Berkas privat tersimpan di luar `public/` web root dan hanya disajikan via streaming berotorisasi Policy.
- Tidak ada penutupan tiket sepihak oleh tim IT tanpa persetujuan (Accept) dari Requester.

**Scale/Scope**: ~1,000 pengguna internal karyawan, estimasi ~100–300 tiket per hari, riwayat audit tersimpan permanen.

## Constitution Check

*GATE: Evaluasi terhadap Konstitusi Proyek v1.0.0*

| Prinsip Konstitusi | Status Evaluasi | Catatan Kesesuaian Desain |
|---|---|---|
| **I. Security-First & Least Privilege** | **PASS** | Validasi input via Form Request, proteksi CSRF di seluruh form mutasi, otorisasi Policies deny-by-default, mitigasi IDOR, password hashing Argon2id/Bcrypt, rate limiting login tanpa enumerasi akun. |
| **II. Business Workflow Integrity & Concurrency** | **PASS** | State machine 15 status ditegakkan di level server; pencegahan *race condition* pada *self-assign* menggunakan conditional update atomik berstatus `Siap Dikerjakan` dan `assigned_to IS NULL`. |
| **III. Strict Server-Side Authorization** | **PASS** | `TicketPolicy` memvalidasi hierarki atasan bawahan, penugasan developer, dan peran requester; Admin sistem tidak memiliki hak approve tiket bisnis. |
| **IV. Test-Driven Verification** | **PASS** | Rencana pengujian mencakup *Feature Tests* untuk seluruh jalur skenario (Minor, Major, Rejection, Revision, Re-assign), *Authorization Tests*, dan *Concurrency Tests*. |
| **V. Simplicity, Maintainability & YAGNI** | **PASS** | Monolitik Blade standar tanpa dependensi SPA pihak ketiga yang tidak diperlukan; tidak menggunakan queue/cache prematur. |
| **VI. Tamper-Resistant Audit Trail** | **PASS** | Tabel khusus `ticket_audit_logs` bersifat *append-only* (tanpa kemampuan update/delete di level aplikasi) merekam setiap transisi, aktor, waktu, dan alasan. |

## Project Structure

### Documentation (this feature)

```text
specs/001-it-helpdesk/
├── plan.md              # Rencana implementasi teknis & arsitektur
├── research.md          # Analisis & keputusan teknis (Phase 0)
├── data-model.md        # Desain skema basis data & relasi (Phase 1)
├── quickstart.md        # Skenario validasi ujung-ke-ujung (Phase 1)
├── contracts/           # Spesifikasi rute, kebijakan, & state machine
│   ├── web-routes.md
│   ├── policy-rules.md
│   └── state-machine.md
├── checklists/          # Checklist kualitas spesifikasi
│   └── requirements.md
└── spec.md              # Spesifikasi kebutuhan fungsional
```

### Source Code (Laravel Application Layout)

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/AuthenticatedSessionController.php
│   │   ├── DashboardController.php
│   │   ├── TicketController.php
│   │   ├── SupervisorApprovalController.php
│   │   ├── ItHeadApprovalController.php
│   │   ├── MeetingMinutesController.php
│   │   ├── TicketAssignmentController.php
│   │   ├── TicketActivityController.php
│   │   ├── TicketReviewController.php
│   │   └── AttachmentController.php
│   └── Requests/
│       ├── StoreTicketRequest.php
│       ├── UpdateTicketRequest.php
│       ├── SupervisorApprovalRequest.php
│       ├── SupervisorRejectRequest.php
│       ├── SupervisorReviseRequest.php
│       ├── ItHeadClassifyRequest.php
│       ├── ItHeadRejectRequest.php
│       ├── ItHeadReviseRequest.php
│       ├── StoreMeetingMinutesRequest.php
│       ├── ApproveMeetingMinutesRequest.php
│       ├── StoreActivityLogRequest.php
│       ├── ReviewAcceptRequest.php
│       └── ReviewReviseRequest.php
├── Models/
│   ├── User.php
│   ├── Department.php
│   ├── Ticket.php
│   ├── TicketAttachment.php
│   ├── MeetingMinutes.php
│   ├── TicketActivityLog.php
│   ├── TicketAuditLog.php
│   └── TicketReview.php
├── Policies/
│   ├── TicketPolicy.php
│   └── MeetingMinutesPolicy.php
└── Services/
    ├── TicketService.php           # Logika pembuatan tiket & nomor urut unik
    ├── TicketStateMachine.php      # Validasi & eksekusi transisi status
    └── AuditTrailService.php       # Pencatatan riwayat audit immutable

database/
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 2026_10_28_000001_create_departments_table.php
│   ├── 2026_10_28_000002_create_tickets_table.php
│   ├── 2026_10_28_000003_create_ticket_attachments_table.php
│   ├── 2026_10_28_000004_create_meeting_minutes_table.php
│   ├── 2026_10_28_000005_create_ticket_activity_logs_table.php
│   ├── 2026_10_28_000006_create_ticket_reviews_table.php
│   └── 2026_10_28_000007_create_ticket_audit_logs_table.php
└── seeders/
    └── DatabaseSeeder.php

resources/views/
├── layouts/
│   ├── app.blade.php
│   └── guest.blade.php
├── auth/
│   └── login.blade.php
├── dashboard/
│   ├── requester.blade.php
│   ├── supervisor.blade.php
│   ├── it_head.blade.php
│   └── it_developer.blade.php
├── tickets/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   ├── edit.blade.php
│   └── partials/
│       ├── audit-history.blade.php
│       ├── activity-timeline.blade.php
│       └── minutes-section.blade.php
└── meeting-minutes/
    ├── create.blade.php
    └── show.blade.php

routes/
├── web.php
└── auth.php

tests/
├── Feature/
│   ├── AuthenticationTest.php
│   ├── TicketCreationTest.php
│   ├── SupervisorApprovalTest.php
│   ├── ItHeadClassificationTest.php
│   ├── MeetingMinutesTest.php
│   ├── TicketConcurrencyTest.php
│   ├── UserReviewClosureTest.php
│   └── TicketAuthorizationTest.php
└── Unit/
    └── TicketStateMachineTest.php
```

**Structure Decision**: Menggunakan tata letak monolitik standar Laravel (*Single Application Structure*) yang memisahkan tanggung jawab secara tegas (*Separation of Concerns*): Form Requests untuk validasi, Policies untuk otorisasi objek, Domain Services untuk logika transisi & nomor tiket unik, dan Blade Views untuk antarmuka pengguna responsif.

## Complexity Tracking

> **Status Konstitusi: TIDAK ADA PELANGGARAN (ZERO VIOLATIONS)**

Seluruh keputusan teknis mematuhi prinsip arsitektur konstitusi v1.0.0. Tidak ada *design pattern* berlebih (seperti *Repository Pattern* berlapis ganda atau arsitektur *Microservices*) yang diterapkan.
