<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marriage_sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marriage_id')->constrained('marriages', 'id')->onDelete('cascade');

            $table->string('role', 50);

            $table->string('full_name');
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('relationship', 100)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['marriage_id', 'role'], 'idx_marriage_sponsors_marriage_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marriage_sponsors');
    }
};
