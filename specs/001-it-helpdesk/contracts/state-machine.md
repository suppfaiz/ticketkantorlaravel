# State Machine & Status Transitions Contract

**Feature**: Aplikasi IT Helpdesk Internal Perusahaan  
**Directory**: `specs/001-it-helpdesk/contracts`  
**Date**: 2026-10-28  

Dokumen ini mendefinisikan seluruh transisi status yang sah, aktor pemicu, syarat pra-kondisi (*guards*), dan mutasi audit trail. Transisi status yang tidak tercantum dalam tabel ini **ditolak keras (illegal state transition exception)**.

---

## 1. Tabel Transisi Status Resmi

| No | Status Awal (`from_status`) | Status Tujuan (`to_status`) | Aktor Berwenang | Aksi / Trigger | Pra-kondisi / Guards | Tindakan Audit Trail |
|---|---|---|---|---|---|---|
| 1 | `Draft` | `Menunggu Approval Atasan` | Requester | `submit` | Judul, deskripsi, kategori valid; requester memiliki atasan aktif | Mencatat aksi `SUBMIT_TICKET` |
| 2 | `Menunggu Approval Atasan` | `Menunggu Approval Kepala Bagian IT` | Atasan Langsung | `supervisor_approve` | Pengguna adalah atasan langsung requester | Mencatat aksi `SUPERVISOR_APPROVE` |
| 3 | `Menunggu Approval Atasan` | `Ditolak Atasan` | Atasan Langsung | `supervisor_reject` | Alasan penolakan wajib diisi (min 10 karakter) | Mencatat aksi `SUPERVISOR_REJECT` + alasan |
| 4 | `Menunggu Approval Atasan` | `Revisi Requester` | Atasan Langsung | `supervisor_revise` | Catatan revisi wajib diisi (min 10 karakter) | Mencatat aksi `SUPERVISOR_REQUEST_REVISION` + catatan |
| 5 | `Revisi Requester` | `Menunggu Approval Atasan` | Requester | `resubmit_after_supervisor_revision` | Tiket telah diperbarui oleh requester | Mencatat aksi `RESUBMIT_TICKET` |
| 6 | `Menunggu Approval Kepala Bagian IT` | `Siap Dikerjakan` | Kepala Bagian IT | `it_head_approve_minor` | Klasifikasi diset `Minor`, prioritas terisi | Mencatat aksi `IT_HEAD_APPROVE_MINOR` |
| 7 | `Menunggu Approval Kepala Bagian IT` | `Menunggu Rapat Major` | Kepala Bagian IT | `it_head_classify_major` | Klasifikasi diset `Major`, prioritas terisi | Mencatat aksi `IT_HEAD_CLASSIFY_MAJOR` |
| 8 | `Menunggu Approval Kepala Bagian IT` | `Ditolak Kepala Bagian IT` | Kepala Bagian IT | `it_head_reject` | Alasan penolakan wajib diisi | Mencatat aksi `IT_HEAD_REJECT` + alasan |
| 9 | `Menunggu Approval Kepala Bagian IT` | `Revisi Requester oleh Kepala Bagian IT` | Kepala Bagian IT | `it_head_revise` | Catatan revisi wajib diisi | Mencatat aksi `IT_HEAD_REQUEST_REVISION` + catatan |
| 10 | `Revisi Requester oleh Kepala Bagian IT` | `Menunggu Approval Atasan` | Requester | `resubmit_after_it_head_revision` | Sesuai klarifikasi Q1: wajib melalui atasan kembali | Mencatat aksi `RESUBMIT_TO_SUPERVISOR_AFTER_IT_REVISION` |
| 11 | `Menunggu Rapat Major` | `Menunggu Persetujuan Notulensi` | Requester / Kabag IT | `submit_meeting_minutes` | Notulensi terisi (tanggal, peserta, ruang lingkup) | Mencatat aksi `SUBMIT_MEETING_MINUTES` |
| 12 | `Menunggu Persetujuan Notulensi` | `Siap Dikerjakan` | Kepala Bagian IT | `approve_meeting_minutes` | Kabag IT menyetujui notulensi rapat Major | Mencatat aksi `APPROVE_MEETING_MINUTES` |
| 13 | `Menunggu Persetujuan Notulensi` | `Menunggu Rapat Major` | Kepala Bagian IT | `reject_or_revise_minutes` | Kabag IT meminta revisi notulensi rapat | Mencatat aksi `REVISE_MEETING_MINUTES` |
| 14 | `Siap Dikerjakan` | `Dalam Pengerjaan` | IT Developer | `self_assign` | `assigned_to IS NULL`; atomic conditional update | Mencatat aksi `DEVELOPER_SELF_ASSIGN` |
| 15 | `Dalam Pengerjaan` | `Menunggu Review User` | IT Developer (Assignee) | `complete_work` | Assignee menyelesaikan tugas; ringkasan penanganan | Mencatat aksi `WORK_COMPLETED` |
| 16 | `Menunggu Review User` | `Selesai` | Requester | `user_accept` | Requester memberikan feedback ulasan | Mencatat aksi `USER_ACCEPT_CLOSE` + feedback |
| 17 | `Menunggu Review User` | `Revisi Hasil Pekerjaan` | Requester | `user_revise` | Requester mengisi alasan revisi hasil pekerjaan | Mencatat aksi `USER_REQUEST_WORK_REVISION` + alasan |
| 18 | `Revisi Hasil Pekerjaan` | `Menunggu Review User` | IT Developer (Assignee) | `resubmit_work` | Tiket otomatis tetap di tangan developer asal | Mencatat aksi `WORK_REPAIRED_RESUBMITTED` |

---

## 2. Invarian State Machine

1. **Terminal States**:
   - Status `Selesai`, `Ditolak Atasan`, dan `Ditolak Kepala Bagian IT` adalah status akhir (*terminal states*). Tidak ada transisi keluar dari status ini.
2. **Major Queue Gate**:
   - Tiket berkategori Major **TIDAK PERNAH** dapat melompat dari `Menunggu Rapat Major` langsung ke `Siap Dikerjakan`. Tiket Major WAJIB melalui `Menunggu Persetujuan Notulensi` dan disetujui Kabag IT.
3. **Pencegahan Double Self-Assign**:
   - Transisi No. 14 (`Siap Dikerjakan` → `Dalam Pengerjaan`) dieksekusi secara atomik menggunakan conditional update:
     `UPDATE tickets SET status = 'Dalam Pengerjaan', assigned_to = ? WHERE id = ? AND status = 'Siap Dikerjakan' AND assigned_to IS NULL;`
4. **Alokasi Otomatis Pasca-Review User**:
   - Transisi No. 17 (`Menunggu Review User` → `Revisi Hasil Pekerjaan`) mempertahankan `assigned_to` developer sebelumnya, sehingga saat developer membuka dashboard, tiket langsung berada di daftar perbaikan pengerjaan miliknya.
