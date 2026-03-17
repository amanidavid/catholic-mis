<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marriages', function (Blueprint $table) {
            $table->string('bride_external_full_name')->nullable()->after('bride_member_id');
            $table->string('bride_external_phone', 50)->nullable()->after('bride_external_full_name');
            $table->string('bride_external_address')->nullable()->after('bride_external_phone');
            $table->string('bride_external_home_parish_name')->nullable()->after('bride_external_address');

            $table->string('male_witness_phone', 50)->nullable()->after('male_witness_name');
            $table->string('male_witness_address')->nullable()->after('male_witness_phone');
            $table->string('male_witness_relationship', 100)->nullable()->after('male_witness_address');

            $table->string('female_witness_phone', 50)->nullable()->after('female_witness_name');
            $table->string('female_witness_address')->nullable()->after('female_witness_phone');
            $table->string('female_witness_relationship', 100)->nullable()->after('female_witness_address');
        });

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE marriages MODIFY bride_member_id BIGINT UNSIGNED NULL');
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE marriages ALTER COLUMN bride_member_id DROP NOT NULL');
        }

        if ($driver === 'sqlite') {
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE marriages MODIFY bride_member_id BIGINT UNSIGNED NOT NULL');
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE marriages ALTER COLUMN bride_member_id SET NOT NULL');
        }

        Schema::table('marriages', function (Blueprint $table) {
            $table->dropColumn([
                'bride_external_full_name',
                'bride_external_phone',
                'bride_external_address',
                'bride_external_home_parish_name',
                'male_witness_phone',
                'male_witness_address',
                'male_witness_relationship',
                'female_witness_phone',
                'female_witness_address',
                'female_witness_relationship',
            ]);
        });
    }
};
