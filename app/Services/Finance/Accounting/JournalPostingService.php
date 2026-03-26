<?php

namespace App\Services\Finance\Accounting;

use App\Models\Finance\GeneralLedger;
use App\Models\Finance\Journal;
use App\Models\Finance\JournalLine;
use App\Traits\FormatsAmounts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JournalPostingService
{
    use FormatsAmounts;

    public function post(Journal $journal, int $postedByUserId): void
    {
        if ($journal->is_posted) {
            throw new \RuntimeException('Journal is already posted.');
        }

        DB::transaction(function () use ($journal, $postedByUserId): void {
            $journal = Journal::query()->where('id', $journal->id)->lockForUpdate()->firstOrFail();

            if ($journal->is_posted) {
                throw new \RuntimeException('Journal is already posted.');
            }

            $lines = JournalLine::query()
                ->where('journal_id', $journal->id)
                ->select(['id', 'ledger_id', 'description', 'debit_amount', 'credit_amount'])
                ->orderBy('id')
                ->get();

            if ($lines->count() === 0) {
                throw new \RuntimeException('Journal has no lines.');
            }

            $totalDebit = 0.0;
            $totalCredit = 0.0;

            foreach ($lines as $line) {
                $debit = (float) self::normalizeAmount($line->debit_amount, 4);
                $credit = (float) self::normalizeAmount($line->credit_amount, 4);

                if ($debit <= 0 && $credit <= 0) {
                    throw new \RuntimeException('Each journal line must have a debit or credit amount.');
                }

                if ($debit > 0 && $credit > 0) {
                    throw new \RuntimeException('A journal line cannot have both debit and credit.');
                }

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            if (round($totalDebit - $totalCredit, 4) !== 0.0) {
                throw new \RuntimeException('Journal is not balanced. Total debit must equal total credit.');
            }

            $journal->amount = number_format($totalDebit, 4, '.', '');
            $journal->is_posted = true;
            $journal->posted_at = now();
            $journal->posted_by = $postedByUserId;
            $journal->save();

            $rows = [];
            foreach ($lines as $line) {
                $rows[] = [
                    'uuid' => (string) (method_exists(Str::class, 'uuid7') ? Str::uuid7() : Str::uuid()),
                    'journal_id' => (int) $journal->id,
                    'ledger_id' => (int) $line->ledger_id,
                    'description' => $line->description,
                    'debit_amount' => number_format((float) self::normalizeAmount($line->debit_amount, 4), 4, '.', ''),
                    'credit_amount' => number_format((float) self::normalizeAmount($line->credit_amount, 4), 4, '.', ''),
                    'transaction_date' => $journal->transaction_date,
                    'created_by' => (int) $journal->created_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (count($rows) > 0) {
                GeneralLedger::query()->insert($rows);
            }
        }, 3);
    }
}
