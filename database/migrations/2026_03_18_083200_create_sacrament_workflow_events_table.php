<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sacrament_workflow_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');

            $table->string('entity_type', 60);
            $table->unsignedBigInteger('entity_id');

            $table->string('action', 60);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();

            $table->foreignId('performed_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->dateTime('performed_at');

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'id'], 'idx_sacrament_workflow_events_entity');
            $table->index(['parish_id', 'entity_type', 'id'], 'idx_sacrament_workflow_events_parish_entity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sacrament_workflow_events');
    }
};
