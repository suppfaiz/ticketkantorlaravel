# DESIGN.md - Sistem Desain Aplikasi IT Helpdesk Internal

## 1. Visual Theme & Atmosphere
Desain mengusung konsep **Clean, Minimalist Enterprise Light Mode**. Tampilan dirancang bersih, profesional, dan fokus pada efisiensi kerja karyawan tanpa distraksi visual atau penggunaan warna yang berlebihan. Antarmuka menggunakan dasar putih dengan kontras tinggi dan hierarki tipografi yang jelas untuk kenyamanan penggunaan sepanjang hari.

## 2. Color Palette (Minimalist Light Mode)
- **Background Utama (Base):** `#FFFFFF` (Putih bersih).
- **Background Sekunder / Surface:** `#F8FAFC` (Slate lembut untuk latar belakang halaman / kontras panel).
- **Permukaan Kartu (Cards):** `#FFFFFF` (Putih) dengan border halus `#E2E8F0`.
- **Border / Garis Pemisah:** `#E2E8F0` (Abu-abu netral tipis).
- **Teks Utama:** `#0F172A` (Slate gelap, kontras tinggi dan tajam).
- **Teks Sekunder / Label:** `#64748B` (Muted slate untuk timestamp, label formulir, dan metadata).
- **Aksen Primer (Aksi & Fokus):** `#1E293B` / `#0F172A` (Deep Slate monokromatik untuk tombol utama) atau aksen korporat tunggal `#2563EB` (Royal Blue klasik untuk tautan dan status aktif).
- **Status Warna Fungsional (Subtle Badges):**
  - **Netral / Menunggu Approval:** Background `#F1F5F9`, teks `#334155`.
  - **Perlu Revisi / Catatan:** Background `#FEF3C7`, teks `#92400E` (Amber lembut).
  - **Ditolak:** Background `#FEE2E2`, teks `#991B1B` (Merah lembut).
  - **Siap Dikerjakan / Dalam Pengerjaan:** Background `#E0F2FE`, teks `#0369A1` (Biru lembut).
  - **Selesai:** Background `#DCFCE7`, teks `#166534` (Hijau lembut).

## 3. Component Stylings
- **Kartu (Cards):** Latar `#FFFFFF`, `border-radius: 8px` hingga `12px`, border `1px solid #E2E8F0`, tanpa shadow tebal (hanya `box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05)`). Padding internal `16px` hingga `24px`.
- **Formulir & Input:** Input latar putih `#FFFFFF`, border `1px solid #CBD5E1`, sudut `rounded-md` (6px), focus ring tipis `#2563EB` tanpa glow berlebih.
- **Tabel Data:** Latar baris `#FFFFFF`, border pemisah `1px solid #F1F5F9`, hover baris halus `#F8FAFC`. Header tabel berlatar `#F8FAFC` dengan teks uppercase kecil berbobot semi-bold `#64748B`.
- **Kotak Catatan / Alasan (Alerts & Notes):** Kotak berlatar abu-abu netral `#F8FAFC` atau amber lembut `#FEF3C7` dengan border kiri tipis `3px` untuk memudahkan pembacaan catatan revisi/alasan penolakan.

## 4. Typography
- **Font Utama:** `Inter`, `system-ui`, `-apple-system`, `sans-serif`.
- **Judul Halaman:** Semi Bold (600), 20px - 24px, warna `#0F172A`.
- **Sub-judul / Bagian:** Medium (500), 16px, warna `#334155`.
- **Badan Teks:** Regular (400), 14px, warna `#0F172A`.
- **Metadata & Label:** Regular (400), 12px, warna `#64748B`.
- **Nomor Tiket & Kode:** Monospace (`font-mono`, misal `ui-monospace, SFMono-Regular`), bobot medium, warna `#1E293B`.

## 5. Layout & Spacing
- **Layout:** Grid 12 kolom responsif dengan batas lebar kontainer maksimal yang nyaman dibaca (`max-w-7xl`).
- **Spasial:** Sistem kelipatan 4px/8px standar Tailwind (`p-4`, `p-6`, `gap-6`).
- **Dashboard:** Ringkasan KPI di bagian atas dengan kartu statistik putih bersih, diikuti tabel antrean tiket dan riwayat aktivitas.

## 6. Do's and Don'ts
- ✅ **Do:** Pertahankan dasar putih bersih dan kontras teks tinggi agar mudah dibaca di layar kantor.
- ✅ **Do:** Gunakan warna hanya secara fungsional pada badge status kecil, bukan pada latar belakang area besar.
- ❌ **Don't:** Menggunakan dark mode atau tema gelap neon.
- ❌ **Don't:** Menggunakan terlalu banyak warna cerah (gradien neón, shadow berwarna) yang mendistraksi proses kerja.
- ❌ **Don't:** Menggunakan font dekoratif yang mengurangi keterbacaan data tiket.
