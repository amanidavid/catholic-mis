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
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('type');
            $table->string('location')->nullable();
            $table->string('country')->nullable();
            $table->string('contact')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active'], 'idx_institutions_type_active');
        });

        Schema::create('clergy', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions', 'id')->onDelete('restrict');
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->date('ordination_date')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('clergy_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('institution_id', 'idx_clergy_institution_id');
            $table->index(['last_name', 'first_name'], 'idx_clergy_name');
        });

        Schema::create('parish_clergy_assignments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('cascade');
            $table->foreignId('clergy_id')->constrained('clergy', 'id')->onDelete('restrict');
            $table->string('role');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parish_id', 'is_active'], 'idx_parish_clergy_assignments_parish_active');
            $table->index(['clergy_id', 'is_active'], 'idx_parish_clergy_assignments_clergy_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parish_clergy_assignments');
        Schema::dropIfExists('clergy');
        Schema::dropIfExists('institutions');
    }
};
