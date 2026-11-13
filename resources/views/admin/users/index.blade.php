<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2 sm:gap-3 py-1">
            <h1 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight truncate">Kelola Pengguna, Divisi &amp; Kategori</h1>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('admin.delegations.index') }}" class="hidden sm:inline-flex btn-secondary text-xs py-2 px-3 items-center gap-1.5">
                    Delegasi Approval
                </a>
                <button
                    type="button"
                    x-data
                    @click="$dispatch('open-modal', 'modal-manage-categories')"
                    class="hidden sm:inline-flex btn-secondary text-xs py-2 px-3 items-center gap-1.5 cursor-pointer"
                >
                    <span>Kategori Request ({{ $categories->count() }})</span>
                </button>
                <button
                    type="button"
                    x-data
                    @click="$dispatch('open-modal', 'modal-manage-departments')"
                    class="hidden sm:inline-flex btn-secondary text-xs py-2 px-3 items-center gap-1.5 cursor-pointer"
                >
                    <span>Kelola Divisi ({{ $departments->count() }})</span>
                </button>
                <button
                    type="button"
                    x-data
                    @click="$dispatch('open-modal', 'modal-create-user')"
                    class="px-3 sm:px-4 py-1.5 sm:py-2 text-xs font-semibold rounded-lg bg-[#1E40AF] max-sm:bg-white/20 max-sm:ring-1 max-sm:ring-white/30 hover:bg-[#1D4ED8] text-white shadow-xs flex items-center gap-1.5 transition-all cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden sm:inline">Tambah Pengguna</span>
                    <span class="sm:hidden">Tambah</span>
                </button>
            </div>
        </div>
    </x-slot>

    @php
        $shortRoleLabels = [
            'requester'    => 'Pemohon',
            'supervisor'   => 'Atasan',
            'it_head'      => 'Kabag IT',
            'kadiv_it'     => 'Kadiv IT',
            'direksi'      => 'Direksi',
            'it_developer' => 'Teknisi IT',
            'admin'        => 'Admin Sistem',
        ];
    @endphp

    <div
        x-data="{
            editingUser: {
                id: null,
                name: '',
                email: '',
                role: 'requester',
                department_id: '',
                supervisor_id: '',
                is_active: true,
                reset_password: false,
                updateUrl: ''
            },
            openEditUser(user, url) {
                this.editingUser = {
                    id: user.id,
                    name: user.name,
                    email: user.email,
                    role: user.role,
                    department_id: user.department_id || '',
                    supervisor_id: user.supervisor_id || '',
                    is_active: !!user.is_active,
                    reset_password: false,
                    updateUrl: url
                };
                $dispatch('open-modal', 'modal-edit-user');
            }
        }"
    >
        <!-- Filter & Search Bar -->
        <div class="mb-4 sm:mb-5">
            <div class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/90 sm:border-slate-200 p-2.5 sm:p-3.5 shadow-2xs">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-2.5">
                    <div class="flex items-center gap-2 sm:flex-1">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Cari nama atau email pengguna..."
                                class="w-full pl-9 py-2 text-xs rounded-xl sm:rounded-lg border-slate-200 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-slate-50/70 focus:bg-white"
                            >
                        </div>
                        <button type="submit" class="sm:hidden btn-primary text-xs py-2 px-3.5 rounded-xl shrink-0 cursor-pointer">
                            Cari
                        </button>
                    </div>

                    <div class="hidden sm:flex items-center gap-2">
                        <select name="role" onchange="this.form.submit()" class="w-48 py-2 text-xs rounded-lg border-slate-200 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-slate-50/60">
                            <option value="">Semua Role</option>
                            @foreach($roleLabels as $val => $lbl)
                                <option value="{{ $val }}" {{ request('role') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>

                        <select name="department_id" onchange="this.form.submit()" class="w-48 py-2 text-xs rounded-lg border-slate-200 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-slate-50/60">
                            <option value="">Semua Divisi</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ (string) request('department_id') === (string) $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn-primary text-xs py-2 px-4 cursor-pointer">
                            Filter
                        </button>

                        @if(request()->filled('search') || request()->filled('role') || request()->filled('department_id'))
                            <a href="{{ route('admin.users.index') }}" class="btn-secondary text-xs py-2 px-3">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Mobile Quick Chips: Role Filter, Manage Divisi & Kategori -->
            <div class="sm:hidden flex items-center gap-2 overflow-x-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none] pt-2.5 pb-0.5">
                <button
                    type="button"
                    @click="$dispatch('open-modal', 'modal-manage-categories')"
                    class="px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap shrink-0 bg-slate-800 text-white cursor-pointer"
                >
                    Kategori ({{ $categories->count() }})
                </button>
                <button
                    type="button"
                    @click="$dispatch('open-modal', 'modal-manage-departments')"
                    class="px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap shrink-0 bg-slate-800 text-white cursor-pointer"
                >
                    Divisi ({{ $departments->count() }})
                </button>
                <a
                    href="{{ route('admin.users.index', array_filter(['search' => request('search')])) }}"
                    class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap shrink-0 {{ !request()->filled('role') ? 'bg-[#1E40AF] text-white' : 'bg-white text-slate-600 border border-slate-200/90' }}"
                >
                    Semua
                </a>
                @foreach($shortRoleLabels as $val => $lbl)
                    <a
                        href="{{ route('admin.users.index', array_filter(['role' => $val, 'search' => request('search')])) }}"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap shrink-0 {{ request('role') === $val ? 'bg-[#1E40AF] text-white' : 'bg-white text-slate-600 border border-slate-200/90' }}"
                    >
                        {{ $lbl }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Mobile Separated Cards (Handphone Only) -->
        <div class="sm:hidden space-y-2.5">
            @foreach($users as $u)
                <button
                    type="button"
                    @click="openEditUser(@js([
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'role' => $u->role,
                        'department_id' => $u->department_id,
                        'supervisor_id' => $u->supervisor_id,
                        'is_active' => (bool) $u->is_active,
                    ]), '{{ route('admin.users.update', $u) }}')"
                    class="w-full text-left bg-white rounded-2xl border border-slate-200/80 px-4 py-3.5 shadow-2xs active:bg-slate-50 transition-colors flex items-center gap-3.5 cursor-pointer"
                >
                    <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0">
                        {{ strtoupper(substr($u->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-slate-900 truncate">{{ $u->name }}</span>
                            <span class="text-[11px] {{ $u->is_active ? 'text-slate-500' : 'text-rose-600 font-semibold' }} shrink-0">
                                {{ $u->is_active ? ($shortRoleLabels[$u->role] ?? $u->role) : 'Nonaktif' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-1">
                            <span class="text-xs text-slate-500 truncate">
                                {{ $u->email }} &bull; {{ $u->departmentRecord?->name ?? $u->department ?? 'Tanpa Divisi' }}
                            </span>
                            <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </button>
            @endforeach
        </div>

        <!-- Desktop Table View -->
        <div class="hidden sm:block bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-[#1E40AF]">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">Pengguna</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">Role Sistem</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">Divisi / Departemen</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">Atasan Langsung</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-white uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100 text-xs">
                        @foreach($users as $u)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-900 text-sm">{{ $u->name }}</div>
                                            <div class="text-slate-500">{{ $u->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md font-medium bg-slate-100 text-slate-800">
                                        {{ $shortRoleLabels[$u->role] ?? $u->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-slate-700">
                                    {{ $u->departmentRecord?->name ?? $u->department ?? '-' }}
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-slate-600">
                                    {{ $u->supervisor?->name ?? '-' }}
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    @if($u->is_active)
                                        <span class="inline-flex items-center gap-1.5 text-emerald-700 font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-rose-600 font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-right">
                                    <button
                                        type="button"
                                        @click="openEditUser(@js([
                                            'id' => $u->id,
                                            'name' => $u->name,
                                            'email' => $u->email,
                                            'role' => $u->role,
                                            'department_id' => $u->department_id,
                                            'supervisor_id' => $u->supervisor_id,
                                            'is_active' => (bool) $u->is_active,
                                        ]), '{{ route('admin.users.update', $u) }}')"
                                        class="text-[#1E40AF] hover:underline font-semibold cursor-pointer"
                                    >
                                        Edit / Reset Sandi
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        @if($users->hasPages())
            <div class="sm:hidden mt-3">
                {{ $users->links() }}
            </div>
        @endif

        <!-- MODAL 1: Create User -->
        <x-modal name="modal-create-user" focusable>
            <form method="POST" action="{{ route('admin.users.store') }}" class="p-5 sm:p-6">
                @csrf
                <h3 class="text-base font-bold text-slate-900 mb-1">Tambah Pengguna Baru</h3>
                <p class="text-xs text-slate-500 mb-4">Buat akun pengguna baru, tentukan role, divisi, serta atasan langsungnya.</p>

                <div class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="Contoh: Andi Saputra" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Email Kantor <span class="text-rose-500">*</span></label>
                            <input type="email" name="email" required placeholder="andi@kantor.internal" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Kata Sandi Awal <span class="text-rose-500">*</span></label>
                            <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Role Sistem <span class="text-rose-500">*</span></label>
                            <select name="role" required class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                                @foreach($roleLabels as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Divisi / Departemen</label>
                            <select name="department_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                                <option value="">-- Pilih Divisi --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Atasan Langsung (Untuk Pemohon)</label>
                        <select name="supervisor_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                            <option value="">-- Tanpa Atasan / Tidak Perlu --</option>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $shortRoleLabels[$sup->role] ?? $sup->role }})</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="inline-flex items-center gap-2 pt-1 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-[#1E40AF] focus:ring-[#1E40AF]">
                        <span class="text-xs font-medium text-slate-700">Akun langsung aktif</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2.5 mt-5 pt-3 border-t border-slate-100">
                    <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">Batal</button>
                    <button type="submit" class="btn-primary text-xs cursor-pointer">Simpan Pengguna</button>
                </div>
            </form>
        </x-modal>

        <!-- MODAL 2: Edit User -->
        <x-modal name="modal-edit-user" focusable>
            <form method="POST" :action="editingUser.updateUrl" class="p-5 sm:p-6">
                @csrf
                @method('PATCH')
                <h3 class="text-base font-bold text-slate-900 mb-1">Perbarui Data Pengguna</h3>
                <p class="text-xs text-slate-500 mb-4">Ubah role, divisi, atasan langsung, atau atur ulang kata sandi pengguna.</p>

                <div class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="editingUser.name" required class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Email Kantor <span class="text-rose-500">*</span></label>
                            <input type="email" name="email" x-model="editingUser.email" required class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                        </div>
                        <div>
                            <label class="inline-flex items-center gap-2 font-semibold text-slate-700 cursor-pointer">
                                <input type="checkbox" name="reset_password" value="1" x-model="editingUser.reset_password" class="rounded border-slate-300 text-[#1E40AF] focus:ring-[#1E40AF]">
                                Atur ulang kata sandi
                            </label>
                            <template x-if="editingUser.reset_password">
                                <input type="password" name="password" minlength="6" maxlength="100" required autocomplete="new-password" placeholder="Kata sandi baru (minimal 6 karakter)" aria-label="Kata sandi baru" class="mt-2 w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Role Sistem <span class="text-rose-500">*</span></label>
                            <select name="role" x-model="editingUser.role" required class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                                @foreach($roleLabels as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Divisi / Departemen</label>
                            <select name="department_id" x-model="editingUser.department_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                                <option value="">-- Pilih Divisi --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Atasan Langsung</label>
                        <select name="supervisor_id" x-model="editingUser.supervisor_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                            <option value="">-- Tanpa Atasan / Tidak Perlu --</option>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $shortRoleLabels[$sup->role] ?? $sup->role }})</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="inline-flex items-center gap-2 pt-1 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" x-model="editingUser.is_active" class="rounded border-slate-300 text-[#1E40AF] focus:ring-[#1E40AF]">
                        <span class="text-xs font-medium text-slate-700">Akun Aktif (dapat login ke sistem)</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2.5 mt-5 pt-3 border-t border-slate-100">
                    <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">Batal</button>
                    <button type="submit" class="btn-primary text-xs cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </x-modal>

        <!-- MODAL 3: Manage Departments -->
        <x-modal name="modal-manage-departments" focusable>
            <div class="p-5 sm:p-6">
                <h3 class="text-base font-bold text-slate-900 mb-1">Kelola Divisi / Departemen</h3>
                <p class="text-xs text-slate-500 mb-4">Tambahkan atau hapus unit kerja kantor.</p>

                <form method="POST" action="{{ route('admin.departments.store') }}" class="flex flex-col sm:flex-row gap-2 mb-5 pb-4 border-b border-slate-100">
                    @csrf
                    <input type="text" name="code" required maxlength="20" placeholder="Kode (mis: HRD)" class="sm:w-28 text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] uppercase">
                    <input type="text" name="name" required maxlength="150" placeholder="Nama Divisi (mis: Divisi SDM & Umum)" class="flex-1 text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                    <button type="submit" class="btn-primary text-xs py-2 px-3.5 shrink-0 cursor-pointer">Tambah</button>
                </form>

                <div class="divide-y divide-slate-100 max-h-60 overflow-y-auto text-xs">
                    @foreach($departments as $dept)
                        <div class="py-2.5 flex items-center justify-between gap-2">
                            <div>
                                <span class="font-mono font-bold text-[#1E40AF] mr-1.5">{{ $dept->code }}</span>
                                <span class="font-medium text-slate-800">{{ $dept->name }}</span>
                            </div>
                            <form method="POST" action="{{ route('admin.departments.destroy', $dept) }}" onsubmit="return confirm('Hapus divisi ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 hover:underline font-medium cursor-pointer">Hapus</button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end mt-4 pt-3 border-t border-slate-100">
                    <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">Tutup</button>
                </div>
            </div>
        </x-modal>

        <!-- MODAL 4: Manage Ticket Request Categories -->
        <x-modal name="modal-manage-categories" focusable>
            <div class="p-5 sm:p-6" x-data="{ editingCatId: null }">
                <h3 class="text-base font-bold text-slate-900 mb-1">Kelola Kategori Request Tiket</h3>
                <p class="text-xs text-slate-500 mb-4">Tambah, ubah nama, nonaktifkan, atau hapus pilihan kategori permohonan tiket IT.</p>

                <!-- Add New Category Form -->
                <form method="POST" action="{{ route('admin.categories.store') }}" class="flex flex-col sm:flex-row gap-2 mb-5 pb-4 border-b border-slate-100">
                    @csrf
                    <input type="text" name="name" required maxlength="100" placeholder="Nama Kategori (mis: Printer & Scanner)" class="sm:w-48 text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                    <input type="text" name="description" maxlength="255" placeholder="Keterangan singkat (opsional)" class="flex-1 text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                    <button type="submit" class="btn-primary text-xs py-2 px-3.5 shrink-0 cursor-pointer">Tambah</button>
                </form>

                <!-- Existing Categories List -->
                <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto text-xs">
                    @forelse($categories as $cat)
                        <div class="py-3">
                            <!-- View Mode -->
                            <div x-show="editingCatId !== {{ $cat->id }}" class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-semibold text-slate-900">{{ $cat->name }}</span>
                                        @if($cat->is_active)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/70">Aktif</span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">Nonaktif</span>
                                        @endif
                                        <span class="text-[11px] text-slate-400">({{ $cat->tickets_count }} tiket)</span>
                                    </div>
                                    @if($cat->description)
                                        <p class="text-slate-500 mt-0.5 leading-snug">{{ $cat->description }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <button
                                        type="button"
                                        @click="editingCatId = {{ $cat->id }}"
                                        class="text-[#1E40AF] hover:underline font-semibold cursor-pointer"
                                    >
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" onsubmit="return confirm('Hapus kategori request ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:underline font-medium cursor-pointer">Hapus</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Inline Edit Mode -->
                            <form
                                x-show="editingCatId === {{ $cat->id }}"
                                x-cloak
                                method="POST"
                                action="{{ route('admin.categories.update', $cat) }}"
                                class="bg-slate-50 rounded-xl p-3 border border-slate-200 space-y-2.5"
                            >
                                @csrf
                                @method('PATCH')
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Kategori</label>
                                        <input type="text" name="name" value="{{ $cat->name }}" required maxlength="100" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Keterangan</label>
                                        <input type="text" name="description" value="{{ $cat->description }}" maxlength="255" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-1">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="is_active" value="1" {{ $cat->is_active ? 'checked' : '' }} class="rounded border-slate-300 text-[#1E40AF] focus:ring-[#1E40AF]">
                                        <span class="text-xs font-medium text-slate-700">Tampilkan pada form Buat Tiket</span>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="editingCatId = null" class="btn-secondary text-xs py-1 px-2.5">Batal</button>
                                        <button type="submit" class="btn-primary text-xs py-1 px-3 cursor-pointer">Simpan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400">Belum ada kategori request terdaftar.</div>
                    @endforelse
                </div>

                <div class="flex justify-end mt-4 pt-3 border-t border-slate-100">
                    <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">Tutup</button>
                </div>
            </div>
        </x-modal>
    </div>
</x-app-layout>
