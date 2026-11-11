<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegator_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('delegate_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('scope', 30)->default('all');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('reason', 500);
            $table->boolean('is_active')->default(true);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['delegator_id', 'scope', 'is_active', 'starts_at', 'ends_at'], 'delegation_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_delegations');
    }
};
