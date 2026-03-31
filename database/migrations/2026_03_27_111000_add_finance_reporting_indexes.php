<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->index('description', 'idx_journals_description');
        });

        Schema::table('general_ledgers', function (Blueprint $table) {
            $table->index(['ledger_id', 'transaction_date', 'id'], 'idx_general_ledgers_ledger_date_id');
        });
    }

    public function down(): void
    {
        Schema::table('general_ledgers', function (Blueprint $table) {
            $table->dropIndex('idx_general_ledgers_ledger_date_id');
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex('idx_journals_description');
        });
    }
};
