<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sacrament_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');

            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');

            $table->dateTime('scheduled_for');
            $table->foreignId('location_parish_id')->nullable()->constrained('parishes', 'id')->onDelete('restrict');
            $table->string('location_text')->nullable();

            $table->string('status')->default('proposed');

            $table->foreignId('created_by_user_id')->constrained('users', 'id')->onDelete('restrict');

            $table->timestamps();

            $table->index(['entity_type', 'entity_id'], 'idx_sacrament_schedules_entity');
            $table->index(['parish_id', 'scheduled_for'], 'idx_sacrament_schedules_parish_datetime');
        });

        Schema::create('sacrament_schedule_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sacrament_schedule_id')
                ->constrained('sacrament_schedules', 'id')
                ->onDelete('cascade');

            $table->dateTime('old_scheduled_for')->nullable();
            $table->dateTime('new_scheduled_for')->nullable();

            $table->foreignId('changed_by_user_id')->constrained('users', 'id')->onDelete('restrict');
            $table->text('reason')->nullable();

            $table->timestamps();

            $table->index('sacrament_schedule_id', 'idx_schedule_changes_schedule_id');
            $table->index('changed_by_user_id', 'idx_schedule_changes_changed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sacrament_schedule_changes');
        Schema::dropIfExists('sacrament_schedules');
    }
};
