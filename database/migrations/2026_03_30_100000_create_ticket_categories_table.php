<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ticket_categories')) {
            Schema::create('ticket_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('description', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $defaults = [
            ['name' => 'Hardware', 'description' => 'Perangkat keras komputer, printer, monitor, proyektor, dll.'],
            ['name' => 'Software', 'description' => 'Instalasi, lisensi, atau kendala aplikasi kerja.'],
            ['name' => 'Jaringan', 'description' => 'Koneksi internet, Wi-Fi, LAN, VPN, atau perangkat jaringan.'],
            ['name' => 'Akses Akun', 'description' => 'Pembuatan akun baru, reset akses, email kantor, atau hak akses folder.'],
            ['name' => 'Perubahan Sistem', 'description' => 'Penambahan fitur baru, modifikasi modul, atau integrasi sistem (CR).'],
        ];

        $now = now();
        foreach ($defaults as $item) {
            DB::table('ticket_categories')->updateOrInsert(
                ['name' => $item['name']],
                [
                    'description' => $item['description'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        if (Schema::hasTable('tickets')) {
            $existingCategories = DB::table('tickets')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct()
                ->pluck('category');

            foreach ($existingCategories as $catName) {
                DB::table('ticket_categories')->updateOrInsert(
                    ['name' => trim((string) $catName)],
                    [
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_categories');
    }
};
