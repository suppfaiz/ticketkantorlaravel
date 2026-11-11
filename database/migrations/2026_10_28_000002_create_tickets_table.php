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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 50)->unique()->index();
            $table->foreignId('requester_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->string('category', 100);
            $table->string('title', 255);
            $table->text('description');
            $table->string('classification', 20)->nullable()->index(); // Minor, Major
            $table->string('priority', 20)->nullable(); // Low, Medium, High, Urgent
            $table->string('status', 60)->default('Menunggu Approval Atasan')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_by_supervisor_at')->nullable();
            $table->timestamp('approved_by_it_head_at')->nullable();
            $table->timestamp('approved_by_kadiv_at')->nullable();
            $table->timestamp('approved_by_direksi_at')->nullable();
            $table->timestamp('meeting_scheduled_at')->nullable();
            $table->timestamp('minutes_submitted_at')->nullable();
            $table->timestamp('minutes_approved_at')->nullable();
            $table->timestamp('target_completion_at')->nullable();
            $table->timestamp('work_started_at')->nullable();
            $table->timestamp('work_completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
