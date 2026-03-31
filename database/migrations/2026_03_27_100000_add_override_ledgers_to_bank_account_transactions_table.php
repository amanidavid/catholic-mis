<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_account_transactions', function (Blueprint $table) {
            $table->foreignId('debit_ledger_id')->nullable()->after('double_entry_id')->constrained('ledgers', 'id')->onDelete('restrict');
            $table->foreignId('credit_ledger_id')->nullable()->after('debit_ledger_id')->constrained('ledgers', 'id')->onDelete('restrict');
            $table->boolean('is_manual_override')->default(false)->after('journal_id');

            $table->index('debit_ledger_id', 'idx_bank_account_transactions_debit_ledger');
            $table->index('credit_ledger_id', 'idx_bank_account_transactions_credit_ledger');
            $table->index(['bank_account_id', 'transaction_type', 'transaction_date'], 'idx_bank_account_transactions_account_type_date');
        });
    }

    public function down(): void
    {
        Schema::table('bank_account_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_bank_account_transactions_debit_ledger');
            $table->dropIndex('idx_bank_account_transactions_credit_ledger');
            $table->dropIndex('idx_bank_account_transactions_account_type_date');
            $table->dropForeign(['debit_ledger_id']);
            $table->dropForeign(['credit_ledger_id']);
            $table->dropColumn(['debit_ledger_id', 'credit_ledger_id', 'is_manual_override']);
        });
    }
};
