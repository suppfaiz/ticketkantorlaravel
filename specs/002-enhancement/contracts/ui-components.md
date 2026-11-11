# UI Component & Event Contracts: UI/UX Enhancement (002-enhancement)

**Feature Branch**: `002-enhancement`  
**Date**: 2026-10-28  
**Spec Reference**: [spec.md](../spec.md)

---

## 1. Sidebar Component Contract

### Blade Inclusion
`resources/views/layouts/sidebar.blade.php` diintegrasikan dalam `layouts/app.blade.php`:

```blade
<x-sidebar /> <!-- atau @include('layouts.sidebar') -->
```

### State & Alpine.js Contract
- **Desktop Collapse Toggle**:
  - `sidebarCollapsed`: Boolean disimpan di `localStorage.getItem('it_helpdesk_sidebar_collapsed')`.
  - Ketika `true`: Kontainer sidebar memiliki class `w-20` (80px), teks label disembunyikan (`hidden`), ikon di-center.
  - Ketika `false`: Kontainer sidebar memiliki class `w-64` (256px), teks label ditampilkan dengan transisi halus.
- **Mobile Drawer**:
  - `sidebarMobileOpen`: Boolean.
  - Di-toggle melalui event Alpine: `$dispatch('toggle-mobile-sidebar')` atau `sidebarMobileOpen = !sidebarMobileOpen`.
  - Backdrop klik otomatis menutup drawer: `@click="sidebarMobileOpen = false"`.
  - Tombol ESC otomatis menutup drawer: `@keydown.escape.window="sidebarMobileOpen = false"`.

---

## 2. Visual Stepper Component Contract

### Blade Component
`resources/views/components/ticket-stepper.blade.php`

### Props Contract
- `@props(['ticket'])`: Instance model `App\Models\Ticket` wajib disediakan.

### Visual State Rules
- Setiap node tahap (1 sampai 5) merender:
  1. **Indikator Nomor / Ikon**:
     - *Completed*: Ikon centang SVG, background `bg-emerald-50`, border `border-emerald-300`, text `text-emerald-700`.
     - *Active*: Nomor tahap tebal, background `bg-blue-600`, text `text-white`, ring pendar `ring-4 ring-blue-100`.
     - *Revision / Rejected*: Ikon seru / silang SVG, background `bg-amber-50` / `bg-red-50`, text `text-amber-700` / `text-red-700`.
     - *Pending*: Nomor tahap redup, background `bg-slate-100`, border `border-slate-200`, text `text-slate-400`.
  2. **Nama Tahap & Subtitle**:
     - Tahap A: Pengajuan Tiket (`Selesai` atau `Draft`)
     - Tahap B: Approval Atasan (`Atasan: {nama}` atau `Disetujui` / `Ditolak`)
     - Tahap C: Kabag IT (`Klasifikasi: {Minor|Major}` atau `Menunggu Rapat`)
     - Tahap D: Pengerjaan IT (`Teknisi: {nama}` atau `Antrean Siap`)
     - Tahap E: Review & Selesai (`Ulasan Pemohon` atau `Tiket Selesai`)

---

## 3. Action Modal Contract

### Modal Trigger & Events
Tombol aksi pada `tickets/show.blade.php` memicu modal melalui Alpine dispatch:

```blade
<!-- Contoh Trigger Tombol -->
<button type="button" @click="$dispatch('open-modal', 'supervisor-approve-modal')" class="...">
    Setujui Pengajuan
</button>

<button type="button" @click="$dispatch('open-modal', 'supervisor-reject-modal')" class="...">
    Tolak Pengajuan
</button>
```

### Modal Container & Payload Contract
Setiap modal merender form POST yang sah dengan proteksi CSRF ke endpoint backend:

| Modal Name | Target Route | Form Inputs | Validasi Client & Server |
|---|---|---|---|
| `supervisor-approve-modal` | `route('tickets.supervisor-approve', $ticket)` | `@csrf`, `catatan` (opsional) | Konfirmasi eksplisit |
| `supervisor-reject-modal` | `route('tickets.supervisor-reject', $ticket)` | `@csrf`, `catatan_alasan` (wajib) | Min. 5 karakter |
| `supervisor-revise-modal` | `route('tickets.supervisor-revise', $ticket)` | `@csrf`, `catatan_revisi` (wajib) | Min. 5 karakter |
| `ithead-approve-minor-modal` | `route('tickets.ithead-approve-minor', $ticket)` | `@csrf`, `estimasi_hari` (opsional) | Konfirmasi minor |
| `ithead-approve-major-modal` | `route('tickets.ithead-approve-major', $ticket)` | `@csrf`, `jadwal_rapat` (opsional) | Konfirmasi major |
| `ithead-reject-modal` | `route('tickets.ithead-reject', $ticket)` | `@csrf`, `catatan_alasan` (wajib) | Min. 5 karakter |
| `ithead-revise-modal` | `route('tickets.ithead-revise', $ticket)` | `@csrf`, `catatan_revisi` (wajib) | Min. 5 karakter |
| `dev-complete-modal` | `route('tickets.dev-complete', $ticket)` | `@csrf`, `hasil_pekerjaan` (opsional) | Konfirmasi selesai |
| `user-review-modal` | `route('tickets.user-review', $ticket)` | `@csrf`, `decision` (accept/revise), `feedback` | Catatan wajib jika revisi |

---

## 4. KPI Card Component Contract

### Blade Component
`resources/views/components/kpi-card.blade.php`

### Props Contract
- `@props(['title', 'value', 'icon', 'color' => 'blue', 'subtitle' => null, 'trend' => null])`
- Rendering HTML menghasilkan struktur kartu putih elegan dengan border lembut dan kontras WCAG AA.
