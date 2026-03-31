<?php

namespace App\Http\Controllers\Finance\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Accounting\PettyCashActionRequest;
use App\Http\Requests\Finance\Accounting\StorePettyCashReplenishmentRequest;
use App\Http\Resources\Finance\Accounting\PettyCashFundIndexResource;
use App\Http\Resources\Finance\Accounting\PettyCashReplenishmentIndexResource;
use App\Models\Finance\Ledger;
use App\Models\Finance\PettyCashFund;
use App\Models\Finance\PettyCashReplenishment;
use App\Services\Finance\Accounting\JournalNumberService;
use App\Services\Finance\Accounting\JournalPostingService;
use App\Services\Finance\Accounting\PettyCashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PettyCashReplenishmentController extends Controller
{
    public function __construct(
        private readonly PettyCashService $pettyCashService,
        private readonly JournalNumberService $journalNumberService,
        private readonly JournalPostingService $journalPostingService,
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PettyCashReplenishment::class);

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';
        $status = is_string($request->query('status')) ? trim((string) $request->query('status')) : '';
        $dateFrom = is_string($request->query('date_from')) ? trim((string) $request->query('date_from')) : '';
        $dateTo = is_string($request->query('date_to')) ? trim((string) $request->query('date_to')) : '';
        $prefillFundUuid = is_string($request->query('fund_uuid')) ? trim((string) $request->query('fund_uuid')) : '';
        $openCreate = in_array(strtolower((string) $request->query('open_create')), ['1', 'true', 'yes'], true);

        $items = PettyCashReplenishment::query()
            ->with([
                'fund:id,uuid,name',
                'sourceLedger:id,uuid,name,account_code',
                'journal:id,uuid,journal_no',
            ])
            ->when($q !== '', function ($qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $qb->where(function ($w) use ($safe) {
                    $w->where('replenishment_no', 'like', $safe . '%')
                        ->orWhere('reference_no', 'like', $safe . '%');
                });
            })
            ->when($status !== '', fn ($qb) => $qb->where('status', $status))
            ->when($dateFrom !== '' && $dateTo !== '', fn ($qb) => $qb->whereBetween('transaction_date', [$dateFrom, $dateTo]))
            ->when($dateFrom !== '' && $dateTo === '', fn ($qb) => $qb->where('transaction_date', '>=', $dateFrom))
            ->when($dateFrom === '' && $dateTo !== '', fn ($qb) => $qb->where('transaction_date', '<=', $dateTo))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->simplePaginate(20)
            ->withQueryString();

        $funds = PettyCashFund::query()->select(['id', 'uuid', 'name', 'code', 'ledger_id', 'currency_id', 'imprest_amount', 'is_active'])->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Finance/Accounting/PettyCash/Replenishments/Index', [
            'items' => PettyCashReplenishmentIndexResource::collection($items),
            'funds' => PettyCashFundIndexResource::collection($funds)->resolve(),
            'prefill' => [
                'fund_uuid' => $prefillFundUuid !== '' ? $prefillFundUuid : null,
                'open_create' => $openCreate,
            ],
            'filters' => [
                'q' => $q,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'statuses' => ['draft', 'submitted', 'approved', 'posted', 'cancelled'],
        ]);
    }

    public function store(StorePettyCashReplenishmentRequest $request): RedirectResponse
    {
        $this->authorize('create', PettyCashReplenishment::class);

        $validated = $request->validated();
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        $fund = PettyCashFund::query()->where('uuid', (string) $validated['petty_cash_fund_uuid'])->where('is_active', true)->first();
        $sourceLedger = Ledger::query()->where('uuid', (string) $validated['source_ledger_uuid'])->where('is_active', true)->first();
        if (! $fund || ! $sourceLedger) {
            return back()->with('error', 'Invalid or inactive fund/source ledger. Please select active records and try again.');
        }

        try {
            $this->pettyCashService->createReplenishment($fund, $sourceLedger, $validated, (int) $user->id);
            return back()->with('success', 'Petty cash replenishment created as draft.');
        } catch (\Throwable $e) {
            Log::error('Petty cash replenishment create failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to create petty cash replenishment.');
        }
    }

    public function submit(Request $request, string $uuid): RedirectResponse
    {
        $item = $this->findByUuid($uuid);
        $this->authorize('submit', $item);
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $this->pettyCashService->submitReplenishment($item, (int) $user->id);
            return back()->with('success', 'Petty cash replenishment submitted for approval.');
        } catch (\Throwable $e) {
            Log::error('Petty cash replenishment submit failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to submit petty cash replenishment.');
        }
    }

    public function approve(Request $request, string $uuid): RedirectResponse
    {
        $item = $this->findByUuid($uuid);
        $this->authorize('approve', $item);
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $this->pettyCashService->approveReplenishment($item, (int) $user->id);
            return back()->with('success', 'Petty cash replenishment approved.');
        } catch (\Throwable $e) {
            Log::error('Petty cash replenishment approve failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to approve petty cash replenishment.');
        }
    }

    public function reject(PettyCashActionRequest $request, string $uuid): RedirectResponse
    {
        $item = $this->findByUuid($uuid);
        $this->authorize('reject', $item);
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $this->pettyCashService->rejectReplenishment($item, (int) $user->id, $request->validated('reason'));
            return back()->with('success', 'Petty cash replenishment returned to draft.');
        } catch (\Throwable $e) {
            Log::error('Petty cash replenishment reject failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to reject petty cash replenishment.');
        }
    }

    public function post(Request $request, string $uuid): RedirectResponse
    {
        $item = $this->findByUuid($uuid);
        $this->authorize('post', $item);
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $this->pettyCashService->postReplenishment($item, (int) $user->id, $this->journalNumberService, $this->journalPostingService);
            return back()->with('success', 'Petty cash replenishment posted to journal and general ledger.');
        } catch (\Throwable $e) {
            Log::error('Petty cash replenishment post failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to post petty cash replenishment.');
        }
    }

    public function cancel(PettyCashActionRequest $request, string $uuid): RedirectResponse
    {
        $item = $this->findByUuid($uuid);
        $this->authorize('cancel', $item);
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $this->pettyCashService->cancelReplenishment($item, (int) $user->id, $request->validated('reason'));
            return back()->with('success', 'Petty cash replenishment cancelled.');
        } catch (\Throwable $e) {
            Log::error('Petty cash replenishment cancel failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to cancel petty cash replenishment.');
        }
    }

    private function findByUuid(string $uuid): PettyCashReplenishment
    {
        return PettyCashReplenishment::query()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
