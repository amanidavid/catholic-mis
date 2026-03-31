<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_cash_funds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('petty_cash_funds_uuid_unique');
            $table->string('name', 100);
            $table->string('code', 30)->unique('petty_cash_funds_code_unique');
            $table->foreignId('ledger_id')->constrained('ledgers', 'id')->onDelete('restrict');
            $table->foreignId('currency_id')->constrained('currencies', 'id')->onDelete('restrict');
            $table->foreignId('custodian_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->decimal('imprest_amount', 16, 4)->default(0.0000);
            $table->decimal('min_reorder_amount', 16, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users', 'id')->onDelete('restrict');
            $table->timestamps();

            $table->index(['is_active', 'name'], 'idx_petty_cash_funds_active_name');
            $table->index('ledger_id', 'idx_petty_cash_funds_ledger');
            $table->index(['custodian_user_id', 'is_active'], 'idx_petty_cash_funds_custodian_active');
        });

        Schema::create('petty_cash_vouchers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('petty_cash_vouchers_uuid_unique');
            $table->string('voucher_no', 30)->unique('petty_cash_vouchers_voucher_no_unique');
            $table->foreignId('petty_cash_fund_id')->constrained('petty_cash_funds', 'id')->onDelete('restrict');
            $table->date('transaction_date');
            $table->string('payee_name', 120)->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->string('description', 150)->nullable();
            $table->decimal('amount', 16, 4)->default(0.0000);
            $table->string('status', 20)->default('draft');
            $table->foreignId('journal_id')->nullable()->constrained('journals', 'id')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users', 'id')->onDelete('restrict');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->timestamps();

            $table->index(['petty_cash_fund_id', 'transaction_date', 'id'], 'idx_petty_cash_vouchers_fund_date_id');
            $table->index(['status', 'transaction_date'], 'idx_petty_cash_vouchers_status_date');
            $table->index('journal_id', 'idx_petty_cash_vouchers_journal');
            $table->index('created_by', 'idx_petty_cash_vouchers_created_by');
        });

        Schema::create('petty_cash_voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('petty_cash_voucher_lines_uuid_unique');
            $table->foreignId('petty_cash_voucher_id')->constrained('petty_cash_vouchers', 'id')->onDelete('cascade');
            $table->foreignId('expense_ledger_id')->constrained('ledgers', 'id')->onDelete('restrict');
            $table->string('description', 150)->nullable();
            $table->decimal('amount', 16, 4)->default(0.0000);
            $table->timestamps();

            $table->index(['petty_cash_voucher_id', 'expense_ledger_id'], 'idx_petty_cash_voucher_lines_voucher_ledger');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_voucher_lines');
        Schema::dropIfExists('petty_cash_vouchers');
        Schema::dropIfExists('petty_cash_funds');
    }
};
