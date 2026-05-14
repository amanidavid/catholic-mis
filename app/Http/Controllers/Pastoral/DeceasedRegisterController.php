<?php

namespace App\Http\Controllers\Pastoral;

use App\Http\Controllers\Concerns\ResolvesSingleParishContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pastoral\IndexDeceasedRegisterRequest;
use App\Http\Requests\Pastoral\StoreDeceasedRegisterEntryRequest;
use App\Http\Requests\Pastoral\UpdateDeceasedRegisterEntryRequest;
use App\Http\Resources\Pastoral\DeceasedRegisterEntryResource;
use App\Models\Pastoral\DeceasedRegisterEntry;
use App\Models\People\Member;
use App\Services\People\MemberDeceasedStatusService;
use App\Traits\NormalizesNames;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DeceasedRegisterController extends Controller
{
    use ResolvesSingleParishContext;

    public function __construct(private readonly MemberDeceasedStatusService $memberDeceasedStatusService)
    {
        $this->middleware('auth');
    }

    public function index(IndexDeceasedRegisterRequest $request): Response
    {
        $this->authorize('viewAny', DeceasedRegisterEntry::class);

        $validated = $request->validated();
        $parishId = $this->resolveCurrentParishId($request->user());

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $dateFilter = is_string($validated['date_filter'] ?? null) ? trim((string) $validated['date_filter']) : 'all';
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 15);

        $namePrefix = $this->normalizedPrefixLike($q);

        $entries = DeceasedRegisterEntry::query()
            ->where('parish_id', $parishId)
            ->with([
                'member:id,uuid,first_name,middle_name,last_name,family_id,jumuiya_id',
                'member.family:id,uuid,family_name',
                'member.jumuiya:id,uuid,name',
                'recordedByUser:id,name',
            ])
            ->when($namePrefix, function (Builder $qb) use ($namePrefix) {
                $qb->whereHas('member', function (Builder $memberQ) use ($namePrefix) {
                    $memberQ->where('last_name', 'like', $namePrefix)
                        ->orWhere('first_name', 'like', $namePrefix)
                        ->orWhere('middle_name', 'like', $namePrefix);
                });
            });

        $this->applyDateFilter($entries, $dateFilter, $dateFrom, $dateTo);

        $entries = $entries
            ->orderByDesc('date_of_death')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Pastoral/DeceasedRegister/Index', [
            'filters' => [
                'q' => $q !== '' ? $q : null,
                'date_filter' => $dateFilter,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ],
            'entries' => DeceasedRegisterEntryResource::collection($entries),
            'can' => [
                'create' => $request->user()?->can('deceased-register.create') ?? false,
                'update' => $request->user()?->can('deceased-register.update') ?? false,
                'delete' => $request->user()?->can('deceased-register.delete') ?? false,
            ],
        ]);
    }

    public function create(\Illuminate\Http\Request $request): Response
    {
        $this->authorize('create', DeceasedRegisterEntry::class);

        return Inertia::render('Pastoral/DeceasedRegister/Create', [
            'defaults' => [
                'member_uuid' => '',
                'date_of_death' => '',
                'time_of_death' => '',
                'place_of_death' => '',
                'cause_of_death' => '',
                'death_certificate_number' => '',
                'hospital_or_health_facility' => '',
                'funeral_date' => '',
                'burial_date' => '',
                'burial_location_or_cemetery' => '',
                'funeral_mass_location' => '',
                'priest_or_celebrant_name' => '',
                'homily_or_remarks' => '',
                'notes' => '',
            ],
        ]);
    }

    public function store(StoreDeceasedRegisterEntryRequest $request): RedirectResponse
    {
        $this->authorize('create', DeceasedRegisterEntry::class);

        $validated = $request->validated();
        $parishId = $this->resolveCurrentParishId($request->user());

        $member = Member::query()
            ->where('uuid', $validated['member_uuid'])
            ->whereExists(function ($sub) use ($parishId) {
                $sub->selectRaw('1')
                    ->from('jumuiyas')
                    ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
                    ->whereColumn('jumuiyas.id', 'members.jumuiya_id')
                    ->where('zones.parish_id', $parishId);
            })
            ->first();

        if (! $member) {
            return back()->with('error', 'Invalid member selected for this parish.');
        }

        if (DeceasedRegisterEntry::query()->where('member_id', $member->id)->exists()) {
            return back()->with('error', 'Deceased record already exists for this member.');
        }

        try {
            DB::transaction(function () use ($request, $validated, $parishId, $member): void {
                $entry = DeceasedRegisterEntry::query()->create([
                    'parish_id' => $parishId,
                    'member_id' => (int) $member->id,
                    'date_of_death' => $validated['date_of_death'],
                    'time_of_death' => $this->normalizeTime($validated['time_of_death'] ?? null),
                    'place_of_death' => trim((string) $validated['place_of_death']),
                    'cause_of_death' => $validated['cause_of_death'] ?? null,
                    'death_certificate_number' => $validated['death_certificate_number'] ?? null,
                    'hospital_or_health_facility' => $validated['hospital_or_health_facility'] ?? null,
                    'funeral_date' => $validated['funeral_date'] ?? null,
                    'burial_date' => $validated['burial_date'] ?? null,
                    'burial_location_or_cemetery' => $validated['burial_location_or_cemetery'] ?? null,
                    'funeral_mass_location' => $validated['funeral_mass_location'] ?? null,
                    'priest_or_celebrant_name' => $validated['priest_or_celebrant_name'] ?? null,
                    'homily_or_remarks' => $validated['homily_or_remarks'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'recorded_by_user_id' => $request->user()?->id,
                    'updated_by_user_id' => $request->user()?->id,
                ]);

                $entry->load('member');
                $this->memberDeceasedStatusService->syncFromEntry($entry);
            });

            return back()->with('success', 'Deceased register entry saved successfully.');
        } catch (\Throwable $e) {
            Log::error('Failed to save deceased register entry', ['exception' => $e]);

            return back()->with('error', 'Unable to save deceased register entry. Please try again.');
        }
    }

    public function update(UpdateDeceasedRegisterEntryRequest $request, DeceasedRegisterEntry $deceasedRegisterEntry): RedirectResponse
    {
        $entry = DeceasedRegisterEntry::query()
            ->where('uuid', $deceasedRegisterEntry->uuid)
            ->with('member')
            ->firstOrFail();

        abort_unless((int) $entry->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        $this->authorize('update', $entry);

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($request, $entry, $validated): void {
                $entry->update([
                    'date_of_death' => $validated['date_of_death'],
                    'time_of_death' => $this->normalizeTime($validated['time_of_death'] ?? null),
                    'place_of_death' => trim((string) $validated['place_of_death']),
                    'cause_of_death' => $validated['cause_of_death'] ?? null,
                    'death_certificate_number' => $validated['death_certificate_number'] ?? null,
                    'hospital_or_health_facility' => $validated['hospital_or_health_facility'] ?? null,
                    'funeral_date' => $validated['funeral_date'] ?? null,
                    'burial_date' => $validated['burial_date'] ?? null,
                    'burial_location_or_cemetery' => $validated['burial_location_or_cemetery'] ?? null,
                    'funeral_mass_location' => $validated['funeral_mass_location'] ?? null,
                    'priest_or_celebrant_name' => $validated['priest_or_celebrant_name'] ?? null,
                    'homily_or_remarks' => $validated['homily_or_remarks'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by_user_id' => $request->user()?->id,
                ]);

                $this->memberDeceasedStatusService->syncFromEntry($entry);
            });

            return back()->with('success', 'Deceased register entry updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Failed to update deceased register entry', ['exception' => $e, 'uuid' => $entry->uuid]);

            return back()->with('error', 'Unable to update deceased register entry. Please try again.');
        }
    }

    public function edit(\Illuminate\Http\Request $request, DeceasedRegisterEntry $deceasedRegisterEntry): Response
    {
        $entry = DeceasedRegisterEntry::query()
            ->where('uuid', $deceasedRegisterEntry->uuid)
            ->with([
                'member:id,uuid,first_name,middle_name,last_name,family_id,jumuiya_id',
                'member.family:id,uuid,family_name',
                'member.jumuiya:id,uuid,name',
            ])
            ->firstOrFail();

        abort_unless((int) $entry->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        $this->authorize('update', $entry);

        return Inertia::render('Pastoral/DeceasedRegister/Edit', [
            'entry' => (new DeceasedRegisterEntryResource($entry))->resolve(),
        ]);
    }

    public function destroy(\Illuminate\Http\Request $request, DeceasedRegisterEntry $deceasedRegisterEntry): RedirectResponse
    {
        $entry = DeceasedRegisterEntry::query()
            ->where('uuid', $deceasedRegisterEntry->uuid)
            ->with('member')
            ->firstOrFail();

        abort_unless((int) $entry->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        $this->authorize('delete', $entry);

        try {
            DB::transaction(function () use ($entry): void {
                $this->memberDeceasedStatusService->clearFromEntry($entry);
                $entry->delete();
            });

            return back()->with('success', 'Deceased register entry deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('Failed to delete deceased register entry', ['exception' => $e, 'uuid' => $entry->uuid]);

            return back()->with('error', 'Unable to delete deceased register entry. Please try again.');
        }
    }

    private function applyDateFilter(Builder $query, string $filter, mixed $dateFrom, mixed $dateTo): void
    {
        if ($filter === 'today') {
            $query->where('date_of_death', now()->toDateString());
            return;
        }

        if ($filter === 'this_week') {
            $query->whereBetween('date_of_death', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()]);
            return;
        }

        if ($filter === 'this_month') {
            $query->whereBetween('date_of_death', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);
            return;
        }

        if ($filter === 'custom') {
            if (is_string($dateFrom) && trim($dateFrom) !== '') {
                $query->where('date_of_death', '>=', trim($dateFrom));
            }
            if (is_string($dateTo) && trim($dateTo) !== '') {
                $query->where('date_of_death', '<=', trim($dateTo));
            }
        }
    }

    private function normalizeTime(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return preg_match('/^\d{2}:\d{2}$/', $value) === 1 ? $value.':00' : null;
    }

    private function normalizedPrefixLike(?string $value): ?string
    {
        $normalized = NormalizesNames::normalize(is_string($value) ? $value : null);
        $normalized = $normalized !== null ? mb_strtolower($normalized, 'UTF-8') : '';
        if ($normalized === '') {
            return null;
        }

        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $normalized).'%';
    }
}
