<?php

namespace App\Http\Controllers\Finance\ChartOfAccounts;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\ChartOfAccounts\AccountGroupOptionResource;
use App\Http\Resources\Finance\ChartOfAccounts\AccountTypeIndexResource;
use App\Http\Requests\Finance\ChartOfAccounts\BulkUpsertAccountTypesRequest;
use App\Models\Finance\AccountGroup;
use App\Models\Finance\AccountSubtype;
use App\Models\Finance\AccountType;
use App\Traits\NormalizesNames;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AccountTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AccountType::class);

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
            ->join('account_groups', 'account_groups.id', '=', 'account_types.account_group_id')
            ->select([
                'account_types.id',
                'account_types.uuid',
                'account_types.name',
                'account_types.account_group_id',
                'account_types.is_active',
                'account_types.created_at',
                'account_groups.uuid as group_uuid',
                'account_groups.name as group_name',
                'account_groups.code as group_code',
            ])
            ->when($q !== '', function ($qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $code = ctype_digit($q) ? (int) $q : null;

                $qb->where(function ($w) use ($safe, $code) {
                    $w->where('account_types.name', 'like', $safe . '%')
                        ->orWhere('account_groups.name', 'like', $safe . '%')
                        ->when($code !== null, fn ($ww) => $ww->orWhere('account_groups.code', '=', $code));
                });
            })
            ->orderBy('account_groups.code')
            ->orderBy('account_groups.name')
            ->orderBy('account_types.name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Finance/ChartOfAccounts/AccountTypes/Index', [
            'types' => AccountTypeIndexResource::collection($types),
            'groups' => AccountGroupOptionResource::collection($groups)->resolve(),
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function bulkUpsert(BulkUpsertAccountTypesRequest $request): RedirectResponse
    {
        $this->authorize('create', AccountType::class);

        $validated = $request->validated();

        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        $group = AccountGroup::query()->where('uuid', $validated['account_group_uuid'])->first();
        if (! $group) {
            return back()->with('error', 'Invalid account group.');
        }

        try {
            DB::transaction(function () use ($validated, $group, $user): void {
                $rows = [];
                $uuids = [];

                foreach (($validated['items'] ?? []) as $item) {
                    $uuid = isset($item['uuid']) ? trim((string) $item['uuid']) : '';
                    $name = NormalizesNames::normalize((string) $item['name']);

                    $rows[] = [
                        'uuid' => $uuid !== ''
                            ? $uuid
                            : (method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid()),
                        'account_group_id' => (int) $group->id,
                        'name' => $name,
                        'created_by' => (int) $user->id,
                        'is_active' => array_key_exists('is_active', $item) ? (bool) $item['is_active'] : true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];
                    $uuids[] = $rows[array_key_last($rows)]['uuid'];
                }

                if (count($rows) > 0) {
                    $existing = AccountType::query()
                        ->whereIn('uuid', $uuids)
                        ->get()
                        ->keyBy('uuid');

                    AccountType::query()->upsert(
                        $rows,
                        ['uuid'],
                        ['account_group_id', 'name', 'is_active', 'updated_at']
                    );

                    $persisted = AccountType::query()
                        ->whereIn('uuid', $uuids)
                        ->get()
                        ->keyBy('uuid');

                    foreach ($persisted as $uuid => $type) {
                        $before = $existing->get($uuid);

                        if (! $before) {
                            $type->logCustomAudit('created', null, $type->getAttributes(), "Created account type {$type->name}");
                            continue;
                        }

                        $oldValues = $this->auditValues($before->getAttributes(), ['account_group_id', 'name', 'is_active']);
                        $newValues = $this->auditValues($type->getAttributes(), ['account_group_id', 'name', 'is_active']);

                        if ($oldValues !== $newValues) {
                            $type->logCustomAudit('updated', $oldValues, $newValues, "Updated account type {$type->name}");
                        }
                    }
                }
            });

            return back()->with('success', 'Account types saved.');
        } catch (\Throwable $e) {
            Log::error('Account types bulk upsert failed', ['exception' => $e]);
            return back()->with('error', 'Unable to save account types. Please try again.');
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
        $type = AccountType::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $type);

        $type->is_active = false;
        $type->save();

        return back()->with('success', 'Account type deactivated.');
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $type = AccountType::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $type);

        $hasSubtypes = AccountSubtype::query()->where('account_type_id', $type->id)->exists();
        if ($hasSubtypes) {
            return back()->with('error', 'Cannot delete this type because it has account subtypes.');
        }

        $type->delete();
        return back()->with('success', 'Account type deleted.');
    }
}
