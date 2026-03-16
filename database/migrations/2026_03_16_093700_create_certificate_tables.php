<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_number_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');

            $table->string('entity_type');
            $table->string('prefix')->nullable();
            $table->unsignedSmallInteger('sequence_padding')->default(6);
            $table->boolean('include_year')->default(true);

            $table->timestamps();

            $table->unique(['parish_id', 'entity_type'], 'uq_cert_rules_parish_entity');
        });

        Schema::create('certificate_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');

            $table->string('entity_type');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('next_number')->default(1);

            $table->timestamps();

            $table->unique(['parish_id', 'entity_type', 'year'], 'uq_cert_sequences_parish_entity_year');
            $table->index(['parish_id', 'entity_type', 'year'], 'idx_cert_sequences_parish_entity_year');
        });

        Schema::create('parish_seals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');

            $table->string('image_path');
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();

            $table->timestamps();

            $table->index(['parish_id', 'is_active'], 'idx_parish_seals_parish_active');
        });

        Schema::create('certificate_issuances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');

            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');

            $table->string('certificate_no');
            $table->string('certificate_no_key');

            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');

            $table->json('snapshot_json')->nullable();

            $table->string('pdf_path')->nullable();
            $table->string('pdf_sha256', 64)->nullable();

            $table->unsignedBigInteger('seal_version')->nullable();

            $table->timestamp('collected_at')->nullable();
            $table->foreignId('collected_by_member_id')->nullable()->constrained('members', 'id')->onDelete('set null');
            $table->string('collected_by_name')->nullable();

            $table->timestamps();

            $table->unique(['parish_id', 'certificate_no_key'], 'uq_cert_issuances_parish_cert_key');
            $table->index(['entity_type', 'entity_id'], 'idx_cert_issuances_entity');
            $table->index(['parish_id', 'issued_at'], 'idx_cert_issuances_parish_issued_at');
            $table->index('certificate_no_key', 'idx_cert_issuances_cert_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_issuances');
        Schema::dropIfExists('parish_seals');
        Schema::dropIfExists('certificate_sequences');
        Schema::dropIfExists('certificate_number_rules');
    }
};
