<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('member_sacrament_statuses')) {
            return;
        }

        Schema::create('member_sacrament_statuses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('member_id')->constrained('members', 'id')->onDelete('cascade');
            $table->string('sacrament_type', 40);
            $table->boolean('is_received')->default(false);
            $table->string('certificate_no', 120)->nullable();
            $table->date('sacrament_date')->nullable();
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_record_id')->nullable();
            $table->uuid('source_record_uuid')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'sacrament_type'], 'uq_member_sacrament_statuses_member_type');
            $table->unique(['sacrament_type', 'certificate_no'], 'uq_member_sacrament_statuses_type_certificate');
            $table->index(['sacrament_type', 'is_received'], 'idx_member_sacrament_statuses_type_received');
            $table->index(['source_type', 'source_record_id'], 'idx_member_sacrament_statuses_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_sacrament_statuses');
    }
};
