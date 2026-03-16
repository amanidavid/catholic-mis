<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('baptisms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');
            $table->foreignId('origin_jumuiya_id')->constrained('jumuiyas', 'id')->onDelete('restrict');

            $table->foreignId('member_id')->constrained('members', 'id')->onDelete('restrict');

            $table->date('birth_date')->nullable();
            $table->string('birth_town')->nullable();
            $table->string('residence')->nullable();

            $table->date('baptism_date')->nullable();
            $table->foreignId('baptism_parish_id')->nullable()->constrained('parishes', 'id')->onDelete('restrict');

            $table->string('certificate_no')->nullable();
            $table->string('certificate_no_key')->nullable();

            $table->foreignId('father_member_id')->nullable()->constrained('members', 'id')->onDelete('set null');
            $table->string('father_name')->nullable();

            $table->foreignId('mother_member_id')->nullable()->constrained('members', 'id')->onDelete('set null');
            $table->string('mother_name')->nullable();

            $table->foreignId('sponsor_member_id')->nullable()->constrained('members', 'id')->onDelete('set null');
            $table->string('sponsor_name')->nullable();

            $table->foreignId('minister_staff_id')->nullable()->constrained('parish_staff', 'id')->onDelete('set null');
            $table->string('minister_name')->nullable();

            $table->string('status')->default('draft');

            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');

            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');

            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');
            $table->text('rejection_reason')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');

            $table->timestamps();

            $table->unique(['parish_id', 'member_id'], 'uq_baptisms_parish_member');
            $table->unique(['parish_id', 'certificate_no_key'], 'uq_baptisms_parish_cert_key');

            $table->index(['origin_jumuiya_id', 'status'], 'idx_baptisms_jumuiya_status');
            $table->index(['parish_id', 'status', 'created_at'], 'idx_baptisms_parish_status_created');
            $table->index('certificate_no_key', 'idx_baptisms_cert_key');
        });

        Schema::create('communions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');
            $table->foreignId('origin_jumuiya_id')->constrained('jumuiyas', 'id')->onDelete('restrict');

            $table->foreignId('member_id')->constrained('members', 'id')->onDelete('restrict');

            $table->date('communion_date')->nullable();
            $table->foreignId('communion_parish_id')->nullable()->constrained('parishes', 'id')->onDelete('restrict');

            $table->foreignId('minister_staff_id')->nullable()->constrained('parish_staff', 'id')->onDelete('set null');
            $table->string('minister_name')->nullable();

            $table->string('status')->default('draft');

            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');

            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');

            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');
            $table->text('rejection_reason')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique(['parish_id', 'member_id'], 'uq_communions_parish_member');

            $table->index(['origin_jumuiya_id', 'status'], 'idx_communions_jumuiya_status');
            $table->index(['parish_id', 'status', 'created_at'], 'idx_communions_parish_status_created');
        });

        Schema::create('confirmations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');
            $table->foreignId('origin_jumuiya_id')->constrained('jumuiyas', 'id')->onDelete('restrict');

            $table->foreignId('member_id')->constrained('members', 'id')->onDelete('restrict');

            $table->string('residence')->nullable();

            $table->date('confirmation_date')->nullable();
            $table->foreignId('confirmation_parish_id')->nullable()->constrained('parishes', 'id')->onDelete('restrict');

            $table->string('certificate_no')->nullable();
            $table->string('certificate_no_key')->nullable();

            $table->foreignId('sponsor_member_id')->nullable()->constrained('members', 'id')->onDelete('set null');
            $table->string('sponsor_name')->nullable();

            $table->foreignId('minister_staff_id')->nullable()->constrained('parish_staff', 'id')->onDelete('set null');
            $table->string('minister_name')->nullable();

            $table->string('status')->default('draft');

            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');

            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');

            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');
            $table->text('rejection_reason')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');

            $table->timestamps();

            $table->unique(['parish_id', 'member_id'], 'uq_confirmations_parish_member');
            $table->unique(['parish_id', 'certificate_no_key'], 'uq_confirmations_parish_cert_key');

            $table->index(['origin_jumuiya_id', 'status'], 'idx_confirmations_jumuiya_status');
            $table->index(['parish_id', 'status', 'created_at'], 'idx_confirmations_parish_status_created');
            $table->index('certificate_no_key', 'idx_confirmations_cert_key');
        });

        Schema::create('marriages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');
            $table->foreignId('origin_jumuiya_id')->constrained('jumuiyas', 'id')->onDelete('restrict');

            $table->foreignId('groom_member_id')->constrained('members', 'id')->onDelete('restrict');
            $table->foreignId('bride_member_id')->constrained('members', 'id')->onDelete('restrict');

            $table->date('marriage_date')->nullable();
            $table->foreignId('marriage_parish_id')->nullable()->constrained('parishes', 'id')->onDelete('restrict');

            $table->string('certificate_no')->nullable();
            $table->string('certificate_no_key')->nullable();

            $table->foreignId('male_witness_member_id')->nullable()->constrained('members', 'id')->onDelete('set null');
            $table->string('male_witness_name')->nullable();

            $table->foreignId('female_witness_member_id')->nullable()->constrained('members', 'id')->onDelete('set null');
            $table->string('female_witness_name')->nullable();

            $table->foreignId('minister_staff_id')->nullable()->constrained('parish_staff', 'id')->onDelete('set null');
            $table->string('minister_name')->nullable();

            $table->string('status')->default('draft');

            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');

            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');

            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');
            $table->text('rejection_reason')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users', 'id')->onDelete('restrict');

            $table->timestamps();

            $table->unique(['parish_id', 'certificate_no_key'], 'uq_marriages_parish_cert_key');

            $table->index(['origin_jumuiya_id', 'status'], 'idx_marriages_jumuiya_status');
            $table->index(['parish_id', 'status', 'created_at'], 'idx_marriages_parish_status_created');
            $table->index('certificate_no_key', 'idx_marriages_cert_key');
            $table->index(['groom_member_id', 'bride_member_id'], 'idx_marriages_groom_bride');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marriages');
        Schema::dropIfExists('confirmations');
        Schema::dropIfExists('communions');
        Schema::dropIfExists('baptisms');
    }
};
