<?php

namespace App\Support\Finance;

class BankTransactionTypes
{
    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            'customer-receipt',
            'capital-injection',
            'bank-charge',
            'supplier-payment',
            'manual',
            'deposit',
            'withdrawal',
            'adjustment',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'customer-receipt' => 'Customer Receipt',
            'capital-injection' => 'Capital Injection',
            'bank-charge' => 'Bank Charge',
            'supplier-payment' => 'Supplier Payment',
            'manual' => 'Manual',
            'deposit' => 'Deposit',
            'withdrawal' => 'Withdrawal',
            'adjustment' => 'Adjustment',
        ];
    }
}
