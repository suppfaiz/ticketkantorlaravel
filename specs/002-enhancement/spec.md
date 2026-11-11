# Feature Specification: Peningkatan Antarmuka Pengguna (UI/UX Enhancement)

**Feature Branch**: `002-enhancement`

**Created**: 2026-10-28

**Status**: Ready for Planning

**Input**: User description: "saya ingin memperbaiki ui nya dong"

## Clarifications

### Resolved Clarifications

- **Q1 (Fokus Utama UI/UX)**: Comprehensive UI enhancement di seluruh layar utama (Dashboard 4 peran, Detail Tiket, Tabel Index Tiket, dan Formulir Pengajuan/Revisi).
- **Q2 (Tata Letak Navigasi)**: Sidebar Modern Collapsible di sisi kiri dengan gaya Clean Enterprise (ikon + label, minimal footprint, ruang konten maksimal, toggle collapse/expand ergonomis, drawer mobile).
- **Q3 (Komponen Interaktif Detail Tiket)**: Dialog Modal Interaktif (*pop-up dialogs*) untuk seluruh aksi kritis alur kerja (Setujui, Tolak dengan alasan wajib, Minta Revisi dengan catatan, Selesai Pengerjaan, dan Ulasan Pemohon).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Tata Letak Navigasi Sidebar Collapsible & Dashboard Enterprise (Priority: P1)

Sebagai Seluruh Pengguna (Requester, Supervisor, IT Head, IT Developer), saya ingin navigasi aplikasi menggunakan Sidebar kiri yang modern dan collapsible serta tampilan dashboard yang bersih dan terstruktur dengan kartu metrik yang jelas, sehingga ruang kerja terasa luas, rapi, dan efisien.

**Why this priority**: Navigasi dan dashboard merupakan gerbang utama interaksi seluruh aktor. Sidebar yang dapat diciutkan memberikan efisiensi ruang layar maksimal untuk tabel dan konten tiket.

**Independent Test**: Dapat diuji secara independen dengan membuka halaman dashboard untuk setiap peran, mencoba toggle collapse/expand sidebar, dan memverifikasi tata letak kartu metrik, navigasi, dan konsistensi warna desain light-mode modern.

**Acceptance Scenarios**:

1. **Given** Pengguna login ke sistem, **When** Membuka dashboard, **Then** Sistem menampilkan sidebar kiri collapsible dengan menu sesuai peran pengguna dan kartu metrik statistik dengan visual tipografi kuat, border halus, serta ikon representatif.
2. **Given** Pengguna mengklik tombol toggle sidebar, **When** Sidebar diciutkan, **Then** Sidebar mengecil menampilkan ikon menu saja dengan tooltip, memberikan ruang horizontal maksimal untuk konten utama.
3. **Given** Pengguna mengakses aplikasi melalui perangkat seluler (tablet/smartphone), **When** Membuka menu navigasi, **Then** Sidebar muncul sebagai slide-over drawer yang dapat ditutup kembali dengan mulus tanpa merusak tata letak layar.

---

### User Story 2 - Visualisasi Stepper Progres 5-Tahap pada Detail Tiket (Priority: P2)

Sebagai Karyawan pemohon dan tim penilai, saya ingin melihat indikator linimasa visual (Visual Lifecycle Stepper) berbentuk tahapan horizontal (Tahap A s/d E) pada halaman detail tiket, sehingga status posisi tiket dalam siklus bisnis dapat langsung dipahami dalam 1 detik tanpa harus membaca teks panjang.

**Why this priority**: Mengubah status teks biasa menjadi representasi visual alur kerja (Workflow Stepper) meningkatkan kejelasan proses bisnis helpdesk secara dramatis.

**Independent Test**: Membuka halaman detail tiket di berbagai status (misal: Tahap B, Tahap C Major, Tahap D, dan Selesai) dan memverifikasi bahwa indikator langkah aktif, langkah selesai (centang hijau), dan langkah tertunda terwarnai secara akurat.

**Acceptance Scenarios**:

1. **Given** Tiket berada pada status `Menunggu Approval Atasan`, **When** Membuka halaman rincian tiket, **Then** Stepper menampilkan Tahap A selesai dan Tahap B sebagai tahapan aktif berwarna sorotan dengan keterangan nama atasan.
2. **Given** Tiket berkategori Major pada status rapat, **When** Membuka detail tiket, **Then** Stepper menyorot Tahap C dengan penanda khusus "Rapat Koordinasi Major & Notulensi".
3. **Given** Tiket telah berstatus `Selesai`, **When** Membuka detail tiket, **Then** Seluruh tahapan A hingga E bertanda centang selesai hijau dengan ringkasan durasi total.

---

### User Story 3 - Penyempurnaan Formulir & Modal Interaksi Tindakan Kritis (Priority: P3)

Sebagai Petugas approver (Supervisor, Kabag IT) dan Requester, saya ingin melakukan tindakan persetujuan, penolakan, revisi, pengerjaan, atau ulasan melalui dialog modal yang terfokus dengan validasi langsung dan pesan umpan balik visual (*toast notification/flash feedback*) yang jelas, sehingga interaksi terasa modern dan minim kesalahan klik.

**Why this priority**: Mengurangi beban kognitif pengguna saat melakukan aksi penting (seperti tolak atau revisi) dan mencegah submit tidak sengaja.

**Independent Test**: Melakukan tindakan approve, reject, atau revisi dan memverifikasi dialog modal konfirmasi interaktif serta notifikasi sukses yang muncul dengan animasi halus.

**Acceptance Scenarios**:

1. **Given** Atasan atau Kabag IT ingin menolak atau meminta revisi tiket, **When** Menekan tombol aksi, **Then** Sistem menampilkan dialog modal terfokus dengan area input alasan/catatan wajib sebelum aksi diproses.
2. **Given** Requester menerima tiket yang selesai dikerjakan, **When** Menekan tombol Ulas / Selesaikan, **Then** Modal dialog konfirmasi ulasan muncul dengan opsi terima atau minta revisi hasil pengerjaan.
3. **Given** Pengguna mengunggah lampiran berkas pada form tiket, **When** Memilih berkas, **Then** Tampil area dropzone interaktif beserta daftar pratinjau nama berkas dan ukuran file yang dipilih sebelum form disubmit.

---

### Edge Cases

- **Layar Ultra-Wide vs Layar Kecil**: Tata letak grid kartu dan tabel tidak boleh melar secara berlebihan di layar monitor lebar (terbatas pada kontainer ideal), serta tabel data di perangkat seluler wajib memiliki pembungkus gulir horizontal halus (*smooth scroll*).
- **Teks Deskripsi atau Catatan Sangat Panjang**: Kotak catatan revisi dan ulasan tidak boleh merusak layout; teks panjang wajib memiliki pemenggalan kata otomatis (*word-break*) dan pembatas tinggi teratur.
- **Warna Kontras Aksesibilitas**: Seluruh warna teks dan badge status harus memenuhi standar kontras WCAG AA agar mudah dibaca oleh seluruh karyawan.
- **Transisi Modal saat Keyboard Esc atau Backdrop Click**: Dialog modal harus dapat ditutup dengan menekan tombol ESC atau mengklik backdrop luar tanpa memicu submit form.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Sistem HARUS mempertahankan prinsip desain Light Mode dasar putih bersih (`#FFFFFF` dan `#F8FAFC`) dengan border halus (`#E2E8F0`) sesuai pedoman visual korporat `DESIGN.md`.
- **FR-002**: Halaman detail tiket HARUS menyajikan komponen visual *Workflow Stepper* interaktif 5-tahap (Tahap A: Pengajuan, Tahap B: Atasan, Tahap C: Kabag IT, Tahap D: Pengerjaan IT, Tahap E: Review & Selesai) yang secara dinamis merefleksikan status saat ini.
- **FR-003**: Komponen kartu statistik (KPI Cards) pada seluruh dashboard HARUS diperkaya dengan ikon ilustratif SVG netral, bobot angka metrik yang tegas, dan label keterangan yang terbaca jelas.
- **FR-004**: Tabel daftar tiket HARUS dilengkapi dengan baris interaktif berpendar halus saat disentuh kursor (*subtle row hover*), badge status dengan warna fungsional lembut, dan pembagi baris tipis.
- **FR-005**: Formulir pengajuan dan revisi tiket HARUS menampilkan area unggah berkas (*file dropzone area*) yang informatif mencantumkan batasan format dan ukuran secara eksplisit.
- **FR-006**: Tombol tindakan utama (Setujui, Minta Revisi, Tolak, Ambil Tiket, Selesai) HARUS memiliki hierarki warna visual yang konsisten (Hijau untuk Setujui, Amber untuk Revisi, Merah untuk Tolak, Slate/Biru untuk Aksi Primer).
- **FR-007**: Sistem HARUS menampilkan notifikasi *flash message* (sukses / error) dengan visual card yang modern, ikon status yang jelas, dan transisi masuk yang mulus.
- **FR-008**: Tampilan riwayat jejak audit (*audit trail timeline*) HARUS ditata dengan garis alur vertikal yang elegan, penanda titik waktu (*bullet timestamp*), dan pembeda peran aktor yang mudah dipindai mata.
- **FR-009**: Sistem HARUS menyediakan tata letak Sidebar Collapsible di sisi kiri (ikon + teks label, tombol pemicu ciut/kembang yang persisten, dan drawer responsif pada perangkat bergerak) dengan mempertahankan ruang konten tetap bersih dan luas.
- **FR-010**: Seluruh aksi keputusan penting (Approve, Reject dengan alasan, Revise dengan catatan, Selesaikan Tiket, Ulasan Review) pada detail tiket HARUS menggunakan dialog modal interaktif terfokus dengan validasi input sebelum submit.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% halaman utama (Login, Dashboard 4 Peran, Index Tiket, Detail Tiket, dan Formulir Tiket) mengadopsi standar visual modern Light Mode terpadu dengan Sidebar Collapsible yang bersih.
- **SC-002**: Pengguna dapat mengidentifikasi tahapan alur tiket saat ini melalui Visual Stepper dalam waktu kurang dari 3 detik setelah membuka halaman detail.
- **SC-003**: Seluruh dialog modal aksi alur kerja (Persetujuan, Penolakan, Revisi, Penyelesaian) dapat dibuka, dibatalkan, dan disubmit dengan feedback visual jelas tanpa hambatan navigasi.
- **SC-004**: 100% komponen formulir, kartu, dan tabel sepenuhnya responsif dan dapat dioperasikan secara ergonomis pada perangkat seluler (lebar layar >= 360px) tanpa layout patah.
- **SC-005**: Skor keterbacaan kontras teks terhadap latar belakang mencapai kepatuhan standar WCAG AA (kontras minimum 4.5:1 untuk teks normal).
- **SC-006**: Seluruh 60 skenario pengujian fungsional dan integritas bisnis yang ada tetap lulus 100% tanpa regresi.

---

## Assumptions

- Perubahan antarmuka berfokus pada penyempurnaan Blade template, komponen UI, Tailwind utility classes, dan ikon SVG tanpa mengubah integritas alur bisnis 5-tahap atau merombak logika backend `TicketStateMachine`.
- Tema desain tetap berpegang teguh pada gaya *Clean Minimalist Enterprise Light Mode* dasar putih sesuai ketetapan sebelumnya (tidak beralih ke dark mode atau tema neon).
- Komponen interaktif (Sidebar toggle, modal dialogs, dropzone preview) menggunakan JavaScript ringan (Alpine.js bawaan Laravel Breeze) tanpa memerlukan penambahan framework SPA besar seperti React atau Vue.
