# Quickstart & Verification Guide: UI/UX Enhancement (002-enhancement)

**Feature Branch**: `002-enhancement`  
**Date**: 2026-10-28  
**Spec Reference**: [spec.md](./spec.md)

---

## 1. Prerequisites & Setup

1. **Pastikan Database & Dependency Terpasang**:
   ```bash
   composer install
   npm install
   php artisan migrate --seed
   npm run build
   ```

2. **Jalankan Aplikasi Web**:
   ```bash
   php artisan serve
   ```
   Akses di peramban: `http://localhost:8000`

---

## 2. Skenario Pengujian & Validasi Fitur

### Skenario 1: Navigasi Sidebar Collapsible & Responsif
- **Langkah**:
  1. Buka `http://localhost:8000/login` dan masuk dengan salah satu akun (misal: `supervisor@example.com`).
  2. Perhatikan tata letak: Sidebar berada di sisi kiri dengan gaya bersih enterprise (*white background*, border lembut, ikon rapi, teks label jelas).
  3. Klik tombol toggle di bagian bawah/atas sidebar untuk menciutkan (*collapse*).
  4. **Hasil yang diharapkan**: Sidebar menciut menjadi `w-20` (80px), hanya menampilkan ikon menu dengan tooltip, dan area konten tiket meluas. Refresh halaman; status ciut tetap bertahan (*persisted*).
  5. Buka DevTools dan ubah ukuran layar ke perangkat seluler (lebar 375px).
  6. **Hasil yang diharapkan**: Sidebar desktop tersembunyi, muncul tombol hamburger di navbar atas. Menekan hamburger membuka slide-over drawer dengan backdrop gelap halus.

### Skenario 2: Modern KPI Cards pada Dashboard Seluruh Peran
- **Langkah**:
  1. Buka dashboard untuk masing-masing peran:
     - Requester: `requester@example.com`
     - Supervisor: `supervisor@example.com`
     - IT Head: `ithead@example.com`
     - IT Developer: `dev1@example.com`
  2. **Hasil yang diharapkan**: Seluruh kartu metrik memiliki visual tipografi tegas, angka statistik yang tebal, ikon SVG fungsional yang elegan, dan latar belakang kartu putih bersih dengan batas border halus.

### Skenario 3: Visual Lifecycle Stepper 5-Tahap pada Detail Tiket
- **Langkah**:
  1. Masuk sebagai Requester dan buka salah satu tiket aktif atau buat tiket baru.
  2. Buka halaman detail tiket (`/tickets/{id}`).
  3. **Hasil yang diharapkan**:
     - Di bawah judul tiket, tampil komponen linimasa horizontal 5-tahap (Tahap A: Pengajuan s/d Tahap E: Review & Selesai).
     - Tahap yang sudah selesai bertanda centang hijau.
     - Tahap yang sedang aktif berwarna biru mencolok dengan ring pendar halus.
     - Tahap yang belum dicapai berwarna abu-abu netral.
     - Jika tiket ditolak/revisi, node terkait berwarna merah/amber dengan keterangan status.

### Skenario 4: Modal Dialog Interaktif untuk Aksi Keputusan
- **Langkah**:
  1. Buka tiket berstatus `menunggu_approval_atasan` sebagai Supervisor (`supervisor@example.com`).
  2. Di bagian aksi keputusan, klik tombol **"Tolak Tiket"** atau **"Minta Revisi"**.
  3. **Hasil yang diharapkan**: Muncul dialog modal terfokus di tengah layar dengan backdrop redup.
  4. Tekan tombol `Esc` pada keyboard atau klik di luar modal; dialog tertutup secara mulus tanpa mengirim data.
  5. Buka kembali modal, ketikkan alasan penolakan/revisi, lalu klik konfirmasi kirim.
  6. **Hasil yang diharapkan**: Form terkirim dengan proteksi CSRF, halaman reload, pesan flash sukses tampil dalam format kartu modern.

### Skenario 5: Area Dropzone Berkas Unggahan
- **Langkah**:
  1. Masuk sebagai Requester dan buka halaman **Buat Tiket** (`/tickets/create`).
  2. Periksa area unggah lampiran pendukung.
  3. **Hasil yang diharapkan**: Area dropzone modern dengan garis putus-putus (*dashed border*), ikon berkas elegan, dan teks panduan batas ukuran (10MB).
  4. Pilih berkas melalui klik atau seret (*drag-and-drop*).
  5. **Hasil yang diharapkan**: Tampil ringkasan berkas terpilih (nama dan ukuran file) beserta opsi batal/hapus sebelum form disubmit.

---

## 3. Verifikasi Bebas Regresi (Automated Tests)

Jalankan seluruh pengujian otomatis:
```bash
php artisan test
```
**Ekspektasi**: 60 test lulus (0 failed, 0 deprecations, 0 errors).
