# Interface Contracts: Web Routes & Endpoints

**Feature**: Aplikasi IT Helpdesk Internal Perusahaan  
**Directory**: `specs/001-it-helpdesk/contracts`  
**Date**: 2026-10-28  

Dokumen ini memetakan seluruh rute HTTP web, middleware proteksi, Form Request validasi server, serta respons yang dihasilkan.

---

## 1. Authentication Routes (Laravel Native)

| Method | URI | Middleware | Controller Action | Deskripsi |
|---|---|---|---|---|
| `GET` | `/login` | `guest` | `AuthenticatedSessionController@create` | Menampilkan formulir login |
| `POST` | `/login` | `guest`, `throttle:login` | `AuthenticatedSessionController@store` | Autentikasi sesi & regenerasi session ID |
| `POST` | `/logout` | `auth` | `AuthenticatedSessionController@destroy` | Invaliasi session & regenerasi CSRF |

---

## 2. Dashboard Route

| Method | URI | Middleware | Controller Action | Deskripsi |
|---|---|---|---|---|
| `GET` | `/dashboard` | `auth` | `DashboardController@index` | Menampilkan dashboard terpersonalisasi sesuai role pengguna |

---

## 3. Ticket Management Routes

### 3.1. Pengajuan & Pemantauan (Requester)

| Method | URI | Middleware | Policy Gate | Form Request | Deskripsi |
|---|---|---|---|---|---|
| `GET` | `/tickets` | `auth` | `viewAny` | - | Daftar tiket terfilter sesuai hak akses role pengguna |
| `GET` | `/tickets/create` | `auth` | `create` | - | Formulir pengajuan tiket baru |
| `POST` | `/tickets` | `auth` | `create` | `StoreTicketRequest` | Menyimpan dan mengirimkan tiket ke status `Menunggu Approval Atasan` |
| `GET` | `/tickets/{ticket}` | `auth` | `view` | - | Detail tiket, riwayat status, log penanganan, dan lampiran |
| `GET` | `/tickets/{ticket}/edit` | `auth` | `update` | - | Formulir revisi tiket (hanya saat status `Revisi Requester` / draft) |
| `PUT` | `/tickets/{ticket}` | `auth` | `update` | `UpdateTicketRequest` | Mengirimkan pembaruan tiket pasca-revisi |

### 3.2. Approval Atasan Langsung (Supervisor)

| Method | URI | Middleware | Policy Gate | Form Request | Deskripsi |
|---|---|---|---|---|---|
| `POST` | `/tickets/{ticket}/supervisor-approve` | `auth` | `supervisorApprove` | `SupervisorApprovalRequest` | Menyetujui tiket → `Menunggu Approval Kepala Bagian IT` |
| `POST` | `/tickets/{ticket}/supervisor-reject` | `auth` | `supervisorReject` | `SupervisorRejectRequest` | Menolak tiket → `Ditolak Atasan` (wajib alasan) |
| `POST` | `/tickets/{ticket}/supervisor-revise` | `auth` | `supervisorRevise` | `SupervisorReviseRequest` | Meminta revisi → `Revisi Requester` (wajib catatan) |

### 3.3. Approval & Klasifikasi Kepala Bagian IT (IT Head)

| Method | URI | Middleware | Policy Gate | Form Request | Deskripsi |
|---|---|---|---|---|---|
| `POST` | `/tickets/{ticket}/it-head-classify-approve` | `auth` | `itHeadDecide` | `ItHeadClassifyRequest` | Menentukan Minor/Major, menyetujui Minor → `Siap Dikerjakan`, atau Major → `Menunggu Rapat Major` |
| `POST` | `/tickets/{ticket}/it-head-reject` | `auth` | `itHeadDecide` | `ItHeadRejectRequest` | Menolak tiket → `Ditolak Kepala Bagian IT` (wajib alasan) |
| `POST` | `/tickets/{ticket}/it-head-revise` | `auth` | `itHeadDecide` | `ItHeadReviseRequest` | Meminta revisi → `Revisi Requester oleh Kepala Bagian IT` |

### 3.4. Notulensi Rapat Major

| Method | URI | Middleware | Policy Gate | Form Request | Deskripsi |
|---|---|---|---|---|---|
| `GET` | `/tickets/{ticket}/meeting-minutes/create` | `auth` | `manageMeetingMinutes` | - | Form input notulensi rapat Major (Requester / Kabag IT) |
| `POST` | `/tickets/{ticket}/meeting-minutes` | `auth` | `manageMeetingMinutes` | `StoreMeetingMinutesRequest` | Menyimpan notulensi → `Menunggu Persetujuan Notulensi` |
| `POST` | `/tickets/{ticket}/meeting-minutes/approve` | `auth` | `approveMeetingMinutes` | `ApproveMeetingMinutesRequest` | Kabag IT menyetujui notulensi → `Siap Dikerjakan` |

### 3.5. Antrean Pengerjaan & Aktivitas Tim IT (Developer)

| Method | URI | Middleware | Policy Gate | Form Request | Deskripsi |
|---|---|---|---|---|---|
| `POST` | `/tickets/{ticket}/self-assign` | `auth` | `selfAssign` | - | Mengambil tiket mandiri secara atomic → `Dalam Pengerjaan` |
| `POST` | `/tickets/{ticket}/activities` | `auth` | `addActivityLog` | `StoreActivityLogRequest` | Menambahkan catatan progres pengerjaan |
| `POST` | `/tickets/{ticket}/complete` | `auth` | `completeWork` | `CompleteWorkRequest` | Menyelesaikan pengerjaan → `Menunggu Review User` |

### 3.6. Review Hasil & Penutupan (Requester)

| Method | URI | Middleware | Policy Gate | Form Request | Deskripsi |
|---|---|---|---|---|---|
| `POST` | `/tickets/{ticket}/review-accept` | `auth` | `reviewTicket` | `ReviewAcceptRequest` | Menerima hasil pekerjaan → `Selesai` (wajib feedback) |
| `POST` | `/tickets/{ticket}/review-revise` | `auth` | `reviewTicket` | `ReviewReviseRequest` | Meminta perbaikan hasil → `Revisi Hasil Pekerjaan` (wajib alasan) |

### 3.7. Pengunduhan Lampiran Aman

| Method | URI | Middleware | Policy Gate | Deskripsi |
|---|---|---|---|---|
| `GET` | `/tickets/{ticket}/attachments/{attachment}/download` | `auth` | `downloadAttachment` | Mengunduh file lampiran dari private storage melalui stream |

---

## 4. Skema Validasi Form Request Utama

1. **`StoreTicketRequest`**:
   - `title`: `required|string|min:5|max:255`
   - `category`: `required|string|max:100`
   - `description`: `required|string|min:20`
   - `attachments.*`: `nullable|file|max:10240|mimes:pdf,docx,xlsx,png,jpg,jpeg`
2. **`SupervisorRejectRequest` & `ItHeadRejectRequest`**:
   - `reason`: `required|string|min:10|max:1000`
3. **`SupervisorReviseRequest` & `ItHeadReviseRequest`**:
   - `revision_notes`: `required|string|min:10|max:1000`
4. **`ItHeadClassifyRequest`**:
   - `classification`: `required|in:Minor,Major`
   - `priority`: `required|in:Low,Medium,High,Urgent`
   - `notes`: `nullable|string|max:1000`
5. **`StoreMeetingMinutesRequest`**:
   - `meeting_date`: `required|date|before_or_equal:today`
   - `attendees`: `required|string|min:5`
   - `scope_summary`: `required|string|min:20`
   - `deliverables`: `required|string|min:10`
   - `target_completion_date`: `nullable|date|after:meeting_date`
   - `minutes_document`: `nullable|file|max:10240|mimes:pdf,docx`
6. **`ReviewAcceptRequest`**:
   - `feedback`: `required|string|min:5|max:1000`
   - `rating`: `nullable|integer|between:1,5`
7. **`ReviewReviseRequest`**:
   - `revision_reason`: `required|string|min:10|max:1000`
