<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sacrament_attachments', function (Blueprint $table) {
            if (! Schema::hasColumn('sacrament_attachments', 'parish_id')) {
                return;
            }

            $table->index(['parish_id', 'entity_type', 'entity_id'], 'idx_sacrament_attachments_parish_entity');
            $table->index(['parish_id', 'entity_type', 'type'], 'idx_sacrament_attachments_parish_type');
        });

        Schema::table('marriages', function (Blueprint $table) {
            if (! Schema::hasColumn('marriages', 'parish_id')) {
                return;
            }

            $table->index(['parish_id', 'origin_jumuiya_id', 'status', 'created_at'], 'idx_marriages_parish_jumuiya_status_created');
            $table->index(['parish_id', 'submitted_at'], 'idx_marriages_parish_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('marriages', function (Blueprint $table) {
            $table->dropIndex('idx_marriages_parish_jumuiya_status_created');
            $table->dropIndex('idx_marriages_parish_submitted_at');
        });

        Schema::table('sacrament_attachments', function (Blueprint $table) {
            $table->dropIndex('idx_sacrament_attachments_parish_entity');
            $table->dropIndex('idx_sacrament_attachments_parish_type');
        });
    }
};
