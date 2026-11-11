# Authorization Matrix & Policy Rules

**Feature**: Aplikasi IT Helpdesk Internal Perusahaan  
**Directory**: `specs/001-it-helpdesk/contracts`  
**Date**: 2026-10-28  

Dokumen ini mendefinisikan matriks otorisasi server-side berbasis **Laravel Policies** dan **Gates** untuk mencegah *Insecure Direct Object References (IDOR/BOLA)* dan menjamin prinsip *least privilege*.

---

## 1. Matriks Otorisasi Berdasarkan Tindakan & Peran

| Tindakan / Policy Method | Requester (Pembuat) | Atasan Langsung | Kepala Bagian IT | Tim IT / Developer | Admin Sistem | Prasyarat Status Tiket |
|---|---|---|---|---|---|---|
| `viewAny` | Ya (milik sendiri) | Ya (milik bawahan) | Ya (seluruh tiket) | Ya (antrean & ditangani) | Ya (read-only monitoring) | - |
| `view` | Ya (jika requester_id == user.id) | Ya (jika supervisor_id == user.id) | Ya | Ya (jika siap dikerjakan / ditangani) | Ya (monitoring) | Sembarang status |
| `create` | Ya | Tidak | Tidak | Tidak | Tidak | - |
| `update` (Edit Permohonan) | Ya | Tidak | Tidak | Tidak | Tidak | `Draft` ATAU `Revisi Requester` |
| `supervisorApprove` | Tidak | Ya (hanya jika supervisor_id == user.id) | Tidak | Tidak | Tidak | `Menunggu Approval Atasan` |
| `supervisorReject` | Tidak | Ya (hanya jika supervisor_id == user.id) | Tidak | Tidak | Tidak | `Menunggu Approval Atasan` |
| `supervisorRevise` | Tidak | Ya (hanya jika supervisor_id == user.id) | Tidak | Tidak | Tidak | `Menunggu Approval Atasan` |
| `itHeadDecide` (Classify/Reject/Revise) | Tidak | Tidak | Ya (role == it_head) | Tidak | Tidak | `Menunggu Approval Kepala Bagian IT` |
| `manageMeetingMinutes` (Input Notulensi) | Ya (jika requester_id == user.id) | Tidak | Ya (role == it_head) | Tidak | Tidak | `Menunggu Rapat Major` ATAU `Menunggu Notulensi` |
| `approveMeetingMinutes` | Tidak | Tidak | Ya (role == it_head) | Tidak | Tidak | `Menunggu Persetujuan Notulensi` |
| `selfAssign` | Tidak | Tidak | Tidak | Ya (role == it_developer & assigned_to IS NULL) | Tidak | `Siap Dikerjakan` |
| `addActivityLog` | Tidak | Tidak | Tidak | Ya (jika assigned_to == user.id) | Tidak | `Dalam Pengerjaan` ATAU `Revisi Hasil Pekerjaan` |
| `completeWork` | Tidak | Tidak | Tidak | Ya (jika assigned_to == user.id) | Tidak | `Dalam Pengerjaan` ATAU `Revisi Hasil Pekerjaan` |
| `reviewTicket` (Accept/Revisi) | Ya (hanya jika requester_id == user.id) | Tidak | Tidak | Tidak | Tidak | `Menunggu Review User` |
| `downloadAttachment` | Ya (jika berhak view tiket) | Ya (jika berhak view tiket) | Ya | Ya (jika berhak view tiket) | Ya (monitoring) | Sesuai hak akses tiket |

---

## 2. Invarian Kebijakan Keamanan (Security Policy Invariants)

1. **Deny-by-Default**:
   - Jika suatu kombinasi `(user, action, ticket)` tidak terdaftar secara eksplisit dengan nilai `true` pada Policy, Laravel Policy akan otomatis mengembalikan `Response::deny('Unauthorized action.')` (HTTP 403 Forbidden).
2. **Pencegahan IDOR (Insecure Direct Object Reference)**:
   - Mengetahui atau mengganti parameter ID tiket pada URL (misal: `/tickets/105/review-accept`) TIDAK BISA dieksekusi oleh pengguna lain karena `TicketPolicy@reviewTicket` memvalidasi `$ticket->requester_id === $user->id`.
   - Supervisor A tidak dapat menyetujui tiket bawahan Supervisor B karena `supervisorApprove` memvalidasi `$ticket->supervisor_id === $user->id`.
3. **Pemisahan Peran Administrator**:
   - Administrator sistem memiliki wewenang mengelola data master (departemen, akun pengguna, reset password administratif).
   - Hak administrator sistem **TIDAK memberikan izin** untuk melakukan *approval* tiket, *self-assign*, penulisan *notulensi*, maupun penutupan tiket atas nama bisnis.
4. **Validasi Ganda Status & Otorisasi**:
   - Seluruh tindakan harus lolos **kedua** lapis pemeriksaan:
     - Lapisan 1: Otorisasi pengguna (`Gate::authorize` / `$this->authorize(...)`).
     - Lapisan 2: Validasi transisi status (*state machine transition integrity*).
