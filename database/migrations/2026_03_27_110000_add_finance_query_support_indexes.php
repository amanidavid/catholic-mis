<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->index(['is_active', 'name_normalized'], 'idx_banks_active_name_normalized');
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->index(['bank_id', 'account_name_normalized'], 'idx_bank_accounts_bank_name_normalized');
        });

        Schema::table('ledgers', function (Blueprint $table) {
            $table->index(['is_active', 'name'], 'idx_ledgers_active_name');
        });
    }

    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropIndex('idx_ledgers_active_name');
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropIndex('idx_bank_accounts_bank_name_normalized');
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->dropIndex('idx_banks_active_name_normalized');
        });
    }
};
