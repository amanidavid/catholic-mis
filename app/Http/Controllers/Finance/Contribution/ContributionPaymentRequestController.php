<?php

namespace App\Http\Controllers\Finance\Contribution;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\ContributionPaymentRequestResource;
use App\Models\Finance\ContributionCatalog;
use App\Models\Finance\ContributionPaymentRequest;
use App\Models\People\Member;
use App\Models\Parish;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContributionPaymentRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ContributionPaymentRequest::class);

        $validated = $request->validate([
            'contribution_catalog_uuid' => ['required', 'string', 'exists:contribution_catalogs,uuid'],
            'payer_member_uuid' => ['required', 'string', 'exists:members,uuid'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $catalog = ContributionCatalog::where('uuid', $validated['contribution_catalog_uuid'])->firstOrFail();

        // Find an active rule for the selected catalog (effective today)
        $today = now()->toDateString();
        $rule = \App\Models\Finance\ContributionRule::query()
            ->where('contribution_catalog_id', $catalog->id)
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

        if (! $rule) {
            return back()
                ->withErrors(['contribution_catalog_uuid' => 'No active rule found for the selected catalog.'])
                ->withInput();
        }

        $payerId = Member::where('uuid', $validated['payer_member_uuid'])->value('id');

        ContributionPaymentRequest::create([
            'contribution_catalog_id' => $catalog->id,
            'source_type' => 'manual',
            'source_id' => 0,
            'subject_member_id' => null,
            'payer_member_id' => $payerId,
            'family_id' => null,
            'rule_snapshot_name' => $catalog->name,
            'rule_snapshot_code' => $catalog->code,
            'rule_snapshot_amount' => (float) $rule->amount,
            'currency_code' => $rule->currency_code ?? 'TZS',
            'amount_due' => (float) $rule->amount,
            'amount_paid' => 0.0000,
            'balance' => (float) $rule->amount,
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', 'Contribution payment request created successfully.');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ContributionPaymentRequest::class);

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';
        $status = is_string($request->query('status')) ? trim((string) $request->query('status')) : '';
        $catalogUuid = is_string($request->query('catalog_uuid')) ? trim((string) $request->query('catalog_uuid')) : '';
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = ContributionPaymentRequest::query()
            ->with('catalog:id,uuid,name,code')
            ->select(['id', 'uuid', 'contribution_catalog_id', 'source_type', 'source_id', 'subject_member_id', 'payer_member_id', 'family_id', 'rule_snapshot_name', 'rule_snapshot_code', 'rule_snapshot_amount', 'currency_code', 'amount_due', 'amount_paid', 'balance', 'status', 'due_date', 'notes', 'created_at']);

        if ($q !== '') {
            $safe = addcslashes($q, '%_\\');
            $query->where(function ($w) use ($safe) {
                $w->where('rule_snapshot_name', 'like', $safe . '%')
                    ->orWhere('rule_snapshot_code', 'like', $safe . '%')
                    ->orWhereHas('subjectMember', function ($m) use ($safe) {
                        $m->where('first_name', 'like', $safe . '%')
                            ->orWhere('last_name', 'like', $safe . '%');
                    })
                    ->orWhereHas('payerMember', function ($m) use ($safe) {
                        $m->where('first_name', 'like', $safe . '%')
                            ->orWhere('last_name', 'like', $safe . '%');
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($catalogUuid !== '') {
            $catalogId = ContributionCatalog::where('uuid', $catalogUuid)->value('id');
            if ($catalogId) {
                $query->where('contribution_catalog_id', $catalogId);
            }
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                $request->date('date_from')->startOfDay(),
                $request->date('date_to')->endOfDay(),
            ]);
        }

        $items = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $catalogs = ContributionCatalog::query()
            ->where('is_active', true)
            ->select(['id', 'uuid', 'name', 'code'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Finance/Contribution/PaymentRequests/Index', [
            'items' => ContributionPaymentRequestResource::collection($items),
            'catalogs' => $catalogs,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'catalog_uuid' => $catalogUuid,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function show(string $uuid): Response
    {
        $obligation = ContributionPaymentRequest::where('uuid', $uuid)
            ->with([
                'catalog:id,uuid,name,code',
                'subjectMember:id,uuid,first_name,last_name',
                'payerMember:id,uuid,first_name,last_name',
                'family:id,uuid,family_name',
            ])
            ->firstOrFail();

        $this->authorize('view', $obligation);

        $transactions = $obligation->transactions()
            ->with('member:id,uuid,first_name,last_name')
            ->select(['id', 'uuid', 'contribution_payment_request_id', 'member_id', 'transaction_type', 'amount', 'payment_method', 'reference_no', 'paid_at', 'notes', 'created_at'])
            ->orderBy('paid_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Finance/Contribution/PaymentRequests/Show', [
            'item' => new ContributionPaymentRequestResource($obligation),
            'transactions' => $transactions,
        ]);
    }
}
