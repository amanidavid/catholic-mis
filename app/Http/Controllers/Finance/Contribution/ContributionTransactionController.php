<?php

namespace App\Http\Controllers\Finance\Contribution;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Contribution\StoreContributionTransactionRequest;
use App\Models\Finance\ContributionPaymentRequest;
use App\Models\Finance\ContributionRule;
use App\Services\Finance\Contribution\ContributionPaymentRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ContributionTransactionController extends Controller
{
    public function __construct(
        private readonly ContributionPaymentRequestService $paymentRequestService,
    ) {
        $this->middleware('auth');
    }

    public function store(StoreContributionTransactionRequest $request): RedirectResponse
    {
        $paymentRequest = ContributionPaymentRequest::where('uuid', $request->validated('contribution_payment_request_uuid'))
            ->firstOrFail();

        $today = now()->toDateString();
        $rule = ContributionRule::query()
            ->where('contribution_catalog_id', $paymentRequest->contribution_catalog_id)
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        if (
            $request->validated('transaction_type') === 'payment'
            && $rule
            && ! (bool) $rule->allow_partial_payment
        ) {
            $balance = (float) $paymentRequest->balance;
            $entered = (float) $request->validated('amount');
            if ($entered < $balance) {
                return back()->withErrors([
                    'amount' => 'Partial payments are not allowed for this contribution. Please pay the full balance.',
                ])->withInput();
            }
        }


        $user = Auth::user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $memberId = null;
            if (is_string($request->validated('member_uuid'))) {
                $memberId = \App\Models\People\Member::where('uuid', $request->validated('member_uuid'))->value('id');
            } else {
                // If no member was specified, default to the payer on the payment request (if set)
                $memberId = $paymentRequest->payer_member_id ?: null;
            }

            $this->paymentRequestService->recordPayment(
                $paymentRequest,
                $request->validated('transaction_type'),
                (float) $request->validated('amount'),
                $request->validated('payment_method', 'cash'),
                $request->validated('reference_no'),
                $memberId,
                $request->validated('notes'),
                (int) $user->id
            );

            return back()->with('success', 'Transaction recorded successfully.');
        } catch (\Throwable $e) {
            Log::error('Contribution transaction save failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to record transaction. Please try again.');
        }
    }

    public function waive(string $uuid): RedirectResponse
    {
        $paymentRequest = ContributionPaymentRequest::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $paymentRequest);

        $today = now()->toDateString();
        $rule = ContributionRule::query()
            ->where('contribution_catalog_id', $paymentRequest->contribution_catalog_id)
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        if ($rule && ! (bool) $rule->waiver_allowed) {
            return back()->with('error', 'Waiver is not allowed for this contribution.');
        }

        $user = Auth::user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $this->paymentRequestService->waivePaymentRequest(
                $paymentRequest,
                'Waived by staff',
                (int) $user->id
            );

            return back()->with('success', 'Payment request waived successfully.');
        } catch (\Throwable $e) {
            Log::error('Contribution payment request waive failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to waive payment request. Please try again.');
        }
    }

    public function cancel(string $uuid): RedirectResponse
    {
        $paymentRequest = ContributionPaymentRequest::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $paymentRequest);

        $user = Auth::user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $this->paymentRequestService->cancelPaymentRequest($paymentRequest, (int) $user->id);

            return back()->with('success', 'Payment request cancelled successfully.');
        } catch (\Throwable $e) {
            Log::error('Contribution payment request cancel failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to cancel payment request. Please try again.');
        }
    }
}
