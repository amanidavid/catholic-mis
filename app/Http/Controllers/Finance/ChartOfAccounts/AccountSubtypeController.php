<?php

namespace App\Http\Controllers\Finance\ChartOfAccounts;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\ChartOfAccounts\AccountGroupOptionResource;
use App\Http\Resources\Finance\ChartOfAccounts\AccountSubtypeIndexResource;
use App\Http\Resources\Finance\ChartOfAccounts\AccountTypeOptionResource;
use App\Http\Requests\Finance\ChartOfAccounts\BulkUpsertAccountSubtypesRequest;
use App\Models\Finance\AccountGroup;
use App\Models\Finance\AccountSubtype;
use App\Models\Finance\AccountType;
use App\Models\Finance\Ledger;
use App\Traits\NormalizesNames;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AccountSubtypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AccountSubtype::class);

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
            ->where('is_active', true)
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        $types = AccountType::query()
            ->select(['id', 'uuid', 'name', 'account_group_id'])
            ->orderBy('name')
            ->get();

        $subtypes = AccountSubtype::query()
            ->join('account_types', 'account_subtypes.account_type_id', '=', 'account_types.id')
            ->join('account_groups', 'account_types.account_group_id', '=', 'account_groups.id')
            ->select([
                'account_subtypes.uuid as uuid',
                'account_subtypes.name as name',
                'account_subtypes.is_active as is_active',
                'account_subtypes.created_at as created_at',
                'account_types.uuid as type_uuid',
                'account_types.name as type_name',
                'account_groups.uuid as group_uuid',
                'account_groups.name as group_name',
                'account_groups.code as group_code',
            ])
            ->when($q !== '', function ($qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $code = ctype_digit($q) ? (int) $q : null;

                $qb->where(function ($w) use ($safe, $code) {
                    $w->where('account_subtypes.name', 'like', $safe . '%')
                        ->orWhere('account_types.name', 'like', $safe . '%')
                        ->orWhere('account_groups.name', 'like', $safe . '%')
                        ->when($code !== null, fn ($ww) => $ww->orWhere('account_groups.code', '=', $code));
                });
            })
            ->orderBy('account_groups.code')
            ->orderBy('account_groups.name')
            ->orderBy('account_types.name')
            ->orderBy('account_subtypes.name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Finance/ChartOfAccounts/AccountSubtypes/Index', [
            'subtypes' => AccountSubtypeIndexResource::collection($subtypes),
            'groups' => AccountGroupOptionResource::collection($groups)->resolve(),
            'types' => AccountTypeOptionResource::collection($types)->resolve(),
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function bulkUpsert(BulkUpsertAccountSubtypesRequest $request): RedirectResponse
    {
        $this->authorize('create', AccountSubtype::class);

        $validated = $request->validated();

        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        $type = AccountType::query()->where('uuid', $validated['account_type_uuid'])->first();
        if (! $type) {
            return back()->with('error', 'Invalid account type.');
        }

        try {
            DB::transaction(function () use ($validated, $type, $user): void {
                $rows = [];

                foreach (($validated['items'] ?? []) as $item) {
                    $uuid = isset($item['uuid']) ? trim((string) $item['uuid']) : '';
                    $name = NormalizesNames::normalize((string) $item['name']);

                    $rows[] = [
                        'uuid' => $uuid !== ''
                            ? $uuid
                            : (method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid()),
                        'account_type_id' => (int) $type->id,
                        'name' => $name,
                        'created_by' => (int) $user->id,
                        'is_active' => array_key_exists('is_active', $item) ? (bool) $item['is_active'] : true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];
                }

                if (count($rows) > 0) {
                    AccountSubtype::query()->upsert(
                        $rows,
                        ['uuid'],
                        ['account_type_id', 'name', 'is_active', 'updated_at']
                    );
                }
            });

            return back()->with('success', 'Account subtypes saved.');
        } catch (\Throwable $e) {
            Log::error('Account subtypes bulk upsert failed', ['exception' => $e]);
            return back()->with('error', 'Unable to save account subtypes. Please try again.');
        }
    }

    public function deactivate(Request $request, string $uuid): RedirectResponse
    {
        $subtype = AccountSubtype::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $subtype);

        $subtype->is_active = false;
        $subtype->save();

        return back()->with('success', 'Account subtype deactivated.');
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $subtype = AccountSubtype::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $subtype);

        $hasLedgers = Ledger::query()->where('account_subtype_id', $subtype->id)->exists();
        if ($hasLedgers) {
            return back()->with('error', 'Cannot delete this subtype because it has ledgers.');
        }

        $subtype->delete();
        return back()->with('success', 'Account subtype deleted.');
    }
}
