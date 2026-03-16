<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sacrament_attachments', function (Blueprint $table) {
            $table->unique(['entity_type', 'entity_id', 'type'], 'uq_sacrament_attachments_entity_type_once');
        });
    }

    public function down(): void
    {
        Schema::table('sacrament_attachments', function (Blueprint $table) {
            $table->dropUnique('uq_sacrament_attachments_entity_type_once');
        });
    }
};
