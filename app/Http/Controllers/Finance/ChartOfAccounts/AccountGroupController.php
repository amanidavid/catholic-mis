<?php

namespace App\Http\Controllers\Finance\ChartOfAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ChartOfAccounts\BulkUpsertAccountGroupsRequest;
use App\Models\Finance\AccountGroup;
use App\Models\Finance\AccountType;
use App\Traits\NormalizesNames;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AccountGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AccountGroup::class);

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';
        $perPage = (int) ($request->query('per_page') ?? 15);
        if ($perPage < 5) {
            $perPage = 5;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $groups = AccountGroup::query()
            ->select(['uuid', 'name', 'name_normalized', 'code', 'is_active', 'created_at'])
            ->when($q !== '', function ($qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $qb->where('name', 'like', $safe . '%');
            })
            ->orderBy('code')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Finance/ChartOfAccounts/AccountGroups/Index', [
            'groups' => $groups,
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function bulkUpsert(BulkUpsertAccountGroupsRequest $request): RedirectResponse
    {
        $this->authorize('create', AccountGroup::class);

        $validated = $request->validated();
        $user = $request->user();

        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            DB::transaction(function () use ($validated): void {
                $rows = [];
                $uuids = [];
                foreach (($validated['items'] ?? []) as $item) {
                    $uuid = isset($item['uuid']) ? trim((string) $item['uuid']) : '';
                    $name = NormalizesNames::normalize((string) $item['name']);
                    $nameNormalized = mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $name)), 'UTF-8');

                    $incomingCode = array_key_exists('code', $item) ? trim((string) $item['code']) : null;
                    $incomingCode = $incomingCode === '' ? null : $incomingCode;
                    $code = $incomingCode !== null ? (int) $incomingCode : null;

                    if ($uuid !== '' && ! array_key_exists('code', $item)) {
                        $existing = AccountGroup::query()->select(['code'])->where('uuid', $uuid)->first();
                        if (! $existing) {
                            throw new \RuntimeException('Invalid group UUID.');
                        }
                        $code = $existing->code !== null ? (int) $existing->code : null;
                    }

                    $rows[] = [
                        'uuid' => $uuid !== ''
                            ? $uuid
                            : (method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid()),
                        'name' => $name,
                        'name_normalized' => $nameNormalized,
                        'code' => $code,
                        'is_active' => array_key_exists('is_active', $item) ? (bool) $item['is_active'] : true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];
                    $uuids[] = $rows[array_key_last($rows)]['uuid'];
                }

                if (count($rows) > 0) {
                    $existing = AccountGroup::query()
                        ->whereIn('uuid', $uuids)
                        ->get()
                        ->keyBy('uuid');

                    AccountGroup::query()->upsert(
                        $rows,
                        ['uuid'],
                        ['name', 'name_normalized', 'code', 'is_active', 'updated_at']
                    );

                    $persisted = AccountGroup::query()
                        ->whereIn('uuid', $uuids)
                        ->get()
                        ->keyBy('uuid');

                    foreach ($persisted as $uuid => $group) {
                        $before = $existing->get($uuid);

                        if (! $before) {
                            $group->logCustomAudit('created', null, $group->getAttributes(), "Created account group {$group->name}");
                            continue;
                        }

                        $oldValues = $this->auditValues($before->getAttributes(), ['name', 'name_normalized', 'code', 'is_active']);
                        $newValues = $this->auditValues($group->getAttributes(), ['name', 'name_normalized', 'code', 'is_active']);

                        if ($oldValues !== $newValues) {
                            $group->logCustomAudit('updated', $oldValues, $newValues, "Updated account group {$group->name}");
                        }
                    }
                }
            });

            return back()->with('success', 'Account groups saved.');
        } catch (\Throwable $e) {
            Log::error('Account groups bulk upsert failed', ['exception' => $e]);
            return back()->with('error', 'Unable to save account groups. Please try again.');
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
        $group = AccountGroup::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $group);

        $group->is_active = false;
        $group->save();

        return back()->with('success', 'Account group deactivated.');
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $group = AccountGroup::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $group);

        $hasTypes = AccountType::query()->where('account_group_id', $group->id)->exists();
        if ($hasTypes) {
            return back()->with('error', 'Cannot delete this group because it has account types.');
        }

        $group->delete();
        return back()->with('success', 'Account group deleted.');
    }
}
