<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('external_loa_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->string('approval_level', 50)->index(); // supervisor, kadiv_it_ops, direksi
            $table->string('external_reference_id', 100)->nullable()->index();
            $table->string('idempotency_key', 64)->unique()->index();
            $table->string('sync_status', 30)->default('pending')->index(); // pending, synced, sync_failed
            $table->string('decision', 30)->default('waiting')->index(); // waiting, approved, rejected, revision
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('dispatch_payload_hash', 64)->nullable();
            $table->json('response_payload')->nullable();
            $table->text('last_error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_loa_transactions');
    }
};
