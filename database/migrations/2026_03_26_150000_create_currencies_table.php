<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('currencies_uuid_unique');
            $table->string('code', 3)->unique('currencies_code_unique');
            $table->string('name', 40);
            $table->string('symbol', 10)->nullable();
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code', 'idx_currencies_code');
        });

        DB::table('currencies')->insert([
            'uuid' => method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid(),
            'code' => 'TZS',
            'name' => 'Tanzanian Shilling',
            'symbol' => 'TZS',
            'decimals' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
