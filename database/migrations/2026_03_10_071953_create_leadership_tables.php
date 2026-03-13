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
        Schema::create('jumuiya_leadership_roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('jumuiya_leaderships', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('jumuiya_id')->constrained('jumuiyas', 'id')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members', 'id')->onDelete('restrict');
            $table->foreignId('jumuiya_leadership_role_id')
                ->constrained('jumuiya_leadership_roles', 'id')
                ->onDelete('restrict');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['jumuiya_id', 'end_date'], 'idx_jumuiya_leaderships_jumuiya_end_date');
            $table->index(['member_id', 'end_date'], 'idx_jumuiya_leaderships_member_end_date');
            $table->index('jumuiya_leadership_role_id', 'idx_jumuiya_leaderships_role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jumuiya_leaderships');
        Schema::dropIfExists('jumuiya_leadership_roles');
    }
};
