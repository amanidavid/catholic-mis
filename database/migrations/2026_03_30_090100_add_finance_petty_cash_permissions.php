<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'finance.petty-cash-funds.view',
            'finance.petty-cash-funds.create',
            'finance.petty-cash-funds.update',
            'finance.petty-cash-vouchers.view',
            'finance.petty-cash-vouchers.create',
            'finance.petty-cash-vouchers.approve',
            'finance.petty-cash-vouchers.post',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function down(): void
    {
        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'finance.petty-cash-funds.view',
                'finance.petty-cash-funds.create',
                'finance.petty-cash-funds.update',
                'finance.petty-cash-vouchers.view',
                'finance.petty-cash-vouchers.create',
                'finance.petty-cash-vouchers.approve',
                'finance.petty-cash-vouchers.post',
            ])
            ->delete();
    }
};
