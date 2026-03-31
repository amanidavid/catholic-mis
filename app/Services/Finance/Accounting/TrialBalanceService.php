<?php

namespace App\Services\Finance\Accounting;

use App\Traits\FormatsAmounts;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class TrialBalanceService
{
    use FormatsAmounts;

    /**
     * @return array{rows: CursorPaginator, totals: array{debit:string, credit:string}}
     */
    public function getReport(string $asAt, int $perPage = 50): array
    {
        $baseQuery = $this->baseQuery($asAt);

        $rows = (clone $baseQuery)
            ->orderBy('ledger_name')
            ->orderBy('ledger_id')
            ->cursorPaginate($perPage, ['*'], 'cursor')
            ->withQueryString();

        $totals = DB::query()
            ->fromSub($baseQuery, 'trial_balance_rows')
            ->selectRaw('
                COALESCE(SUM(debit_balance), 0) as total_debit,
                COALESCE(SUM(credit_balance), 0) as total_credit
            ')
            ->first();

        return [
            'rows' => $rows,
            'totals' => [
                'debit' => self::normalizeAmount($totals?->total_debit ?? 0, 4),
                'credit' => self::normalizeAmount($totals?->total_credit ?? 0, 4),
            ],
        ];
    }

    private function baseQuery(string $asAt)
    {
        $ledgerSums = DB::table('general_ledgers')
            ->selectRaw('
                ledger_id,
                COALESCE(SUM(debit_amount), 0) as total_debit_posted,
                COALESCE(SUM(credit_amount), 0) as total_credit_posted
            ')
            ->where('transaction_date', '<=', $asAt)
            ->groupBy('ledger_id');

        return DB::table('ledgers')
            ->join('account_subtypes', 'account_subtypes.id', '=', 'ledgers.account_subtype_id')
            ->join('account_types', 'account_types.id', '=', 'account_subtypes.account_type_id')
            ->join('account_groups', 'account_groups.id', '=', 'account_types.account_group_id')
            ->leftJoinSub($ledgerSums, 'ledger_sums', function ($join) {
                $join->on('ledger_sums.ledger_id', '=', 'ledgers.id');
            })
            ->where('ledgers.is_active', true)
            ->selectRaw("
                ledgers.id as ledger_id,
                ledgers.uuid as ledger_uuid,
                ledgers.name as ledger_name,
                account_groups.code as account_group_code,
                CASE account_groups.code
                    WHEN 1 THEN 'Asset'
                    WHEN 2 THEN 'Expense'
                    WHEN 3 THEN 'Revenue'
                    WHEN 4 THEN 'Liability'
                    WHEN 5 THEN 'Capital'
                    ELSE 'Other'
                END as natural_class,
                CASE
                    WHEN account_groups.code IN (1, 2) THEN 'Debit'
                    WHEN account_groups.code IN (3, 4, 5) THEN 'Credit'
                    ELSE CASE
                        WHEN (
                            CASE
                                WHEN ledgers.opening_balance_type = 'credit' THEN -COALESCE(ledgers.opening_balance, 0)
                                ELSE COALESCE(ledgers.opening_balance, 0)
                            END
                            + COALESCE(ledger_sums.total_debit_posted, 0)
                            - COALESCE(ledger_sums.total_credit_posted, 0)
                        ) >= 0 THEN 'Debit'
                        ELSE 'Credit'
                    END
                END as natural_balance_side,
                COALESCE(ledger_sums.total_debit_posted, 0) as total_debit_posted,
                COALESCE(ledger_sums.total_credit_posted, 0) as total_credit_posted,
                (
                    CASE
                        WHEN ledgers.opening_balance_type = 'credit' THEN -COALESCE(ledgers.opening_balance, 0)
                        ELSE COALESCE(ledgers.opening_balance, 0)
                    END
                    + COALESCE(ledger_sums.total_debit_posted, 0)
                    - COALESCE(ledger_sums.total_credit_posted, 0)
                ) as closing_signed,
                CASE
                    WHEN (
                        CASE
                            WHEN ledgers.opening_balance_type = 'credit' THEN -COALESCE(ledgers.opening_balance, 0)
                            ELSE COALESCE(ledgers.opening_balance, 0)
                        END
                        + COALESCE(ledger_sums.total_debit_posted, 0)
                        - COALESCE(ledger_sums.total_credit_posted, 0)
                    ) > 0
                    THEN (
                        CASE
                            WHEN ledgers.opening_balance_type = 'credit' THEN -COALESCE(ledgers.opening_balance, 0)
                            ELSE COALESCE(ledgers.opening_balance, 0)
                        END
                        + COALESCE(ledger_sums.total_debit_posted, 0)
                        - COALESCE(ledger_sums.total_credit_posted, 0)
                    )
                    ELSE 0
                END as debit_balance,
                CASE
                    WHEN (
                        CASE
                            WHEN ledgers.opening_balance_type = 'credit' THEN -COALESCE(ledgers.opening_balance, 0)
                            ELSE COALESCE(ledgers.opening_balance, 0)
                        END
                        + COALESCE(ledger_sums.total_debit_posted, 0)
                        - COALESCE(ledger_sums.total_credit_posted, 0)
                    ) < 0
                    THEN ABS(
                        CASE
                            WHEN ledgers.opening_balance_type = 'credit' THEN -COALESCE(ledgers.opening_balance, 0)
                            ELSE COALESCE(ledgers.opening_balance, 0)
                        END
                        + COALESCE(ledger_sums.total_debit_posted, 0)
                        - COALESCE(ledger_sums.total_credit_posted, 0)
                    )
                    ELSE 0
                END as credit_balance
            ")
            ->havingRaw('ROUND(closing_signed, 4) <> 0');
    }
}
