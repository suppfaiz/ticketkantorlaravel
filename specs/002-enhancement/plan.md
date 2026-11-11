# Implementation Plan: Peningkatan Antarmuka Pengguna (UI/UX Enhancement)

**Branch**: `002-enhancement` | **Date**: 2026-10-28 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/002-enhancement/spec.md`

## Summary

Peningkatan antarmuka pengguna (UI/UX) komprehensif pada aplikasi IT Helpdesk internal perusahaan dengan standar *Clean Minimalist Enterprise Light Mode* (`DESIGN.md`). Pendekatan teknis mengintegrasikan navigasi Sidebar modern *collapsible* di sisi kiri menggunakan Alpine.js, komponen visual *Workflow Stepper* 5-tahap (Tahap A s/d E) pada detail tiket, dialog modal interaktif terfokus untuk aksi keputusan alur kerja (Persetujuan, Penolakan dengan alasan wajib, Revisi dengan catatan, dan Penyelesaian), kartu metrik (KPI Cards) modern pada seluruh dashboard, dropzone interaktif untuk unggahan lampiran, serta tabel daftar tiket yang lebih elegan tanpa mengubah integritas alur bisnis backend atau skema database.

## Technical Context

**Language/Version**: PHP 8.2+ (terverifikasi kompatibel PHP 8.5)

**Primary Dependencies**: Laravel 12.x, Tailwind CSS, Alpine.js (bawaan Laravel Breeze)

**Storage**: SQLite (`database/database.sqlite` untuk dev, `:memory:` untuk pengujian otomatis)

**Testing**: PHPUnit 11 (Feature & Unit tests, 60 skenario pengujian)

**Target Platform**: Peramban web modern (Desktop dan Mobile responsif >= 360px)

**Project Type**: Web Application Full-Stack (Laravel Blade + Tailwind CSS + Alpine.js)

**Performance Goals**: Waktu render halaman < 200ms, zero layout shift (CLS), kepatuhan kontras WCAG AA (>= 4.5:1 untuk teks normal)

**Constraints**:
- Wajib mematuhi pedoman visual *Clean Minimalist Enterprise Light Mode* dasar putih (`#FFFFFF`, `#F8FAFC`, `#E2E8F0`, `#0F172A`, aksen `#2563EB`).
- Tidak ada dark mode, tidak ada warna neon, tidak ada background pastel di area besar.
- Menjaga seluruh aturan otorisasi server-side dan state machine 5-tahap (Constitution Prinsip I, II, III).
- Tidak menggunakan framework frontend eksternal berat (React/Vue/Angular) per Prinsip V (YAGNI & Simplicity).
- Tidak mengubah skema database relasional (*zero migrations*).

**Scale/Scope**:
- 1 Layout Master (`layouts/app.blade.php`) + Komponen Sidebar Baru (`layouts/sidebar.blade.php`).
- 4 Tampilan Dashboard Peran (`dashboard/requester.blade.php`, `dashboard/supervisor.blade.php`, `dashboard/it_head.blade.php`, `dashboard/it_developer.blade.php`).
- 1 Komponen Stepper 5-Tahap (`components/ticket-stepper.blade.php`).
- 1 Komponen Kartu KPI (`components/kpi-card.blade.php`).
- 1 Komponen File Dropzone (`components/file-dropzone.blade.php`).
- Halaman Detail Tiket (`tickets/show.blade.php`) dengan dialog modal interaktif.
- Halaman Index Tiket (`tickets/index.blade.php`) dengan tabel modern berbaris hover lembut.
- Halaman Form Buat & Edit Tiket (`tickets/create.blade.php`, `tickets/edit.blade.php`).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Prinsip Konstitusi | Status Evaluasi | Justifikasi Teknis |
|---|---|---|
| **I. Security-First & Least Privilege** | **PASS** | Semua modal dialog menyertakan `@csrf` dan POST ke endpoint yang dilindungi server-side Policies. Tidak ada kontrol UI yang diperlakukan sebagai batas keamanan. |
| **II. Business Workflow Integrity & Concurrency Safety** | **PASS** | State machine 5-tahap (`TicketStateMachine`) dan lock atomik pengerjaan tidak diubah sedikit pun. Alur data tetap melalui siklus Tahap A -> B -> C -> D -> E. |
| **III. Strict Server-Side Authorization & Deny-by-Default** | **PASS** | Otorisasi berbasis role, kepemilikan tiket, dan pencegahan IDOR tetap ditegakkan oleh Laravel Policies (`TicketPolicy`, `MeetingMinutesPolicy`). |
| **IV. Test-Driven Verification & Quality Assurance** | **PASS** | Seluruh 60 test otomatis di `tests/Feature` dan `tests/Unit` wajib tetap lulus 100% tanpa regresi. |
| **V. Architectural Simplicity, Maintainability & YAGNI** | **PASS** | Menggunakan native Blade template, Tailwind CSS, dan Alpine.js bawaan Breeze. Tidak ada library SPA baru yang ditambahkan. |
| **VI. Tamper-Resistant Audit Trail & Observability** | **PASS** | Seluruh pencatatan jejak audit tetap dieksekusi secara otomatis oleh state machine di backend dan disajikan dalam timeline vertikal yang rapi. |

## Project Structure

### Documentation (this feature)

```text
specs/002-enhancement/
├── plan.md              # File ini (Implementation Plan)
├── research.md          # Output Riset Fase 0 (Keputusan arsitektur UI, layout, stepper, modal)
├── data-model.md        # Output Desain Fase 1 (Model presentasi view, status stepper, state dropzone)
├── contracts/
│   └── ui-components.md # Output Kontrak Fase 1 (Kontrak komponen Blade, props, dan event Alpine)
├── quickstart.md        # Output Panduan Verifikasi Fase 1 (Skenario pengujian end-to-end)
└── tasks.md             # Output Fase 2 (/speckit-tasks)
```

### Source Code Layout

```text
resources/
├── css/
│   └── app.css                             # Custom utility classes & font imports
├── js/
│   ├── app.js                              # Alpine.js initialization & bootstrap
│   └── bootstrap.js
└── views/
    ├── layouts/
    │   ├── app.blade.php                   # Master app layout dengan integrasi sidebar
    │   ├── sidebar.blade.php               # Komponen sidebar collapsible enterprise
    │   ├── topbar.blade.php                # Topbar ringkas dengan profil & hamburger
    │   └── guest.blade.php                 # Layout autentikasi
    ├── components/
    │   ├── ticket-stepper.blade.php         # Visual 5-stage workflow stepper
    │   ├── kpi-card.blade.php               # Statistical KPI metric card
    │   ├── file-dropzone.blade.php          # Drag-and-drop file upload zone
    │   ├── ticket-status-badge.blade.php    # Clean enterprise status badge
    │   ├── ticket-priority-badge.blade.php  # Priority indicator badge
    │   └── modal.blade.php                 # Reusable Alpine.js modal dialog
    ├── dashboard/
    │   ├── requester.blade.php              # Modern requester dashboard
    │   ├── supervisor.blade.php             # Modern supervisor dashboard
    │   ├── it_head.blade.php                # Modern IT head dashboard
    │   └── it_developer.blade.php           # Modern IT developer dashboard
    └── tickets/
        ├── index.blade.php                  # Modern data table with filters
        ├── show.blade.php                   # Detail view dengan Stepper & Action Modals
        ├── create.blade.php                 # Form pembuatan dengan dropzone
        ├── edit.blade.php                   # Form revisi dengan dropzone
        └── partials/
            ├── audit-history.blade.php      # Vertical timeline audit log
            ├── activity-timeline.blade.php  # Developer activity log timeline
            ├── minutes-section.blade.php    # Major meeting minutes section
            └── review-section.blade.php     # Final review & sign-off section
```

## Complexity Tracking

> *Zero constitution violations. Semua komponen menggunakan pustaka inti yang sudah tersedia di proyek.*

| Violation | Why Needed | Simpler Alternative Rejected Because |
|---|---|---|
| *None* | N/A | N/A |
