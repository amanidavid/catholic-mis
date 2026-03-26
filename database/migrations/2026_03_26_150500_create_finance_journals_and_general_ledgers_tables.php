<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('journals_uuid_unique');
            $table->string('journal_no', 30)->unique('journals_journal_no_unique');
            $table->date('transaction_date');
            $table->decimal('amount', 16, 4)->default(0.0000);
            $table->string('description', 150)->nullable();

            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users', 'id')->onDelete('restrict');

            $table->foreignId('created_by')->constrained('users', 'id')->onDelete('restrict');
            $table->timestamps();

            $table->index('transaction_date', 'idx_journals_date');
            $table->index('is_posted', 'idx_journals_posted');
            $table->index('created_by', 'idx_journals_created_by');
            $table->index(['transaction_date', 'id'], 'idx_journals_date_id');
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('journal_lines_uuid_unique');

            $table->foreignId('journal_id')->constrained('journals', 'id')->onDelete('cascade');
            $table->foreignId('ledger_id')->constrained('ledgers', 'id')->onDelete('restrict');

            $table->string('description', 150)->nullable();
            $table->decimal('debit_amount', 16, 4)->default(0.0000);
            $table->decimal('credit_amount', 16, 4)->default(0.0000);
            $table->timestamps();

            $table->index('journal_id', 'idx_journal_lines_journal');
            $table->index('ledger_id', 'idx_journal_lines_ledger');
            $table->index(['ledger_id', 'journal_id'], 'idx_journal_lines_ledger_journal');
        });

        Schema::create('general_ledgers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('general_ledgers_uuid_unique');

            $table->foreignId('journal_id')->nullable()->constrained('journals', 'id')->onDelete('set null');
            $table->foreignId('ledger_id')->constrained('ledgers', 'id')->onDelete('restrict');

            $table->string('description', 150)->nullable();
            $table->decimal('debit_amount', 16, 4)->default(0.0000);
            $table->decimal('credit_amount', 16, 4)->default(0.0000);
            $table->date('transaction_date');

            $table->foreignId('created_by')->constrained('users', 'id')->onDelete('restrict');
            $table->timestamps();

            $table->index('ledger_id', 'idx_general_ledgers_ledger');
            $table->index('journal_id', 'idx_general_ledgers_journal');
            $table->index('transaction_date', 'idx_general_ledgers_date');
            $table->index(['ledger_id', 'transaction_date'], 'idx_general_ledgers_ledger_date');
            $table->index(['journal_id', 'ledger_id'], 'idx_general_ledgers_journal_ledger');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_ledgers');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journals');
    }
};
