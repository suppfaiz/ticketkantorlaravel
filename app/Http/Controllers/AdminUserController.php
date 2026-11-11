<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public const ALLOWED_ROLES = [
        User::ROLE_REQUESTER => 'Karyawan / Pemohon (Requester)',
        User::ROLE_SUPERVISOR => 'Atasan Langsung (Supervisor)',
        User::ROLE_IT_HEAD => 'Kepala Bagian IT (IT Head)',
        User::ROLE_KADIV_IT => 'Kepala Divisi IT & Ops (Kadiv IT)',
        User::ROLE_DIREKSI => 'Dewan Direksi',
        User::ROLE_IT_DEVELOPER => 'Teknisi / IT Developer',
        User::ROLE_ADMIN => 'Administrator Sistem',
    ];

    public function index(Request $request): View
    {
        $query = User::with(['department', 'supervisor'])->orderBy('name');

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('role') && array_key_exists($request->query('role'), self::ALLOWED_ROLES)) {
            $query->where('role', $request->query('role'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', (int) $request->query('department_id'));
        }

        $users = $query->paginate(15)->withQueryString();
        $departments = Department::orderBy('name')->get();
        $categories = Schema::hasTable('ticket_categories')
            ? TicketCategory::withCount('tickets')->orderBy('name')->get()
            : collect();
        $supervisors = User::whereIn('role', [
            User::ROLE_SUPERVISOR,
            User::ROLE_IT_HEAD,
            User::ROLE_KADIV_IT,
            User::ROLE_DIREKSI,
        ])->where('is_active', true)->orderBy('name')->get();

        $roleLabels = self::ALLOWED_ROLES;

        return view('admin.users.index', compact('users', 'departments', 'categories', 'supervisors', 'roleLabels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'role' => ['required', Rule::in(array_keys(self::ALLOWED_ROLES))],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'supervisor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->whereIn('role', [User::ROLE_SUPERVISOR, User::ROLE_IT_HEAD, User::ROLE_KADIV_IT, User::ROLE_DIREKSI])),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $deptName = isset($validated['department_id'])
            ? Department::find($validated['department_id'])?->name
            : null;

        User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'department_id' => $validated['department_id'] ?? null,
            'department' => $deptName,
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna baru '{$validated['name']}' berhasil ditambahkan.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
            'role' => ['required', Rule::in(array_keys(self::ALLOWED_ROLES))],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'supervisor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->whereIn('role', [User::ROLE_SUPERVISOR, User::ROLE_IT_HEAD, User::ROLE_KADIV_IT, User::ROLE_DIREKSI])),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Prevent currently logged-in admin from demoting or deactivating themselves
        if ((int) $user->id === (int) $request->user()->id) {
            if ($validated['role'] !== User::ROLE_ADMIN) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'Anda tidak dapat mengubah role Administrator milik akun Anda sendiri.');
            }
            if (! $request->boolean('is_active', true)) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            }
        }

        if (isset($validated['supervisor_id']) && (int) $validated['supervisor_id'] === (int) $user->id) {
            $validated['supervisor_id'] = null;
        }

        $deptName = isset($validated['department_id'])
            ? Department::find($validated['department_id'])?->name
            : null;

        $payload = [
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => $validated['role'],
            'department_id' => $validated['department_id'] ?? null,
            'department' => $deptName,
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            'is_active' => $request->boolean('is_active', false),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return redirect()->route('admin.users.index')
            ->with('success', "Data pengguna '{$user->name}' berhasil diperbarui.");
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:150'],
        ]);

        Department::create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Divisi/Departemen '{$validated['name']}' berhasil ditambahkan.");
    }

    public function destroyDepartment(Department $department): RedirectResponse
    {
        if ($department->users()->exists()) {
            return redirect()->route('admin.users.index')
                ->with('error', "Divisi '{$department->name}' tidak dapat dihapus karena masih memiliki anggota pengguna.");
        }

        $department->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Divisi '{$department->name}' berhasil dihapus.");
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:ticket_categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        TicketCategory::create([
            'name' => trim($validated['name']),
            'description' => isset($validated['description']) ? trim($validated['description']) : null,
            'is_active' => true,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Kategori request '{$validated['name']}' berhasil ditambahkan.");
    }

    public function updateCategory(Request $request, TicketCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('ticket_categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldName = $category->name;
        $newName = trim($validated['name']);

        DB::transaction(function () use ($category, $validated, $request, $oldName, $newName) {
            $category->update([
                'name' => $newName,
                'description' => isset($validated['description']) ? trim($validated['description']) : null,
                'is_active' => $request->boolean('is_active', false),
            ]);

            if ($oldName !== $newName) {
                Ticket::where('category', $oldName)->update(['category' => $newName]);
            }
        });

        return redirect()->route('admin.users.index')
            ->with('success', "Kategori request '{$newName}' berhasil diperbarui.");
    }

    public function destroyCategory(TicketCategory $category): RedirectResponse
    {
        $usageCount = Ticket::where('category', $category->name)->count();

        if ($usageCount > 0) {
            return redirect()->route('admin.users.index')
                ->with('error', "Kategori '{$category->name}' sudah digunakan pada {$usageCount} tiket. Silakan nonaktifkan kategori ini jika tidak ingin ditampilkan lagi.");
        }

        $category->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Kategori request '{$category->name}' berhasil dihapus.");
    }
}
