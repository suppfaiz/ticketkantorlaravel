# Research & Architectural Decisions: IT Helpdesk Internal

**Feature**: Aplikasi IT Helpdesk Internal Perusahaan  
**Directory**: `specs/001-it-helpdesk`  
**Date**: 2026-10-28  

Dokumen ini merangkum analisis riset teknis dan keputusan arsitektural untuk memastikan implementasi sistem memenuhi prinsip **keamanan (security-first)**, **keandalan**, **maintainability**, dan **kesederhanaan (YAGNI)** sesuai konstitusi proyek `v1.0.0`.

---

## Decision 1: Database Engine Selection

- **Decision**: PostgreSQL (didukung MySQL 8.0+ sebagai fallback kompatibel).
- **Rationale**:
  - PostgreSQL menyediakan dukungan transaksi ACID yang sangat ketat dengan fitur *row-level locking* (`SELECT ... FOR UPDATE` dan `SKIP LOCKED`), sangat ideal untuk mencegah *race condition* pada antrean tiket dan operasi *self-assign*.
  - Mendukung tipe data `JSONB` asli untuk menyimpan metadata perubahan (*diff payload*) pada log audit secara efisien dan dapat diindeks.
  - Memiliki fitur *atomic sequences* yang memudahkan pembuatan nomor tiket unik bebas benturan.
- **Alternatives Considered**:
  - *MySQL 8.0+*: Memiliki kemampuan yang memadai, namun PostgreSQL lebih unggul dalam penanganan konkurensi tingkat baris dan integritas tipe data JSON.
  - *SQLite*: Hanya digunakan untuk *in-memory testing* saat pengujian unit cepat, tidak untuk lingkungan staging/produksi multi-pengguna.

---

## Decision 2: Authentication & Session Security Architecture

- **Decision**: Laravel Native Session-based Authentication menggunakan Laravel Breeze (Blade Stack).
- **Rationale**:
  - Aplikasi merupakan sistem web internal perusahaan yang diakses melalui peramban desktop/laptop kantor.
  - Session-based authentication bawaan Laravel secara otomatis menangani *HTTP-only cookies*, proteksi *SameSite*, regenerasi session ID pasca-login (mencegah *session fixation*), dan regenerasi token CSRF saat logout.
  - Tidak menyimpan token di `localStorage` peramban, sehingga kebal terhadap pencurian kredensial via XSS.
  - Menyediakan *rate limiting* bawaan pada endpoint autentikasi (`RateLimiter` per IP + username) tanpa membocorkan eksistensi akun (*prevent user enumeration*).
- **Alternatives Considered**:
  - *Laravel Sanctum / Passport (API Tokens / JWT)*: Ditolak karena menambah kompleksitas yang tidak dibutuhkan untuk web internal Blade murni (melanggar prinsip YAGNI).
  - *Custom Auth Guard*: Ditolak karena rawan kesalahan keamanan dibanding implementasi resmi Laravel.

---

## Decision 3: Concurrency Control Strategy for Self-Assign

- **Decision**: Atomic Conditional Update dengan Transaksi Database.
- **Rationale**:
  - Dua anggota IT dapat membuka antrean tiket `Siap Dikerjakan` dan mengklik tombol "Ambil Tiket" pada mikrodetik yang sama.
  - Strategi penanganan:
    ```sql
    UPDATE tickets 
    SET assigned_to = :developer_id, 
        status = 'Dalam Pengerjaan', 
        updated_at = NOW() 
    WHERE id = :ticket_id 
      AND status = 'Siap Dikerjakan' 
      AND assigned_to IS NULL;
    ```
  - Jika jumlah baris yang terpengaruh (*affected rows*) adalah `1`, operasi berhasil dan sistem mencatat riwayat audit.
  - Jika jumlah baris adalah `0`, berarti tiket telah diambil oleh anggota tim lain sesaat sebelumnya; sistem langsung membatalkan transaksi dan mengembalikan respons kegagalan yang ramah ke pengguna tanpa inkonsistensi data.
- **Alternatives Considered**:
  - *Pessimistic Locking (`SELECT FOR UPDATE`)*: Digunakan bersamaan di dalam blok `DB::transaction` untuk memastikan keamanan penuh pada level ORM.
  - *Optimistic Locking via version counter*: Memerlukan penambahan kolom versi di semua tabel; conditional update pada status & assignee lebih sederhana dan langsung menyelesaikan masalah.

---

## Decision 4: Collision-Safe Unique Ticket Number Generation

- **Decision**: Format `TICK-YYYYMMDD-XXXX` (Counter 4 digit terisolasi per hari).
- **Rationale**:
  - Mudah dibaca oleh manusia (*human-readable*) dan mencerminkan tanggal pengajuan.
  - Dihasilkan di dalam transaksi database menggunakan tabel sequence/counter harian yang terkunci atau atomic increment.
  - Menjamin nomor tiket tidak loncat atau duplikat saat terjadi submit bersamaan oleh banyak karyawan.
- **Alternatives Considered**:
  - *UUID v4*: Unik secara matematis namun sulit diucapkan atau dikomunikasikan secara lisan antar-karyawan saat menanyakan status tiket ke IT Helpdesk.
  - *Auto-increment ID murni (1, 2, 3...)*: Membocorkan volume tiket perusahaan dan rentan terhadap penelusuran berurutan.

---

## Decision 5: Tamper-Resistant Audit Trail Architecture

- **Decision**: Dedicated Append-Only Audit Table (`ticket_audit_logs`).
- **Rationale**:
  - Menampung seluruh peristiwa krusial: pembuatan tiket, approval atasan, penolakan, permintaan revisi, klasifikasi Minor/Major, persetujuan notulensi, self-assign, progres pengerjaan, revisi hasil, dan review sign-off.
  - Atribut audit: `ticket_id`, `actor_id`, `action`, `from_status`, `to_status`, `remarks`, `ip_address`, `user_agent`, `created_at`.
  - Pada level Eloquent Model: tidak menyediakan method `update()` atau `delete()`.
  - Data bersifat *append-only* untuk kepatuhan audit internal dan penelusuran investigasi.
- **Alternatives Considered**:
  - *File Log Mentah (`laravel.log`)*: Sulit dicari (*search/filter*) dari dashboard pengguna dan tidak terstruktur secara relasional per tiket.
  - *Third-party package audit (Spatie Activitylog)*: Sangat populer, namun implementasi model audit internal sederhana lebih ringan, mudah disesuaikan dengan alur approval 5 tahap, dan tidak menambah dependensi eksternal.

---

## Decision 6: Private File Upload & Secure Streaming

- **Decision**: Penyimpanan di Private Storage (`storage/app/private/tickets`) dengan streaming terotorisasi via Policy.
- **Rationale**:
  - Lampiran tiket dan dokumen notulensi rapat Major sering memuat data rahasia internal perusahaan.
  - File disimpan di luar direktori publik web server (`public/`).
  - Akses unduh file dilindungi oleh endpoint perantara (misal: `/tickets/{ticket}/attachments/{attachment}/download`) yang memeriksa hak akses pengguna melalui `TicketPolicy@viewAttachment`.
  - Validasi ketat saat upload: pengecekan MIME type asli (bukan hanya ekstensi file), pembatasan ukuran maksimal (misal: 10 MB), dan nama file acak di disk (*hash naming*) untuk mencegah eksekusi skrip berbahaya.
- **Alternatives Considered**:
  - *Public Storage Symlink (`storage:link`)*: Ditolak keras karena file privat dapat diakses oleh siapa pun yang mengetahui URL langsungnya.

---

## Decision 7: Frontend Theme & Design System (Clean Minimalist Enterprise Light Mode)

- **Decision**: Mengadopsi Clean Minimalist Enterprise Light Mode dengan dasar putih (`#FFFFFF`), latar netral (`#F8FAFC`), dan palet warna terbatas sesuai [DESIGN.md](../../DESIGN.md).
- **Rationale**:
  - Aplikasi IT Helpdesk adalah perangkat kerja harian karyawan kantor yang membutuhkan keterbacaan tinggi (*high readability*) dan suasana profesional tanpa distraksi visual.
  - Dasar putih dengan kontras tajam (`#0F172A`) mempermudah staf membaca deskripsi teknis, dokumen notulensi, dan log penanganan.
  - Penggunaan warna diminimalisasi secara ketat: warna fungsional hanya diterapkan pada badge status kecil dengan nuansa pastel lembut (Slate untuk Menunggu, Amber untuk Revisi, Merah untuk Ditolak, Biru untuk Pengerjaan, Hijau untuk Selesai).
  - Mengeliminasi dark mode dan aksen neon berlebih untuk menjaga kesederhanaan dan kepatuhan terhadap prinsip YAGNI.
- **Alternatives Considered**:
  - *Dark Mode / Neon Dashboard*: Ditolak sesuai arahan pengguna agar tidak menggunakan dark mode atau banyak warna neon yang melelahkan mata untuk aplikasi administratif internal.
  - *Multi-Color Rich Theme*: Ditolak untuk menjaga estetika enterprise yang bersih, ringan, dan cepat di-render di peramban.
