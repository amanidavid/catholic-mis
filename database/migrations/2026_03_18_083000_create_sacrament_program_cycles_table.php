<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sacrament_program_cycles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('program', 50);

            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');

            $table->string('name');

            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->dateTime('late_registration_closes_at')->nullable();

            $table->string('status', 30)->default('draft');

            $table->foreignId('created_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');

            $table->timestamps();

            $table->index(['parish_id', 'program', 'status', 'id'], 'idx_program_cycles_parish_program_status_id');
            $table->index(['program', 'id'], 'idx_program_cycles_program_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sacrament_program_cycles');
    }
};
