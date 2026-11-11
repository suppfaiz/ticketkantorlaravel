# Web & Webhook Route Contracts

**Feature**: `004-sso-loa-integration`  
**Date**: 2026-10-28  
**Status**: Completed (Phase 1)  
**Governance**: Aligned with Constitution v3.1.0

---

## 1. Authentication & Session Routes

| Method | URI | Controller Action | Middleware | Deskripsi |
|:---|:---|:---|:---|:---|
| `GET` | `/login` | `AuthController@showLoginForm` | `guest` | Menampilkan halaman login SSO Enterprise |
| `POST` | `/login` | `AuthController@login` | `guest`, `throttle:login` | Memvalidasi kredensial via `SsoService`, menetapkan sesi |
| `POST` | `/logout` | `AuthController@logout` | `auth` | Memusnahkan sesi lokal dan cookie |

---

## 2. Inbound Webhook Routes (API)

| Method | URI | Controller Action | Middleware | Deskripsi |
|:---|:---|:---|:---|:---|
| `POST` | `/api/v1/webhooks/loa-decision` | `LoaWebhookController@handle` | `verify.loa.signature` | Menerima callback konfirmasi keputusan LOA eksternal |

---

## 3. Post-Direksi Major Meeting & Minutes Routes (Web)

| Method | URI | Controller Action | Middleware & Policy | Deskripsi |
|:---|:---|:---|:---|:---|
| `POST` | `/tickets/{ticket}/schedule-meeting` | `TicketMeetingController@scheduleMeeting` | `auth`, `can:scheduleMeeting,ticket` | Kabag IT mencatat rapat koordinasi Major |
| `POST` | `/tickets/{ticket}/submit-minutes` | `TicketMeetingController@submitMinutes` | `auth`, `can:submitMinutes,ticket` | User pemohon menginput notulensi rapat & lampiran |
| `POST` | `/tickets/{ticket}/approve-minutes` | `TicketMeetingController@approveMinutes` | `auth`, `can:approveMinutes,ticket` | Kabag IT menyetujui notulensi -> ke Pool Siap Progress |
| `POST` | `/tickets/{ticket}/revise-minutes` | `TicketMeetingController@reviseMinutes` | `auth`, `can:reviseMinutes,ticket` | Kabag IT meminta revisi notulensi ke user pemohon |

---

## 4. Ticket Lifecycle & Execution Routes (Web)

| Method | URI | Controller Action | Middleware & Policy | Deskripsi |
|:---|:---|:---|:---|:---|
| `POST` | `/tickets` | `TicketController@store` | `auth`, `can:create,App\Models\Ticket` | Pengajuan tiket baru & pemicu LOA Atasan via API |
| `POST` | `/tickets/{ticket}/it-head-decision` | `TicketController@itHeadDecision` | `auth`, `can:reviewItHead,ticket` | Kabag IT mengeksekusi 4 cabang (Minor/Major/Revisi/Tolak) |
| `POST` | `/tickets/{ticket}/sync-loa` | `TicketController@syncLoaStatus` | `auth`, `can:view,ticket` | Sinkronisasi manual status LOA eksternal |
| `POST` | `/tickets/{ticket}/self-assign` | `TicketController@selfAssign` | `auth`, `can:claim,ticket` | Developer IT mengklaim tiket di Pool Siap Progress (`lockForUpdate`) |
| `POST` | `/tickets/{ticket}/activities` | `TicketActivityController@store` | `auth`, `can:updateProgress,ticket` | Developer mencatat kemajuan aktivitas teknis |
| `POST` | `/tickets/{ticket}/complete-work` | `TicketController@completeWork` | `auth`, `can:completeWork,ticket` | Developer menyelesaikan pekerjaan -> ke Review User |
| `POST` | `/tickets/{ticket}/user-review` | `TicketReviewController@store` | `auth`, `can:reviewWork,ticket` | Pemohon mengeksekusi Accept (tutup) atau Revisi Pekerjaan |
