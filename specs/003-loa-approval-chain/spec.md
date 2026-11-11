# Feature Specification: Alur Persetujuan LOA Kadiv IT dan Direksi (LOA Executive Approval Chain)

**Feature Branch**: `003-loa-approval-chain`

**Created**: 2026-10-28

**Status**: Ready for Planning

**Input**: User description: "Alur Persetujuan LOA Kadiv IT dan LOA Direksi untuk Tiket Minor dan Major berdasarkan Konstitusi v2.0.0"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Persetujuan LOA Kadiv IT untuk Seluruh Request (Priority: P1) 🎯 MVP

Sebagai Kepala Divisi IT (Kadiv IT), saya ingin menerima antrean tiket permohonan yang telah disetujui oleh Kepala Bagian IT (baik kategori Minor maupun Major), meninjau kelayakannya secara menyeluruh, serta mengeksekusi keputusan (Setujui LOA, Tolak, atau Minta Revisi), sehingga setiap permohonan memiliki legitimasi kepemimpinan IT sebelum masuk ke antrean pengerjaan teknis atau diteruskan ke Direksi.

**Why this priority**: Kadiv IT merupakan pilar utama tata kelola (governance) baru di mana 100% tiket (Minor dan Major) wajib melalui persetujuan LOA Kadiv IT. Fitur ini merupakan fondasi wajib (MVP) dari Konstitusi v2.0.0.

**Independent Test**: Dapat diuji secara mandiri dengan mengajukan tiket Minor atau Major yang disetujui Kabag IT, lalu memverifikasi bahwa tiket muncul di antrean Kadiv IT, dan saat Kadiv IT menyetujui, tiket Minor langsung masuk antrean `Siap Dikerjakan`.

**Acceptance Scenarios**:

1. **Given** Tiket Minor telah disetujui dan diklasifikasikan oleh Kepala Bagian IT, **When** Kadiv IT menyetujui LOA tiket tersebut, **Then** Tiket Minor resmi beralih status ke `Siap Dikerjakan` (antrean terbuka tim IT developer).
2. **Given** Tiket Minor atau Major berada di antrean persetujuan Kadiv IT, **When** Kadiv IT menolak tiket dengan mencantumkan alasan wajib, **Then** Proses dihentikan seketika dan tiket beralih ke status `Ditolak Kadiv IT`.
3. **Given** Tiket Minor atau Major berada di antrean persetujuan Kadiv IT, **When** Kadiv IT meminta revisi dengan catatan perbaikan, **Then** Tiket dikembalikan ke pemohon untuk direvisi tanpa menghapus histori audit log sebelumnya.
4. **Given** Tiket Minor telah disetujui Kabag IT namun belum disetujui LOA oleh Kadiv IT, **When** Pengembang IT mencoba mengklaim tiket, **Then** Sistem secara tegas memblokir klaim pengerjaan di sisi server (invarian minor barrier).

---

### User Story 2 - Persetujuan LOA Direksi untuk Request Berkategori Major (Priority: P2)

Sebagai Direksi Perusahaan, saya ingin menerima antrean tiket berkategori Major yang telah disetujui resmi oleh Kabag IT dan Kadiv IT, meninjau dampak operasional dan anggaran strategis, serta memberikan keputusan persetujuan akhir (Setujui LOA Direksi, Tolak, atau Minta Revisi), sehingga proyek IT berskala besar memiliki otorisasi eksekutif tertinggi perusahaan sebelum pengerjaan teknis dimulai.

**Why this priority**: Menegakkan integritas bisnis dan kepatuhan korporat untuk alur persetujuan multi-tier pada perubahan arsitektur atau sistem berskala besar.

**Independent Test**: Dapat diuji dengan memproses tiket Major yang telah disetujui LOA oleh Kadiv IT, lalu login sebagai Direksi untuk menyetujui tiket ke `Siap Dikerjakan`, menolak tiket, atau meminta revisi.

**Acceptance Scenarios**:

1. **Given** Tiket Major telah disetujui LOA oleh Kadiv IT, **When** Direksi menyetujui LOA tiket tersebut, **Then** Tiket Major resmi beralih status ke `Siap Dikerjakan` (antrean terbuka tim IT developer).
2. **Given** Tiket Major telah disetujui Kabag IT namun belum disetujui LOA oleh Kadiv IT, **When** Dilakukan upaya persetujuan oleh Direksi atau langsung dilompati, **Then** Sistem memblokir aksi tersebut di sisi server (invarian major multi-tier barrier).
3. **Given** Tiket Major telah disetujui LOA oleh Kadiv IT namun belum disetujui Direksi, **When** Pengembang IT mencoba mengklaim tiket ke antrean pengerjaan, **Then** Sistem menolak dan memblokir aksi pengerjaan.
4. **Given** Tiket Major berada di antrean Direksi, **When** Direksi menolak permohonan dengan alasan wajib, **Then** Tiket beralih ke status `Ditolak Direksi` dan proses dihentikan permanen.

---

### User Story 3 - Dashboard Eksekutif, Visual Stepper Multi-Tier & Transparansi Audit (Priority: P3)

Sebagai Kadiv IT, Direksi, dan Pemohon tiket, saya ingin melihat pembaruan pada Visual Stepper alur tiket yang mencakup tahapan LOA Kadiv IT dan LOA Direksi, dashboard khusus dengan kartu KPI antrean persetujuan eksekutif, serta riwayat audit trail yang mencatat nama approver, waktu, keputusan, dan catatan secara transparan, sehingga alur birokrasi terpantau jelas dan akuntabel.

**Why this priority**: Meningkatkan visibilitas pengguna dan efisiensi pejabat eksekutif dalam mengelola dan memantau antrean persetujuan tiket.

**Independent Test**: Login sebagai Kadiv IT dan Direksi, membuka dashboard masing-masing untuk memeriksa kartu KPI antrean, serta membuka halaman detail tiket untuk memverifikasi stepper alur kerja yang dinamis.

**Acceptance Scenarios**:

1. **Given** Pengguna Kadiv IT login ke sistem, **When** Membuka dashboard, **Then** Ditampilkan kartu metrik antrean tiket Minor & Major yang menanti persetujuan LOA Kadiv IT serta tabel aksi cepat.
2. **Given** Pengguna Direksi login ke sistem, **When** Membuka dashboard, **Then** Ditampilkan kartu metrik antrean tiket Major yang menanti otorisasi Direksi serta tabel aksi cepat.
3. **Given** Pengguna membuka halaman detail tiket, **When** Tiket berada pada tahap LOA, **Then** Visual Stepper menampilkan tahapan persetujuan LOA Kadiv IT dan LOA Direksi secara akurat sesuai klasifikasi tiket.
4. **Given** Keputusan approval, penolakan, atau revisi dieksekusi oleh Kadiv IT atau Direksi, **When** Memeriksa jejak audit tiket, **Then** Tercatat identitas approver, jabatan, waktu eksekusi, hasil keputusan, dan catatan secara permanen tanpa menghapus catatan sebelumnya.

---

### Edge Cases

- **Upaya Bypass Endpoint Langsung**: Permintaan HTTP POST langsung ke endpoint persetujuan Direksi untuk tiket yang belum disetujui Kadiv IT wajib ditolak dengan HTTP 403 / Forbidden oleh Policy server-side.
- **Upaya Bypass Antrean Development**: Pengembang IT yang mencoba memanggil endpoint `self-assign` pada tiket yang masih berstatus persetujuan LOA (belum `Siap Dikerjakan`) wajib digagalkan oleh State Machine dan Policy.
- **Penolakan Tanpa Alasan**: Input alasan penolakan wajib divalidasi minimal 5 karakter; pengiriman tanpa alasan akan digagalkan oleh validasi form.
- **Histori Revisi Berulang**: Tiket yang mengalami revisi lebih dari sekali di tingkat LOA tidak boleh kehilangan riwayat audit revisi pertama; seluruh histori log wajib bersifat *append-only*.
- **Kriteria Klasifikasi Minor & Major**: Sistem tidak mengubah kriteria penentuan Minor/Major yang telah ada; perbedaan hanya pada alur persetujuan lanjutan setelah LOA Kadiv IT.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Sistem HARUS mewajibkan proses persetujuan LOA oleh Kepala Divisi IT (Kadiv IT) untuk SEMUA request tiket, baik berkategori Minor maupun Major, setelah disetujui oleh Kepala Bagian IT.
- **FR-002**: Sistem HARUS melarang keras tiket permohonan apapun (baik Minor maupun Major) melewati atau membypass tahap persetujuan LOA Kadiv IT.
- **FR-003**: Sistem HARUS mengalihkan tiket berkategori Minor yang telah disetujui LOA oleh Kadiv IT secara otomatis ke antrean `Siap Dikerjakan` (antrean pengerjaan tim developer).
- **FR-004**: Sistem HARUS meneruskan tiket berkategori Major yang telah disetujui LOA oleh Kadiv IT ke tahap persetujuan LOA Direksi.
- **FR-005**: Sistem HARUS memastikan tiket berkategori Major TIDAK DAPAT diteruskan ke Direksi atau disetujui oleh Direksi tanpa terlebih dahulu disetujui oleh Kadiv IT.
- **FR-006**: Sistem HARUS memastikan tiket berkategori Minor TIDAK DAPAT masuk ke antrean `Siap Dikerjakan` sebelum mendapat persetujuan resmi dari Kadiv IT.
- **FR-007**: Sistem HARUS memastikan tiket berkategori Major TIDAK DAPAT masuk ke antrean `Siap Dikerjakan` sebelum mendapat persetujuan LENGKAP dari Kepala Bagian IT, Kadiv IT, dan Direksi.
- **FR-008**: Sistem HARUS menghentikan proses secara permanen apabila tiket ditolak oleh Kadiv IT atau Direksi, dan memindahkan tiket ke daftar penolakan dengan alasan penolakan wajib.
- **FR-009**: Sistem HARUS mengembalikan tiket ke tahap yang sesuai apabila terdapat permintaan revisi oleh Kadiv IT atau Direksi tanpa menghapus riwayat audit dan catatan sebelumnya.
- **FR-010**: Sistem HARUS mencatat identitas approver (`actor_id`), waktu keputusan (`timestamp`), hasil keputusan, dan catatan ke dalam tabel jejak audit (`TicketAuditLog`) untuk setiap keputusan approval.
- **FR-011**: Hak akses persetujuan, otorisasi peran, dan validasi transisi status HARUS diperiksa dan ditegakkan secara mutlak di sisi server (*server-side Laravel Policies & TicketStateMachine*).
- **FR-012**: Sistem HARUS mempertahankan kriteria penentuan kategori Minor dan Major yang telah ditetapkan perusahaan tanpa perubahan kriteria.
- **FR-013**: Sistem HARUS menyediakan antarmuka dashboard dan modal dialog interaktif persetujuan LOA yang ergonomis untuk peran Kepala Divisi IT dan Direksi sesuai panduan *Clean Minimalist Enterprise Light Mode*.

### Key Entities

- **User (Role: `kadiv_it` & `direksi`)**: Entitas akun pejabat eksekutif yang memiliki kewenangan otorisasi LOA sesuai batas perannya.
- **Ticket (Statuses & Approvals)**: Menyimpan status transisi LOA baru (`menunggu_approval_kadiv_it`, `menunggu_approval_direksi`, `ditolak_kadiv_it`, `ditolak_direksi`) serta timestamp otorisasi (`approved_by_kadiv_at`, `approved_by_direksi_at`).
- **TicketAuditLog**: Entitas jejak audit yang merekam setiap keputusan persetujuan, penolakan, dan revisi di tingkat LOA secara permanen dan immutable.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% tiket permohonan (Minor dan Major) yang diproses terverifikasi melewati persetujuan LOA Kadiv IT tanpa adanya celah bypass.
- **SC-002**: 100% tiket berkategori Major yang berhasil masuk ke antrean `Siap Dikerjakan` terbukti telah mengantongi persetujuan sah dari Kepala Bagian IT, Kadiv IT, dan Direksi.
- **SC-003**: 0% (tidak ada) tiket berstatus persetujuan LOA yang dapat diklaim atau diambil oleh tim teknisi IT sebelum disetujui lengkap.
- **SC-004**: 100% lulus pada 7 skenario pengujian otomatis alur approval (Minor disetujui masuk dev, Minor terblokir tanpa Kadiv, Major disetujui lengkap masuk dev, Major terblokir tanpa Kadiv, Major terblokir tanpa Direksi, penghentian penolakan, integritas histori revisi).
- **SC-005**: Seluruh keputusan approval LOA tercatat ke dalam audit log dalam transaksi database atomik (< 200ms) bersamaan dengan mutasi status tiket.

---

## Assumptions

- Peran `kadiv_it` dan `direksi` ditambahkan ke dalam skema enum/konfigurasi peran pengguna aplikasi dengan akun pengujian otomatis yang disediakan dalam seeder.
- Alur notulensi rapat tiket Major (jika dipersyaratkan) diselesaikan sebelum atau selama peninjauan oleh Kadiv IT dan Direksi sesuai ketetapan alur bisnis.
- Tampilan antarmuka mengikuti standar *Clean Minimalist Enterprise Light Mode* yang sudah dibangun pada fitur `002-enhancement` (termasuk modal dialog konfirmasi dan sidebar responsif).
