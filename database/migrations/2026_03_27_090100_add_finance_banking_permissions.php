<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $perms = [
            'finance.banks.view',
            'finance.banks.create',
            'finance.banks.update',
            'finance.banks.delete',
            'finance.bank-accounts.view',
            'finance.bank-accounts.create',
            'finance.bank-accounts.update',
            'finance.bank-accounts.delete',
            'finance.bank-account-transactions.view',
            'finance.bank-account-transactions.create',
            'finance.bank-account-transactions.update',
            'finance.bank-account-transactions.delete',
        ];

        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }
    }

    public function down(): void
    {
        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'finance.banks.view',
                'finance.banks.create',
                'finance.banks.update',
                'finance.banks.delete',
                'finance.bank-accounts.view',
                'finance.bank-accounts.create',
                'finance.bank-accounts.update',
                'finance.bank-accounts.delete',
                'finance.bank-account-transactions.view',
                'finance.bank-account-transactions.create',
                'finance.bank-account-transactions.update',
                'finance.bank-account-transactions.delete',
            ])
            ->delete();
    }
};
