<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('double_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('double_entries_uuid_unique');

            $table->string('description', 120)->nullable();

            // Lookup key for automated journal creation (optional).
            $table->foreignId('ledger_id')->nullable()->constrained('ledgers', 'id')->onDelete('restrict');

            // The actual double entry mapping.
            $table->foreignId('debit_ledger_id')->constrained('ledgers', 'id')->onDelete('restrict');
            $table->foreignId('credit_ledger_id')->constrained('ledgers', 'id')->onDelete('restrict');

            // Reserved for future linkage, keep nullable.
            $table->foreignId('journal_id')->nullable()->constrained('journals', 'id')->onDelete('set null');

            $table->foreignId('created_by')->constrained('users', 'id')->onDelete('restrict');
            $table->timestamps();

            $table->index('ledger_id', 'idx_double_entries_ledger');
            $table->index('debit_ledger_id', 'idx_double_entries_debit');
            $table->index('credit_ledger_id', 'idx_double_entries_credit');
            $table->index('journal_id', 'idx_double_entries_journal');
            $table->index(['debit_ledger_id', 'credit_ledger_id'], 'idx_double_entries_ledgers');

            // MySQL allows multiple NULLs, so this works as "unique when provided".
            $table->unique('ledger_id', 'uq_double_entries_ledger');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('double_entries');
    }
};
