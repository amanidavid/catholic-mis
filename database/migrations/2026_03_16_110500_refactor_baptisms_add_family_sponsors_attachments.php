<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('baptisms', function (Blueprint $table) {
            $table->foreignId('family_id')->nullable()->after('member_id')->constrained('families', 'id')->onDelete('restrict');
        });

        DB::table('baptisms')
            ->join('members', 'members.id', '=', 'baptisms.member_id')
            ->whereNull('baptisms.family_id')
            ->update([
                'baptisms.family_id' => DB::raw('members.family_id'),
            ]);

        Schema::create('baptism_sponsors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('baptism_id')->constrained('baptisms', 'id')->onDelete('cascade');

            $table->string('role')->nullable();

            $table->foreignId('member_id')->nullable()->constrained('members', 'id')->onDelete('set null');

            $table->string('full_name')->nullable();
            $table->string('parish_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->timestamps();

            $table->index(['baptism_id'], 'idx_baptism_sponsors_baptism');
            $table->index(['member_id'], 'idx_baptism_sponsors_member');
        });

        Schema::create('sacrament_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');

            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');

            $table->string('type');

            $table->string('original_name');
            $table->string('mime_type', 190);
            $table->unsignedBigInteger('size_bytes');

            $table->string('storage_disk', 50)->default('local');
            $table->string('storage_path');
            $table->string('sha256', 64);

            $table->foreignId('uploaded_by_user_id')->constrained('users', 'id')->onDelete('restrict');

            $table->timestamps();

            $table->index(['entity_type', 'entity_id'], 'idx_sacrament_attachments_entity');
            $table->index(['entity_type', 'entity_id', 'type'], 'idx_sacrament_attachments_entity_type');
            $table->index('sha256', 'idx_sacrament_attachments_sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sacrament_attachments');
        Schema::dropIfExists('baptism_sponsors');

        Schema::table('baptisms', function (Blueprint $table) {
            if (Schema::hasColumn('baptisms', 'family_id')) {
                $table->dropForeign(['family_id']);
                $table->dropColumn('family_id');
            }
        });
    }
};
