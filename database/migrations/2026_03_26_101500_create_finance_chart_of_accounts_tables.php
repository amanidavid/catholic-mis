<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('account_groups_uuid_unique');
            $table->string('name', 30);
            $table->unsignedTinyInteger('code')->nullable()->comment('1=Assets, 2=Expenses, 3=Revenue, 4=Liabilities, 5=Capital');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name', 'account_groups_name_unique');
            $table->index('name', 'idx_account_groups_name');
            $table->index('code', 'idx_account_groups_code');
        });

        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('account_types_uuid_unique');
            $table->foreignId('account_group_id')
                ->constrained('account_groups', 'id')
                ->onDelete('restrict');
            $table->string('name', 60);
            $table->foreignId('created_by')
                ->constrained('users', 'id')
                ->onDelete('restrict');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['account_group_id', 'name'], 'uq_account_types_group_name');
            $table->index('account_group_id', 'idx_account_types_group');
            $table->index('name', 'idx_account_types_name');
            $table->index(['account_group_id', 'is_active'], 'idx_account_types_group_active');
        });

        Schema::create('account_subtypes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('account_subtypes_uuid_unique');
            $table->string('name', 60);
            $table->foreignId('account_type_id')
                ->constrained('account_types', 'id')
                ->onDelete('restrict');
            $table->foreignId('created_by')
                ->constrained('users', 'id')
                ->onDelete('restrict');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['account_type_id', 'name'], 'uq_account_subtypes_type_name');
            $table->index('created_by', 'account_subtypes_created_by_foreign');
            $table->index('account_type_id', 'idx_account_subtypes_type');
            $table->index('name', 'idx_account_subtypes_name');
            $table->index(['account_type_id', 'is_active'], 'idx_account_subtypes_type_active');
        });

        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('ledgers_uuid_unique');
            $table->string('name', 80);
            $table->string('account_code', 50)->nullable()->comment('Format: 1-1-1-001');
            $table->foreignId('account_subtype_id')
                ->constrained('account_subtypes', 'id')
                ->onDelete('restrict');

            $table->unsignedBigInteger('currency_id')->nullable();

            $table->decimal('opening_balance', 16, 4)->default(0.0000);
            $table->enum('opening_balance_type', ['debit', 'credit'])->default('debit');

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->constrained('users', 'id')
                ->onDelete('restrict');

            $table->timestamps();

            $table->unique('account_code', 'ledgers_account_code_unique');
            $table->index('created_by', 'ledgers_created_by_foreign');
            $table->index('account_subtype_id', 'idx_ledgers_subtype');
            $table->index('account_code', 'idx_ledgers_code');
            $table->index('name', 'idx_ledgers_name');
            $table->index(['account_subtype_id', 'is_active'], 'idx_ledgers_subtype_active');
            $table->index('currency_id', 'idx_ledgers_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
        Schema::dropIfExists('account_subtypes');
        Schema::dropIfExists('account_types');
        Schema::dropIfExists('account_groups');
    }
};
