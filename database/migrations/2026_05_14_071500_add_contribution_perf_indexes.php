<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contribution_rules', function (Blueprint $table) {
            // Ordering and filtering
            $table->index('created_at', 'idx_contribution_rules_created_at');
            $table->index(['created_at', 'id'], 'idx_contribution_rules_created_id');
            $table->index(['is_active', 'created_at', 'id'], 'idx_contribution_rules_active_created_id');
            $table->index(['contribution_catalog_id', 'created_at', 'id'], 'idx_contribution_rules_catalog_created_id');
        });

        Schema::table('contribution_payment_requests', function (Blueprint $table) {
            $table->index('created_at', 'idx_contribution_payment_requests_created_at');
        });

        Schema::table('contribution_transactions', function (Blueprint $table) {
            $table->index('created_at', 'idx_contribution_transactions_created_at');
            $table->index(['created_at', 'id'], 'idx_contribution_transactions_created_id');
        });
    }

    public function down(): void
    {
        Schema::table('contribution_rules', function (Blueprint $table) {
            $table->dropIndex('idx_contribution_rules_created_at');
            $table->dropIndex('idx_contribution_rules_created_id');
            $table->dropIndex('idx_contribution_rules_active_created_id');
            $table->dropIndex('idx_contribution_rules_catalog_created_id');
        });

        Schema::table('contribution_payment_requests', function (Blueprint $table) {
            $table->dropIndex('idx_contribution_payment_requests_created_at');
        });

        Schema::table('contribution_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_contribution_transactions_created_at');
            $table->dropIndex('idx_contribution_transactions_created_id');
        });
    }
};
