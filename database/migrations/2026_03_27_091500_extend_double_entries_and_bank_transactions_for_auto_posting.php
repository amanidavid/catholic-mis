<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('double_entries', function (Blueprint $table) {
            $table->string('transaction_type', 30)->nullable()->after('description');
            $table->index('transaction_type', 'idx_double_entries_transaction_type');
            $table->index(['ledger_id', 'transaction_type'], 'idx_double_entries_lookup_type');
        });

        Schema::table('bank_account_transactions', function (Blueprint $table) {
            $table->foreignId('double_entry_id')->nullable()->after('bank_account_id')->constrained('double_entries', 'id')->onDelete('set null');
            $table->foreignId('journal_id')->nullable()->after('source_id')->constrained('journals', 'id')->onDelete('set null');
            $table->index('double_entry_id', 'idx_bank_account_transactions_double_entry');
            $table->index('journal_id', 'idx_bank_account_transactions_journal');
        });

        Schema::table('double_entries', function (Blueprint $table) {
            $table->dropUnique('uq_double_entries_ledger');
            $table->unique(['ledger_id', 'transaction_type'], 'uq_double_entries_ledger_transaction_type');
        });
    }

    public function down(): void
    {
        Schema::table('double_entries', function (Blueprint $table) {
            $table->dropUnique('uq_double_entries_ledger_transaction_type');
        });

        Schema::table('bank_account_transactions', function (Blueprint $table) {
            $table->dropForeign(['double_entry_id']);
            $table->dropForeign(['journal_id']);
            $table->dropIndex('idx_bank_account_transactions_double_entry');
            $table->dropIndex('idx_bank_account_transactions_journal');
            $table->dropColumn(['double_entry_id', 'journal_id']);
        });

        Schema::table('double_entries', function (Blueprint $table) {
            $table->dropIndex('idx_double_entries_transaction_type');
            $table->dropIndex('idx_double_entries_lookup_type');
            $table->dropColumn('transaction_type');
            $table->unique('ledger_id', 'uq_double_entries_ledger');
        });
    }
};
