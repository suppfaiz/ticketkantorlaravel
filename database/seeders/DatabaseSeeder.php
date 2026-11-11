<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->call(ProductionSeeder::class);

            return;
        }

        // 1. Departments
        $deptIt = Department::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Divisi Teknologi Informasi']
        );

        $deptOps = Department::firstOrCreate(
            ['code' => 'OPS'],
            ['name' => 'Divisi Operasional & Umum']
        );

        $deptFin = Department::firstOrCreate(
            ['code' => 'FIN'],
            ['name' => 'Divisi Keuangan & Akuntansi']
        );

        // 1b. Ticket Request Categories
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

        // 2. Supervisor
        $supervisor = User::firstOrCreate(
            ['email' => 'supervisor@kantor.internal'],
            [
                'name' => 'Budi Santoso (Supervisor)',
                'password' => Hash::make('password'),
                'role' => 'supervisor',
                'department_id' => $deptOps->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // 3. Requester (bawahan langsung dari supervisor)
        $requesterUser = User::firstOrCreate(
            ['email' => 'requester@kantor.internal'],
            [
                'name' => 'Ahmad Fauzi (Requester)',
                'password' => Hash::make('password'),
                'role' => 'requester',
                'department_id' => $deptOps->id,
                'supervisor_id' => $supervisor->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        if (! $requesterUser->supervisor_id) {
            $requesterUser->update([
                'supervisor_id' => $supervisor->id,
                'department_id' => $deptOps->id,
            ]);
        }

        // Requester B (unit kerja lain untuk pengujian IDOR)
        User::firstOrCreate(
            ['email' => 'requester_b@kantor.internal'],
            [
                'name' => 'Siti Rahma (Requester B)',
                'password' => Hash::make('password'),
                'role' => 'requester',
                'department_id' => $deptFin->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // 4. Kepala Bagian IT
        User::updateOrCreate(
            ['email' => 'kabagit@kantor.internal'],
            [
                'name' => 'Hendra Wijaya (Kabag IT)',
                'password' => Hash::make('password'),
                'role' => 'it_head',
                'department_id' => $deptIt->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // 4b. Kepala Divisi IT & Operasional (Tahap C.1 Major)
        User::updateOrCreate(
            ['email' => 'kadivit@kantor.internal'],
            [
                'name' => 'Irwan Setiawan (Kadiv IT & Ops)',
                'password' => Hash::make('password'),
                'role' => 'kadiv_it',
                'department_id' => $deptIt->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // 4c. Dewan Direksi (Tahap C.2 Major)
        User::updateOrCreate(
            ['email' => 'direksi@kantor.internal'],
            [
                'name' => 'Dr. Bambang Hartono (Direksi)',
                'password' => Hash::make('password'),
                'role' => 'direksi',
                'department_id' => $deptOps->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // 5. IT Developers
        User::firstOrCreate(
            ['email' => 'dev1@kantor.internal'],
            [
                'name' => 'Rian Pratama (Dev 1)',
                'password' => Hash::make('password'),
                'role' => 'it_developer',
                'department_id' => $deptIt->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'dev2@kantor.internal'],
            [
                'name' => 'Doni Kusuma (Dev 2)',
                'password' => Hash::make('password'),
                'role' => 'it_developer',
                'department_id' => $deptIt->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // 6. Administrator Sistem
        User::firstOrCreate(
            ['email' => 'admin@kantor.internal'],
            [
                'name' => 'Administrator Sistem',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department_id' => $deptIt->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
    }
}
