<?php

namespace App\Services\Finance\Banking;

use App\Models\Finance\BankAccount;
use App\Models\Finance\BankAccountTransaction;
use App\Models\Finance\DoubleEntry;
use App\Models\Finance\Journal;
use App\Models\Finance\JournalLine;
use App\Models\Finance\Ledger;
use App\Services\Finance\Accounting\JournalNumberService;
use App\Services\Finance\Accounting\JournalPostingService;
use App\Traits\FormatsAmounts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BankTransactionPostingService
{
    use FormatsAmounts;

    public function __construct(
        private readonly JournalNumberService $journalNumberService,
        private readonly JournalPostingService $journalPostingService,
    ) {
    }

    public function createAndPost(BankAccount $bankAccount, array $validated, int $userId): BankAccountTransaction
    {
        return DB::transaction(function () use ($bankAccount, $validated, $userId): BankAccountTransaction {
            $bankLedgerId = (int) $bankAccount->ledger_id;
            $mapping = DoubleEntry::query()
                ->where('ledger_id', $bankLedgerId)
                ->where('transaction_type', (string) $validated['transaction_type'])
                ->select(['id', 'uuid', 'debit_ledger_id', 'credit_ledger_id'])
                ->first();

            $defaultDebitLedgerId = $mapping ? (int) $mapping->debit_ledger_id : null;
            $defaultCreditLedgerId = $mapping ? (int) $mapping->credit_ledger_id : null;

            $debitLedgerId = $this->resolveLedgerId((string) $validated['debit_ledger_uuid']);
            $creditLedgerId = $this->resolveLedgerId((string) $validated['credit_ledger_uuid']);

            if (! $debitLedgerId || ! $creditLedgerId) {
                throw new \RuntimeException('The selected debit or credit ledger is invalid.');
            }

            if ($debitLedgerId === $creditLedgerId) {
                throw new \RuntimeException('Debit ledger and credit ledger must be different.');
            }

            $direction = (string) $validated['direction'];
            if ($direction === 'inflow') {
                if ($debitLedgerId !== $bankLedgerId) {
                    throw new \RuntimeException('For an inflow, the bank ledger must be the debit ledger.');
                }
            } elseif ($direction === 'outflow') {
                if ($creditLedgerId !== $bankLedgerId) {
                    throw new \RuntimeException('For an outflow, the bank ledger must be the credit ledger.');
                }
            }

            if ($debitLedgerId !== $bankLedgerId && $creditLedgerId !== $bankLedgerId) {
                throw new \RuntimeException('The selected debit and credit ledgers must include the bank account ledger.');
            }

            $isManualOverride = $defaultDebitLedgerId !== $debitLedgerId || $defaultCreditLedgerId !== $creditLedgerId;

            $transactionDate = (string) $validated['transaction_date'];
            $year = (int) date('Y', strtotime($transactionDate));
            $amount = self::normalizeAmount($validated['amount'], 4);
            $description = isset($validated['description']) ? trim((string) $validated['description']) : '';
            $description = $description !== '' ? $description : null;

            $no = $this->journalNumberService->nextForYear($year);

            $journal = new Journal();
            $journal->uuid = method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid();
            $journal->journal_no = $no['journal_no'];
            $journal->sequence = $no['sequence'];
            $journal->journal_year = $no['journal_year'];
            $journal->transaction_date = $transactionDate;
            $journal->description = $description;
            $journal->amount = self::normalizeAmount(0, 4);
            $journal->is_posted = false;
            $journal->created_by = $userId;
            $journal->save();

            $journalLineRows = [
                [
                    'uuid' => method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid(),
                    'journal_id' => (int) $journal->id,
                    'ledger_id' => $debitLedgerId,
                    'description' => $description,
                    'debit_amount' => $amount,
                    'credit_amount' => self::normalizeAmount(0, 4),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'uuid' => method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid(),
                    'journal_id' => (int) $journal->id,
                    'ledger_id' => $creditLedgerId,
                    'description' => $description,
                    'debit_amount' => self::normalizeAmount(0, 4),
                    'credit_amount' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            JournalLine::query()->insert($journalLineRows);
            $journal->logCustomAudit('journal_lines_created', null, [
                'journal_uuid' => $journal->uuid,
                'journal_no' => $journal->journal_no,
                'line_count' => count($journalLineRows),
                'lines' => $this->auditJournalLinesPayload($journalLineRows),
            ], "Created journal lines for {$journal->journal_no}");

            $this->journalPostingService->post($journal, $userId);

            $item = new BankAccountTransaction();
            $item->bank_account_id = (int) $bankAccount->id;
            $item->double_entry_id = $mapping ? (int) $mapping->id : null;
            $item->debit_ledger_id = $debitLedgerId;
            $item->credit_ledger_id = $creditLedgerId;
            $item->transaction_date = $transactionDate;
            $item->transaction_type = (string) $validated['transaction_type'];
            $item->direction = $direction;
            $item->amount = $amount;
            $item->reference_no = isset($validated['reference_no']) ? trim((string) $validated['reference_no']) ?: null : null;
            $item->description = $description;
            $item->journal_id = (int) $journal->id;
            $item->is_manual_override = $isManualOverride;
            $item->created_by = $userId;
            $item->save();
            $item->logCustomAudit('bank_transaction_posted', null, [
                'bank_account_id' => (int) $bankAccount->id,
                'bank_account_transaction_uuid' => $item->uuid,
                'journal_uuid' => $journal->uuid,
                'journal_no' => $journal->journal_no,
                'amount' => $amount,
                'direction' => $direction,
                'transaction_type' => (string) $validated['transaction_type'],
                'is_manual_override' => $isManualOverride,
            ], 'Created and posted bank account transaction');

            return $item;
        }, 3);
    }

    private function auditJournalLinesPayload(array $rows): array
    {
        return array_map(fn (array $row) => [
            'uuid' => $row['uuid'] ?? null,
            'ledger_id' => $row['ledger_id'] ?? null,
            'description' => $row['description'] ?? null,
            'debit_amount' => $row['debit_amount'] ?? null,
            'credit_amount' => $row['credit_amount'] ?? null,
        ], $rows);
    }

    private function resolveLedgerId(string $uuid): ?int
    {
        $trimmed = trim($uuid);

        if ($trimmed === '') {
            return null;
        }

        return Ledger::query()->where('uuid', $trimmed)->value('id');
    }
}
