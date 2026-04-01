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

            $totals = JournalLine::query()
                ->where('journal_id', $journal->id)
                ->selectRaw('COUNT(*) as total_lines, COALESCE(SUM(debit_amount), 0) as total_debit, COALESCE(SUM(credit_amount), 0) as total_credit')
                ->first();

            if ((int) ($totals?->total_lines ?? 0) === 0) {
                throw new \RuntimeException('Journal has no lines.');
            }

            $totalDebit = (float) self::normalizeAmount($totals?->total_debit ?? 0, 4);
            $totalCredit = (float) self::normalizeAmount($totals?->total_credit ?? 0, 4);

            if (round($totalDebit - $totalCredit, 4) !== 0.0) {
                throw new \RuntimeException('Journal is not balanced. Total debit must equal total credit.');
            }

            $journalLines = JournalLine::query()
                ->where('journal_id', $journal->id)
                ->select(['id', 'ledger_id', 'description', 'debit_amount', 'credit_amount'])
                ->orderBy('id')
                ->cursor();

            $now = now();
            $uuidMethod = method_exists(Str::class, 'uuid7') ? 'uuid7' : 'uuid';
            $rows = [];

            foreach ($journalLines as $line) {
                $debit = (float) self::normalizeAmount($line->debit_amount, 4);
                $credit = (float) self::normalizeAmount($line->credit_amount, 4);

                if ($debit <= 0 && $credit <= 0) {
                    throw new \RuntimeException('Each journal line must have a debit or credit amount.');
                }

                if ($debit > 0 && $credit > 0) {
                    throw new \RuntimeException('A journal line cannot have both debit and credit.');
                }

                $rows[] = [
                    'uuid' => (string) Str::{$uuidMethod}(),
                    'journal_id' => (int) $journal->id,
                    'ledger_id' => (int) $line->ledger_id,
                    'description' => $line->description,
                    'debit_amount' => number_format($debit, 4, '.', ''),
                    'credit_amount' => number_format($credit, 4, '.', ''),
                    'transaction_date' => $journal->transaction_date,
                    'created_by' => (int) $journal->created_by,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $journal->amount = number_format($totalDebit, 4, '.', '');
            $journal->is_posted = true;
            $journal->posted_at = now();
            $journal->posted_by = $postedByUserId;
            $journal->save();

            if (count($rows) > 0) {
                GeneralLedger::query()->insert($rows);
                $journal->logCustomAudit('general_ledger_entries_created', null, [
                    'journal_uuid' => $journal->uuid,
                    'journal_no' => $journal->journal_no,
                    'entry_count' => count($rows),
                    'entries' => $this->auditGeneralLedgerRows($rows),
                ], "Created general ledger entries for {$journal->journal_no}");
            }

            $journal->logCustomAudit('journal_posted', null, [
                'journal_uuid' => $journal->uuid,
                'journal_no' => $journal->journal_no,
                'posted_by' => $postedByUserId,
                'amount' => number_format($totalDebit, 4, '.', ''),
                'entry_count' => count($rows),
            ], "Posted journal {$journal->journal_no}");
        }, 3);
    }

    private function auditGeneralLedgerRows(array $rows): array
    {
        return array_map(fn (array $row) => [
            'uuid' => $row['uuid'] ?? null,
            'ledger_id' => $row['ledger_id'] ?? null,
            'description' => $row['description'] ?? null,
            'debit_amount' => $row['debit_amount'] ?? null,
            'credit_amount' => $row['credit_amount'] ?? null,
            'transaction_date' => $row['transaction_date'] ?? null,
            'created_by' => $row['created_by'] ?? null,
        ], $rows);
    }
}
