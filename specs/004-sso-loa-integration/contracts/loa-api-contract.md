# External LOA API Contract Specification

**Feature**: `004-sso-loa-integration`  
**Date**: 2026-10-28  
**Status**: Completed (Phase 1)  
**Governance**: Aligned with Constitution v3.1.0 (Prinsip IV & V)

---

## 1. Outbound Request: Pengajuan Persetujuan LOA

Pemanggilan dilakukan dari sisi server (`App\Services\EnterpriseLoaService`) ke aplikasi LOA korporat.

- **Endpoint**: `POST {LOA_BASE_URL}/api/v1/loa/requests`
- **Headers**:
  ```http
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer {LOA_API_KEY}
  Idempotency-Key: {UUIDv4_UNIQUE_KEY}
  ```
- **Request Body Payload**:
  ```json
  {
    "ticket_number": "TICK-20261028-0001",
    "approval_level": "supervisor",
    "ticket_summary": {
      "title": "Upgrade Kapasitas Server Database Finansial",
      "category": "software",
      "classification": "major",
      "created_at": "2026-10-28T09:00:00Z"
    },
    "requester": {
      "sso_id": "sso-usr-98214",
      "name": "Budi Santoso",
      "email": "karyawan.it@internal.corp",
      "department": "Finance & Accounting"
    },
    "target_approver": {
      "sso_id": "sso-usr-98001",
      "name": "Agus Setiawan",
      "email": "agus.setiawan@internal.corp"
    }
  }
  ```

### Response 202 Accepted (Berhasil Dikirim ke Antrean LOA):
```json
{
  "status": "accepted",
  "message": "LOA request queued successfully",
  "data": {
    "external_reference_id": "LOA-REF-2026-88192",
    "ticket_number": "TICK-20261028-0001",
    "current_status": "waiting",
    "dispatched_at": "2026-10-28T09:01:15Z"
  }
}
```

*Catatan Penting*: Respons 202 ini **TIDAK MENGUBAH STATUS TIKET MENJADI DISETUJUI**, melainkan mencatat transaksi integrasi berstatus `pending` dan tiket tetap berada pada status menunggu approval.

---

## 2. Inbound Webhook Callback: Keputusan LOA Eksternal

Aplikasi LOA memanggil endpoint webhook Helpdesk saat approver (Atasan, Kadiv IT, atau Direksi) mengambil keputusan resmi.

- **Endpoint**: `POST {HELPDESK_BASE_URL}/api/v1/webhooks/loa-decision`
- **Headers**:
  ```http
  Content-Type: application/json
  Accept: application/json
  X-LOA-Signature: sha256={HMAC_SHA256_SIGNATURE}
  ```
- **Webhook Body Payload**:
  ```json
  {
    "event": "loa.decision.updated",
    "data": {
      "external_reference_id": "LOA-REF-2026-88192",
      "ticket_number": "TICK-20261028-0001",
      "approval_level": "supervisor",
      "decision": "approved",
      "approver": {
        "sso_id": "sso-usr-98001",
        "name": "Agus Setiawan",
        "email": "agus.setiawan@internal.corp"
      },
      "comments": "Permohonan disetujui, spesifikasi teknis dinilai wajar.",
      "decided_at": "2026-10-28T09:45:00Z"
    }
  }
  ```

### Nilai Bidang `decision`:
1. `approved`: Persetujuan dikonfirmasi. Tiket melanjutkan alurnya sesuai klasifikasi.
2. `rejected`: Penolakan dikonfirmasi. Tiket dipindahkan secara permanen ke `Pool Tiket Ditolak` dengan status `ditolak_atasan`, `ditolak_kadiv_it`, atau `ditolak_direksi`.
3. `revision`: Permintaan revisi. Tiket beralih ke `revisi_requester` untuk diperbaiki pemohon.

### Webhook Response:
- `200 OK`: `{"status": "success", "message": "Decision processed idempotently"}`
- `401 Unauthorized`: Jika `X-LOA-Signature` tidak valid.
- `404 Not Found`: Jika `ticket_number` tidak ditemukan di Helpdesk.

---

## 3. Fallback Status Check (Polling)

Endpoint yang dipanggil oleh Artisan command `php artisan helpdesk:sync-loa` atau tombol aksi manual:

- **Endpoint**: `GET {LOA_BASE_URL}/api/v1/loa/status/{external_reference_id}`
- **Headers**:
  ```http
  Accept: application/json
  Authorization: Bearer {LOA_API_KEY}
  ```
- **Response 200 OK**:
  ```json
  {
    "status": "success",
    "data": {
      "external_reference_id": "LOA-REF-2026-88192",
      "current_status": "approved",
      "decision": "approved",
      "approver_comments": "Disetujui oleh atasan.",
      "decided_at": "2026-10-28T09:45:00Z"
    }
  }
  ```
