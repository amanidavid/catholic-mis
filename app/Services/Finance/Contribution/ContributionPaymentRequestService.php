<?php

namespace App\Services\Finance\Contribution;

use App\Models\Finance\ContributionCatalog;
use App\Models\Finance\ContributionPaymentRequest;
use App\Models\Finance\ContributionRule;
use App\Models\Finance\ContributionTransaction;
use App\Traits\FormatsAmounts;
use Illuminate\Support\Facades\DB;

class ContributionPaymentRequestService
{
    use FormatsAmounts;

    /**
     * Create payment requests from rules for a source.
     *
     * @param array<int, ContributionRule> $rules
     * @return array<int, ContributionPaymentRequest>
     */
    public function createPaymentRequestsFromRules(
        string $sourceType,
        int $sourceId,
        array $rules,
        ?int $subjectMemberId = null,
        ?int $payerMemberId = null,
        ?int $familyId = null,
        int $createdByUserId
    ): array {
        $paymentRequests = [];

        foreach ($rules as $rule) {
            $paymentRequest = ContributionPaymentRequest::create([
                'contribution_catalog_id' => $rule->contribution_catalog_id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'subject_member_id' => $subjectMemberId,
                'payer_member_id' => $payerMemberId,
                'family_id' => $familyId,
                'rule_snapshot_name' => $rule->catalog?->name ?? '',
                'rule_snapshot_code' => $rule->catalog?->code ?? '',
                'rule_snapshot_amount' => (float) $rule->amount,
                'currency_code' => $rule->currency_code ?? 'TZS',
                'amount_due' => (float) $rule->amount,
                'amount_paid' => 0.0000,
                'balance' => (float) $rule->amount,
                'status' => 'pending',
                'due_date' => null,
                'notes' => null,
                'created_by' => $createdByUserId,
                'updated_by' => $createdByUserId,
            ]);

            $paymentRequests[] = $paymentRequest;
        }

        return $paymentRequests;
    }

    /**
     * Record a payment transaction and update payment request status/balance.
     */
    public function recordPayment(
        ContributionPaymentRequest $paymentRequest,
        string $transactionType,
        float $amount,
        string $paymentMethod,
        ?string $referenceNo = null,
        ?int $memberId = null,
        ?string $notes = null,
        int $recordedByUserId
    ): ContributionTransaction {
        return DB::transaction(function () use (
            $paymentRequest,
            $transactionType,
            $amount,
            $paymentMethod,
            $referenceNo,
            $memberId,
            $notes,
            $recordedByUserId
        ) {
            $paymentRequest = ContributionPaymentRequest::query()
                ->where('id', $paymentRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            $transaction = ContributionTransaction::create([
                'parish_id' => $paymentRequest->parish_id,
                'contribution_payment_request_id' => $paymentRequest->id,
                'member_id' => $memberId,
                'transaction_type' => $transactionType,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'reference_no' => $referenceNo,
                'paid_at' => now(),
                'notes' => $notes,
                'recorded_by' => $recordedByUserId,
            ]);

            // If a payer isn't set yet and we have a member on this transaction, persist it for the request
            if ($memberId && is_null($paymentRequest->payer_member_id)) {
                $paymentRequest->update([
                    'payer_member_id' => $memberId,
                ]);
            }

            $this->recalculatePaymentRequest($paymentRequest);

            return $transaction;
        });
    }

    /**
     * Recalculate payment request totals and update status.
     */
    public function recalculatePaymentRequest(ContributionPaymentRequest $paymentRequest): void
    {
        $totalPaid = ContributionTransaction::query()
            ->where('contribution_payment_request_id', $paymentRequest->id)
            ->where('transaction_type', 'payment')
            ->sum('amount');

        $totalWaived = ContributionTransaction::query()
            ->where('contribution_payment_request_id', $paymentRequest->id)
            ->where('transaction_type', 'waiver')
            ->sum('amount');

        $totalRefund = ContributionTransaction::query()
            ->where('contribution_payment_request_id', $paymentRequest->id)
            ->where('transaction_type', 'refund')
            ->sum('amount');

        $totalAdjustment = ContributionTransaction::query()
            ->where('contribution_payment_request_id', $paymentRequest->id)
            ->where('transaction_type', 'adjustment')
            ->sum('amount');

        $amountPaid = (float) self::normalizeAmount($totalPaid - $totalRefund + $totalAdjustment, 4);
        $balance = (float) self::normalizeAmount($paymentRequest->amount_due - $amountPaid - $totalWaived, 4);

        if ($balance < 0) {
            $balance = 0.0000;
        }

        $status = $this->determineStatus($paymentRequest, $amountPaid, $totalWaived, $balance);

        $paymentRequest->update([
            'amount_paid' => $amountPaid,
            'balance' => $balance,
            'status' => $status,
        ]);
    }

    /**
     * Determine payment request status based on payments and waivers.
     */
    protected function determineStatus(
        ContributionPaymentRequest $paymentRequest,
        float $amountPaid,
        float $totalWaived,
        float $balance
    ): string {
        if ($totalWaived >= $paymentRequest->amount_due) {
            return 'waived';
        }

        if ($balance <= 0.0000) {
            return 'paid';
        }

        if ($amountPaid > 0.0000) {
            return 'partial';
        }

        return 'pending';
    }

    /**
     * Waive a payment request.
     */
    public function waivePaymentRequest(
        ContributionPaymentRequest $paymentRequest,
        ?string $notes = null,
        int $recordedByUserId
    ): ContributionTransaction {
        return $this->recordPayment(
            $paymentRequest,
            'waiver',
            (float) $paymentRequest->balance,
            'other',
            null,
            null,
            $notes,
            $recordedByUserId
        );
    }

    /**
     * Cancel a payment request.
     */
    public function cancelPaymentRequest(ContributionPaymentRequest $paymentRequest, int $cancelledByUserId): void
    {
        $paymentRequest->update([
            'status' => 'cancelled',
            'updated_by' => $cancelledByUserId,
        ]);
    }
}
