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
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('jumuiya_id')->constrained('jumuiyas', 'id')->onDelete('restrict');
            $table->string('family_name');
            $table->string('family_code')->nullable();
            $table->string('house_number')->nullable();
            $table->string('street')->nullable();
            $table->unsignedBigInteger('head_of_family_member_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('jumuiya_id', 'idx_families_jumuiya_id');
            $table->unique(['jumuiya_id', 'family_name'], 'uq_families_jumuiya_name');
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('family_id')->constrained('families', 'id')->onDelete('restrict');
            $table->foreignId('jumuiya_id')->constrained('jumuiyas', 'id')->onDelete('restrict');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('national_id')->nullable();
            $table->string('marital_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('family_id', 'idx_members_family_id');
            $table->index('jumuiya_id', 'idx_members_jumuiya_id');
            $table->index(['jumuiya_id', 'last_name'], 'idx_members_jumuiya_last_name');
        });

        Schema::table('families', function (Blueprint $table) {
            $table->foreign('head_of_family_member_id')
                ->references('id')
                ->on('members')
                ->onDelete('set null');
        });

        Schema::create('member_jumuiya_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('member_id')->constrained('members', 'id')->onDelete('cascade');
            $table->unsignedBigInteger('from_jumuiya_id')->nullable();
            $table->foreignId('to_jumuiya_id')->constrained('jumuiyas', 'id')->onDelete('restrict');
            $table->date('effective_date');
            $table->text('reason')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users', 'id')->onDelete('restrict');
            $table->timestamps();

            $table->foreign('from_jumuiya_id')
                ->references('id')
                ->on('jumuiyas')
                ->onDelete('set null');

            $table->index('member_id', 'idx_member_jumuiya_histories_member');
            $table->index('effective_date', 'idx_member_jumuiya_histories_effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_jumuiya_histories');
        Schema::table('families', function (Blueprint $table) {
            $table->dropForeign(['head_of_family_member_id']);
        });
        Schema::dropIfExists('members');
        Schema::dropIfExists('families');
    }
};
