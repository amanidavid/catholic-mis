<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\People\Family;
use App\Models\People\FamilyRelationship;
use App\Models\People\Member;
use App\Models\Structure\Jumuiya;
use App\Models\Structure\Zone;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, AuditLogService $auditLogs): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $q = $request->query('q');
        $modelType = $request->query('model_type');
        $action = $request->query('action');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $perPage = (int) ($request->query('per_page') ?? 20);

        $q = is_string($q) ? trim($q) : '';
        $modelType = is_string($modelType) ? trim($modelType) : '';
        $action = is_string($action) ? trim($action) : '';
        $fromDate = is_string($fromDate) ? trim($fromDate) : '';
        $toDate = is_string($toDate) ? trim($toDate) : '';

        $from = null;
        $to = null;
        try {
            if ($fromDate !== '') {
                $from = Carbon::parse($fromDate)->startOfDay();
            }
        } catch (\Throwable $e) {
            $from = null;
        }

        try {
            if ($toDate !== '') {
                $to = Carbon::parse($toDate)->endOfDay();
            }
        } catch (\Throwable $e) {
            $to = null;
        }

        $perPage = max(5, min(100, $perPage));

        $logs = $auditLogs->getFilteredAuditLogs([
            'search' => $q !== '' ? $q : null,
            'model_type' => $modelType !== '' ? $modelType : null,
            'action' => $action !== '' ? $action : null,
            'from_date' => $from,
            'to_date' => $to,
        ], $perPage);

        $items = collect($logs->items());

        $fkMaps = $this->buildForeignKeyMaps($items);

        $rows = $items->map(function (AuditLog $log) use ($fkMaps) {
            $old = is_array($log->old_values) ? $log->old_values : [];
            $new = is_array($log->new_values) ? $log->new_values : [];

            return [
                'created_at' => $log->created_at?->toDateTimeString(),
                'model' => $this->modelLabel($log->model_type),
                'action' => $log->action,
                'changed_by' => $log->relationLoaded('changedBy') && $log->changedBy ? ($log->changedBy->email ?? null) : null,
                'changed_by_phone' => $log->relationLoaded('changedBy') && $log->changedBy
                    ? ($log->changedBy->member?->phone ?? null)
                    : null,
                'description' => $log->description,
                'changes' => $this->prettyDiff($old, $new, $fkMaps),
            ];
        })->values();

        return Inertia::render('AuditLogs/Index', [
            'filters' => [
                'q' => $q,
                'model_type' => $modelType,
                'action' => $action,
                'from_date' => $from ? $from->toDateString() : ($fromDate !== '' ? $fromDate : ''),
                'to_date' => $to ? $to->toDateString() : ($toDate !== '' ? $toDate : ''),
                'per_page' => $perPage,
            ],
            'logs' => [
                'data' => $rows,
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'from' => $logs->firstItem(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'to' => $logs->lastItem(),
                    'total' => $logs->total(),
                    'links' => $logs->linkCollection(),
                ],
            ],
            'modelTypes' => $this->modelTypeOptionsDynamic(),
            'actions' => $this->actionOptionsDynamic(),
        ]);
    }

    protected function modelTypeOptionsDynamic(): array
    {
        $types = AuditLog::query()
            ->withoutGlobalScopes()
            ->select('model_type')
            ->distinct()
            ->orderBy('model_type')
            ->pluck('model_type')
            ->filter(fn ($t) => is_string($t) && trim($t) !== '')
            ->values();

        return $types->map(function (string $t) {
            return ['value' => $t, 'label' => $this->modelLabel($t)];
        })->all();
    }

    protected function actionOptionsDynamic(): array
    {
        return AuditLog::query()
            ->withoutGlobalScopes()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->filter(fn ($a) => is_string($a) && trim($a) !== '')
            ->values()
            ->all();
    }

    protected function modelLabel(?string $modelType): string
    {
        if ($modelType === Member::class) return 'Member';
        if ($modelType === Family::class) return 'Family';
        if ($modelType === Jumuiya::class) return 'Christian Community';

        if (! is_string($modelType) || $modelType === '') return 'Unknown';
        $parts = explode('\\', $modelType);
        return (string) end($parts);
    }

    protected function buildForeignKeyMaps($items): array
    {
        $jumuiyaIds = [];
        $familyIds = [];
        $memberIds = [];
        $familyRelationshipIds = [];
        $zoneIds = [];
        $userIds = [];

        foreach ($items as $log) {
            if (! $log instanceof AuditLog) continue;

            foreach (['old_values', 'new_values'] as $field) {
                $vals = $log->{$field};
                if (! is_array($vals)) continue;

                foreach ($vals as $k => $v) {
                    if (is_array($v) || is_object($v) || is_bool($v) || $v === null) {
                        continue;
                    }

                    if (! is_int($v) && ! (is_string($v) && ctype_digit($v))) {
                        continue;
                    }

                    $id = (int) $v;

                    if ($k === 'jumuiya_id') $jumuiyaIds[] = $id;
                    if ($k === 'family_id') $familyIds[] = $id;
                    if ($k === 'head_of_family_member_id') $memberIds[] = $id;
                    if ($k === 'family_relationship_id') $familyRelationshipIds[] = $id;
                    if ($k === 'zone_id') $zoneIds[] = $id;
                    if ($this->isUserReferenceField($k)) $userIds[] = $id;
                }
            }
        }

        $jumuiyaIds = array_values(array_unique(array_filter($jumuiyaIds)));
        $familyIds = array_values(array_unique(array_filter($familyIds)));
        $memberIds = array_values(array_unique(array_filter($memberIds)));
        $familyRelationshipIds = array_values(array_unique(array_filter($familyRelationshipIds)));
        $zoneIds = array_values(array_unique(array_filter($zoneIds)));
        $userIds = array_values(array_unique(array_filter($userIds)));

        $jumuiyas = Jumuiya::query()->whereIn('id', $jumuiyaIds)->get(['id', 'name']);
        $families = Family::query()->whereIn('id', $familyIds)->get(['id', 'family_name']);
        $members = Member::query()->whereIn('id', $memberIds)->get(['id', 'first_name', 'middle_name', 'last_name']);
        $relationships = FamilyRelationship::query()->whereIn('id', $familyRelationshipIds)->get(['id', 'name']);
        $zones = Zone::query()->whereIn('id', $zoneIds)->get(['id', 'name']);
        $users = User::query()->whereIn('id', $userIds)->get(['id', 'name', 'email']);

        $memberNameMap = $members->mapWithKeys(function (Member $m) {
            $name = trim(implode(' ', array_filter([$m->first_name, $m->middle_name, $m->last_name])));
            return [$m->id => ($name !== '' ? $name : (string) $m->id)];
        })->all();

        $userMap = $users->mapWithKeys(function (User $user) {
            $name = is_string($user->name ?? null) ? trim((string) $user->name) : '';
            $email = is_string($user->email ?? null) ? trim((string) $user->email) : '';
            $label = $name !== '' && $email !== '' ? "{$name} ({$email})" : ($email !== '' ? $email : ($name !== '' ? $name : (string) $user->id));

            return [$user->id => $label];
        })->all();

        return [
            'jumuiya_id' => $jumuiyas->pluck('name', 'id')->all(),
            'family_id' => $families->pluck('family_name', 'id')->all(),
            'head_of_family_member_id' => $memberNameMap,
            'family_relationship_id' => $relationships->pluck('name', 'id')->all(),
            'zone_id' => $zones->pluck('name', 'id')->all(),
            '__users' => $userMap,
        ];
    }

    protected function prettyDiff(array $old, array $new, array $fkMaps): array
    {
        $keys = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
        sort($keys);

        $rows = [];

        foreach ($keys as $k) {
            if ($this->isHiddenField($k)) {
                continue;
            }

            $from = array_key_exists($k, $old) ? $old[$k] : null;
            $to = array_key_exists($k, $new) ? $new[$k] : null;

            if ($from === $to) {
                continue;
            }

            $rows[] = [
                'field' => $this->fieldLabel($k),
                'from' => $this->humanValue($k, $from, $fkMaps),
                'to' => $this->humanValue($k, $to, $fkMaps),
            ];
        }

        return $rows;
    }

    protected function fieldLabel(string $field): string
    {
        $map = [
            'jumuiya_id' => 'Christian Community',
            'zone_id' => 'Zone',
            'family_id' => 'Family',
            'family_name' => 'Family name',
            'family_code' => 'Family code',
            'head_of_family_member_id' => 'Head of family',
            'family_relationship_id' => 'Family relationship',
            'first_name' => 'First name',
            'middle_name' => 'Middle name',
            'last_name' => 'Last name',
            'phone' => 'Phone number',
            'email' => 'Email',
            'national_id' => 'National ID',
            'birth_date' => 'Birth date',
            'marital_status' => 'Marital status',
            'gender' => 'Gender',
            'is_active' => 'Status',
            'created_at' => 'Created at',
            'updated_at' => 'Updated at',
            'created_by' => 'Created by',
            'updated_by' => 'Updated by',
            'submitted_by' => 'Submitted by',
            'approved_by' => 'Approved by',
            'rejected_by' => 'Rejected by',
            'cancelled_by' => 'Cancelled by',
            'posted_by' => 'Posted by',
            'changed_by' => 'Changed by',
            'line_count' => 'Number of lines',
            'entry_count' => 'Number of entries',
            'voucher_uuid' => 'Voucher reference',
            'journal_uuid' => 'Journal reference',
            'bank_account_transaction_uuid' => 'Bank transaction reference',
            'replenishment_uuid' => 'Replenishment reference',
            'journal_no' => 'Journal number',
            'voucher_no' => 'Voucher number',
            'replenishment_no' => 'Replenishment number',
            'transaction_type' => 'Transaction type',
            'is_manual_override' => 'Manual override',
            'lines' => 'Lines',
            'entries' => 'Entries',
            'attachments' => 'Attachments',
        ];

        return $map[$field] ?? str_replace('_', ' ', $field);
    }

    protected function humanValue(string $field, $value, array $fkMaps): string
    {
        if ($value === null) return '-';

        if (is_array($value)) {
            return $this->humanArrayValue($field, $value);
        }

        if (is_object($value)) {
            return $this->truncateDisplayValue(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Object');
        }

        if ($this->isDateField($field)) {
            $dt = $this->tryParseDateTime($value);
            return $dt ? $dt->format('Y-m-d H:i:s') : '-';
        }

        if ($this->isDateOnlyField($field)) {
            $dt = $this->tryParseDateTime($value);
            return $dt ? $dt->format('Y-m-d') : '-';
        }

        if ($this->isUserReferenceField($field)) {
            $id = is_int($value) ? $value : (is_string($value) && ctype_digit($value) ? (int) $value : null);
            if ($id !== null && array_key_exists($id, $fkMaps['__users'] ?? [])) {
                return (string) $fkMaps['__users'][$id];
            }

            return is_scalar($value) ? trim((string) $value) : '-';
        }

        if (array_key_exists($field, $fkMaps)) {
            $id = is_int($value) ? $value : (ctype_digit((string) $value) ? (int) $value : null);
            if ($id !== null && array_key_exists($id, $fkMaps[$field])) {
                return (string) $fkMaps[$field][$id];
            }

            return '-';
        }

        if ($field === 'is_active') {
            return (bool) $value ? 'Active' : 'Inactive';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_scalar($value)) {
            $str = trim((string) $value);
            return $str === '' ? '-' : $str;
        }

        return 'Updated';
    }

    protected function humanArrayValue(string $field, array $value): string
    {
        if ($value === []) {
            return '[]';
        }

        if (in_array($field, ['lines', 'entries', 'attachments'], true)) {
            $count = count($value);
            $label = match ($field) {
                'lines' => 'line',
                'entries' => 'entry',
                'attachments' => 'attachment',
                default => 'item',
            };

            $sample = array_slice(array_map(function ($item) {
                if (! is_array($item)) {
                    return $item;
                }

                $formatted = [];
                foreach ($item as $key => $itemValue) {
                    if (in_array($key, ['uuid', 'voucher_uuid', 'journal_uuid', 'bank_account_transaction_uuid', 'replenishment_uuid'], true)) {
                        continue;
                    }

                    $formatted[$this->fieldLabel((string) $key)] = is_scalar($itemValue) || $itemValue === null
                        ? $itemValue
                        : '[structured]';
                }

                return $formatted;
            }, $value), 0, 2);

            return $this->truncateDisplayValue(sprintf(
                '%d %s%s%s',
                $count,
                $label,
                $count === 1 ? '' : 's',
                $sample !== [] ? ': '.(json_encode($sample, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]') : ''
            ));
        }

        if ($this->isListArray($value)) {
            $count = count($value);
            $sample = array_slice($value, 0, 2);

            return $this->truncateDisplayValue(sprintf(
                '%s item(s): %s',
                $count,
                json_encode($sample, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]'
            ));
        }

        return $this->truncateDisplayValue(
            json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
        );
    }

    protected function isListArray(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }

    protected function truncateDisplayValue(string $value, int $limit = 220): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));
        $normalized = is_string($normalized) ? $normalized : $value;

        return Str::limit($normalized, $limit);
    }

    protected function isUserReferenceField(string $field): bool
    {
        $field = strtolower($field);

        return $field === 'changed_by' || str_ends_with($field, '_by');
    }

    protected function isHiddenField(string $field): bool
    {
        $field = strtolower($field);

        return in_array($field, [
            'id',
            'uuid',
            'password',
            'remember_token',
        ], true);
    }

    protected function isDateField(string $field): bool
    {
        return str_ends_with($field, '_at');
    }

    protected function isDateOnlyField(string $field): bool
    {
        return str_ends_with($field, '_date');
    }

    protected function tryParseDateTime($value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_int($value)) {
            return Carbon::createFromTimestamp($value);
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
