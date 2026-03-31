<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_cash_vouchers', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('rejected_at')->constrained('users', 'id')->onDelete('set null');
            $table->string('rejection_reason', 200)->nullable()->after('rejected_by');
            $table->timestamp('cancelled_at')->nullable()->after('rejection_reason');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users', 'id')->onDelete('set null');
            $table->string('cancellation_reason', 200)->nullable()->after('cancelled_by');
            $table->index('rejected_by', 'idx_petty_cash_vouchers_rejected_by');
            $table->index('cancelled_by', 'idx_petty_cash_vouchers_cancelled_by');
        });

        Schema::create('petty_cash_replenishments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('petty_cash_replenishments_uuid_unique');
            $table->string('replenishment_no', 30)->unique('petty_cash_replenishments_no_unique');
            $table->foreignId('petty_cash_fund_id')->constrained('petty_cash_funds', 'id')->onDelete('restrict');
            $table->date('transaction_date');
            $table->foreignId('source_ledger_id')->constrained('ledgers', 'id')->onDelete('restrict');
            $table->string('reference_no', 100)->nullable();
            $table->string('description', 150)->nullable();
            $table->decimal('amount', 16, 4)->default(0.0000);
            $table->string('status', 20)->default('draft');
            $table->foreignId('journal_id')->nullable()->constrained('journals', 'id')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users', 'id')->onDelete('restrict');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->string('rejection_reason', 200)->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->string('cancellation_reason', 200)->nullable();
            $table->timestamps();

            $table->index(['petty_cash_fund_id', 'transaction_date', 'id'], 'idx_petty_cash_replenishments_fund_date_id');
            $table->index(['status', 'transaction_date'], 'idx_petty_cash_replenishments_status_date');
            $table->index('journal_id', 'idx_petty_cash_replenishments_journal');
            $table->index('source_ledger_id', 'idx_petty_cash_replenishments_source_ledger');
        });

        foreach ([
            'finance.petty-cash-vouchers.update',
            'finance.petty-cash-vouchers.cancel',
            'finance.petty-cash-replenishments.view',
            'finance.petty-cash-replenishments.create',
            'finance.petty-cash-replenishments.approve',
            'finance.petty-cash-replenishments.post',
            'finance.petty-cash-replenishments.cancel',
            'finance.petty-cash-book.view',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function down(): void
    {
        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'finance.petty-cash-vouchers.update',
                'finance.petty-cash-vouchers.cancel',
                'finance.petty-cash-replenishments.view',
                'finance.petty-cash-replenishments.create',
                'finance.petty-cash-replenishments.approve',
                'finance.petty-cash-replenishments.post',
                'finance.petty-cash-replenishments.cancel',
                'finance.petty-cash-book.view',
            ])
            ->delete();

        Schema::dropIfExists('petty_cash_replenishments');

        Schema::table('petty_cash_vouchers', function (Blueprint $table) {
            $table->dropIndex('idx_petty_cash_vouchers_rejected_by');
            $table->dropIndex('idx_petty_cash_vouchers_cancelled_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn([
                'rejected_at',
                'rejection_reason',
                'cancelled_at',
                'cancellation_reason',
            ]);
        });
    }
};
