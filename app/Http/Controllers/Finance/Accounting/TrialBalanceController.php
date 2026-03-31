<?php

namespace App\Http\Controllers\Finance\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\Accounting\TrialBalanceEntryResource;
use App\Models\Finance\TrialBalance;
use App\Services\Finance\Accounting\TrialBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $asAt = is_string($request->query('as_at')) && trim((string) $request->query('as_at')) !== ''
            ? trim((string) $request->query('as_at'))
            : Carbon::today()->toDateString();

        $perPage = (int) ($request->query('per_page') ?? 50);
        $perPage = max(10, min(100, $perPage));

        $report = $this->trialBalanceService->getReport($asAt, $perPage);
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
                'as_at' => $asAt,
                'per_page' => $perPage,
            ],
        ]);
    }
}
