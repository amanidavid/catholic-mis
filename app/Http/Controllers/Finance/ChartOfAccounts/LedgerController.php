<?php

namespace App\Http\Controllers\Finance\ChartOfAccounts;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\Banking\CurrencyOptionResource;
use App\Http\Resources\Finance\ChartOfAccounts\AccountGroupOptionResource;
use App\Http\Resources\Finance\ChartOfAccounts\AccountSubtypeOptionResource;
use App\Http\Resources\Finance\ChartOfAccounts\AccountTypeOptionResource;
use App\Http\Resources\Finance\ChartOfAccounts\LedgerIndexResource;
use App\Http\Requests\Finance\ChartOfAccounts\BulkUpsertLedgersRequest;
use App\Models\Finance\AccountGroup;
use App\Models\Finance\AccountSubtype;
use App\Models\Finance\AccountType;
use App\Models\Finance\Currency;
use App\Models\Finance\Ledger;
use App\Traits\FormatsAmounts;
use App\Traits\NormalizesNames;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LedgerController extends Controller
{
    use FormatsAmounts;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Ledger::class);

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';

        $perPage = (int) ($request->query('per_page') ?? 15);
        if ($perPage < 5) {
            $perPage = 5;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $groups = AccountGroup::query()
            ->select(['id', 'uuid', 'name', 'code'])
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        $types = AccountType::query()
            ->select(['id', 'uuid', 'name', 'account_group_id'])
            ->orderBy('name')
            ->get();

        $subtypes = AccountSubtype::query()
            ->select(['id', 'uuid', 'name', 'account_type_id'])
            ->orderBy('name')
            ->get();

        $currencies = Currency::query()
            ->select(['id', 'uuid', 'code', 'name'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $ledgers = Ledger::query()
            ->with([
                'subtype:id,uuid,name,account_type_id',
                'subtype.type:id,uuid,name,account_group_id',
                'subtype.type.group:id,uuid,name,code',
                'currency:id,uuid,code,name',
            ])
            ->select([
                'id',
                'uuid',
                'name',
                'account_code',
                'currency_id',
                'opening_balance',
                'opening_balance_type',
                'is_active',
                'account_subtype_id',
                'created_at',
            ])
            ->when($q !== '', function ($qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $qb->where('ledgers.name', 'like', $safe . '%');
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Finance/ChartOfAccounts/Ledgers/Index', [
            'ledgers' => LedgerIndexResource::collection($ledgers),
            'groups' => AccountGroupOptionResource::collection($groups)->resolve(),
            'types' => AccountTypeOptionResource::collection($types)->resolve(),
            'subtypes' => AccountSubtypeOptionResource::collection($subtypes)->resolve(),
            'currencies' => CurrencyOptionResource::collection($currencies)->resolve(),
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function bulkUpsert(BulkUpsertLedgersRequest $request): RedirectResponse
    {
        $this->authorize('create', Ledger::class);

        $validated = $request->validated();

        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        $subtype = AccountSubtype::query()->where('uuid', $validated['account_subtype_uuid'])->first();
        if (! $subtype) {
            return back()->with('error', 'Invalid account subtype.');
        }

        $currency = Currency::query()->where('uuid', $validated['currency_uuid'])->first();
        if (! $currency) {
            return back()->with('error', 'Invalid currency.');
        }

        try {
            DB::transaction(function () use ($validated, $subtype, $currency, $user): void {
                $rows = [];
                $uuids = [];

                foreach (($validated['items'] ?? []) as $item) {
                    $uuid = isset($item['uuid']) ? trim((string) $item['uuid']) : '';
                    $name = NormalizesNames::normalize((string) $item['name']);
                    $accountCode = array_key_exists('account_code', $item) ? trim((string) $item['account_code']) : null;
                    $accountCode = $accountCode === '' ? null : $accountCode;

                    $openingBalance = array_key_exists('opening_balance', $item)
                        ? self::normalizeAmount($item['opening_balance'], 4)
                        : self::normalizeAmount(0, 4);
                    $openingBalanceType = array_key_exists('opening_balance_type', $item)
                        ? (string) $item['opening_balance_type']
                        : 'debit';

                    $rows[] = [
                        'uuid' => $uuid !== ''
                            ? $uuid
                            : (method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid()),
                        'account_subtype_id' => (int) $subtype->id,
                        'name' => $name,
                        'account_code' => $accountCode,
                        'currency_id' => (int) $currency->id,
                        'opening_balance' => $openingBalance,
                        'opening_balance_type' => $openingBalanceType,
                        'is_active' => array_key_exists('is_active', $item) ? (bool) $item['is_active'] : true,
                        'created_by' => (int) $user->id,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];
                    $uuids[] = $rows[array_key_last($rows)]['uuid'];
                }

                if (count($rows) > 0) {
                    $existing = Ledger::query()
                        ->whereIn('uuid', $uuids)
                        ->get()
                        ->keyBy('uuid');

                    Ledger::query()->upsert(
                        $rows,
                        ['uuid'],
                        ['account_subtype_id', 'name', 'account_code', 'currency_id', 'opening_balance', 'opening_balance_type', 'is_active', 'updated_at']
                    );

                    $persisted = Ledger::query()
                        ->whereIn('uuid', $uuids)
                        ->get()
                        ->keyBy('uuid');

                    foreach ($persisted as $uuid => $ledger) {
                        $before = $existing->get($uuid);

                        if (! $before) {
                            $ledger->logCustomAudit('created', null, $ledger->getAttributes(), "Created ledger {$ledger->name}");
                            continue;
                        }

                        $oldValues = $this->auditValues($before->getAttributes(), ['account_subtype_id', 'name', 'account_code', 'currency_id', 'opening_balance', 'opening_balance_type', 'is_active']);
                        $newValues = $this->auditValues($ledger->getAttributes(), ['account_subtype_id', 'name', 'account_code', 'currency_id', 'opening_balance', 'opening_balance_type', 'is_active']);

                        if ($oldValues !== $newValues) {
                            $ledger->logCustomAudit('updated', $oldValues, $newValues, "Updated ledger {$ledger->name}");
                        }
                    }
                }
            });

            return back()->with('success', 'Ledgers saved.');
        } catch (\Throwable $e) {
            Log::error('Ledgers bulk upsert failed', ['exception' => $e]);
            return back()->with('error', 'Unable to save ledgers. Please try again.');
        }
    }

    private function auditValues(array $attributes, array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $attributes[$key] ?? null;
        }

        return $values;
    }

    public function deactivate(Request $request, string $uuid): RedirectResponse
    {
        $ledger = Ledger::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $ledger);

        $ledger->is_active = false;
        $ledger->save();

        return back()->with('success', 'Ledger deactivated.');
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $ledger = Ledger::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $ledger);

        $ledger->delete();
        return back()->with('success', 'Ledger deleted.');
    }
}
