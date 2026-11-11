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
        Schema::create('meeting_minutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('meeting_date');
            $table->text('attendees')->nullable();
            $table->text('attendees_summary')->nullable();
            $table->longText('notes')->nullable();
            $table->text('scope_summary')->nullable();
            $table->text('deliverables')->nullable();
            $table->date('target_completion_date')->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->string('status', 50)->default('Menunggu Persetujuan Notulensi');
            $table->boolean('is_approved_by_it_head')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_it_head_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('revision_comments')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_minutes');
    }
};
