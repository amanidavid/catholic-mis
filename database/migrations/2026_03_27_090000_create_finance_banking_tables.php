<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('banks_uuid_unique');
            $table->string('name', 120);
            $table->string('name_normalized', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name_normalized', 'banks_name_normalized_unique');
            $table->index('name', 'idx_banks_name');
            $table->index('is_active', 'idx_banks_active');
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('bank_accounts_uuid_unique');

            $table->foreignId('bank_id')
                ->constrained('banks', 'id')
                ->onDelete('restrict');

            $table->foreignId('ledger_id')
                ->constrained('ledgers', 'id')
                ->onDelete('restrict');

            $table->foreignId('currency_id')
                ->constrained('currencies', 'id')
                ->onDelete('restrict');

            $table->string('account_name', 120);
            $table->string('account_name_normalized', 120);
            $table->string('account_number', 40);
            $table->string('branch', 80)->nullable();
            $table->string('swift_code', 20)->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->constrained('users', 'id')
                ->onDelete('restrict');

            $table->timestamps();

            $table->unique('ledger_id', 'bank_accounts_ledger_id_unique');
            $table->unique(['bank_id', 'account_number'], 'uq_bank_accounts_bank_account_number');
            $table->index('bank_id', 'idx_bank_accounts_bank');
            $table->index('currency_id', 'idx_bank_accounts_currency');
            $table->index('account_name_normalized', 'idx_bank_accounts_name_normalized');
            $table->index('account_number', 'idx_bank_accounts_number');
            $table->index(['bank_id', 'is_active'], 'idx_bank_accounts_bank_active');
        });

        Schema::create('bank_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('bank_account_transactions_uuid_unique');

            $table->foreignId('bank_account_id')
                ->constrained('bank_accounts', 'id')
                ->onDelete('restrict');

            $table->date('transaction_date');
            $table->string('transaction_type', 30)->default('manual');
            $table->enum('direction', ['inflow', 'outflow']);
            $table->decimal('amount', 16, 4);
            $table->string('reference_no', 100)->nullable();
            $table->string('description', 150)->nullable();
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->foreignId('created_by')
                ->constrained('users', 'id')
                ->onDelete('restrict');

            $table->timestamps();

            $table->index('bank_account_id', 'idx_bank_account_transactions_account');
            $table->index('transaction_date', 'idx_bank_account_transactions_date');
            $table->index('transaction_type', 'idx_bank_account_transactions_type');
            $table->index('reference_no', 'idx_bank_account_transactions_reference');
            $table->index(['bank_account_id', 'transaction_date', 'id'], 'idx_bank_account_transactions_account_date_id');
            $table->index(['source_type', 'source_id'], 'idx_bank_account_transactions_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_account_transactions');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('banks');
    }
};
