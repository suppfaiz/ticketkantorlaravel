# Data Model: IT Helpdesk Internal Perusahaan

**Feature**: Aplikasi IT Helpdesk Internal Perusahaan  
**Directory**: `specs/001-it-helpdesk`  
**Date**: 2026-10-28  

Dokumen ini mendefinisikan skema data relasional, aturan validasi, integritas referensial, dan kamus status untuk implementasi berbasis Laravel Eloquent dan PostgreSQL/MySQL.

---

## 1. Status Lifecycle Enum (`TicketStatus`)

Sistem menggunakan 15 status terdaftar yang mencerminkan tahapan alur bisnis 5 fase:

| Status Code / Value | Fase Bisnis | Deskripsi Singkat |
|---|---|---|
| `Draft` | Tahap A | Tiket disimpan oleh Karyawan namun belum resmi diajukan |
| `Menunggu Approval Atasan` | Tahap A/B | Tiket telah diajukan dan menunggu tindakan Atasan Langsung |
| `Revisi Requester` | Tahap B | Atasan Langsung meminta perbaikan data kepada Requester |
| `Ditolak Atasan` | Tahap B | Tiket ditolak resmi oleh Atasan Langsung (Final, tidak aktif) |
| `Menunggu Approval Kepala Bagian IT` | Tahap B/C | Telah disetujui Atasan Langsung, menunggu evaluasi Kabag IT |
| `Revisi Requester oleh Kepala Bagian IT` | Tahap C | Kabag IT meminta perbaikan; submit ulang wajib melalui atasan kembali |
| `Ditolak Kepala Bagian IT` | Tahap C | Ditolak resmi oleh Kabag IT (Final, tidak aktif) |
| `Menunggu Rapat Major` | Tahap C | Diklasifikasikan Major; menunggu penjadwalan/pelaksanaan rapat koordinasi |
| `Menunggu Notulensi` | Tahap C | Rapat selesai; menunggu pengisian notulensi ke sistem |
| `Menunggu Persetujuan Notulensi` | Tahap C | Notulensi telah diinput; menunggu verifikasi & persetujuan Kabag IT |
| `Siap Dikerjakan` | Tahap C/D | Disetujui (Minor/Major-approved); berada di antrean pengerjaan Tim IT |
| `Dalam Pengerjaan` | Tahap D | Diambil oleh Developer IT via *self-assign* dan sedang ditangani |
| `Menunggu Review User` | Tahap D/E | Pekerjaan IT selesai; menunggu pemeriksaan dan persetujuan Requester |
| `Revisi Hasil Pekerjaan` | Tahap E | Requester meminta perbaikan; tiket otomatis kembali ke developer asal |
| `Selesai` | Tahap E | Tiket diterima oleh Requester dan ditutup secara permanen |

---

## 2. Definisi Entitas & Skema Tabel

### 2.1. Tabel `users`
Menyimpan identitas pengguna dan penugasan peran dalam struktur organisasi.

```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL CHECK (role IN ('requester', 'supervisor', 'it_head', 'it_developer', 'admin')),
    department_id BIGINT NULL REFERENCES departments(id) ON DELETE SET NULL,
    supervisor_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_supervisor_id ON users(supervisor_id);
CREATE INDEX idx_users_department_id ON users(department_id);
```

### 2.2. Tabel `departments`
Menyimpan master data unit kerja / departemen karyawan.

```sql
CREATE TABLE departments (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    code VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);
```

### 2.3. Tabel `tickets`
Entitas transaksi utama permohonan IT Helpdesk.

```sql
CREATE TABLE tickets (
    id BIGSERIAL PRIMARY KEY,
    ticket_number VARCHAR(50) NOT NULL UNIQUE,
    requester_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    supervisor_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    category VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    classification VARCHAR(20) NULL CHECK (classification IN ('Minor', 'Major')),
    priority VARCHAR(20) NULL CHECK (priority IN ('Low', 'Medium', 'High', 'Urgent')),
    status VARCHAR(60) NOT NULL DEFAULT 'Menunggu Approval Atasan',
    assigned_to BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    submitted_at TIMESTAMP WITH TIME ZONE NULL,
    approved_by_supervisor_at TIMESTAMP WITH TIME ZONE NULL,
    approved_by_it_head_at TIMESTAMP WITH TIME ZONE NULL,
    work_started_at TIMESTAMP WITH TIME ZONE NULL,
    work_completed_at TIMESTAMP WITH TIME ZONE NULL,
    closed_at TIMESTAMP WITH TIME ZONE NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_tickets_ticket_number ON tickets(ticket_number);
CREATE INDEX idx_tickets_requester_id ON tickets(requester_id);
CREATE INDEX idx_tickets_supervisor_id ON tickets(supervisor_id);
CREATE INDEX idx_tickets_assigned_to ON tickets(assigned_to);
CREATE INDEX idx_tickets_status ON tickets(status);
CREATE INDEX idx_tickets_classification ON tickets(classification);
```

### 2.4. Tabel `ticket_attachments`
Menyimpan berkas lampiran pendukung tiket pada penyimpanan privat.

```sql
CREATE TABLE ticket_attachments (
    id BIGSERIAL PRIMARY KEY,
    ticket_id BIGINT NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    uploader_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    file_name VARCHAR(255) NOT NULL,
    stored_path VARCHAR(500) NOT NULL,
    file_size_bytes BIGINT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_ticket_attachments_ticket_id ON ticket_attachments(ticket_id);
```

### 2.5. Tabel `meeting_minutes`
Menyimpan notulensi dan kesepakatan ruang lingkup untuk tiket berkategori Major (Relasi 1:1 dengan tiket).

```sql
CREATE TABLE meeting_minutes (
    id BIGSERIAL PRIMARY KEY,
    ticket_id BIGINT NOT NULL UNIQUE REFERENCES tickets(id) ON DELETE CASCADE,
    author_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    meeting_date DATE NOT NULL,
    attendees TEXT NOT NULL,
    scope_summary TEXT NOT NULL,
    deliverables TEXT NOT NULL,
    target_completion_date DATE NULL,
    attachment_path VARCHAR(500) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Menunggu Persetujuan Notulensi' CHECK (status IN ('Draft', 'Menunggu Persetujuan Notulensi', 'Disetujui')),
    approved_by BIGINT NULL REFERENCES users(id) ON DELETE RESTRICT,
    approved_at TIMESTAMP WITH TIME ZONE NULL,
    approval_notes TEXT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_meeting_minutes_ticket_id ON meeting_minutes(ticket_id);
```

### 2.6. Tabel `ticket_activity_logs`
Menyimpan catatan penanganan berkala, milestone, dan laporan teknis Tim IT.

```sql
CREATE TABLE ticket_activity_logs (
    id BIGSERIAL PRIMARY KEY,
    ticket_id BIGINT NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    activity_type VARCHAR(50) NOT NULL DEFAULT 'progress_note',
    content TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_ticket_activity_logs_ticket_id ON ticket_activity_logs(ticket_id);
```

### 2.7. Tabel `ticket_reviews`
Menyimpan hasil review akhir, keputusan *accept/revisi*, dan ulasan kepuasan dari Requester.

```sql
CREATE TABLE ticket_reviews (
    id BIGSERIAL PRIMARY KEY,
    ticket_id BIGINT NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    reviewer_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    decision VARCHAR(20) NOT NULL CHECK (decision IN ('Accept', 'Revisi')),
    feedback TEXT NOT NULL,
    rating SMALLINT NULL CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_ticket_reviews_ticket_id ON ticket_reviews(ticket_id);
```

### 2.8. Tabel `ticket_audit_logs` (Append-Only)
Menampung jejak audit hukum dan kepatuhan sistem yang bersifat kekal (*tamper-resistant*).

```sql
CREATE TABLE ticket_audit_logs (
    id BIGSERIAL PRIMARY KEY,
    ticket_id BIGINT NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    actor_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    action VARCHAR(100) NOT NULL,
    from_status VARCHAR(60) NULL,
    to_status VARCHAR(60) NULL,
    remarks TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload_diff JSONB NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_ticket_audit_logs_ticket_id ON ticket_audit_logs(ticket_id);
CREATE INDEX idx_ticket_audit_logs_actor_id ON ticket_audit_logs(actor_id);
CREATE INDEX idx_ticket_audit_logs_created_at ON ticket_audit_logs(created_at);
```

---

## 3. Relasi Antar-Model (Eloquent Relationships)

- **User**:
  - `hasMany(Ticket::class, 'requester_id')` → Tiket yang diajukan.
  - `hasMany(Ticket::class, 'supervisor_id')` → Tiket bawahan yang menjadi tanggung jawab approval.
  - `hasMany(Ticket::class, 'assigned_to')` → Tiket yang sedang ditangani developer.
  - `belongsTo(User::class, 'supervisor_id')` → Atasan langsung pengguna.
  - `hasMany(User::class, 'supervisor_id')` → Daftar bawahan langsung.
  - `belongsTo(Department::class)` → Departemen/unit kerja pengguna.
- **Ticket**:
  - `belongsTo(User::class, 'requester_id')`
  - `belongsTo(User::class, 'supervisor_id')`
  - `belongsTo(User::class, 'assigned_to')`
  - `hasMany(TicketAttachment::class)`
  - `hasOne(MeetingMinutes::class)`
  - `hasMany(TicketActivityLog::class)`
  - `hasMany(TicketReview::class)`
  - `hasMany(TicketAuditLog::class)`
- **MeetingMinutes**:
  - `belongsTo(Ticket::class)`
  - `belongsTo(User::class, 'author_id')`
  - `belongsTo(User::class, 'approved_by')`
- **TicketAuditLog**:
  - `belongsTo(Ticket::class)`
  - `belongsTo(User::class, 'actor_id')`
  - Tidak memiliki method delete/update pada policy/model (hanya create & read).
