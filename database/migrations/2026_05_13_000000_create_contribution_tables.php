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
        Schema::create('contribution_catalogs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('contribution_catalogs_uuid_unique');
            $table->foreignId('parish_id')
                ->nullable()
                ->constrained('parishes', 'id')
                ->onDelete('set null');
            $table->string('name', 80)->comment('e.g., Baptism, Marriage, Catechism, Building Fund');
            $table->string('code', 30)->comment('e.g., BAPTISM, MARRIAGE, CATECHISM, BUILDING_FUND');
            $table->string('description', 250)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users', 'id')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->timestamps();

            $table->index('parish_id', 'idx_contribution_catalogs_parish');
            $table->index('name', 'idx_contribution_catalogs_name');
            $table->index('code', 'idx_contribution_catalogs_code');
            $table->index(['parish_id', 'is_active'], 'idx_contribution_catalogs_parish_active');
        });

        Schema::create('contribution_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('contribution_rules_uuid_unique');
            $table->foreignId('parish_id')
                ->nullable()
                ->constrained('parishes', 'id')
                ->onDelete('set null');
            $table->foreignId('contribution_catalog_id')
                ->constrained('contribution_catalogs', 'id')
                ->onDelete('restrict');
            $table->string('applies_to_type', 60)->comment('e.g., pastoral_service_category, baptism, marriage, program_cycle');
            $table->unsignedBigInteger('applies_to_id')->nullable();
            $table->decimal('amount', 16, 4)->default(0.0000);
            $table->char('currency_code', 3)->default('TZS');
            $table->boolean('is_required')->default(true);
            $table->boolean('allow_partial_payment')->default(false);
            $table->boolean('allow_override')->default(false);
            $table->boolean('waiver_allowed')->default(false);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                ->constrained('users', 'id')
                ->onDelete('restrict');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users', 'id')
                ->onDelete('set null');
            $table->timestamps();

            $table->index('parish_id', 'idx_contribution_rules_parish');
            $table->index('contribution_catalog_id', 'idx_contribution_rules_catalog');
            $table->index('applies_to_type', 'idx_contribution_rules_applies_type');
            $table->index(['applies_to_type', 'applies_to_id'], 'idx_contribution_rules_applies_type_id');
            $table->index(['parish_id', 'is_active', 'effective_from'], 'idx_contribution_rules_parish_active_from');
            $table->index('sort_order', 'idx_contribution_rules_sort');
        });

        Schema::create('contribution_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('contribution_payment_requests_uuid_unique');
            $table->foreignId('parish_id')
                ->nullable()
                ->constrained('parishes', 'id')
                ->onDelete('set null');
            $table->foreignId('contribution_catalog_id')
                ->constrained('contribution_catalogs', 'id')
                ->onDelete('restrict');
            $table->string('source_type', 60)->comment('e.g., pastoral_service_request_item, baptism, marriage, sacrament_program_registration');
            $table->unsignedBigInteger('source_id');
            $table->foreignId('subject_member_id')
                ->nullable()
                ->constrained('members', 'id')
                ->onDelete('set null');
            $table->foreignId('payer_member_id')
                ->nullable()
                ->constrained('members', 'id')
                ->onDelete('set null');
            $table->foreignId('family_id')
                ->nullable()
                ->constrained('families', 'id')
                ->onDelete('set null');
            $table->string('rule_snapshot_name', 80);
            $table->string('rule_snapshot_code', 30);
            $table->decimal('rule_snapshot_amount', 16, 4)->default(0.0000);
            $table->char('currency_code', 3)->default('TZS');
            $table->decimal('amount_due', 16, 4)->default(0.0000);
            $table->decimal('amount_paid', 16, 4)->default(0.0000);
            $table->decimal('balance', 16, 4)->default(0.0000);
            $table->string('status', 20)->default('pending')->comment('pending, partial, paid, waived, cancelled');
            $table->date('due_date')->nullable();
            $table->string('notes', 500)->nullable();
            $table->foreignId('created_by')
                ->constrained('users', 'id')
                ->onDelete('restrict');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users', 'id')
                ->onDelete('set null');
            $table->timestamps();

            $table->index('parish_id', 'idx_contribution_payment_requests_parish');
            $table->index('contribution_catalog_id', 'idx_contribution_payment_requests_catalog');
            $table->index(['source_type', 'source_id'], 'idx_contribution_payment_requests_source');
            $table->index('subject_member_id', 'idx_contribution_payment_requests_subject_member');
            $table->index('payer_member_id', 'idx_contribution_payment_requests_payer_member');
            $table->index('family_id', 'idx_contribution_payment_requests_family');
            $table->index('status', 'idx_contribution_payment_requests_status');
            $table->index('due_date', 'idx_contribution_payment_requests_due_date');
            $table->index(['parish_id', 'status'], 'idx_contribution_payment_requests_parish_status');
            $table->index(['payer_member_id', 'status'], 'idx_contribution_payment_requests_payer_status');
        });

        Schema::create('contribution_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('contribution_transactions_uuid_unique');
            $table->foreignId('parish_id')
                ->nullable()
                ->constrained('parishes', 'id')
                ->onDelete('set null');
            $table->foreignId('contribution_payment_request_id')
                ->constrained('contribution_payment_requests', 'id', 'payment_request_id_foreign')
                ->onDelete('restrict');
            $table->foreignId('member_id')
                ->nullable()
                ->constrained('members', 'id')
                ->onDelete('set null');
            $table->string('transaction_type', 30)->default('payment')->comment('payment, waiver, refund, adjustment');
            $table->decimal('amount', 16, 4)->default(0.0000);
            $table->string('payment_method', 30)->default('cash')->comment('cash, bank, mobile, other');
            $table->string('reference_no', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('notes', 500)->nullable();
            $table->foreignId('recorded_by')
                ->constrained('users', 'id')
                ->onDelete('restrict');
            $table->timestamps();

            $table->index('parish_id', 'idx_contribution_transactions_parish');
            $table->index('contribution_payment_request_id', 'idx_contribution_transactions_payment_request');
            $table->index('member_id', 'idx_contribution_transactions_member');
            $table->index('transaction_type', 'idx_contribution_transactions_type');
            $table->index('reference_no', 'idx_contribution_transactions_reference');
            $table->index('paid_at', 'idx_contribution_transactions_paid_at');
            $table->index(['contribution_payment_request_id', 'paid_at'], 'idx_contribution_transactions_payment_request_paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contribution_transactions');
        Schema::dropIfExists('contribution_payment_requests');
        Schema::dropIfExists('contribution_rules');
        Schema::dropIfExists('contribution_catalogs');
    }
};
