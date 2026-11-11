# Data Model & UI Presentation Structures: UI/UX Enhancement (002-enhancement)

**Feature Branch**: `002-enhancement`  
**Date**: 2026-10-28  
**Spec Reference**: [spec.md](./spec.md)

---

## 1. Backend Domain Entities (Unchanged Invariants)

Per Constitution Prinsip II dan V, arsitektur database backend dan schema relasional tetap dipertahankan tanpa perubahan destruktif (*zero schema migration changes required*). Semua entitas yang ada tetap menjadi sumber kebenaran data:

| Entity | Primary Attributes | Role in UI/UX Enhancement |
|--------|-------------------|---------------------------|
| `Ticket` | `id`, `ticket_number`, `title`, `description`, `category`, `priority`, `status`, `classification`, `requester_id`, `supervisor_id`, `assigned_to` | Data sumber untuk Visual Stepper, kartu detail, dan kartu metrik dashboard. |
| `User` | `id`, `name`, `email`, `role`, `department` | Menentukan menu navigasi yang tampil di sidebar, otorisasi aksi modal, dan foto avatar/inisial profil. |
| `TicketAttachment` | `id`, `ticket_id`, `uploaded_by`, `file_name`, `file_path`, `file_size_bytes`, `mime_type` | Ditampilkan pada daftar berkas unduhan dan diinputkan via dropzone baru. |
| `TicketActivity` | `id`, `ticket_id`, `user_id`, `activity_type`, `description` | Ditampilkan pada linimasa aktivitas developer tim IT. |
| `TicketAuditLog` | `id`, `ticket_id`, `actor_id`, `action`, `previous_state`, `new_state`, `comments_or_reason`, `created_at` | Ditampilkan pada riwayat jejak audit vertikal terstruktur. |
| `MeetingMinutes` | `id`, `ticket_id`, `meeting_date`, `attendees`, `summary`, `action_items`, `is_approved`, `approved_by` | Ditampilkan pada bagian khusus tiket Major dengan status persetujuan terpisah. |

---

## 2. UI View-Models & Presentation Data Structures

### A. Navigation Menu Item Structure (Sidebar)

Setiap item menu pada sidebar direpresentasikan oleh struktur berikut:

```php
[
    'label' => 'Dashboard',
    'route' => 'dashboard',
    'active' => request()->routeIs('dashboard'),
    'icon' => '<svg ...></svg>', // Clean Enterprise Lucide/Heroicon SVG
    'roles' => ['requester', 'supervisor', 'it_head', 'it_developer', 'admin'],
    'badge' => null // Optional dynamic count (e.g. pending approvals)
]
```

### B. Visual Workflow Stepper State Mapping

Komponen `<x-ticket-stepper :ticket="$ticket" />` menghitung status visual 5-tahap berdasarkan status riil dari `Ticket`:

```text
Status Enum Tiket:
- draft
- menunggu_approval_atasan
- ditolak_atasan
- revisi_requester
- menunggu_approval_it_head
- menunggu_rapat_major
- menunggu_notulensi
- menunggu_persetujuan_notulensi
- ditolak_it_head
- siap_dikerjakan
- dalam_pengerjaan
- revisi_hasil_pekerjaan
- menunggu_review_user
- selesai
```

**Representasi 5 Tahap:**

| Tahap ID | Nama Tahap | Deskripsi Singkat | Status Aktif Tiket | Kondisi Selesai (Completed) | Kondisi Gagal/Peringatan |
|---|---|---|---|---|---|
| **Tahap A** | Pengajuan Tiket | Requester submit form permohonan | `draft` | Seluruh status setelah draft | - |
| **Tahap B** | Approval Atasan | Atasan langsung mengevaluasi kebutuhan | `menunggu_approval_atasan` | Lolos ke Tahap C atau setelahnya | `ditolak_atasan` (Merah), `revisi_requester` (Amber) |
| **Tahap C** | Review & Kabag IT | Klasifikasi Minor/Major & Persetujuan Rapat | `menunggu_approval_it_head`, `menunggu_rapat_major`, `menunggu_notulensi`, `menunggu_persetujuan_notulensi` | Masuk ke antrean pengerjaan (`siap_dikerjakan`) | `ditolak_it_head` (Merah) |
| **Tahap D** | Pengerjaan Tim IT | Antrean pengerjaan & penanganan teknis | `siap_dikerjakan`, `dalam_pengerjaan` | Tiket siap direview pemohon | `revisi_hasil_pekerjaan` (Kembali dari review) |
| **Tahap E** | Review & Selesai | Requester menguji dan menerima solusi | `menunggu_review_user` | `selesai` (Centang hijau penuh) | - |

**State Node Stepper:**
- `is_current`: boolean (tahap yang sedang berjalan saat ini).
- `is_completed`: boolean (tahap yang telah berhasil dilewati).
- `is_rejected`: boolean (tahap tempat tiket dihentikan/ditolak).
- `is_revision`: boolean (tahap yang sedang meminta revisi).
- `is_pending`: boolean (tahap di masa depan yang belum tercapai).

### C. Modal Action Definition Structure

Setiap modal dialog aksi pada halaman tiket dikonfigurasi dengan struktur berikut:

```php
[
    'id' => 'modal-supervisor-reject',
    'title' => 'Tolak Pengajuan Tiket',
    'action_url' => route('tickets.supervisor-reject', $ticket),
    'method' => 'POST',
    'confirm_label' => 'Tolak Tiket',
    'confirm_variant' => 'danger', // danger, warning, success, primary
    'requires_reason' => true,
    'reason_field_name' => 'catatan_alasan',
    'reason_placeholder' => 'Jelaskan alasan penolakan secara spesifik...',
]
```

### D. File Dropzone Client-Side State (Alpine.js)

```javascript
{
    files: [], // Array of { name, size, formattedSize, extension }
    isDragging: false,
    maxSize: 10 * 1024 * 1024, // 10MB
    allowedExtensions: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'],
    errorMessage: null,
    handleDrop(event) { ... },
    handleSelect(event) { ... },
    removeFile(index) { ... }
}
```
