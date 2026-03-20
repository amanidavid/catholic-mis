<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sacrament_program_registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('program_cycle_id')
                ->constrained('sacrament_program_cycles', 'id')
                ->onDelete('cascade');

            $table->string('program', 50);

            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');
            $table->foreignId('origin_jumuiya_id')->constrained('jumuiyas', 'id')->onDelete('restrict');

            $table->foreignId('family_id')->constrained('families', 'id')->onDelete('restrict');
            $table->foreignId('member_id')->constrained('members', 'id')->onDelete('restrict');

            $table->boolean('is_transfer')->default(false);

            $table->string('status', 30)->default('draft');

            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->foreignId('submitted_by_member_id')->nullable()->constrained('members', 'id')->onDelete('set null');

            $table->dateTime('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');

            $table->dateTime('rejected_at')->nullable();
            $table->foreignId('rejected_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->text('rejection_reason')->nullable();

            $table->dateTime('completed_at')->nullable();

            $table->dateTime('issued_at')->nullable();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['program_cycle_id', 'member_id'], 'uq_program_registrations_cycle_member');

            $table->index(['program_cycle_id', 'status', 'id'], 'idx_program_registrations_cycle_status_id');
            $table->index(['parish_id', 'id'], 'idx_program_registrations_parish_id');
            $table->index(['origin_jumuiya_id', 'id'], 'idx_program_registrations_origin_jumuiya_id');
            $table->index(['member_id', 'program', 'status'], 'idx_program_registrations_member_program_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sacrament_program_registrations');
    }
};
