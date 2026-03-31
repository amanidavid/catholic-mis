<?php

namespace App\Services\Finance\Accounting;

use App\Models\Finance\GeneralLedger;
use App\Models\Finance\Journal;
use App\Models\Finance\JournalLine;
use App\Models\Finance\Ledger;
use App\Models\Finance\PettyCashFund;
use App\Models\Finance\PettyCashVoucherAttachment;
use App\Models\Finance\PettyCashReplenishment;
use App\Models\Finance\PettyCashVoucher;
use App\Models\Finance\PettyCashVoucherLine;
use App\Traits\FormatsAmounts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PettyCashService
{
    use FormatsAmounts;

    public function createVoucher(PettyCashFund $fund, array $validated, int $userId, array $attachments = []): PettyCashVoucher
    {
        return DB::transaction(function () use ($fund, $validated, $userId, $attachments): PettyCashVoucher {
            $voucher = new PettyCashVoucher();
            $voucher->voucher_no = $this->nextVoucherNumber();
            $voucher->petty_cash_fund_id = (int) $fund->id;
            $voucher->transaction_date = (string) $validated['transaction_date'];
            $voucher->payee_name = $validated['payee_name'] ?? null;
            $voucher->reference_no = $validated['reference_no'] ?? null;
            $voucher->description = $validated['description'] ?? null;
            $voucher->status = 'draft';
            $voucher->created_by = $userId;
            $voucher->amount = self::normalizeAmount(0, 2);
            $voucher->save();

            $lines = $this->buildVoucherLines($voucher, $validated['lines'] ?? []);
            PettyCashVoucherLine::query()->insert($lines['rows']);

            $voucher->amount = $lines['total'];
            $voucher->save();

            if (! empty($attachments)) {
                PettyCashVoucherAttachment::query()->insert(
                    collect($attachments)
                        ->map(fn ($attachment) => [
                            'uuid' => (string) (method_exists(Str::class, 'uuid7') ? Str::uuid7() : Str::uuid()),
                            'petty_cash_voucher_id' => (int) $voucher->id,
                            'original_name' => (string) ($attachment['original_name'] ?? ''),
                            'mime_type' => $attachment['mime_type'] ?? null,
                            'size_bytes' => (int) ($attachment['size_bytes'] ?? 0),
                            'storage_disk' => (string) ($attachment['storage_disk'] ?? 'local'),
                            'storage_path' => (string) ($attachment['storage_path'] ?? ''),
                            'sha256' => $attachment['sha256'] ?? null,
                            'uploaded_by_user_id' => $userId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                        ->values()
                        ->all()
                );
            }

            return $voucher->fresh(['fund', 'lines.expenseLedger', 'attachments']);
        }, 3);
    }

    public function updateDraftVoucher(PettyCashVoucher $voucher, array $validated, array $attachments = []): PettyCashVoucher
    {
        return DB::transaction(function () use ($voucher, $validated, $attachments): PettyCashVoucher {
            $voucher = PettyCashVoucher::query()->where('id', $voucher->id)->lockForUpdate()->firstOrFail();

            if ($voucher->status !== 'draft') {
                throw new \RuntimeException('Only draft vouchers can be edited.');
            }

            $fund = PettyCashFund::query()->where('uuid', (string) $validated['petty_cash_fund_uuid'])->first();
            if (! $fund) {
                throw new \RuntimeException('Invalid petty cash fund.');
            }

            $voucher->petty_cash_fund_id = (int) $fund->id;
            $voucher->transaction_date = (string) $validated['transaction_date'];
            $voucher->payee_name = $validated['payee_name'] ?? null;
            $voucher->reference_no = $validated['reference_no'] ?? null;
            $voucher->description = $validated['description'] ?? null;
            $voucher->amount = self::normalizeAmount(0, 4);
            $voucher->save();

            PettyCashVoucherLine::query()->where('petty_cash_voucher_id', $voucher->id)->delete();
            $lines = $this->buildVoucherLines($voucher, $validated['lines'] ?? []);
            PettyCashVoucherLine::query()->insert($lines['rows']);

            $voucher->amount = $lines['total'];
            $voucher->save();

            if (! empty($attachments)) {
                PettyCashVoucherAttachment::query()->insert(
                    collect($attachments)
                        ->map(fn ($attachment) => [
                            'uuid' => (string) (method_exists(Str::class, 'uuid7') ? Str::uuid7() : Str::uuid()),
                            'petty_cash_voucher_id' => (int) $voucher->id,
                            'original_name' => (string) ($attachment['original_name'] ?? ''),
                            'mime_type' => $attachment['mime_type'] ?? null,
                            'size_bytes' => (int) ($attachment['size_bytes'] ?? 0),
                            'storage_disk' => (string) ($attachment['storage_disk'] ?? 'local'),
                            'storage_path' => (string) ($attachment['storage_path'] ?? ''),
                            'sha256' => $attachment['sha256'] ?? null,
                            'uploaded_by_user_id' => (int) ($voucher->created_by ?? 0),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                        ->values()
                        ->all()
                );
            }

            return $voucher->fresh(['fund', 'lines.expenseLedger', 'attachments']);
        }, 3);
    }

    public function submitVoucher(PettyCashVoucher $voucher, int $userId): void
    {
        DB::transaction(function () use ($voucher, $userId): void {
            $voucher = PettyCashVoucher::query()->where('id', $voucher->id)->lockForUpdate()->firstOrFail();

            if ($voucher->status !== 'draft') {
                throw new \RuntimeException('Only draft vouchers can be submitted.');
            }

            $hasLines = PettyCashVoucherLine::query()->where('petty_cash_voucher_id', $voucher->id)->exists();
            if (! $hasLines) {
                throw new \RuntimeException('Voucher must have at least one expense line before submission.');
            }

            $voucher->status = 'submitted';
            $voucher->submitted_at = now();
            $voucher->submitted_by = $userId;
            $voucher->save();
        }, 3);
    }

    public function approveVoucher(PettyCashVoucher $voucher, int $userId): void
    {
        DB::transaction(function () use ($voucher, $userId): void {
            $voucher = PettyCashVoucher::query()->where('id', $voucher->id)->lockForUpdate()->firstOrFail();

            if ($voucher->status !== 'submitted') {
                throw new \RuntimeException('Only submitted vouchers can be approved.');
            }

            $voucher->status = 'approved';
            $voucher->approved_at = now();
            $voucher->approved_by = $userId;
            $voucher->save();
        }, 3);
    }

    public function rejectVoucher(PettyCashVoucher $voucher, int $userId, ?string $reason = null): void
    {
        DB::transaction(function () use ($voucher, $userId, $reason): void {
            $voucher = PettyCashVoucher::query()->where('id', $voucher->id)->lockForUpdate()->firstOrFail();

            if ($voucher->status !== 'submitted') {
                throw new \RuntimeException('Only submitted vouchers can be rejected.');
            }

            $voucher->status = 'draft';
            $voucher->rejected_at = now();
            $voucher->rejected_by = $userId;
            $voucher->rejection_reason = $reason ?: 'Returned to draft for correction.';
            $voucher->approved_at = null;
            $voucher->approved_by = null;
            $voucher->save();
        }, 3);
    }

    public function postVoucher(PettyCashVoucher $voucher, int $userId, JournalNumberService $journalNumberService, JournalPostingService $journalPostingService): void
    {
        DB::transaction(function () use ($voucher, $userId, $journalNumberService, $journalPostingService): void {
            $voucher = PettyCashVoucher::query()
                ->with(['fund:id,ledger_id', 'lines:id,petty_cash_voucher_id,expense_ledger_id,description,amount'])
                ->where('id', $voucher->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($voucher->status !== 'approved') {
                throw new \RuntimeException('Only approved vouchers can be posted.');
            }

            if ($voucher->journal_id) {
                throw new \RuntimeException('Voucher is already linked to a journal.');
            }

            $year = (int) date('Y', strtotime((string) $voucher->transaction_date));
            $number = $journalNumberService->nextForYear($year);

            $journal = new Journal();
            $journal->journal_no = $number['journal_no'];
            $journal->sequence = $number['sequence'];
            $journal->journal_year = $number['journal_year'];
            $journal->transaction_date = $voucher->transaction_date;
            $journal->description = $voucher->description ?: "Petty cash voucher {$voucher->voucher_no}";
            $journal->amount = self::normalizeAmount($voucher->amount, 4);
            $journal->is_posted = false;
            $journal->created_by = $voucher->created_by;
            $journal->save();

            $rows = [];
            foreach ($voucher->lines as $line) {
                $rows[] = [
                    'uuid' => (string) (method_exists(Str::class, 'uuid7') ? Str::uuid7() : Str::uuid()),
                    'journal_id' => (int) $journal->id,
                    'ledger_id' => (int) $line->expense_ledger_id,
                    'description' => $line->description ?: $voucher->description,
                    'comment' => $line->description ?: $voucher->description,
                    'debit_amount' => self::normalizeAmount($line->amount, 4),
                    'credit_amount' => self::normalizeAmount(0, 4),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $rows[] = [
                'uuid' => (string) (method_exists(Str::class, 'uuid7') ? Str::uuid7() : Str::uuid()),
                'journal_id' => (int) $journal->id,
                'ledger_id' => (int) $voucher->fund->ledger_id,
                'description' => $voucher->description ?: "Petty cash disbursement {$voucher->voucher_no}",
                'comment' => $voucher->description ?: "Petty cash disbursement {$voucher->voucher_no}",
                'debit_amount' => self::normalizeAmount(0, 4),
                'credit_amount' => self::normalizeAmount($voucher->amount, 4),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            JournalLine::query()->insert($rows);
            $journalPostingService->post($journal, $userId);

            $voucher->journal_id = (int) $journal->id;
            $voucher->status = 'posted';
            $voucher->posted_at = now();
            $voucher->posted_by = $userId;
            $voucher->save();
        }, 3);
    }

    public function cancelVoucher(PettyCashVoucher $voucher, int $userId, ?string $reason = null): void
    {
        DB::transaction(function () use ($voucher, $userId, $reason): void {
            $voucher = PettyCashVoucher::query()->where('id', $voucher->id)->lockForUpdate()->firstOrFail();

            if (in_array($voucher->status, ['posted', 'cancelled'], true)) {
                throw new \RuntimeException('This voucher can no longer be cancelled.');
            }

            $voucher->status = 'cancelled';
            $voucher->cancelled_at = now();
            $voucher->cancelled_by = $userId;
            $voucher->cancellation_reason = $reason ?: 'Cancelled by user.';
            $voucher->save();
        }, 3);
    }

    public function createReplenishment(PettyCashFund $fund, Ledger $sourceLedger, array $validated, int $userId): PettyCashReplenishment
    {
        return DB::transaction(function () use ($fund, $sourceLedger, $validated, $userId): PettyCashReplenishment {
            $item = new PettyCashReplenishment();
            $item->replenishment_no = $this->nextReplenishmentNumber();
            $item->petty_cash_fund_id = (int) $fund->id;
            $item->transaction_date = (string) $validated['transaction_date'];
            $item->source_ledger_id = (int) $sourceLedger->id;
            $item->reference_no = $validated['reference_no'] ?? null;
            $item->description = $validated['description'] ?? null;
            $item->amount = self::normalizeAmount($validated['amount'] ?? 0, 4);
            $item->status = 'draft';
            $item->created_by = $userId;
            $item->save();

            return $item->fresh(['fund', 'sourceLedger']);
        }, 3);
    }

    public function submitReplenishment(PettyCashReplenishment $item, int $userId): void
    {
        DB::transaction(function () use ($item, $userId): void {
            $item = PettyCashReplenishment::query()->where('id', $item->id)->lockForUpdate()->firstOrFail();

            if ($item->status !== 'draft') {
                throw new \RuntimeException('Only draft replenishments can be submitted.');
            }

            $item->status = 'submitted';
            $item->submitted_at = now();
            $item->submitted_by = $userId;
            $item->save();
        }, 3);
    }

    public function approveReplenishment(PettyCashReplenishment $item, int $userId): void
    {
        DB::transaction(function () use ($item, $userId): void {
            $item = PettyCashReplenishment::query()->where('id', $item->id)->lockForUpdate()->firstOrFail();

            if ($item->status !== 'submitted') {
                throw new \RuntimeException('Only submitted replenishments can be approved.');
            }

            $item->status = 'approved';
            $item->approved_at = now();
            $item->approved_by = $userId;
            $item->save();
        }, 3);
    }

    public function rejectReplenishment(PettyCashReplenishment $item, int $userId, ?string $reason = null): void
    {
        DB::transaction(function () use ($item, $userId, $reason): void {
            $item = PettyCashReplenishment::query()->where('id', $item->id)->lockForUpdate()->firstOrFail();

            if ($item->status !== 'submitted') {
                throw new \RuntimeException('Only submitted replenishments can be rejected.');
            }

            $item->status = 'draft';
            $item->rejected_at = now();
            $item->rejected_by = $userId;
            $item->rejection_reason = $reason ?: 'Returned to draft for correction.';
            $item->approved_at = null;
            $item->approved_by = null;
            $item->save();
        }, 3);
    }

    public function postReplenishment(PettyCashReplenishment $item, int $userId, JournalNumberService $journalNumberService, JournalPostingService $journalPostingService): void
    {
        DB::transaction(function () use ($item, $userId, $journalNumberService, $journalPostingService): void {
            $item = PettyCashReplenishment::query()
                ->with(['fund:id,ledger_id', 'sourceLedger:id,opening_balance,opening_balance_type'])
                ->where('id', $item->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($item->status !== 'approved') {
                throw new \RuntimeException('Only approved replenishments can be posted.');
            }

            if ($item->journal_id) {
                throw new \RuntimeException('Replenishment is already linked to a journal.');
            }

            $availableBalance = $this->ledgerAvailableBalance($item->sourceLedger);
            $replenishmentAmount = (float) self::normalizeAmount($item->amount, 4);
            if ($availableBalance < $replenishmentAmount) {
                throw new \RuntimeException('Insufficient available balance on the selected source ledger for this replenishment.');
            }

            $year = (int) date('Y', strtotime((string) $item->transaction_date));
            $number = $journalNumberService->nextForYear($year);

            $journal = new Journal();
            $journal->journal_no = $number['journal_no'];
            $journal->sequence = $number['sequence'];
            $journal->journal_year = $number['journal_year'];
            $journal->transaction_date = $item->transaction_date;
            $journal->description = $item->description ?: "Petty cash replenishment {$item->replenishment_no}";
            $journal->amount = self::normalizeAmount($item->amount, 4);
            $journal->is_posted = false;
            $journal->created_by = $item->created_by;
            $journal->save();

            JournalLine::query()->insert([
                [
                    'uuid' => (string) (method_exists(Str::class, 'uuid7') ? Str::uuid7() : Str::uuid()),
                    'journal_id' => (int) $journal->id,
                    'ledger_id' => (int) $item->fund->ledger_id,
                    'description' => $item->description ?: "Petty cash replenishment {$item->replenishment_no}",
                    'comment' => $item->description ?: "Petty cash replenishment {$item->replenishment_no}",
                    'debit_amount' => self::normalizeAmount($item->amount, 4),
                    'credit_amount' => self::normalizeAmount(0, 4),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'uuid' => (string) (method_exists(Str::class, 'uuid7') ? Str::uuid7() : Str::uuid()),
                    'journal_id' => (int) $journal->id,
                    'ledger_id' => (int) $item->source_ledger_id,
                    'description' => $item->description ?: "Petty cash replenishment {$item->replenishment_no}",
                    'comment' => $item->description ?: "Petty cash replenishment {$item->replenishment_no}",
                    'debit_amount' => self::normalizeAmount(0, 4),
                    'credit_amount' => self::normalizeAmount($item->amount, 4),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $journalPostingService->post($journal, $userId);

            $item->journal_id = (int) $journal->id;
            $item->status = 'posted';
            $item->posted_at = now();
            $item->posted_by = $userId;
            $item->save();
        }, 3);
    }

    public function cancelReplenishment(PettyCashReplenishment $item, int $userId, ?string $reason = null): void
    {
        DB::transaction(function () use ($item, $userId, $reason): void {
            $item = PettyCashReplenishment::query()->where('id', $item->id)->lockForUpdate()->firstOrFail();

            if (in_array($item->status, ['posted', 'cancelled'], true)) {
                throw new \RuntimeException('This replenishment can no longer be cancelled.');
            }

            $item->status = 'cancelled';
            $item->cancelled_at = now();
            $item->cancelled_by = $userId;
            $item->cancellation_reason = $reason ?: 'Cancelled by user.';
            $item->save();
        }, 3);
    }

    private function buildVoucherLines(PettyCashVoucher $voucher, array $lines): array
    {
        $ledgerUuids = collect($lines)->pluck('expense_ledger_uuid')->filter()->unique()->values();
        $ledgerMap = Ledger::query()
            ->join('account_subtypes', 'account_subtypes.id', '=', 'ledgers.account_subtype_id')
            ->join('account_types', 'account_types.id', '=', 'account_subtypes.account_type_id')
            ->join('account_groups', 'account_groups.id', '=', 'account_types.account_group_id')
            ->whereIn('ledgers.uuid', $ledgerUuids)
            ->where('ledgers.is_active', true)
            ->where(function ($expense) {
                $expense->where('account_groups.code', 2)
                    ->orWhere('account_groups.name_normalized', 'expense')
                    ->orWhereRaw('LOWER(account_groups.name) IN (?, ?)', ['expense', 'expenses']);
            })
            ->select(['ledgers.id', 'ledgers.uuid'])
            ->get()
            ->keyBy('uuid');

        $rows = [];
        $total = 0.0;

        foreach ($lines as $line) {
            $ledger = $ledgerMap->get((string) ($line['expense_ledger_uuid'] ?? ''));
            if (! $ledger) {
                throw new \RuntimeException('Invalid expense ledger. Select an active ledger from the Expense account group.');
            }

            $amount = (float) ($line['amount'] ?? 0);
            if ($amount <= 0) {
                throw new \RuntimeException('Voucher line amount must be greater than zero.');
            }

            $amountNorm = self::normalizeAmount($amount, 4);
            $total += (float) $amountNorm;

            $rows[] = [
                'uuid' => (string) (method_exists(Str::class, 'uuid7') ? Str::uuid7() : Str::uuid()),
                'petty_cash_voucher_id' => (int) $voucher->id,
                'expense_ledger_id' => (int) $ledger->id,
                'description' => $line['description'] ?? null,
                'amount' => $amountNorm,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return [
            'rows' => $rows,
            'total' => self::normalizeAmount($total, 4),
        ];
    }

    private function nextVoucherNumber(): string
    {
        $year = now()->year;
        $prefix = "PCV-{$year}-";
        $latest = PettyCashVoucher::query()->where('voucher_no', 'like', $prefix . '%')->orderByDesc('id')->value('voucher_no');
        $next = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $next = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%06d', $prefix, $next);
    }

    private function nextReplenishmentNumber(): string
    {
        $year = now()->year;
        $prefix = "PCR-{$year}-";
        $latest = PettyCashReplenishment::query()->where('replenishment_no', 'like', $prefix . '%')->orderByDesc('id')->value('replenishment_no');
        $next = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $next = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%06d', $prefix, $next);
    }

    private function ledgerAvailableBalance(Ledger $ledger): float
    {
        $openingSigned = (float) self::signedOpeningBalance($ledger->opening_balance, $ledger->opening_balance_type, 4);
        $postedDelta = (float) GeneralLedger::query()
            ->where('ledger_id', $ledger->id)
            ->selectRaw('COALESCE(SUM(debit_amount - credit_amount), 0) as balance')
            ->value('balance');

        return $openingSigned + $postedDelta;
    }
}
