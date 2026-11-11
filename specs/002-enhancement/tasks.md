---
description: "Daftar tugas implementasi peningkatan UI/UX (002-enhancement)"
---

# Tasks: Peningkatan Antarmuka Pengguna (UI/UX Enhancement)

**Input**: Design documents from `specs/002-enhancement/` (`spec.md`, `plan.md`, `research.md`, `data-model.md`, `contracts/ui-components.md`, `quickstart.md`)  
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/ui-components.md`  
**Constitution**: Compliance with Principles I, II, III, IV, V, VI from `.specify/memory/constitution.md`  
**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[TaskID] [P?] [Story?] Description with file path`

- **[P]**: Dapat dikerjakan secara paralel (berkas terpisah, tanpa dependensi belum terpenuhi)
- **[Story]**: Menandakan user story asal ([US1], [US2], [US3])
- Seluruh tugas menyertakan path berkas yang jelas dan eksplisit.

---

## Phase 1: Setup (Infrastruktur Bersama)

**Purpose**: Konfigurasi aset styling, warna desain light-mode, dan integrasi utilitas Tailwind

- [X] T001 Verify asset build configuration and styling utility setup in vite.config.js and tailwind.config.js
- [X] T002 [P] Configure custom design tokens and enterprise light-mode utility classes in resources/css/app.css

---

## Phase 2: Foundational (Prasyarat Pemblokir)

**Purpose**: Komponen dasar layout dan UI umum yang WAJIB selesai sebelum implementasi user story

**⚠️ CRITICAL**: Pekerjaan user story bergantung pada komponen layout dan komponen kartu dasar di fase ini

- [X] T003 Create reusable KPI metric card Blade component in resources/views/components/kpi-card.blade.php
- [X] T004 [P] Create top navigation bar component with breadcrumbs and user profile in resources/views/layouts/topbar.blade.php
- [X] T005 [P] Create collapsible enterprise sidebar navigation component in resources/views/layouts/sidebar.blade.php
- [X] T006 Update master application layout with sidebar grid and mobile drawer in resources/views/layouts/app.blade.php

**Checkpoint**: Pondasi layout sidebar dan komponen kartu KPI siap - implementasi user story dapat dimulai

---

## Phase 3: User Story 1 - Tata Letak Navigasi Sidebar Collapsible & Dashboard Enterprise (Priority: P1) 🎯 MVP

**Goal**: Menyajikan navigasi sidebar kiri modern collapsible yang bersih dan memperbarui dashboard 4 peran dengan kartu KPI metrik yang elegan dan tata letak terstruktur.

**Independent Test**: Login sebagai Requester, Supervisor, IT Head, dan IT Developer; uji tombol toggle collapse sidebar (persistensi `localStorage`), uji mobile drawer pada lebar layar < 768px, dan verifikasi tampilan kartu metrik statistik pada masing-masing dashboard peran.

### Implementation for User Story 1

- [X] T007 [P] [US1] Update Requester dashboard with modern KPI cards and recent ticket list in resources/views/dashboard/requester.blade.php
- [X] T008 [P] [US1] Update Supervisor dashboard with pending approval KPI cards and team ticket list in resources/views/dashboard/supervisor.blade.php
- [X] T009 [P] [US1] Update IT Head dashboard with classification KPI cards and major meeting indicators in resources/views/dashboard/it_head.blade.php
- [X] T010 [P] [US1] Update IT Developer dashboard with active task KPI cards and queue table in resources/views/dashboard/it_developer.blade.php
- [X] T011 [US1] Polish role dashboard router and empty-state illustrations in resources/views/dashboard.blade.php

**Checkpoint**: User Story 1 berfungsi penuh secara mandiri sebagai MVP rilis visual pertama.

---

## Phase 4: User Story 2 - Visualisasi Stepper Progres 5-Tahap pada Detail Tiket (Priority: P2)

**Goal**: Menyajikan komponen visual *Workflow Stepper* 5-tahap (Tahap A s/d E) pada halaman detail tiket untuk memberikan kejelasan posisi status tiket secara instan.

**Independent Test**: Buka halaman rincian tiket (`/tickets/{id}`) pada berbagai status (Tahap B, Tahap C Major, Tahap D antrean, dan Tahap E selesai); verifikasi centang hijau tahap selesai, penanda biru aktif berdenyut halus, dan penanda abu-abu netral untuk tahap tertunda.

### Implementation for User Story 2

- [X] T012 [US2] Create 5-stage visual workflow stepper component mapping 9 ticket statuses to stages A-E in resources/views/components/ticket-stepper.blade.php
- [X] T013 [US2] Embed stepper component and polish ticket header metadata card in resources/views/tickets/show.blade.php
- [X] T014 [P] [US2] Polish audit log vertical timeline styling with bullet timestamps in resources/views/tickets/partials/audit-history.blade.php
- [X] T015 [P] [US2] Polish developer activity timeline styling with clean status icons in resources/views/tickets/partials/activity-timeline.blade.php

**Checkpoint**: User Story 1 dan User Story 2 keduanya berfungsi secara mandiri dan terintegrasi harmonis.

---

## Phase 5: User Story 3 - Penyempurnaan Formulir & Modal Interaksi Tindakan Kritis (Priority: P3)

**Goal**: Mengintegrasikan dialog modal interaktif terfokus untuk seluruh tindakan keputusan alur kerja (Approve, Reject, Revise, Complete, Sign-off), area dropzone berkas unggahan interaktif pada formulir, dan tabel daftar tiket modern.

**Independent Test**: Buka halaman detail tiket sebagai Supervisor / IT Head / Dev / Requester dan picu dialog modal aksi; uji penutupan dengan tombol ESC dan backdrop klik, serta pengiriman form dengan alasan wajib. Uji dropzone unggah berkas pada formulir pembuatan tiket.

### Implementation for User Story 3

- [X] T016 [P] [US3] Create interactive file dropzone component with client-side preview in resources/views/components/file-dropzone.blade.php
- [X] T017 [US3] Integrate file dropzone and clean enterprise layout in ticket creation form in resources/views/tickets/create.blade.php
- [X] T018 [US3] Integrate file dropzone and revision feedback alert in ticket revision form in resources/views/tickets/edit.blade.php
- [X] T019 [US3] Implement interactive modal dialogs for Supervisor decisions (Approve, Reject, Revise) in resources/views/tickets/show.blade.php
- [X] T020 [US3] Implement interactive modal dialogs for IT Head decisions (Minor, Major, Reject, Revise) in resources/views/tickets/show.blade.php
- [X] T021 [US3] Implement interactive modal dialogs for IT Developer completion and Requester review in resources/views/tickets/show.blade.php
- [X] T022 [P] [US3] Modernize ticket index data table with search filters, subtle row hover, and soft status badges in resources/views/tickets/index.blade.php

**Checkpoint**: Seluruh user story (US1, US2, US3) telah lengkap dan dapat dioperasikan secara penuh.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Verifikasi build frontend, keselarasan warna WCAG AA, dan pemastian 100% tes otomatis lulus tanpa regresi

- [X] T023 [P] Compile production frontend assets via Vite in resources/css/app.css and resources/js/app.js
- [X] T024 [P] Polish authentication login card and quick-login helpers in resources/views/auth/login.blade.php
- [X] T025 Execute end-to-end verification scenarios per specs/002-enhancement/quickstart.md
- [X] T026 Run full test suite with zero warnings and regressions in tests/Feature and tests/Unit

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Tanpa dependensi - selesai.
- **Foundational (Phase 2)**: Bergantung pada Phase 1 - selesai.
- **User Stories (Phase 3+)**: Bergantung pada Phase 2 - selesai.
- **Polish (Phase 6)**: Bergantung pada User Stories - selesai.

### User Story Dependencies

- **User Story 1 (P1)**: Selesai.
- **User Story 2 (P2)**: Selesai.
- **User Story 3 (P3)**: Selesai.

---

## Parallel Opportunities

- **Phase 1**: T001 dan T002 selesai.
- **Phase 2**: T004 dan T005 selesai.
- **Phase 3 (US1)**: T007, T008, T009, T010 selesai.
- **Phase 4 (US2)**: T014 dan T015 selesai.
- **Phase 5 (US3)**: T016 dan T022 selesai.
- **Phase 6**: T023 dan T024 selesai.

---

## Implementation Strategy

### MVP First (User Story 1 Saja)
1. Phase 1 (Setup) dan Phase 2 (Foundational Layout & KPI Component) [X]
2. Phase 3 (User Story 1: Collapsible Sidebar & 4 Role Dashboards) [X]

### Pengiriman Inkremental (Incremental Delivery)
1. **Inkrement 1**: Phase 1 + 2 + 3 → Navigasi Sidebar Modern + Dashboard 4 Peran (MVP) [X]
2. **Inkrement 2**: Phase 4 → 5-Stage Stepper visual alur kerja pada detail tiket [X]
3. **Inkrement 3**: Phase 5 → Modal dialog interaktif keputusan alur kerja & Dropzone formulir [X]
4. **Inkrement 4**: Phase 6 → Kompilasi final Vite & verifikasi 60 test otomatis 100% bebas regresi [X]
