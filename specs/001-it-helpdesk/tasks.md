# Tasks: Aplikasi IT Helpdesk Internal Perusahaan

**Input**: Design documents from `specs/001-it-helpdesk/`  
**Prerequisites**: `plan.md`, `spec.md`, `data-model.md`, `contracts/`, `research.md`, `quickstart.md`, `DESIGN.md`  
**Constitution**: Enforces Security-First, Business Workflow Integrity, Deny-by-default RBAC, Test-Driven Verification, and Tamper-Resistant Audit Trail per `.specify/memory/constitution.md`.  
**Visual Design**: Clean Minimalist Enterprise Light Mode (dasar putih `#FFFFFF`, border halus `#E2E8F0`, teks `#0F172A`) per `DESIGN.md`.

---

## Format: `- [ ] [ID] [P?] [Story?] Description with file path`

- **[P]**: Dapat dikerjakan secara paralel (file berbeda, tidak ada ketergantungan tugas yang belum selesai)
- **[Story]**: Label pemetaan ke User Story (`[US1]` s/d `[US5]`)
- Setiap tugas wajib menyertakan path file yang jelas

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Inisialisasi proyek Laravel, konfigurasi dependensi, dan tema antarmuka Light Mode dasar putih.

- [X] T001 Initialize Laravel application structure with Composer and environment configuration in `.env.example`
- [X] T002 [P] Install and configure Laravel Breeze with Blade authentication stack in `composer.json` and `package.json`
- [X] T003 [P] Configure Tailwind CSS with Clean Minimalist Enterprise Light Mode palette per `DESIGN.md` in `tailwind.config.js` and `resources/css/app.css`
- [X] T004 [P] Configure database connection for PostgreSQL/MySQL in `config/database.php` and `.env`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Infrastruktur inti (skema basis data, model dasar, transisi status state machine, dan kebijakan otorisasi) yang **WAJIB** selesai sebelum implementasi user story apa pun dapat dimulai.

**⚠️ CRITICAL**: Seluruh User Story bergantung pada fondasi tabel, status enums, dan state machine di fase ini.

- [X] T005 Create database migrations for `departments` table in `database/migrations/2026_10_28_000001_create_departments_table.php`
- [X] T006 Create database migration updating `users` table with `role` (enum: 'requester','supervisor','it_head','it_developer','admin'), `department_id`, and `supervisor_id` in `database/migrations/0001_01_01_000000_create_users_table.php`
- [X] T007 Create database migrations for `tickets` table with 15 status lifecycle values, classification ('Minor','Major'), priority ('Low','Medium','High','Urgent'), and unique `ticket_number` in `database/migrations/2026_10_28_000002_create_tickets_table.php`
- [X] T008 [P] Create database migration for `ticket_audit_logs` append-only table in `database/migrations/2026_10_28_000007_create_ticket_audit_logs_table.php`
- [X] T009 [P] Create Eloquent models `Department` and `User` with organizational hierarchy relationships in `app/Models/Department.php` and `app/Models/User.php`
- [X] T010 [P] Create Eloquent model `Ticket` with `$fillable`, status casts, and relation methods in `app/Models/Ticket.php`
- [X] T011 [P] Create Eloquent model `TicketAuditLog` without update/delete capabilities in `app/Models/TicketAuditLog.php`
- [X] T012 Implement `AuditTrailService` to record immutable events in `app/Services/AuditTrailService.php`
- [X] T013 Implement `TicketStateMachine` enforcing the 18 validated state transitions and guards in `app/Services/TicketStateMachine.php`
- [X] T014 [P] Implement `TicketPolicy` enforcing deny-by-default and IDOR prevention rules in `app/Policies/TicketPolicy.php`
- [X] T015 Create base database seeder with default organizational users (requester, supervisor, it_head, it_developer, admin) in `database/seeders/DatabaseSeeder.php`
- [X] T016 [P] Create base Blade application layout `resources/views/layouts/app.blade.php` adopting the minimalist light mode per `DESIGN.md`

**Checkpoint**: Fondasi arsitektur siap — implementasi User Story dapat dimulai secara independen.

---

## Phase 3: User Story 1 - Pengajuan Tiket & Approval Atasan Langsung (Priority: P1) 🎯 MVP

**Goal**: Karyawan dapat membuat tiket bantuan IT dengan nomor tiket unik dan upload berkas; tiket diteruskan ke Atasan Langsung untuk disetujui (ke Kabag IT), ditolak (dengan alasan wajib), atau diminta revisi.  
**Independent Test**: Jalankan `php artisan test --filter=SupervisorApprovalTest` untuk membuktikan pengajuan tiket, perutean ke atasan langsung yang benar, dan 3 cabang keputusan atasan (Setujui, Tolak, Revisi).

### Tests for User Story 1

- [X] T017 [P] [US1] Unit test for ticket number generation and state machine transitions in `tests/Unit/TicketStateMachineTest.php`
- [X] T018 [P] [US1] Feature test for ticket submission and supervisor approval/rejection/revision in `tests/Feature/SupervisorApprovalTest.php`

### Implementation for User Story 1

- [X] T019 [P] [US1] Create database migration for `ticket_attachments` in `database/migrations/2026_10_28_000003_create_ticket_attachments_table.php`
- [X] T020 [P] [US1] Create Eloquent model `TicketAttachment` in `app/Models/TicketAttachment.php`
- [X] T021 [US1] Implement `TicketService` for collision-safe sequential ticket numbering (`TICK-YYYYMMDD-XXXX`) and submission logic in `app/Services/TicketService.php`
- [X] T022 [P] [US1] Create Form Requests `StoreTicketRequest` and `UpdateTicketRequest` with server-side validation in `app/Http/Requests/StoreTicketRequest.php` and `app/Http/Requests/UpdateTicketRequest.php`
- [X] T023 [P] [US1] Create Form Requests `SupervisorApprovalRequest`, `SupervisorRejectRequest`, and `SupervisorReviseRequest` in `app/Http/Requests/SupervisorApprovalRequest.php`, `app/Http/Requests/SupervisorRejectRequest.php`, and `app/Http/Requests/SupervisorReviseRequest.php`
- [X] T024 [US1] Implement `TicketController` for ticket index, create, store, show, and edit in `app/Http/Controllers/TicketController.php`
- [X] T025 [US1] Implement `SupervisorApprovalController` handling supervisor approve, reject, and revise actions in `app/Http/Controllers/SupervisorApprovalController.php`
- [X] T026 [P] [US1] Implement secure attachment download controller with policy check in `app/Http/Controllers/AttachmentController.php`
- [X] T027 [P] [US1] Create Blade views for ticket creation form and ticket details in `resources/views/tickets/create.blade.php` and `resources/views/tickets/show.blade.php`
- [X] T028 [P] [US1] Create Blade view for ticket editing post-revision in `resources/views/tickets/edit.blade.php`
- [X] T029 [US1] Register web routes for ticket submission and supervisor approval in `routes/web.php`

**Checkpoint**: User Story 1 (MVP) berfungsi penuh dan dapat diuji secara mandiri!

---

## Phase 4: User Story 2 - Persetujuan & Klasifikasi oleh Kepala Bagian IT (Priority: P2)

**Goal**: Kepala Bagian IT mengevaluasi tiket yang disetujui atasan, menentukan klasifikasi Minor/Major, menyetujui Minor langsung ke antrean `Siap Dikerjakan`, atau mengadakan rapat koordinasi Major dan menyetujui notulensi rapat sebelum tiket Major dapat masuk antrean.  
**Independent Test**: Jalankan `php artisan test --filter=ItHeadClassificationTest` dan `MeetingMinutesTest` untuk menguji jalur Minor langsung siap kerja, dan jalur Major yang wajib melalui input notulensi dan persetujuan notulensi oleh Kabag IT.

### Tests for User Story 2

- [X] T030 [P] [US2] Feature test for IT Head classification (Minor vs Major) and rejection/revision in `tests/Feature/ItHeadClassificationTest.php`
- [X] T031 [P] [US2] Feature test for Major meeting minutes submission and approval workflow in `tests/Feature/MeetingMinutesTest.php`

### Implementation for User Story 2

- [X] T032 [P] [US2] Create database migration for `meeting_minutes` table in `database/migrations/2026_10_28_000004_create_meeting_minutes_table.php`
- [X] T033 [P] [US2] Create Eloquent model `MeetingMinutes` in `app/Models/MeetingMinutes.php`
- [X] T034 [P] [US2] Create `MeetingMinutesPolicy` in `app/Policies/MeetingMinutesPolicy.php`
- [X] T035 [P] [US2] Create Form Requests `ItHeadClassifyRequest`, `ItHeadRejectRequest`, and `ItHeadReviseRequest` in `app/Http/Requests/ItHeadClassifyRequest.php`, `app/Http/Requests/ItHeadRejectRequest.php`, and `app/Http/Requests/ItHeadReviseRequest.php`
- [X] T036 [P] [US2] Create Form Requests `StoreMeetingMinutesRequest` and `ApproveMeetingMinutesRequest` in `app/Http/Requests/StoreMeetingMinutesRequest.php` and `app/Http/Requests/ApproveMeetingMinutesRequest.php`
- [X] T037 [US2] Implement `ItHeadApprovalController` for classification, approval, rejection, and revision in `app/Http/Controllers/ItHeadApprovalController.php`
- [X] T038 [US2] Implement `MeetingMinutesController` for minutes creation, submission, and approval in `app/Http/Controllers/MeetingMinutesController.php`
- [X] T039 [P] [US2] Create Blade partial view for meeting minutes section in `resources/views/tickets/partials/minutes-section.blade.php` and form in `resources/views/meeting-minutes/create.blade.php`
- [X] T040 [US2] Register web routes for IT Head classification and meeting minutes management in `routes/web.php`

**Checkpoint**: User Story 1 dan User Story 2 terintegrasi penuh.

---

## Phase 5: User Story 3 - Antrean Pengerjaan & Self-Assign oleh Tim IT (Priority: P3)

**Goal**: Anggota Tim IT melihat tiket berstatus `Siap Dikerjakan`, mengambil tiket secara mandiri (*self-assign*) dengan proteksi anti-tabrakan konkurensi (hanya satu developer yang berhasil mengambil tiket yang sama), mencatat laporan progres penanganan, dan menandai pekerjaan selesai ke tahap review user.  
**Independent Test**: Jalankan `php artisan test --filter=TicketConcurrencyTest` untuk memvalidasi keamanan klik bersamaan pada *self-assign*, serta pelaporan progres aktivitas.

### Tests for User Story 3

- [X] T041 [P] [US3] Integration test for atomic self-assignment concurrency collision in `tests/Feature/TicketConcurrencyTest.php`
- [X] T042 [P] [US3] Feature test for IT progress updates and work completion in `tests/Feature/TicketExecutionTest.php`

### Implementation for User Story 3

- [X] T043 [P] [US3] Create database migration for `ticket_activity_logs` in `database/migrations/2026_10_28_000005_create_ticket_activity_logs_table.php`
- [X] T044 [P] [US3] Create Eloquent model `TicketActivityLog` in `app/Models/TicketActivityLog.php`
- [X] T045 [P] [US3] Create Form Requests `StoreActivityLogRequest` and `CompleteWorkRequest` in `app/Http/Requests/StoreActivityLogRequest.php` and `app/Http/Requests/CompleteWorkRequest.php`
- [X] T046 [US3] Implement `TicketAssignmentController` with atomic conditional SQL update for self-assign in `app/Http/Controllers/TicketAssignmentController.php`
- [X] T047 [US3] Implement `TicketActivityController` for adding activity notes and marking work complete in `app/Http/Controllers/TicketActivityController.php`
- [X] T048 [P] [US3] Create Blade partial view for activity timeline in `resources/views/tickets/partials/activity-timeline.blade.php`
- [X] T049 [US3] Register web routes for self-assignment, activity logging, and work completion in `routes/web.php`

**Checkpoint**: Antrean pengerjaan IT dan pencatatan aktivitas berfungsi aman terhadap konkurensi.

---

## Phase 6: User Story 4 - Review Hasil Pekerjaan & Penutupan Tiket oleh Requester (Priority: P4)

**Goal**: Requester memeriksa hasil pekerjaan, memilih `Accept` (menutup tiket resmi ke status `Selesai` beserta feedback) atau `Revisi` (tiket otomatis kembali ke developer asal untuk diperbaiki tanpa re-assign acak).  
**Independent Test**: Jalankan `php artisan test --filter=UserReviewClosureTest` untuk memverifikasi penutupan tiket hanya dapat dilakukan oleh Requester, serta alur pengembalian revisi ke developer sebelumnya.

### Tests for User Story 4

- [X] T050 [P] [US4] Feature test for user review acceptance, closure, and revision feedback in `tests/Feature/UserReviewClosureTest.php`

### Implementation for User Story 4

- [X] T051 [P] [US4] Create database migration for `ticket_reviews` in `database/migrations/2026_10_28_000006_create_ticket_reviews_table.php`
- [X] T052 [P] [US4] Create Eloquent model `TicketReview` in `app/Models/TicketReview.php`
- [X] T053 [P] [US4] Create Form Requests `ReviewAcceptRequest` and `ReviewReviseRequest` in `app/Http/Requests/ReviewAcceptRequest.php` and `app/Http/Requests/ReviewReviseRequest.php`
- [X] T054 [US4] Implement `TicketReviewController` for user review decisions in `app/Http/Controllers/TicketReviewController.php`
- [X] T055 [P] [US4] Create Blade component for user review modal / form in `resources/views/tickets/partials/review-section.blade.php`
- [X] T056 [US4] Register web routes for ticket review acceptance and revision in `routes/web.php`

**Checkpoint**: Siklus hidup tiket dari submit hingga penutupan formal dan ulasan selesai.

---

## Phase 7: User Story 5 - Dashboard & Penelusuran Riwayat Aktivitas (Priority: P5)

**Goal**: Menyediakan dashboard terpersonalisasi untuk masing-masing peran (Requester, Atasan Langsung, Kepala Bagian IT, Tim IT) serta tampilan audit trail kronologis yang tidak dapat diedit/dihapus oleh pengguna.  
**Independent Test**: Jalankan `php artisan test --filter=DashboardAndAuditTest` untuk memverifikasi metrik kartu ringkasan dan riwayat audit per peran.

### Tests for User Story 5

- [X] T057 [P] [US5] Feature test for role-based dashboard metric queries and audit trail display in `tests/Feature/DashboardAndAuditTest.php`

### Implementation for User Story 5

- [X] T058 [US5] Implement `DashboardController` generating paginated metrics and ticket lists per role in `app/Http/Controllers/DashboardController.php`
- [X] T059 [P] [US5] Create Blade view for Requester dashboard in `resources/views/dashboard/requester.blade.php`
- [X] T060 [P] [US5] Create Blade view for Supervisor dashboard in `resources/views/dashboard/supervisor.blade.php`
- [X] T061 [P] [US5] Create Blade view for IT Head dashboard in `resources/views/dashboard/it_head.blade.php`
- [X] T062 [P] [US5] Create Blade view for IT Developer dashboard in `resources/views/dashboard/it_developer.blade.php`
- [X] T063 [P] [US5] Create Blade partial view for immutable audit trail list in `resources/views/tickets/partials/audit-history.blade.php`
- [X] T064 [US5] Register web routes for `/dashboard` and ticket list filtering in `routes/web.php`

**Checkpoint**: Dashboard operasional untuk 4 aktor bisnis dan penelusuran audit selesai.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Verifikasi pencegahan IDOR di seluruh endpoint, optimasi kueri (eager loading bebas N+1), audit visual Light Mode, dan validasi quickstart.

- [X] T065 [P] Feature test verifying IDOR prevention across all ticket endpoints in `tests/Feature/TicketAuthorizationTest.php`
- [X] T066 [P] Add database query optimization with eager loading (`with(['requester', 'supervisor', 'assignedTo'])`) in `app/Http/Controllers/TicketController.php`
- [X] T067 Verify all Blade views adhere strictly to Clean Minimalist Light Mode standards in `DESIGN.md` (white base `#FFFFFF`, clean cards, subtle borders)
- [X] T068 Execute full end-to-end quickstart validation scenarios per `specs/001-it-helpdesk/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Tidak ada dependensi — langsung dapat dimulai.
- **Foundational (Phase 2)**: Bergantung pada Setup (Phase 1) — **MEMBLOKIR** seluruh User Story.
- **User Story 1 (Phase 3 - P1)**: Bergantung pada Foundational (Phase 2). Merupakan **MVP**.
- **User Story 2 (Phase 4 - P2)**: Bergantung pada US1 (karena tiket yang masuk ke Kabag IT berasal dari approval Atasan).
- **User Story 3 (Phase 5 - P3)**: Bergantung pada US1 dan US2 (tiket di antrean IT berasal dari tiket yang disetujui Kabag IT).
- **User Story 4 (Phase 6 - P4)**: Bergantung pada US3 (review user hanya aktif setelah pekerjaan IT selesai).
- **User Story 5 (Phase 7 - P5)**: Bergantung pada komponen data US1-US4 untuk menampilkan metrik dan riwayat lengkap.
- **Polish (Phase 8)**: Bergantung pada seluruh User Story (US1-US5) telah selesai.

---

## Parallel Execution Examples

### Parallel Example: User Story 1
```bash
# Form Requests dapat dibuat secara paralel:
Task T022: StoreTicketRequest & UpdateTicketRequest
Task T023: SupervisorApprovalRequest, RejectRequest, ReviseRequest

# Model dan View dapat dikerjakan secara paralel:
Task T020: TicketAttachment model
Task T026: AttachmentController
Task T027: Blade views create.blade.php & show.blade.php
```

### Parallel Example: User Story 2
```bash
# Model MeetingMinutes dan Policy dapat dibuat paralel:
Task T033: MeetingMinutes model in app/Models/MeetingMinutes.php
Task T034: MeetingMinutesPolicy in app/Policies/MeetingMinutesPolicy.php
Task T035: ItHeadClassifyRequest, RejectRequest, ReviseRequest
Task T036: StoreMeetingMinutesRequest & ApproveMeetingMinutesRequest
```

---

## Implementation Strategy

### 1. MVP First (Phase 1 + Phase 2 + Phase 3)
1. Selesaikan Setup (T001 - T004) dan Fondasi (T005 - T016).
2. Implementasikan User Story 1 (T017 - T029).
3. **Uji Mandiri MVP**: Jalankan `php artisan test --filter=SupervisorApprovalTest`.
4. Karyawan sudah dapat mengajukan tiket dan atasan sudah dapat menyetujui/menolak/merevisi.

### 2. Pengiriman Tambahan Bertahap (Incremental Delivery)
- Tambahkan US2 (T030 - T040) → Evaluasi Kabag IT & Rapat Major aktif.
- Tambahkan US3 (T041 - T049) → Antrean IT & Self-assign konkurensi aman aktif.
- Tambahkan US4 (T050 - T056) → Review hasil oleh requester & penutupan tiket aktif.
- Tambahkan US5 (T057 - T064) → Dashboard & audit trail aktif.
- Polish (T065 - T068) → Pengerasan IDOR dan pengujian akhir.
