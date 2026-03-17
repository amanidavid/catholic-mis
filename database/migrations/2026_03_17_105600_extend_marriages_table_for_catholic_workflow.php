<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marriages', function (Blueprint $table) {
            $table->foreignId('groom_family_id')->nullable()->after('groom_member_id')->constrained('families', 'id')->onDelete('restrict');
            $table->foreignId('groom_jumuiya_id')->nullable()->after('groom_family_id')->constrained('jumuiyas', 'id')->onDelete('restrict');
            $table->foreignId('groom_parish_id')->nullable()->after('groom_jumuiya_id')->constrained('parishes', 'id')->onDelete('restrict');

            $table->foreignId('bride_family_id')->nullable()->after('bride_member_id')->constrained('families', 'id')->onDelete('restrict');
            $table->foreignId('bride_jumuiya_id')->nullable()->after('bride_family_id')->constrained('jumuiyas', 'id')->onDelete('restrict');
            $table->foreignId('bride_parish_id')->nullable()->after('bride_jumuiya_id')->constrained('parishes', 'id')->onDelete('restrict');

            $table->time('marriage_time')->nullable()->after('marriage_date');
            $table->string('wedding_type', 50)->nullable()->after('marriage_parish_id');

            $table->foreignId('submitted_origin_jumuiya_id')->nullable()->after('submitted_by_user_id')->constrained('jumuiyas', 'id')->onDelete('restrict');
            $table->foreignId('submitted_by_member_id')->nullable()->after('submitted_origin_jumuiya_id')->constrained('members', 'id')->onDelete('restrict');

            $table->string('couple_key', 191)->nullable()->after('certificate_no_key');

            $table->index(['parish_id', 'marriage_date'], 'idx_marriages_parish_marriage_date');
            $table->index(['groom_member_id'], 'idx_marriages_groom_member');
            $table->index(['bride_member_id'], 'idx_marriages_bride_member');
            $table->unique(['parish_id', 'couple_key'], 'uq_marriages_parish_couple_key');
        });
    }

    public function down(): void
    {
        Schema::table('marriages', function (Blueprint $table) {
            $table->dropUnique('uq_marriages_parish_couple_key');
            $table->dropIndex('idx_marriages_parish_marriage_date');
            $table->dropIndex('idx_marriages_groom_member');
            $table->dropIndex('idx_marriages_bride_member');

            $table->dropConstrainedForeignId('submitted_by_member_id');
            $table->dropConstrainedForeignId('submitted_origin_jumuiya_id');

            $table->dropColumn('wedding_type');
            $table->dropColumn('marriage_time');

            $table->dropConstrainedForeignId('bride_parish_id');
            $table->dropConstrainedForeignId('bride_jumuiya_id');
            $table->dropConstrainedForeignId('bride_family_id');

            $table->dropConstrainedForeignId('groom_parish_id');
            $table->dropConstrainedForeignId('groom_jumuiya_id');
            $table->dropConstrainedForeignId('groom_family_id');

            $table->dropColumn('couple_key');
        });
    }
};
