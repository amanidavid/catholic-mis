<?php

namespace App\Http\Controllers\Finance\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Accounting\StorePettyCashFundRequest;
use App\Http\Resources\Finance\Accounting\LedgerOptionResource;
use App\Http\Resources\Finance\Accounting\PettyCashFundIndexResource;
use App\Http\Resources\Finance\Banking\CurrencyOptionResource;
use App\Models\Finance\Currency;
use App\Models\Finance\Ledger;
use App\Models\Finance\PettyCashFund;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PettyCashFundController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PettyCashFund::class);

        $items = PettyCashFund::query()
            ->join('ledgers', 'ledgers.id', '=', 'petty_cash_funds.ledger_id')
            ->join('currencies', 'currencies.id', '=', 'petty_cash_funds.currency_id')
            ->leftJoin('users as custodians', 'custodians.id', '=', 'petty_cash_funds.custodian_user_id')
            ->select([
                'petty_cash_funds.id',
                'petty_cash_funds.uuid',
                'petty_cash_funds.name',
                'petty_cash_funds.code',
                'petty_cash_funds.created_at',
                'petty_cash_funds.updated_at',
                'petty_cash_funds.ledger_id',
                'petty_cash_funds.currency_id',
                'petty_cash_funds.custodian_user_id',
                'petty_cash_funds.imprest_amount',
                'petty_cash_funds.min_reorder_amount',
                'petty_cash_funds.is_active',
                'ledgers.uuid as ledger_uuid',
                'ledgers.name as ledger_name',
                'ledgers.opening_balance as ledger_opening_balance',
                'ledgers.opening_balance_type as ledger_opening_balance_type',
                'currencies.uuid as currency_uuid',
                'currencies.code as currency_code',
                'custodians.name as custodian_name',
            ])
            ->orderBy('petty_cash_funds.name')
            ->paginate(20)
            ->withQueryString();

        $ledgerIds = collect($items->items())
            ->pluck('ledger_id')
            ->filter()
            ->unique()
            ->values();

        $glSums = $ledgerIds->isEmpty()
            ? collect()
            : DB::table('general_ledgers')
                ->selectRaw('ledger_id, COALESCE(SUM(debit_amount - credit_amount), 0) as gl_delta')
                ->whereIn('ledger_id', $ledgerIds)
                ->groupBy('ledger_id')
                ->pluck('gl_delta', 'ledger_id');

        $items->setCollection(
            $items->getCollection()->map(function ($item) use ($glSums) {
                $opening = (float) ($item->ledger_opening_balance ?? 0);
                $openingSigned = ($item->ledger_opening_balance_type ?? 'debit') === 'credit' ? -$opening : $opening;
                $delta = (float) ($glSums[$item->ledger_id] ?? 0);
                $item->gl_balance_signed = $openingSigned + $delta;

                return $item;
            })
        );

        $ledgers = Cache::remember('petty-cash:active-ledgers:v1', now()->addMinutes(10), function (): Collection {
            return Ledger::query()
                ->select(['id', 'uuid', 'name', 'account_code', 'currency_id'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        });

        $currencies = Cache::remember('petty-cash:active-currencies:v1', now()->addMinutes(30), function (): Collection {
            return Currency::query()
                ->select(['id', 'uuid', 'code', 'name'])
                ->where('is_active', true)
                ->orderBy('code')
                ->get();
        });

        $users = Cache::remember('petty-cash:active-users:v1', now()->addMinutes(10), function (): Collection {
            return User::query()
                ->select(['uuid', 'name'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        });

        return Inertia::render('Finance/Accounting/PettyCash/Funds/Index', [
            'funds' => PettyCashFundIndexResource::collection($items),
            'ledgers' => LedgerOptionResource::collection($ledgers)->resolve(),
            'currencies' => CurrencyOptionResource::collection($currencies)->resolve(),
            'users' => $users->map(fn (User $user) => ['uuid' => $user->uuid, 'name' => $user->name])->values(),
        ]);
    }

    public function store(StorePettyCashFundRequest $request): RedirectResponse
    {
        $this->authorize('create', PettyCashFund::class);

        $validated = $request->validated();
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        $ledger = Ledger::query()->select(['id'])->where('uuid', (string) $validated['ledger_uuid'])->first();
        $currency = Currency::query()->select(['id'])->where('uuid', (string) $validated['currency_uuid'])->first();
        $custodian = ! empty($validated['custodian_user_uuid'])
            ? User::query()->select(['id'])->where('uuid', (string) $validated['custodian_user_uuid'])->where('is_active', true)->first()
            : null;

        if (! $ledger || ! $currency) {
            return back()->with('error', 'Invalid ledger or currency.');
        }

        try {
            DB::transaction(function () use ($validated, $ledger, $currency, $custodian, $user): PettyCashFund {
                $fund = new PettyCashFund();
                $fund->name = (string) $validated['name'];
                $fund->code = $this->nextFundCode();
                $fund->ledger_id = (int) $ledger->id;
                $fund->currency_id = (int) $currency->id;
                $fund->custodian_user_id = $custodian?->id;
                $fund->imprest_amount = $validated['imprest_amount'];
                $fund->min_reorder_amount = $validated['min_reorder_amount'] ?? null;
                $fund->is_active = array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true;
                $fund->created_by = (int) $user->id;
                $fund->save();

                return $fund;
            }, 3);

            $this->forgetPettyCashDropdownCaches();

            return back()->with('success', 'Petty cash fund created.');
        } catch (QueryException $e) {
            Log::warning('Petty cash fund create validation failure at DB', ['message' => $e->getMessage()]);

            if ($this->isDuplicateFundCodeException($e)) {
                return back()->with('error', 'Unable to create fund due to a code conflict. Please try again.')->withInput();
            }

            return back()->with('error', 'Unable to create petty cash fund due to invalid or conflicting data. Please review your inputs and try again.')->withInput();
        } catch (\Throwable $e) {
            Log::error('Petty cash fund create failed', ['exception' => $e]);
            return back()->with('error', 'Unable to create petty cash fund right now. Please try again or contact support if the issue persists.')->withInput();
        }
    }

    private function nextFundCode(): string
    {
        $year = now()->year;
        $prefix = "PCF-{$year}-";

        return Cache::lock('petty-cash:auto-fund-code:v1', 5)->block(3, function () use ($prefix): string {
            $latest = PettyCashFund::query()
                ->where('code', 'like', $prefix . '%')
                ->orderByDesc('id')
                ->value('code');

            $next = 1;
            if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
                $next = ((int) $matches[1]) + 1;
            }

            return sprintf('%s%05d', $prefix, $next);
        });
    }

    private function isDuplicateFundCodeException(QueryException $e): bool
    {
        $errorInfo = $e->errorInfo;
        $sqlState = is_array($errorInfo) && isset($errorInfo[0]) ? (string) $errorInfo[0] : '';
        $driverCode = is_array($errorInfo) && isset($errorInfo[1]) ? (string) $errorInfo[1] : '';
        $message = strtolower((string) $e->getMessage());

        return $sqlState === '23000'
            && (
                str_contains($message, 'petty_cash_funds_code_unique')
                || str_contains($message, 'duplicate')
                || $driverCode === '1062'
            );
    }

    private function forgetPettyCashDropdownCaches(): void
    {
        Cache::forget('petty-cash:active-ledgers:v1');
        Cache::forget('petty-cash:active-currencies:v1');
        Cache::forget('petty-cash:active-users:v1');
    }
}
