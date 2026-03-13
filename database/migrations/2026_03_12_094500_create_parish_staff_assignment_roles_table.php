<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parish_staff_assignment_roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');

            $table->string('name');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('parish_id', 'idx_staff_assignment_roles_parish_id');
            $table->index(['parish_id', 'is_active'], 'idx_staff_assignment_roles_parish_active');
            $table->unique(['parish_id', 'name'], 'uq_staff_assignment_roles_parish_name');
        });

        Schema::table('parish_staff_assignments', function (Blueprint $table) {
            $table->foreignId('parish_staff_assignment_role_id')
                ->nullable()
                ->after('parish_staff_id')
                ->constrained('parish_staff_assignment_roles', 'id')
                ->onDelete('restrict');

            $table->index('parish_staff_assignment_role_id', 'idx_staff_assignments_role_id');
        });
    }

    public function down(): void
    {
        Schema::table('parish_staff_assignments', function (Blueprint $table) {
            $table->dropIndex('idx_staff_assignments_role_id');
            $table->dropConstrainedForeignId('parish_staff_assignment_role_id');
        });

        Schema::dropIfExists('parish_staff_assignment_roles');
    }
};
