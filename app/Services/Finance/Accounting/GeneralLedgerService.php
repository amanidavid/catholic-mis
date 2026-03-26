<?php

namespace App\Services\Finance\Accounting;

use App\Models\Finance\GeneralLedger;
use App\Models\Finance\Ledger;
use App\Traits\FormatsAmounts;
use Illuminate\Pagination\LengthAwarePaginator;

class GeneralLedgerService
{
    use FormatsAmounts;

    /**
     * @return array{opening_balance:string, opening_balance_signed:string, entries:LengthAwarePaginator}
     */
    public function getLedgerReport(Ledger $ledger, string $dateFrom, string $dateTo, int $perPage = 15): array
    {
        $openingSigned = $this->computeOpeningCarryForward($ledger, $dateFrom);

        $entries = GeneralLedger::query()
            ->with(['journal:id,uuid,journal_no'])
            ->where('ledger_id', $ledger->id)
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'opening_balance' => self::normalizeAmount($openingSigned, 4),
            'opening_balance_signed' => self::normalizeAmount($openingSigned, 4),
            'entries' => $entries,
        ];
    }

    public function computeOpeningCarryForward(Ledger $ledger, string $dateFrom): string
    {
        $ledgerOpening = (float) self::signedOpeningBalance($ledger->opening_balance, $ledger->opening_balance_type, 4);

        $beforeSum = (float) GeneralLedger::query()
            ->where('ledger_id', $ledger->id)
            ->where('transaction_date', '<', $dateFrom)
            ->selectRaw('COALESCE(SUM(debit_amount - credit_amount), 0) as bal')
            ->value('bal');

        return number_format($ledgerOpening + $beforeSum, 4, '.', '');
    }
}
