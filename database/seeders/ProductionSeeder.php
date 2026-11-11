<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProductionSeeder extends Seeder
{
    /**
     * Seed master data essential for production without dummy test accounts.
     */
    public function run(): void
    {
        // 1. Default Corporate Departments
        $deptIt = Department::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Divisi Teknologi Informasi']
        );

        Department::firstOrCreate(
            ['code' => 'OPS'],
            ['name' => 'Divisi Operasional & Umum']
        );

        Department::firstOrCreate(
            ['code' => 'FIN'],
            ['name' => 'Divisi Keuangan & Akuntansi']
        );

        // 2. Default Ticket Request Categories
        if (Schema::hasTable('ticket_categories')) {
            $defaultCategories = [
                ['name' => 'Hardware', 'description' => 'Perangkat keras komputer, printer, monitor, proyektor, dll.'],
                ['name' => 'Software', 'description' => 'Instalasi, lisensi, atau kendala aplikasi kerja.'],
                ['name' => 'Jaringan', 'description' => 'Koneksi internet, Wi-Fi, LAN, VPN, atau perangkat jaringan.'],
                ['name' => 'Akses Akun', 'description' => 'Pembuatan akun baru, reset akses, email kantor, atau hak akses folder.'],
                ['name' => 'Perubahan Sistem', 'description' => 'Penambahan fitur baru, modifikasi modul, atau integrasi sistem (CR).'],
            ];

            foreach ($defaultCategories as $cat) {
                TicketCategory::firstOrCreate(
                    ['name' => $cat['name']],
                    ['description' => $cat['description'], 'is_active' => true]
                );
            }
        }

        // 3. Initial System Administrator Account
        $adminEmail = trim((string) env('INITIAL_ADMIN_EMAIL'));
        $adminPassword = (string) env('INITIAL_ADMIN_PASSWORD');

        if ($adminEmail === '' || ! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('INITIAL_ADMIN_EMAIL wajib diisi dengan alamat email yang valid.');
        }

        if (strlen($adminPassword) < 12) {
            throw new \RuntimeException('INITIAL_ADMIN_PASSWORD wajib diisi minimal 12 karakter.');
        }

        User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrator Sistem',
                'password' => Hash::make($adminPassword),
                'role' => User::ROLE_ADMIN,
                'department_id' => $deptIt->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
    }
}
