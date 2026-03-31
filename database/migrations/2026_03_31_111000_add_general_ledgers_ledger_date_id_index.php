<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->indexExists('general_ledgers', 'idx_general_ledgers_ledger_date_id')) {
            Schema::table('general_ledgers', function (Blueprint $table) {
                $table->index(['ledger_id', 'transaction_date', 'id'], 'idx_general_ledgers_ledger_date_id');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('general_ledgers', 'idx_general_ledgers_ledger_date_id')) {
            Schema::table('general_ledgers', function (Blueprint $table) {
                $table->dropIndex('idx_general_ledgers_ledger_date_id');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $databaseName = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
