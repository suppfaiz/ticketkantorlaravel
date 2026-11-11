<!--
Sync Impact Report:
- Version Change: 3.1.0 -> 3.1.1 (Clarification of Requester Supervisor Resolution on SSO Provisioning & Submission Guard)
- Rationale: Non-breaking PATCH refinement clarifying the supervisor resolution requirement for ticket submission compliance. Requesters provisioned via corporate SSO without an explicit organizational supervisor attribute must automatically resolve to an active departmental supervisor to ensure unbroken LOA approval workflow dispatch.
- Modified Sections:
  - Authorization & Role-Based Access Control (RBAC): Added mandatory supervisor linkage requirement for Karyawan / Requester.
  - Tahap A — Pengajuan Request (Submission): Explicitly codified automatic supervisor fallback and pre-submission supervisor association invariant.
- Governance Dates:
  - Ratified: 2026-10-28
  - Last Amended: 2026-10-30
- Follow-up TODOs: None.
-->

# IT Helpdesk Internal Perusahaan Constitution

## Core Principles

### I. Security-First & Least Privilege (NON-NEGOTIABLE)
Security is a foundational functional requirement for all features, never an afterthought. Every layer of the system MUST enforce least-privilege access, strict input validation, automatic output escaping, CSRF protection, and defense against OWASP Top 10 vulnerabilities. Unauthenticated or unauthorized access MUST be denied by default on the server side. UI controls (such as hidden buttons or disabled links) MUST NOT be treated as security boundaries.

### II. Business Workflow Integrity & Concurrency Safety
The IT Helpdesk workflow is an inviolable state machine enforced strictly by server-side domain logic and database transactions. State transitions MUST follow the defined sequence:
- **Jalur Minor**:
  `User (Submission) → Approval LOA Atasan (API) → Approval & Klasifikasi Kepala Bagian IT → Pool Siap Progress (Self-Assign) → Pengerjaan Tim IT → Review & Sign-Off User → Selesai (Closed).`
  *(Tiket Minor langsung masuk ke Pool Siap Progress setelah disetujui Kabag IT; tidak melalui LOA Kadiv IT & Ops, LOA Direksi, rapat, maupun notulensi).*
- **Jalur Major**:
  `User (Submission) → Approval LOA Atasan (API) → Approval & Klasifikasi Kepala Bagian IT → LOA Kepala Divisi IT dan Operasional (API) → LOA Direksi (API) → Rapat oleh Kepala Bagian IT → Input Notulensi oleh User → Approval Notulensi oleh Kepala Bagian IT → Pool Siap Progress (Self-Assign) → Pengerjaan Tim IT → Review & Sign-Off User → Selesai (Closed).`

Tickets MUST NOT bypass mandatory approval stages. Setelah memperoleh LOA Direksi, tiket Major DILARANG LANGSUNG masuk ke Pool Siap Progress sebelum ketiga tahapan rapat dan notulensi diselesaikan. Pengambilan tiket di Pool Siap Progress oleh Tim IT wajib atomik dan terlindung dari konkurensi (menggunakan `lockForUpdate`).

### III. Enterprise SSO as Authentication Source of Truth (JWT Non-Negotiable)
The IT Helpdesk application MUST authenticate all users exclusively through the existing corporate Single Sign-On (SSO) API returning JSON Web Tokens (JWT). The application MUST NOT build an isolated or parallel username/password authentication mechanism. Token validation (signature, issuer, audience, and expiration) MUST be enforced strictly on the server side. Frontend claims MUST NEVER be trusted blindly. JWT tokens MUST be stored securely using server sessions or `HttpOnly`, `Secure`, and `SameSite` cookies; storing JWT tokens in browser `localStorage` is strictly PROHIBITED. Credentials and tokens MUST NEVER be logged or leaked into application outputs.

### IV. Existing LOA Services as Approval Authority Source (Delegated Orchestration)
The IT Helpdesk application functions as a ticket orchestrator and business connector, while approval decisions for Line of Authority (LOA) MUST originate from existing corporate LOA services via API. The system MUST NOT rebuild an internal LOA approval engine when services already exist. External LOA transactions MUST record transaction references, sync statuses, and dispatch timestamps to guarantee end-to-end auditability.

### V. API Integration Resilience, Idempotency & Fault Tolerance
All outbound API communication with corporate SSO and LOA systems MUST be conducted server-side. The application MUST gracefully handle network partitions, timeouts, and HTTP 5xx errors. The system MUST NOT treat a simple successful HTTP dispatch as a business approval confirmation; approval decisions MUST be officially verified by the source application. To prevent duplicate LOA submissions caused by network retries, all outbound submissions MUST enforce unique idempotency keys (`Idempotency-Key` or unique transaction references).

### VI. Strict Server-Side Authorization & Deny-by-Default
All operations and data access MUST be governed by server-side Laravel Policies, Gates, and authorization middleware. Access to tickets MUST be verified against object ownership and organizational hierarchy to eliminate Insecure Direct Object References (IDOR/BOLA). System administrator privileges DO NOT grant unilateral rights to approve or sign off on business tickets. Every role-based permission check MUST fail closed (deny-by-default).

### VII. Test-Driven Verification & Quality Assurance
Every business workflow path, authorization rule, integration client, and state transition MUST be accompanied by comprehensive tests prior to acceptance. Automated tests MUST cover positive flows, negative access scenarios (unauthorized user actions), concurrent self-assign conflicts, post-Direksi meeting/minutes flows, API failure/timeout simulations, and transaction rollbacks. A feature without automated tests is considered incomplete and prohibited from merging into production.

### VIII. Architectural Simplicity, Maintainability & YAGNI
The application MUST prioritize simplicity, readability, and maintainability over complex abstractions. Architecture MUST leverage native Laravel conventions (Form Requests, Eloquent Models, Policies, Blade components) without introducing redundant third-party libraries, complex Single Page Application (SPA) frameworks, or premature caching/queueing systems until concrete performance metrics demand them. "You Aren't Gonna Need It" (YAGNI) applies across the entire codebase.

### IX. Tamper-Resistant Audit Trail & Observability
Every critical business decision—including ticket creation, SSO login events, LOA Atasan API dispatch/decision, IT Head classification, LOA Kadiv IT & Ops API dispatch/decision, LOA Direksi API dispatch/decision, meeting details scheduling, user minutes submission, IT Head minutes approval/revision, developer self-assign, progress notes, and user review sign-offs—MUST be recorded in an immutable, tamper-resistant audit trail (`TicketAuditLog`). Logs MUST capture the actor, timestamp, action, object ID, decision outcome, external transaction references, and remarks, while strictly preventing sensitive data (passwords, JWT tokens, personal secrets) from entering logs.

---

## Technology Stack & Architectural Constraints

1. **Backend**: Laravel (latest stable release compatible with supported PHP versions).
2. **Frontend**: Laravel Blade templates with a clean, responsive, lightweight UI approach (Clean Minimalist Enterprise Light Mode). No external frontend SPA frameworks (React, Vue, Angular) shall be introduced without documented justification.
3. **Database**: A single relational database engine (PostgreSQL or MySQL) managed exclusively via Laravel Database Migrations.
4. **Authentication**: Enterprise SSO integration utilizing corporate JWT login API, managed securely via server-side session or HttpOnly cookie storage.
5. **Authorization**: Native Laravel Policies, Gates, and middleware validating every request on the server.
6. **External Integration Client**: Server-side HTTP Client (Laravel `Http` facade) with strict timeouts, retry mechanisms, and idempotency headers.
7. **Testing**: Automated testing using PHPUnit or Pest covering unit, integration, and HTTP feature scenarios.
8. **Dependency Management**: Composer with locked dependencies (`composer.lock`) committed to version control.

---

## Authentication, Account Security & Access Control

### 1. SSO JWT Authentication & Session Security
- Authentication MUST call the corporate SSO login endpoint.
- Server-side validation MUST verify JWT signature, issuer, audience, and expiration before establishing user context.
- Use secure, HTTP-only, SameSite session cookies over HTTPS.
- Session IDs MUST be regenerated upon login and invalidated upon logout to prevent session fixation.
- Authentication tokens or credentials MUST NOT be stored in browser `localStorage` or `sessionStorage`.
- Token expiration, invalid signature, or SSO service failure MUST fail closed, returning safe error messages without exposing system internals.
- Passwords and raw JWT tokens MUST NEVER be stored in plaintext or recorded in application log files.

### 2. Authorization & Role-Based Access Control (RBAC)
The application MUST enforce distinct business roles with clear boundaries:
- **Karyawan / Requester**: Create tickets, view own ticket status, input meeting minutes post-Direksi LOA, revise returned requests/minutes, review finished work, and provide acceptance feedback. Every requester MUST be associated with an active direct supervisor to ensure submission eligibility and LOA dispatch compliance.
- **Atasan Langsung (Direct Supervisor)**: Authorize or reject tickets submitted by subordinates via existing corporate LOA API.
- **Kepala Bagian IT (IT Head)**: Review supervisor-approved tickets, classify into Minor or Major, approve/reject/request revisions, schedule/conduct post-Direksi Major meetings, and review/approve user-submitted meeting minutes.
- **Kepala Divisi IT & Operasional (Kadiv IT & Ops)**: Executive authority for Line of Authority (LOA) review of ALL Major requests following IT Head approval via corporate LOA API.
- **Direksi (Board of Directors)**: Executive authority for Line of Authority (LOA) review of ALL Major requests following Kadiv IT & Ops approval via corporate LOA API.
- **Tim IT / Developer**: View approved tickets in `Pool Siap Progress`, perform atomic self-assignment, update handling progress and activity logs, submit completed work for user review.
- **Administrator Sistem**: Manage system configurations and integration settings. Does NOT possess authority to perform business approvals or bypass workflow checks.

---

## Mandatory Business Workflow & Invariants

The application MUST strictly adhere to the following multi-tier business workflow. No interface shortcut, API endpoint, or direct database mutation may bypass these steps:

### Tahap A — Pengajuan Request (Submission)
1. Requester submits a support request containing title, description, category, and relevant details.
2. The system generates a collision-safe, unique ticket identification number (`TICK-YYYYMMDD-XXXX`).
3. The ticket is assigned initial status `Menunggu Approval Atasan`.
4. The system automatically associates the ticket to the requester's direct supervisor and dispatches an LOA request to the external LOA service. If a user provisioned via corporate SSO lacks an explicit supervisor attribute, the system MUST automatically resolve and associate the user with an active departmental supervisor prior to submission to guarantee workflow validity.

### Tahap B — Approval LOA Atasan Langsung (Supervisor Approval via API)
The direct supervisor approval is performed via the external LOA service:
- **Setujui (Approve)**: Confirmed approval advances ticket to `Menunggu Approval Kepala Bagian IT`.
- **Tolak (Reject)**: Confirmed rejection moves ticket to `Pool Tiket Ditolak` (`Ditolak Atasan`). Rejection reason is recorded, and workflow terminates.
- **Revisi (Revision)**: Ticket transitions to `Revisi Requester`. Requester modifies details and resubmits, re-entering the supervisor LOA workflow.

### Tahap C — Persetujuan dan Klasifikasi oleh Kepala Bagian IT (IT Head Review & Classification)
Setelah request disetujui oleh Atasan Langsung, Kepala Bagian IT meninjau request dan menetapkan klasifikasi:
- **Jalur Minor**:
  - **Setujui & Klasifikasikan Minor**: Tiket beralih seketika ke antrean `Siap Dikerjakan` (`Pool Siap Progress`). *Request Minor TIDAK PERLU melewati LOA Kadiv IT & Operasional, LOA Direksi, rapat, maupun notulensi*.
  - **Revisi**: Tiket beralih ke `Revisi Requester oleh Kepala Bagian IT`. Setelah diperbaiki pemohon, tiket wajib diverifikasi ulang oleh Atasan Langsung sebelum kembali ke Kabag IT.
  - **Tolak**: Tiket beralih ke `Ditolak Kepala Bagian IT` dengan alasan wajib.
- **Jalur Major**:
  - **Setujui & Klasifikasikan Major**: Tiket beralih ke status `Menunggu LOA Kadiv IT`. Sistem menginisiasi pengajuan LOA ke Kadiv IT & Operasional via API eksternal.
  - **Revisi**: Tiket dikembalikan untuk revisi pemohon.
  - **Tolak**: Tiket ditolak dengan alasan resmi.

### Tahap C.1 — Persetujuan LOA Kepala Divisi IT & Operasional (Khusus Request Major via API)
Setelah disetujui Kabag IT sebagai Major, sistem menginisiasi pengajuan LOA ke Kadiv IT & Operasional melalui API LOA eksternal:
1. **Setujui (LOA Kadiv IT & Ops Approved)**: Tiket Major secara otomatis melanjutkan pengajuan ke LOA Direksi via API eksternal.
2. **Tolak (Reject)**: Tiket beralih ke `Pool Tiket Ditolak` (`Ditolak Kadiv IT`) dan proses dihentikan permanen.
3. **Revisi**: Tiket dikembalikan ke tahap yang sesuai tanpa menghapus histori audit log sebelumnya.

### Tahap C.2 — Persetujuan LOA Direksi (Khusus Request Major via API)
Direksi merupakan otorisasi eksekutif lanjutan untuk request Major yang telah disetujui Kadiv IT & Ops:
1. **Setujui (LOA Direksi Approved)**: Tiket Major beralih ke status `Menunggu Pelaksanaan Rapat Major` (Tahap C.3). *TIKET DILARANG LANGSUNG MASUK KE POOL SIAP PROGRESS*.
2. **Tolak (Reject)**: Tiket beralih ke `Pool Tiket Ditolak` (`Ditolak Direksi`) dan proses dihentikan permanen.
3. **Revisi**: Tiket dikembalikan ke tahap yang sesuai tanpa menghapus histori audit log sebelumnya.

### Tahap C.3 — Penjadwalan dan Pelaksanaan Rapat (Khusus Request Major)
Setelah permohonan Major disetujui LOA Direksi:
1. Kepala Bagian IT mengadakan rapat koordinasi bersama user pemohon dan Kepala Bagian dari unit kerja yang mengajukan permohonan.
2. Rapat dilakukan untuk membahas dan memperjelas rincian kebutuhan teknis, batasan sistem, dan ruang lingkup yang telah disetujui Direksi.
3. Informasi pelaksanaan rapat dicatat ke dalam sistem Helpdesk (tanggal rapat, peserta, dan ringkasan arahan). Status tiket beralih ke `Menunggu Input Notulensi User`.

### Tahap C.4 — Input Notulensi oleh User Pemohon (Khusus Request Major)
1. Setelah rapat koordinasi terlaksana, user pemohon menginput notulensi hasil rapat (beserta dokumen pendukung jika ada) ke dalam aplikasi IT Helpdesk.
2. Notulensi wajib terhubung secara eksplisit dengan tiket Major yang bersangkutan.
3. Tiket berada pada status `Menunggu Input Notulensi User` dan WAJIB tertahan menunggu penginputan notulensi sebelum dapat melanjutkan ke tahap approval notulensi.
4. Setelah user pemohon mengirimkan notulensi, status tiket beralih ke `Menunggu Approval Notulensi Kabag IT`.

### Tahap C.5 — Approval Notulensi oleh Kepala Bagian IT (Khusus Request Major)
1. Notulensi yang telah diinput oleh user diteruskan kepada Kepala Bagian IT untuk diteliti dan diverifikasi.
2. **Keputusan Kabag IT**:
   - **Setujui (Notulensi Approved)**: Notulensi disahkan resmi. Tiket Major resmi beralih ke antrean `Siap Dikerjakan` (`Pool Siap Progress`).
   - **Revisi Notulensi**: Jika notulensi belum sesuai atau perlu perbaikan, tiket dikembalikan kepada user pemohon berstatus `Revisi Notulensi Major` disertai komentar/alasan wajib agar user mengetahui perbaikan yang diperlukan. User memperbaiki dan mengirimkan kembali notulensi.

### Tahap D — Antrean dan Pengerjaan Tim IT (Development Queue & Execution)
1. Approved tickets (Minor setelah disetujui Kabag IT, ATAU Major setelah disetujui Direksi + Rapat + Notulensi disetujui Kabag IT) masuk ke status `Siap Dikerjakan` di `Pool Siap Progress`.
2. IT Developers mengklaim tiket melalui mekanisme penguncian atomik mandiri (atomic self-assignment) menjadi `Dalam Pengerjaan`.
3. Concurrency Protection: Sistem WAJIB mengunci tiket selama proses klaim (`lockForUpdate`) guna menjamin tidak ada dua developer yang mengklaim tiket yang sama secara bersamaan.
4. Assigned developer mencatat pembaruan progres, catatan teknis, dan laporan aktivitas secara bertahap.
5. Setelah penanganan tuntas, developer mengalihkan status tiket menjadi `Menunggu Review User`.

### Tahap E — Review oleh User dan Penutupan (User Sign-off & Closure)
1. Requester memeriksa hasil pekerjaan/solusi yang diserahkan.
2. Requester menentukan salah satu keputusan:
   - **Accept**: Pemohon menerima hasil pengerjaan. Tiket beralih ke `Selesai` (Closed). Rating kepuasan (1-5), feedback, dan timestamp sign-off dicatat permanen.
   - **Revisi**: Pemohon menyatakan hasil belum sesuai kebutuhan. Tiket beralih ke `Revisi Hasil Pekerjaan` dengan feedback wajib, kembali secara otomatis ke developer penanggung jawab sebelumnya untuk diperbaiki.
3. *Invariant*: Tiket TIDAK BOLEH ditutup sebagai `Selesai` tanpa persetujuan eksplisit dari pemohon pembuat tiket.

---

## Workflow Invariants (Non-Negotiable Business Rules)

- **Minor Fast-Track Invariant**: Request minor langsung masuk ke `Pool Siap Progress` setelah disetujui Kepala Bagian IT tanpa perlu persetujuan Kadiv IT & Operasional, LOA Direksi, rapat, maupun notulensi.
- **Major Post-Direksi Barrier Invariant**: Request major yang telah disetujui LOA Direksi DILARANG LANGSUNG masuk ke `Pool Siap Progress`. Tiket Major WAJIB melalui: (1) Rapat oleh Kabag IT, (2) Input Notulensi oleh User, dan (3) Persetujuan Notulensi oleh Kabag IT.
- **Major Multi-Tier Approval Invariant**: Request major TIDAK DAPAT masuk ke `Pool Siap Progress` sebelum seluruh persetujuan (Kabag IT, Kadiv IT & Ops, Direksi, dan Notulensi Kabag IT) disetujui lengkap.
- **Exclusive User Minutes Input**: Penginputan notulensi hasil rapat Major pasca-LOA Direksi menjadi tanggung jawab user pemohon tiket.
- **No Premature Approval on Dispatch**: Pengiriman payload API ke sistem LOA eksternal TIDAK BOLEH dianggap sebagai persetujuan; keputusan tiket hanya berubah setelah ada konfirmasi resmi dari API sumber.
- **Idempotent External Submissions**: Pengiriman ulang (*retry*) ke API eksternal WAJIB menggunakan `Idempotency-Key` atau referensi unik yang sama untuk mencegah permohonan ganda.
- **Termination on Rejection**: Penolakan oleh Atasan Langsung, Kepala Bagian IT, Kadiv IT & Ops, atau Direksi menghentikan proses secara seketika dan memindahkan tiket ke `Pool Tiket Ditolak`.
- **User Sign-Off Sovereignty**: Tiket TIDAK BOLEH ditutup resmi sebagai `Selesai` tanpa persetujuan eksplisit dari pemohon pembuat tiket.
- **Zero Token Leakage**: JWT token, password, dan secret keys dilarang disimpan di `localStorage` peramban atau dicatat di berkas log sistem.

---

## Secure Coding & Architecture

1. **Separation of Concerns**: Controllers MUST remain lean, delegating request validation to Form Requests, authorization to Policies, external integrations to dedicated Services (`SsoService`, `LoaService`), and workflow state management to `TicketStateMachine`.
2. **Mass Assignment Protection**: Models MUST explicitly define `$fillable` or use guarded attributes. Sensitive attributes (`role`, `status`, `supervisor_id`, `approved_by_supervisor_at`, `approved_by_it_head_at`, `approved_by_kadiv_at`, `approved_by_direksi_at`, `assigned_to`, `minutes_approved_at`) MUST NEVER be mass-assigned from raw request payloads.
3. **Database Consistency**: All multi-step operations (e.g., ticket state transition coupled with audit logging and notification) MUST be wrapped in database transactions (`DB::transaction`).
4. **Injection Prevention**: All database interactions MUST utilize Eloquent ORM or parameterized query bindings. Raw unescaped SQL queries are strictly prohibited.
5. **Output Escaping**: Blade templates MUST use `{{ $var }}` default escaping. Unescaped `{!! $var !!}` is prohibited unless explicitly sanitized through an approved HTML sanitizer.
6. **File Upload Security**: Uploaded files (attachments, meeting minutes documents) MUST undergo strict server-side validation for file type (MIME check, not just extension), file size, and sanitized storage names. Uploads MUST be stored in private storage outside the public web root and served exclusively via authorized streaming endpoints.
7. **Schema Migrations**: All database schema changes MUST be managed through version-controlled Laravel migrations. Manual adjustments to production databases are strictly prohibited.

---

## Audit Trail, Logging & Observability

1. **Business Audit Trail**:
   - Every state transition, approval, rejection, revision, classification, meeting scheduling, user minutes submission, IT Head minutes approval/revision, external LOA dispatch/confirmation, self-assign, and user review sign-off MUST generate an immutable audit log entry in `TicketAuditLog`.
   - Audit records MUST include: `ticket_id`, `actor_id`, `action`, `from_status`, `to_status`, `comments_or_reason`, `external_transaction_id`, `ip_address`, and `timestamp`.
   - Audit trail records MUST NOT be editable or deletable.
2. **Technical Logging**:
   - Application errors, exceptions, and security events MUST be logged using structured logging (Monolog).
   - Sensitive data—including user passwords, JWT authorization tokens, client secrets, and session cookies—MUST be scrubbed from log contexts.
   - In production environments, `APP_DEBUG` MUST be set to `false`. Detailed stack traces MUST NEVER be returned to the client browser.

---

## Testing Strategy & Quality Assurance

Quality assurance MUST validate security boundaries, authorization matrices, integration clients, and business invariants through automated tests:

### 1. Mandatory Workflow & Approval Chain Tests
1. **Minor Happy Path**: Request minor disetujui Atasan (API) dan Kabag IT, kemudian langsung masuk ke `Pool Siap Progress`.
2. **Minor Bypass Kadiv & Rapat**: Request minor diverifikasi TIDAK memerlukan persetujuan Kadiv IT, Direksi, maupun tahapan rapat/notulensi.
3. **Major Happy Path**: Request major disetujui Atasan (API), Kabag IT, Kadiv IT & Ops (API), Direksi (API), kemudian melalui Rapat Kabag IT, Input Notulensi User, dan Persetujuan Notulensi Kabag IT, lalu berhasil masuk ke `Pool Siap Progress`.
4. **Major Post-Direksi Barrier**: Request major diverifikasi TIDAK DAPAT langsung masuk ke `Pool Siap Progress` setelah disetujui Direksi sebelum menyelesaikan tahapan rapat dan approval notulensi.
5. **Major Kadiv Bypass Barrier**: Request major diverifikasi TIDAK DAPAT melompati persetujuan Kadiv IT & Ops (tidak dapat langsung diajukan ke Direksi).
6. **Termination on Rejection**: Penolakan pada tingkat manapun (Atasan, Kabag IT, Kadiv IT & Ops, Direksi) menghentikan proses seketika dan memindahkan tiket ke `Pool Tiket Ditolak`.
7. **Revision History Integrity**: Permintaan revisi pada tahap approval maupun notulensi mengembalikan tiket ke proses yang benar tanpa menghapus histori audit log sebelumnya.

### 2. Integration & Security Tests
- **SSO Authentication Lifecycle**: Valid JWT token returns authenticated session; invalid token or expired signature is safely rejected; zero JWT stored in `localStorage`; zero JWT in log files.
- **External LOA Client**: Outbound request includes `Idempotency-Key`; timeout or network failure does not cause premature approval/rejection; retry uses identical idempotency key.
- **Concurrent Self-Assignment Test**: Verification that simultaneous requests to claim a ticket result in exactly one successful assignment and a graceful failure for competing requests (`lockForUpdate`).
- **User Review Sign-off**: Verification that only the requester can accept and close the ticket, and rejection/revision returns ticket to the developer.

---

## Governance

1. **Supremacy**: This Constitution represents the supreme architectural and development standard for the IT Helpdesk application. All specifications (`spec.md`), implementation plans (`plan.md`), task definitions (`tasks.md`), and pull requests MUST comply with the rules established herein.
2. **Amendment Procedure**:
   - Amendments to this Constitution require a documented change proposal specifying the rationale, affected sections, and migration impact.
   - Proposed amendments MUST be reviewed and formally approved by project governance stakeholders before taking effect.
   - Any approved modification requires a version increment and recording in the project changelog.
3. **Semantic Versioning for Governance**:
   - **MAJOR (X.0.0)**: Breaking governance changes, fundamental architectural shifts, or redefinition/removal of core principles.
   - **MINOR (1.X.0)**: Addition of new principles, material expansion of workflow rules, or introduction of new compliance domains.
   - **PATCH (1.0.X)**: Wording refinements, grammatical corrections, and non-semantic formatting adjustments.
4. **Compliance Verification**: All automated pull request checks, code reviews, and Spec Kit validation commands (`/speckit-analyze`, `/speckit-plan`, `/speckit-implement`) MUST verify compliance with this Constitution.

---

**Version**: 3.1.1 | **Ratified**: 2026-10-28 | **Last Amended**: 2026-10-30
