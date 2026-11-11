# Quickstart & Validation Guide: IT Helpdesk Internal

**Feature**: Aplikasi IT Helpdesk Internal Perusahaan  
**Directory**: `specs/001-it-helpdesk`  
**Date**: 2026-10-28  

Panduan ini berisi skenario validasi ujung-ke-ujung (*end-to-end validation scenarios*) untuk memverifikasi fungsionalitas dan invarian bisnis sistem IT Helpdesk setelah tahap implementasi.

---

## 1. Persiapan Lingkungan & Prasyarat Pengujian

1. **PHP**: Versi 8.2 atau lebih tinggi (CLI dan ekstensi PDO/PostgreSQL/MySQL aktif).
2. **Composer**: Versi 2.x.
3. **Database**: PostgreSQL 15+ atau MySQL 8.0+.
4. **Setup Perintah**:
   ```bash
   # Jalankan migrasi basis data
   php artisan migrate:fresh --seed
   ```
5. **Akun Pengujian Bawaan Seeder**:
   - Requester: `requester@kantor.internal` (Bawahan langsung dari Supervisor)
   - Atasan Langsung: `supervisor@kantor.internal`
   - Kepala Bagian IT: `kabagit@kantor.internal`
   - IT Developer 1: `dev1@kantor.internal`
   - IT Developer 2: `dev2@kantor.internal`
   - Administrator: `admin@kantor.internal`

---

## 2. Skenario Validasi End-to-End

### Skenario 1: Happy Path Tiket Minor (P1, P2, P3, P4)
1. **Pengajuan (Requester)**:
   - Login sebagai `requester@kantor.internal`.
   - Ajukan tiket baru: Judul "Koneksi Printer Lambat", Kategori "Hardware/Network", Deskripsi rinci.
   - *Verifikasi*: Tiket terbit dengan format nomor `TICK-YYYYMMDD-XXXX`, status `Menunggu Approval Atasan`, dan audit log mencatat aksi `SUBMIT_TICKET`.
2. **Approval Atasan (Supervisor)**:
   - Login sebagai `supervisor@kantor.internal`.
   - Buka menu tiket bawahan, pilih tindakan "Setujui".
   - *Verifikasi*: Status tiket berubah menjadi `Menunggu Approval Kepala Bagian IT`.
3. **Klasifikasi Minor (Kabag IT)**:
   - Login sebagai `kabagit@kantor.internal`.
   - Pilih klasifikasi "Minor", prioritas "Medium", lalu klik "Setujui".
   - *Verifikasi*: Status tiket berubah menjadi `Siap Dikerjakan`. Tiket muncul di antrean pengerjaan Tim IT.
4. **Self-Assign & Pengerjaan (IT Developer 1)**:
   - Login sebagai `dev1@kantor.internal`.
   - Buka antrean `Siap Dikerjakan`, klik "Ambil Tiket" (*Self-Assign*).
   - *Verifikasi*: Status berubah menjadi `Dalam Pengerjaan`, `assigned_to` terisi ID Developer 1.
   - Tambahkan log aktivitas penanganan: "Penggantian kabel LAN dan konfigurasi IP printer".
   - Klik tombol "Selesai Dikerjakan".
   - *Verifikasi*: Status tiket berubah menjadi `Menunggu Review User`.
5. **Review & Penutupan (Requester)**:
   - Login sebagai `requester@kantor.internal`.
   - Buka tiket, periksa hasil pengerjaan, pilih "Accept", isi feedback "Printer sudah lancar kembali, terima kasih!".
   - *Verifikasi*: Status tiket berubah menjadi `Selesai`, `closed_at` terisi, dan audit trail mencatat penutupan permanen.

---

### Skenario 2: Happy Path Tiket Major & Notulensi Rapat (P2)
1. **Pengajuan & Approval Atasan**: Ikuti langkah 1-2 dari Skenario 1 (misal judul: "Permintaan Sistem CRM Baru").
2. **Klasifikasi Major oleh Kabag IT**:
   - Login sebagai `kabagit@kantor.internal`.
   - Pilih klasifikasi "Major", prioritas "High", klik simpan keputusan.
   - *Verifikasi*: Status tiket berubah menjadi `Menunggu Rapat Major`. Tiket **TIDAK MUNCUL** di antrean pengerjaan `Siap Dikerjakan`.
3. **Input Notulensi Rapat**:
   - Login sebagai `requester@kantor.internal` atau `kabagit@kantor.internal`.
   - Buka tiket, klik "Input Notulensi Rapat".
   - Masukkan tanggal rapat, daftar hadir, ringkasan ruang lingkup (*scope summary*), dan target waktu.
   - *Verifikasi*: Status tiket berpindah menjadi `Menunggu Persetujuan Notulensi`.
4. **Persetujuan Notulensi oleh Kabag IT**:
   - Login sebagai `kabagit@kantor.internal`.
   - Periksa notulensi, klik "Setujui Notulensi".
   - *Verifikasi*: Status tiket sekarang berpindah ke `Siap Dikerjakan`. Tiket kini tersedia di antrean pengerjaan developer.

---

### Skenario 3: Uji Keamanan Konkurensi Self-Assign (P3 Edge Case)
1. Siapkan 1 tiket pada status `Siap Dikerjakan`.
2. Buka dua sesi peramban berbeda: satu untuk `dev1@kantor.internal` dan satu untuk `dev2@kantor.internal`.
3. Kirimkan aksi "Ambil Tiket" pada detik yang sama dari kedua pengguna.
4. *Hasil yang Diharapkan*:
   - Tepat satu developer yang berhasil menerima konfirmasi sukses penugasan.
   - Developer kedua menerima pesan ramah bahwa tiket sudah diambil oleh anggota lain.
   - Tiket hanya memiliki satu `assigned_to` yang sah tanpa data korup.

---

### Skenario 4: Siklus Revisi Hasil Pekerjaan oleh Requester (P4)
1. Tiket berada pada status `Menunggu Review User` yang dikerjakan oleh Developer 1.
2. Login sebagai `requester@kantor.internal`, pilih tindakan "Revisi", isi alasan "Printer masih putus-putus saat mencetak dokumen tebal".
3. *Verifikasi*: Status tiket berubah menjadi `Revisi Hasil Pekerjaan`, dan tiket otomatis tetap melekat pada Developer 1.
4. Login sebagai `dev1@kantor.internal`, periksa daftar tugas revisi, tambahkan catatan progres perbaikan, lalu ajukan ulang pengerjaan.
5. *Verifikasi*: Tiket kembali ke status `Menunggu Review User`.

---

### Skenario 5: Uji Penolakan Otorisasi & Pencegahan IDOR (Security Gate)
1. Karyawan B (`requester_b@kantor.internal`) mencoba mengakses rincian tiket milik Karyawan A melalui manipulasi URL `/tickets/{id_tiket_a}`.
   - *Hasil yang Diharapkan*: HTTP 403 Forbidden.
2. Supervisor A mencoba menyetujui tiket bawahan Supervisor B.
   - *Hasil yang Diharapkan*: HTTP 403 Forbidden.
3. Karyawan biasa mencoba mengirimkan request POST ke rute `/tickets/{id}/it-head-classify-approve`.
   - *Hasil yang Diharapkan*: HTTP 403 Forbidden.
4. Tim IT mencoba menutup tiket secara sepihak tanpa melalui tombol Accept dari Requester.
   - *Hasil yang Diharapkan*: Transisi ditolak oleh server.

---

## 3. Eksekusi Pengujian Otomatis

Jalankan rangkaian pengujian PHPUnit/Pest:

```bash
# Uji seluruh alur fitur dan otorisasi
php artisan test --filter=TicketWorkflowTest

# Uji keamanan konkurensi self-assign
php artisan test --filter=TicketConcurrencyTest

# Uji proteksi IDOR dan Policy
php artisan test --filter=TicketAuthorizationTest
```
