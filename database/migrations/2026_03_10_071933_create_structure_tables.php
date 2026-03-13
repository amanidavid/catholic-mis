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
        Schema::create('dioceses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->string('archbishop_name')->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('parishes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('diocese_id')->constrained('dioceses', 'id')->onDelete('restrict');
            $table->string('name')->unique();
            $table->string('code')->nullable();
            $table->string('patron_saint')->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('diocese_id', 'idx_parishes_diocese_id');
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('parish_id', 'idx_zones_parish_id');
            $table->unique(['parish_id', 'name'], 'uq_zones_parish_name');
        });

        Schema::create('jumuiyas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('zone_id')->constrained('zones', 'id')->onDelete('restrict');
            $table->string('name');
            $table->string('meeting_day')->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('zone_id', 'idx_jumuiyas_zone_id');
            $table->unique(['zone_id', 'name'], 'uq_jumuiyas_zone_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jumuiyas');
        Schema::dropIfExists('zones');
        Schema::dropIfExists('parishes');
        Schema::dropIfExists('dioceses');
    }
};
