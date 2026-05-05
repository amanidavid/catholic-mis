<?php

namespace App\Http\Controllers\Finance\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\Accounting\GeneralLedgerEntryResource;
use App\Models\Finance\GeneralLedger;
use App\Models\Finance\Ledger;
use App\Services\Finance\Accounting\GeneralLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class GeneralLedgerController extends Controller
{
    public function __construct(
        private readonly GeneralLedgerService $generalLedgerService,
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', GeneralLedger::class);

        $ledgerUuid = is_string($request->query('ledger_uuid')) ? trim((string) $request->query('ledger_uuid')) : '';
        $dateFrom = is_string($request->query('date_from')) ? trim((string) $request->query('date_from')) : '';
        $dateTo = is_string($request->query('date_to')) ? trim((string) $request->query('date_to')) : '';

        $perPage = (int) ($request->query('per_page') ?? 15);
        if ($perPage < 5) {
            $perPage = 5;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $report = null;
        $selectedLedger = null;
        $entries = null;

        if ($ledgerUuid !== '') {
            $selectedLedger = Ledger::query()->where('uuid', $ledgerUuid)->first();
            if ($selectedLedger) {
                $effectiveDateFrom = $dateFrom !== '' ? $dateFrom : '1900-01-01';
                $effectiveDateTo = $dateTo !== '' ? $dateTo : Carbon::today()->toDateString();
                $report = $this->generalLedgerService->getLedgerReport($selectedLedger, $effectiveDateFrom, $effectiveDateTo, $perPage);
                $entries = GeneralLedgerEntryResource::collection($report['entries']);
            }
        } else {
            $effectiveDateFrom = $dateFrom !== '' ? $dateFrom : '1900-01-01';
            $effectiveDateTo = $dateTo !== '' ? $dateTo : Carbon::today()->toDateString();

            $allEntries = GeneralLedger::query()
                ->with([
                    'journal:id,uuid,journal_no',
                    'ledger:id,uuid,name,account_code',
                ])
                ->whereBetween('transaction_date', [$effectiveDateFrom, $effectiveDateTo])
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->paginate($perPage)
                ->withQueryString();

            $entries = GeneralLedgerEntryResource::collection($allEntries);
        }

        return Inertia::render('Finance/Accounting/GeneralLedger/Index', [
            'ledgers' => [],
            'selected_ledger' => $selectedLedger?->only(['uuid', 'name', 'account_code']),
            'opening_balance_signed' => $report['opening_balance_signed'] ?? null,
            'entries' => $entries,
            'filters' => [
                'ledger_uuid' => $ledgerUuid,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GeneralLedger::class);

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';

        $rows = Ledger::query()
            ->select(['uuid', 'name', 'account_code'])
            ->where('is_active', true)
            ->when($q !== '', function ($qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $qb->where(function ($w) use ($safe) {
                    $w->where('name', 'like', $safe.'%')
                        ->orWhere('account_code', 'like', $safe.'%');
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn (Ledger $ledger) => [
                'uuid' => $ledger->uuid,
                'name' => $ledger->name,
                'account_code' => $ledger->account_code,
                'subtitle' => $ledger->account_code ? 'Code: '.$ledger->account_code : null,
            ])
            ->values();

        return response()->json(['data' => $rows]);
    }
}
