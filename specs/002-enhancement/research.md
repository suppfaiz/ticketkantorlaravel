# Research & Design Decisions: UI/UX Enhancement (002-enhancement)

**Feature Branch**: `002-enhancement`  
**Date**: 2026-10-28  
**Spec Reference**: [spec.md](./spec.md)

---

## 1. Collapsible Sidebar Layout Architecture

### Context & Problem
Aplikasi sebelumnya menggunakan navigasi horizontal atas (`layouts/navigation.blade.php`), yang membatasi ruang vertikal dan kurang optimal untuk aplikasi enterprise dengan banyak peran serta tabel data yang lebar. Pengguna menginginkan modern collapsible sidebar di sisi kiri yang bersih, hemat ruang, dan responsif.

### Decision
Mengimplementasikan komponen Sidebar kiri terintegrasi pada `resources/views/layouts/sidebar.blade.php` yang dipanggil di dalam `resources/views/layouts/app.blade.php`.
- **State Management**: Alpine.js komponen `x-data="{ sidebarOpen: false, sidebarCollapsed: $persist(false).as('it_helpdesk_sidebar_collapsed') }"` (atau fallback ke standard `localStorage` jika plugin persist tidak terpasang).
- **Desktop (Screen >= `md` / `lg`)**:
  - Lebar saat terbuka: `w-64` (256px) dengan label menu + ikon SVG.
  - Lebar saat diciutkan (*collapsed*): `w-20` (80px) hanya menampilkan ikon dengan tooltip saat hover.
  - Tombol toggle collapse di bagian footer sidebar atau header sidebar.
- **Mobile (Screen < `md`)**:
  - Sidebar tersembunyi secara default; tombol hamburger di navbar atas membuka sidebar sebagai slide-over off-canvas drawer dengan backdrop semi-transparan `bg-slate-900/50`.
- **Top App Bar**:
  - Ringkas setinggi `h-16`, menampilkan tombol hamburger (mobile), breadcrumb/page title, dan profil dropdown pengguna di pojok kanan atas.

### Alternatives Considered
- *Header Navigation Horisontal Saja*: Ditolak karena memakan ruang atas dan tidak memberikan rasa aplikasi enterprise modern yang lega.
- *Heavy JS Sidebar Package*: Ditolak per Constitution Prinsip V (YAGNI & Simplicity). Alpine.js bawaan Laravel Breeze sudah sangat mumpuni.

---

## 2. Visual Lifecycle Stepper Component (5-Tahap)

### Context & Problem
Pengguna kesulitan mengidentifikasi status alur bisnis tiket secara seketika jika hanya melihat teks badge status. Sistem memiliki 9 enum status backend yang perlu dipetakan secara visual ke dalam 5 tahapan bisnis (Tahap A s/d Tahap E).

### Decision
Membuat Blade component `<x-ticket-stepper :ticket="$ticket" />` yang ditempatkan di bagian atas halaman `tickets/show.blade.php`.
- **Pemetaan Status ke Tahapan**:
  - **Tahap A (Pengajuan)**: Selalu selesai (`completed`) setelah tiket dibuat.
  - **Tahap B (Approval Atasan)**:
    - *Active*: status `menunggu_approval_atasan`.
    - *Completed*: status lolos approval atasan.
    - *Rejected*: status `ditolak_atasan`.
    - *Revision*: status `revisi_requester`.
  - **Tahap C (Review & Rapat Kabag IT)**:
    - *Active*: status `menunggu_approval_it_head`, `menunggu_rapat_major`, `menunggu_notulensi`, `menunggu_persetujuan_notulensi`.
    - *Completed*: status `siap_dikerjakan` atau tahap berikutnya.
    - *Rejected*: status `ditolak_it_head`.
  - **Tahap D (Pengerjaan IT)**:
    - *Active*: status `siap_dikerjakan` (antrean) atau `dalam_pengerjaan`.
    - *Completed*: status `menunggu_review_user` atau `selesai`.
  - **Tahap E (Review Requester & Selesai)**:
    - *Active*: status `menunggu_review_user` atau `revisi_hasil_pekerjaan`.
    - *Completed*: status `selesai`.
- **Visual Design**:
  - Tahap Selesai: Lingkaran hijau lembut (`bg-emerald-50 text-emerald-600 border border-emerald-300`) dengan ikon centang SVG.
  - Tahap Aktif: Lingkaran biru korporat (`bg-blue-600 text-white shadow-sm ring-4 ring-blue-100`) dengan nomor urut atau ikon aksi berdenyut lembut.
  - Tahap Belum/Menunggu: Lingkaran abu-abu netral (`bg-slate-100 text-slate-400 border border-slate-200`).
  - Tahap Ditolak/Revisi: Lingkaran amber/merah dengan tanda seru atau silang.
  - Garis penghubung horizontal responsif antar langkah yang menyesuaikan warna tahap sebelumnya.

### Alternatives Considered
- *Timeline Vertikal Tunggal*: Ditolak untuk ringkasan lifecycle karena memakan ruang vertikal terlalu panjang; timeline vertikal tetap digunakan untuk riwayat audit (*audit log trail*).

---

## 3. Interactive Modal Dialogs untuk Tindakan Keputusan

### Context & Problem
Pada implementasi sebelumnya, form aksi (Setujui, Tolak, Minta Revisi, Ambil Tiket, dsb.) berada dalam panel besar yang menumpuk di halaman tiket detail. Pengguna menginginkan interaksi modal dialog pop-up yang terfokus untuk mencegah klik tidak sengaja dan menjaga tampilan detail tiket tetap bersih.

### Decision
Memanfaatkan dan memperkaya komponen `resources/views/components/modal.blade.php` bawaan Breeze atau custom Alpine.js modal wrapper:
- Tombol aksi utama (misal: "Setujui", "Tolak", "Minta Revisi", "Selesaikan Pekerjaan", "Terima / Revisi Ulasan") ditempatkan di Action Bar yang rapi.
- Menekan tombol membuka dialog modal terfokus:
  1. **Modal Tolak (Supervisor / IT Head)**: Berisi field wajib `catatan_alasan` (textarea) dengan tombol konfirmasi merah bahaya.
  2. **Modal Revisi (Supervisor / IT Head)**: Berisi field wajib `catatan_revisi` (textarea) dengan tombol konfirmasi amber peringatan.
  3. **Modal Setujui (Supervisor / IT Head)**: Konfirmasi persetujuan (dan pemilihan klasifikasi Minor/Major untuk IT Head) dengan tombol hijau konfirmasi.
  4. **Modal Selesaikan Pekerjaan (IT Dev)**: Ringkasan pengerjaan dan konfirmasi submit untuk review.
  5. **Modal Review Pemohon (Requester)**: Opsi Terima Solusi (rating/catatan opsional) atau Ajukan Komplain/Revisi (catatan wajib).
- **Aksesibilitas & Keamanan**:
  - Mendukung tombol `Esc` untuk menutup dialog.
  - Backdrop blur/dimming halus `bg-slate-900/40`.
  - Form di dalam modal menyertakan `@csrf` dan validasi error bawaan Laravel.
  - Tidak mengubah URL endpoint backend sama sekali (100% kompatibel dengan route dan controller yang ada).

---

## 4. Design System Tokens & Enterprise Light Mode Styling

### Context & Problem
Aplikasi harus mematuhi panduan `DESIGN.md` secara ketat: *Clean Minimalist Enterprise Light Mode*, tidak ada dark mode, tidak ada warna neon, tidak ada background pastel di area besar.

### Decision
- **Color Palette**:
  - Background Utama: `#FFFFFF` (kartu dan panel putih bersih).
  - Background Halaman: `#F8FAFC` (`bg-slate-50` / `bg-bg-subtle`).
  - Border: `#E2E8F0` (`border-slate-200` / `border-border-subtle`).
  - Teks Utama: `#0F172A` (`text-slate-900` / `text-text-main`).
  - Teks Redup: `#64748B` (`text-slate-500` / `text-text-muted`).
  - Aksen Korporat: `#2563EB` (`bg-blue-600`, `hover:bg-blue-700`).
  - Status Berhasil/Selesai: `#059669` (`emerald-600`) dengan badge `bg-emerald-50 text-emerald-700 border-emerald-200`.
  - Status Bahaya/Ditolak: `#DC2626` (`red-600`) dengan badge `bg-red-50 text-red-700 border-red-200`.
  - Status Peringatan/Revisi: `#D97706` (`amber-600`) dengan badge `bg-amber-50 text-amber-700 border-amber-200`.
- **Card & Table Styling**:
  - Kartu metrik (KPI Cards): `bg-white border border-slate-200 rounded-xl p-5 shadow-xs hover:border-slate-300 transition`.
  - Ikon metrik: Ikon SVG netral dalam kontainer lingkaran warna lembut `w-10 h-10 rounded-lg flex items-center justify-center`.
  - Tabel: Header latar `bg-slate-50/75` dengan teks kecil huruf kapital semibold `text-xs uppercase tracking-wider text-slate-500`, baris hover halus `hover:bg-slate-50/50 transition-colors`.

---

## 5. File Upload Dropzone Component

### Context & Problem
Formulir buat tiket dan revisi saat ini menggunakan input file standar HTML `<input type="file">` yang kaku dan tidak mengindikasikan file yang terpilih secara atraktif.

### Decision
Membuat area dropzone visual dengan Tailwind & Alpine.js:
- Kotak bergaris putus-putus (`border-2 border-dashed border-slate-300 hover:border-blue-400 bg-slate-50/50 hover:bg-blue-50/20 rounded-xl p-6 text-center transition cursor-pointer`).
- Menampilkan ikon dokumen SVG yang elegan, teks petunjuk ("Klik untuk memilih file atau seret file ke sini"), dan keterangan batasan ("PDF, DOCX, JPG, PNG maks. 10MB").
- Alpine.js mendeteksi file yang dipilih dan menampilkan chip/kartu pratinjau nama file beserta ukuran (KB/MB) dengan tombol hapus/reset sebelum dikirim ke backend.

---

## 6. Testing & Non-Regression Strategy

### Context & Problem
Aplikasi memiliki 60 test otomatis yang menguji logika state machine, authorization, CSRF, IDOR, dan concurrency. Perubahan UI tidak boleh merusak satu pun dari test ini.

### Decision
- Seluruh form name, attribute name, hidden input, dan URL endpoint method POST/PATCH tetap dipertahankan 100% identik.
- Seluruh selector teks penting yang diuji di test (misal: "Menunggu Approval Atasan", "Setujui", "Tolak", "Revisi", "Buat Tiket", dll.) tetap dipertahankan.
- Menjalankan `php artisan test` dan `npm run build` di setiap iterasi untuk memverifikasi 60 test tetap hijau (100% pass) dengan 0 deprecation warning.
