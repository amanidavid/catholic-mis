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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('action', 50);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('changed_by')->constrained('users', 'id')->onDelete('restrict');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id'], 'idx_audit_logs_model');
            $table->index('changed_by', 'idx_audit_logs_changed_by');
            $table->index('action', 'idx_audit_logs_action');
            $table->index('created_at', 'idx_audit_logs_created_at');
            $table->index(['model_type', 'created_at'], 'idx_audit_logs_model_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
