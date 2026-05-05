<?php

namespace App\Http\Controllers\Finance\Accounting;

use App\Exports\TrialBalanceExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\Accounting\TrialBalanceEntryResource;
use App\Models\Finance\TrialBalance;
use App\Services\Finance\Accounting\TrialBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;
use Inertia\Response;

class TrialBalanceController extends Controller
{
    public function __construct(
        private readonly TrialBalanceService $trialBalanceService,
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TrialBalance::class);

        $dateTo = is_string($request->query('date_to')) && trim((string) $request->query('date_to')) !== ''
            ? trim((string) $request->query('date_to'))
            : Carbon::today()->toDateString();
        $dateFrom = is_string($request->query('date_from')) && trim((string) $request->query('date_from')) !== ''
            ? trim((string) $request->query('date_from'))
            : Carbon::parse($dateTo)->startOfMonth()->toDateString();

        $perPage = (int) ($request->query('per_page') ?? 50);
        $perPage = max(10, min(100, $perPage));

        $report = $this->trialBalanceService->getReport($dateFrom, $dateTo, $perPage);
        $rows = $report['rows'];

        return Inertia::render('Finance/Accounting/TrialBalance/Index', [
            'rows' => [
                'data' => collect($rows->items())
                    ->map(fn ($row) => (new TrialBalanceEntryResource($row))->toArray($request))
                    ->values()
                    ->all(),
                'next_page_url' => $rows->nextPageUrl(),
                'prev_page_url' => $rows->previousPageUrl(),
                'per_page' => $rows->perPage(),
            ],
            'totals' => $report['totals'],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', TrialBalance::class);

        if (! class_exists(Excel::class)) {
            return response()->json([
                'message' => 'Excel export is not installed. Please run: composer require maatwebsite/excel',
            ], 501);
        }

        $dateTo = is_string($request->query('date_to')) && trim((string) $request->query('date_to')) !== ''
            ? trim((string) $request->query('date_to'))
            : Carbon::today()->toDateString();
        $dateFrom = is_string($request->query('date_from')) && trim((string) $request->query('date_from')) !== ''
            ? trim((string) $request->query('date_from'))
            : Carbon::parse($dateTo)->startOfMonth()->toDateString();

        $rows = $this->trialBalanceService->getExportRows($dateFrom, $dateTo);
        $export = new TrialBalanceExport($rows);
        $filename = "trial-balance_{$dateFrom}_to_{$dateTo}.xlsx";

        return Excel::download($export, $filename);
    }
}
