# Feature Specification: Aplikasi IT Helpdesk Internal Perusahaan

**Feature Branch**: `001-it-helpdesk`

**Created**: 2026-10-28

**Status**: Draft

**Input**: User description: "Bangun aplikasi IT Helpdesk internal perusahaan untuk mengelola permintaan bantuan IT dari karyawan secara terstruktur, mulai dari pengajuan request, persetujuan atasan langsung, persetujuan Kepala Bagian IT, pengerjaan oleh tim IT, hingga review dan penutupan tiket oleh user. Aplikasi harus mengikuti alur bisnis yang dijelaskan di bawah ini. Jangan menambahkan tahapan approval atau mengubah alur bisnis tanpa dasar dari spesifikasi ini."

## Clarifications

### Session 2026-10-28

- Q: Ke manakah tiket harus dialirkan ketika pemohon (requester) telah memperbaiki dan mengirimkan kembali tiket yang sebelumnya diminta revisi oleh Kepala Bagian IT? (FR-012) → A: Tiket wajib melalui persetujuan ulang Atasan Langsung (`Menunggu Approval Atasan`) sebelum kembali ke antrean Kepala Bagian IT (Option B).
- Q: Siapa aktor yang memiliki hak akses untuk memasukkan notulensi rapat Major ke dalam sistem sebelum diverifikasi dan disetujui oleh Kepala Bagian IT? (FR-013) → A: Requester maupun Kepala Bagian IT sama-sama berwenang memasukkan notulensi ke dalam sistem, dengan persetujuan akhir tetap eksklusif di tangan Kepala Bagian IT (Option A).
- Q: Ke manakah tiket dialokasikan ketika pemohon (requester) meminta revisi atas hasil pekerjaan IT pada tahap review? (FR-020) → A: Otomatis kembali ke developer IT penanggung jawab sebelumnya untuk diperbaiki (Option A).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Pengajuan Tiket & Approval Atasan Langsung (Priority: P1)

Sebagai Karyawan (Requester), saya ingin mengajukan permohonan bantuan IT melalui formulir terstruktur dan secara otomatis diteruskan kepada Atasan Langsung saya, sehingga permintaan bantuan resmi dapat diverifikasi oleh penanggung jawab operasional saya sebelum diproses lebih lanjut. Sebagai Atasan Langsung, saya ingin memeriksa permohonan bawahan saya, menyetujui, menolak dengan alasan, atau meminta perbaikan agar permintaan yang diteruskan ke bagian IT adalah kebutuhan yang valid.

**Why this priority**: Merupakan gerbang masuk utama (entry point) seluruh permintaan IT. Tanpa mekanisme pengajuan dan persetujuan lini pertama ini, tidak ada permintaan yang dapat diproses secara sah dalam organisasi.

**Independent Test**: Dapat diuji secara independen dengan skenario pengajuan tiket oleh karyawan, verifikasi penerimaan notifikasi/tugas oleh atasan langsung yang bersangkutan, dan pengujian 3 cabang keputusan atasan: Setujui (lanjut ke tahap berikutnya), Tolak (berhenti dengan alasan), dan Revisi (kembali ke karyawan untuk diedit).

**Acceptance Scenarios**:

1. **Given** Karyawan yang telah terotentikasi membuka formulir permohonan IT, **When** Karyawan mengisi judul, rincian permohonan, kategori permintaan dan mengirimkan formulir, **Then** Sistem membuat nomor tiket unik, mencatat identitas requester dan unit kerja, menetapkan status `Menunggu Approval Atasan`, dan meneruskan tiket ke antrean atasan langsung requester.
2. **Given** Tiket berstatus `Menunggu Approval Atasan`, **When** Atasan Langsung memilih tindakan "Setujui" beserta catatan persetujuan, **Then** Sistem mencatat keputusan, waktu, dan identitas atasan ke dalam log riwayat, lalu memperbarui status tiket menjadi `Menunggu Approval Kepala Bagian IT`.
3. **Given** Tiket berstatus `Menunggu Approval Atasan`, **When** Atasan Langsung memilih tindakan "Tolak" dan mengisi alasan penolakan wajib, **Then** Sistem memperbarui status tiket menjadi `Ditolak Atasan`, menyimpan alasan penolakan, menghentikan alur pengerjaan tiket, dan menampilkan tiket di daftar tiket ditolak milik requester dan atasan.
4. **Given** Tiket berstatus `Menunggu Approval Atasan`, **When** Atasan Langsung memilih tindakan "Revisi" dengan menyertakan catatan perbaikan, **Then** Sistem memperbarui status menjadi `Revisi Requester` dan memberikan hak akses edit kembali kepada Requester untuk memperbaiki data.
5. **Given** Tiket berstatus `Revisi Requester`, **When** Requester memperbarui rincian pengajuan dan mengirimkan kembali permohonan, **Then** Sistem memperbarui status kembali menjadi `Menunggu Approval Atasan` untuk diverifikasi ulang oleh Atasan Langsung.

---

### User Story 2 - Persetujuan & Klasifikasi oleh Kepala Bagian IT (Priority: P2)

Sebagai Kepala Bagian IT, saya ingin memeriksa tiket yang telah disetujui atasan langsung, menentukan klasifikasinya sebagai Minor atau Major, menyetujui, meminta revisi, menolak, atau mengadakan rapat koordinasi untuk tiket Major serta menyetujui notulensi rapat, sehingga kapasitas dan ruang lingkup pekerjaan tim IT terkontrol secara akurat.

**Why this priority**: Merupakan tahap evaluasi teknis dan penentuan kelayakan sumber daya IT. Membedakan penanganan tugas rutin (Minor) dengan proyek/perubahan berskala besar (Major) yang wajib memiliki kesepakatan ruang lingkup.

**Independent Test**: Dapat diuji secara independen dengan memberikan tiket berstatus `Menunggu Approval Kepala Bagian IT`, lalu menguji dua jalur klasifikasi: (1) Jalur Minor disetujui langsung menuju antrean pengerjaan; (2) Jalur Major yang mewajibkan status rapat, pengisian notulensi, dan persetujuan notulensi sebelum tiket dapat masuk antrean pengerjaan.

**Acceptance Scenarios**:

1. **Given** Tiket berstatus `Menunggu Approval Kepala Bagian IT`, **When** Kepala Bagian IT menetapkan klasifikasi "Minor" dan memilih "Setujui", **Then** Sistem memperbarui status tiket menjadi `Siap Dikerjakan` dan tiket tersedia di antrean pengerjaan Tim IT.
2. **Given** Tiket berstatus `Menunggu Approval Kepala Bagian IT`, **When** Kepala Bagian IT menetapkan klasifikasi "Major", **Then** Sistem mengubah status tiket menjadi `Menunggu Rapat Major`, dan tiket TIDAK dapat masuk antrean pengerjaan Tim IT.
3. **Given** Tiket berstatus `Menunggu Rapat Major`, **When** Rapat bersama requester telah terlaksana dan notulensi dimasukkan ke sistem oleh Requester atau Kepala Bagian IT, **Then** Status tiket berubah menjadi `Menunggu Persetujuan Notulensi`.
4. **Given** Tiket berstatus `Menunggu Persetujuan Notulensi`, **When** Kepala Bagian IT memeriksa dan menyetujui notulensi rapat, **Then** Sistem mencatat persetujuan notulensi dan mengubah status tiket menjadi `Siap Dikerjakan`.
5. **Given** Tiket pada tahap pemeriksaan Kepala Bagian IT (Minor maupun Major), **When** Kepala Bagian IT memilih tindakan "Tolak" dengan menyertakan alasan penolakan, **Then** Sistem mengubah status tiket menjadi `Ditolak Kepala Bagian IT` dan tiket dihentikan dari seluruh alur pengerjaan.
6. **Given** Tiket pada tahap pemeriksaan Kepala Bagian IT, **When** Kepala Bagian IT memilih tindakan "Revisi", **Then** Sistem mengubah status tiket menjadi `Revisi Requester oleh Kepala Bagian IT` dengan catatan perbaikan yang wajib dibaca oleh requester.
7. **Given** Tiket berstatus `Revisi Requester oleh Kepala Bagian IT`, **When** Requester memperbarui rincian permohonan dan mengirimkan kembali tiket, **Then** Sistem memperbarui status menjadi `Menunggu Approval Atasan` agar Atasan Langsung memverifikasi ulang perubahan sebelum diteruskan kembali ke Kepala Bagian IT.

---

### User Story 3 - Antrean Pengerjaan & Self-Assign oleh Tim IT (Priority: P3)

Sebagai Anggota Tim IT / Developer, saya ingin melihat antrean tiket yang sudah disetujui, memilih tiket secara mandiri (self-assign) tanpa risiko konflik perebutan tiket dengan rekan lain, memperbarui kemajuan pengerjaan, mencatat laporan aktivitas penanganan, dan meneruskan tiket ke tahap review user ketika pekerjaan selesai.

**Why this priority**: Memastikan distribusi pekerjaan IT berlangsung transparan, akuntabel, terlindung dari tabrakan tugas (concurrency conflict), dan progresnya dapat dipantau oleh pemohon.

**Independent Test**: Dapat diuji secara mandiri dengan tiket berstatus `Siap Dikerjakan`. IT Developer melakukan self-assign (memastikan status berubah ke `Dalam Pengerjaan` dan tiket tidak dapat diambil developer lain), mencatat log progres aktivitas, dan menekan tombol selesai pengerjaan hingga tiket berpindah ke `Menunggu Review User`.

**Acceptance Scenarios**:

1. **Given** Tiket berstatus `Siap Dikerjakan`, **When** Developer IT memilih tiket dan melakukan tindakan self-assign, **Then** Sistem menetapkan Developer tersebut sebagai penanggung jawab resmi tiket, mengubah status tiket menjadi `Dalam Pengerjaan`, dan mencatat waktu mulai pengerjaan.
2. **Given** Tiket berstatus `Siap Dikerjakan` yang sedang di-claim oleh Developer A, **When** Developer B mencoba melakukan self-assign pada detik yang sama terhadap tiket tersebut, **Then** Sistem mengizinkan Developer A dan menolak permintaan Developer B dengan pesan bahwa tiket telah diambil oleh anggota lain.
3. **Given** Tiket berstatus `Dalam Pengerjaan`, **When** Developer penanggung jawab menambahkan catatan aktivitas penanganan (progress notes/milestones), **Then** Catatan tersebut tersimpan dalam riwayat pengerjaan tiket dan dapat dilihat oleh requester serta atasan sesuai hak akses.
4. **Given** Tiket berstatus `Dalam Pengerjaan`, **When** Developer penanggung jawab menyelesaikan pekerjaan teknis dan menekan tombol "Selesai Dikerjakan", **Then** Sistem mengubah status tiket menjadi `Menunggu Review User` dan memberitahukan requester bahwa hasil pekerjaan siap diperiksa.

---

### User Story 4 - Review Hasil Pekerjaan & Penutupan Tiket oleh Requester (Priority: P4)

Sebagai Karyawan (Requester), saya ingin memeriksa hasil pekerjaan yang diselesaikan oleh tim IT, kemudian memberikan persetujuan (Accept) untuk menutup tiket secara permanen disertai ulasan/feedback, atau meminta revisi (Revisi) disertai alasan spesifik jika hasil pekerjaan belum sesuai kebutuhan.

**Why this priority**: Menjamin kepuasan pengguna akhir dan kesesuaian solusi sebelum tiket ditutup resmi. Tiket tidak boleh ditutup sepihak oleh tim IT tanpa validasi penerima manfaat.

**Independent Test**: Dapat diuji secara independen dengan tiket berstatus `Menunggu Review User`. Requester menguji dua cabang: (1) Memilih "Accept" -> status menjadi `Selesai` beserta feedback; (2) Memilih "Revisi" -> status menjadi `Revisi Hasil Pekerjaan` dengan alasan revisi, dan tiket kembali ke penanganan IT.

**Acceptance Scenarios**:

1. **Given** Tiket berstatus `Menunggu Review User`, **When** Requester memeriksa hasil pekerjaan dan memilih tindakan "Accept" disertai feedback kepuasan, **Then** Sistem mencatat waktu penutupan, identitas reviewer, feedback, dan mengubah status tiket menjadi `Selesai`.
2. **Given** Tiket berstatus `Menunggu Review User`, **When** Requester mendapati hasil belum sesuai dan memilih tindakan "Revisi" dengan mengisi alasan ketidaksesuaian, **Then** Sistem mengubah status tiket menjadi `Revisi Hasil Pekerjaan`, menyimpan alasan revisi, dan mengembalikan tiket secara otomatis kepada penanggung jawab IT sebelumnya untuk diperbaiki.
3. **Given** Tiket berstatus `Revisi Hasil Pekerjaan`, **When** Tim IT menyelesaikan perbaikan dan mengajukan ulang, **Then** Status tiket kembali menjadi `Menunggu Review User` untuk diperiksa kembali oleh Requester.
4. **Given** Pengguna yang bukan merupakan pembuat tiket (Requester) atau bukan penerima kuasa sah, **When** Mencoba melakukan tindakan Accept atau Revisi hasil pengerjaan, **Then** Sistem menolak tindakan tersebut dengan pesan otorisasi tidak sah.

---

### User Story 5 - Dashboard & Penelusuran Riwayat Aktivitas (Priority: P5)

Sebagai seluruh Aktor (Requester, Atasan Langsung, Kepala Bagian IT, Tim IT), saya ingin melihat dashboard yang menampilkan ringkasan tiket sesuai peran saya serta menelusuri riwayat perubahan status dan audit trail tiket secara transparan dan tidak dapat dimanipulasi.

**Why this priority**: Memberikan visibilitas operasional, transparansi SLA, pemantauan beban kerja, dan akuntabilitas audit kepatuhan internal.

**Independent Test**: Menguji tampilan dashboard untuk masing-masing peran, memastikan metrik dan filter status tiket sesuai dengan matriks hak akses masing-masing aktor, serta memastikan audit log menampilkan riwayat kronologis lengkap tanpa opsi hapus/edit.

**Acceptance Scenarios**:

1. **Given** Requester masuk ke aplikasi, **When** Membuka dashboard, **Then** Sistem menampilkan daftar dan jumlah tiket miliknya yang menunggu approval, perlu direvisi, ditolak, sedang diproses, menunggu review, dan selesai.
2. **Given** Atasan Langsung masuk ke aplikasi, **When** Membuka dashboard, **Then** Sistem menampilkan daftar tiket bawahan yang menunggu approval, tiket bawahan yang ditolak, tiket bawahan yang direvisi, serta progres tiket bawahan.
3. **Given** Kepala Bagian IT masuk ke aplikasi, **When** Membuka dashboard, **Then** Sistem menampilkan antrean approval Kepala Bagian IT, perbandingan jumlah tiket Minor vs Major, tiket Major yang menunggu rapat/notulensi, serta tiket yang ditolak/direvisi.
4. **Given** Developer Tim IT masuk ke aplikasi, **When** Membuka dashboard, **Then** Sistem menampilkan antrean tiket siap dikerjakan (`Siap Dikerjakan`), tiket yang sedang ditangani dirinya (`Dalam Pengerjaan`), tiket menunggu review, dan tiket revisi hasil pekerjaan.
5. **Given** Setiap pengguna yang memiliki hak akses melihat rincian tiket, **When** Membuka tab/bagian Riwayat Aktivitas, **Then** Sistem menampilkan riwayat kronologis lengkap: aktor, waktu, aksi perubahan status, dan catatan/alasan terkait.

---

### Edge Cases

- **Konkurensi Self-Assign**: Dua anggota Tim IT mengklik tombol "Ambil Tiket" pada tiket yang sama di milidetik yang sama. Sistem wajib mengunci proses sehingga hanya satu yang berhasil menjadi penanggung jawab, sementara anggota kedua menerima notifikasi informatif bahwa tiket telah diambil.
- **Karyawan Tanpa Atasan Langsung**: Karyawan mencoba mengajukan tiket namun profil organisasinya belum memiliki atasan langsung yang terkonfigurasi. Sistem wajib mencegah pengajuan tiket berstatus menggantung dan menampilkan instruksi untuk melengkapi data atasan melalui administrator.
- **Penolakan atau Permintaan Revisi Tanpa Alasan**: Atasan Langsung atau Kepala Bagian IT memilih "Tolak" atau "Revisi" tanpa mengisi komentar alasan. Sistem wajib menolak submit pada level validasi dan mewajibkan pengisian alasan minimal.
- **Upaya Manipulasi Akses (IDOR)**: Requester A mencoba mengubah nomor tiket pada URL untuk melihat atau menyetujui tiket milik Requester B di unit kerja berbeda. Sistem wajib menolak akses dan mencatat insiden keamanan.
- **Bypass Tahapan Notulensi Major**: Tiket Major yang belum memiliki notulensi rapat yang disetujui oleh Kepala Bagian IT dicoba untuk diambil pengerjaannya oleh IT Developer. Sistem wajib melarang tiket tersebut muncul di antrean `Siap Dikerjakan` dan menolak setiap aksi penugasan.
- **Tiket Ditolak Dicoba Dikerjakan**: Upaya pengalihan status tiket yang telah berstatus `Ditolak Atasan` atau `Ditolak Kepala Bagian IT` langsung ke pengerjaan. Sistem wajib menolak transisi status ilegal tersebut.
- **Review Berulang Kali**: Requester meminta revisi hasil pekerjaan berkali-kali. Sistem harus mempertahankan riwayat setiap putaran revisi dan komentar terdahulu tanpa menimpa data historis.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Sistem HARUS menyediakan formulir pengajuan tiket bantuan IT yang dapat diakses oleh Karyawan terotentikasi, mencakup judul, deskripsi rinci, kategori permintaan, dan dokumen lampiran opsional.
- **FR-002**: Sistem HARUS secara otomatis menghasilkan nomor tiket unik yang tidak mengalami duplikasi meskipun terdapat pengajuan tiket bersamaan.
- **FR-003**: Sistem HARUS secara otomatis mengidentifikasi dan mengaitkan pengajuan tiket dengan Atasan Langsung dari Karyawan pemohon berdasarkan data hierarki organisasi.
- **FR-004**: Sistem HARUS menyediakan fasilitas simpan sebagai `Draft` sebelum tiket diajukan secara resmi oleh Requester.
- **FR-005**: Sistem HARUS mengirimkan tiket yang diajukan ke status `Menunggu Approval Atasan` dan hanya memberikan hak tindakan persetujuan kepada Atasan Langsung dari pemohon bersangkutan.
- **FR-006**: Atasan Langsung HARUS dapat melakukan salah satu dari tiga tindakan: Menyetujui (ke Kepala Bagian IT), Menolak (dengan alasan penolakan wajib), atau Meminta Revisi (dengan instruksi perbaikan wajib).
- **FR-007**: Tiket yang ditolak oleh Atasan Langsung HARUS berpindah ke status `Ditolak Atasan`, tidak dapat diteruskan ke alur pengerjaan, dan alasan penolakannya dapat diakses oleh Requester.
- **FR-008**: Tiket yang disetujui Atasan Langsung HARUS berpindah ke status `Menunggu Approval Kepala Bagian IT`.
- **FR-009**: Kepala Bagian IT HARUS menentukan klasifikasi tiket sebagai `Minor` atau `Major` sebelum tiket dapat disetujui masuk ke antrean pengerjaan.
- **FR-010**: Untuk tiket Minor, Kepala Bagian IT HARUS dapat menyetujui tiket langsung ke status `Siap Dikerjakan`, menolak tiket ke status `Ditolak Kepala Bagian IT`, atau mengembalikan tiket ke status `Revisi Requester oleh Kepala Bagian IT`.
- **FR-011**: Untuk tiket Major, tiket HARUS melalui status `Menunggu Rapat Major`, diikuti pencatatan notulensi rapat (`Menunggu Notulensi` / `Menunggu Persetujuan Notulensi`), dan hanya dapat berpindah ke `Siap Dikerjakan` setelah notulensi rapat disetujui secara resmi oleh Kepala Bagian IT.
- **FR-012**: Ketika Requester mengirimkan kembali tiket yang telah diperbaiki pasca-revisi dari Kepala Bagian IT, sistem HARUS mengembalikan tiket ke status `Menunggu Approval Atasan` untuk disetujui ulang oleh Atasan Langsung sebelum dapat diteruskan kembali ke Kepala Bagian IT.
- **FR-013**: Sistem HARUS mengizinkan Requester maupun Kepala Bagian IT untuk memasukkan notulensi rapat Major ke dalam sistem, dan mewajibkan persetujuan formal akhir secara eksklusif oleh Kepala Bagian IT sebelum tiket berpindah ke antrean pengerjaan (`Siap Dikerjakan`).
- **FR-014**: Anggota Tim IT HARUS dapat melihat daftar tiket pada status `Siap Dikerjakan` dan melakukan pengambilan tiket mandiri (`self-assign`).
- **FR-015**: Sistem HARUS menjamin keamanan konkurensi pada operasi `self-assign` sehingga satu tiket tidak dapat diambil oleh lebih dari satu anggota Tim IT secara simultan.
- **FR-016**: Saat tiket berhasil di-assign, sistem HARUS mencatat identitas penanggung jawab IT, mencatat waktu mulai pengerjaan, dan mengubah status tiket menjadi `Dalam Pengerjaan`.
- **FR-017**: Penanggung jawab IT HARUS dapat menambahkan catatan progres pengerjaan dan log aktivitas teknis secara berkala selama tiket dalam status `Dalam Pengerjaan`.
- **FR-018**: Setelah pekerjaan IT selesai, penanggung jawab IT HARUS meneruskan tiket ke status `Menunggu Review User`.
- **FR-019**: Requester HARUS dapat memeriksa hasil pekerjaan dan memilih keputusan: `Accept` (menutup tiket ke status `Selesai` beserta feedback) atau `Revisi` (mengembalikan tiket dengan catatan alasan revisi).
- **FR-020**: Ketika Requester meminta revisi atas hasil pekerjaan pada tahap review user, sistem HARUS mengubah status tiket menjadi `Revisi Hasil Pekerjaan` dan secara otomatis mengembalikannya kepada developer IT penanggung jawab sebelumnya untuk diperbaiki.
- **FR-021**: Sistem HARUS melarang penutupan tiket menjadi `Selesai` tanpa persetujuan eksplisit (Accept) dari Requester pembuat tiket.
- **FR-022**: Sistem HARUS mencatat seluruh riwayat aktivitas, perubahan status, pelaku aksi, stempel waktu, dan komentar ke dalam log audit yang tidak dapat diedit atau dihapus oleh pengguna.
- **FR-023**: Sistem HARUS menyediakan dashboard pemantauan yang disesuaikan secara dinamis dengan peran pengguna (Requester, Atasan Langsung, Kepala Bagian IT, dan Tim IT).

---

### Key Entities *(include if feature involves data)*

- **Ticket (Tiket Bantuan IT)**: Entitas inti yang merepresentasikan permintaan bantuan IT. Atribut konseptual: Nomor tiket unik, judul, deskripsi kebutuhan, kategori permintaan, lampiran, identitas pemohon (Requester), unit kerja pemohon, atasan langsung terkait, klasifikasi (Belum Ditentukan / Minor / Major), status saat ini, penanggung jawab IT, waktu pengajuan, waktu persetujuan, waktu penyelesaian, dan waktu penutupan.
- **User & Organizational Role (Pengguna & Peran)**: Pengguna terautentikasi dalam struktur perusahaan beserta peran bisnisnya: Karyawan/Requester, Atasan Langsung, Kepala Bagian IT, Tim IT/Developer, dan Administrator Sistem. Memiliki relasi hierarkis (siapa atasan dari siapa).
- **Approval & Decision Record (Catatan Keputusan Approval)**: Catatan riwayat formal setiap keputusan persetujuan, penolakan, atau revisi. Mencakup identitas pembuat keputusan, peran saat memutuskan, tahapan keputusan (Atasan / Kepala Bagian IT / User Review), jenis keputusan (Setujui / Tolak / Revisi / Accept), alasan/komentar, dan stempel waktu.
- **Meeting Minutes (Notulensi Rapat Major)**: Dokumen/catatan kesepakatan ruang lingkup untuk tiket berkategori Major. Mencakup tanggal rapat, peserta rapat, ringkasan ruang lingkup kebutuhan, kesepakatan batas waktu, dokumen pendukung, stempel waktu pengisian, dan status persetujuan oleh Kepala Bagian IT.
- **Handling Activity & Audit Trail (Catatan Penanganan & Log Audit)**: Catatan kronologis peristiwa tiket. Mencakup stempel waktu peristiwa, aktor pelaku, tipe aktivitas (Pembuatan Tiket, Transisi Status, Catatan Progres Pengerjaan, Self-Assign, Notulensi, Review User), status sebelumnya, status baru, dan keterangan aktivitas. Bersifat append-only.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% tiket yang diajukan berhasil diteruskan ke Atasan Langsung yang tepat tanpa memerlukan routing manual.
- **SC-002**: 0% tiket yang masuk ke antrean pengerjaan Tim IT (`Siap Dikerjakan`) tanpa melalui persetujuan wajib Atasan Langsung dan Kepala Bagian IT.
- **SC-003**: 100% tiket berkategori Major di antrean pengerjaan terbukti memiliki notulensi rapat yang telah disetujui Kepala Bagian IT.
- **SC-004**: Pada skenario persaingan klik (concurrency), 100% upaya self-assign simultan berhasil menghasilkan tepat satu penanggung jawab IT tanpa tabrakan atau duplikasi penugasan.
- **SC-005**: 100% tiket berstatus `Selesai` memiliki riwayat persetujuan eksplisit (Accept) dan feedback kepuasan dari Requester pembuat tiket.
- **SC-006**: Seluruh perubahan status tiket tercatat 100% dalam log audit kronologis beserta pelaku, stempel waktu, dan alasannya.
- **SC-007**: Pengguna (Requester, Atasan, Kabag IT, IT Developer) dapat menyelesaikan aksi utama perannya (submit, approval, self-assign, update progress, review) dalam waktu kurang dari 2 menit per interaksi.

---

## Assumptions

- Seluruh pengguna (Karyawan, Atasan, Kepala Bagian IT, Tim IT) telah terdaftar dalam sistem dengan struktur departemen/unit kerja dan atasan langsung yang telah terkonfigurasi.
- Komunikasi notifikasi email atau notifikasi internal sistem diasumsikan aktif untuk memberi tahu pengguna mengenai tiket yang memerlukan tindakan.
- Satu tiket hanya memiliki satu Karyawan sebagai Requester dan satu Atasan Langsung utama sebagai penanggung jawab persetujuan lini pertama.
- Tiket yang telah berstatus `Selesai` atau `Ditolak` bersifat final dan tidak dapat dibuka kembali secara langsung, melainkan harus melalui pengajuan tiket baru jika masalah serupa muncul kembali.
- Klasifikasi Minor atau Major ditentukan sepenuhnya oleh kebijakan pertimbangan Kepala Bagian IT dan tidak ditentukan secara otomatis oleh sistem saat pengajuan awal.
