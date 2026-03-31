<?php

namespace App\Http\Controllers\Finance\Banking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Banking\BulkUpsertBankAccountsRequest;
use App\Http\Resources\Finance\Accounting\LedgerOptionResource;
use App\Http\Resources\Finance\Banking\BankAccountIndexResource;
use App\Http\Resources\Finance\Banking\BankOptionResource;
use App\Http\Resources\Finance\Banking\CurrencyOptionResource;
use App\Models\Finance\Bank;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankAccountTransaction;
use App\Models\Finance\Currency;
use App\Models\Finance\Ledger;
use App\Traits\NormalizesNames;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BankAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', BankAccount::class);

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';
        $perPage = (int) ($request->query('per_page') ?? 15);
        $perPage = max(5, min(100, $perPage));

        $qNormalized = $q !== '' ? mb_strtolower(NormalizesNames::normalize($q) ?? '', 'UTF-8') : '';

        $banks = Bank::query()
            ->select(['id', 'uuid', 'name'])
            ->where('is_active', true)
            ->orderBy('name_normalized')
            ->get();

        $ledgers = Ledger::query()
            ->select(['id', 'uuid', 'name', 'account_code', 'currency_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $currencies = Currency::query()
            ->select(['id', 'uuid', 'code', 'name'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $items = BankAccount::query()
            ->leftJoin('banks', 'banks.id', '=', 'bank_accounts.bank_id')
            ->leftJoin('ledgers', 'ledgers.id', '=', 'bank_accounts.ledger_id')
            ->leftJoin('currencies', 'currencies.id', '=', 'bank_accounts.currency_id')
            ->select([
                'bank_accounts.id',
                'bank_accounts.uuid',
                'bank_accounts.bank_id',
                'bank_accounts.ledger_id',
                'bank_accounts.currency_id',
                'bank_accounts.account_name',
                'bank_accounts.account_name_normalized',
                'bank_accounts.account_number',
                'bank_accounts.branch',
                'bank_accounts.swift_code',
                'bank_accounts.is_active',
                'bank_accounts.created_at',
                'banks.uuid as bank_uuid',
                'banks.name as bank_name',
                'ledgers.uuid as ledger_uuid',
                'ledgers.name as ledger_name',
                'ledgers.account_code as ledger_account_code',
                'currencies.uuid as currency_uuid',
                'currencies.code as currency_code',
                'currencies.name as currency_name',
            ])
            ->when($q !== '', function ($qb) use ($q, $qNormalized) {
                $safeNumber = addcslashes($q, '%_\\');
                $safeName = addcslashes($qNormalized, '%_\\');
                $safeText = addcslashes($q, '%_\\');

                $qb->where(function ($w) use ($safeNumber, $safeName, $safeText) {
                    $w->where('account_number', 'like', $safeNumber . '%')
                        ->orWhere('bank_accounts.account_name_normalized', 'like', $safeName . '%')
                        ->orWhere('banks.name_normalized', 'like', $safeName . '%')
                        ->orWhere(function ($l) use ($safeNumber, $safeText) {
                            $l->where('ledgers.account_code', 'like', $safeNumber . '%')
                                ->orWhere('ledgers.name', 'like', $safeText . '%');
                        });
                });
            })
            ->orderBy('banks.name_normalized')
            ->orderBy('bank_accounts.account_name_normalized')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Finance/Banking/BankAccounts/Index', [
            'accounts' => BankAccountIndexResource::collection($items),
            'banks' => BankOptionResource::collection($banks)->resolve(),
            'ledgers' => LedgerOptionResource::collection($ledgers)->resolve(),
            'currencies' => CurrencyOptionResource::collection($currencies)->resolve(),
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function bulkUpsert(BulkUpsertBankAccountsRequest $request): RedirectResponse
    {
        $this->authorize('create', BankAccount::class);

        $validated = $request->validated();
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        $bank = Bank::query()->where('uuid', $validated['bank_uuid'])->first();
        $currency = Currency::query()->where('uuid', $validated['currency_uuid'])->first();
        if (! $bank || ! $currency) {
            return back()->with('error', 'Invalid bank or currency.');
        }

        $ledgerUuids = collect($validated['items'] ?? [])->pluck('ledger_uuid')->filter()->unique()->values();
        $ledgerMap = Ledger::query()->whereIn('uuid', $ledgerUuids)->select(['id', 'uuid'])->get()->keyBy('uuid');

        if ($ledgerMap->count() !== $ledgerUuids->count()) {
            return back()->with('error', 'One or more selected ledgers are invalid.');
        }

        try {
            DB::transaction(function () use ($validated, $user, $bank, $currency, $ledgerMap): void {
                $rows = [];

                foreach (($validated['items'] ?? []) as $item) {
                    $accountName = NormalizesNames::normalize((string) $item['account_name']);
                    $accountNameNormalized = mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $accountName)), 'UTF-8');
                    $uuid = isset($item['uuid']) ? trim((string) $item['uuid']) : '';

                    $rows[] = [
                        'uuid' => $uuid !== ''
                            ? $uuid
                            : (method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid()),
                        'bank_id' => (int) $bank->id,
                        'ledger_id' => (int) $ledgerMap->get((string) $item['ledger_uuid'])->id,
                        'currency_id' => (int) $currency->id,
                        'account_name' => $accountName,
                        'account_name_normalized' => $accountNameNormalized,
                        'account_number' => trim((string) $item['account_number']),
                        'branch' => isset($item['branch']) ? trim((string) $item['branch']) ?: null : null,
                        'swift_code' => isset($item['swift_code']) ? strtoupper(trim((string) $item['swift_code'])) ?: null : null,
                        'is_active' => array_key_exists('is_active', $item) ? (bool) $item['is_active'] : true,
                        'created_by' => (int) $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (count($rows) > 0) {
                    BankAccount::query()->upsert(
                        $rows,
                        ['uuid'],
                        ['bank_id', 'ledger_id', 'currency_id', 'account_name', 'account_name_normalized', 'account_number', 'branch', 'swift_code', 'is_active', 'updated_at']
                    );
                }
            });

            return back()->with('success', 'Bank accounts saved.');
        } catch (\Throwable $e) {
            Log::error('Bank accounts bulk upsert failed', ['exception' => $e]);
            return back()->with('error', 'Unable to save bank accounts. Please try again.');
        }
    }

    public function deactivate(Request $request, string $uuid): RedirectResponse
    {
        $account = BankAccount::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $account);

        $account->is_active = false;
        $account->save();

        return back()->with('success', 'Bank account deactivated.');
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $account = BankAccount::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $account);

        if (BankAccountTransaction::query()->where('bank_account_id', $account->id)->exists()) {
            return back()->with('error', 'Cannot delete this bank account because it has transactions.');
        }

        $account->delete();
        return back()->with('success', 'Bank account deleted.');
    }
}
