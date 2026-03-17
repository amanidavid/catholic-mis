<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marriage_parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marriage_id')->constrained('marriages', 'id')->onDelete('cascade');
            $table->string('party', 10);

            $table->foreignId('father_member_id')->nullable()->constrained('members', 'id')->onDelete('set null');
            $table->string('father_name')->nullable();
            $table->string('father_religion', 50)->nullable();
            $table->boolean('father_is_alive')->nullable();

            $table->foreignId('mother_member_id')->nullable()->constrained('members', 'id')->onDelete('set null');
            $table->string('mother_name')->nullable();
            $table->string('mother_religion', 50)->nullable();
            $table->boolean('mother_is_alive')->nullable();

            $table->timestamps();

            $table->unique(['marriage_id', 'party'], 'uq_marriage_parents_marriage_party');
            $table->index(['party'], 'idx_marriage_parents_party');
        });

        Schema::create('marriage_freedom_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marriage_id')->constrained('marriages', 'id')->onDelete('cascade');
            $table->string('party', 10);

            $table->boolean('ever_married_before')->default(false);
            $table->string('previous_marriage_type', 20)->nullable();
            $table->boolean('previous_spouse_deceased')->nullable();
            $table->boolean('annulment_exists')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['marriage_id', 'party'], 'uq_marriage_freedom_marriage_party');
            $table->index(['party'], 'idx_marriage_freedom_party');
        });

        Schema::create('marriage_sacramental_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marriage_id')->constrained('marriages', 'id')->onDelete('cascade');
            $table->string('party', 10);

            $table->boolean('is_baptized')->default(true);
            $table->string('baptism_parish_name')->nullable();

            $table->boolean('is_confirmed')->nullable();
            $table->string('confirmation_parish_name')->nullable();

            $table->boolean('first_holy_communion_done')->nullable();

            $table->timestamps();

            $table->unique(['marriage_id', 'party'], 'uq_marriage_sacramental_marriage_party');
            $table->index(['party'], 'idx_marriage_sacramental_party');
        });

        Schema::create('marriage_preparation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marriage_id')->constrained('marriages', 'id')->onDelete('cascade');

            $table->boolean('attended_class')->nullable();
            $table->boolean('retreat_completed')->nullable();
            $table->unsignedSmallInteger('counseling_sessions_count')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['marriage_id'], 'uq_marriage_preparation_marriage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marriage_preparation');
        Schema::dropIfExists('marriage_sacramental_status');
        Schema::dropIfExists('marriage_freedom_checks');
        Schema::dropIfExists('marriage_parents');
    }
};
