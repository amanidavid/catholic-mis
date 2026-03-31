<?php

namespace App\Http\Controllers\Finance\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\Accounting\PettyCashBookEntryResource;
use App\Http\Resources\Finance\Accounting\PettyCashFundIndexResource;
use App\Models\Finance\GeneralLedger;
use App\Models\Finance\PettyCashFund;
use App\Services\Finance\Accounting\GeneralLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PettyCashBookController extends Controller
{
    public function __construct(
        private readonly GeneralLedgerService $generalLedgerService,
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('finance.petty-cash-book.view'), 403);

        $fundUuid = is_string($request->query('petty_cash_fund_uuid')) ? trim((string) $request->query('petty_cash_fund_uuid')) : '';
        $dateFrom = is_string($request->query('date_from')) ? trim((string) $request->query('date_from')) : '';
        $dateTo = is_string($request->query('date_to')) ? trim((string) $request->query('date_to')) : '';
        $perPage = max(10, min(100, (int) ($request->query('per_page') ?? 20)));

        $funds = PettyCashFund::query()->select(['id', 'uuid', 'name', 'code', 'ledger_id', 'currency_id', 'imprest_amount', 'is_active'])->where('is_active', true)->orderBy('name')->get();

        $selectedFund = null;
        $entries = null;
        $openingBalanceSigned = null;
        $effectiveDateFrom = $dateFrom !== '' ? $dateFrom : '1900-01-01';
        $effectiveDateTo = $dateTo !== '' ? $dateTo : Carbon::today()->toDateString();

        $baseEntriesQuery = GeneralLedger::query()
            ->join('petty_cash_funds', 'petty_cash_funds.ledger_id', '=', 'general_ledgers.ledger_id')
            ->leftJoin('journals', 'journals.id', '=', 'general_ledgers.journal_id')
            ->leftJoin('petty_cash_vouchers', 'petty_cash_vouchers.journal_id', '=', 'general_ledgers.journal_id')
            ->leftJoin('petty_cash_replenishments', 'petty_cash_replenishments.journal_id', '=', 'general_ledgers.journal_id')
            ->whereBetween('general_ledgers.transaction_date', [$effectiveDateFrom, $effectiveDateTo])
            ->select([
                'general_ledgers.id',
                'general_ledgers.uuid',
                'general_ledgers.transaction_date',
                'general_ledgers.description',
                'general_ledgers.created_at',
                'general_ledgers.updated_at',
                'general_ledgers.debit_amount',
                'general_ledgers.credit_amount',
                DB::raw('journals.journal_no as journal_no'),
                DB::raw('petty_cash_funds.uuid as fund_uuid'),
                DB::raw('petty_cash_funds.name as fund_name'),
                DB::raw('petty_cash_funds.code as fund_code'),
                DB::raw('petty_cash_vouchers.voucher_no as voucher_no'),
                DB::raw('petty_cash_replenishments.replenishment_no as replenishment_no'),
            ]);

        if ($fundUuid !== '') {
            $selectedFund = PettyCashFund::query()->with('ledger:id,uuid,name,account_code,opening_balance,opening_balance_type')->where('uuid', $fundUuid)->first();
            if ($selectedFund) {
                $openingBalanceSigned = $this->generalLedgerService->computeOpeningCarryForward($selectedFund->ledger, $effectiveDateFrom);

                $entries = (clone $baseEntriesQuery)
                    ->where('general_ledgers.ledger_id', $selectedFund->ledger_id)
                    ->orderBy('general_ledgers.transaction_date')
                    ->orderBy('general_ledgers.id')
                    ->simplePaginate($perPage)
                    ->withQueryString();
            }
        } else {
            $entries = (clone $baseEntriesQuery)
                ->where('petty_cash_funds.is_active', true)
                ->orderBy('general_ledgers.transaction_date')
                ->orderBy('general_ledgers.id')
                ->simplePaginate($perPage)
                ->withQueryString();
        }

        return Inertia::render('Finance/Accounting/PettyCash/Book/Index', [
            'funds' => PettyCashFundIndexResource::collection($funds)->resolve(),
            'selected_fund' => $selectedFund ? ['uuid' => $selectedFund->uuid, 'name' => $selectedFund->name] : null,
            'opening_balance_signed' => $openingBalanceSigned,
            'entries' => $entries ? PettyCashBookEntryResource::collection($entries) : null,
            'filters' => [
                'petty_cash_fund_uuid' => $fundUuid,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ],
        ]);
    }
}
