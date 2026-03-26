<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $currencyId = DB::table('currencies')->where('code', 'TZS')->value('id');
        if ($currencyId) {
            DB::table('ledgers')->whereNull('currency_id')->update(['currency_id' => (int) $currencyId]);
        }

        Schema::table('ledgers', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable(false)->change();
            $table->foreign('currency_id', 'ledgers_currency_id_foreign')
                ->references('id')->on('currencies')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropForeign('ledgers_currency_id_foreign');
            $table->unsignedBigInteger('currency_id')->nullable()->change();
        });
    }
};
