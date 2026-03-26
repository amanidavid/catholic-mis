<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->unsignedInteger('sequence')->after('journal_no');
            $table->unsignedSmallInteger('journal_year')->after('sequence');

            $table->index(['journal_year', 'sequence'], 'idx_journals_year_sequence');
            $table->unique(['journal_year', 'sequence'], 'uq_journals_year_sequence');
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropUnique('uq_journals_year_sequence');
            $table->dropIndex('idx_journals_year_sequence');
            $table->dropColumn(['sequence', 'journal_year']);
        });
    }
};
