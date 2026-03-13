<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_relationships', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name'], 'uq_family_relationships_name');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('family_relationship_id')
                ->nullable()
                ->after('family_id')
                ->constrained('family_relationships', 'id')
                ->onDelete('set null');

            $table->index('family_relationship_id', 'idx_members_family_relationship_id');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['family_relationship_id']);
            $table->dropIndex('idx_members_family_relationship_id');
            $table->dropColumn('family_relationship_id');
        });

        Schema::dropIfExists('family_relationships');
    }
};
