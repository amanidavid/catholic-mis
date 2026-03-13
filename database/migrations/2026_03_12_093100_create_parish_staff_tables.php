<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parish_staff', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');

            $table->foreignId('member_id')->nullable()->constrained('members', 'id')->onDelete('set null');

            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('national_id')->nullable();

            $table->boolean('has_login')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users', 'id')->onDelete('set null');

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('parish_id', 'idx_parish_staff_parish_id');
            $table->index('member_id', 'idx_parish_staff_member_id');
            $table->index('user_id', 'idx_parish_staff_user_id');

            $table->index(['parish_id', 'last_name'], 'idx_parish_staff_parish_last_name');
            $table->index(['parish_id', 'phone'], 'idx_parish_staff_parish_phone');
            $table->index(['parish_id', 'email'], 'idx_parish_staff_parish_email');

            $table->unique(['parish_id', 'email'], 'uq_parish_staff_parish_email');
            $table->unique(['parish_id', 'phone'], 'uq_parish_staff_parish_phone');
        });

        Schema::create('parish_staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parish_staff_id')->constrained('parish_staff', 'id')->onDelete('cascade');

            $table->foreignId('institution_id')->nullable()->constrained('institutions', 'id')->onDelete('restrict');

            $table->string('assignment_type');
            $table->string('title')->nullable();

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('parish_staff_id', 'idx_staff_assignments_staff_id');
            $table->index(['parish_staff_id', 'is_active'], 'idx_staff_assignments_staff_active');
            $table->index(['assignment_type', 'is_active'], 'idx_staff_assignments_type_active');
            $table->index(['start_date', 'end_date'], 'idx_staff_assignments_dates');
            $table->index(['institution_id', 'is_active'], 'idx_staff_assignments_institution_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parish_staff_assignments');
        Schema::dropIfExists('parish_staff');
    }
};
