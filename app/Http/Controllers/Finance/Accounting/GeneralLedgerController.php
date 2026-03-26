<?php

namespace App\Http\Controllers\Finance\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\Accounting\GeneralLedgerEntryResource;
use App\Http\Resources\Finance\Accounting\LedgerOptionResource;
use App\Models\Finance\GeneralLedger;
use App\Models\Finance\Ledger;
use App\Services\Finance\Accounting\GeneralLedgerService;
use Illuminate\Http\Request;
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

        $ledgers = Ledger::query()
            ->select(['id', 'uuid', 'name', 'account_code', 'currency_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $report = null;
        $selectedLedger = null;

        if ($ledgerUuid !== '' && $dateFrom !== '' && $dateTo !== '') {
            $selectedLedger = Ledger::query()->where('uuid', $ledgerUuid)->first();
            if ($selectedLedger) {
                $report = $this->generalLedgerService->getLedgerReport($selectedLedger, $dateFrom, $dateTo, $perPage);
            }
        }

        return Inertia::render('Finance/Accounting/GeneralLedger/Index', [
            'ledgers' => LedgerOptionResource::collection($ledgers)->resolve(),
            'selected_ledger' => $selectedLedger?->only(['uuid', 'name', 'account_code']),
            'opening_balance_signed' => $report['opening_balance_signed'] ?? null,
            'entries' => $report ? GeneralLedgerEntryResource::collection($report['entries']) : null,
            'filters' => [
                'ledger_uuid' => $ledgerUuid,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ],
        ]);
    }
}
